<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Permissions;

use App\Models\Landlord\Permission as LandlordPermission;
use App\Models\Landlord\Role as LandlordRole;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\Permission\PermissionRegistrar as BaseRegistrar;

/**
 * Landlord-safe PermissionRegistrar that avoids hitting tenant tables
 * when there is no current tenant. It returns an empty permission set.
 */
class LandlordPermissionRegistrar extends BaseRegistrar
{
    public function __construct(CacheManager $cacheManager, Dispatcher $dispatcher)
    {
        // Initialize parent
        parent::__construct($cacheManager, $dispatcher);
        // Force landlord models for admin (superadmin guard)
        $this->permissionClass = LandlordPermission::class;
        $this->roleClass = LandlordRole::class;
    }

    /**
     * Use default behavior to actually load landlord permissions.
     */
    public function getPermissions(array $params = [], bool $onlyOne = false): EloquentCollection
    {
        return parent::getPermissions($params, $onlyOne);
    }

    /**
     * Register permission check method on Gate for admin context.
     */
    public function registerPermissions(Gate $gate): bool
    {
        return parent::registerPermissions($gate);
    }
}
