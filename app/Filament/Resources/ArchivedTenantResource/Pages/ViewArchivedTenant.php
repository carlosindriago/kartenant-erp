<?php

namespace App\Filament\Resources\ArchivedTenantResource\Pages;

use App\Filament\Resources\ArchivedTenantResource;
use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;

class ViewArchivedTenant extends ViewRecord
{
    protected static string $resource = ArchivedTenantResource::class;

    protected ?string $heading = 'Detalles del Tenant Archivado';

    protected ?string $subheading = 'Información completa del tenant archivado';

    /**
     * Resolve the record for the ViewArchivedTenant page.
     * CRITICAL: Must include withTrashed() to find soft-deleted tenants.
     *
     * @param  int | string  $key
     */
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()
            ->withTrashed() // <--- CRITICAL FIX: Include soft-deleted records
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('go_back')
                ->label('Volver a la Lista')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => ArchivedTenantResource::getUrl('index'))
                ->color('gray'),

            Actions\Action::make('refresh_data')
                ->label('Actualizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    // Clear cache for this specific tenant
                    $tenant = $this->record;
                    $patterns = [
                        "*archived_tenant_users_{$tenant->id}*",
                        "*archived_storage_{$tenant->database}*",
                        "*archived_products_{$tenant->database}*",
                        "*archived_sales_{$tenant->database}*",
                        "*archived_activity_{$tenant->id}*",
                        "*archived_files_{$tenant->id}*",
                    ];

                    foreach ($patterns as $pattern) {
                        $keys = Cache::getRedis()->keys($pattern);
                        foreach ($keys as $key) {
                            Cache::forget($key);
                        }
                    }

                    Notification::make()
                        ->title('Datos Actualizados')
                        ->body('Se han actualizado las estadísticas de este tenant.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('create_backup')
                ->label('Crear Backup Ahora')
                ->icon('heroicon-o-circle-stack')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Crear Backup del Tenant Archivado')
                ->modalDescription(function ($record) {
                    return "Se creará un backup inmediato de la base de datos '{$record->database}' del tenant '{$record->name}'.";
                })
                ->action(function ($record) {
                    try {
                        $backupService = app(\App\Services\TenantBackupService::class);

                        Notification::make()
                            ->title('Iniciando Backup')
                            ->body("Creando backup de {$record->name}...")
                            ->info()
                            ->send();

                        $result = $backupService->backupDatabase($record->database, $record->id, 'archived_manual_view');

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
                }),

            Actions\Action::make('restore_tenant')
                ->label('Restaurar Tenant')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('🔄 Restaurar Tenant')
                ->modalDescription(function ($record) {
                    return "**¿Confirmas la restauración del tenant '{$record->name}'?**\n\n".
                           "Esta acción:\n".
                           "• Reactivará el tenant y restaurará el acceso\n".
                           "• Cambiará el estado de 'archived' a 'active'\n".
                           "• Permitirá que los usuarios accedan nuevamente\n".
                           '• Generará un backup previo a la restauración';
                })
                ->modalSubmitActionLabel('Restaurar Tenant')
                ->form([
                    \Filament\Forms\Components\Textarea::make('restore_reason')
                        ->label('Motivo de la Restauración')
                        ->required()
                        ->rows(3)
                        ->placeholder('Describe por qué necesitas restaurar este tenant...'),

                    \Filament\Forms\Components\TextInput::make('admin_password')
                        ->label('Contraseña de Administrador')
                        ->required()
                        ->password()
                        ->revealable()
                        ->helperText('Ingresa tu contraseña para confirmar esta acción.'),

                    \Filament\Forms\Components\TextInput::make('confirm_tenant_name')
                        ->label('Confirmar Nombre del Tenant')
                        ->required()
                        ->placeholder(function ($record) {
                            return 'Escribe exactamente: '.$record->name;
                        })
                        ->helperText('Escribe el nombre exacto del tenant para confirmar.'),

                    \Filament\Forms\Components\Checkbox::make('backup_before_restore')
                        ->label('Crear backup antes de restaurar (recomendado)')
                        ->default(true),

                    \Filament\Forms\Components\Checkbox::make('understand_consequences')
                        ->label('Entiendo las consecuencias de esta restauración.')
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $admin = auth('superadmin')->user();

                        // Validate admin password
                        if (! \Illuminate\Support\Facades\Hash::check($data['admin_password'], $admin->password)) {
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
                                ->body('El nombre del tenant no coincide. Restauración cancelada.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Create backup before restore if requested
                        if ($data['backup_before_restore']) {
                            $backupService = app(\App\Services\TenantBackupService::class);
                            $backupResult = $backupService->backupDatabase($record->database, $record->id, 'pre_restore');

                            if (! $backupResult['success']) {
                                throw new \Exception('No se pudo crear el backup previo a la restauración.');
                            }
                        }

                        // Restore the tenant
                        $record->status = \App\Models\Tenant::STATUS_ACTIVE;
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
                                'restored_from_view' => true,
                            ])
                            ->log('Tenant restaurado desde vista detallada');

                        Notification::make()
                            ->title('✅ Tenant Restaurado Exitosamente')
                            ->body("El tenant '{$record->name}' ha sido restaurado y ahora está activo.")
                            ->success()
                            ->duration(10000)
                            ->send();

                        // Redirect to active tenant view
                        $this->redirect(TenantResource::getUrl('view', ['record' => $record]));

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
                                'restored_from_view' => true,
                            ])
                            ->log('Error al restaurar tenant archivado desde vista');
                    }
                })
                ->visible(fn () => auth('superadmin')->user()?->is_super_admin ?? false),

            Actions\Action::make('export_tenant_data')
                ->label('Exportar Datos Completos')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Exportar Datos del Tenant')
                ->modalDescription(function ($record) {
                    return "Se generará una exportación completa de todos los datos del tenant '{$record->name}'. Este proceso puede tomar varios minutos.";
                })
                ->modalSubmitActionLabel('Iniciar Exportación')
                ->action(function ($record) {
                    try {
                        // TODO: Implement comprehensive data export
                        Notification::make()
                            ->title('📋 Exportación Iniciada')
                            ->body("La exportación de datos para '{$record->name}' ha sido iniciada. Recibirás una notificación cuando esté completa.")
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
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            ArchivedTenantResource::getUrl('index') => 'Tenants Archivados',
            $this->record->name,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\ArchivedTenantDetailsWidget::class,
        ];
    }
}
