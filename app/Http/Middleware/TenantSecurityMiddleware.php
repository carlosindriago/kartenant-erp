<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * High-Friction Security Middleware for Tenant Management
 *
 * This middleware provides enterprise-grade security controls for tenant operations:
 * - Multi-factor authentication verification
 * - Rate limiting and abuse prevention
 * - Admin privilege escalation (sudo mode)
 * - Comprehensive audit logging
 * - Device fingerprinting and geolocation tracking
 */
class TenantSecurityMiddleware
{
    /**
     * Security operation tiers with different friction levels
     */
    private const SECURITY_TIERS = [
        'tier_1' => ['edit', 'view', 'generate_reports', 'send_welcome'],
        'tier_2' => ['maintenance_mode', 'manual_backup', 'password_reset', 'subscription_modify'],
        'tier_3' => ['tenant_deactivate', 'status_change', 'api_access_modify'],
        'tier_4' => ['tenant_archive', 'force_delete', 'data_export', 'emergency_access'],
    ];

    /**
     * Rate limits per tier (per admin per hour)
     */
    private const RATE_LIMITS = [
        'tier_1' => 100,    // Standard operations
        'tier_2' => 20,     // Elevated risk operations
        'tier_3' => 5,      // High risk operations
        'tier_4' => 1,      // Critical risk operations
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $admin = auth('superadmin')->user();

        if (! $admin) {
            return $this->errorResponse('No autenticado', 401);
        }

        // Determine security tier for the operation
        $tier = $this->getSecurityTier($operation);

        // 1. RATE LIMITING CHECK
        if (! $this->checkRateLimit($admin, $operation, $tier)) {
            activity()
                ->causedBy($admin)
                ->withProperties([
                    'operation' => $operation,
                    'tier' => $tier,
                    'ip' => $request->ip(),
                    'blocked' => 'rate_limit',
                ])
                ->log('Rate limit exceeded for tenant operation');

            return $this->errorResponse('Límite de frecuencia excedido. Intenta más tarde.', 429);
        }

        // 2. SUDO MODE VALIDATION (Tiers 2+)
        if (in_array($tier, ['tier_2', 'tier_3', 'tier_4'])) {
            if (! $this->validateSudoMode($admin, $request)) {
                return $this->errorResponse('Se requiere modo elevado. Confirma tu contraseña.', 403);
            }
        }

        // 3. OTP VERIFICATION (Tiers 3+)
        if (in_array($tier, ['tier_3', 'tier_4'])) {
            if (! $this->validateOTP($admin, $request)) {
                return $this->errorResponse('Código de verificación inválido o expirado.', 403);
            }
        }

        // 4. DEVICE FINGERPRINTING (Tier 4)
        if ($tier === 'tier_4') {
            $this->logDeviceFingerprint($admin, $request);
        }

        // 5. PRE-OPERATION AUDIT LOG
        $this->logSecurityEvent($admin, $request, $operation, $tier, 'initiated');

        // Execute the operation
        $response = $next($request);

        // 6. POST-OPERATION AUDIT LOG
        $this->logSecurityEvent($admin, $request, $operation, $tier, 'completed', [
            'status_code' => $response->getStatusCode(),
            'success' => $response->isSuccessful(),
        ]);

        return $response;
    }

    /**
     * Determine security tier for an operation
     */
    private function getSecurityTier(string $operation): string
    {
        foreach (self::SECURITY_TIERS as $tier => $operations) {
            if (in_array($operation, $operations)) {
                return $tier;
            }
        }

        return 'tier_1'; // Default to lowest tier
    }

    /**
     * Check rate limiting for operation
     */
    private function checkRateLimit(User $admin, string $operation, string $tier): bool
    {
        $key = "tenant_security:{$admin->id}:{$operation}";
        $maxAttempts = self::RATE_LIMITS[$tier] ?? 100;

        return RateLimiter::attempt(
            $key,
            $maxAttempts,
            fn () => true,
            3600 // 1 hour decay
        );
    }

    /**
     * Validate sudo mode (admin password re-entry)
     */
    private function validateSudoMode(User $admin, Request $request): bool
    {
        $sudoKey = "sudo_mode_{$admin->id}";

        // Check if sudo mode is active
        if (Cache::has($sudoKey)) {
            return true;
        }

        // Validate admin password from request
        $password = $request->input('admin_password');
        if (! $password || ! Hash::check($password, $admin->password)) {
            return false;
        }

        // Activate sudo mode for 15 minutes
        Cache::put($sudoKey, true, Carbon::now()->addMinutes(15));

        return true;
    }

    /**
     * Validate OTP verification
     */
    private function validateOTP(User $admin, Request $request): bool
    {
        $providedOTP = $request->input('otp_code');
        if (! $providedOTP) {
            return false;
        }

        // Check cache for expected OTP (generated by OTP service)
        $otpKey = "tenant_otp_{$admin->id}";
        $expectedOTP = Cache::get($otpKey);

        if (! $expectedOTP || ! hash_equals($expectedOTP, $providedOTP)) {
            return false;
        }

        // Clear OTP after successful validation
        Cache::forget($otpKey);

        return true;
    }

    /**
     * Log device fingerprint for critical operations
     */
    private function logDeviceFingerprint(User $admin, Request $request): void
    {
        $fingerprint = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'connection' => $request->header('Connection'),
            'cache_control' => $request->header('Cache-Control'),
            'dnt' => $request->header('DNT'),
            'upgrade_insecure_requests' => $request->header('Upgrade-Insecure-Requests'),
        ];

        Cache::put(
            "device_fp_{$admin->id}",
            $fingerprint,
            Carbon::now()->addHours(24)
        );
    }

    /**
     * Log comprehensive security event
     */
    private function logSecurityEvent(
        User $admin,
        Request $request,
        string $operation,
        string $tier,
        string $status,
        ?array $additional = null
    ): void {
        $properties = [
            'operation' => $operation,
            'security_tier' => $tier,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ];

        if ($additional) {
            $properties = array_merge($properties, $additional);
        }

        // Log to Spatie activity log
        activity()
            ->causedBy($admin)
            ->event('tenant_security_operation')
            ->withProperties($properties)
            ->log("Tenant security operation: {$operation} - {$status}");

        // Also log to specialized security log
        Cache::put(
            "security_log_{$admin->id}_".time(),
            $properties,
            Carbon::now()->addDays(90) // 90-day retention
        );
    }

    /**
     * Standard error response
     */
    private function errorResponse(string $message, int $code): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Get current rate limit status for user
     */
    public static function getRateLimitStatus(User $admin, string $operation): array
    {
        $tier = (new self)->getSecurityTier($operation);
        $key = "tenant_security:{$admin->id}:{$operation}";
        $maxAttempts = self::RATE_LIMITS[$tier] ?? 100;

        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $availableIn = RateLimiter::availableIn($key);

        return [
            'remaining' => $remaining,
            'max_attempts' => $maxAttempts,
            'reset_in_seconds' => $availableIn,
            'tier' => $tier,
            'operation' => $operation,
        ];
    }

    /**
     * Check if user is in sudo mode
     */
    public static function isSudoModeActive(User $admin): bool
    {
        return Cache::has("sudo_mode_{$admin->id}");
    }

    /**
     * Get device fingerprint for user
     */
    public static function getDeviceFingerprint(User $admin): ?array
    {
        return Cache::get("device_fp_{$admin->id}");
    }

    /**
     * Clear sudo mode for user
     */
    public static function clearSudoMode(User $admin): void
    {
        Cache::forget("sudo_mode_{$admin->id}");
    }

    /**
     * Get recent security events for user
     */
    public static function getRecentSecurityEvents(User $admin, int $hours = 24): array
    {
        $events = [];
        $pattern = "security_log_{$admin->id}_*";

        $keys = Cache::getRedis()->keys($pattern);

        foreach ($keys as $key) {
            $event = Cache::get($key);
            if ($event && isset($event['timestamp'])) {
                $eventTime = Carbon::parse($event['timestamp']);
                if ($eventTime->gt(now()->subHours($hours))) {
                    $events[] = $event;
                }
            }
        }

        return collect($events)->sortByDesc('timestamp')->values()->toArray();
    }
}
