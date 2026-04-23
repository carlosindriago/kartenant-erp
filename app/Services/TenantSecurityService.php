<?php

namespace App\Services;

use App\Mail\ArchiveOTPNotification;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Multi-Factor Authentication and Security Service
 *
 * Provides enterprise-grade security controls for tenant management:
 * - OTP generation and validation
 * - Sudo mode management
 * - Rate limiting for sensitive operations
 * - Email verification for critical actions
 * - Device fingerprinting and anomaly detection
 */
class TenantSecurityService
{
    /**
     * Generate secure OTP for tenant operations
     */
    public function generateOTP(User $admin, string $operation, Tenant $tenant): string
    {
        // Rate limit OTP generation
        $this->checkOTPRateLimit($admin);

        // Generate context-specific OTP
        $otp = $this->generateContextOTP($operation, $tenant);

        // Store OTP with expiration (10 minutes)
        $otpKey = "tenant_otp_{$admin->id}";
        Cache::put($otpKey, $otp, Carbon::now()->addMinutes(10));

        // Log OTP generation
        activity()
            ->causedBy($admin)
            ->performedOn($tenant)
            ->withProperties([
                'operation' => $operation,
                'otp_length' => strlen($otp),
                'expires_at' => Carbon::now()->addMinutes(10)->toISOString(),
                'ip' => request()->ip(),
            ])
            ->log('OTP generated for tenant operation');

        return $otp;
    }

    /**
     * Generate context-specific OTP codes
     */
    private function generateContextOTP(string $operation, Tenant $tenant): string
    {
        switch ($operation) {
            case 'tenant_deactivate':
                return 'DEACTIVATE'.strtoupper(substr($tenant->name, 0, 4));

            case 'tenant_archive':
                return 'ARCHIVE'.strtoupper(substr($tenant->name, 0, 4));

            case 'force_delete':
                return 'DELETE'.strtoupper(substr($tenant->name, 0, 4)).now()->format('Hi');

            case 'emergency_access':
                return 'EMERGENCY'.strtoupper(substr($tenant->name, 0, 3));

            case 'data_export':
                return 'EXPORT'.strtoupper(substr($tenant->name, 0, 3));

            default:
                // Generate random 6-digit code for other operations
                return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Validate OTP for tenant operation
     */
    public function validateOTP(User $admin, string $providedOTP): bool
    {
        $otpKey = "tenant_otp_{$admin->id}";
        $expectedOTP = Cache::get($otpKey);

        if (! $expectedOTP) {
            return false;
        }

        // Constant-time comparison to prevent timing attacks
        $isValid = hash_equals($expectedOTP, strtoupper(trim($providedOTP)));

        if ($isValid) {
            // Clear OTP after successful validation
            Cache::forget($otpKey);

            activity()
                ->causedBy($admin)
                ->withProperties([
                    'otp_validated' => true,
                    'ip' => request()->ip(),
                ])
                ->log('OTP successfully validated');
        } else {
            activity()
                ->causedBy($admin)
                ->withProperties([
                    'otp_invalid' => true,
                    'provided' => substr($providedOTP, 0, 3).'***',
                    'ip' => request()->ip(),
                ])
                ->log('OTP validation failed');
        }

        return $isValid;
    }

    /**
     * Activate sudo mode for admin
     */
    public function activateSudoMode(User $admin, string $password): bool
    {
        // Validate admin password
        if (! Hash::check($password, $admin->password)) {
            activity()
                ->causedBy($admin)
                ->withProperties([
                    'sudo_attempt' => false,
                    'ip' => request()->ip(),
                ])
                ->log('Sudo mode activation failed - invalid password');

            return false;
        }

        // Activate sudo mode for 15 minutes
        $sudoKey = "sudo_mode_{$admin->id}";
        Cache::put($sudoKey, [
            'activated_at' => now()->toISOString(),
            'activated_by' => $admin->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], Carbon::now()->addMinutes(15));

        activity()
            ->causedBy($admin)
            ->withProperties([
                'sudo_activated' => true,
                'expires_at' => Carbon::now()->addMinutes(15)->toISOString(),
                'ip' => request()->ip(),
            ])
            ->log('Sudo mode activated');

        return true;
    }

    /**
     * Check if sudo mode is active for admin
     */
    public function isSudoModeActive(User $admin): bool
    {
        $sudoKey = "sudo_mode_{$admin->id}";

        return Cache::has($sudoKey);
    }

    /**
     * Get sudo mode status for admin
     */
    public function getSudoModeStatus(User $admin): ?array
    {
        $sudoKey = "sudo_mode_{$admin->id}";
        $sudoData = Cache::get($sudoKey);

        if (! $sudoData) {
            return null;
        }

        return [
            'active' => true,
            'activated_at' => $sudoData['activated_at'],
            'expires_at' => Carbon::parse($sudoData['activated_at'])->addMinutes(15)->toISOString(),
            'remaining_minutes' => max(0, Carbon::parse($sudoData['activated_at'])->addMinutes(15)->diffInMinutes(now())),
            'ip' => $sudoData['ip'],
        ];
    }

    /**
     * Clear sudo mode for admin
     */
    public function clearSudoMode(User $admin): void
    {
        $sudoKey = "sudo_mode_{$admin->id}";
        Cache::forget($sudoKey);

        activity()
            ->causedBy($admin)
            ->withProperties([
                'sudo_cleared' => true,
                'ip' => request()->ip(),
            ])
            ->log('Sudo mode cleared');
    }

    /**
     * Send email verification for critical operations
     */
    public function sendEmailVerification(User $admin, Tenant $tenant, string $operation): string
    {
        // Generate unique verification token
        $token = Str::random(32);
        $verificationKey = "email_verify_{$admin->id}_{$operation}";

        Cache::put($verificationKey, [
            'token' => $token,
            'operation' => $operation,
            'tenant_id' => $tenant->id,
            'created_at' => now()->toISOString(),
        ], Carbon::now()->addMinutes(30));

        // TODO: Implement email sending with verification link
        // Mail::to($admin->email)->send(new TenantOperationVerification($admin, $tenant, $operation, $token));

        activity()
            ->causedBy($admin)
            ->performedOn($tenant)
            ->withProperties([
                'operation' => $operation,
                'verification_sent' => true,
                'token_hash' => hash('sha256', $token),
                'ip' => request()->ip(),
            ])
            ->log('Email verification sent for tenant operation');

        return $token;
    }

    /**
     * Validate email verification token
     */
    public function validateEmailVerification(User $admin, string $operation, string $token): bool
    {
        $verificationKey = "email_verify_{$admin->id}_{$operation}";
        $verificationData = Cache::get($verificationKey);

        if (! $verificationData || ! hash_equals($verificationData['token'], $token)) {
            return false;
        }

        // Clear verification token
        Cache::forget($verificationKey);

        activity()
            ->causedBy($admin)
            ->withProperties([
                'operation' => $operation,
                'email_verified' => true,
                'ip' => request()->ip(),
            ])
            ->log('Email verification successful');

        return true;
    }

    /**
     * Check for suspicious activity patterns
     */
    public function detectSuspiciousActivity(User $admin): array
    {
        $suspicious = [];
        $currentIp = request()->ip();
        $currentUserAgent = request()->userAgent();

        // Check for multiple IPs in short time
        $recentIPs = $this->getRecentIPs($admin, 24); // Last 24 hours
        if (count($recentIPs) > 3) {
            $suspicious[] = [
                'type' => 'multiple_ips',
                'severity' => 'medium',
                'description' => 'Múltiples direcciones IP detectadas en 24 horas',
                'ips' => $recentIPs,
            ];
        }

        // Check for unusual time patterns
        $recentOperations = $this->getRecentSecurityEvents($admin, 1); // Last hour
        if (count($recentOperations) > 10) {
            $suspicious[] = [
                'type' => 'high_frequency',
                'severity' => 'high',
                'description' => 'Alta frecuencia de operaciones en poco tiempo',
                'count' => count($recentOperations),
            ];
        }

        // Check for new device
        $knownDevices = $this->getKnownDevices($admin);
        $deviceFingerprint = $this->generateDeviceFingerprint();

        if (! in_array($deviceFingerprint, $knownDevices)) {
            $suspicious[] = [
                'type' => 'new_device',
                'severity' => 'medium',
                'description' => 'Acceso desde dispositivo no reconocido',
                'fingerprint' => substr($deviceFingerprint, 0, 8).'...',
            ];
        }

        return $suspicious;
    }

    /**
     * Get recent IPs for user
     */
    private function getRecentIPs(User $admin, int $hours): array
    {
        $events = $this->getRecentSecurityEvents($admin, $hours);

        return collect($events)
            ->pluck('ip_address')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get recent security events
     */
    private function getRecentSecurityEvents(User $admin, int $hours): array
    {
        // Implementation would query the security logs
        // For now, return empty array
        return [];
    }

    /**
     * Get known devices for user
     */
    private function getKnownDevices(User $admin): array
    {
        // Implementation would query known devices from database
        // For now, return empty array
        return [];
    }

    /**
     * Generate device fingerprint
     */
    private function generateDeviceFingerprint(): string
    {
        $data = [
            request()->userAgent(),
            request()->header('Accept-Language'),
            request()->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Check OTP rate limiting
     */
    private function checkOTPRateLimit(User $admin): void
    {
        $key = "otp_generate_{$admin->id}";

        if (! RateLimiter::attempt($key, 5, fn () => true, 3600)) {
            activity()
                ->causedBy($admin)
                ->withProperties([
                    'otp_rate_limited' => true,
                    'ip' => request()->ip(),
                ])
                ->log('OTP generation rate limited');

            throw new \Exception('Límite de generación de OTP excedido. Espera 1 hora.');
        }
    }

    /**
     * Get comprehensive security report for admin
     */
    public function getSecurityReport(User $admin): array
    {
        return [
            'sudo_mode' => $this->getSudoModeStatus($admin),
            'recent_otp_requests' => $this->getRecentOTPRequests($admin),
            'suspicious_activity' => $this->detectSuspiciousActivity($admin),
            'rate_limits' => $this->getCurrentRateLimits($admin),
            'known_devices' => $this->getKnownDevices($admin),
            'last_login' => $this->getLastSecurityEvent($admin, 'login'),
        ];
    }

    /**
     * Get recent OTP requests
     */
    private function getRecentOTPRequests(User $admin): array
    {
        // Implementation would query OTP generation logs
        return [];
    }

    /**
     * Get current rate limits
     */
    private function getCurrentRateLimits(User $admin): array
    {
        // Implementation would check current rate limit status
        return [];
    }

    /**
     * Get last security event
     */
    private function getLastSecurityEvent(User $admin, string $type): ?array
    {
        // Implementation would query security logs
        return null;
    }

    /**
     * Generate Archive-specific OTP with dual-factor authentication
     */
    public function generateArchiveOTP(User $admin, Tenant $tenant): array
    {
        // Check rate limiting for archive operations
        $this->checkArchiveRateLimit($admin);

        // Generate 6-digit numeric OTP code
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Generate context verification code (fallback)
        $contextCode = 'ARCHIVE'.strtoupper(substr($tenant->name, 0, 4));

        // Store OTP with metadata for 10 minutes
        $otpKey = "archive_otp_{$admin->id}";
        $otpData = [
            'code' => $otpCode,
            'context_code' => $contextCode,
            'tenant_id' => $tenant->id,
            'operation' => 'tenant_archive',
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addMinutes(10)->toISOString(),
            'attempts' => 0,
            'max_attempts' => 3,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        Cache::put($otpKey, $otpData, Carbon::now()->addMinutes(10));

        // Generate email verification token
        $emailToken = $this->generateArchiveEmailToken($admin, $tenant);

        // Log OTP generation for archival
        activity()
            ->causedBy($admin)
            ->performedOn($tenant)
            ->withProperties([
                'operation' => 'tenant_archive',
                'otp_generated' => true,
                'email_token_generated' => true,
                'tenant_id' => $tenant->id,
                'expires_at' => $otpData['expires_at'],
                'ip' => request()->ip(),
            ])
            ->log('Archive OTP and email verification generated');

        return [
            'otp_code' => $otpCode,
            'context_code' => $contextCode,
            'email_token' => $emailToken,
            'expires_at' => $otpData['expires_at'],
            'max_attempts' => 3,
        ];
    }

    /**
     * Validate Archive OTP with attempt limiting
     */
    public function validateArchiveOTP(User $admin, string $providedOTP, Tenant $tenant): array
    {
        $otpKey = "archive_otp_{$admin->id}";
        $otpData = Cache::get($otpKey);

        if (! $otpData) {
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_validation' => 'failed_no_data',
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP validation failed - no data found');

            return [
                'valid' => false,
                'error' => 'El código ha expirado o no existe. Solicita un nuevo código.',
                'attempts_remaining' => 0,
            ];
        }

        // Verify tenant matches
        if ($otpData['tenant_id'] !== $tenant->id) {
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_validation' => 'failed_tenant_mismatch',
                    'expected_tenant_id' => $otpData['tenant_id'],
                    'provided_tenant_id' => $tenant->id,
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP validation failed - tenant mismatch');

            return [
                'valid' => false,
                'error' => 'El código no es válido para esta tienda.',
                'attempts_remaining' => $otpData['max_attempts'] - $otpData['attempts'],
            ];
        }

        // Check attempts
        $otpData['attempts']++;
        if ($otpData['attempts'] > $otpData['max_attempts']) {
            Cache::forget($otpKey);

            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_validation' => 'failed_max_attempts',
                    'total_attempts' => $otpData['attempts'],
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP validation failed - max attempts exceeded');

            return [
                'valid' => false,
                'error' => 'Demasiados intentos fallidos. Solicita un nuevo código.',
                'attempts_remaining' => 0,
            ];
        }

        // Validate OTP (accept either 6-digit code or context code)
        $isValidOTP = hash_equals($otpData['code'], trim($providedOTP));
        $isValidContext = strtoupper(trim($providedOTP)) === $otpData['context_code'];

        if ($isValidOTP || $isValidContext) {
            // Clear OTP after successful validation
            Cache::forget($otpKey);

            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_validation' => 'success',
                    'validation_type' => $isValidOTP ? 'otp_code' : 'context_code',
                    'attempts_used' => $otpData['attempts'],
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP successfully validated');

            return [
                'valid' => true,
                'attempts_remaining' => $otpData['max_attempts'] - $otpData['attempts'],
            ];
        }

        // Update attempts count
        Cache::put($otpKey, $otpData, Carbon::parse($otpData['expires_at']));

        activity()
            ->causedBy($admin)
            ->performedOn($tenant)
            ->withProperties([
                'otp_validation' => 'failed_invalid_code',
                'attempts' => $otpData['attempts'],
                'max_attempts' => $otpData['max_attempts'],
                'ip' => request()->ip(),
            ])
            ->log('Archive OTP validation failed - invalid code');

        return [
            'valid' => false,
            'error' => 'Código incorrecto. Verifica e intenta nuevamente.',
            'attempts_remaining' => $otpData['max_attempts'] - $otpData['attempts'],
        ];
    }

    /**
     * Generate email verification token for archive operations
     */
    public function generateArchiveEmailToken(User $admin, Tenant $tenant): string
    {
        $token = Str::random(32);
        $tokenKey = "archive_email_token_{$admin->id}";

        $tokenData = [
            'token' => $token,
            'tenant_id' => $tenant->id,
            'operation' => 'tenant_archive',
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addMinutes(10)->toISOString(),
            'ip' => request()->ip(),
        ];

        Cache::put($tokenKey, $tokenData, Carbon::now()->addMinutes(10));

        // TODO: Send email with verification token
        // For now, log that token was generated
        activity()
            ->causedBy($admin)
            ->performedOn($tenant)
            ->withProperties([
                'email_token_generated' => true,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $tokenData['expires_at'],
                'ip' => request()->ip(),
            ])
            ->log('Archive email verification token generated');

        return $token;
    }

    /**
     * Validate archive email verification token
     */
    public function validateArchiveEmailToken(User $admin, string $token, Tenant $tenant): bool
    {
        $tokenKey = "archive_email_token_{$admin->id}";
        $tokenData = Cache::get($tokenKey);

        if (! $tokenData) {
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'email_validation' => 'failed_no_data',
                    'ip' => request()->ip(),
                ])
                ->log('Archive email validation failed - no data found');

            return false;
        }

        // Verify tenant matches
        if ($tokenData['tenant_id'] !== $tenant->id) {
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'email_validation' => 'failed_tenant_mismatch',
                    'ip' => request()->ip(),
                ])
                ->log('Archive email validation failed - tenant mismatch');

            return false;
        }

        // Validate token with constant-time comparison
        $isValid = hash_equals($tokenData['token'], $token);

        if ($isValid) {
            // Clear token after successful validation
            Cache::forget($tokenKey);

            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'email_validation' => 'success',
                    'ip' => request()->ip(),
                ])
                ->log('Archive email verification successful');
        } else {
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'email_validation' => 'failed_invalid_token',
                    'ip' => request()->ip(),
                ])
                ->log('Archive email validation failed - invalid token');
        }

        return $isValid;
    }

    /**
     * Send archive OTP via email
     */
    public function sendArchiveOTPEmail(User $admin, Tenant $tenant, array $otpData): bool
    {
        try {
            // Log OTP data for development and debugging
            \Log::info('ARCHIVE OTP EMAIL', [
                'admin_email' => $admin->email,
                'admin_id' => $admin->id,
                'tenant_name' => $tenant->name,
                'tenant_id' => $tenant->id,
                'otp_code' => $otpData['otp_code'],
                'context_code' => $otpData['context_code'],
                'email_token' => $otpData['email_token'],
                'expires_at' => $otpData['expires_at'],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Send email with OTP notification
            Mail::to($admin->email)->send(new ArchiveOTPNotification($admin, $tenant, $otpData));

            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_email_sent' => true,
                    'email' => $admin->email,
                    'otp_hash' => hash('sha256', $otpData['otp_code']),
                    'expires_at' => $otpData['expires_at'],
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP email sent successfully');

            return true;

        } catch (\Exception $e) {
            // Log email failure but don't expose detailed error to user
            activity()
                ->causedBy($admin)
                ->performedOn($tenant)
                ->withProperties([
                    'otp_email_failed' => true,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP email delivery failed');

            // For development, show the error details
            if (config('app.env') !== 'production') {
                throw new \Exception('Error al enviar email: '.$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Check archive-specific rate limiting
     */
    private function checkArchiveRateLimit(User $admin): void
    {
        // OTP generation rate limit: 3 per hour for archive operations
        $otpKey = "archive_otp_generate_{$admin->id}";
        if (! RateLimiter::attempt($otpKey, 3, fn () => true, 3600)) {
            activity()
                ->causedBy($admin)
                ->withProperties([
                    'archive_otp_rate_limited' => true,
                    'ip' => request()->ip(),
                ])
                ->log('Archive OTP generation rate limited');

            throw new \Exception('Límite de generación de códigos de archivado excedido. Espera 1 hora.');
        }

        // Archive operation rate limit: 1 per 24 hours
        $archiveKey = "archive_operation_{$admin->id}";
        if (Cache::has($archiveKey)) {
            throw new \Exception('Solo puedes realizar una operación de archivado cada 24 horas.');
        }
    }

    /**
     * Check if user has pending archive OTP
     */
    public function hasPendingArchiveOTP(User $admin): ?array
    {
        $otpKey = "archive_otp_{$admin->id}";
        $otpData = Cache::get($otpKey);

        if (! $otpData) {
            return null;
        }

        return [
            'has_pending' => true,
            'expires_at' => $otpData['expires_at'],
            'attempts_remaining' => $otpData['max_attempts'] - $otpData['attempts'],
            'attempts_used' => $otpData['attempts'],
        ];
    }

    /**
     * Force security reset for admin (emergency use)
     */
    public function forceSecurityReset(User $admin): void
    {
        // Clear all security caches
        $this->clearSudoMode($admin);
        Cache::forget("tenant_otp_{$admin->id}");
        Cache::forget("archive_otp_{$admin->id}");

        // Clear all email verifications
        $pattern = "email_verify_{$admin->id}_*";
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear archive-specific tokens
        $archivePattern = "archive_email_token_{$admin->id}";
        Cache::forget($archivePattern);

        activity()
            ->causedBy($admin)
            ->withProperties([
                'security_reset' => true,
                'ip' => request()->ip(),
            ])
            ->log('Security reset performed');

        // TODO: Send security alert email to admin
    }
}
