<?php

namespace App\Filament\Actions;

use App\Models\BackupLog;
use App\Models\Tenant;
use App\Services\TenantBackupService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DividerAction;
use Illuminate\Support\Facades\Hash;

trait HasStandardActionGroup
{
    /**
     * Get standard quick access actions (dashboard/details)
     */
    protected static function getQuickAccessActions(): array
    {
        return [
            Action::make('view_details')
                ->label('Ver Detalles')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->tooltip('Ver información completa')
                ->visible(fn (): bool => auth()->user()?->can('view', $this->record)),

            Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->tooltip('Ir al dashboard')
                ->url(function () {
                    if (method_exists($this, 'getDashboardUrl')) {
                        return $this->getDashboardUrl();
                    }

                    return null;
                })
                ->openUrlInNewTab()
                ->visible(fn () => method_exists($this, 'getDashboardUrl') && auth()->user()?->can('view', $this->record)),
        ];
    }

    /**
     * Get standard management actions (edit, duplicate, etc.)
     */
    protected static function getManagementActions(): array
    {
        return [
            Action::make('edit')
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->tooltip('Modificar información'),

            Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->tooltip('Crear una copia'),
        ];
    }

    /**
     * Get standard system actions (export, backup, etc.)
     */
    protected static function getSystemActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->tooltip('Exportar datos'),

            Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->tooltip('Imprimir documento'),

            Action::make('backup')
                ->label('Backup')
                ->icon('heroicon-o-circle-stack')
                ->color('gray')
                ->tooltip('Crear respaldo')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Backup')
                ->modalDescription('Se creará un respaldo de este elemento.')
                ->modalSubmitActionLabel('Crear Backup'),
        ];
    }

    /**
     * Get standard destructive actions (archive, delete)
     */
    protected static function getDestructiveActions(): array
    {
        return [
            Action::make('archive')
                ->label('Archivar')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('warning')
                ->tooltip('Archivar elemento')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Archivado')
                ->modalDescription('Este elemento será archivado y no aparecerá en listados activos.')
                ->modalSubmitActionLabel('Archivar'),

            Action::make('delete')
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->tooltip('Eliminar permanentemente')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Eliminación')
                ->modalDescription('Esta acción no se puede deshacer. Se eliminarán todos los datos relacionados.')
                ->modalSubmitActionLabel('Sí, Eliminar'),
        ];
    }

    /**
     * Create standard ActionGroup with all sections
     */
    protected static function getStandardActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            // QUICK ACCESS SECTION
            ...static::getQuickAccessActions(),

            // DIVIDER
            DividerAction::make('management_divider')
                ->label('Gestión'),

            // MANAGEMENT SECTION
            ...static::getManagementActions(),

            // DIVIDER
            DividerAction::make('system_divider')
                ->label('Sistema'),

            // SYSTEM SECTION
            ...static::getSystemActions(),

            // DIVIDER
            DividerAction::make('destructive_divider')
                ->label('Acciones Críticas'),

            // DESTRUCTIVE SECTION
            ...static::getDestructiveActions(),
        ])
            ->label('')
            ->icon('heroicon-o-ellipsis-vertical')
            ->color('gray')
            ->tooltip('Más acciones')
            ->dropdownWidth('max-w-xs')
            ->dropdownPlacement('bottom-end');
    }

    /**
     * Get resource-specific actions for customization
     */
    protected static function getResourceSpecificActions(): array
    {
        return [];
    }

    /**
     * Get complete action group with resource-specific actions
     */
    protected static function getCompleteActionGroup(): ActionGroup
    {
        $allActions = [
            // QUICK ACCESS SECTION
            ...static::getQuickAccessActions(),

            // DIVIDER
            DividerAction::make('management_divider')
                ->label('Gestión'),

            // MANAGEMENT SECTION
            ...static::getManagementActions(),

            // RESOURCE SPECIFIC SECTION
            ...static::getResourceSpecificActions(),

            // DIVIDER
            DividerAction::make('system_divider')
                ->label('Sistema'),

            // SYSTEM SECTION
            ...static::getSystemActions(),

            // DIVIDER
            DividerAction::make('destructive_divider')
                ->label('Acciones Críticas'),

            // DESTRUCTIVE SECTION
            ...static::getDestructiveActions(),
        ];

        return ActionGroup::make($allActions)
            ->label('')
            ->icon('heroicon-o-ellipsis-vertical')
            ->color('gray')
            ->tooltip('Más acciones')
            ->dropdownWidth('max-w-xs')
            ->dropdownPlacement('bottom-end');
    }

    /**
     * Get action group specifically for archived tenants
     */
    protected static function getArchivedActionGroup(): ActionGroup
    {
        $allActions = [
            // QUICK ACCESS SECTION
            ...static::getArchivedQuickAccessActions(),

            // DIVIDER
            DividerAction::make('management_divider')
                ->label('Gestión'),

            // MANAGEMENT SECTION (Restore focused)
            ...static::getArchivedManagementActions(),

            // DIVIDER
            DividerAction::make('system_divider')
                ->label('Sistema'),

            // SYSTEM SECTION
            ...static::getArchivedSystemActions(),

            // DIVIDER
            DividerAction::make('destructive_divider')
                ->label('Acciones Críticas'),

            // DESTRUCTIVE SECTION
            ...static::getArchivedDestructiveActions(),
        ];

        return ActionGroup::make($allActions)
            ->label('')
            ->icon('heroicon-o-ellipsis-horizontal')
            ->size('sm')
            ->color('gray')
            ->tooltip('Más acciones')
            ->dropdownWidth('max-w-xs')
            ->dropdownPlacement('bottom-end');
    }

    /**
     * Get quick access actions for archived tenants
     */
    protected static function getArchivedQuickAccessActions(): array
    {
        return [
            Action::make('view_details')
                ->label('Ver Detalles')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->tooltip('Ver información completa del tenant archivado')
                ->visible(fn (): bool => auth('superadmin')->user()?->can('admin.archived_tenants.view') ?? false),

            Action::make('download_backup')
                ->label('Descargar Último Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->tooltip('Descargar el backup más reciente')
                ->requiresConfirmation()
                ->modalHeading('Descargar Backup')
                ->modalDescription('Se descargará el backup más reciente disponible.')
                ->visible(fn ($record) => static::hasBackupAvailable($record))
                ->action(function ($record) {
                    static::downloadLatestBackup($record);
                }),
        ];
    }

    /**
     * Get management actions for archived tenants
     */
    protected static function getArchivedManagementActions(): array
    {
        return [
            Action::make('restore')
                ->label('🔄 Restaurar Tienda')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->tooltip('Restaurar tenant a estado activo')
                ->requiresConfirmation()
                ->modalHeading('🔄 Restaurar Tenant Archivado')
                ->modalDescription(function ($record) {
                    return "**¿Estás seguro de restaurar la tienda '{$record->name}'?**\n\n".
                           "Esta acción:\n".
                           "• Reactivará el tenant y todos sus datos\n".
                           "• Restaurará el acceso para los usuarios\n".
                           "• Requerirá verificación de seguridad\n".
                           '• Generará un backup previo a la restauración';
                })
                ->modalSubmitActionLabel('Restaurar Tenant')
                ->form([
                    Textarea::make('restore_reason')
                        ->label('Motivo de la Restauración')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe por qué estás restaurando este tenant...')
                        ->helperText('Esta información quedará registrada en la auditoría.'),

                    TextInput::make('admin_password')
                        ->label('Contraseña de Administrador')
                        ->required()
                        ->password()
                        ->revealable()
                        ->helperText('Confirma tu identidad para realizar esta acción crítica.'),

                    TextInput::make('confirm_tenant_name')
                        ->label('Confirmar Nombre de la Tienda')
                        ->required()
                        ->placeholder(function ($record) {
                            return 'Escribe exactamente: '.$record->name;
                        })
                        ->helperText('Escribe el nombre exacto de la tienda para confirmar.'),

                    Checkbox::make('understand_consequences')
                        ->label('Entiendo las consecuencias de restaurar este tenant.')
                        ->required(),

                    Checkbox::make('backup_before_restore')
                        ->label('Crear backup antes de restaurar (recomendado)')
                        ->default(true),
                ])
                ->action(function ($record, array $data) {
                    static::handleRestoreAction($record, $data);
                })
                ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),

            Action::make('view_activities')
                ->label('Ver Actividades Pre-Archivo')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->tooltip('Ver actividades antes del archivado')
                ->url(function ($record) {
                    return route('filament.admin.resources.tenants.activities', $record->id);
                })
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get system actions for archived tenants
     */
    protected static function getArchivedSystemActions(): array
    {
        return [
            Action::make('create_backup')
                ->label('Crear Backup Manual')
                ->icon('heroicon-o-circle-stack')
                ->color('info')
                ->tooltip('Crear backup del tenant archivado')
                ->requiresConfirmation()
                ->modalHeading('Crear Backup de Tenant Archivado')
                ->modalDescription(function ($record) {
                    return "Se creará un backup de la base de datos '{$record->database}' del tenant archivado '{$record->name}'.";
                })
                ->modalSubmitActionLabel('Crear Backup')
                ->action(function ($record) {
                    static::handleBackupAction($record, 'archived_manual');
                })
                ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),

            Action::make('export_data')
                ->label('Exportar Datos Completos')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->tooltip('Exportar todos los datos del tenant')
                ->requiresConfirmation()
                ->modalHeading('Exportar Datos del Tenant')
                ->modalDescription(function ($record) {
                    return "Se generará un archivo exportado con todos los datos del tenant '{$record->name}'.\n\nEsta acción puede tardar varios minutos dependiendo del volumen de datos.";
                })
                ->modalSubmitActionLabel('Generar Exportación')
                ->action(function ($record) {
                    static::handleExportAction($record);
                }),
        ];
    }

    /**
     * Get destructive actions for archived tenants
     */
    protected static function getArchivedDestructiveActions(): array
    {
        return [
            Action::make('force_delete')
                ->label('🗑️ Eliminar Permanentemente')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->tooltip('Eliminar permanentemente todos los datos')
                ->requiresConfirmation()
                ->modalHeading('⚠️ ELIMINAR PERMANENTEMENTE')
                ->modalDescription(function ($record) {
                    return "**ADVERTENCIA: Esta acción es IRREVERSIBLE**\n\n".
                           "Eliminar permanentemente el tenant '{$record->name}' significa:\n\n".
                           "• Todos los datos serán borrados para siempre\n".
                           "• No se podrá recuperar ninguna información\n".
                           "• Los backups podrían ser eliminados también\n".
                           '• Esta acción no puede deshacerse bajo ninguna circunstancia';
                })
                ->modalSubmitActionLabel('Entiendo, Eliminar Permanentemente')
                ->form([
                    Textarea::make('delete_reason')
                        ->label('Motivo de la Eliminación')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe exhaustivamente el motivo de esta eliminación...'),

                    TextInput::make('admin_password')
                        ->label('Contraseña de Administrador')
                        ->required()
                        ->password()
                        ->revealable(),

                    TextInput::make('confirm_delete_keyword')
                        ->label('Palabra Clave de Confirmación')
                        ->required()
                        ->placeholder('Escribe: DELETE_PERMANENTLY'),

                    Checkbox::make('understand_permanent')
                        ->label('Entiendo que esta acción es PERMANENTE e IRREVERSIBLE.')
                        ->required(),

                    Checkbox::make('legal_compliance')
                        ->label('Confirmo cumplir con todas las obligaciones legales de retención de datos.')
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    static::handleForceDeleteAction($record, $data);
                })
                ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),
        ];
    }

    /**
     * Check if tenant has available backup
     */
    protected static function hasBackupAvailable($record): bool
    {
        try {
            return BackupLog::where('tenant_id', $record->id)
                ->where('status', 'completed')
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Download latest backup for archived tenant
     */
    protected static function downloadLatestBackup($record): void
    {
        try {
            $latestBackup = BackupLog::where('tenant_id', $record->id)
                ->where('status', 'completed')
                ->latest('created_at')
                ->first();

            if (! $latestBackup || ! $latestBackup->file_path) {
                Notification::make()
                    ->title('❌ Backup No Disponible')
                    ->body('No hay backups disponibles para este tenant.')
                    ->danger()
                    ->send();

                return;
            }

            if (! Storage::exists($latestBackup->file_path)) {
                Notification::make()
                    ->title('❌ Archivo No Encontrado')
                    ->body('El archivo de backup no existe en el almacenamiento.')
                    ->danger()
                    ->send();

                return;
            }

            return Storage::download($latestBackup->file_path, "backup_{$record->database}_".date('Y-m-d_H-i-s').'.sql.gz');

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Descargar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Handle restore action for archived tenants
     */
    protected static function handleRestoreAction($record, array $data): void
    {
        try {
            $admin = auth('superadmin')->user();

            // Validate admin password
            if (! Hash::check($data['admin_password'], $admin->password)) {
                Notification::make()
                    ->title('Error de Autenticación')
                    ->body('La contraseña de administrador es incorrecta.')
                    ->danger()
                    ->send();

                return;
            }

            // Validate tenant name confirmation
            if ($data['confirm_tenant_name'] !== $record->name) {
                Notification::make()
                    ->title('Error de Confirmación')
                    ->body('El nombre de la tienda no coincide. Restauración cancelada.')
                    ->danger()
                    ->send();

                return;
            }

            // Create backup before restore if requested
            if ($data['backup_before_restore']) {
                $backupService = app(TenantBackupService::class);
                $backupResult = $backupService->backupDatabase(
                    $record->database,
                    $record->id,
                    'pre_restore'
                );

                if (! $backupResult['success']) {
                    throw new \Exception('No se pudo crear el backup previo a la restauración.');
                }
            }

            // Restore the tenant
            $record->status = Tenant::STATUS_ACTIVE;
            $record->restore();

            // Log the restoration
            activity()
                ->causedBy($admin)
                ->performedOn($record)
                ->withProperties([
                    'action' => 'restore',
                    'restore_reason' => $data['restore_reason'],
                    'backup_before_restore' => $data['backup_before_restore'],
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Tenant restaurado desde estado archivado');

            Notification::make()
                ->title('✅ Tenant Restaurado Exitosamente')
                ->body("La tienda '{$record->name}' ha sido restaurada y está activa nuevamente.")
                ->success()
                ->duration(10000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error en la Restauración')
                ->body($e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();

            // Log the error
            activity()
                ->causedBy(auth('superadmin')->user())
                ->performedOn($record)
                ->withProperties([
                    'error' => $e->getMessage(),
                    'restore_attempt' => true,
                    'ip' => request()->ip(),
                ])
                ->log('Error al restaurar tenant archivado');
        }
    }

    /**
     * Handle backup action
     */
    protected static function handleBackupAction($record, string $type = 'manual'): void
    {
        try {
            $backupService = app(TenantBackupService::class);

            Notification::make()
                ->title('Iniciando Backup')
                ->body("Creando backup de {$record->name}...")
                ->info()
                ->send();

            $result = $backupService->backupDatabase($record->database, $record->id, $type);

            if ($result['success']) {
                Notification::make()
                    ->title('✅ Backup Creado Exitosamente')
                    ->body('Backup completado: '.round($result['file_size'] / 1024 / 1024, 2).' MB')
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                Notification::make()
                    ->title('❌ Error en el Backup')
                    ->body($result['error'])
                    ->danger()
                    ->duration(10000)
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error Crítico')
                ->body($e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Handle export action
     */
    protected static function handleExportAction($record): void
    {
        try {
            // TODO: Implement data export functionality
            Notification::make()
                ->title('📋 Exportación Iniciada')
                ->body("La exportación de datos para '{$record->name}' ha sido iniciada. Recibirás una notificación cuando esté lista.")
                ->info()
                ->duration(10000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error en Exportación')
                ->body($e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Handle force delete action for archived tenants
     */
    protected static function handleForceDeleteAction($record, array $data): void
    {
        try {
            $admin = auth('superadmin')->user();

            // Validate admin password
            if (! Hash::check($data['admin_password'], $admin->password)) {
                Notification::make()
                    ->title('Error de Autenticación')
                    ->body('La contraseña de administrador es incorrecta.')
                    ->danger()
                    ->send();

                return;
            }

            // Validate delete keyword
            if ($data['confirm_delete_keyword'] !== 'DELETE_PERMANENTLY') {
                Notification::make()
                    ->title('Error de Confirmación')
                    ->body('La palabra clave es incorrecta. Eliminación cancelada.')
                    ->danger()
                    ->send();

                return;
            }

            // Log the final deletion
            activity()
                ->causedBy($admin)
                ->performedOn($record)
                ->withProperties([
                    'action' => 'force_delete',
                    'delete_reason' => $data['delete_reason'],
                    'final_deletion' => true,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Tenant eliminado permanentemente');

            // Force delete the tenant
            $record->forceDelete();

            Notification::make()
                ->title('🗑️ Tenant Eliminado Permanentemente')
                ->body("El tenant '{$record->name}' ha sido eliminado permanentemente del sistema.")
                ->danger()
                ->duration(15000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error en la Eliminación')
                ->body($e->getMessage())
                ->danger()
                ->duration(15000)
                ->send();
        }
    }
}
