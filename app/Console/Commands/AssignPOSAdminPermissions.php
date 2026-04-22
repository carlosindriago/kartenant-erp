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
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPOSAdminPermissions extends Command
{
    protected $signature = 'pos:assign-admin-permissions {tenant} {email}';
    
    protected $description = 'Asigna todos los permisos del módulo POS a un usuario administrador';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');
        $userEmail = $this->argument('email');
        
        $tenant = Tenant::where('domain', $tenantDomain)->first();
        
        if (!$tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");
            return 1;
        }
        
        // Configurar conexión del tenant
        config(['database.connections.tenant.database' => $tenant->database_name]);
        \DB::purge('tenant');
        \DB::reconnect('tenant');
        
        // Buscar usuario en la tabla de usuarios del landlord
        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            $this->error("❌ Usuario con email '{$userEmail}' no encontrado");
            return 1;
        }
        
        $this->info("🔧 Configurando permisos POS para: {$user->name}");
        $this->info("📍 Tenant: {$tenant->name}");
        $this->newLine();
        
        // Definir permisos del módulo POS
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
        
        $createdPermissions = 0;
        $assignedPermissions = 0;
        
        foreach ($posPermissions as $permissionName => $description) {
            // Crear permiso si no existe
            $permission = Permission::firstOrCreate(
                [
                    'name' => $permissionName,
                    'guard_name' => 'tenant'
                ],
                [
                    'description' => $description
                ]
            );
            
            if ($permission->wasRecentlyCreated) {
                $this->info("✅ Permiso creado: {$permissionName}");
                $createdPermissions++;
            }
            
            // Asignar permiso al usuario
            if (!$user->hasPermissionTo($permissionName, 'tenant')) {
                $user->givePermissionTo($permission);
                $this->info("  ↳ Asignado a {$user->name}");
                $assignedPermissions++;
            } else {
                $this->comment("  ↳ Ya tenía: {$permissionName}");
            }
        }
        
        $this->newLine();
        $this->info("📊 RESUMEN:");
        $this->info("   Permisos creados: {$createdPermissions}");
        $this->info("   Permisos asignados: {$assignedPermissions}");
        $this->newLine();
        
        // Buscar o crear rol de administrador POS
        $adminRole = Role::firstOrCreate(
            [
                'name' => 'Administrador POS',
                'guard_name' => 'tenant'
            ],
            [
                'description' => 'Administrador con acceso completo al módulo POS'
            ]
        );
        
        if ($adminRole->wasRecentlyCreated) {
            $this->info("✅ Rol creado: Administrador POS");
        }
        
        // Asignar todos los permisos al rol
        $adminRole->syncPermissions(array_keys($posPermissions));
        
        // Asignar rol al usuario si no lo tiene
        if (!$user->hasRole($adminRole, 'tenant')) {
            $user->assignRole($adminRole);
            $this->info("✅ Rol 'Administrador POS' asignado a {$user->name}");
        }
        
        $this->newLine();
        $this->info("🎉 ¡Proceso completado exitosamente!");
        $this->info("   Usuario {$user->name} ahora tiene acceso completo al módulo POS");
        $this->info("   Incluye la capacidad de forzar cierre de cajas de otros usuarios");
        
        return 0;
    }
}
