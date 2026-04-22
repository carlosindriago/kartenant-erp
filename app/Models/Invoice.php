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
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\BelongsToTenant;

class Invoice extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $connection = 'landlord';
    protected $table = 'invoices';

    // Invoice statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'tenant_id',
        'tenant_subscription_id',
        'invoice_number',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'payment_method',
        'payment_provider',
        'provider_payment_id',
        'paid_at',
        'due_date',
        'billing_name',
        'billing_email',
        'billing_address',
        'tax_id',
        'items',
        'notes',
        'admin_notes',
        'pdf_path',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'items' => 'array',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isOverdue(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    public function isDueSoon(int $days = 7): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->due_date
            && $this->due_date->isFuture()
            && $this->due_date->lte(now()->addDays($days));
    }

    public function daysUntilDue(): ?int
    {
        if (!$this->due_date || !$this->isPending()) {
            return null;
        }

        return (int) now()->diffInDays($this->due_date, false);
    }

    public function markAsPaid(string $paymentProvider = null, string $providerPaymentId = null): bool
    {
        $data = [
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ];

        if ($paymentProvider) {
            $data['payment_provider'] = $paymentProvider;
        }

        if ($providerPaymentId) {
            $data['provider_payment_id'] = $providerPaymentId;
        }

        return $this->update($data);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => self::STATUS_FAILED]);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function refund(): bool
    {
        return $this->update(['status' => self::STATUS_REFUNDED]);
    }

    public function getFormattedTotal(): string
    {
        return $this->currency . ' ' . number_format((float) $this->total, 2);
    }

    public function getFormattedSubtotal(): string
    {
        return $this->currency . ' ' . number_format((float) $this->subtotal, 2);
    }

    public function getFormattedTax(): string
    {
        return $this->currency . ' ' . number_format((float) $this->tax, 2);
    }

    public function getFormattedDiscount(): string
    {
        return $this->currency . ' ' . number_format((float) $this->discount, 2);
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_REFUNDED => 'info',
            default => 'secondary',
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            self::STATUS_PAID => 'heroicon-o-check-circle',
            self::STATUS_PENDING => 'heroicon-o-clock',
            self::STATUS_FAILED => 'heroicon-o-x-circle',
            self::STATUS_CANCELLED => 'heroicon-o-no-symbol',
            self::STATUS_REFUNDED => 'heroicon-o-arrow-uturn-left',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function addItem(array $item): bool
    {
        $items = $this->items ?? [];
        $items[] = $item;
        return $this->update(['items' => $items]);
    }

    public function calculateTotals(): void
    {
        $this->total = $this->subtotal + $this->tax - $this->discount;
    }

    // Static helpers
    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'INV';

        // Find the last invoice number for today
        $lastInvoice = self::where('invoice_number', 'LIKE', "{$prefix}-{$date}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PAID => 'Pagado',
            self::STATUS_FAILED => 'Fallido',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_REFUNDED => 'Reembolsado',
        ];
    }

    public static function createForSubscription(
        TenantSubscription $subscription,
        array $additionalData = []
    ): self {
        $tenant = $subscription->tenant;
        $plan = $subscription->subscription_plan;

        $invoice = new self();
        $invoice->invoice_number = self::generateInvoiceNumber();
        $invoice->tenant_id = $tenant->id;
        $invoice->tenant_subscription_id = $subscription->id;
        $invoice->status = self::STATUS_PENDING;
        $invoice->currency = $subscription->currency;
        $invoice->payment_method = $subscription->payment_method;

        // Billing details from tenant
        $invoice->billing_name = $tenant->name;
        $invoice->billing_email = $tenant->owner_email;

        // Calculate amounts
        $invoice->subtotal = $subscription->price;
        $invoice->tax = $additionalData['tax'] ?? 0;
        $invoice->discount = $additionalData['discount'] ?? 0;
        $invoice->calculateTotals();

        // Items
        $invoice->items = [
            [
                'description' => $plan->name . ' - ' . ucfirst($subscription->billing_cycle),
                'quantity' => 1,
                'unit_price' => (float) $subscription->price,
                'total' => (float) $subscription->price,
            ],
        ];

        // Due date (30 days from now)
        $invoice->due_date = $additionalData['due_date'] ?? now()->addDays(30);

        // Notes
        $invoice->notes = $additionalData['notes'] ?? null;
        $invoice->admin_notes = $additionalData['admin_notes'] ?? null;

        $invoice->save();

        return $invoice;
    }

    public static function getTotalRevenue(?string $period = null): float
    {
        $query = self::paid();

        if ($period === 'today') {
            $query->whereDate('paid_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year);
        } elseif ($period === 'year') {
            $query->whereYear('paid_at', now()->year);
        }

        return (float) $query->sum('total');
    }

    public static function getAverageInvoiceAmount(): float
    {
        $avg = self::paid()->avg('total');
        return $avg ? (float) $avg : 0.0;
    }

    public static function getOutstandingAmount(): float
    {
        return (float) self::pending()->sum('total');
    }

    public static function getOverdueAmount(): float
    {
        return (float) self::overdue()->sum('total');
    }

    // Boot method to auto-generate invoice number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });

        static::updating(function ($invoice) {
            // Auto-set paid_at when status changes to paid
            if ($invoice->isDirty('status') && $invoice->status === self::STATUS_PAID && !$invoice->paid_at) {
                $invoice->paid_at = now();
            }
        });
    }
}
