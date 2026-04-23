<?php

namespace App\Filament\Resources\ArchivedTenantResource\Pages;

use App\Filament\Resources\ArchivedTenantResource;
use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListArchivedTenants extends ListRecords
{
    protected static string $resource = ArchivedTenantResource::class;

    protected ?string $heading = 'Tenants Archivados';

    protected ?string $subheading = 'Gestión de tiendas archivadas y desactivadas';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_stats')
                ->label('Actualizar Estadísticas')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    // Clear all cached statistics for archived tenants
                    $keys = Cache::getRedis()->keys('*archived_*');
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }

                    Notification::make()
                        ->title('Estadísticas Actualizadas')
                        ->body('Se han limpiado las cachés de estadísticas de tenants archivados.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('bulk_backup_all')
                ->label('Backup de Todos los Archivados')
                ->icon('heroicon-o-circle-stack')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Crear Backup de Todos los Tenants Archivados')
                ->modalDescription('Esta acción creará backups de todos los tenants archivados. Puede tomar bastante tiempo.')
                ->modalSubmitActionLabel('Iniciar Backups en Lote')
                ->action(function () {
                    try {
                        $archivedTenants = Tenant::onlyTrashed()
                            ->where('status', Tenant::STATUS_ARCHIVED)
                            ->get();

                        if ($archivedTenants->isEmpty()) {
                            Notification::make()
                                ->title('No Hay Tenants Archivados')
                                ->body('No se encontraron tenants archivados para realizar backups.')
                                ->info()
                                ->send();

                            return;
                        }

                        $backupService = app(\App\Services\TenantBackupService::class);
                        $successCount = 0;
                        $failedCount = 0;

                        foreach ($archivedTenants as $tenant) {
                            try {
                                $result = $backupService->backupDatabase($tenant->database, $tenant->id, 'archived_full_backup');

                                if ($result['success']) {
                                    $successCount++;
                                } else {
                                    $failedCount++;
                                }
                            } catch (\Exception $e) {
                                $failedCount++;
                            }
                        }

                        if ($failedCount === 0) {
                            Notification::make()
                                ->title('✅ Backups Completados Exitosamente')
                                ->body("Se han creado {$successCount} backup(s) de tenants archivados.")
                                ->success()
                                ->duration(10000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('⚠️ Backups Completados con Errores')
                                ->body("Exitosos: {$successCount} | Fallidos: {$failedCount}")
                                ->warning()
                                ->duration(10000)
                                ->send();
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Error en Backups en Lote')
                            ->body($e->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();
                    }
                })
                ->visible(fn () => auth('superadmin')->user()?->is_super_admin ?? false),

            Actions\Action::make('export_archived_list')
                ->label('Exportar Lista')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    try {
                        $archivedTenants = Tenant::onlyTrashed()
                            ->where('status', Tenant::STATUS_ARCHIVED)
                            ->with(['backupLogs' => function ($query) {
                                $query->latest();
                            }])
                            ->get();

                        $exportData = $archivedTenants->map(function ($tenant) {
                            $latestBackup = $tenant->backupLogs->first();

                            return [
                                'ID' => $tenant->id,
                                'Nombre' => $tenant->name,
                                'Dominio' => $tenant->domain,
                                'Email Contacto' => $tenant->contact_email,
                                'Estado' => $tenant->status_label,
                                'Fecha Archivado' => $tenant->deleted_at?->format('d/m/Y H:i'),
                                'Días Archivado' => $tenant->deleted_at ? $tenant->deleted_at->diffInDays(now()) : 'N/A',
                                'Último Backup' => $latestBackup?->created_at?->format('d/m/Y H:i') ?? 'Nunca',
                                'Estado Backup' => $latestBackup?->status ?? 'N/A',
                                'Base de Datos' => $tenant->database,
                                'CUIT/RUT' => $tenant->cuit ?? 'N/A',
                                'Teléfono' => $tenant->phone ?? 'N/A',
                                'Zona Horaria' => $tenant->timezone,
                                'Moneda' => $tenant->currency,
                            ];
                        });

                        // Generate CSV content
                        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
                        $csv .= implode(',', array_keys($exportData->first()))."\n";

                        foreach ($exportData as $row) {
                            $csv .= implode(',', array_map(function ($value) {
                                return '"'.str_replace('"', '""', $value).'"';
                            }, $row))."\n";
                        }

                        // Return download response
                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'tenants_archivados_'.now()->format('Y-m-d_H-i-s').'.csv', [
                            'Content-Type' => 'text/csv; charset=utf-8',
                        ]);

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

    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->withCount(['users', 'backupLogs'])
            ->with(['backupLogs' => function ($query) {
                $query->latest()->limit(1);
            }]);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No hay tenants archivados';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'No se encontraron tenants en estado archivado. Los tenants archivados aparecerán aquí.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\Action::make('go_to_active_tenants')
                ->label('Ver Tiendas Activas')
                ->icon('heroicon-arrow-left')
                ->url(fn () => TenantResource::getUrl('index'))
                ->color('primary'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Tenants Archivados';
    }
}
