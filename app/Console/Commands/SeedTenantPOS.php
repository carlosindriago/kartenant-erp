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
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SeedTenantPOS extends Command
{
    protected $signature = 'tenant:seed-pos {tenant}';
    
    protected $description = 'Ejecuta el seeder POS en el contexto de un tenant específico';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');
        
        $tenant = Tenant::where('domain', $tenantDomain)->first();
        
        if (!$tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");
            return 1;
        }
        
        $this->info("🔧 Ejecutando seeder POS para tenant: {$tenant->name}");
        $this->newLine();
        
        // Hacer que el tenant sea el actual
        $tenant->makeCurrent();
        
        // Verificar que la conexión es correcta
        $currentDb = DB::connection('tenant')->getDatabaseName();
        $this->info("📊 Base de datos: {$currentDb}");
        
        // Configurar el default a tenant temporalmente
        $originalDefault = config('database.default');
        config(['database.default' => 'tenant']);
        
        try {
            // Resetear caché de permisos para este tenant
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            // Ejecutar el seeder
            $this->call('db:seed', [
                '--class' => 'TenantPOSSeeder'
            ]);
            
            $this->newLine();
            $this->info('✅ Seeder ejecutado exitosamente');
            
            // Ahora asignar los permisos a los usuarios del tenant
            $users = $tenant->users()->get();
            
            if ($users->isEmpty()) {
                $this->warn('⚠️  No hay usuarios en este tenant para asignar permisos');
            } else {
                $this->info('👤 Asignando rol de Administrador a usuarios...');
                
                foreach ($users as $user) {
                    $user->assignRole('Administrador');
                    $this->info("  ✓ {$user->name} - {$user->email}");
                }
            }
            
        } finally {
            // Restaurar la conexión default
            config(['database.default' => $originalDefault]);
            $tenant->forgetCurrent();
        }
        
        $this->newLine();
        $this->info('🎉 Proceso completado. Los usuarios ahora tienen acceso al módulo POS.');
        
        return 0;
    }
}
