<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use HasFactory;

    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';

    protected $fillable = [
        'allow_cashier_void_last_sale',
        'cashier_void_time_limit_minutes',
        'cashier_void_requires_same_day',
        'cashier_void_requires_own_sale',
    ];

    protected $casts = [
        'allow_cashier_void_last_sale' => 'boolean',
        'cashier_void_time_limit_minutes' => 'integer',
        'cashier_void_requires_same_day' => 'boolean',
        'cashier_void_requires_own_sale' => 'boolean',
    ];

    /**
     * Get or create settings for current tenant
     * In database-per-tenant, there's only ONE row per tenant database
     */
    public static function getForCurrentTenant(): self
    {
        return self::firstOrCreate(
            ['id' => 1], // Solo un registro por tenant DB
            [
                'allow_cashier_void_last_sale' => true,
                'cashier_void_time_limit_minutes' => 5,
                'cashier_void_requires_same_day' => true,
                'cashier_void_requires_own_sale' => true,
            ]
        );
    }
}
