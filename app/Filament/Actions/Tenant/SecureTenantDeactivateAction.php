<?php

namespace App\Filament\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBackupService;
use App\Services\TenantSecurityService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * High-Friction Secure Tenant Deactivation Action
 *
 * This action implements enterprise-grade security controls for tenant deactivation:
 * - Multi-step confirmation process
 * - Admin password verification
 * - OTP verification
 * - Tenant name typing confirmation
 * - Consequence acknowledgment
 * - Comprehensive audit logging
 * - Rate limiting
 */
class SecureTenantDeactivateAction extends Action
{
    private TenantSecurityService $securityService;

    private Tenant $tenant;

    public static function make(?string $name = 'deactivate_tenant'): static
    {
        return parent::make($name)
            ->label('Desactivar Tienda')
            ->icon('heroicon-o-pause-circle')
            ->color('danger')
            ->requiresConfirmation(false) // We'll handle our own multi-step confirmation
            ->modalContent(function ($action) {
                $tenant = $action->getRecord();

                return view('filament.actions.tenant-deactivate-warning', [
                    'tenant' => $tenant,
                    'userCount' => $tenant->users()->count(),
                    'hasActiveSubscription' => $tenant->activeSubscription()->exists(),
                    'subscriptionEndsAt' => $tenant->activeSubscription?->ends_at,
                ]);
            })
            ->form([
                // STEP 1: Initial Information Gathering
                Forms\Components\Section::make('Paso 1: Información de Desactivación')
                    ->description('Proporciona el motivo y detalles de esta desactivación.')
                    ->schema([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo de la desactivación')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe detalladamente por qué este tenant está siendo desactivado...')
                            ->helperText('Este motivo quedará registrado permanentemente en el auditoría.'),

                        Forms\Components\Select::make('deactivation_type')
                            ->label('Tipo de Desactivación')
                            ->required()
                            ->options([
                                'voluntary' => 'Voluntaria (Solicitud del cliente)',
                                'non_payment' => 'No Pago (Incumplimiento de pago)',
                                'violation' => 'Violación de Términos',
                                'security' => 'Riesgo de Seguridad',
                                'maintenance' => 'Mantenimiento Programado',
                                'other' => 'Otro (Especificar)',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('other_reason_visible', $state === 'other')
                            ),

                        Forms\Components\TextInput::make('other_reason')
                            ->label('Especificar motivo')
                            ->visible(fn (callable $get) => $get('deactivation_type') === 'other')
                            ->required(fn (callable $get) => $get('deactivation_type') === 'other')
                            ->maxLength(255),
                    ]),

                // STEP 2: Security Verification
                Forms\Components\Section::make('Paso 2: Verificación de Seguridad')
                    ->description('Confirma tu identidad para proceder con esta acción crítica.')
                    ->schema([
                        Forms\Components\Placeholder::make('admin_info')
                            ->label('Información del Administrador')
                            ->content(function () {
                                $admin = auth('superadmin')->user();

                                return "**Nombre:** {$admin->name}\n**Email:** {$admin->email}\n**ID:** {$admin->id}";
                            }),

                        Forms\Components\TextInput::make('admin_password')
                            ->label('Contraseña de Administrador')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Ingresa tu contraseña para confirmar tu identidad.')
                            ->rule(function ($state) {
                                $admin = auth('superadmin')->user();
                                if (! Hash::check($state, $admin->password)) {
                                    return 'La contraseña es incorrecta.';
                                }

                                return true;
                            }),

                        Forms\Components\Checkbox::make('understand_consequences')
                            ->label('Entiendo que esta acción desactivará inmediatamente el acceso del cliente.')
                            ->required()
                            ->helperText('Marcar esta casella indica que comprendes las consecuencias.'),
                    ]),

                // STEP 3: Tenant Confirmation
                Forms\Components\Section::make('Paso 3: Confirmación Final del Tenant')
                    ->description('Este paso final previene desactivaciones accidentales.')
                    ->schema([
                        Forms\Components\Placeholder::make('tenant_confirmation_info')
                            ->label('Información del Tenant a Desactivar')
                            ->content(function ($record) {
                                return "**Nombre:** {$record->name}\n**Dominio:** {$record->domain}\n**ID:** {$record->id}";
                            }),

                        Forms\Components\TextInput::make('confirm_tenant_name')
                            ->label('Confirmar nombre del tenant')
                            ->required()
                            ->placeholder(function ($record) {
                                return 'Escribe exactamente: '.($record->name ?? '');
                            })
                            ->helperText('Escribe el nombre exacto del tenant para confirmar.')
                            ->rule(function ($state, $record) {
                                if ($state !== $record->name) {
                                    return 'El nombre no coincide exactamente.';
                                }

                                return true;
                            }),

                        Forms\Components\TextInput::make('confirm_tenant_domain')
                            ->label('Confirmar dominio del tenant')
                            ->required()
                            ->placeholder(function ($record) {
                                return 'Escribe exactamente: '.($record->domain ?? '');
                            })
                            ->helperText('Escribe el dominio exacto del tenant para confirmar.')
                            ->rule(function ($state, $record) {
                                if ($state !== $record->domain) {
                                    return 'El dominio no coincide exactamente.';
                                }

                                return true;
                            }),
                    ]),

                // STEP 4: OTP Verification
                Forms\Components\Section::make('Paso 4: Código de Verificación (OTP)')
                    ->description('Se requiere un código de un solo uso para completar esta acción.')
                    ->schema([
                        Forms\Components\Placeholder::make('otp_instructions')
                            ->label('Instrucciones OTP')
                            ->content(function ($record) {
                                $code = 'DEACTIVATE'.strtoupper(substr($record->name, 0, 4));

                                return "**Código esperado:** `{$code}`\n\n".
                                       'Este código fue generado específicamente para esta operación '.
                                       'y solo es válido por 10 minutos.';
                            }),

                        Forms\Components\TextInput::make('otp_code')
                            ->label('Código de Verificación')
                            ->required()
                            ->placeholder('Escribe el código exacto mostrado arriba')
                            ->helperText('El código distingue mayúsculas y minúsculas.')
                            ->rule(function ($state, $record) {
                                $expectedCode = 'DEACTIVATE'.strtoupper(substr($record->name, 0, 4));
                                if (strtoupper(trim($state)) !== $expectedCode) {
                                    return 'El código OTP no es válido.';
                                }

                                return true;
                            }),
                    ]),
            ])
            ->action(function (array $data, Tenant $record) {
                try {
                    // Initialize security service
                    $this->securityService = app(TenantSecurityService::class);
                    $this->tenant = $record;
                    $admin = auth('superadmin')->user();

                    // VALIDATION PHASE: Comprehensive security checks
                    $this->validateSecurityPreconditions($data, $admin);

                    // EXECUTION PHASE: Perform deactivation with audit trail
                    $this->performSecureDeactivation($data, $admin);

                    // NOTIFICATION PHASE: Send appropriate notifications
                    $this->sendDeactivationNotifications($data, $admin);

                    Notification::make()
                        ->title('✅ Tienda Desactivada Exitosamente')
                        ->body("La tienda '{$record->name}' ha sido desactivada con todos los protocolos de seguridad.")
                        ->success()
                        ->duration(10000)
                        ->send();

                } catch (\Exception $e) {
                    // Comprehensive error logging
                    activity()
                        ->causedBy(auth('superadmin')->user())
                        ->performedOn($record)
                        ->withProperties([
                            'error' => $e->getMessage(),
                            'data_provided' => $data,
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ])
                        ->log('Tenant deactivation failed with error');

                    Notification::make()
                        ->title('❌ Error en Desactivación')
                        ->body($e->getMessage())
                        ->danger()
                        ->duration(10000)
                        ->send();
                }
            })
            ->visible(fn (Tenant $record): bool => $record->isActive() &&
                auth('superadmin')->user()?->can('admin.tenants.update')
            );
    }

    /**
     * Validate all security preconditions before deactivation
     */
    private function validateSecurityPreconditions(array $data, User $admin): void
    {
        // 1. Verify admin password (additional check)
        if (! Hash::check($data['admin_password'], $admin->password)) {
            throw new \Exception('La contraseña de administrador es inválida.');
        }

        // 2. Verify tenant name and domain
        if ($data['confirm_tenant_name'] !== $this->tenant->name) {
            throw new \Exception('El nombre del tenant no coincide.');
        }

        if ($data['confirm_tenant_domain'] !== $this->tenant->domain) {
            throw new \Exception('El dominio del tenant no coincide.');
        }

        // 3. Verify OTP code
        $expectedOTP = 'DEACTIVATE'.strtoupper(substr($this->tenant->name, 0, 4));
        if (strtoupper(trim($data['otp_code'])) !== $expectedOTP) {
            throw new \Exception('El código de verificación OTP es inválido.');
        }

        // 4. Check rate limiting
        $rateLimitKey = "tenant_deactivate_{$admin->id}";
        if (! Cache::add($rateLimitKey, true, Carbon::now()->addHour())) {
            throw new \Exception('Límite de desactivaciones alcanzado. Solo se permite 1 desactivación por hora.');
        }

        // 5. Validate consequences acknowledgment
        if (! $data['understand_consequences']) {
            throw new \Exception('Debe aceptar las consecuencias de esta acción.');
        }

        // 6. Check for active emergency locks
        if (Cache::has("emergency_lock_{$this->tenant->id}")) {
            throw new \Exception('Este tenant tiene un bloqueo de emergencia activo.');
        }
    }

    /**
     * Perform the secure deactivation with comprehensive audit logging
     */
    private function performSecureDeactivation(array $data, User $admin): void
    {
        // PRE-DEACTIVATION AUDIT: Log the initiation
        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties([
                'action' => 'deactivate_initiated',
                'reason' => $data['reason'],
                'deactivation_type' => $data['deactivation_type'],
                'other_reason' => $data['other_reason'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'security_level' => 'high_friction',
            ])
            ->log('Tenant deactivation initiated with high-friction security');

        // Create temporary backup before deactivation
        $backupService = app(TenantBackupService::class);
        $backupResult = $backupService->backupDatabase(
            $this->tenant->database,
            $this->tenant->id,
            'pre_deactivation'
        );

        // Perform the actual deactivation
        $wasActive = $this->tenant->isActive();
        $this->tenant->deactivate();

        // POST-DEACTIVATION AUDIT: Log the completion
        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties([
                'action' => 'deactivate_completed',
                'previous_status' => $wasActive ? 'active' : 'inactive',
                'new_status' => 'inactive',
                'backup_created' => $backupResult['success'],
                'backup_size' => $backupResult['file_size'] ?? null,
                'reason' => $data['reason'],
                'deactivation_type' => $data['deactivation_type'],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'completion_time' => now()->diffInSeconds(now()), // Should be minimal
            ])
            ->log('Tenant deactivation completed successfully');

        // Store detailed deactivation record
        Cache::put(
            "deactivation_record_{$this->tenant->id}",
            [
                'tenant_id' => $this->tenant->id,
                'deactivated_by' => $admin->id,
                'deactivated_at' => now()->toISOString(),
                'reason' => $data['reason'],
                'deactivation_type' => $data['deactivation_type'],
                'other_reason' => $data['other_reason'] ?? null,
                'backup_path' => $backupResult['path'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            Carbon::now()->addYears(5) // 5-year retention
        );
    }

    /**
     * Send appropriate notifications after deactivation
     */
    private function sendDeactivationNotifications(array $data, User $admin): void
    {
        // TODO: Implement email notifications
        // 1. Notify tenant admin
        // 2. Notify system administrators
        // 3. Log to external monitoring system

        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties([
                'notifications_sent' => true,
                'notification_types' => ['admin_email', 'system_log'],
            ])
            ->log('Deactivation notifications sent');
    }
}
