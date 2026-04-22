<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models\Tenant;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Force tenant connection for roles in tenant context
    protected $connection = 'tenant';
}
