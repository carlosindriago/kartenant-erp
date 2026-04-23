<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;
    use LogsActivity;

    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'unit_of_measure',
        'category_id',
        'description',
        'cost_price',
        'price',
        'tax_id',
        'status',
        'stock',
        'min_stock',
        'image',
    ];

    protected $casts = [
        'status' => 'boolean',
        'cost_price' => 'decimal:2',
        'price' => 'decimal:2', // ✅ PRECIO BASE (sin impuestos)
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tax::class);
    }

    /**
     * Relación con items de venta
     * Un producto puede estar en múltiples items de venta
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(\App\Modules\POS\Models\SaleItem::class);
    }

    /**
     * Calcula el monto del impuesto para este producto
     * Basado en el precio base * tasa de impuesto
     */
    public function getTaxAmountAttribute(): float
    {
        if (! $this->tax || $this->tax->rate <= 0) {
            return 0;
        }

        return round($this->price * ($this->tax->rate / 100), 2);
    }

    /**
     * Calcula el precio final (precio base + impuestos)
     * Este es el precio que paga el cliente
     */
    public function getFinalPriceAttribute(): float
    {
        return round($this->price + $this->tax_amount, 2);
    }

    /**
     * Devuelve información formateada del impuesto
     */
    public function getTaxInfoAttribute(): ?string
    {
        if (! $this->tax) {
            return null;
        }

        return "{$this->tax->name} ({$this->tax->rate}%)";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'barcode', 'price', 'cost_price', 'stock', 'min_stock', 'status', 'category_id', 'tax_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
