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

class CheckUserPermissions extends Command
{
    protected $signature = 'user:check-permissions {tenant} {email}';
    
    protected $description = 'Verifica los permisos y roles de un usuario';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');
        $userEmail = $this->argument('email');
        
        $tenant = Tenant::where('domain', $tenantDomain)->first();
        
        if (!$tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");
            return 1;
        }
        
        // Hacer que el tenant sea el actual
        $tenant->makeCurrent();
        
        // Buscar usuario
        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            $this->error("❌ Usuario con email '{$userEmail}' no encontrado");
            return 1;
        }
        
        $this->info("👤 Usuario: {$user->name} ({$user->email})");
        $this->newLine();
        
        // Mostrar roles
        $roles = $user->roles()->where('guard_name', 'tenant')->get();
        
        if ($roles->isEmpty()) {
            $this->warn('⚠️  No tiene roles asignados');
        } else {
            $this->info('👥 Roles:');
            foreach ($roles as $role) {
                $this->info("  - {$role->name}");
            }
        }
        
        $this->newLine();
        
        // Mostrar permisos
        $permissions = $user->getAllPermissions();
        
        if ($permissions->isEmpty()) {
            $this->warn('⚠️  No tiene permisos asignados');
        } else {
            $this->info('🔐 Permisos:');
            foreach ($permissions as $permission) {
                $this->info("  - {$permission->name}");
            }
        }
        
        $this->newLine();
        
        // Verificar permisos específicos del POS
        $posPermissions = [
            'pos.access',
            'pos.view_all_registers',
            'pos.force_close_registers',
        ];
        
        $this->info('✅ Verificación de permisos POS:');
        foreach ($posPermissions as $perm) {
            $has = $user->can($perm, 'tenant');
            $icon = $has ? '✓' : '✗';
            $color = $has ? 'info' : 'error';
            $this->line("  {$icon} {$perm}");
        }
        
        $tenant->forgetCurrent();
        
        return 0;
    }
}
