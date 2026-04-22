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
use Database\Seeders\DefaultPermissionsSeeder;
use Database\Seeders\DefaultRolesSeeder;

class SetupTenantPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:setup-permissions 
                            {--all : Setup permissions for all tenants}
                            {--tenant= : Setup permissions for specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup default permissions and roles for tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando permisos y roles para tenants...');
        $this->newLine();

        if ($this->option('all')) {
            // Setup for all tenants
            $this->info('📦 Configurando para TODOS los tenants...');
            $this->call('tenants:artisan', [
                'artisanCommand' => 'db:seed --class=DefaultRolesSeeder'
            ]);
        } elseif ($tenantId = $this->option('tenant')) {
            // Setup for specific tenant
            $this->info("📦 Configurando para tenant ID: {$tenantId}...");
            $this->call('tenants:artisan', [
                'artisanCommand' => 'db:seed --class=DefaultRolesSeeder',
                '--tenant' => $tenantId
            ]);
        } else {
            // Interactive selection
            $this->warn('⚠️  No se especificó ningún tenant.');
            $this->info('Opciones disponibles:');
            $this->info('  --all         : Configurar todos los tenants');
            $this->info('  --tenant=ID   : Configurar tenant específico');
            $this->newLine();
            
            if ($this->confirm('¿Deseas configurar TODOS los tenants?', true)) {
                $this->call('tenants:artisan', [
                    'artisanCommand' => 'db:seed --class=DefaultRolesSeeder'
                ]);
            } else {
                $this->error('❌ Operación cancelada.');
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('✅ ¡Permisos y roles configurados exitosamente!');
        $this->newLine();
        $this->comment('📋 Roles creados:');
        $this->comment('   • Administrador (acceso completo)');
        $this->comment('   • Gerente (gestión completa)');
        $this->comment('   • Cajero (operaciones de venta)');
        $this->comment('   • Almacenero (gestión de inventario)');
        $this->comment('   • Supervisor (supervisión y reportes)');
        $this->newLine();
        $this->comment('🔑 30+ permisos creados para gestión granular');

        return Command::SUCCESS;
    }
}
