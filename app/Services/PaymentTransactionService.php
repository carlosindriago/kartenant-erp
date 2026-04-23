<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentTransactionService
{
    /**
     * Create payment transaction for manual payment
     */
    public function createManualTransaction(
        TenantSubscription $subscription,
        PaymentProof $paymentProof,
        ?string $transactionId = null
    ): PaymentTransaction {
        $transactionId = $transactionId ?? 'MANUAL-'.uniqid().'-'.time();

        return PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'gateway_driver' => 'manual',
            'amount' => $paymentProof->amount,
            'currency' => $paymentProof->currency,
            'status' => PaymentTransaction::STATUS_PENDING,
            'transaction_id' => $transactionId,
            'proof_of_payment' => json_encode([
                'payment_proof_id' => $paymentProof->id,
                'file_paths' => $paymentProof->file_paths,
                'payment_method' => $paymentProof->payment_method,
                'reference_number' => $paymentProof->reference_number,
                'payment_date' => $paymentProof->payment_date->toDateString(),
            ]),
            'metadata' => [
                'submission_ip' => $paymentProof->ip_address,
                'user_agent' => $paymentProof->user_agent,
                'submission_date' => $paymentProof->created_at->toIso8601String(),
                'payment_proof_files_count' => count($paymentProof->file_paths ?? []),
                'payment_proof_total_size_mb' => $paymentProof->total_file_size_mb,
            ],
        ]);
    }

    /**
     * Process transaction approval
     */
    public function processApproval(
        PaymentTransaction $transaction,
        User $approver,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($transaction, $approver, $notes) {
            // Update transaction
            $transaction->update([
                'status' => PaymentTransaction::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'approval_notes' => $notes,
                    'approval_ip' => request()->ip(),
                    'approval_date' => now()->toIso8601String(),
                ]),
            ]);

            // Process related subscription
            if ($transaction->subscription) {
                $this->activateSubscription($transaction->subscription, $transaction);
            }

            // Process related invoice
            if ($transaction->subscription) {
                $this->markInvoiceAsPaid($transaction->subscription, $transaction);
            }

            Log::info('Payment transaction approved', [
                'transaction_id' => $transaction->id,
                'subscription_id' => $transaction->subscription_id,
                'amount' => $transaction->amount,
                'approved_by' => $approver->id,
            ]);

            return true;
        });
    }

    /**
     * Process transaction rejection
     */
    public function processRejection(
        PaymentTransaction $transaction,
        User $rejector,
        string $reason,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($transaction, $rejector, $reason, $notes) {
            // Update transaction
            $transaction->update([
                'status' => PaymentTransaction::STATUS_REJECTED,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'rejection_reason' => $reason,
                    'rejection_notes' => $notes,
                    'rejected_by' => $rejector->id,
                    'rejection_ip' => request()->ip(),
                    'rejection_date' => now()->toIso8601String(),
                ]),
            ]);

            // Process related subscription
            if ($transaction->subscription) {
                $this->handleRejectionForSubscription($transaction->subscription, $transaction);
            }

            Log::info('Payment transaction rejected', [
                'transaction_id' => $transaction->id,
                'subscription_id' => $transaction->subscription_id,
                'amount' => $transaction->amount,
                'rejected_by' => $rejector->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Activate subscription after successful payment
     */
    private function activateSubscription(TenantSubscription $subscription, PaymentTransaction $transaction): void
    {
        // Calculate new end date
        $billingCycle = $subscription->billing_cycle;
        $currentEndDate = $subscription->ends_at ?? now();
        $newEndDate = $billingCycle === 'yearly'
            ? $currentEndDate->copy()->addYear()
            : $currentEndDate->copy()->addMonth();

        $subscription->update([
            'status' => 'active',
            'ends_at' => $newEndDate,
            'next_billing_at' => $newEndDate,
        ]);

        // Update tenant status
        $tenant = $subscription->tenant;
        $tenant->update(['status' => 'active']);

        // Clear subscription cache
        $this->clearSubscriptionCache($subscription->tenant_id);
    }

    /**
     * Mark invoice as paid
     */
    private function markInvoiceAsPaid(TenantSubscription $subscription, PaymentTransaction $transaction): void
    {
        $invoice = Invoice::where('tenant_subscription_id', $subscription->id)
            ->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT])
            ->first();

        if ($invoice) {
            $invoice->markAsPaid('manual', $transaction->transaction_id);
        }
    }

    /**
     * Handle rejection for subscription
     */
    private function handleRejectionForSubscription(TenantSubscription $subscription, PaymentTransaction $transaction): void
    {
        // Count recent rejections
        $recentRejections = PaymentTransaction::where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->where('status', PaymentTransaction::STATUS_REJECTED)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Suspend subscription after 3 rejections in 30 days
        if ($recentRejections >= 3) {
            $subscription->update(['status' => 'suspended']);

            $tenant = $subscription->tenant;
            $tenant->update(['status' => 'suspended']);

            Log::warning('Subscription suspended due to multiple rejections', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'rejection_count' => $recentRejections,
            ]);
        } else {
            $subscription->update(['status' => 'payment_failed']);
        }
    }

    /**
     * Create transaction for automated gateway
     */
    public function createGatewayTransaction(
        TenantSubscription $subscription,
        string $gateway,
        string $gatewayTransactionId,
        float $amount,
        string $currency,
        array $gatewayData = []
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'gateway_driver' => $gateway,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentTransaction::STATUS_PENDING,
            'transaction_id' => $gatewayTransactionId,
            'metadata' => array_merge($gatewayData, [
                'gateway' => $gateway,
                'gateway_transaction_id' => $gatewayTransactionId,
                'subscription_billing_cycle' => $subscription->billing_cycle,
                'created_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Update transaction from gateway webhook
     */
    public function updateFromGatewayWebhook(
        string $gatewayTransactionId,
        array $webhookData
    ): ?PaymentTransaction {
        $transaction = PaymentTransaction::where('transaction_id', $gatewayTransactionId)->first();

        if (! $transaction) {
            Log::warning('Transaction not found for webhook', [
                'gateway_transaction_id' => $gatewayTransactionId,
                'webhook_data' => $webhookData,
            ]);

            return null;
        }

        $newStatus = $this->mapGatewayStatus($webhookData['status'] ?? null);

        if ($newStatus) {
            $transaction->update([
                'status' => $newStatus,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'webhook_data' => $webhookData,
                    'webhook_processed_at' => now()->toIso8601String(),
                ]),
            ]);

            // Auto-approve if status is completed
            if ($newStatus === PaymentTransaction::STATUS_COMPLETED) {
                $systemUser = User::where('is_super_admin', true)->first();
                $this->processApproval($transaction, $systemUser, 'Aprobación automática desde gateway');
            }

            Log::info('Transaction updated from webhook', [
                'transaction_id' => $transaction->id,
                'gateway_transaction_id' => $gatewayTransactionId,
                'new_status' => $newStatus,
            ]);
        }

        return $transaction;
    }

    /**
     * Map gateway status to internal status
     */
    private function mapGatewayStatus(?string $gatewayStatus): ?string
    {
        return match (strtolower($gatewayStatus ?? '')) {
            'succeeded', 'completed', 'paid' => PaymentTransaction::STATUS_COMPLETED,
            'failed', 'declined', 'error' => PaymentTransaction::STATUS_FAILED,
            'pending', 'processing' => PaymentTransaction::STATUS_PENDING,
            default => null,
        };
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = PaymentTransaction::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $pending = (clone $query)->where('status', PaymentTransaction::STATUS_PENDING)->count();
        $approved = (clone $query)->where('status', PaymentTransaction::STATUS_APPROVED)->count();
        $rejected = (clone $query)->where('status', PaymentTransaction::STATUS_REJECTED)->count();
        $completed = (clone $query)->where('status', PaymentTransaction::STATUS_COMPLETED)->count();
        $failed = (clone $query)->where('status', PaymentTransaction::STATUS_FAILED)->count();

        $totalAmount = (clone $query)->whereIn('status', [
            PaymentTransaction::STATUS_APPROVED,
            PaymentTransaction::STATUS_COMPLETED,
        ])->sum('amount');

        $averageAmount = $total > 0 ? $totalAmount / ($approved + $completed) : 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'completed' => $completed,
            'failed' => $failed,
            'total_amount' => (float) $totalAmount,
            'average_amount' => (float) $averageAmount,
            'approval_rate' => $total > 0 ? (($approved + $completed) / $total) * 100 : 0,
            'rejection_rate' => $total > 0 ? ($rejected / $total) * 100 : 0,
        ];
    }

    /**
     * Get tenant transaction history
     */
    public function getTenantTransactionHistory(
        Tenant $tenant,
        ?int $limit = 50,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = PaymentTransaction::where('tenant_id', $tenant->id)
            ->with(['subscription.plan', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $transactions = $query->limit($limit)->get();

        return [
            'transactions' => $transactions->toArray(),
            'summary' => [
                'total' => $transactions->count(),
                'total_amount' => (float) $transactions->sum('amount'),
                'pending_count' => $transactions->where('status', PaymentTransaction::STATUS_PENDING)->count(),
                'approved_count' => $transactions->whereIn('status', [
                    PaymentTransaction::STATUS_APPROVED,
                    PaymentTransaction::STATUS_COMPLETED,
                ])->count(),
                'rejected_count' => $transactions->where('status', PaymentTransaction::STATUS_REJECTED)->count(),
            ],
        ];
    }

    /**
     * Get pending transactions requiring approval
     */
    public function getPendingTransactions(?int $limit = 20): array
    {
        $transactions = PaymentTransaction::where('status', PaymentTransaction::STATUS_PENDING)
            ->with(['tenant', 'subscription.plan'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'tenant_name' => $transaction->tenant->name,
                'tenant_email' => $transaction->tenant->owner_email,
                'plan_name' => $transaction->subscription->plan->name ?? 'N/A',
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'gateway' => $transaction->gateway_driver,
                'transaction_id' => $transaction->transaction_id,
                'created_at' => $transaction->created_at->toIso8601String(),
                'days_pending' => $transaction->created_at->diffInDays(now()),
                'payment_method' => $this->getPaymentMethodFromTransaction($transaction),
            ];
        })->toArray();
    }

    /**
     * Get payment method from transaction metadata
     */
    private function getPaymentMethodFromTransaction(PaymentTransaction $transaction): string
    {
        $metadata = $transaction->metadata ?? [];

        if (isset($metadata['payment_method'])) {
            return $metadata['payment_method'];
        }

        if (isset($metadata['gateway'])) {
            return match ($metadata['gateway']) {
                'stripe' => 'Tarjeta de Crédito/Débito',
                'paypal' => 'PayPal',
                'mercadopago' => 'Mercado Pago',
                default => ucfirst($metadata['gateway']),
            };
        }

        return 'Manual';
    }

    /**
     * Search transactions
     */
    public function searchTransactions(array $filters): array
    {
        $query = PaymentTransaction::with(['tenant', 'subscription.plan', 'approver']);

        // Filter by tenant
        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by gateway
        if (! empty($filters['gateway'])) {
            $query->where('gateway_driver', $filters['gateway']);
        }

        // Filter by amount range
        if (! empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Search by transaction ID
        if (! empty($filters['transaction_id'])) {
            $query->where('transaction_id', 'like', '%'.$filters['transaction_id'].'%');
        }

        // Order and paginate
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 20;

        $transactions = $query->orderBy($orderBy, $orderDirection)
            ->paginate($perPage);

        return [
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ];
    }

    /**
     * Clear subscription cache
     */
    private function clearSubscriptionCache(int $tenantId): void
    {
        $cacheKeys = [
            "tenant_subscription_{$tenantId}",
            "tenant_status_{$tenantId}",
            "tenant_billing_summary_{$tenantId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get transactions requiring follow-up
     */
    public function getTransactionsRequiringFollowUp(): array
    {
        $results = [
            'overdue_pending' => [],
            'failed_transactions' => [],
            'high_value_pending' => [],
        ];

        // Pending transactions over 48 hours
        $results['overdue_pending'] = PaymentTransaction::where('status', PaymentTransaction::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(48))
            ->with(['tenant', 'subscription.plan'])
            ->get()
            ->toArray();

        // Failed transactions from last 7 days
        $results['failed_transactions'] = PaymentTransaction::where('status', PaymentTransaction::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['tenant', 'subscription.plan'])
            ->get()
            ->toArray();

        // High value pending transactions (> $100)
        $results['high_value_pending'] = PaymentTransaction::where('status', PaymentTransaction::STATUS_PENDING)
            ->where('amount', '>', 100)
            ->with(['tenant', 'subscription.plan'])
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return $results;
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactionsToCsv(array $filters): string
    {
        $query = PaymentTransaction::with(['tenant', 'subscription.plan', 'approver']);

        // Apply same filters as search method
        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Create CSV content
        $csv = implode(',', [
            'ID',
            'Tenant',
            'Plan',
            'Amount',
            'Currency',
            'Gateway',
            'Status',
            'Transaction ID',
            'Created At',
            'Approved At',
            'Approved By',
        ])."\n";

        foreach ($transactions as $transaction) {
            $csv .= implode(',', [
                $transaction->id,
                '"'.($transaction->tenant->name ?? '').'"',
                '"'.($transaction->subscription->plan->name ?? '').'"',
                $transaction->amount,
                $transaction->currency,
                $transaction->gateway_driver,
                $transaction->status,
                $transaction->transaction_id,
                $transaction->created_at->toIso8601String(),
                $transaction->approved_at?->toIso8601String() ?? '',
                '"'.($transaction->approver->name ?? '').'"',
            ])."\n";
        }

        // Generate filename
        $filename = 'payment_transactions_'.now()->format('Y-m-d_H-i-s').'.csv';
        $path = "exports/{$filename}";

        // Store file
        Storage::disk('public')->put($path, $csv);

        return $path;
    }
}
