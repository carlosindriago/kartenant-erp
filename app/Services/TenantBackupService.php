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
 * TenantBackupService
 *
 * Handles multi-tenant database backups
 * - Backs up landlord database
 * - Backs up all tenant databases
 * - Stores backups locally in storage/app/backups
 * - Manages retention (7 days)
 * - Logs all operations to backup_logs table
 */
class TenantBackupService
{
    private const RETENTION_DAYS = 7;
    private const BACKUP_PATH = 'backups';

    /**
     * Backup all databases (landlord + all tenants)
     */
    public function backupAllTenants(string $type = 'daily'): array
    {
        $results = [];

        // 1. Backup landlord database (get real DB name from config)
        $landlordDbName = config('database.connections.landlord.database');
        Log::info("[Backup] Starting landlord database backup (DB: {$landlordDbName})");
        $results['landlord'] = $this->backupDatabase($landlordDbName, null, $type);

        // 2. Backup all tenant databases
        $tenants = Tenant::all();
        Log::info("[Backup] Found {$tenants->count()} tenants to backup");

        foreach ($tenants as $tenant) {
            Log::info("[Backup] Starting backup for tenant: {$tenant->name} (DB: {$tenant->database})");
            $results[$tenant->database] = $this->backupDatabase($tenant->database, $tenant->id, $type);
        }

        return $results;
    }

    /**
     * Backup a single database
     */
    public function backupDatabase(string $databaseName, ?int $tenantId, string $type = 'daily'): array
    {
        $log = BackupLog::create([
            'tenant_id' => $tenantId,
            'database_name' => $databaseName,
            'status' => 'running',
            'started_at' => now(),
            'backup_type' => $type,
        ]);

        try {
            // Generate filename
            $date = now()->format('Y-m-d');
            $time = now()->format('H-i-s');
            $filename = "{$type}/{$date}/{$time}-{$databaseName}.sql.gz";
            $fullPath = storage_path('app/' . self::BACKUP_PATH . '/' . $filename);

            // Create directory if not exists
            File::ensureDirectoryExists(dirname($fullPath));

            // Get database credentials from config
            $connection = $databaseName === 'landlord' ? 'landlord' : 'tenant';
            $dbConfig = config("database.connections.{$connection}");

            // Create PostgreSQL dump
            PostgreSql::create()
                ->setHost($dbConfig['host'])
                ->setPort($dbConfig['port'])
                ->setDbName($databaseName)
                ->setUserName($dbConfig['username'])
                ->setPassword($dbConfig['password'])
                ->useCompressor(new \Spatie\DbDumper\Compressors\GzipCompressor())
                ->dumpToFile($fullPath);

            // Get file size
            $fileSize = File::size($fullPath);

            // Update log as successful
            $log->update([
                'status' => 'success',
                'file_path' => self::BACKUP_PATH . '/' . $filename,
                'file_size' => $fileSize,
                'completed_at' => now(),
            ]);

            Log::info("[Backup] SUCCESS: {$databaseName} - {$log->formatted_file_size}");

            return [
                'success' => true,
                'database' => $databaseName,
                'file_path' => $filename,
                'file_size' => $fileSize,
                'duration' => $log->duration,
            ];

        } catch (Throwable $e) {
            // Update log as failed
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error("[Backup] FAILED: {$databaseName} - {$e->getMessage()}");

            return [
                'success' => false,
                'database' => $databaseName,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean old backups (older than RETENTION_DAYS)
     * Includes daily, manual, and pre-restore backups
     */
    public function cleanOldBackups(): array
    {
        $cutoffDate = Carbon::now()->subDays(self::RETENTION_DAYS);
        $deleted = [];

        Log::info("[Backup] Cleaning backups older than {$cutoffDate->toDateString()}");

        // Get old backup logs (all types: daily, manual, pre-restore)
        $oldLogs = BackupLog::where('created_at', '<', $cutoffDate)
            ->where('status', 'success')
            ->get();

        foreach ($oldLogs as $log) {
            try {
                // Delete physical file if exists
                if ($log->file_path && Storage::exists($log->file_path)) {
                    Storage::delete($log->file_path);
                    $deleted[] = $log->file_path;
                    Log::info("[Backup] Deleted old backup: {$log->file_path} (type: {$log->backup_type})");
                }

                // Delete log record
                $log->delete();

            } catch (Throwable $e) {
                Log::error("[Backup] Failed to delete {$log->file_path}: {$e->getMessage()}");
            }
        }

        // Clean empty directories
        $this->cleanEmptyDirectories();

        Log::info("[Backup] Cleanup complete. Deleted " . count($deleted) . " backups");

        return [
            'deleted_count' => count($deleted),
            'files' => $deleted,
        ];
    }

    /**
     * Get backup status for a specific tenant
     */
    public function getBackupStatus(?int $tenantId): array
    {
        if ($tenantId === null) {
            // Landlord status - get real DB name from config
            $landlordDbName = config('database.connections.landlord.database');
            $latestBackup = BackupLog::whereNull('tenant_id')
                ->where('database_name', $landlordDbName)
                ->latest('created_at')
                ->first();
        } else {
            $latestBackup = BackupLog::where('tenant_id', $tenantId)
                ->latest('created_at')
                ->first();
        }

        if (!$latestBackup) {
            return [
                'status' => 'never',
                'message' => 'No se han realizado backups',
            ];
        }

        $hoursAgo = $latestBackup->created_at->diffInHours(now());

        return [
            'status' => $latestBackup->status,
            'last_backup' => $latestBackup->created_at,
            'hours_ago' => $hoursAgo,
            'file_size' => $latestBackup->formatted_file_size,
            'message' => $this->getStatusMessage($latestBackup, $hoursAgo),
        ];
    }

    /**
     * Get overall system backup statistics
     */
    public function getSystemStatistics(): array
    {
        $today = now()->startOfDay();

        return [
            'total_tenants' => Tenant::count(),
            'backups_today_success' => BackupLog::where('created_at', '>=', $today)
                ->where('status', 'success')
                ->count(),
            'backups_today_failed' => BackupLog::where('created_at', '>=', $today)
                ->where('status', 'failed')
                ->count(),
            'last_backup' => BackupLog::latest('created_at')->first(),
            'total_storage_used' => BackupLog::where('status', 'success')->sum('file_size'),
            'oldest_backup' => BackupLog::where('status', 'success')
                ->oldest('created_at')
                ->first(),
        ];
    }

    /**
     * Get tenants with failed or missing backups
     */
    public function getProblematicTenants(): array
    {
        $problematic = [];

        // Check landlord (get real DB name)
        $landlordDbName = config('database.connections.landlord.database');
        $landlordStatus = $this->getBackupStatus(null);
        if ($landlordStatus['status'] === 'failed' || ($landlordStatus['status'] !== 'never' && $landlordStatus['hours_ago'] > 24)) {
            $problematic[] = [
                'id' => null,
                'name' => 'Landlord (Sistema Central)',
                'database' => $landlordDbName,
                'status' => $landlordStatus,
            ];
        }

        // Check all tenants
        foreach (Tenant::all() as $tenant) {
            $status = $this->getBackupStatus($tenant->id);
            if ($status['status'] === 'failed' || $status['status'] === 'never' || $status['hours_ago'] > 24) {
                $problematic[] = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'database' => $tenant->database,
                    'status' => $status,
                ];
            }
        }

        return $problematic;
    }

    /**
     * Clean empty directories in backup path
     */
    private function cleanEmptyDirectories(): void
    {
        $backupBasePath = storage_path('app/' . self::BACKUP_PATH);

        if (!File::isDirectory($backupBasePath)) {
            return;
        }

        $directories = File::directories($backupBasePath);

        foreach ($directories as $dir) {
            if ($this->isDirectoryEmpty($dir)) {
                File::deleteDirectory($dir);
                Log::info("[Backup] Deleted empty directory: {$dir}");
            } else {
                // Check subdirectories
                $subdirs = File::directories($dir);
                foreach ($subdirs as $subdir) {
                    if ($this->isDirectoryEmpty($subdir)) {
                        File::deleteDirectory($subdir);
                        Log::info("[Backup] Deleted empty subdirectory: {$subdir}");
                    }
                }
            }
        }
    }

    /**
     * Check if directory is empty
     */
    private function isDirectoryEmpty(string $directory): bool
    {
        $files = File::allFiles($directory);
        $directories = File::directories($directory);

        return count($files) === 0 && count($directories) === 0;
    }

    /**
     * Get status message based on backup state
     */
    private function getStatusMessage(BackupLog $log, int $hoursAgo): string
    {
        if ($log->status === 'failed') {
            return 'Backup fallido: ' . substr($log->error_message, 0, 100);
        }

        if ($log->status === 'running') {
            return 'Backup en ejecución...';
        }

        if ($hoursAgo < 1) {
            return 'Hace menos de 1 hora';
        }

        if ($hoursAgo < 24) {
            return "Hace {$hoursAgo} horas";
        }

        $daysAgo = floor($hoursAgo / 24);
        return "Hace {$daysAgo} días";
    }
}
