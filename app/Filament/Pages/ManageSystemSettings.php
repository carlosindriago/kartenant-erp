<?php

namespace App\Filament\Pages;

use App\Models\SystemSettings;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración del Sistema';

    protected static ?string $title = 'Configuración del Sistema';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.manage-system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-globe-alt')
                            ->schema($this->getGeneralSchema()),

                        Tabs\Tab::make('Slack')
                            ->icon('heroicon-o-bell-alert')
                            ->schema($this->getSlackSchema()),

                        Tabs\Tab::make('Email / SMTP')
                            ->icon('heroicon-o-envelope')
                            ->schema($this->getEmailSchema()),

                        Tabs\Tab::make('Backups')
                            ->icon('heroicon-o-archive-box')
                            ->schema($this->getBackupsSchema()),

                        Tabs\Tab::make('Seguridad')
                            ->icon('heroicon-o-shield-check')
                            ->schema($this->getSecuritySchema()),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getGeneralSchema(): array
    {
        return [
            Section::make('Configuración General')
                ->description('Configuraciones básicas del sistema')
                ->schema([
                    TextInput::make('app_name')
                        ->label('Nombre de la Aplicación')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Kartenant'),

                    Select::make('app_timezone')
                        ->label('Zona Horaria')
                        ->required()
                        ->searchable()
                        ->options($this->getTimezones())
                        ->helperText('Zona horaria por defecto para el panel de administración'),

                    Select::make('app_locale')
                        ->label('Idioma')
                        ->required()
                        ->options([
                            'es' => 'Español',
                            'en' => 'English',
                            'pt' => 'Português',
                        ]),

                    Select::make('app_currency')
                        ->label('Moneda por Defecto')
                        ->required()
                        ->searchable()
                        ->options([
                            'USD' => 'USD - Dólar estadounidense',
                            'MXN' => 'MXN - Peso mexicano',
                            'COP' => 'COP - Peso colombiano',
                            'ARS' => 'ARS - Peso argentino',
                            'CLP' => 'CLP - Peso chileno',
                            'PEN' => 'PEN - Sol peruano',
                            'EUR' => 'EUR - Euro',
                        ]),
                ]),
        ];
    }

    protected function getSlackSchema(): array
    {
        return [
            Section::make('Configuración de Slack')
                ->description('Configura notificaciones de errores y alertas vía Slack')
                ->schema([
                    Toggle::make('slack_enabled')
                        ->label('Habilitar Notificaciones Slack')
                        ->helperText('Activar/desactivar notificaciones a Slack')
                        ->reactive(),

                    TextInput::make('slack_webhook_url')
                        ->label('Webhook URL')
                        ->url()
                        ->placeholder('https://hooks.slack.com/services/YOUR/WEBHOOK/URL')
                        ->helperText('Obtén tu webhook en: https://api.slack.com/apps')
                        ->required(fn ($get) => $get('slack_enabled')),

                    TextInput::make('slack_username')
                        ->label('Nombre de Usuario')
                        ->placeholder('Kartenant Security')
                        ->default('Kartenant Security'),

                    TextInput::make('slack_emoji')
                        ->label('Emoji')
                        ->placeholder(':rotating_light:')
                        ->default(':rotating_light:'),

                    Toggle::make('slack_notify_critical_errors')
                        ->label('Notificar Errores Críticos')
                        ->default(true),

                    Toggle::make('slack_notify_security_events')
                        ->label('Notificar Eventos de Seguridad')
                        ->default(true),

                    Toggle::make('slack_notify_backup_failures')
                        ->label('Notificar Fallos en Backups')
                        ->default(true),
                ]),
        ];
    }

    protected function getEmailSchema(): array
    {
        return [
            Section::make('Configuración SMTP')
                ->description('Configura el servidor de correo electrónico')
                ->schema([
                    Toggle::make('mail_enabled')
                        ->label('Habilitar Envío de Emails')
                        ->reactive(),

                    Select::make('mail_mailer')
                        ->label('Mailer')
                        ->options([
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'mailgun' => 'Mailgun',
                            'ses' => 'Amazon SES',
                        ])
                        ->default('smtp')
                        ->required(fn ($get) => $get('mail_enabled')),

                    TextInput::make('mail_host')
                        ->label('Host SMTP')
                        ->placeholder('smtp.gmail.com')
                        ->required(fn ($get) => $get('mail_enabled')),

                    TextInput::make('mail_port')
                        ->label('Puerto')
                        ->numeric()
                        ->default(587)
                        ->required(fn ($get) => $get('mail_enabled')),

                    TextInput::make('mail_username')
                        ->label('Usuario')
                        ->required(fn ($get) => $get('mail_enabled')),

                    TextInput::make('mail_password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->required(fn ($get) => $get('mail_enabled')),

                    Select::make('mail_encryption')
                        ->label('Encriptación')
                        ->options([
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                            'none' => 'Ninguna',
                        ])
                        ->default('tls'),

                    TextInput::make('mail_from_address')
                        ->label('Email Remitente')
                        ->email()
                        ->placeholder('noreply@kartenant.com')
                        ->required(fn ($get) => $get('mail_enabled')),

                    TextInput::make('mail_from_name')
                        ->label('Nombre Remitente')
                        ->placeholder('Kartenant')
                        ->required(fn ($get) => $get('mail_enabled')),
                ]),
        ];
    }

    protected function getBackupsSchema(): array
    {
        return [
            Section::make('Configuración de Backups')
                ->description('Configura el sistema automático de backups')
                ->schema([
                    Toggle::make('backup_enabled')
                        ->label('Habilitar Backups Automáticos')
                        ->helperText('Ejecuta backups diarios a las 3:00 AM')
                        ->default(true),

                    TextInput::make('backup_retention_days')
                        ->label('Días de Retención')
                        ->numeric()
                        ->default(7)
                        ->minValue(1)
                        ->maxValue(90)
                        ->helperText('Número de días que se conservarán los backups'),

                    Toggle::make('backup_notify_on_success')
                        ->label('Notificar Backups Exitosos')
                        ->default(false)
                        ->helperText('Enviar notificación cuando el backup se complete'),

                    Toggle::make('backup_notify_on_failure')
                        ->label('Notificar Fallos en Backups')
                        ->default(true),
                ]),

            Section::make('Almacenamiento Externo')
                ->description('Configura almacenamiento en la nube (próximamente)')
                ->schema([
                    Select::make('backup_disk')
                        ->label('Disco de Almacenamiento')
                        ->options([
                            'local' => 'Local (storage/app/backups)',
                            's3' => 'Amazon S3 (próximamente)',
                            'spaces' => 'DigitalOcean Spaces (próximamente)',
                        ])
                        ->default('local')
                        ->disabled(),

                    Textarea::make('backup_notes')
                        ->label('Notas')
                        ->placeholder('Para configurar almacenamiento externo S3/Spaces, contacta soporte.')
                        ->disabled(),
                ]),
        ];
    }

    protected function getSecuritySchema(): array
    {
        return [
            Section::make('Configuración de Seguridad')
                ->description('Configuraciones de seguridad del sistema')
                ->schema([
                    Toggle::make('security_2fa_required')
                        ->label('Requerir 2FA para Superadmins')
                        ->helperText('Todos los superadmins deberán habilitar 2FA')
                        ->default(true),

                    TextInput::make('security_max_login_attempts')
                        ->label('Intentos Máximos de Login')
                        ->numeric()
                        ->default(5)
                        ->minValue(3)
                        ->maxValue(10),

                    TextInput::make('security_lockout_minutes')
                        ->label('Minutos de Bloqueo')
                        ->numeric()
                        ->default(15)
                        ->minValue(5)
                        ->maxValue(60),

                    Toggle::make('security_log_all_queries')
                        ->label('Registrar Todas las Consultas SQL')
                        ->helperText('⚠️ Solo para debugging, afecta el rendimiento')
                        ->default(false),

                    Toggle::make('security_notify_new_superadmin')
                        ->label('Notificar Creación de Nuevos Superadmins')
                        ->default(true),
                ]),
        ];
    }

    protected function getSettings(): array
    {
        $groups = ['general', 'slack', 'email', 'backup', 'security'];
        $settings = [];

        foreach ($groups as $group) {
            $settings = array_merge($settings, SystemSettings::getGroup($group));
        }

        // Set defaults if not exists
        $settings['app_name'] = $settings['app_name'] ?? config('app.name');
        $settings['app_timezone'] = $settings['app_timezone'] ?? config('app.timezone');
        $settings['app_locale'] = $settings['app_locale'] ?? 'es';
        $settings['app_currency'] = $settings['app_currency'] ?? 'USD';

        return $settings;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            // Determine group from key prefix
            $group = $this->getGroupFromKey($key);
            $type = $this->getTypeFromValue($value);

            SystemSettings::set($key, $value, $group, $type);
        }

        // Clear cache
        SystemSettings::clearCache();

        Notification::make()
            ->success()
            ->title('Configuración Guardada')
            ->body('La configuración del sistema se ha guardado correctamente.')
            ->send();
    }

    protected function getGroupFromKey(string $key): string
    {
        if (str_starts_with($key, 'slack_')) {
            return 'slack';
        } elseif (str_starts_with($key, 'mail_')) {
            return 'email';
        } elseif (str_starts_with($key, 'backup_')) {
            return 'backup';
        } elseif (str_starts_with($key, 'security_')) {
            return 'security';
        }

        return 'general';
    }

    protected function getTypeFromValue(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    protected function getTimezones(): array
    {
        return collect(timezone_identifiers_list())
            ->mapWithKeys(fn ($tz) => [$tz => $tz])
            ->toArray();
    }
}
