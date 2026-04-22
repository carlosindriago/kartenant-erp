<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultRolesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * 
     * This seeder creates default roles with appropriate permissions
     * for common business scenarios. Designed for tenant databases.
     */
    public function run(): void
    {
        // Ensure permissions exist first
        $this->call(DefaultPermissionsSeeder::class);
        
        // Get models from config (will use tenant models in tenant context)
        $roleClass = config('permission.models.role');
        $permissionClass = config('permission.models.permission');
        
        // Define default roles with their permissions
        $roles = [
            'Administrador' => [
                'description' => 'Acceso completo al sistema',
                'permissions' => 'all', // Special case: assign all permissions
            ],
            'Gerente' => [
                'description' => 'Gestión completa excepto configuración de roles',
                'permissions' => [
                    'view_products', 'create_products', 'edit_products', 'delete_products', 'export_products',
                    'view_stock_movements', 'create_stock_movements', 'export_stock_movements',
                    'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
                    'view_taxes', 'create_taxes', 'edit_taxes', 'delete_taxes',
                    'view_movement_reasons', 'create_movement_reasons', 'edit_movement_reasons', 'delete_movement_reasons',
                    'view_employees',
                    'view_activity_log',
                    'view_reports', 'export_reports',
                    'view_sales', 'create_sales', 'void_sales', 'process_returns',
                    'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
                ],
            ],
            'Cajero' => [
                'description' => 'Operaciones de venta y atención al cliente',
                'permissions' => [
                    'view_products',
                    'view_stock_movements',
                    'view_categories',
                    'view_sales', 'create_sales',
                    'view_customers', 'create_customers', 'edit_customers',
                ],
            ],
            'Almacenero' => [
                'description' => 'Gestión de inventario y productos',
                'permissions' => [
                    'view_products', 'create_products', 'edit_products',
                    'view_stock_movements', 'create_stock_movements', 'export_stock_movements',
                    'view_categories', 'create_categories', 'edit_categories',
                    'view_movement_reasons',
                ],
            ],
            'Supervisor' => [
                'description' => 'Supervisión de operaciones y reportes',
                'permissions' => [
                    'view_products', 'edit_products', 'export_products',
                    'view_stock_movements', 'create_stock_movements', 'export_stock_movements',
                    'view_categories',
                    'view_taxes',
                    'view_movement_reasons',
                    'view_employees',
                    'view_activity_log',
                    'view_reports', 'export_reports',
                    'view_sales', 'void_sales', 'process_returns',
                    'view_customers', 'create_customers', 'edit_customers',
                ],
            ],
        ];
        
        // Create roles and assign permissions
        foreach ($roles as $roleName => $roleData) {
            // Create or get the role
            $role = $roleClass::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'description' => $roleData['description'],
                ]
            );
            
            // Assign permissions
            if ($roleData['permissions'] === 'all') {
                // Assign all available permissions
                $allPermissions = $permissionClass::where('guard_name', 'web')->pluck('name')->toArray();
                $role->syncPermissions($allPermissions);
            } else {
                // Assign specific permissions
                $role->syncPermissions($roleData['permissions']);
            }
            
            $this->command->info("✅ Rol '{$roleName}' creado con " . $role->permissions->count() . " permisos.");
        }
        
        $this->command->info('🎉 ' . count($roles) . ' roles predeterminados creados exitosamente.');
        
        // Seed predefined movement reasons
        $this->call(MovementReasonsSeeder::class);
    }
}
