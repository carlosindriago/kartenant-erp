<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $connection = 'landlord';
    protected $table = 'payment_transactions';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'gateway_driver',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'proof_of_payment',
        'metadata',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway_driver', $gateway);
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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
     * Approve transaction
     */
    public function approve(User $approver): bool
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Activate subscription
        if ($this->subscription) {
            $this->subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
            ]);
        }

        return true;
    }

    /**
     * Reject transaction
     */
    public function reject(User $rejector, ?string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['rejection_reason'] = $reason;
        $metadata['rejected_by'] = $rejector->id;
        $metadata['rejected_at'] = now()->toIso8601String();

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'metadata' => $metadata,
        ]);
    }
}
