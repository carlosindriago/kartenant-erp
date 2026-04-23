<?php

namespace Database\Seeders;

use App\Models\SystemSettings;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => config('app.name', 'Kartenant'),
                'group' => 'general',
                'type' => 'string',
                'description' => 'Nombre de la aplicación',
                'is_public' => true,
            ],
            [
                'key' => 'app_timezone',
                'value' => config('app.timezone', 'UTC'),
                'group' => 'general',
                'type' => 'string',
                'description' => 'Zona horaria del sistema',
                'is_public' => false,
            ],
            [
                'key' => 'app_locale',
                'value' => 'es',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Idioma por defecto',
                'is_public' => true,
            ],
            [
                'key' => 'app_currency',
                'value' => 'USD',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Moneda por defecto',
                'is_public' => true,
            ],

            // Slack Settings
            [
                'key' => 'slack_enabled',
                'value' => 'false',
                'group' => 'slack',
                'type' => 'boolean',
                'description' => 'Habilitar notificaciones de Slack',
                'is_public' => false,
            ],
            [
                'key' => 'slack_webhook_url',
                'value' => config('logging.channels.slack.url', ''),
                'group' => 'slack',
                'type' => 'string',
                'description' => 'URL del webhook de Slack',
                'is_public' => false,
            ],
            [
                'key' => 'slack_username',
                'value' => 'Kartenant Security',
                'group' => 'slack',
                'type' => 'string',
                'description' => 'Nombre de usuario para Slack',
                'is_public' => false,
            ],
            [
                'key' => 'slack_emoji',
                'value' => ':rotating_light:',
                'group' => 'slack',
                'type' => 'string',
                'description' => 'Emoji para notificaciones',
                'is_public' => false,
            ],
            [
                'key' => 'slack_notify_critical_errors',
                'value' => 'true',
                'group' => 'slack',
                'type' => 'boolean',
                'description' => 'Notificar errores críticos',
                'is_public' => false,
            ],
            [
                'key' => 'slack_notify_security_events',
                'value' => 'true',
                'group' => 'slack',
                'type' => 'boolean',
                'description' => 'Notificar eventos de seguridad',
                'is_public' => false,
            ],
            [
                'key' => 'slack_notify_backup_failures',
                'value' => 'true',
                'group' => 'slack',
                'type' => 'boolean',
                'description' => 'Notificar fallos en backups',
                'is_public' => false,
            ],

            // Email/SMTP Settings
            [
                'key' => 'mail_enabled',
                'value' => 'false',
                'group' => 'email',
                'type' => 'boolean',
                'description' => 'Habilitar envío de emails',
                'is_public' => false,
            ],
            [
                'key' => 'mail_mailer',
                'value' => 'smtp',
                'group' => 'email',
                'type' => 'string',
                'description' => 'Driver de mail',
                'is_public' => false,
            ],
            [
                'key' => 'mail_host',
                'value' => config('mail.mailers.smtp.host', ''),
                'group' => 'email',
                'type' => 'string',
                'description' => 'Host SMTP',
                'is_public' => false,
            ],
            [
                'key' => 'mail_port',
                'value' => (string) config('mail.mailers.smtp.port', 587),
                'group' => 'email',
                'type' => 'integer',
                'description' => 'Puerto SMTP',
                'is_public' => false,
            ],
            [
                'key' => 'mail_encryption',
                'value' => config('mail.mailers.smtp.encryption', 'tls'),
                'group' => 'email',
                'type' => 'string',
                'description' => 'Encriptación',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_address',
                'value' => config('mail.from.address', ''),
                'group' => 'email',
                'type' => 'string',
                'description' => 'Email remitente',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_name',
                'value' => config('mail.from.name', ''),
                'group' => 'email',
                'type' => 'string',
                'description' => 'Nombre remitente',
                'is_public' => false,
            ],

            // Backup Settings
            [
                'key' => 'backup_enabled',
                'value' => 'true',
                'group' => 'backup',
                'type' => 'boolean',
                'description' => 'Habilitar backups automáticos',
                'is_public' => false,
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '7',
                'group' => 'backup',
                'type' => 'integer',
                'description' => 'Días de retención de backups',
                'is_public' => false,
            ],
            [
                'key' => 'backup_notify_on_success',
                'value' => 'false',
                'group' => 'backup',
                'type' => 'boolean',
                'description' => 'Notificar backups exitosos',
                'is_public' => false,
            ],
            [
                'key' => 'backup_notify_on_failure',
                'value' => 'true',
                'group' => 'backup',
                'type' => 'boolean',
                'description' => 'Notificar fallos en backups',
                'is_public' => false,
            ],
            [
                'key' => 'backup_disk',
                'value' => 'local',
                'group' => 'backup',
                'type' => 'string',
                'description' => 'Disco de almacenamiento de backups',
                'is_public' => false,
            ],

            // Security Settings
            [
                'key' => 'security_2fa_required',
                'value' => 'true',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Requerir 2FA para superadmins',
                'is_public' => false,
            ],
            [
                'key' => 'security_max_login_attempts',
                'value' => '5',
                'group' => 'security',
                'type' => 'integer',
                'description' => 'Intentos máximos de login',
                'is_public' => false,
            ],
            [
                'key' => 'security_lockout_minutes',
                'value' => '15',
                'group' => 'security',
                'type' => 'integer',
                'description' => 'Minutos de bloqueo',
                'is_public' => false,
            ],
            [
                'key' => 'security_log_all_queries',
                'value' => 'false',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Registrar todas las consultas SQL',
                'is_public' => false,
            ],
            [
                'key' => 'security_notify_new_superadmin',
                'value' => 'true',
                'group' => 'security',
                'type' => 'boolean',
                'description' => 'Notificar creación de nuevos superadmins',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSettings::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✓ System settings seeded successfully');
    }
}
