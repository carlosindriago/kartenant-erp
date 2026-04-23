<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\HoneypotSubmission;
use App\Models\Tenant;
use App\Models\TrialIpTracking;
use App\Models\User;

class RegistrationValidationService
{
    public function __construct(
        private SimpleCaptchaService $captchaService
    ) {}

    /**
     * Validate complete registration request
     */
    public function validate(array $data): array
    {
        $errors = [];
        $ip = request()->ip();

        // 1. Check if IP is blocked
        if (BlockedIp::isBlocked($ip)) {
            $errors[] = 'Tu dirección IP está bloqueada. Contacta soporte si crees que es un error.';

            return $errors;
        }

        // 2. Check trial usage if requesting trial
        if (($data['plan_type'] ?? 'trial') === 'trial') {
            if (TrialIpTracking::hasUsedTrial($ip)) {
                $errors[] = 'Ya has usado un período de prueba desde esta dirección IP. Por favor, selecciona un plan de pago.';
            }
        }

        // 3. Validate domain availability
        if (isset($data['domain'])) {
            if (! $this->isDomainValid($data['domain'])) {
                $errors[] = 'El dominio contiene caracteres no permitidos.';
            }

            if ($this->isDomainTaken($data['domain'])) {
                $errors[] = 'Este dominio ya está en uso.';
            }

            if ($this->isReservedDomain($data['domain'])) {
                $errors[] = 'Este dominio está reservado.';
            }
        }

        // 4. Validate email uniqueness
        if (isset($data['email'])) {
            if ($this->isEmailTaken($data['email'])) {
                $errors[] = 'Este email ya está registrado.';
            }
        }

        // 5. Validate captcha
        if (! $this->captchaService->validate($data['captcha_answer'] ?? null)) {
            $errors[] = 'Respuesta del captcha incorrecta.';
        }

        // 6. Check honeypot
        if (! empty($data['website'] ?? '')) { // 'website' is honeypot field
            // Silent block - don't tell them
            HoneypotSubmission::recordSubmission('website', $data['website']);
            $errors[] = 'Error en el formulario. Por favor intenta nuevamente.';
        }

        return $errors;
    }

    private function isDomainValid(string $domain): bool
    {
        return preg_match('/^[a-z0-9]+([a-z0-9-]*[a-z0-9]+)?$/', $domain);
    }

    private function isDomainTaken(string $domain): bool
    {
        return Tenant::where('domain', $domain)->exists();
    }

    private function isReservedDomain(string $domain): bool
    {
        $reserved = [
            'admin', 'api', 'www', 'app', 'mail', 'ftp', 'localhost',
            'staging', 'dev', 'test', 'demo', 'support', 'help',
            'blog', 'shop', 'store', 'panel', 'dashboard', 'sistema',
        ];

        return in_array(strtolower($domain), $reserved);
    }

    private function isEmailTaken(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
}
