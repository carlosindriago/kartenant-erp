<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ArchivedTenantDetailsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public ?Tenant $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        try {
            $stats = $this->record->getArchivedStats();
            $archiveInfo = $this->record->archive_info;

            return [
                Stat::make('Días Archivado', $stats['days_archived'])
                    ->description("Desde: {$stats['archive_date']}")
                    ->icon('heroicon-o-calendar')
                    ->color('warning'),

                Stat::make('Estado Original', ucfirst($stats['original_status']))
                    ->description('Estado antes de archivar')
                    ->icon('heroicon-o-clock')
                    ->color('info'),

                Stat::make('Backups Disponibles', $stats['backup_count'])
                    ->description($stats['last_backup_date'] ? "Último: {$stats['last_backup_date']}" : 'Sin backups')
                    ->icon('heroicon-o-archive-box')
                    ->color(fn () => $stats['backup_count'] > 0 ? 'success' : 'danger'),

                Stat::make('Usuarios Registrados', $stats['user_count'])
                    ->description('Usuarios en el tenant')
                    ->icon('heroicon-o-users')
                    ->color('primary'),

                Stat::make('Tamaño de Datos', number_format($stats['data_size_mb'], 2) . ' MB')
                    ->description('Tamaño total de la base de datos')
                    ->icon('heroicon-o-server')
                    ->color('gray'),

                Stat::make('Estado de Restauración', $stats['has_conflicts'] ? 'Con Conflictos' : 'Listo para Restaurar')
                    ->description('Verificación de viabilidad')
                    ->icon($stats['has_conflicts'] ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                    ->color($stats['has_conflicts'] ? 'danger' : 'success'),
            ];
        } catch (\Exception $e) {
            return [
                Stat::make('Error', 'No se pudieron cargar las estadísticas')
                    ->description($e->getMessage())
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}