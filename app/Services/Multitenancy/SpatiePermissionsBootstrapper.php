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

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Permission\PermissionRegistrar;

class SpatiePermissionsBootstrapper implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        // Switch Spatie Permission to use tenant models
        config([
            'permission.models.permission' => Permission::class,
            'permission.models.role' => Role::class,
            'permission.cache.key' => 'spatie.permission.cache.tenant.'.$tenant->id,
        ]);

        // Clear permission cache for tenant context
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
