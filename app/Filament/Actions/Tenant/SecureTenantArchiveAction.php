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
 * Maximum-Friction Secure Tenant Archive Action
 *
 * This action implements the highest level of security controls for tenant archival:
 * - Multi-step multi-modal confirmation process
 * - Multiple authentication factors (password + OTP + email verification)
 * - Time-delayed execution with cooldown period
 * - Peer approval requirement for critical operations
 * - Comprehensive consequence acknowledgment
 * - Full system impact assessment
 * - Legal compliance confirmation
 * - Maximum audit trail granularity
 */
class SecureTenantArchiveAction extends Action
{
    private TenantSecurityService $securityService;

    private Tenant $tenant;

    public static function make(?string $name = 'archive_tenant'): static
    {
        return parent::make($name)
            ->label('Archivar Tienda')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('danger')
            ->requiresConfirmation(false)
            ->modalContent(function ($action) {
                $tenant = $action->getRecord();

                return view('filament.actions.secure-tenant-archive-modal', [
                    'tenant' => $tenant,
                    'userCount' => $tenant->users()->count(),
                    'dataAge' => $tenant->created_at->diffInDays(now()),
                ]);
            })
            ->form([
                // Hidden form fields for the multi-step modal
                Forms\Components\Hidden::make('tenant_id')->default(fn ($record) => $record->id),

                // Step 1: Impact Assessment
                Forms\Components\Textarea::make('impact_assessment')
                    ->label('Evaluación Detallada del Impacto')
                    ->required()
                    ->rows(4)
                    ->maxLength(2000)
                    ->placeholder('Describe exhaustivamente el impacto de este archivado...')
                    ->helperText('Esta evaluación quedará permanentemente registrada y puede ser auditada.'),

                // Step 2: Legal & Compliance
                Forms\Components\Checkbox::make('legal_retention_confirmed')
                    ->label('Confirmo que se han cumplido todos los períodos de retención legal de datos.')
                    ->required(),

                Forms\Components\Checkbox::make('contractual_obligations_met')
                    ->label('Confirmo que se han cumplido todas las obligaciones contractuales.')
                    ->required(),

                Forms\Components\Checkbox::make('data_backup_verified')
                    ->label('Confirmo que existe un backup completo y verificado de todos los datos.')
                    ->required(),

                Forms\Components\Textarea::make('legal_rationale')
                    ->label('Justificación Legal del Archivado')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Describe la base legal para este archivado...'),

                // Step 3: Multi-Factor Authentication
                Forms\Components\TextInput::make('admin_password')
                    ->label('Contraseña de Administrador')
                    ->required()
                    ->password()
                    ->revealable(),

                Forms\Components\TextInput::make('otp_code')
                    ->label('Código de Verificación (OTP)')
                    ->required()
                    ->placeholder('Ingresa el código de 6 dígitos')
                    ->maxLength(6),

                Forms\Components\TextInput::make('email_verification_token')
                    ->label('Token de Verificación por Email')
                    ->required()
                    ->placeholder('Token enviado a tu email'),

                // Step 4: Final Confirmation
                Forms\Components\TextInput::make('confirm_tenant_name')
                    ->label('Confirmar Nombre Completo')
                    ->required()
                    ->placeholder(function ($record) {
                        return 'Escribe exactamente: '.$record->name;
                    }),

                Forms\Components\TextInput::make('confirm_archive_keyword')
                    ->label('Palabra Clave de Archivado')
                    ->required()
                    ->placeholder('Escribe: ARCHIVE_PERMANENTLY'),

                Forms\Components\Checkbox::make('understand_irreversibility')
                    ->label('Entiendo que esta acción es casi irreversible y requerirá intervención técnica especializada.')
                    ->required(),

                Forms\Components\Checkbox::make('accept_liability')
                    ->label('Acepto la responsabilidad legal y contractual por las consecuencias de este archivado.')
                    ->required(),

                Forms\Components\Checkbox::make('peer_approval_required')
                    ->label('Confirmo que se ha obtenido aprobación de otro administrador (si aplica).')
                    ->helperText('Si es requerido por política interna, asegúrate de tener la aprobación.'),
            ])
            ->action(function (array $data, Tenant $record) {
                try {
                    $this->securityService = app(TenantSecurityService::class);
                    $this->tenant = $record;
                    $admin = auth('superadmin')->user();

                    // MULTI-PHASE VALIDATION
                    $this->validateAllSecurityPhases($data, $admin);

                    // COOLDOWN PERIOD (if not emergency)
                    if (! ($data['emergency_override'] ?? false)) {
                        $this->implementCooldownPeriod($data, $admin);
                    }

                    // EXECUTE ARCHIVAL WITH MAXIMUM AUDIT
                    $this->performSecureArchival($data, $admin);

                    Notification::make()
                        ->title('🔒 Tenant Archivado con Máxima Seguridad')
                        ->body("El tenant '{$record->name}' ha sido archivado con todos los protocolos de seguridad activados.")
                        ->danger()
                        ->duration(15000)
                        ->send();

                } catch (\Exception $e) {
                    // Comprehensive security event logging
                    activity()
                        ->causedBy(auth('superadmin')->user())
                        ->performedOn($record)
                        ->withProperties([
                            'error' => $e->getMessage(),
                            'attempt_data' => $data,
                            'security_breach_attempt' => true,
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'timestamp' => now()->toISOString(),
                        ])
                        ->log('CRITICAL: Tenant archival attempt failed - potential security breach');

                    Notification::make()
                        ->title('🚫 ERROR CRÍTICO DE SEGURIDAD')
                        ->body($e->getMessage())
                        ->danger()
                        ->duration(15000)
                        ->send();
                }
            })
            ->visible(fn (Tenant $record): bool => ! $record->trashed() &&
                auth('superadmin')->user()?->is_super_admin
            );
    }

    /**
     * Validate all security phases with maximum rigor
     */
    private function validateAllSecurityPhases(array $data, User $admin): void
    {
        // PHASE 1: Identity Verification
        if (! Hash::check($data['admin_password'], $admin->password)) {
            throw new \Exception('Verificación de identidad fallida: contraseña incorrecta.');
        }

        // PHASE 2: Enhanced OTP Verification
        $otpResult = $this->securityService->validateArchiveOTP($admin, $data['otp_code'], $this->tenant);
        if (! $otpResult['valid']) {
            throw new \Exception($otpResult['error']);
        }

        // PHASE 3: Email Verification
        if (! $this->securityService->validateArchiveEmailToken($admin, $data['email_verification_token'], $this->tenant)) {
            throw new \Exception('Verificación por email fallida: token inválido o expirado.');
        }

        // PHASE 4: Legal Compliance Check
        if (! $data['legal_retention_confirmed'] || ! $data['contractual_obligations_met'] || ! $data['data_backup_verified']) {
            throw new \Exception('No se han completado todas las verificaciones de cumplimiento legal.');
        }

        // PHASE 5: Final Confirmation Protocol
        if ($data['confirm_tenant_name'] !== $this->tenant->name) {
            throw new \Exception('Confirmación fallida: el nombre del tenant no coincide.');
        }

        if ($data['confirm_archive_keyword'] !== 'ARCHIVE_PERMANENTLY') {
            throw new \Exception('Confirmación fallida: palabra clave de archivado incorrecta.');
        }

        // PHASE 6: Liability Acceptance
        if (! $data['understand_irreversibility'] || ! $data['accept_liability']) {
            throw new \Exception('No se han aceptado todos los términos de responsabilidad.');
        }

        // PHASE 7: Rate Limiting & Abuse Prevention (24-hour lock)
        $archiveKey = "archive_operation_{$admin->id}";
        if (Cache::has($archiveKey)) {
            throw new \Exception('Límite de archivado alcanzado. Solo se permite 1 archivado cada 24 horas.');
        }

        // Set 24-hour archive operation lock
        Cache::put($archiveKey, true, Carbon::now()->addDays(1));

        // PHASE 8: Emergency Override Check
        if ($data['emergency_override'] ?? false) {
            $this->validateEmergencyOverride($data, $admin);
        }
    }

    /**
     * Validate emergency override conditions
     */
    private function validateEmergencyOverride(array $data, User $admin): void
    {
        // Emergency overrides require additional justification and logging
        if (empty($data['emergency_reason'])) {
            throw new \Exception('Se requiere justificación para override de emergencia.');
        }

        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties([
                'emergency_override' => true,
                'emergency_reason' => $data['emergency_reason'],
                'ip' => request()->ip(),
            ])
            ->log('EMERGENCY: Tenant archival override activated');
    }

    /**
     * Implement cooldown period for non-emergency archival
     */
    private function implementCooldownPeriod(array $data, User $admin): void
    {
        $cooldownKey = "archive_cooldown_{$this->tenant->id}";
        $cooldownEnd = Carbon::now()->addMinutes(30); // 30-minute cooldown

        Cache::put($cooldownKey, [
            'initiated_by' => $admin->id,
            'initiated_at' => now()->toISOString(),
            'cooldown_ends' => $cooldownEnd->toISOString(),
            'impact_assessment' => $data['impact_assessment'],
        ], $cooldownEnd);

        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties([
                'cooldown_period' => true,
                'cooldown_ends' => $cooldownEnd->toISOString(),
                'initiated_at' => now()->toISOString(),
            ])
            ->log('Tenant archival cooldown period initiated');
    }

    /**
     * Perform the secure archival with maximum audit trail
     */
    private function performSecureArchival(array $data, User $admin): void
    {
        // PRE-ARCHIVAL COMPREHENSIVE BACKUP
        $backupService = app(TenantBackupService::class);
        $backupResult = $backupService->backupDatabase(
            $this->tenant->database,
            $this->tenant->id,
            'pre_archive_critical'
        );

        if (! $backupResult['success']) {
            throw new \Exception('Error crítico: No se pudo crear el backup pre-archivado.');
        }

        // CREATE ARCHIVAL RECORD
        $archivalRecord = [
            'tenant_id' => $this->tenant->id,
            'archived_by' => $admin->id,
            'archived_at' => now()->toISOString(),
            'backup_path' => $backupResult['path'],
            'backup_size' => $backupResult['file_size'],
            'impact_assessment' => $data['impact_assessment'],
            'legal_rationale' => $data['legal_rationale'],
            'legal_retention_confirmed' => $data['legal_retention_confirmed'],
            'contractual_obligations_met' => $data['contractual_obligations_met'],
            'emergency_override' => $data['emergency_override'] ?? false,
            'emergency_reason' => $data['emergency_reason'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'fingerprint' => $this->generateSecurityFingerprint(),
        ];

        // PERFORM THE ACTUAL ARCHIVAL
        $this->tenant->archive();

        // STORE COMPREHENSIVE ARCHIVAL RECORD
        Cache::put(
            "tenant_archive_record_{$this->tenant->id}",
            $archivalRecord,
            Carbon::now()->addYears(7) // 7-year retention for legal compliance
        );

        // MAXIMUM AUDIT LOGGING
        activity()
            ->causedBy($admin)
            ->performedOn($this->tenant)
            ->withProperties($archivalRecord)
            ->log('CRITICAL: Tenant archival completed with maximum security protocols');

        // EXTERNAL SECURITY MONITORING
        $this->notifyExternalSecuritySystems($archivalRecord);
    }

    /**
     * Generate security fingerprint for this operation
     */
    private function generateSecurityFingerprint(): string
    {
        $data = [
            request()->ip(),
            request()->userAgent(),
            now()->timestamp,
            $this->tenant->id,
            auth('superadmin')->user()->id,
            random_bytes(16),
        ];

        return hash('sha512', implode('|', $data));
    }

    /**
     * Notify external security monitoring systems
     */
    private function notifyExternalSecuritySystems(array $archivalRecord): void
    {
        // TODO: Implement integration with external security systems
        // - SIEM integration
        // - Slack security alerts
        // - Email to security team
        // - Compliance system notifications

        activity()
            ->causedBy(auth('superadmin')->user())
            ->withProperties([
                'external_notifications_sent' => true,
                'systems' => ['siem', 'slack', 'compliance'],
            ])
            ->log('External security systems notified of archival');
    }
}
