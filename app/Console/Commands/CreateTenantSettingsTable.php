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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;

class CreateTenantSettingsTable extends Command
{
    protected $signature = 'tenants:create-settings-table';
    protected $description = 'Create tenant_settings table in all tenant databases';

    public function handle()
    {
        $this->info('Creating tenant_settings table in all tenant databases...');
        
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            
            $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");
            
            if (Schema::connection('tenant')->hasTable('tenant_settings')) {
                $this->warn("  ⚠️  Table already exists, skipping...");
                continue;
            }
            
            try {
                // Create table
                DB::connection('tenant')->statement("
                    CREATE TABLE tenant_settings (
                        id BIGSERIAL PRIMARY KEY,
                        tenant_id BIGINT NOT NULL,
                        allow_cashier_void_last_sale BOOLEAN DEFAULT true,
                        cashier_void_time_limit_minutes INTEGER DEFAULT 5,
                        cashier_void_requires_same_day BOOLEAN DEFAULT true,
                        cashier_void_requires_own_sale BOOLEAN DEFAULT true,
                        created_at TIMESTAMP(0) WITHOUT TIME ZONE,
                        updated_at TIMESTAMP(0) WITHOUT TIME ZONE
                    )
                ");
                
                // Create indexes
                DB::connection('tenant')->statement("CREATE INDEX tenant_settings_tenant_id_index ON tenant_settings(tenant_id)");
                DB::connection('tenant')->statement("CREATE UNIQUE INDEX tenant_settings_tenant_id_unique ON tenant_settings(tenant_id)");
                
                // Add comments
                DB::connection('tenant')->statement("COMMENT ON COLUMN tenant_settings.allow_cashier_void_last_sale IS 'Permite a cajeros anular su última venta'");
                DB::connection('tenant')->statement("COMMENT ON COLUMN tenant_settings.cashier_void_time_limit_minutes IS 'Límite de tiempo en minutos para anular ventas (cajeros)'");
                DB::connection('tenant')->statement("COMMENT ON COLUMN tenant_settings.cashier_void_requires_same_day IS 'Cajeros solo pueden anular ventas del mismo día'");
                DB::connection('tenant')->statement("COMMENT ON COLUMN tenant_settings.cashier_void_requires_own_sale IS 'Cajeros solo pueden anular sus propias ventas'");
                
                // Register migration in migrations table
                DB::connection('tenant')->table('migrations')->insert([
                    'migration' => '2025_10_11_204118_create_tenant_settings_table',
                    'batch' => DB::connection('tenant')->table('migrations')->max('batch') + 1,
                ]);
                
                $this->info("  ✅ Table created successfully!");
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
            }
            
            $tenant->forgetCurrent();
        }
        
        $this->info('');
        $this->info('✅ Process completed!');
        
        return 0;
    }
}
