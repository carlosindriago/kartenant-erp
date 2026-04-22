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

class ListTenantUsers extends Command
{
    protected $signature = 'tenant:list-users {tenant}';
    
    protected $description = 'Lista todos los usuarios de un tenant';

    public function handle()
    {
        $tenantDomain = $this->argument('tenant');
        
        $tenant = Tenant::where('domain', $tenantDomain)->first();
        
        if (!$tenant) {
            $this->error("❌ Tenant '{$tenantDomain}' no encontrado");
            return 1;
        }
        
        $this->info("📋 Usuarios del tenant: {$tenant->name}");
        $this->newLine();
        
        // Obtener usuarios a través de la relación
        $users = $tenant->users()->get();
        
        if ($users->isEmpty()) {
            $this->warn("⚠️  No hay usuarios asignados a este tenant");
            return 0;
        }
        
        $headers = ['ID', 'Nombre', 'Email', 'Super Admin'];
        $rows = [];
        
        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->is_super_admin ? '✓ Sí' : '✗ No'
            ];
        }
        
        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info("💡 Para asignar permisos POS a un usuario, usa:");
        $this->comment("   ./vendor/bin/sail artisan pos:assign-admin-permissions {$tenantDomain} <email>");
        
        return 0;
    }
}
