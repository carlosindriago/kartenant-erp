<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeManagementPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Creando permisos de gestión de empleados...');

        // Guard para tenant
        $guard = 'web';

        // ===== PERMISOS DE GESTIÓN DE EMPLEADOS =====
        $employeePermissions = [
            // Gestión básica
            ['name' => 'view_employee_events', 'guard_name' => $guard],
            ['name' => 'create_employee_registration', 'guard_name' => $guard],
            ['name' => 'deactivate_employee', 'guard_name' => $guard],
            ['name' => 'reactivate_employee', 'guard_name' => $guard],
            
            // Comprobantes y verificación
            ['name' => 'download_employee_certificates', 'guard_name' => $guard],
            ['name' => 'verify_employee_documents', 'guard_name' => $guard],
            
            // Auditoría y reportes
            ['name' => 'view_employee_history', 'guard_name' => $guard],
            ['name' => 'view_employee_audit_trail', 'guard_name' => $guard],
        ];

        // Crear permisos
        foreach ($employeePermissions as $permData) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $permData['name'],
                    'guard_name' => $permData['guard_name'],
                ]
            );

            $this->command->info("  ✓ {$permData['name']}");
        }

        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->assignPermissionsToRoles($guard);

        $this->command->info('✅ Permisos de gestión de empleados creados exitosamente');
    }

    /**
     * Asigna permisos a roles por defecto
     */
    private function assignPermissionsToRoles(string $guard): void
    {
        $this->command->info('📝 Asignando permisos a roles...');

        // ADMIN: Todos los permisos de empleados
        $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_employee_events',
                    'create_employee_registration',
                    'deactivate_employee',
                    'reactivate_employee',
                    'download_employee_certificates',
                    'verify_employee_documents',
                    'view_employee_history',
                    'view_employee_audit_trail',
                ])->where('guard_name', $guard)->get();
                
                $admin->givePermissionTo($permissions);
                $this->command->info('  ✓ Admin: Todos los permisos de empleados asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Admin: ' . $e->getMessage());
            }
        }

        // GERENTE: Permisos completos de gestión de empleados
        $gerente = Role::where('name', 'gerente')->where('guard_name', $guard)->first();
        if ($gerente) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_employee_events',
                    'create_employee_registration',
                    'deactivate_employee',
                    'reactivate_employee',
                    'download_employee_certificates',
                    'verify_employee_documents',
                    'view_employee_history',
                    'view_employee_audit_trail',
                ])->where('guard_name', $guard)->get();
                
                $gerente->givePermissionTo($permissions);
                $this->command->info('  ✓ Gerente: Permisos de empleados asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Gerente: ' . $e->getMessage());
            }
        }

        // RRHH: Crear rol específico para Recursos Humanos
        try {
            $rrhh = Role::firstOrCreate(
                ['name' => 'rrhh', 'guard_name' => $guard]
            );
            
            $permissions = Permission::whereIn('name', [
                'view_employee_events',
                'create_employee_registration',
                'deactivate_employee',
                'reactivate_employee',
                'download_employee_certificates',
                'verify_employee_documents',
                'view_employee_history',
                'view_employee_audit_trail',
            ])->where('guard_name', $guard)->get();
            
            $rrhh->givePermissionTo($permissions);
            $this->command->info('  ✓ RRHH: Rol y permisos creados');
        } catch (\Exception $e) {
            $this->command->warn('  ⚠ Error creando rol RRHH: ' . $e->getMessage());
        }

        // SUPERVISOR: Solo ver eventos y descargar certificados
        $supervisor = Role::where('name', 'supervisor')->where('guard_name', $guard)->first();
        if ($supervisor) {
            try {
                $permissions = Permission::whereIn('name', [
                    'view_employee_events',
                    'download_employee_certificates',
                    'view_employee_history',
                ])->where('guard_name', $guard)->get();
                
                $supervisor->givePermissionTo($permissions);
                $this->command->info('  ✓ Supervisor: Permisos de visualización asignados');
            } catch (\Exception $e) {
                $this->command->warn('  ⚠ Error asignando permisos a Supervisor: ' . $e->getMessage());
            }
        }
    }
}
