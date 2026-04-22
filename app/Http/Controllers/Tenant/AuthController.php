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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
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
            'tenant' => \App\Models\Tenant::current(),
        ]);
    }

    /**
     * Handle login request with enhanced security
     */
    public function login(Request $request)
    {
        $startTime = microtime(true);

        \Log::info('Tenant login attempt', [
            'email' => $request->email,
            'tenant_domain' => \App\Models\Tenant::current()?->domain,
            'ip' => $request->ip()
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

        // Find user in landlord database
        $user = User::where('email', $email)->first();

        // Check credentials and all security conditions
        $loginSuccess = false;
        $failureReason = null;

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $failureReason = 'invalid_credentials';
        } elseif (!$user->is_active) {
            $failureReason = 'inactive_account';
        } elseif (!$this->validateTenantMembershipCritical($user, \App\Models\Tenant::current())) {
            $failureReason = 'unauthorized_tenant';

            // CRITICAL SECURITY LOG: Cross-tenant access attempt
            \Log::critical('SECURITY BREACH: Cross-tenant authentication attempt blocked', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'attempted_tenant_id' => \App\Models\Tenant::current()?->id,
                'attempted_tenant_domain' => \App\Models\Tenant::current()?->domain,
                'attempted_tenant_database' => \App\Models\Tenant::current()?->database,
                'request_host' => $request->getHost(),
                'request_path' => $request->path(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => Session::getId(),
                'timestamp' => now()->toISOString(),
                'security_context' => [
                    'tenant_context' => \App\Models\Tenant::current() ? [
                        'id' => \App\Models\Tenant::current()->id,
                        'name' => \App\Models\Tenant::current()->name,
                        'domain' => \App\Models\Tenant::current()->domain,
                        'status' => \App\Models\Tenant::current()->status,
                        'database' => \App\Models\Tenant::current()->database,
                    ] : null,
                    'user_context' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'validation_attempt' => 'tenant_user_relationship_check',
                ]
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

        // Regenerate session to prevent session fixation
        Session::regenerate();
        Session::put('tenant_authenticated', true);

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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '¡Inicio de sesión exitoso!',
                'redirect' => route('tenant.dashboard')
            ]);
        }

        return redirect()->intended('tenant.dashboard')
            ->with('success', '¡Inicio de sesión exitoso!');
    }

    /**
     * Show 2FA verification form
     */
    public function showTwoFactorForm(Request $request)
    {
        // Check if 2FA session is valid
        if (!$this->twoFactorAuthService->isValidTwoFactorSession()) {
            return redirect()->route('tenant.login')
                ->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        }

        // Get user from session
        $user = $this->twoFactorAuthService->getUserFromSession();
        if (!$user) {
            return redirect()->route('tenant.login')
                ->with('error', 'Sesión inválida. Por favor inicia sesión nuevamente.');
        }

        $tenant = \App\Models\Tenant::current();
        $email = Session::get('user_email');
        $remainingTime = $this->twoFactorAuthService->getRemainingSessionTime();

        return view('tenant.auth.two-factor', compact('tenant', 'email', 'remainingTime'));
    }

    /**
     * Verify 2FA code with enhanced security
     */
    public function verifyTwoFactor(Request $request)
    {
        $startTime = microtime(true);

        // Validate request with generic error messages
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ], [
            'code.required' => self::GENERIC_AUTH_ERROR,
            'code.size' => self::GENERIC_AUTH_ERROR,
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
                ->withInput();
        }

        // Check if 2FA session is valid
        if (!$this->twoFactorAuthService->isValidTwoFactorSession()) {
            $this->applyTimingAttackPrevention($startTime);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => self::GENERIC_AUTH_ERROR,
                    'redirect' => route('tenant.login')
                ], 419);
            }

            return redirect()->route('tenant.login')
                ->with('error', self::GENERIC_AUTH_ERROR);
        }

        // Get user from session
        $user = $this->twoFactorAuthService->getUserFromSession();
        if (!$user) {
            $this->applyTimingAttackPrevention($startTime);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => self::GENERIC_AUTH_ERROR,
                    'redirect' => route('tenant.login')
                ], 419);
            }

            return redirect()->route('tenant.login')
                ->with('error', self::GENERIC_AUTH_ERROR);
        }

        // Check 2FA rate limiting
        $this->checkTwoFactorRateLimit($request, $user);

        try {
            // Verify 2FA code
            if ($this->twoFactorAuthService->verifyCode($user, $request->code)) {
                // Clear 2FA rate limit on successful verification
                $this->clearTwoFactorRateLimit($request, $user);

                // Regenerate session to prevent session fixation
                Session::regenerate();
                Session::put('tenant_authenticated', true);

                // Complete authentication
                Auth::guard('tenant')->login($user);
                $this->twoFactorAuthService->completeAuthentication($user);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => '¡Verificación exitosa!',
                        'redirect' => route('tenant.dashboard')
                    ]);
                }

                return redirect()->route('tenant.dashboard')
                    ->with('success', '¡Verificación exitosa!');
            } else {
                // Increment 2FA failure count
                $this->hitTwoFactorRateLimit($request, $user);
                $this->applyTimingAttackPrevention($startTime);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => self::GENERIC_AUTH_ERROR
                    ], 401);
                }

                throw ValidationException::withMessages([
                    'code' => self::GENERIC_AUTH_ERROR,
                ]);
            }
        } catch (Exception $e) {
            // Increment 2FA failure count on service errors too
            $this->hitTwoFactorRateLimit($request, $user);
            $this->applyTimingAttackPrevention($startTime);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => self::GENERIC_AUTH_ERROR
                ], 429);
            }

            throw ValidationException::withMessages([
                'code' => self::GENERIC_AUTH_ERROR,
            ]);
        }
    }

    /**
     * Resend 2FA code
     */
    public function resendTwoFactorCode(Request $request)
    {
        // Check if 2FA session is valid
        if (!$this->twoFactorAuthService->isValidTwoFactorSession()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
                    'redirect' => route('tenant.login')
                ], 419);
            }

            return redirect()->route('tenant.login')
                ->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        }

        // Get user from session
        $user = $this->twoFactorAuthService->getUserFromSession();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión inválida. Por favor inicia sesión nuevamente.',
                    'redirect' => route('tenant.login')
                ], 419);
            }

            return redirect()->route('tenant.login')
                ->with('error', 'Sesión inválida. Por favor inicia sesión nuevamente.');
        }

        // Check if user can request new code
        if (!$this->twoFactorAuthService->canRequestNewCode($user)) {
            $waitTime = $this->twoFactorAuthService->getTimeUntilNewCode($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Por favor espera {$waitTime} segundos antes de solicitar un nuevo código."
                ], 429);
            }

            return back()->with('error', "Por favor espera {$waitTime} segundos antes de solicitar un nuevo código.");
        }

        try {
            // Generate and send new code
            $this->twoFactorAuthService->generateAndSendCode($user);

            // Mark that user requested new code
            $this->twoFactorAuthService->markCodeRequest($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Código de verificación reenviado correctamente'
                ]);
            }

            return back()->with('success', 'Código de verificación reenviado correctamente');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Logout user with enhanced session security
     */
    public function logout(Request $request)
    {
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

    /**
     * Check advanced rate limiting with exponential backoff
     * Prevents IP rotation bypass by using email:ip combination
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
     * Check 2FA rate limiting
     */
    private function checkTwoFactorRateLimit(Request $request, User $user): void
    {
        $key = '2fa_attempt:' . $user->id . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) { // Max 10 attempts per minute
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'code' => "Demasiados intentos de verificación. Intenta en {$seconds} segundos.",
            ]);
        }
    }

    /**
     * Hit 2FA rate limit
     */
    private function hitTwoFactorRateLimit(Request $request, User $user): void
    {
        $key = '2fa_attempt:' . $user->id . ':' . $request->ip();
        RateLimiter::hit($key, 60); // 1 minute decay
    }

    /**
     * Clear 2FA rate limit
     */
    private function clearTwoFactorRateLimit(Request $request, User $user): void
    {
        $key = '2fa_attempt:' . $user->id . ':' . $request->ip();
        RateLimiter::clear($key);
    }

    /**
     * CRITICAL SECURITY FIX: Validate tenant-user membership with landlord DB
     *
     * This method implements the CRITICAL SECURITY FIX for the cross-tenant
     * authentication vulnerability. It explicitly queries the LANDLORD database
     * to validate tenant-user relationships, preventing tenant context contamination.
     *
     * VULNERABILITY FIXED:
     * - Original: $user->tenants()->where('tenants.id', \App\Models\Tenant::current()->id)->exists()
     * - Problem: This query executed in tenant context, causing false positives
     * - Solution: Explicit landlord DB query for validation
     *
     * @param User $user User attempting authentication
     * @param Tenant|null $currentTenant Current tenant context
     * @return bool True if user belongs to tenant, false otherwise
     */
    private function validateTenantMembershipCritical(User $user, ?Tenant $currentTenant): bool
    {
        if (!$currentTenant) {
            \Log::error('CRITICAL: No tenant context during authentication', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'timestamp' => now()->toISOString()
            ]);
            return false;
        }

        try {
            // CRITICAL SECURITY FIX: Force landlord database connection
            // This prevents tenant context contamination and ensures accurate validation
            $membership = \Illuminate\Support\Facades\DB::connection('landlord')
                ->table('tenant_user')
                ->where('user_id', $user->id)
                ->where('tenant_id', $currentTenant->id)
                ->exists();

            if (!$membership) {
                \Log::warning('SECURITY: User does not belong to tenant', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempted_tenant_id' => $currentTenant->id,
                    'attempted_tenant_domain' => $currentTenant->domain,
                    'timestamp' => now()->toISOString()
                ]);
                return false;
            }

            // Additional validation: Ensure tenant exists and is active
            $tenantExists = \Illuminate\Support\Facades\DB::connection('landlord')
                ->table('tenants')
                ->where('id', $currentTenant->id)
                ->where('status', 'active')
                ->exists();

            if (!$tenantExists) {
                \Log::warning('SECURITY: Tenant does not exist or is inactive', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempted_tenant_id' => $currentTenant->id,
                    'attempted_tenant_domain' => $currentTenant->domain,
                    'timestamp' => now()->toISOString()
                ]);
                return false;
            }

            // CRITICAL SECURITY: Additional validation using User model with landlord connection
            $userExistsInLandlord = \App\Models\User::on('landlord')
                ->where('id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (!$userExistsInLandlord) {
                \Log::critical('SECURITY BREACH: User does not exist in landlord database', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempted_tenant_id' => $currentTenant->id,
                    'attempted_tenant_domain' => $currentTenant->domain,
                    'timestamp' => now()->toISOString()
                ]);
                return false;
            }

            // SUCCESS: User is validated for this tenant
            \Log::info('SECURITY: Tenant membership validated successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'validated_tenant_id' => $currentTenant->id,
                'validated_tenant_domain' => $currentTenant->domain,
                'timestamp' => now()->toISOString()
            ]);

            return true;

        } catch (\Exception $e) {
            // CRITICAL: On any validation error, deny access to be safe
            \Log::critical('SECURITY ERROR: Tenant membership validation failed', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'attempted_tenant_id' => $currentTenant->id,
                'attempted_tenant_domain' => $currentTenant->domain,
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            // FAIL SECURE: Deny access on validation errors
            return false;
        }
    }

    /**
     * Apply timing attack prevention
     * Ensures consistent response times regardless of validation outcome
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
}