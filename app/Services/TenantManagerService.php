<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenancy\Role;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Hash;

class TenantManagerService
{
    /**
     * Crea un Tenant y su primer usuario administrador.
     *
     * Pasos:
     * - Crea el Tenant (dispara el Observer que prepara la DB del tenant con migraciones/seed de roles)
     * - Crea la suscripción inicial del tenant
     * - Crea el usuario en landlord y lo vincula al tenant (pivot tenant_user en landlord)
     * - Dentro del contexto del tenant, asigna el rol 'admin' al usuario
     * - Envía el email de bienvenida con credenciales
     */
    public function create(array $data): Tenant
    {
        // 1) Crear Tenant en landlord (el Observer creará DB y migrará/sembrará roles)
        $tenantData = [
            'name' => $data['name'],
            'domain' => $data['domain'],
            'database' => $data['database'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'cuit' => $data['cuit'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'timezone' => $data['timezone'] ?? 'America/Argentina/Buenos_Aires',
            'locale' => $data['locale'] ?? 'es',
            'currency' => $data['currency'] ?? 'USD',
        ];

        $tenant = Tenant::create($tenantData);

        // 2) Crear suscripción inicial
        if (isset($data['subscription_plan_id'])) {
            $this->createSubscription($tenant, $data);
        }

        // 3) Crear usuario administrador en landlord
        // Generate secure 20-character hexadecimal password (safe for all email clients)
        $password = bin2hex(random_bytes(10)); // 10 bytes = 20 hex characters
        $user = User::create([
            'name' => $data['contact_name'],
            'email' => $data['contact_email'],
            'password' => Hash::make($password),
            'must_change_password' => true, // Force password change on first login
        ]);

        // 4) Vincular usuario y tenant en pivot landlord
        $user->tenants()->attach($tenant);

        // 5) Asignar rol 'Administrador' en el contexto del tenant
        $tenant->execute(function () use ($user) {
            $adminRole = Role::findByName('Administrador', 'web');
            $originalConnection = $user->getConnectionName();
            $user->setConnection('tenant');
            $user->assignRole($adminRole);
            $user->setConnection($originalConnection);
        });

        // 6) Enviar email de bienvenida con credenciales (usando mailer directo para evitar MailChannel::make() issue)
        try {
            $mailer = app(Mailer::class);
            $loginUrl = "https://{$tenant->domain}.".parse_url(config('app.url'), PHP_URL_HOST);

            $emailContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Bienvenido a Emporio Digital</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9f9f9; }
        .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>¡Bienvenido a Emporio Digital!</h1>
            <p>Tu negocio {$tenant->name} ya está listo para operar</p>
        </div>
        <div class='content'>
            <p>Hola {$user->name},</p>
            <p>Tu cuenta ha sido creada exitosamente. A continuación te proporcionamos tus credenciales de acceso:</p>

            <div class='credentials'>
                <h3>🔐 Credenciales de Acceso</h3>
                <p><strong>Email:</strong> {$user->email}</p>
                <p><strong>Contraseña Temporal:</strong> <code>{$password}</code></p>
                <p><strong>Tu Dominio:</strong> {$tenant->domain}</p>
            </div>

            <p style='text-align: center;'>
                <a href='{$loginUrl}' class='button'>🚀 Acceder a tu Panel</a>
            </p>

            <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Esta es una contraseña <strong>temporal</strong></li>
                    <li>Deberás cambiarla en tu primer inicio de sesión</li>
                    <li>Guarda esta información en un lugar seguro</li>
                </ul>
            </div>

            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>

            <p>Saludos cordiales,<br>
            <strong>El Equipo de Emporio Digital</strong></p>
        </div>
    </div>
</body>
</html>";

            $mailer->html($emailContent, function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('¡Bienvenido a Emporio Digital!')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Exception $e) {
            // Log del error pero no interrumpir la creación del tenant
            \Log::error('Error sending welcome email during tenant creation', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $tenant;
    }

    /**
     * Crea la suscripción inicial para un tenant
     */
    protected function createSubscription(Tenant $tenant, array $data): TenantSubscription
    {
        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $billingCycle = $data['billing_cycle'] ?? 'monthly';
        $startTrial = $data['start_trial'] ?? ($plan->has_trial ? true : false);

        // Determine subscription dates
        $startsAt = now();

        if ($startTrial && $plan->has_trial) {
            // Trial period
            $trialDays = $data['trial_days_override'] ?? $plan->trial_days;
            $trialEndsAt = now()->addDays($trialDays);
            $endsAt = $trialEndsAt;
            $status = 'active';
        } else {
            // No trial - paid subscription from start
            $trialEndsAt = null;
            $endsAt = $billingCycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();
            $status = 'active';
        }

        // Determine price
        $price = $plan->getPrice($billingCycle);

        // Create subscription
        $subscription = TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => $status,
            'billing_cycle' => $billingCycle,
            'price' => $price,
            'currency' => $data['currency'] ?? 'USD',
            'starts_at' => $startsAt,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $endsAt,
            'renews_at' => $endsAt, // First renewal date
            'auto_renew' => true,
        ]);

        return $subscription;
    }
}
