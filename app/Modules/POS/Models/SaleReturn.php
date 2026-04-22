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

use App\Models\Concerns\HasCrossDatabaseUserRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Traits\VerifiablePDF;

class SaleReturn extends Model
{
    use HasFactory;
    use VerifiablePDF;
    use HasCrossDatabaseUserRelations;

    /**
     * La tabla sale_returns está en la base de datos del tenant
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'original_sale_id',
        'return_number',
        'status',
        'return_type',
        'reason',
        'subtotal',
        'tax_amount',
        'total',
        'refund_method',
        'processed_by_user_id',
        'processed_at',
        'verification_hash',
        'verification_generated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'processed_at' => 'datetime',
        'verification_generated_at' => 'datetime',
    ];

    /**
     * Venta original que se está devolviendo
     */
    public function originalSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    /**
     * Items devueltos en esta devolución
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    /**
     * DEPRECATED: Use $saleReturn->processedByUser attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('processedByUser') provided by HasCrossDatabaseUserRelations trait
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    /**
     * Generar número de devolución único
     * Formato: NCR-YYYYMMDD-XXXX
     */
    public static function generateReturnNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return sprintf('NCR-%s-%04d', $date, $count);
    }

    /**
     * Scope: Devoluciones completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Devoluciones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Métodos requeridos por VerifiablePDF trait
     */
    public function getVerificationContent(): array
    {
        return [
            'type' => 'sale_return',
            'return_number' => $this->return_number,
            'date' => $this->created_at->toIso8601String(),
            'original_sale_id' => $this->original_sale_id,
            'total' => (string) $this->total,
            'return_type' => $this->return_type,
            'reason' => $this->reason,
            'items_count' => $this->items()->count(),
        ];
    }
    
    public function getVerificationDocumentType(): string
    {
        return 'sale_return_receipt';
    }
    
    protected function getUserIdForVerification(): ?int
    {
        return $this->processed_by_user_id;
    }
    
    protected function getVerificationMetadata(): array
    {
        return [
            'return_number' => $this->return_number,
            'total' => (string) $this->total,
            'return_type' => $this->return_type,
            'reason' => $this->reason,
        ];
    }
}
