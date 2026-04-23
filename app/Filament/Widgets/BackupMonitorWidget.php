<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Services\TenantBackupService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

/**
 * BackupMonitorWidget
 *
 * Muestra el estado de backups en el panel de superadmin
 * - Advertencias sobre limitaciones del sistema (MVP)
 * - Estadísticas generales
 * - Estado detallado por tenant
 * - Acciones rápidas (ejecutar backup manual)
 */
class BackupMonitorWidget extends Widget
{
    protected static string $view = 'filament.widgets.backup-monitor';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1; // Mostrar en la parte superior del dashboard

    /**
     * Solo mostrar en panel admin
     */
    public static function canView(): bool
    {
        // Use filament() helper for proper panel context with null checks
        $panel = filament()->getCurrentPanel();

        return $panel && $panel->getId() === 'admin' && filament()->auth()->check();
    }

    /**
     * Get system backup statistics
     */
    public function getStatistics(): array
    {
        $service = app(TenantBackupService::class);

        return $service->getSystemStatistics();
    }

    /**
     * Get problematic tenants (failed or missing backups)
     */
    public function getProblematicTenants(): array
    {
        $service = app(TenantBackupService::class);

        return $service->getProblematicTenants();
    }

    /**
     * Get all tenants with their backup status
     */
    public function getAllTenantsStatus(): array
    {
        $service = app(TenantBackupService::class);
        $tenants = Tenant::all();

        $statuses = [];

        // Add landlord first (get real DB name)
        $landlordDbName = config('database.connections.landlord.database');
        $statuses[] = [
            'id' => null,
            'name' => 'Landlord (Sistema Central)',
            'database' => $landlordDbName,
            'status' => $service->getBackupStatus(null),
        ];

        // Add all tenants
        foreach ($tenants as $tenant) {
            $statuses[] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'database' => $tenant->database,
                'status' => $service->getBackupStatus($tenant->id),
            ];
        }

        return $statuses;
    }

    /**
     * Format bytes to human readable
     */
    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }

    /**
     * Execute manual backup of all tenants
     */
    public function executeManualBackup(): void
    {
        $service = app(TenantBackupService::class);

        // Send initial notification
        Notification::make()
            ->title('Backup Manual Iniciado')
            ->body('Ejecutando backup de todas las bases de datos...')
            ->info()
            ->send();

        // Execute backups
        $results = $service->backupAllTenants('manual');

        $successCount = 0;
        $failedCount = 0;

        foreach ($results as $database => $result) {
            if ($result['success']) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        // Send completion notification
        if ($failedCount === 0) {
            Notification::make()
                ->title('Backups Completados Exitosamente')
                ->body("Se completaron {$successCount} backup(s) sin errores")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Backups Completados con Errores')
                ->body("Exitosos: {$successCount} | Fallidos: {$failedCount}")
                ->warning()
                ->send();
        }

        // Refresh widget
        $this->dispatch('$refresh');
    }
}
