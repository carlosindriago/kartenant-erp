<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventoryPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        // Usar guard 'web' (el guard por defecto en tenant según arquitectura)
        $guard = 'web';

        // Verificar que estamos usando la conexión tenant
        $connection = config('database.default');
        if ($connection !== 'tenant') {
            $this->command->warn("⚠️  Conexión actual: {$connection}. Se esperaba 'tenant'.");
        }

        // Forzar conexión tenant en Spatie
        config([
            'permission.database_connection' => 'tenant',
        ]);

        // Crear permisos de inventario con verificación
        $permissions = [
            // Movimientos de Stock
            'inventory.view_movements' => 'Ver movimientos de inventario',
            'inventory.register_entry' => 'Registrar entradas de mercadería',
            'inventory.register_exit' => 'Registrar salidas de mercadería',
            'inventory.authorize_exit' => 'Autorizar salidas importantes',
            'inventory.download_certificates' => 'Descargar comprobantes verificables',
            'inventory.verify_movements' => 'Verificar autenticidad de movimientos',

            // Productos
            'inventory.view_products' => 'Ver productos',
            'inventory.create_products' => 'Crear productos',
            'inventory.edit_products' => 'Editar productos',
            'inventory.delete_products' => 'Eliminar productos',

            // Reportes
            'inventory.view_reports' => 'Ver reportes de inventario',
            'inventory.export_reports' => 'Exportar reportes',
        ];

        $this->command->info('📝 Creando permisos de inventario...');
        foreach ($permissions as $name => $description) {
            $permission = new Permission;
            $permission->setConnection('tenant');
            $permission = Permission::on('tenant')->firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['description' => $description]
            );
            $this->command->info("  ✓ {$name}");
        }

        // NO limpiar caché aún, necesitamos los permisos cargados
        $this->command->info('👥 Asignando permisos a roles...');

        // Asignar permisos a roles
        $this->assignPermissionsToRoles($guard);

        // Limpiar caché de permisos AL FINAL
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('🔄 Caché de permisos limpiado');
    }

    /**
     * Asignar permisos a roles según nivel de acceso
     */
    private function assignPermissionsToRoles(string $guard): void
    {
        // Recargar permisos desde la base de datos tenant
        $allPermissions = Permission::on('tenant')
            ->where('guard_name', $guard)
            ->whereIn('name', [
                'inventory.view_movements',
                'inventory.register_entry',
                'inventory.register_exit',
                'inventory.authorize_exit',
                'inventory.download_certificates',
                'inventory.verify_movements',
                'inventory.view_products',
                'inventory.create_products',
                'inventory.edit_products',
                'inventory.delete_products',
                'inventory.view_reports',
                'inventory.export_reports',
            ])
            ->get();

        if ($allPermissions->count() === 0) {
            $this->command->error('⚠️  No se encontraron permisos para asignar');

            return;
        }

        $this->command->info("✓ Se encontraron {$allPermissions->count()} permisos");

        // Admin: Acceso total
        $adminRole = Role::on('tenant')->where('name', 'Admin')->where('guard_name', $guard)->first();
        if ($adminRole) {
            // Usar las instancias de permisos directamente, no los nombres
            $adminRole->syncPermissions($allPermissions);
            $this->command->info('  ✓ Admin');
        }

        // Gerente: Acceso completo excepto eliminar productos
        $gerenteRole = Role::on('tenant')->where('name', 'Gerente')->where('guard_name', $guard)->first();
        if ($gerenteRole) {
            $gerentePerms = $allPermissions->whereNotIn('name', ['inventory.delete_products']);
            $gerenteRole->syncPermissions($gerentePerms);
            $this->command->info('  ✓ Gerente');
        }

        // Supervisor: Gestión de movimientos y autorización
        $supervisorRole = Role::on('tenant')->where('name', 'Supervisor')->where('guard_name', $guard)->first();
        if ($supervisorRole) {
            $supervisorPerms = $allPermissions->whereIn('name', [
                'inventory.view_movements',
                'inventory.register_entry',
                'inventory.register_exit',
                'inventory.authorize_exit',
                'inventory.download_certificates',
                'inventory.view_products',
                'inventory.edit_products',
                'inventory.view_reports',
            ]);
            $supervisorRole->syncPermissions($supervisorPerms);
            $this->command->info('  ✓ Supervisor');
        }

        // Almacenero (nuevo rol específico)
        $almaceneroRole = Role::on('tenant')->firstOrCreate(
            ['name' => 'Almacenero', 'guard_name' => $guard],
            ['description' => 'Encargado de almacén e inventario']
        );
        $almaceneroPerms = $allPermissions->whereIn('name', [
            'inventory.view_movements',
            'inventory.register_entry',
            'inventory.register_exit',
            'inventory.download_certificates',
            'inventory.view_products',
            'inventory.view_reports',
        ]);
        $almaceneroRole->syncPermissions($almaceneroPerms);
        $this->command->info('  ✓ Almacenero (nuevo rol creado)');

        // Cajero: Solo visualización y salidas por ventas
        $cajeroRole = Role::on('tenant')->where('name', 'Cajero')->where('guard_name', $guard)->first();
        if ($cajeroRole) {
            $cajeroPerms = $allPermissions->whereIn('name', [
                'inventory.view_movements',
                'inventory.view_products',
            ]);
            $cajeroRole->syncPermissions($cajeroPerms);
            $this->command->info('  ✓ Cajero');
        }

        // Vendedor: Solo visualización
        $vendedorRole = Role::on('tenant')->where('name', 'Vendedor')->where('guard_name', $guard)->first();
        if ($vendedorRole) {
            $vendedorPerms = $allPermissions->whereIn('name', [
                'inventory.view_movements',
                'inventory.view_products',
            ]);
            $vendedorRole->syncPermissions($vendedorPerms);
            $this->command->info('  ✓ Vendedor');
        }

        $this->command->info('✅ Permisos de inventario asignados a roles existentes');
        $this->command->info('✅ Rol "Almacenero" creado con permisos de gestión de inventario');
    }
}
