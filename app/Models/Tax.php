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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tax extends Model
{
    use LogsActivity;
    
    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';
    
    protected $fillable = [
        'name',
        'rate',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'rate'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
