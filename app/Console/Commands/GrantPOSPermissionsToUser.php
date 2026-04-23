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

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class GrantPOSPermissionsToUser extends Command
{
    protected $signature = 'user:grant-pos-permissions {tenant} {email}';

    protected $description = 'Otorga permisos POS a un usuario específico';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');
        $userEmail = $this->argument('email');

        $tenant = Tenant::where('domain', $tenantDomain)->first();

        if (! $tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");

            return 1;
        }

        // Hacer que el tenant sea el actual
        $tenant->makeCurrent();

        // Cambiar conexión default temporalmente
        $originalDefault = config('database.default');
        config(['database.default' => 'tenant']);

        try {
            // Resetear caché de permisos
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            // Buscar usuario
            $user = User::where('email', $userEmail)->first();

            if (! $user) {
                $this->error("❌ Usuario con email '{$userEmail}' no encontrado");

                return 1;
            }

            $this->info("🔧 Otorgando permisos POS a: {$user->name}");
            $this->info("📍 Tenant: {$tenant->name}");
            $this->newLine();

            // Permisos del módulo POS
            $posPermissions = [
                'pos.access',
                'pos.view_all_registers',
                'pos.force_close_registers',
                'pos.view_sales',
                'pos.create_sales',
                'pos.view_customers',
                'pos.manage_customers',
                'pos.process_returns',
            ];

            $assigned = 0;

            foreach ($posPermissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)
                    ->where('guard_name', 'tenant')
                    ->first();

                if (! $permission) {
                    $this->warn("  ⚠️  Permiso '{$permissionName}' no existe, creándolo...");
                    $permission = Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'tenant',
                    ]);
                }

                // Asignar permiso directamente usando el guard 'tenant'
                // No verificamos si ya lo tiene, solo lo asignamos
                try {
                    // Usamos el modelo Permission directamente para evitar problemas de guard
                    $exists = \DB::connection('tenant')
                        ->table('model_has_permissions')
                        ->where('permission_id', $permission->id)
                        ->where('model_type', 'App\Models\User')
                        ->where('model_id', $user->id)
                        ->exists();

                    if (! $exists) {
                        \DB::connection('tenant')
                            ->table('model_has_permissions')
                            ->insert([
                                'permission_id' => $permission->id,
                                'model_type' => 'App\Models\User',
                                'model_id' => $user->id,
                            ]);
                        $this->info("  ✓ {$permissionName}");
                        $assigned++;
                    } else {
                        $this->comment("  • {$permissionName} (ya lo tenía)");
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Error: {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info('📊 RESUMEN:');
            $this->info("   Permisos asignados: {$assigned}");
            $this->info('   Permisos totales: '.count($posPermissions));

            $this->newLine();
            $this->info('🎉 ¡Proceso completado!');
            $this->warn('⚠️  IMPORTANTE: El usuario debe cerrar sesión y volver a iniciar para que los cambios surtan efecto.');

        } finally {
            // Restaurar configuración
            config(['database.default' => $originalDefault]);
            $tenant->forgetCurrent();
        }

        return 0;
    }
}
