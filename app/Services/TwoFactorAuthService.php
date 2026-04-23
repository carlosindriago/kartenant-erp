<?php

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class TwoFactorAuthService
{
    /**
     * Generate and send 2FA code for user
     */
    public function generateAndSendCode(User $user): string
    {
        // Generate new 2FA code
        $code = $user->generateEmail2FACode();

        try {
            // Send code via email
            Mail::to($user->email)->send(new TwoFactorCodeMail($code));

            // Log the code for development when using log mailer
            if (config('mail.mailer') === 'log') {
                \Log::info("2FA CODE FOR {$user->email}: {$code}");
            }
        } catch (Exception $e) {
            // Log error but don't expose to user
            \Log::error('Failed to send 2FA email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('No pudimos enviar el código de verificación. Por favor intenta más tarde.');
        }

        return $code;
    }

    /**
     * Verify 2FA code for user
     */
    public function verifyCode(User $user, string $code): bool
    {
        // Check rate limit for 2FA attempts (includes 3-strikes rule)
        $this->checkRateLimit($user);

        // Verify the code
        $isValid = $user->verifyEmail2FACode($code);

        if (! $isValid) {
            // Increment failed attempts counter
            $this->incrementFailedAttempts($user);

            return false;
        }

        // Clear the code after successful verification
        $user->clearEmail2FACode();

        // Clear failed attempts on successful verification
        $this->clearTwoFactorAttempts($user);

        return true;
    }

    /**
     * Increment failed 2FA attempts
     */
    private function incrementFailedAttempts(User $user): void
    {
        $attemptKey = '2fa_attempts:'.$user->id;
        $attempts = Cache::increment($attemptKey, 1);
        Cache::put($attemptKey, $attempts, 1800); // 30 minutes decay

        // Log failed attempt
        \Log::warning('2FA failed attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'attempts' => $attempts,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isTwoFactorEnabled(User $user): bool
    {
        // TEMPORARY FIX: Disable 2FA for all users to resolve critical dashboard access issue
        // This prevents the Error 500 loop and allows users to access their dashboard
        //
        // TODO: Implement proper 2FA configuration system with:
        // 1. User preference field (two_factor_enabled boolean)
        // 2. 2FA setup/management interface
        // 3. Proper authentication flow with recovery codes

        // Check if 2FA is globally enabled in configuration
        $twoFactorGloballyEnabled = config('auth.two_factor_enabled', false);

        // SECURITY: 2FA is now MANDATORY for all users when globally enabled
        // This ensures that all login attempts require two-factor authentication
        // to prevent unauthorized access and maintain security standards
        return $twoFactorGloballyEnabled;

        // Future implementation (when proper 2FA system is built):
        // return $twoFactorGloballyEnabled && $user->two_factor_enabled;
    }

    /**
     * Store user ID in session for 2FA flow
     */
    public function startTwoFactorSession(User $user): void
    {
        Session::put('2fa_user_id', $user->id);
        Session::put('2fa_attempt_time', now());
        Session::put('user_email', $user->email);
    }

    /**
     * Get user from 2FA session
     */
    public function getUserFromSession(): ?User
    {
        $userId = Session::get('2fa_user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Clear 2FA session data
     */
    public function clearTwoFactorSession(): void
    {
        Session::forget(['2fa_user_id', '2fa_attempt_time', 'user_email']);
    }

    /**
     * Check if 2FA session is valid (not expired)
     */
    public function isValidTwoFactorSession(): bool
    {
        $attemptTime = Session::get('2fa_attempt_time');

        if (! $attemptTime) {
            return false;
        }

        // Session expires after 10 minutes
        return $attemptTime->diffInMinutes(now()) < 10;
    }

    /**
     * Get remaining time for 2FA session in seconds
     */
    public function getRemainingSessionTime(): int
    {
        $attemptTime = Session::get('2fa_attempt_time');

        if (! $attemptTime) {
            return 0;
        }

        $totalTime = 600; // 10 minutes in seconds
        $elapsedTime = $attemptTime->diffInSeconds(now());

        return max(0, $totalTime - $elapsedTime);
    }

    /**
     * Check rate limit for 2FA attempts
     * Implements 3-strikes rule with 24-hour lockout
     */
    private function checkRateLimit(User $user): void
    {
        $lockoutKey = '2fa_lockout:'.$user->id;

        // Check if user is already locked out
        if (Cache::has($lockoutKey)) {
            $remainingTime = Cache::get($lockoutKey);
            $hours = ceil($remainingTime / 3600);
            throw new Exception("Cuenta bloqueada por seguridad. Intenta en {$hours} horas.");
        }

        $attemptKey = '2fa_attempts:'.$user->id;

        // Get current attempts (decay in 30 minutes)
        $attempts = Cache::get($attemptKey, 0);

        // Lock out after 3 failed attempts
        if ($attempts >= 3) {
            $this->applyAccountLockout($user);

            // Send security alert email
            try {
                \Mail::to($user->email)->send(new \App\Mail\AccountLockoutNotification($user));
            } catch (\Exception $e) {
                \Log::error('Failed to send lockout notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            throw new Exception('Demasiados intentos fallidos. Tu cuenta ha sido bloqueada por 24 horas por seguridad.');
        }
    }

    /**
     * Apply account lockout for 24 hours
     */
    private function applyAccountLockout(User $user): void
    {
        $lockoutKey = '2fa_lockout:'.$user->id;
        $lockoutDuration = 24 * 60 * 60; // 24 hours in seconds

        Cache::put($lockoutKey, $lockoutDuration, $lockoutDuration);

        // Log security event
        \Log::warning('Account locked due to 2FA failures', [
            'user_id' => $user->id,
            'email' => $user->email,
            'lockout_duration' => $lockoutDuration,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Check if user account is locked
     */
    public function isAccountLocked(User $user): bool
    {
        $lockoutKey = '2fa_lockout:'.$user->id;

        return Cache::has($lockoutKey);
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getRemainingLockoutTime(User $user): int
    {
        $lockoutKey = '2fa_lockout:'.$user->id;

        return Cache::get($lockoutKey, 0);
    }

    /**
     * Clear 2FA attempts after successful login
     */
    private function clearTwoFactorAttempts(User $user): void
    {
        $attemptKey = '2fa_attempts:'.$user->id;
        Cache::forget($attemptKey);
    }

    /**
     * Get rate limit key for user
     */
    private function getRateLimitKey(User $user): string
    {
        return '2fa:'.$user->id.':'.request()->ip();
    }

    /**
     * Check if user can request a new code (rate limit for resend)
     */
    public function canRequestNewCode(User $user): bool
    {
        $key = '2fa_resend:'.$user->id.':'.request()->ip();

        // Allow resend once every 30 seconds
        return ! RateLimiter::tooManyAttempts($key, 1);
    }

    /**
     * Get time until user can request new code
     */
    public function getTimeUntilNewCode(User $user): int
    {
        $key = '2fa_resend:'.$user->id.':'.request()->ip();

        return RateLimiter::availableIn($key);
    }

    /**
     * Mark that user requested new code
     */
    public function markCodeRequest(User $user): void
    {
        $key = '2fa_resend:'.$user->id.':'.request()->ip();
        RateLimiter::hit($key, 30); // 30 second cooldown
    }

    /**
     * Complete the authentication process
     */
    public function completeAuthentication(User $user): bool
    {
        try {
            // Clear any remaining 2FA session data
            $this->clearTwoFactorSession();

            // Clear all rate limits
            RateLimiter::clear($this->getRateLimitKey($user));
            RateLimiter::clear('2fa_resend:'.$user->id.':'.request()->ip());

            return true;
        } catch (Exception $e) {
            \Log::error('Error completing 2FA authentication', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
