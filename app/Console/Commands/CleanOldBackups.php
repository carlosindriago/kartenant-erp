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

class CleanOldBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old backups (older than 7 days)';

    /**
     * Execute the console command.
     */
    public function handle(TenantBackupService $backupService): int
    {
        $this->info('🧹 Limpiando backups antiguos...');

        $result = $backupService->cleanOldBackups();

        if ($result['deleted_count'] > 0) {
            $this->info("✅ Se eliminaron {$result['deleted_count']} backups antiguos");
        } else {
            $this->info('✨ No hay backups antiguos para eliminar');
        }

        return self::SUCCESS;
    }
}
