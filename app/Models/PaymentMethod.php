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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';

    protected $table = 'payment_methods';

    // Payment method types
    const TYPE_CARD = 'card';

    const TYPE_BANK_TRANSFER = 'bank_transfer';

    const TYPE_PAYPAL = 'paypal';

    const TYPE_MERCADOPAGO = 'mercadopago';

    const TYPE_CASH = 'cash';

    const TYPE_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'type',
        'provider',
        'provider_id',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'card_holder_name',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCards($query)
    {
        return $query->where('type', self::TYPE_CARD);
    }

    public function scopeBankTransfers($query)
    {
        return $query->where('type', self::TYPE_BANK_TRANSFER);
    }

    // Helper methods
    public function isCard(): bool
    {
        return $this->type === self::TYPE_CARD;
    }

    public function isBankTransfer(): bool
    {
        return $this->type === self::TYPE_BANK_TRANSFER;
    }

    public function isPayPal(): bool
    {
        return $this->type === self::TYPE_PAYPAL;
    }

    public function isMercadoPago(): bool
    {
        return $this->type === self::TYPE_MERCADOPAGO;
    }

    public function getDisplayName(): string
    {
        if ($this->isCard()) {
            return ucfirst($this->card_brand).' •••• '.$this->card_last_four;
        }

        if ($this->isBankTransfer()) {
            return 'Transferencia Bancaria';
        }

        if ($this->isPayPal()) {
            return 'PayPal';
        }

        if ($this->isMercadoPago()) {
            return 'MercadoPago';
        }

        return ucfirst($this->type);
    }

    public function getIcon(): string
    {
        return match ($this->type) {
            self::TYPE_CARD => 'heroicon-o-credit-card',
            self::TYPE_BANK_TRANSFER => 'heroicon-o-building-library',
            self::TYPE_PAYPAL => 'heroicon-o-banknotes',
            self::TYPE_MERCADOPAGO => 'heroicon-o-shopping-cart',
            self::TYPE_CASH => 'heroicon-o-banknotes',
            default => 'heroicon-o-currency-dollar',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this->type) {
            self::TYPE_CARD => 'primary',
            self::TYPE_BANK_TRANSFER => 'success',
            self::TYPE_PAYPAL => 'info',
            self::TYPE_MERCADOPAGO => 'warning',
            default => 'secondary',
        };
    }

    public function isExpired(): bool
    {
        if (! $this->isCard()) {
            return false;
        }

        $expMonth = (int) $this->card_exp_month;
        $expYear = (int) $this->card_exp_year;

        if ($expYear < now()->year) {
            return true;
        }

        if ($expYear == now()->year && $expMonth < now()->month) {
            return true;
        }

        return false;
    }

    public function isExpiringSoon(int $months = 2): bool
    {
        if (! $this->isCard()) {
            return false;
        }

        $expMonth = (int) $this->card_exp_month;
        $expYear = (int) $this->card_exp_year;
        $expirationDate = \Carbon\Carbon::create($expYear, $expMonth, 1)->endOfMonth();

        return $expirationDate->isBetween(now(), now()->addMonths($months));
    }

    public function makeDefault(): bool
    {
        // Remove default flag from other methods
        static::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        return $this->update(['is_default' => true]);
    }

    // Static helpers
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_CARD => 'Tarjeta de Crédito/Débito',
            self::TYPE_BANK_TRANSFER => 'Transferencia Bancaria',
            self::TYPE_PAYPAL => 'PayPal',
            self::TYPE_MERCADOPAGO => 'MercadoPago',
            self::TYPE_CASH => 'Efectivo',
            self::TYPE_OTHER => 'Otro',
        ];
    }

    public static function getTypeLabel(string $type): string
    {
        return self::getAvailableTypes()[$type] ?? ucfirst($type);
    }
}
