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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddForceClosePermission extends Command
{
    protected $signature = 'pos:add-force-close-permission {tenant?}';
    
    protected $description = 'Agrega el permiso de forzar cierre de cajas a los administradores';

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
        
        $this->info("Agregando permiso para tenant: {$tenant->name}");
        
        // Crear el permiso si no existe
        $permission = Permission::firstOrCreate(
            [
                'name' => 'pos.force_close_registers',
                'guard_name' => 'tenant'
            ],
            [
                'description' => 'Forzar cierre de cajas de otros usuarios'
            ]
        );
        
        $this->info("✅ Permiso creado: {$permission->name}");
        
        // Asignar a roles de administrador
        $adminRoles = Role::where('guard_name', 'tenant')
            ->where(function($query) {
                $query->where('name', 'LIKE', '%admin%')
                      ->orWhere('name', 'LIKE', '%supervisor%')
                      ->orWhere('name', 'LIKE', '%gerente%');
            })
            ->get();
        
        if ($adminRoles->isEmpty()) {
            $this->warn('⚠️  No se encontraron roles de administrador. Debes asignar el permiso manualmente.');
        } else {
            foreach ($adminRoles as $role) {
                $role->givePermissionTo($permission);
                $this->info("✅ Permiso asignado al rol: {$role->name}");
            }
        }
        
        $this->newLine();
        $this->info('🎉 Proceso completado exitosamente');
        $this->info('Los administradores ahora pueden forzar el cierre de cajas de otros usuarios.');
        
        return 0;
    }
}
