<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Use Spatie Multitenancy's current() method
            if ($tenant = Tenant::current()) {
                $builder->where(self::getTenantForeignKeyName(), $tenant->getKey());
            }
        });

        static::creating(function (Model $model) {
            // Use Spatie Multitenancy's current() method
            if ($tenant = Tenant::current()) {
                $model->{self::getTenantForeignKeyName()} = $tenant->getKey();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, self::getTenantForeignKeyName());
    }

    public static function getTenantForeignKeyName(): string
    {
        return 'tenant_id';
    }
}