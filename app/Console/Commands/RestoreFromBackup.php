<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\TenantRestoreService;
use Illuminate\Console\Command;

class RestoreFromBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {backup_log_id} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a tenant database from a backup (EMERGENCY USE ONLY)';

    /**
     * Execute the console command.
     */
    public function handle(TenantRestoreService $restoreService): int
    {
        $backupLogId = $this->argument('backup_log_id');
        $force = $this->option('force');

        // Find backup log
        $backupLog = BackupLog::find($backupLogId);

        if (! $backupLog) {
            $this->error("❌ Backup log #{$backupLogId} not found");

            return self::FAILURE;
        }

        // Show backup information
        $this->newLine();
        $this->info('🔍 Backup Information:');
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $backupLog->id],
                ['Database', $backupLog->database_name],
                ['Tenant', $backupLog->tenant?->name ?? 'Landlord'],
                ['Status', $backupLog->status],
                ['Created', $backupLog->created_at->format('Y-m-d H:i:s')],
                ['Size', $backupLog->formatted_file_size],
                ['Type', $backupLog->backup_type],
            ]
        );

        if ($backupLog->status !== 'success') {
            $this->error('❌ Cannot restore from a failed backup');

            return self::FAILURE;
        }

        // Preview backup contents
        $this->info('📊 Analyzing backup contents...');
        $preview = $restoreService->previewBackup($backupLog);

        if (! $preview['success']) {
            $this->error("❌ Error previewing backup: {$preview['error']}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('📋 Backup Contents:');

        $tableData = [];
        foreach ($preview['statistics'] as $table => $count) {
            $tableData[] = [$table, $count];
        }

        $this->table(['Table', 'Records'], $tableData);

        // Confirmation
        if (! $force) {
            $this->newLine();
            $this->warn('⚠️  WARNING: This will REPLACE the current database!');
            $this->warn('⚠️  All data since '.$backupLog->created_at->format('Y-m-d H:i:s').' will be LOST!');
            $this->warn('⚠️  A safety backup will be created automatically.');
            $this->newLine();

            $databaseName = $backupLog->database_name;
            $confirmation = $this->ask("Type the database name '{$databaseName}' to confirm");

            if ($confirmation !== $databaseName) {
                $this->error('❌ Confirmation failed. Aborting.');

                return self::FAILURE;
            }

            $finalConfirm = $this->confirm('Are you ABSOLUTELY SURE you want to continue?');

            if (! $finalConfirm) {
                $this->info('✅ Restore cancelled');

                return self::SUCCESS;
            }
        }

        // Execute restoration
        $this->newLine();
        $this->info('🔄 Starting database restoration...');
        $this->info('   This may take several minutes. Please wait...');
        $this->newLine();

        $startTime = now();

        $result = $restoreService->restoreFromBackup($backupLog);

        $duration = $startTime->diffInSeconds(now());

        $this->newLine();

        if ($result['success']) {
            $this->info("✅ Database restored successfully in {$duration}s");
            $this->newLine();

            $this->info('📊 Restored Statistics:');
            $restoredData = [];
            foreach ($result['statistics'] as $table => $count) {
                $restoredData[] = [$table, $count];
            }
            $this->table(['Table', 'Records'], $restoredData);

            if (isset($result['safety_backup_id'])) {
                $this->newLine();
                $this->info("🛡️  Safety backup created: ID #{$result['safety_backup_id']}");
                $this->info('   (You can use this to rollback if needed)');
            }

            return self::SUCCESS;

        } else {
            $this->error("❌ Restoration failed: {$result['error']}");

            if (isset($result['rollback'])) {
                if ($result['rollback'] === 'success') {
                    $this->warn('🔙 Automatic rollback successful - original database restored');
                } else {
                    $this->error('🚨 CRITICAL: Rollback also failed!');
                    $this->error('🚨 Database may be in an inconsistent state');
                    $this->error('🚨 Contact support IMMEDIATELY');
                }
            }

            return self::FAILURE;
        }
    }
}
