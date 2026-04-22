<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as FilamentNotification;
use App\Mail\WelcomeNewTenant;
use App\Models\User;
use App\Models\TenantActivity;
use App\Models\BackupLog;
use App\Services\TenantStatsService;
use App\Services\TenantBackupService;
use App\Services\TenantActivityService;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected static string $view = 'resources.tenant-resource.pages.view-tenant';

    protected ?string $maxContentWidth = 'full';

    public function infolist(Infolist $infolist): Infolist
    {
        $tenant = $this->getRecord();

        return $infolist
            ->schema([
                // HEADER SECTION - Tenant Information & Status
                Components\Section::make('Perfil de la Tienda')
                    ->description('Información general y estado actual del tenant')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                // Logo Display
                                Components\ImageEntry::make('logo_path')
                                    ->label('Logo')
                                    ->visible(fn ($record) => $record->logo_path && $record->logo_type === 'image')
                                    ->size(80)
                                    ->circular()
                                    ->defaultImageUrl(fn ($record) => $record->logo_type === 'text'
                                        ? 'data:image/svg+xml;base64,' . base64_encode(
                                            '<svg width="80" height="80" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="80" height="80" fill="' . ($record->logo_background_color ?? '#3B82F6') . '"/>
                                                <text x="40" y="45" font-family="Arial" font-size="24" font-weight="bold"
                                                      text-anchor="middle" fill="' . ($record->logo_text_color ?? '#FFFFFF') . '">
                                                    ' . strtoupper(substr($record->name, 0, 2)) . '
                                                </text>
                                            </svg>'
                                        )
                                        : null
                                    ),

                                // Basic Info Grid
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('name')
                                            ->label('Nombre de la Tienda')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->columnSpan(2),

                                        Components\TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->color(fn ($record) => $record->status_color)
                                            ->formatStateUsing(fn ($state) => match($state) {
                                                'active' => 'Activo ✅',
                                                'trial' => 'En Prueba 🧪',
                                                'suspended' => 'Suspendido ⚠️',
                                                'expired' => 'Expirado ❌',
                                                'archived' => 'Archivado 📦',
                                                'inactive' => 'Inactivo 💤',
                                                default => 'Desconocido ❓',
                                            }),
                                    ])
                                    ->columnSpan(3),
                            ]),

                        // Contact & Access Information
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('domain')
                                    ->label('Dominio')
                                    ->copyable()
                                    ->copyMessage('Dominio copiado')
                                    ->url(fn ($record) => "https://{$record->domain}.emporiodigital.test")
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-link'),

                                Components\TextEntry::make('contact_email')
                                    ->label('Email de Contacto')
                                    ->copyable()
                                    ->copyMessage('Email copiado')
                                    ->icon('heroicon-o-envelope'),

                                Components\TextEntry::make('contact_name')
                                    ->label('Contacto Principal')
                                    ->icon('heroicon-o-user'),

                                Components\TextEntry::make('phone')
                                    ->label('Teléfono')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('No configurado'),
                            ]),

                        // Subscription Information
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('activeSubscription.plan.name')
                                    ->label('Plan Actual')
                                    ->badge()
                                    ->default('Sin Plan')
                                    ->color(fn ($record) => $record->activeSubscription ? 'success' : 'gray'),

                                Components\TextEntry::make('activeSubscription.billing_cycle')
                                    ->label('Ciclo de Facturación')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'monthly' => 'Mensual 📅',
                                        'yearly' => 'Anual 📆',
                                        default => $state,
                                    })
                                    ->placeholder('N/A'),

                                Components\TextEntry::make('activeSubscription.ends_at')
                                    ->label('Próximo Vencimiento')
                                    ->date('d/m/Y')
                                    ->placeholder('N/A')
                                    ->color(function ($record) {
                                        if (!$record->activeSubscription || !$record->activeSubscription->ends_at) return 'gray';
                                        $days = now()->diffInDays($record->activeSubscription->ends_at, false);
                                        if ($days < 0) return 'danger';
                                        if ($days <= 7) return 'warning';
                                        return 'success';
                                    })
                                    ->formatStateUsing(function ($record) {
                                        if (!$record->activeSubscription || !$record->activeSubscription->ends_at) {
                                            return null;
                                        }

                                        // Usar el mismo método que el dashboard para consistencia
                                        return $record->activeSubscription->getFormattedRemainingTime();
                                    }),
                            ]),
                    ])
                    ->columns(1),

                // QUICK STATS SECTION
                Components\Section::make('Métricas Rápidas')
                    ->description('Estadísticas clave del tenant en tiempo real')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                // Storage Metrics
                                Components\Section::make('Almacenamiento')
                                    ->schema([
                                        Components\TextEntry::make('storage_used')
                                            ->label('Espacio Usado')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantStorageUsage(fn() => $record->database))
                                            ->icon('heroicon-o-server')
                                            ->size('lg')
                                            ->color('primary'),

                                        Components\TextEntry::make('file_count')
                                            ->label('Archivos')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantFileCount(fn() => $record->id))
                                            ->icon('heroicon-o-document'),
                                    ])
                                    ->compact(),

                                // User Metrics
                                Components\Section::make('Usuarios')
                                    ->schema([
                                        Components\TextEntry::make('user_count')
                                            ->label('Total Usuarios')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantUserCount(fn() => $record->id))
                                            ->icon('heroicon-o-users')
                                            ->size('lg')
                                            ->color('success'),

                                        Components\TextEntry::make('last_active')
                                            ->label('Última Actividad')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantLastActivity(fn() => $record->id))
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('Sin actividad'),
                                    ])
                                    ->compact(),

                                // Business Metrics
                                Components\Section::make('Operaciones')
                                    ->schema([
                                        Components\TextEntry::make('product_count')
                                            ->label('Productos')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantProductCount(fn() => $record->database))
                                            ->icon('heroicon-o-cube')
                                            ->size('lg')
                                            ->color('info'),

                                        Components\TextEntry::make('sales_count')
                                            ->label('Ventas Totales')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantSalesCount(fn() => $record->database))
                                            ->icon('heroicon-o-currency-dollar'),
                                    ])
                                    ->compact(),

                                // Performance Metrics
                                Components\Section::make('Rendimiento')
                                    ->schema([
                                        Components\TextEntry::make('health_score')
                                            ->label('Salud del Sistema')
                                            ->formatStateUsing(fn ($record) => TenantResource::calculateTenantHealthScore(fn() => $record))
                                            ->icon('heroicon-o-heart')
                                            ->size('lg')
                                            ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger')),

                                        Components\TextEntry::make('api_calls_today')
                                            ->label('Llamadas API (Hoy)')
                                            ->formatStateUsing(fn ($record) => TenantResource::getTenantApiCallsToday(fn() => $record->id))
                                            ->icon('heroicon-o-chart-bar'),
                                    ])
                                    ->compact(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),

                // TECHNICAL CONFIGURATION SECTION
                Components\Section::make('Configuración Técnica')
                    ->description('Detalles técnicos y configuración del tenant')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('database')
                                    ->label('Base de Datos')
                                    ->copyable()
                                    ->icon('heroicon-o-server')
                                    ->placeholder('No configurada'),

                                Components\TextEntry::make('timezone')
                                    ->label('Zona Horaria')
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('No configurada'),

                                Components\TextEntry::make('locale')
                                    ->label('Idioma')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'es' => 'Español 🇪🇸',
                                        'en' => 'English 🇺🇸',
                                        'pt' => 'Português 🇧🇷',
                                        default => $state,
                                    })
                                    ->icon('heroicon-o-language'),

                                Components\TextEntry::make('currency')
                                    ->label('Moneda')
                                    ->icon('heroicon-o-banknotes')
                                    ->placeholder('USD'),

                                Components\TextEntry::make('cuit')
                                    ->label('CUIT / RUT / RFC')
                                    ->copyable()
                                    ->icon('heroicon-o-document-text'),

                                Components\TextEntry::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ACTIVITY TIMELINE SECTION
                Components\Section::make('Actividad Reciente')
                    ->description('Últimas acciones y eventos del tenant')
                    ->schema([
                        Components\RepeatableEntry::make('recentActivities')
                            ->label('')
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i')
                                            ->size('sm'),

                                        Components\TextEntry::make('user.name')
                                            ->label('Usuario')
                                            ->formatStateUsing(fn ($state) => $state ?? 'Sistema')
                                            ->size('sm'),

                                        Components\TextEntry::make('action')
                                            ->label('Acción')
                                            ->badge()
                                            ->color(fn ($record) => $record->action_color)
                                            ->formatStateUsing(fn ($record) => $record->action_label)
                                            ->size('sm'),

                                        Components\TextEntry::make('description')
                                            ->label('Descripción')
                                            ->size('sm')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->hidden(fn ($record) => !$record->recentActivities()->exists()),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $tenant = $this->getRecord();
        $user = auth('superadmin')->user();

        return [
            // ACCESS ACTIONS
            Actions\ActionGroup::make([
                Actions\Action::make('access_dashboard')
                    ->label('Acceder al Dashboard')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url("https://{$tenant->domain}.emporiodigital.test/app")
                    ->openUrlInNewTab()
                    ->color('success')
                    ->visible($user?->can('admin.tenants.view') ?? false),

                Actions\Action::make('access_storefront')
                    ->label('Tienda Pública')
                    ->icon('heroicon-o-shopping-bag')
                    ->url("https://{$tenant->domain}.emporiodigital.test")
                    ->openUrlInNewTab()
                    ->color('info')
                    ->visible($user?->can('admin.tenants.view') ?? false),
            ])
                ->label('Acceso Rápido')
                ->icon('heroicon-o-key')
                ->color('primary'),

            // MANAGEMENT ACTIONS
            Actions\ActionGroup::make([
                Actions\EditAction::make()
                    ->label('Editar Tenant')
                    ->visible($user?->can('admin.tenants.update') ?? false),

                Actions\Action::make('manage_users')
                    ->label('Gestionar Usuarios')
                    ->icon('heroicon-o-users')
                    ->url(fn () => route('filament.admin.resources.admin-users.index'))
                    ->visible($user?->can('admin.users.view') ?? false),

                Actions\Action::make('resend_welcome')
                    ->label('Reenviar Bienvenida')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar Email de Bienvenida')
                    ->modalDescription('Se generará una nueva contraseña temporal y se enviará el email de bienvenida.')
                    ->action(function () use ($tenant) {
                        $user = User::where('email', $tenant->contact_email)->first();

                        if (!$user) {
                            $this->showNotification(
                                'Usuario no encontrado',
                                "No se encontró un usuario con el email {$tenant->contact_email}",
                                'danger'
                            );
                            return;
                        }

                        $newPassword = bin2hex(random_bytes(10));
                        $user->update([
                            'password' => Hash::make($newPassword),
                            'must_change_password' => true,
                        ]);

                        // Usar envío directo para evitar el problema MailChannel::make() de Laravel 12
                        $this->sendWelcomeEmailDirectly($user, $tenant, $newPassword);

                        $this->showNotification(
                            'Email Reenviado',
                            "Se ha enviado el email de bienvenida a {$user->email} con una nueva contraseña temporal."
                        );
                    })
                    ->visible($user?->can('admin.tenants.update') ?? false),
            ])
                ->label('Gestión')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning'),

            // MAINTENANCE ACTIONS
            Actions\ActionGroup::make([
                Actions\Action::make('backup_now')
                    ->label('Backup Manual')
                    ->icon('heroicon-o-circle-stack')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Ejecutar Backup Manual')
                    ->modalDescription("Se creará un backup de la base de datos '{$tenant->database}' de forma inmediata.")
                    ->action(function () use ($tenant) {
                        $backupService = app(TenantBackupService::class);

                        FilamentNotification::make()
                            ->title('Backup Iniciado')
                            ->body("Ejecutando backup de {$tenant->name}...")
                            ->info()
                            ->send();

                        $result = $backupService->backupDatabase($tenant->database, $tenant->id, 'manual');

                        if ($result['success']) {
                            FilamentNotification::make()
                                ->title('Backup Exitoso')
                                ->body("Backup completado: {$tenant->database} (" . round($result['file_size'] / 1024 / 1024, 2) . " MB)")
                                ->success()
                                ->send();
                        } else {
                            FilamentNotification::make()
                                ->title('Backup Fallido')
                                ->body("Error: {$result['error']}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible($user?->is_super_admin ?? false),

                Actions\Action::make('unlock_accounts')
                    ->label('Desbloquear Cuentas 2FA')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Desbloquear Cuentas de Usuario')
                    ->modalDescription('Esta acción eliminará todos los bloqueos de seguridad 2FA de los usuarios de este tenant.')
                    ->action(function () use ($tenant) {
                        $unlockedCount = 0;
                        $users = $tenant->users;

                        foreach ($users as $user) {
                            $lockoutKey = '2fa_lockout:' . $user->id;
                            $attemptKey = '2fa_attempts:' . $user->id;

                            if (Cache::has($lockoutKey)) {
                                Cache::forget($lockoutKey);
                                $unlockedCount++;
                            }

                            if (Cache::has($attemptKey)) {
                                Cache::forget($attemptKey);
                            }
                        }

                        FilamentNotification::make()
                            ->title('Cuentas Desbloqueadas')
                            ->body("Se han desbloqueado {$unlockedCount} cuentas del tenant {$tenant->name}.")
                            ->success()
                            ->send();
                    })
                    ->visible($user?->can('admin.tenants.update') ?? false),
            ])
                ->label('Mantenimiento')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray'),

            // CONTROL ACTIONS
            Actions\ActionGroup::make([
                Actions\Action::make('maintenance_mode')
                    ->label('Modo Mantenimiento')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Activar Modo Mantenimiento')
                    ->modalDescription('La tienda será temporalmente inaccesible para los usuarios.')
                    ->action(function () use ($tenant) {
                        $tenant->update(['status' => 'suspended']);

                        FilamentNotification::make()
                            ->title('Modo Mantenimiento Activado')
                            ->body("La tienda {$tenant->name} está ahora en mantenimiento.")
                            ->warning()
                            ->send();
                    })
                    ->visible($user?->can('admin.tenants.update') ?? false && $tenant->isActive()),

                Actions\Action::make('deactivate_tenant')
                    ->label('Desactivar Tienda')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar Tienda')
                    ->modalDescription("Esta acción afectará el acceso del cliente. La tienda \"{$tenant->name}\" será desactivada y los usuarios no podrán acceder.")
                    ->modalSubmitActionLabel('Desactivar Tienda')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motivo de la desactivación')
                            ->required()
                            ->rows(3),
                        \Filament\Forms\Components\TextInput::make('confirm_tenant_name')
                            ->label('Confirmar nombre de la tienda')
                            ->required()
                            ->placeholder("Escribe: {$tenant->name}"),
                        \Filament\Forms\Components\TextInput::make('admin_password')
                            ->label('Contraseña de Administrador')
                            ->required()
                            ->password()
                            ->revealable(),
                    ])
                    ->action(function (array $data) use ($tenant) {
                        if ($data['confirm_tenant_name'] !== $tenant->name) {
                            FilamentNotification::make()
                                ->title('Error de Confirmación')
                                ->body('El nombre de la tienda no coincide.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $admin = auth('superadmin')->user();
                        if (!Hash::check($data['admin_password'], $admin->password)) {
                            FilamentNotification::make()
                                ->title('Error de Autenticación')
                                ->body('La contraseña de administrador es incorrecta.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $tenant->deactivate();

                        FilamentNotification::make()
                            ->title('Tienda Desactivada')
                            ->body("La tienda {$tenant->name} ha sido desactivada.")
                            ->warning()
                            ->send();
                    })
                    ->visible($user?->can('admin.tenants.update') ?? false && $tenant->isActive()),
            ])
                ->label('Control')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getBreadcrumb(): string
    {
        return 'Detalles del Tenant';
    }

    /**
     * Mostrar notificación de Filament evitando el problema MailChannel::make()
     */
    private function showNotification(string $title, string $body, string $type = 'success'): void
    {
        // Usar session flash en lugar de FilamentNotification::make()
        // para evitar el problema ChannelManager de Laravel 12
        session()->flash('notification', [
            'title' => $title,
            'body' => $body,
            'type' => $type
        ]);
    }

    /**
     * Enviar email de bienvenida directamente evitando el problema MailChannel::make()
     */
    private function sendWelcomeEmailDirectly(User $user, Tenant $tenant, string $newPassword): void
    {
        try {
            // Crear el mailer directamente para evitar el ChannelManager
            $mailer = app(\Illuminate\Contracts\Mail\Mailer::class);

            // Construir el email manualmente
            $emailContent = $this->buildWelcomeEmailContent($user, $tenant, $newPassword);

            // Enviar usando el mailer directamente
            $mailer->raw($emailContent, function ($message) use ($user, $tenant) {
                $message->to($user->email, $user->name)
                    ->subject('¡Bienvenido a Emporio Digital!')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo
            \Log::error('Error sending welcome email directly', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);

            // Lanzar una excepción para que el usuario sepa que falló
            throw new \Exception('No se pudo enviar el email de bienvenida: ' . $e->getMessage());
        }
    }

    /**
     * Construir el contenido del email de bienvenida en HTML
     */
    private function buildWelcomeEmailContent(User $user, Tenant $tenant, string $password): string
    {
        $loginUrl = "https://{$tenant->domain}." . parse_url(config('app.url'), PHP_URL_HOST);

        return "
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
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎉 ¡Bienvenido a Emporio Digital!</h1>
            <p>Su cuenta ha sido creada exitosamente</p>
        </div>

        <div class='content'>
            <h2>¡Hola {$user->name}!</h2>

            <p>Te damos la bienvenida oficialmente a <strong>{$tenant->name}</strong>. Tu cuenta de administrador ha sido creada y está lista para usar.</p>

            <div class='credentials'>
                <h3>📧 Tus Credenciales de Acceso:</h3>
                <p><strong>Email:</strong> {$user->email}</p>
                <p><strong>Contraseña temporal:</strong> <code style='background: #f0f0f0; padding: 4px 8px; border-radius: 3px;'>{$password}</code></p>
            </div>

            <p style='text-align: center;'>
                <a href='{$loginUrl}' class='button'>🚀 Ir a mi Tienda</a>
            </p>

            <p><strong>🔐 Importante:</strong> Por seguridad, deberás cambiar tu contraseña en el primer inicio de sesión.</p>

            <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
        </div>

        <div class='footer'>
            <p>© 2025 Emporio Digital. Todos los derechos reservados.</p>
            <p>Este es un email automático, por favor no responder a esta dirección.</p>
        </div>
    </div>
</body>
</html>";
    }
}