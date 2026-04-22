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
use App\Models\User;
use App\Traits\VerifiablePDF;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sale extends Model
{
    use HasFactory;
    use LogsActivity;
    use VerifiablePDF;
    use HasCrossDatabaseUserRelations;

    // Use tenant connection in database-per-tenant architecture
    protected $connection = 'tenant';

    protected $fillable = [
        'cash_register_id',
        'invoice_number',
        'customer_id',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'payment_method',
        'transaction_reference',
        'amount_paid',
        'change_amount',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'verification_hash',
        'verification_generated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'verification_generated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * DEPRECATED: Use $sale->user attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('user') provided by HasCrossDatabaseUserRelations trait
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * DEPRECATED: Use $sale->cancelledBy attribute instead
     * Relación directa no funciona porque User vive en landlord DB
     *
     * @deprecated Use getAttribute('cancelledBy') provided by HasCrossDatabaseUserRelations trait
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Devoluciones asociadas a esta venta
     */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'original_sale_id');
    }

    /**
     * Verificar si tiene devoluciones
     */
    public function hasReturns(): bool
    {
        return $this->returns()->exists();
    }

    /**
     * Obtener el total devuelto
     */
    public function getTotalReturnedAttribute(): float
    {
        return $this->returns()->where('status', 'completed')->sum('total');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'invoice_number',
                'customer_id',
                'status',
                'total',
                'payment_method',
                'transaction_reference',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function canBeCancelled(): bool
    {
        return $this->isCompleted() && $this->created_at->diffInDays(now()) <= 7;
    }

    // Generate next invoice number
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'FAC-';
        $date = now()->format('Ymd');
        
        // En database-per-tenant, no necesitamos filtrar por tenant_id
        // porque ya estamos en la BD del tenant
        $lastSale = static::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastSale ? (int) substr($lastSale->invoice_number, -4) + 1 : 1;
        
        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Métodos requeridos por VerifiablePDF trait
     */
    public function getVerificationContent(): array
    {
        return [
            'type' => 'sale',
            'invoice_number' => $this->invoice_number,
            'date' => $this->created_at->toIso8601String(),
            'customer_id' => $this->customer_id,
            'total' => (string) $this->total,
            'payment_method' => $this->payment_method,
            'items_count' => $this->items()->count(),
        ];
    }
    
    public function getVerificationDocumentType(): string
    {
        return 'sale_receipt';
    }
    
    protected function getVerificationMetadata(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'total' => (string) $this->total,
            'payment_method' => $this->payment_method,
        ];
    }
}
