<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class SendPasswordResetEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emporio:send-password-reset {email : El email del usuario} {--tenant= : El tenant (subdomain) si se especifica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email de recuperación de contraseña a un usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tenantSubdomain = $this->option('tenant');

        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ No se encontró ningún usuario con el email: {$email}");

            return 1;
        }

        // Si se especifica tenant, verificar que el usuario pertenezca a ese tenant
        if ($tenantSubdomain) {
            $tenant = Tenant::where('domain', $tenantSubdomain)->first();
            if (! $tenant) {
                $this->error("❌ No se encontró el tenant: {$tenantSubdomain}");

                return 1;
            }

            // Verificar si el usuario está asociado a este tenant
            // Esto depende de cómo estén relacionados los usuarios con los tenants en tu sistema
            $this->info("🏢 Tenant: {$tenant->name} ({$tenant->domain})");
        }

        // Generar token de recuperación
        $token = Password::createToken($user);

        // Enviar notificación
        try {
            $user->sendPasswordResetNotification($token);

            $this->info('✅ Email de recuperación enviado exitosamente!');
            $this->info("👤 Usuario: {$user->name} ({$user->email})");
            $this->info("🔑 Token: {$token}");
            $this->info('🔗 Enlace de recuperación: '.config('app.url')."/reset-password/{$token}");

            // Mostrar información del tenant si aplica
            if ($tenantSubdomain && isset($tenant)) {
                $this->info("🌐 Acceso al tenant: https://{$tenant->domain}.".parse_url(config('app.url'), PHP_URL_HOST));
            }

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el email: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
