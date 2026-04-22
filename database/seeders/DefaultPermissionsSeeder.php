<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * 
     * This seeder creates a comprehensive set of default permissions
     * that cover common business operations. These permissions are
     * designed to be used in tenant databases.
     */
    public function run(): void
    {
        // Get model from config (will use tenant model in tenant context)
        $permissionClass = config('permission.models.permission');
        
        // Define all default permissions organized by module
        $permissions = [
            // Product Management
            'view_products' => 'Ver Productos',
            'create_products' => 'Crear Productos',
            'edit_products' => 'Editar Productos',
            'delete_products' => 'Eliminar Productos',
            'export_products' => 'Exportar Productos',
            
            // Inventory/Stock Management
            'view_stock_movements' => 'Ver Movimientos de Inventario',
            'create_stock_movements' => 'Registrar Movimientos de Inventario',
            'export_stock_movements' => 'Exportar Movimientos de Inventario',
            
            // Category Management
            'view_categories' => 'Ver Categorías',
            'create_categories' => 'Crear Categorías',
            'edit_categories' => 'Editar Categorías',
            'delete_categories' => 'Eliminar Categorías',
            
            // Tax Management
            'view_taxes' => 'Ver Impuestos',
            'create_taxes' => 'Crear Impuestos',
            'edit_taxes' => 'Editar Impuestos',
            'delete_taxes' => 'Eliminar Impuestos',
            
            // Movement Reasons Management
            'view_movement_reasons' => 'Ver Motivos de Movimiento',
            'create_movement_reasons' => 'Crear Motivos de Movimiento',
            'edit_movement_reasons' => 'Editar Motivos de Movimiento',
            'delete_movement_reasons' => 'Eliminar Motivos de Movimiento',
            
            // User/Employee Management
            'view_employees' => 'Ver Empleados',
            'create_employees' => 'Crear Empleados',
            'edit_employees' => 'Editar Empleados',
            'delete_employees' => 'Eliminar Empleados',
            
            // Role Management
            'view_roles' => 'Ver Roles',
            'create_roles' => 'Crear Roles',
            'edit_roles' => 'Editar Roles',
            'delete_roles' => 'Eliminar Roles',
            
            // Activity Log
            'view_activity_log' => 'Ver Registro de Actividad',
            
            // Reports (future)
            'view_reports' => 'Ver Reportes',
            'export_reports' => 'Exportar Reportes',
            
            // Sales (POS module)
            'view_sales' => 'Ver Ventas',
            'create_sales' => 'Crear Ventas',
            'void_sales' => 'Anular Ventas (últimos 5 min)',
            'process_returns' => 'Procesar Devoluciones',
            
            // Customers
            'view_customers' => 'Ver Clientes',
            'create_customers' => 'Crear Clientes',
            'edit_customers' => 'Editar Clientes',
            'delete_customers' => 'Eliminar Clientes',
        ];
        
        // Create all permissions with 'web' guard (tenant guard)
        foreach ($permissions as $name => $description) {
            $permissionClass::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'description' => $description,
                ]
            );
        }
        
        $this->command->info('✅ ' . count($permissions) . ' permisos predeterminados creados exitosamente.');
    }
}
