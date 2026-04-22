<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services\Multitenancy;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SpatiePermissionsBootstrapper implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        // Switch Spatie Permission to use tenant models
        config([
            'permission.models.permission' => \App\Models\Tenant\Permission::class,
            'permission.models.role' => \App\Models\Tenant\Role::class,
            'permission.cache.key' => 'spatie.permission.cache.tenant.' . $tenant->id,
        ]);

        // Clear permission cache for tenant context
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function forgetCurrent(): void
    {
        // Switch back to landlord models
        config([
            'permission.models.permission' => \App\Models\Landlord\Permission::class,
            'permission.models.role' => \App\Models\Landlord\Role::class,
            'permission.cache.key' => 'spatie.permission.cache.landlord',
        ]);

        // Clear permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
