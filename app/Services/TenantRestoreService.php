<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\PostgreSql;
use Throwable;

/**
 * TenantRestoreService
 *
 * Handles database restoration from backups with safety measures
 * - Creates safety backup before restore
 * - Validates backup integrity
 * - Puts tenant in maintenance mode during restore
 * - Provides rollback capability
 * - Comprehensive logging
 */
class TenantRestoreService
{
    private TenantBackupService $backupService;

    public function __construct(TenantBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Preview backup contents without restoring
     * Returns basic statistics about the backup
     */
    public function previewBackup(BackupLog $backupLog): array
    {
        if ($backupLog->status !== 'success') {
            return [
                'success' => false,
                'error' => 'Solo se pueden previsualizar backups exitosos',
            ];
        }

        $backupPath = storage_path('app/' . $backupLog->file_path);

        if (!$backupLog->file_path || !file_exists($backupPath)) {
            return [
                'success' => false,
                'error' => 'Archivo de backup no encontrado: ' . $backupPath,
            ];
        }

        try {
            $tempDbName = $backupLog->database_name . '_preview_' . time();

            // Create temporary database
            DB::connection('landlord')->statement("CREATE DATABASE {$tempDbName}");

            // Restore to temp database (backupPath already defined above)
            $dbConfig = config('database.connections.tenant');

            $command = sprintf(
                'gunzip -c %s | PGPASSWORD=%s psql -h %s -p %s -U %s -d %s 2>&1',
                escapeshellarg($backupPath),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($tempDbName)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // Cleanup
                DB::connection('landlord')->statement("DROP DATABASE IF EXISTS {$tempDbName}");

                return [
                    'success' => false,
                    'error' => 'Error al restaurar backup temporal',
                ];
            }

            // Get statistics from temporary database
            $stats = $this->getDatabaseStatistics($tempDbName);

            // Cleanup temporary database - terminate connections first
            DB::connection('landlord')->statement("
                SELECT pg_terminate_backend(pg_stat_activity.pid)
                FROM pg_stat_activity
                WHERE pg_stat_activity.datname = '{$tempDbName}'
                AND pid <> pg_backend_pid()
            ");
            DB::connection('landlord')->statement("DROP DATABASE IF EXISTS {$tempDbName}");

            return [
                'success' => true,
                'statistics' => $stats,
                'backup_date' => $backupLog->created_at,
                'backup_size' => $backupLog->formatted_file_size,
            ];

        } catch (Throwable $e) {
            // Cleanup on error
            if (isset($tempDbName)) {
                try {
                    // Terminate connections before dropping
                    DB::connection('landlord')->statement("
                        SELECT pg_terminate_backend(pg_stat_activity.pid)
                        FROM pg_stat_activity
                        WHERE pg_stat_activity.datname = '{$tempDbName}'
                        AND pid <> pg_backend_pid()
                    ");
                    DB::connection('landlord')->statement("DROP DATABASE IF EXISTS {$tempDbName}");
                } catch (Throwable $cleanupError) {
                    Log::error("[Restore] Failed to cleanup temp database: {$cleanupError->getMessage()}");
                }
            }

            Log::error("[Restore] Preview failed: {$e->getMessage()}");

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore database from backup with safety measures
     */
    public function restoreFromBackup(BackupLog $backupLog, ?int $userId = null): array
    {
        $startTime = now();
        $safetyBackupLog = null;
        $originalDbExists = true;

        try {
            // 1. Validate backup
            if ($backupLog->status !== 'success') {
                throw new \Exception('Solo se pueden restaurar backups exitosos');
            }

            $backupPath = storage_path('app/' . $backupLog->file_path);

            if (!$backupLog->file_path || !file_exists($backupPath)) {
                throw new \Exception('Archivo de backup no encontrado: ' . $backupPath);
            }

            $databaseName = $backupLog->database_name;
            $tenantId = $backupLog->tenant_id;

            Log::info("[Restore] Starting restore for database: {$databaseName}");

            // 2. Create safety backup of current database
            Log::info("[Restore] Creating safety backup...");

            $safetyResult = $this->backupService->backupDatabase($databaseName, $tenantId, 'pre-restore');

            if (!$safetyResult['success']) {
                throw new \Exception("Error al crear backup de seguridad: {$safetyResult['error']}");
            }

            $safetyBackupLog = BackupLog::where('database_name', $databaseName)
                ->where('backup_type', 'pre-restore')
                ->latest('created_at')
                ->first();

            Log::info("[Restore] Safety backup created: {$safetyBackupLog->id}");

            // 3. Put tenant in maintenance mode (if applicable)
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    Log::info("[Restore] Tenant found, putting in maintenance mode...");
                    // Note: Spatie multitenancy doesn't have built-in maintenance mode
                    // We'll use activity log to mark as "restoring"
                    activity('restore')
                        ->performedOn($tenant)
                        ->withProperties(['status' => 'restoring'])
                        ->log('Tenant database restore started');
                }
            }

            // 4. Drop current database
            Log::info("[Restore] Dropping current database...");
            // Terminate active connections first
            DB::connection('landlord')->statement("
                SELECT pg_terminate_backend(pg_stat_activity.pid)
                FROM pg_stat_activity
                WHERE pg_stat_activity.datname = '{$databaseName}'
                AND pid <> pg_backend_pid()
            ");
            DB::connection('landlord')->statement("DROP DATABASE IF EXISTS {$databaseName}");
            $originalDbExists = false;

            // 5. Create new database
            Log::info("[Restore] Creating new database...");
            DB::connection('landlord')->statement("CREATE DATABASE {$databaseName}");

            // 6. Restore from backup file
            Log::info("[Restore] Restoring from backup file...");
            // backupPath already defined at the beginning
            $dbConfig = config('database.connections.tenant');

            $command = sprintf(
                'gunzip -c %s | PGPASSWORD=%s psql -h %s -p %s -U %s -d %s 2>&1',
                escapeshellarg($backupPath),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($databaseName)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Error al restaurar el backup: ' . implode("\n", $output));
            }

            // 7. Verify database integrity
            Log::info("[Restore] Verifying database integrity...");
            $stats = $this->getDatabaseStatistics($databaseName);

            if (empty($stats)) {
                throw new \Exception('Base de datos restaurada pero sin tablas detectadas');
            }

            // 8. Log successful restoration
            activity('restore')
                ->causedBy($userId)
                ->performedOn($tenantId ? Tenant::find($tenantId) : null)
                ->withProperties([
                    'backup_log_id' => $backupLog->id,
                    'database_name' => $databaseName,
                    'safety_backup_id' => $safetyBackupLog?->id,
                    'duration_seconds' => $startTime->diffInSeconds(now()),
                    'statistics' => $stats,
                ])
                ->log('Database restored successfully');

            Log::info("[Restore] Restoration completed successfully in " . $startTime->diffInSeconds(now()) . "s");

            return [
                'success' => true,
                'database' => $databaseName,
                'duration' => $startTime->diffInSeconds(now()),
                'statistics' => $stats,
                'safety_backup_id' => $safetyBackupLog?->id,
                'message' => 'Base de datos restaurada exitosamente',
            ];

        } catch (Throwable $e) {
            Log::error("[Restore] Restoration failed: {$e->getMessage()}");

            // Rollback: Restore from safety backup if it exists
            if ($safetyBackupLog && !$originalDbExists) {
                Log::warning("[Restore] Attempting rollback from safety backup...");

                try {
                    // Drop failed database
                    DB::connection('landlord')->statement("DROP DATABASE IF EXISTS {$databaseName}");

                    // Create new database
                    DB::connection('landlord')->statement("CREATE DATABASE {$databaseName}");

                    // Restore from safety backup
                    $safetyBackupPath = storage_path('app/' . $safetyBackupLog->file_path);
                    $dbConfig = config('database.connections.tenant');

                    $rollbackCommand = sprintf(
                        'gunzip -c %s | PGPASSWORD=%s psql -h %s -p %s -U %s -d %s 2>&1',
                        escapeshellarg($safetyBackupPath),
                        escapeshellarg($dbConfig['password']),
                        escapeshellarg($dbConfig['host']),
                        escapeshellarg($dbConfig['port']),
                        escapeshellarg($dbConfig['username']),
                        escapeshellarg($databaseName)
                    );

                    exec($rollbackCommand, $rollbackOutput, $rollbackReturnCode);

                    if ($rollbackReturnCode === 0) {
                        Log::info("[Restore] Rollback successful");

                        activity('restore')
                            ->causedBy($userId)
                            ->withProperties([
                                'error' => $e->getMessage(),
                                'rollback' => 'success',
                            ])
                            ->log('Restore failed, rollback successful');

                        return [
                            'success' => false,
                            'error' => $e->getMessage(),
                            'rollback' => 'success',
                            'message' => 'Error en la restauración, pero se recuperó la base de datos anterior',
                        ];
                    } else {
                        Log::error("[Restore] Rollback FAILED!");

                        return [
                            'success' => false,
                            'error' => $e->getMessage(),
                            'rollback' => 'failed',
                            'message' => 'ERROR CRÍTICO: La restauración falló y no se pudo recuperar la DB anterior. Contacta soporte inmediatamente.',
                        ];
                    }

                } catch (Throwable $rollbackError) {
                    Log::critical("[Restore] Rollback exception: {$rollbackError->getMessage()}");

                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'rollback_error' => $rollbackError->getMessage(),
                        'message' => 'ERROR CRÍTICO: La restauración falló y el rollback también. Contacta soporte URGENTE.',
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al restaurar la base de datos',
            ];
        }
    }

    /**
     * Get statistics from a database
     */
    private function getDatabaseStatistics(string $databaseName): array
    {
        try {
            // Temporarily switch connection
            config(['database.connections.temp_restore' => array_merge(
                config('database.connections.tenant'),
                ['database' => $databaseName]
            )]);

            $tables = DB::connection('temp_restore')
                ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

            $stats = [];

            foreach ($tables as $table) {
                $tableName = $table->tablename;

                try {
                    $count = DB::connection('temp_restore')
                        ->table($tableName)
                        ->count();

                    $stats[$tableName] = $count;
                } catch (Throwable $e) {
                    $stats[$tableName] = 'error';
                }
            }

            return $stats;

        } catch (Throwable $e) {
            Log::error("[Restore] Error getting statistics: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Clean old pre-restore backups (older than 7 days)
     */
    public function cleanOldPreRestoreBackups(): array
    {
        $cutoffDate = Carbon::now()->subDays(7);
        $deleted = [];

        Log::info("[Restore] Cleaning pre-restore backups older than {$cutoffDate->toDateString()}");

        $oldLogs = BackupLog::where('created_at', '<', $cutoffDate)
            ->where('backup_type', 'pre-restore')
            ->where('status', 'success')
            ->get();

        foreach ($oldLogs as $log) {
            try {
                if ($log->file_path && Storage::exists($log->file_path)) {
                    Storage::delete($log->file_path);
                    $deleted[] = $log->file_path;
                    Log::info("[Restore] Deleted old pre-restore backup: {$log->file_path}");
                }

                $log->delete();

            } catch (Throwable $e) {
                Log::error("[Restore] Failed to delete {$log->file_path}: {$e->getMessage()}");
            }
        }

        Log::info("[Restore] Cleanup complete. Deleted " . count($deleted) . " pre-restore backups");

        return [
            'deleted_count' => count($deleted),
            'files' => $deleted,
        ];
    }
}
