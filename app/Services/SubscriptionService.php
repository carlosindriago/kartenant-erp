<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantUsage;
use App\Models\User;
use App\Settings\BillingSettings;
use App\Settings\PaymentSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(
        private PaymentSettings $paymentSettings,
        private BillingSettings $billingSettings
    ) {}

    /**
     * Create new subscription for tenant
     */
    public function createSubscription(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $billingCycle = 'monthly',
        ?Carbon $startDate = null
    ): TenantSubscription {
        return DB::connection('landlord')->transaction(function () use ($tenant, $plan, $billingCycle, $startDate) {
            $startDate = $startDate ?? now();
            $price = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $endDate = $this->calculateEndDate($startDate, $billingCycle);

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'billing_cycle' => $billingCycle,
                'price' => $price,
                'currency' => $plan->currency,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'trial_ends_at' => $plan->has_trial ? $startDate->addDays($plan->trial_days) : null,
                'next_billing_at' => $endDate,
                'auto_renew' => true,
            ]);

            // Create invoice
            $this->createInvoice($subscription);

            // Update tenant status
            $tenant->update([
                'status' => $plan->has_trial ? 'trial' : 'suspended',
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);

            // Initialize tenant usage limits for the new subscription
            $this->updateTenantUsageLimits($subscription);

            Log::info('Subscription created', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'plan' => $plan->name,
                'billing_cycle' => $billingCycle,
            ]);

            return $subscription;
        });
    }

    /**
     * Process payment proof submission
     */
    public function submitPaymentProof(
        TenantSubscription $subscription,
        array $proofData,
        array $files = []
    ): PaymentProof {
        return DB::connection('landlord')->transaction(function () use ($subscription, $proofData, $files) {
            // Store uploaded files
            $filePaths = [];
            $totalSize = 0;

            foreach ($files as $file) {
                if ($file->isValid()) {
                    $path = $file->store("payment-proofs/{$subscription->tenant_id}", 'public');
                    $filePaths[] = $path;
                    $totalSize += $file->getSize() / 1024 / 1024; // Convert to MB
                }
            }

            $paymentProof = PaymentProof::create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'payment_method' => $proofData['payment_method'],
                'amount' => $proofData['amount'],
                'currency' => $subscription->currency,
                'payment_date' => $proofData['payment_date'],
                'reference_number' => $proofData['reference_number'] ?? null,
                'payer_name' => $proofData['payer_name'] ?? null,
                'notes' => $proofData['notes'] ?? null,
                'file_paths' => $filePaths,
                'file_type' => ! empty($filePaths) ? pathinfo($filePaths[0], PATHINFO_EXTENSION) : null,
                'total_file_size_mb' => $totalSize,
                'status' => PaymentProof::STATUS_PENDING,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create payment transaction record
            $this->createPaymentTransaction($subscription, $paymentProof);

            Log::info('Payment proof submitted', [
                'subscription_id' => $subscription->id,
                'payment_proof_id' => $paymentProof->id,
                'amount' => $proofData['amount'],
            ]);

            return $paymentProof;
        });
    }

    /**
     * Approve payment proof
     */
    public function approvePaymentProof(
        PaymentProof $paymentProof,
        User $approver,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($paymentProof, $approver, $notes) {
            $paymentProof->approve($approver, $notes);

            $subscription = $paymentProof->subscription;
            if (! $subscription) {
                return false;
            }

            // Mark payment transaction as approved
            $transaction = $paymentProof->paymentTransaction;
            if ($transaction) {
                $transaction->approve($approver);
            }

            // Activate subscription
            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
            ]);

            // Update tenant status
            $tenant = $subscription->tenant;
            $tenant->update(['status' => 'active']);

            // Mark invoice as paid
            $invoice = Invoice::where('tenant_subscription_id', $subscription->id)
                ->unpaid()
                ->first();

            if ($invoice) {
                $invoice->markAsPaid('manual', $transaction->transaction_id ?? null);
            }

            Log::info('Payment proof approved', [
                'payment_proof_id' => $paymentProof->id,
                'subscription_id' => $subscription->id,
                'approved_by' => $approver->id,
            ]);

            return true;
        });
    }

    /**
     * Reject payment proof
     */
    public function rejectPaymentProof(
        PaymentProof $paymentProof,
        User $reviewer,
        string $reason,
        ?string $notes = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($paymentProof, $reviewer, $reason, $notes) {
            $paymentProof->reject($reviewer, $reason, $notes);

            // Mark payment transaction as rejected
            $transaction = $paymentProof->paymentTransaction;
            if ($transaction) {
                $transaction->reject($reviewer, $reason);
            }

            // Update subscription status
            $subscription = $paymentProof->subscription;
            if ($subscription) {
                $subscription->update(['payment_status' => 'failed']);
            }

            Log::info('Payment proof rejected', [
                'payment_proof_id' => $paymentProof->id,
                'subscription_id' => $subscription->id ?? null,
                'rejected_by' => $reviewer->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Change subscription plan
     */
    public function changePlan(
        TenantSubscription $subscription,
        SubscriptionPlan $newPlan,
        ?string $newBillingCycle = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($subscription, $newPlan, $newBillingCycle) {
            $billingCycle = $newBillingCycle ?? $subscription->billing_cycle;
            $newPrice = $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;

            // Calculate prorated amount and new end date
            $newEndDate = $this->calculateEndDate(now(), $billingCycle);
            $proratedAmount = $this->calculateProratedAmount($subscription, $newPlan, $billingCycle);

            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
                'billing_cycle' => $billingCycle,
                'price' => $newPrice,
                'ends_at' => $newEndDate,
                'next_billing_at' => $newEndDate,
            ]);

            // Create credit invoice or additional charge if needed
            if ($proratedAmount != 0) {
                $this->createProratedInvoice($subscription, $newPlan, $proratedAmount);
            }

            Log::info('Subscription plan changed', [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $subscription->subscription_plan_id,
                'new_plan_id' => $newPlan->id,
                'new_price' => $newPrice,
            ]);

            // Update tenant usage limits for the new plan
            $this->updateTenantUsageLimits($subscription);

            return true;
        });
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(
        TenantSubscription $subscription,
        ?string $reason = null
    ): bool {
        return DB::connection('landlord')->transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Update tenant status
            $tenant = $subscription->tenant;
            $tenant->update(['status' => 'inactive']);

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Renew subscription
     */
    public function renewSubscription(TenantSubscription $subscription): bool
    {
        return DB::connection('landlord')->transaction(function () use ($subscription) {
            if (! $subscription->auto_renew) {
                return false;
            }

            $newEndDate = $this->calculateEndDate($subscription->ends_at, $subscription->billing_cycle);

            $subscription->update([
                'ends_at' => $newEndDate,
                'next_billing_at' => $newEndDate,
                'status' => 'active',
            ]);

            // Create new invoice for the renewal period
            $this->createInvoice($subscription);

            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'new_end_date' => $newEndDate,
            ]);

            return true;
        });
    }

    /**
     * Check and process expired subscriptions
     */
    public function processExpiredSubscriptions(): int
    {
        $expiredCount = 0;

        TenantSubscription::where('ends_at', '<', now())
            ->where('status', 'active')
            ->chunk(100, function ($subscriptions) use (&$expiredCount) {
                foreach ($subscriptions as $subscription) {
                    $this->expireSubscription($subscription);
                    $expiredCount++;
                }
            });

        Log::info('Processed expired subscriptions', ['count' => $expiredCount]);

        return $expiredCount;
    }

    /**
     * Expire individual subscription
     */
    private function expireSubscription(TenantSubscription $subscription): void
    {
        DB::connection('landlord')->transaction(function () use ($subscription) {
            $subscription->update(['status' => 'expired']);

            $tenant = $subscription->tenant;
            $tenant->update(['status' => 'expired']);

            Log::info('Subscription expired', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
            ]);
        });
    }

    /**
     * Calculate end date based on billing cycle
     */
    private function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'monthly' => $startDate->copy()->addMonth(),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Calculate prorated amount for plan changes
     */
    private function calculateProratedAmount(
        TenantSubscription $subscription,
        SubscriptionPlan $newPlan,
        string $billingCycle
    ): float {
        $remainingDays = $subscription->ends_at->diffInDays(now());
        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);

        $newPrice = $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;
        $dailyRate = $newPrice / $totalDays;

        return $dailyRate * $remainingDays;
    }

    /**
     * Create invoice for subscription
     */
    private function createInvoice(TenantSubscription $subscription): Invoice
    {
        $plan = $subscription->subscriptionPlan;

        return Invoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => Invoice::STATUS_DRAFT,
            'type' => Invoice::TYPE_SUBSCRIPTION,
            'billing_period_start' => $subscription->starts_at,
            'billing_period_end' => $subscription->ends_at,
            'due_date' => now()->addDays(30),
            'subtotal' => $subscription->price,
            'tax_amount' => $this->billingSettings->calculateTax($subscription->price),
            'total_amount' => $this->billingSettings->getTotalWithTax($subscription->price),
            'currency' => $subscription->currency,
            'plan_name' => $plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'plan_price' => $subscription->price,
        ]);
    }

    /**
     * Create prorated invoice for plan changes
     */
    private function createProratedInvoice(
        TenantSubscription $subscription,
        SubscriptionPlan $newPlan,
        float $proratedAmount
    ): Invoice {
        return Invoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => Invoice::generateInvoiceNumber('ADJ'),
            'status' => Invoice::STATUS_DRAFT,
            'type' => Invoice::TYPE_EXTRA_USAGE,
            'billing_period_start' => now(),
            'billing_period_end' => $subscription->ends_at,
            'due_date' => now()->addDays(7),
            'subtotal' => $proratedAmount,
            'tax_amount' => $this->billingSettings->calculateTax($proratedAmount),
            'total_amount' => $this->billingSettings->getTotalWithTax($proratedAmount),
            'currency' => $subscription->currency,
            'plan_name' => $newPlan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'plan_price' => $proratedAmount,
        ]);
    }

    /**
     * Create payment transaction
     */
    private function createPaymentTransaction(
        TenantSubscription $subscription,
        PaymentProof $paymentProof
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'gateway_driver' => 'manual',
            'amount' => $paymentProof->amount,
            'currency' => $paymentProof->currency,
            'status' => PaymentTransaction::STATUS_PENDING,
            'transaction_id' => 'MANUAL-'.uniqid(),
            'proof_of_payment' => json_encode($paymentProof->file_paths),
            'metadata' => [
                'payment_proof_id' => $paymentProof->id,
                'payment_method' => $paymentProof->payment_method,
                'reference_number' => $paymentProof->reference_number,
                'payment_date' => $paymentProof->payment_date,
            ],
        ]);
    }

    /**
     * Update tenant usage limits when subscription changes
     */
    public function updateTenantUsageLimits(TenantSubscription $subscription): void
    {
        $tenant = $subscription->tenant;
        $plan = $subscription->plan;

        if (! $tenant || ! $plan) {
            return;
        }

        $currentUsage = TenantUsage::getCurrentUsage($tenant->id);

        if (! $currentUsage) {
            return;
        }

        // Update limits based on plan configuration
        if ($plan->hasConfigurableLimits()) {
            // Use new configurable limits system
            $currentUsage->update([
                'max_sales_per_month' => $plan->getConfigurableLimit('monthly_sales'),
                'max_products' => $plan->getConfigurableLimit('products'),
                'max_users' => $plan->getConfigurableLimit('users'),
                'max_storage_mb' => $plan->getConfigurableLimit('storage_mb'),
            ]);
        } else {
            // Use legacy limits for backward compatibility
            $currentUsage->update([
                'max_sales_per_month' => $plan->max_sales_per_month,
                'max_products' => $plan->max_products,
                'max_users' => $plan->max_users,
                'max_storage_mb' => $plan->max_storage_mb,
            ]);
        }

        // Recalculate percentages and status
        $currentUsage->calculatePercentages();

        Log::info('Tenant usage limits updated', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'plan' => $plan->name,
            'configurable_limits' => $plan->hasConfigurableLimits(),
        ]);
    }

    /**
     * Check if tenant can perform action based on current subscription and usage
     */
    public function canTenantPerformAction(Tenant $tenant, string $action): bool
    {
        $subscription = $tenant->subscription;
        if (! $subscription || $subscription->status !== 'active') {
            return false;
        }

        $usage = TenantUsage::getCurrentUsage($tenant->id);
        if (! $usage) {
            return true; // No usage recorded yet
        }

        return match ($action) {
            'create_product' => $usage->canCreateProduct(),
            'create_user' => $usage->canCreateUser(),
            'make_sale' => $usage->canMakeSale(),
            default => true,
        };
    }

    /**
     * Get tenant's current usage status with configurable limits
     */
    public function getTenantUsageStatus(Tenant $tenant): array
    {
        $subscription = $tenant->subscription;
        $plan = $subscription->plan ?? null;
        $usage = TenantUsage::getCurrentUsage($tenant->id);

        if (! $usage) {
            return [
                'status' => 'unknown',
                'metrics' => [],
                'plan_limits' => null,
                'configurable_limits' => false,
            ];
        }

        $metrics = [];
        $planLimits = null;

        if ($plan && $plan->hasConfigurableLimits()) {
            $planLimits = $plan->limits;
            $overagePercentage = $plan->overage_percentage;
            $allowsOverage = $plan->allowsOverage();

            foreach ($plan->getAvailableMetrics() as $metric) {
                $current = match ($metric) {
                    'monthly_sales' => $usage->sales_count,
                    'products' => $usage->products_count,
                    'users' => $usage->users_count,
                    'storage_mb' => $usage->storage_size_mb,
                    default => 0,
                };

                $status = $plan->getLimitStatus($metric, $current);
                $metrics[$metric] = $status;
            }
        } else {
            // Legacy system fallback
            $metrics = [
                'sales' => [
                    'current' => $usage->sales_count,
                    'limit' => $usage->max_sales_per_month,
                    'percentage' => $usage->sales_percentage,
                    'zone' => $usage->getZoneForMetric('sales'),
                ],
                'products' => [
                    'current' => $usage->products_count,
                    'limit' => $usage->max_products,
                    'percentage' => $usage->products_percentage,
                    'zone' => $usage->getZoneForMetric('products'),
                ],
                'users' => [
                    'current' => $usage->users_count,
                    'limit' => $usage->max_users,
                    'percentage' => $usage->users_percentage,
                    'zone' => $usage->getZoneForMetric('users'),
                ],
                'storage' => [
                    'current' => $usage->storage_size_mb,
                    'limit' => $usage->max_storage_mb,
                    'percentage' => $usage->storage_percentage,
                    'zone' => $usage->getZoneForMetric('storage'),
                ],
            ];
        }

        return [
            'status' => $usage->status,
            'upgrade_required' => $usage->upgrade_required_next_cycle,
            'metrics' => $metrics,
            'plan_limits' => $planLimits,
            'configurable_limits' => $plan?->hasConfigurableLimits() ?? false,
            'overage_strategy' => $plan?->overage_strategy ?? 'strict',
            'overage_percentage' => $plan?->overage_percentage ?? 20,
            'period' => $usage->getPeriodLabel(),
            'days_remaining' => $usage->getDaysRemainingInPeriod(),
        ];
    }

    /**
     * Apply plan configuration changes to existing subscriptions
     */
    public function applyPlanConfigurationChanges(SubscriptionPlan $plan): int
    {
        $updatedCount = 0;

        $plan->activeSubscriptions()
            ->with('tenant')
            ->chunk(100, function ($subscriptions) use (&$updatedCount) {
                foreach ($subscriptions as $subscription) {
                    $this->updateTenantUsageLimits($subscription);
                    $updatedCount++;
                }
            });

        Log::info('Plan configuration changes applied', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'updated_subscriptions' => $updatedCount,
        ]);

        return $updatedCount;
    }

    /**
     * Calculate total monthly cost for tenant (Plan + Active Modules)
     *
     * @return array ['plan_price' => float, 'modules_price' => float, 'total' => float]
     */
    public function calculateMonthlyTotal(Tenant $tenant): array
    {
        // Get active subscription plan price
        $planPrice = 0;
        $activeSubscription = $tenant->activeSubscription;

        if ($activeSubscription && $activeSubscription->plan) {
            $planPrice = $activeSubscription->plan->base_price;
        }

        // Calculate active modules total cost
        $modulesPrice = 0;
        $setupFees = 0;
        $activeModules = $tenant->modules()->withPivot(['price_override', 'starts_at'])->get();

        foreach ($activeModules as $module) {
            // Calculate monthly price for module
            $modulePrice = $module->pivot->price_override ?? $module->base_price_monthly;
            $modulesPrice += $modulePrice;

            // Calculate setup fee for modules (one-time, charged in first month)
            $setupFeeAmount = $module->setup_fee;

            // Include setup fee if module has it (setup_fee > 0)
            if ($setupFeeAmount > 0) {
                $setupFees += $setupFeeAmount;
            }
        }

        $total = $planPrice + $modulesPrice;

        return [
            'plan_price' => $planPrice,
            'modules_price' => $modulesPrice,
            'setup_fees' => $setupFees,
            'total' => $total,
            'first_month_total' => $total + $setupFees,
            'currency' => 'USD',
            'module_count' => $activeModules->count(),
            'subscription_id' => $activeSubscription?->id,
            'subscription_status' => $activeSubscription?->status,
        ];
    }
}
