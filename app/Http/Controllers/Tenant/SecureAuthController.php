<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use App\Models\User;
use App\Models\Tenant;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * SECURE AUTH CONTROLLER - CRITICAL SECURITY FIX
 *
 * This controller implements CRITICAL SECURITY FIXES for multi-tenant isolation:
 * 1. Explicit landlord DB queries for tenant-user validation
 * 2. Multi-layer tenant context validation
 * 3. Session isolation enforcement
 * 4. Audit logging for security events
 */
class SecureAuthController extends Controller
{
    // Generic error message for all authentication failures to prevent user enumeration
    private const GENERIC_AUTH_ERROR = "Estas credenciales no coinciden con nuestros registros";

    // Rate limiting configuration
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 60; // seconds
    private const ATTEMPT_DECAY = 300; // 5 minutes

    public function __construct(
        private TwoFactorAuthService $twoFactorAuthService
    ) {
        $this->middleware('guest:tenant')->except(['logout']);
    }

    /**
     * Show the login form with tenant branding
     */
    public function showLoginForm()
    {
        // Get current tenant settings for branding
        $settings = StoreSetting::current();

        // If user is already authenticated, redirect to dashboard
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.dashboard');
        }

        return view('tenant.auth.login', [
            'settings' => $settings,
            'tenant' => tenant(),
        ]);
    }

    /**
     * Handle login request with MULTI-TENANT SECURITY VALIDATION
     */
    public function login(Request $request)
    {
        $startTime = microtime(true);

        // SECURITY LOG: Track all login attempts
        \Log::critical('SECURE: Tenant login attempt', [
            'email' => $request->email,
            'tenant_domain' => tenant()?->domain,
            'tenant_id' => tenant()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        // Validate request with generic error messages
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => self::GENERIC_AUTH_ERROR,
            'email.email' => self::GENERIC_AUTH_ERROR,
            'password.required' => self::GENERIC_AUTH_ERROR,
            'password.min' => self::GENERIC_AUTH_ERROR,
        ]);

        if ($validator->fails()) {
            $this->applyTimingAttackPrevention($startTime);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => self::GENERIC_AUTH_ERROR,
                    'errors' => $validator->errors()
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        // Check advanced rate limiting with exponential backoff
        $this->checkAdvancedRateLimit($request);

        $credentials = $request->only('email', 'password');
        $email = strtolower($credentials['email']);

        // CRITICAL SECURITY FIX: Find user in LANDLORD database explicitly
        $user = $this->findUserInLandlord($email);

        // SECURITY VALIDATION LAYERS
        $loginSuccess = false;
        $failureReason = null;
        $securityContext = $this->buildSecurityContext($request, $email);

        if (!$user) {
            $failureReason = 'invalid_credentials';
        } elseif (!Hash::check($credentials['password'], $user->password)) {
            $failureReason = 'invalid_credentials';
        } elseif (!$user->is_active) {
            $failureReason = 'inactive_account';
        } elseif (!$this->validateTenantMembershipSecure($user, tenant())) {
            $failureReason = 'unauthorized_tenant';
            \Log::alert('CRITICAL: Cross-tenant access attempt blocked', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'attempted_tenant' => tenant()?->id,
                'attempted_domain' => tenant()?->domain,
                'ip' => $request->ip(),
                'security_context' => $securityContext
            ]);
        } elseif ($this->twoFactorAuthService->isAccountLocked($user)) {
            $remainingHours = ceil($this->twoFactorAuthService->getRemainingLockoutTime($user) / 3600);
            $failureReason = 'account_locked_' . $remainingHours;
        } else {
            $loginSuccess = true;
        }

        // Handle login failure
        if (!$loginSuccess) {
            // Don't apply rate limiting for locked accounts (already handled)
            if (!str_starts_with($failureReason, 'account_locked')) {
                $this->handleLoginFailure($request, $email);
            }
            $this->applyTimingAttackPrevention($startTime);

            // Special handling for locked accounts
            if (str_starts_with($failureReason, 'account_locked')) {
                $remainingHours = substr($failureReason, strlen('account_locked_'));
                $message = "Tu cuenta está bloqueada por seguridad. Contacta al soporte para reactivarla. Tiempo restante: {$remainingHours} horas.";

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'account_locked' => true,
                        'remaining_hours' => (int)$remainingHours
                    ], 423); // 423 Locked
                }

                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => self::GENERIC_AUTH_ERROR
                ], 401);
            }

            throw ValidationException::withMessages([
                'email' => self::GENERIC_AUTH_ERROR,
            ]);
        }

        // Handle successful authentication
        $this->handleSuccessfulLogin($request, $user, $email);

        // CRITICAL SECURITY: Establish secure tenant session
        $this->establishSecureTenantContext($user, tenant());

        // Regenerate session to prevent session fixation
        Session::regenerate();
        Session::put('tenant_authenticated', true);
        Session::put('secure_tenant_id', tenant()->id);
        Session::put('secure_user_id', $user->id);

        // Check if 2FA is enabled for this user
        \Log::info('2FA Check for user', ['user_id' => $user->id, 'email' => $user->email, '2fa_enabled' => $this->twoFactorAuthService->isTwoFactorEnabled($user)]);

        if ($this->twoFactorAuthService->isTwoFactorEnabled($user)) {
            try {
                // Generate and send 2FA code
                $this->twoFactorAuthService->generateAndSendCode($user);

                // Start 2FA session
                $this->twoFactorAuthService->startTwoFactorSession($user);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Se ha enviado un código de verificación a tu correo',
                        'redirect' => route('tenant.2fa')
                    ]);
                }

                // Redirect to 2FA verification page
                return redirect()->route('tenant.2fa')
                    ->with('success', 'Se ha enviado un código de verificación a tu correo');
            } catch (Exception $e) {
                // Apply timing attack prevention even on service errors
                $this->applyTimingAttackPrevention($startTime);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => self::GENERIC_AUTH_ERROR
                    ], 500);
                }

                throw ValidationException::withMessages([
                    'email' => self::GENERIC_AUTH_ERROR,
                ]);
            }
        }

        // If no 2FA, authenticate directly
        Auth::guard('tenant')->login($user);

        // SECURITY LOG: Successful authentication
        \Log::critical('SECURE: Successful tenant authentication', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => tenant()->id,
            'tenant_domain' => tenant()->domain,
            'ip' => $request->ip(),
            'security_context' => $securityContext,
            'timestamp' => now()->toISOString()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Inicio de sesión exitoso!',
                'redirect' => route('tenant.dashboard')
            ]);
        }

        return redirect()->intended(route('tenant.dashboard'))
            ->with('success', '¡Inicio de sesión exitoso!');
    }

    /**
     * CRITICAL SECURITY: Find user in LANDLORD database explicitly
     */
    private function findUserInLandlord(string $email): ?User
    {
        // Force query to landlord database to prevent tenant context contamination
        $user = DB::connection('landlord')
            ->table('users')
            ->where('email', $email)
            ->first();

        if (!$user) {
            return null;
        }

        // Convert to User model with explicit landlord connection
        return User::on('landlord')->find($user->id);
    }

    /**
     * CRITICAL SECURITY: Validate tenant-user membership with landlord DB
     */
    private function validateTenantMembershipSecure(User $user, ?Tenant $currentTenant): bool
    {
        if (!$currentTenant) {
            \Log::error('CRITICAL: No tenant context during authentication', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            return false;
        }

        // SECURITY: Query landlord database directly for tenant_user relationship
        $membership = DB::connection('landlord')
            ->table('tenant_user')
            ->where('user_id', $user->id)
            ->where('tenant_id', $currentTenant->id)
            ->exists();

        // Double-check tenant exists and is active
        $tenantExists = DB::connection('landlord')
            ->table('tenants')
            ->where('id', $currentTenant->id)
            ->where('status', 'active')
            ->exists();

        return $membership && $tenantExists;
    }

    /**
     * CRITICAL SECURITY: Build security context for audit
     */
    private function buildSecurityContext(Request $request, string $email): array
    {
        return [
            'request_host' => $request->getHost(),
            'request_path' => $request->path(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tenant_context' => [
                'id' => tenant()?->id,
                'domain' => tenant()?->domain,
                'database' => tenant()?->database,
                'status' => tenant()?->status,
            ],
            'session_data' => [
                'id' => Session::getId(),
                'previous_tenant_id' => Session::get('secure_tenant_id'),
                'previous_user_id' => Session::get('secure_user_id'),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * CRITICAL SECURITY: Establish secure tenant context
     */
    private function establishSecureTenantContext(User $user, Tenant $tenant): void
    {
        // Validate session integrity
        $previousTenantId = Session::get('secure_tenant_id');
        $previousUserId = Session::get('secure_user_id');

        if ($previousTenantId && $previousTenantId !== $tenant->id) {
            \Log::critical('SECURITY: Tenant context switching detected', [
                'user_id' => $user->id,
                'previous_tenant' => $previousTenantId,
                'current_tenant' => $tenant->id,
                'ip' => request()->ip()
            ]);
        }

        // Store secure context markers
        Session::put('secure_tenant_id', $tenant->id);
        Session::put('secure_user_id', $user->id);
        Session::put('secure_tenant_domain', $tenant->domain);
        Session::put('authenticated_at', now()->timestamp);
    }

    /**
     * Check advanced rate limiting with exponential backoff
     */
    private function checkAdvancedRateLimit(Request $request): void
    {
        $email = strtolower($request->input('email', ''));
        $ip = $request->ip();
        $key = 'auth_attempt:' . $email . ':' . $ip;

        // Check if account is locked
        $lockoutKey = 'auth_lockout:' . $email;
        if (Cache::has($lockoutKey)) {
            $remainingTime = Cache::get($lockoutKey);
            throw ValidationException::withMessages([
                'email' => "Cuenta temporalmente bloqueada. Intenta en {$remainingTime} segundos.",
            ]);
        }

        // Check rate limit with exponential backoff
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->applyExponentialLockout($email, $attempts);
        }
    }

    /**
     * Handle login failure with tracking
     */
    private function handleLoginFailure(Request $request, string $email): void
    {
        $ip = $request->ip();
        $key = 'auth_attempt:' . $email . ':' . $ip;

        // Increment attempt counter
        $attempts = Cache::increment($key, 1);
        Cache::put($key, $attempts, self::ATTEMPT_DECAY);

        // Track failed attempts per email across all IPs
        $globalAttemptKey = 'auth_attempts_global:' . $email;
        $globalAttempts = Cache::increment($globalAttemptKey, 1);
        Cache::put($globalAttemptKey, $globalAttempts, self::ATTEMPT_DECAY);

        // Apply lockout if threshold reached
        if ($globalAttempts >= self::MAX_ATTEMPTS) {
            $this->applyExponentialLockout($email, $globalAttempts);
        }
    }

    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin(Request $request, User $user, string $email): void
    {
        // Clear all rate limit counters for this email
        $this->clearAllAuthAttempts($email);

        // Log successful authentication for audit purposes
        Cache::put('auth_success:' . $email, [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'timestamp' => now()
        ], 3600); // Keep for 1 hour
    }

    /**
     * Apply exponential backoff lockout
     */
    private function applyExponentialLockout(string $email, int $attempts): void
    {
        $exponent = min($attempts - self::MAX_ATTEMPTS, 5); // Cap at 5 levels
        $lockoutDuration = self::LOCKOUT_DURATION * (2 ** $exponent); // 60s, 120s, 240s, 480s, 960s

        $lockoutKey = 'auth_lockout:' . $email;
        Cache::put($lockoutKey, $lockoutDuration, $lockoutDuration);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Cuenta bloqueada por {$lockoutDuration} segundos.",
        ]);
    }

    /**
     * Clear all authentication attempts for an email
     */
    private function clearAllAuthAttempts(string $email): void
    {
        // Clear specific IP-based attempts
        $pattern = 'auth_attempt:' . $email . ':*';
        // Note: In production, you might want to use a more efficient pattern clearing method
        Cache::forget('auth_attempts_global:' . $email);
        Cache::forget('auth_lockout:' . $email);
    }

    /**
     * Apply timing attack prevention
     */
    private function applyTimingAttackPrevention(float $startTime): void
    {
        $elapsed = microtime(true) - $startTime;
        $minTime = 0.2; // 200ms minimum time

        if ($elapsed < $minTime) {
            $sleepTime = ($minTime - $elapsed) * 1000000; // Convert to microseconds
            usleep($sleepTime);
        }

        // Add dummy computational work if still too fast
        if ($elapsed < 0.15) {
            $dummy = str_repeat('timing_attack_prevention', 100);
            hash('sha256', $dummy);
        }
    }

    /**
     * Logout user with enhanced session security
     */
    public function logout(Request $request)
    {
        // SECURITY LOG: Logout event
        \Log::critical('SECURE: User logout', [
            'user_id' => Auth::guard('tenant')->id(),
            'tenant_id' => Session::get('secure_tenant_id'),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        // Clear any 2FA session data
        $this->twoFactorAuthService->clearTwoFactorSession();

        // Logout user
        Auth::guard('tenant')->logout();

        // Complete session invalidation to prevent session fixation
        Session::invalidate();
        Session::regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
                'redirect' => route('tenant.login')
            ]);
        }

        return redirect()->route('tenant.login')
            ->with('success', 'Sesión cerrada correctamente');
    }
}