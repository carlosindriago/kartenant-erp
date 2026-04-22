<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class TenantPOSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Importante: Este seeder debe ejecutarse en contexto de tenant
        $this->command->info('🔧 Configurando permisos y roles del módulo POS...');
        
        // Verificar que estamos en contexto tenant
        $connection = config('database.default');
        if ($connection !== 'tenant') {
            $this->command->warn("⚠️  Advertencia: No estás en contexto tenant (conexión: {$connection})");
        }
        
        // Resetear caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Crear permisos del módulo POS
        $posPermissions = [
            'pos.access' => 'Acceso al módulo POS',
            'pos.view_all_registers' => 'Ver todas las cajas registradoras',
            'pos.force_close_registers' => 'Forzar cierre de cajas de otros usuarios',
            'pos.view_sales' => 'Ver ventas',
            'pos.create_sales' => 'Crear ventas',
            'pos.view_customers' => 'Ver clientes',
            'pos.manage_customers' => 'Gestionar clientes',
            'pos.process_returns' => 'Procesar devoluciones',
        ];
        
        $this->command->info('📝 Creando permisos...');
        foreach ($posPermissions as $permissionName => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'tenant']
            );
            $this->command->info("  ✓ {$permissionName}");
        }
        
        // Crear roles básicos
        $this->command->info('👥 Creando roles...');
        
        // Rol: Administrador
        $admin = Role::firstOrCreate(
            ['name' => 'Administrador', 'guard_name' => 'tenant']
        );
        $admin->syncPermissions(Permission::where('guard_name', 'tenant')->pluck('name'));
        $this->command->info('  ✓ Administrador (todos los permisos)');
        
        // Rol: Administrador POS
        $adminPOS = Role::firstOrCreate(
            ['name' => 'Administrador POS', 'guard_name' => 'tenant']
        );
        $adminPOS->syncPermissions(array_keys($posPermissions));
        $this->command->info('  ✓ Administrador POS');
        
        // Rol: Cajero
        $cashier = Role::firstOrCreate(
            ['name' => 'Cajero', 'guard_name' => 'tenant']
        );
        $cashier->syncPermissions([
            'pos.access',
            'pos.create_sales',
            'pos.view_customers',
        ]);
        $this->command->info('  ✓ Cajero');
        
        // Rol: Vendedor
        $seller = Role::firstOrCreate(
            ['name' => 'Vendedor', 'guard_name' => 'tenant']
        );
        $seller->syncPermissions([
            'pos.access',
            'pos.create_sales',
            'pos.view_sales',
            'pos.view_customers',
            'pos.manage_customers',
        ]);
        $this->command->info('  ✓ Vendedor');
        
        $this->command->newLine();
        $this->command->info('✅ Permisos y roles del módulo POS creados exitosamente');
        $this->command->info('📊 Total de permisos: ' . count($posPermissions));
        $this->command->info('👥 Total de roles: 4');
    }
}
