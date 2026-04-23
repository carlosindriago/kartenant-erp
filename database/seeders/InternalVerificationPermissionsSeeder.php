<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InternalVerificationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creando permisos de verificación interna...');

        // Guard para tenant
        $guard = 'web';

        // ===== PERMISOS GENERALES DE VERIFICACIÓN =====
        $generalPermissions = [
            ['name' => 'view_internal_verifications', 'guard_name' => $guard],
            ['name' => 'verify_any_internal_document', 'guard_name' => $guard],
            ['name' => 'view_verification_audit_trail', 'guard_name' => $guard],
        ];

        // ===== PERMISOS DE CAJA =====
        $cashRegisterPermissions = [
            ['name' => 'verify_cash_register_opening', 'guard_name' => $guard],
            ['name' => 'verify_cash_register_closing', 'guard_name' => $guard],
            ['name' => 'manage_cash_register', 'guard_name' => $guard],
            ['name' => 'view_cash_register_reports', 'guard_name' => $guard],
        ];

        // ===== PERMISOS DE INVENTARIO (para futura expansión) =====
        $inventoryPermissions = [
            ['name' => 'verify_stock_movements', 'guard_name' => $guard],
            ['name' => 'verify_stock_adjustments', 'guard_name' => $guard],
        ];

        // ===== PERMISOS DE AUDIT TRAIL (para futura expansión) =====
        $auditPermissions = [
            ['name' => 'verify_product_creation', 'guard_name' => $guard],
            ['name' => 'verify_customer_creation', 'guard_name' => $guard],
            ['name' => 'verify_employee_creation', 'guard_name' => $guard],
        ];

        // Combinar todos los permisos
        $allPermissions = array_merge(
            $generalPermissions,
            $cashRegisterPermissions,
            $inventoryPermissions,
            $auditPermissions
        );

        // Crear permisos
        foreach ($allPermissions as $permData) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $permData['name'],
                    'guard_name' => $permData['guard_name'],
                ]
            );

            $this->command->info("  ✓ {$permData['name']}");
        }

        // ===== ASIGNAR PERMISOS A ROLES =====
        // Limpiar cache de permisos para que se reconozcan los nuevos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assignPermissionsToRoles($guard);

        $this->command->info('✅ Permisos de verificación interna creados exitosamente');
    }

    /**
     * Asigna permisos a roles por defecto
     */
    private function assignPermissionsToRoles(string $guard): void
    {
        $this->command->info('📝 Asignando permisos a roles...');

        // ADMIN: Todos los permisos
        $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_internal_verifications',
                    'verify_any_internal_document',
                    'view_verification_audit_trail',
                    'verify_cash_register_opening',
                    'verify_cash_register_closing',
                    'manage_cash_register',
                    'view_cash_register_reports',
                    'verify_stock_movements',
                    'verify_stock_adjustments',
                    'verify_product_creation',
                    'verify_customer_creation',
                    'verify_employee_creation',
                ])->where('guard_name', $guard)->get();

                $admin->syncPermissions($permissions);
                $this->command->info('  ✓ Admin: Todos los permisos asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Admin: '.$e->getMessage());
            }
        }

        // GERENTE: Permisos de verificación y reportes
        $gerente = Role::where('name', 'gerente')->where('guard_name', $guard)->first();
        if ($gerente) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_internal_verifications',
                    'view_verification_audit_trail',
                    'verify_cash_register_opening',
                    'verify_cash_register_closing',
                    'view_cash_register_reports',
                    'verify_stock_movements',
                    'verify_stock_adjustments',
                ])->where('guard_name', $guard)->get();

                $gerente->syncPermissions($permissions);
                $this->command->info('  ✓ Gerente: Permisos de verificación asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Gerente: '.$e->getMessage());
            }
        }

        // SUPERVISOR: Permisos de caja y verificaciones
        $supervisor = Role::where('name', 'supervisor')->where('guard_name', $guard)->first();
        if ($supervisor) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_internal_verifications',
                    'verify_cash_register_opening',
                    'verify_cash_register_closing',
                    'manage_cash_register',
                    'view_cash_register_reports',
                ])->where('guard_name', $guard)->get();

                $supervisor->syncPermissions($permissions);
                $this->command->info('  ✓ Supervisor: Permisos de caja asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Supervisor: '.$e->getMessage());
            }
        }

        // CAJERO: Solo gestionar caja
        try {
            $cajero = Role::firstOrCreate(
                ['name' => 'cajero', 'guard_name' => $guard]
            );

            $permissions = Permission::where('name', 'manage_cash_register')
                ->where('guard_name', $guard)->get();

            $cajero->syncPermissions($permissions);
            $this->command->info('  ✓ Cajero: Permisos de caja asignados');
        } catch (\Exception $e) {
            $this->command->warn('  ⚠ Error asignando permisos a Cajero: '.$e->getMessage());
        }
    }
}
