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

use App\Services\TenantBackupService;
use Illuminate\Console\Command;

class BackupTenantDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:tenants {--type=daily : Backup type (daily or manual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup all tenant databases (landlord + all tenants)';

    /**
     * Execute the console command.
     */
    public function handle(TenantBackupService $backupService): int
    {
        $type = $this->option('type');

        $this->info('🔒 Iniciando backups de todas las bases de datos...');
        $this->newLine();

        $startTime = now();

        // Execute backups
        $results = $backupService->backupAllTenants($type);

        $this->newLine();
        $this->info('📊 Resumen de backups:');
        $this->newLine();

        $successCount = 0;
        $failedCount = 0;

        foreach ($results as $database => $result) {
            if ($result['success']) {
                $successCount++;
                $this->line("  ✅ {$database}: ".($result['file_size'] / 1024 / 1024).' MB');
            } else {
                $failedCount++;
                $this->error("  ❌ {$database}: {$result['error']}");
            }
        }

        $this->newLine();
        $duration = $startTime->diffInSeconds(now());

        if ($failedCount === 0) {
            $this->info("✨ Todos los backups completados exitosamente en {$duration}s");
            $this->info("   Total: {$successCount} backups");
        } else {
            $this->warn("⚠️  Backups completados con errores en {$duration}s");
            $this->warn("   Exitosos: {$successCount} | Fallidos: {$failedCount}");
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
