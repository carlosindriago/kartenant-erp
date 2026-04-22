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

use Illuminate\Console\Command;
use App\Modules\POS\Models\CashRegister;
use App\Models\Tenant;

class CloseAllOpenCashRegisters extends Command
{
    protected $signature = 'cash-register:close-all {tenant?}';
    
    protected $description = 'Cierra todas las cajas abiertas (para testing)';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant') ?? 'tornillostore';
        
        $tenant = Tenant::where('domain', $tenantDomain)->first();
        
        if (!$tenant) {
            $this->error("Tenant '{$tenantDomain}' no encontrado");
            return 1;
        }
        
        // Configurar conexión del tenant
        config(['database.connections.tenant.database' => $tenant->database_name]);
        \DB::purge('tenant');
        \DB::reconnect('tenant');
        
        $openRegisters = CashRegister::on('tenant')->where('status', 'open')->get();
        
        $this->info("Encontradas {$openRegisters->count()} cajas abiertas en {$tenant->name}");
        
        foreach ($openRegisters as $register) {
            $this->line("Cerrando {$register->register_number} - Usuario: {$register->opened_by_user_id}");
            
            $register->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_user_id' => $register->opened_by_user_id,
                'expected_amount' => 0,
                'actual_amount' => 0,
                'difference' => 0,
                'closing_notes' => 'Cerrado administrativamente para testing',
            ]);
        }
        
        $this->info('✅ Todas las cajas han sido cerradas');
        
        return 0;
    }
}
