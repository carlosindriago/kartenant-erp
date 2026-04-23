<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRITICAL SECURITY MIDDLEWARE - Tenant Isolation Enforcement
 *
 * This middleware implements ZERO-TRUST validation for tenant isolation:
 * 1. Validates authenticated user belongs to current tenant
 * 2. Detects and prevents tenant context switching attacks
 * 3. Forces explicit landlord database queries
 * 4. Provides comprehensive audit logging
 * 5. Blocks unauthorized cross-tenant access attempts
 *
 * CRITICAL: This middleware runs on EVERY protected tenant route
 * to ensure absolute isolation between tenants.
 */
final class EnforceTenantIsolation
{
    /**
     * Handle an incoming request with CRITICAL SECURITY VALIDATION
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip isolation checks for guest routes
        if (! Auth::guard('tenant')->check()) {
            return $next($request);
        }

        $user = Auth::guard('tenant')->user();
        $currentTenant = tenant();

        // CRITICAL SECURITY: Validate tenant context exists
        if (! $currentTenant) {
            $this->logCriticalSecurityEvent('no_tenant_context', $request, $user, null);

            return $this->denyAccess('Contexto de tenant no válido', $request);
        }

        // CRITICAL SECURITY: Validate tenant is active
        if (! $currentTenant->isActive()) {
            $this->logCriticalSecurityEvent('inactive_tenant_access', $request, $user, $currentTenant);

            return $this->denyAccess('Tenant no activo', $request);
        }

        // CRITICAL SECURITY: Validate user belongs to current tenant
        if (! $this->validateTenantMembershipSecure($user, $currentTenant)) {
            $this->logCriticalSecurityEvent('unauthorized_tenant_access', $request, $user, $currentTenant);

            // Immediate logout to prevent session persistence
            Auth::guard('tenant')->logout();

            return $this->denyAccess('Acceso no autorizado a este tenant', $request, 403);
        }

        // CRITICAL SECURITY: Validate session integrity
        if (! $this->validateSessionIntegrity($request, $user, $currentTenant)) {
            $this->logCriticalSecurityEvent('session_tampering_detected', $request, $user, $currentTenant);

            // Logout and invalidate session
            Auth::guard('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->denyAccess('Sesión no válida', $request, 419);
        }

        // CRITICAL SECURITY: Validate tenant context hasn't been switched
        if ($this->detectTenantContextSwitch($request, $user, $currentTenant)) {
            $this->logCriticalSecurityEvent('tenant_context_switch_detected', $request, $user, $currentTenant);

            // Logout and invalidate session
            Auth::guard('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->denyAccess('Cambio de contexto detectado', $request, 423);
        }

        // SECURITY: Update session markers
        $this->updateSecurityMarkers($request, $user, $currentTenant);

        // SECURITY: Log successful access
        $this->logAuthorizedAccess($request, $user, $currentTenant);

        return $next($request);
    }

    /**
     * CRITICAL SECURITY: Validate user-tenant membership with landlord DB
     */
    private function validateTenantMembershipSecure(User $user, Tenant $currentTenant): bool
    {
        try {
            // Force query to landlord database to prevent tenant context contamination
            $membership = DB::connection('landlord')
                ->table('tenant_user')
                ->where('user_id', $user->id)
                ->where('tenant_id', $currentTenant->id)
                ->exists();

            if (! $membership) {
                Log::critical('SECURITY BREACH: User without tenant membership attempted access', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempted_tenant_id' => $currentTenant->id,
                    'attempted_tenant_domain' => $currentTenant->domain,
                    'timestamp' => now()->toISOString(),
                ]);

                return false;
            }

            // Double-check user is active
            $userActive = DB::connection('landlord')
                ->table('users')
                ->where('id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (! $userActive) {
                Log::critical('SECURITY BREACH: Inactive user attempted access', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempted_tenant_id' => $currentTenant->id,
                    'attempted_tenant_domain' => $currentTenant->domain,
                    'timestamp' => now()->toISOString(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::critical('SECURITY ERROR: Tenant membership validation failed', [
                'user_id' => $user->id,
                'tenant_id' => $currentTenant->id,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);

            // Fail secure - deny access on validation errors
            return false;
        }
    }

    /**
     * CRITICAL SECURITY: Validate session integrity
     */
    private function validateSessionIntegrity(Request $request, User $user, Tenant $currentTenant): bool
    {
        try {
            $sessionUserId = $request->session()->get('secure_user_id');
            $sessionTenantId = $request->session()->get('secure_tenant_id');
            $authenticatedAt = $request->session()->get('authenticated_at');

            // Validate session markers exist
            if ($sessionUserId !== $user->id) {
                Log::warning('Session integrity violation: user ID mismatch', [
                    'session_user_id' => $sessionUserId,
                    'auth_user_id' => $user->id,
                    'timestamp' => now()->toISOString(),
                ]);

                return false;
            }

            if ($sessionTenantId !== $currentTenant->id) {
                Log::warning('Session integrity violation: tenant ID mismatch', [
                    'session_tenant_id' => $sessionTenantId,
                    'current_tenant_id' => $currentTenant->id,
                    'timestamp' => now()->toISOString(),
                ]);

                return false;
            }

            // Validate authentication timestamp is reasonable
            if (! $authenticatedAt || (time() - $authenticatedAt) > (24 * 60 * 60)) {
                Log::warning('Session integrity violation: expired authentication', [
                    'authenticated_at' => $authenticatedAt,
                    'current_time' => time(),
                    'timestamp' => now()->toISOString(),
                ]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Session integrity validation error', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);

            // Fail secure - deny access on validation errors
            return false;
        }
    }

    /**
     * CRITICAL SECURITY: Detect tenant context switching attacks
     */
    private function detectTenantContextSwitch(Request $request, User $user, Tenant $currentTenant): bool
    {
        try {
            $previousTenantId = $request->session()->get('previous_secure_tenant_id');
            $previousTenantDomain = $request->session()->get('previous_secure_tenant_domain');

            // First access - establish baseline
            if (! $previousTenantId) {
                $request->session()->put('previous_secure_tenant_id', $currentTenant->id);
                $request->session()->put('previous_secure_tenant_domain', $currentTenant->domain);

                return false;
            }

            // Check if tenant context has changed
            if ($previousTenantId !== $currentTenant->id) {
                // This could be legitimate (user switching subdomains) or malicious
                // Let's check if user belongs to both tenants
                $userBelongsToPrevious = DB::connection('landlord')
                    ->table('tenant_user')
                    ->where('user_id', $user->id)
                    ->where('tenant_id', $previousTenantId)
                    ->exists();

                $userBelongsToCurrent = DB::connection('landlord')
                    ->table('tenant_user')
                    ->where('user_id', $user->id)
                    ->where('tenant_id', $currentTenant->id)
                    ->exists();

                if (! $userBelongsToCurrent) {
                    // User doesn't belong to current tenant - definitely malicious
                    return true;
                }

                if ($userBelongsToPrevious) {
                    // User belongs to both tenants - legitimate context switch
                    $request->session()->put('previous_secure_tenant_id', $currentTenant->id);
                    $request->session()->put('previous_secure_tenant_domain', $currentTenant->domain);

                    return false;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Tenant context switch detection error', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);

            // Fail secure - deny access on detection errors
            return true;
        }
    }

    /**
     * SECURITY: Update security markers in session
     */
    private function updateSecurityMarkers(Request $request, User $user, Tenant $currentTenant): void
    {
        $request->session()->put('secure_user_id', $user->id);
        $request->session()->put('secure_tenant_id', $currentTenant->id);
        $request->session()->put('secure_tenant_domain', $currentTenant->domain);
        $request->session()->put('last_activity', now()->timestamp);

        // Update previous tenant markers for context switch detection
        if ($request->session()->get('secure_tenant_id') !== $currentTenant->id) {
            $request->session()->put('previous_secure_tenant_id', $request->session()->get('secure_tenant_id'));
            $request->session()->put('previous_secure_tenant_domain', $request->session()->get('secure_tenant_domain'));
        }
    }

    /**
     * CRITICAL SECURITY: Log security breach attempts
     */
    private function logCriticalSecurityEvent(string $eventType, Request $request, User $user, ?Tenant $tenant): void
    {
        Log::critical('CRITICAL SECURITY EVENT: '.$eventType, [
            'event_type' => $eventType,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $tenant?->id,
            'tenant_domain' => $tenant?->domain,
            'request_host' => $request->getHost(),
            'request_path' => $request->path(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'timestamp' => now()->toISOString(),

            // Additional context for investigation
            'session_data' => [
                'secure_user_id' => $request->session()->get('secure_user_id'),
                'secure_tenant_id' => $request->session()->get('secure_tenant_id'),
                'authenticated_at' => $request->session()->get('authenticated_at'),
                'last_activity' => $request->session()->get('last_activity'),
            ],

            // Request headers for investigation
            'request_headers' => collect($request->headers->all())
                ->only(['user-agent', 'referer', 'origin', 'x-forwarded-for'])
                ->toArray(),
        ]);
    }

    /**
     * SECURITY: Log authorized access for audit
     */
    private function logAuthorizedAccess(Request $request, User $user, Tenant $currentTenant): void
    {
        // Only log every 10th request to reduce noise, but log sensitive paths always
        $shouldLog = $request->isMethod('POST') ||
                     str_starts_with($request->path(), 'tenant/settings') ||
                     str_starts_with($request->path(), 'tenant/admin') ||
                     (random_int(1, 10) === 1);

        if (! $shouldLog) {
            return;
        }

        Log::info('AUTHORIZED: Tenant access granted', [
            'user_id' => $user->id,
            'tenant_id' => $currentTenant->id,
            'tenant_domain' => $currentTenant->domain,
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'request_ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * SECURITY: Deny access with appropriate response
     */
    private function denyAccess(string $message, Request $request, int $statusCode = 403): Response
    {
        // For API requests, return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'unauthorized_tenant_access',
                'timestamp' => now()->toISOString(),
            ], $statusCode);
        }

        // For web requests, show error page or redirect to login
        if (in_array($statusCode, [419, 423])) {
            // Session-related errors - redirect to login
            return redirect()->route('tenant.login')
                ->with('error', $message);
        }

        // Other errors - show error page
        return response()->view('errors.tenant-unauthorized', [
            'message' => $message,
            'tenant' => tenant(),
        ], $statusCode);
    }
}
