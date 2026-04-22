<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantSecurityService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TenantArchiveOTPManager extends Component
{
    public ?Tenant $tenant = null;
    public ?User $admin = null;
    public string $otpCode = '';
    public string $contextCode = '';
    public string $emailToken = '';
    public string $expiresAt = '';
    public int $maxAttempts = 3;
    public bool $otpGenerated = false;
    public bool $generating = false;
    public string $error = '';
    public string $success = '';

    protected TenantSecurityService $securityService;

    protected $listeners = [
        'generateArchiveOTP' => 'generateOTP',
        'validateArchiveOTP' => 'validateOTP',
    ];

    public function boot()
    {
        $this->securityService = app(TenantSecurityService::class);
        $this->admin = Auth::guard('superadmin')->user();
    }

    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Generate OTP for archive operation
     */
    public function generateOTP(): void
    {
        $this->reset(['error', 'success']);
        $this->generating = true;

        try {
            // Generate OTP and email verification
            $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

            // Send OTP via email
            $emailSent = $this->securityService->sendArchiveOTPEmail($this->admin, $this->tenant, $otpData);

            if (!$emailSent) {
                $this->error = 'Error al enviar el código por email. Intenta nuevamente.';
                return;
            }

            // Set component data
            $this->otpCode = $otpData['otp_code'];
            $this->contextCode = $otpData['context_code'];
            $this->emailToken = $otpData['email_token'];
            $this->expiresAt = $otpData['expires_at'];
            $this->maxAttempts = $otpData['max_attempts'];
            $this->otpGenerated = true;

            $this->success = 'Código de verificación enviado correctamente. Revisa tu email.';

            // Dispatch event to update frontend
            $this->dispatch('otpGenerated', [
                'context_code' => $this->contextCode,
                'expires_at' => $this->expiresAt,
                'max_attempts' => $this->maxAttempts,
            ]);

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->generating = false;
        }
    }

    /**
     * Validate OTP provided by user
     */
    public function validateOTP(string $providedOTP): array
    {
        $this->reset(['error']);

        try {
            $result = $this->securityService->validateArchiveOTP($this->admin, $providedOTP, $this->tenant);

            if ($result['valid']) {
                $this->success = 'OTP validado correctamente.';
                return ['valid' => true];
            } else {
                $this->error = $result['error'];
                return [
                    'valid' => false,
                    'error' => $result['error'],
                    'attempts_remaining' => $result['attempts_remaining'],
                ];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate email token
     */
    public function validateEmailToken(string $token): bool
    {
        try {
            return $this->securityService->validateArchiveEmailToken($this->admin, $token, $this->tenant);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Check if user has pending OTP
     */
    public function hasPendingOTP(): ?array
    {
        return $this->securityService->hasPendingArchiveOTP($this->admin);
    }

    /**
     * Resend OTP if expired or not received
     */
    public function resendOTP(): void
    {
        // Clear any existing OTP
        $this->otpGenerated = false;
        $this->reset(['otpCode', 'contextCode', 'emailToken', 'error', 'success']);

        // Generate new OTP
        $this->generateOTP();
    }

    /**
     * Get OTP status for display
     */
    public function getOTPStatus(): array
    {
        if (!$this->otpGenerated) {
            return [
                'generated' => false,
                'message' => 'No se ha generado ningún código',
            ];
        }

        $pending = $this->hasPendingOTP();
        if (!$pending) {
            return [
                'generated' => true,
                'expired' => true,
                'message' => 'El código ha expirado. Genera uno nuevo.',
            ];
        }

        $timeRemaining = now()->diffInMinutes($pending['expires_at'], true);
        $status = $timeRemaining < 2 ? 'expiring' : ($timeRemaining < 5 ? 'warning' : 'active');

        return [
            'generated' => true,
            'expired' => false,
            'status' => $status,
            'expires_at' => $pending['expires_at'],
            'attempts_remaining' => $pending['attempts_remaining'],
            'attempts_used' => $pending['attempts_used'],
            'message' => $timeRemaining < 2
                ? 'El código está por expirar'
                : "El código expira en {$timeRemaining} minutos",
        ];
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.tenant-archive-otp-manager');
    }
}