<?php

namespace Database\Seeders;

use App\Models\Landlord\Permission;
use App\Models\Landlord\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class LandlordAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Force Spatie Permission to use landlord models during this seeding run
        config([
            'permission.models.permission' => Permission::class,
            'permission.models.role' => Role::class,
            // Use a distinct cache key for landlord context, if configured elsewhere
            'permission.cache.key' => 'spatie.permission.cache.landlord',
        ]);

        $permissions = [
            'admin.access',
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.users.delete',
            'admin.tenants.view',
            'admin.tenants.create',
            'admin.tenants.update',
            'admin.tenants.delete',
            'admin.tenants.restore',
            'admin.tenants.force-delete',
            'admin.archived_tenants.view',
            'admin.archived_tenants.restore',
            'admin.archived_tenants.export',
            'admin.archived_tenants.backup',
            'admin.audit.view',
            'admin.stats.view',
            'admin.security_questions.view',
            'admin.security_questions.create',
            'admin.security_questions.update',
            'admin.security_questions.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'superadmin',
            ]);
        }

        $roles = [
            'admin_manager' => [
                'admin.access',
                'admin.tenants.view',
                'admin.tenants.create',
                'admin.tenants.update',
                'admin.tenants.restore',
                'admin.archived_tenants.view',
                'admin.archived_tenants.restore',
                'admin.archived_tenants.export',
                'admin.archived_tenants.backup',
                'admin.users.view',
                'admin.security_questions.view',
                'admin.security_questions.create',
                'admin.security_questions.update',
                'admin.security_questions.delete',
            ],
            'admin_analyst' => [
                'admin.access',
                'admin.tenants.view',
                'admin.archived_tenants.view',
                'admin.stats.view',
            ],
            'security_auditor' => [
                'admin.access',
                'admin.tenants.view',
                'admin.archived_tenants.view',
                'admin.audit.view',
                'admin.security_questions.view',
            ],
            'admin_user_manager' => [
                'admin.access',
                'admin.users.view',
                'admin.users.create',
                'admin.users.update',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'superadmin',
            ]);
            $permissionModels = Permission::whereIn('name', $perms)->get();
            $role->syncPermissions($permissionModels);
        }

        // Clear Spatie permission cache to ensure new permissions & roles load correctly
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
