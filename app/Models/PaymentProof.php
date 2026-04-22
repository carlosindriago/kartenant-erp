<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentProof extends Model
{
    protected $connection = 'landlord';
    protected $table = 'payment_proofs';

    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_MOBILE_MONEY = 'mobile_money';
    const PAYMENT_METHOD_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'payment_transaction_id',
        'payment_method',
        'amount',
        'currency',
        'payment_date',
        'reference_number',
        'payer_name',
        'notes',
        'file_paths',
        'file_type',
        'total_file_size_mb',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'file_paths' => 'array',
        'total_file_size_mb' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Start review process
     */
    public function startReview(User $reviewer): bool
    {
        return $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Approve payment proof
     */
    public function approve(User $reviewer, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Reject payment proof
     */
    public function reject(User $reviewer, string $reason, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Get formatted file paths
     */
    public function getFormattedFilePathsAttribute(): array
    {
        return collect($this->file_paths ?? [])->map(function ($path) {
            return [
                'original_name' => basename($path),
                'full_path' => storage_path('app/' . $path),
                'url' => asset('storage/' . $path),
                'size' => $this->getFileSize($path),
            ];
        })->toArray();
    }

    /**
     * Get file size
     */
    private function getFileSize(string $path): ?string
    {
        $fullPath = storage_path('app/' . $path);
        if (!file_exists($fullPath)) {
            return null;
        }

        $bytes = filesize($fullPath);
        if ($bytes === false) {
            return null;
        }

        return number_format($bytes / 1024 / 1024, 2) . ' MB';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_UNDER_REVIEW => 'info',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            self::PAYMENT_METHOD_BANK_TRANSFER => 'Transferencia Bancaria',
            self::PAYMENT_METHOD_CASH => 'Efectivo',
            self::PAYMENT_METHOD_MOBILE_MONEY => 'Dinero Móvil',
            self::PAYMENT_METHOD_OTHER => 'Otro',
            default => ucfirst($this->payment_method),
        };
    }
}