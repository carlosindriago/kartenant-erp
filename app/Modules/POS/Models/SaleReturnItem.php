<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Inventory\Models\Product;

class SaleReturnItem extends Model
{
    use HasFactory;

    /**
     * La tabla sale_return_items está en la base de datos del tenant
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'sale_return_id',
        'original_sale_item_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_total',
        'return_reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Devolución a la que pertenece este item
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /**
     * Item original de la venta
     */
    public function originalSaleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'original_sale_item_id');
    }

    /**
     * Producto devuelto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcular el total de la línea
     */
    public function calculateLineTotal(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }
}
