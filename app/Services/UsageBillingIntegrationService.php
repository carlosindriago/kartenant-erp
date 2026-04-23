<?php

namespace App\Services;

use App\Events\SalesTaskCreated;
use App\Mail\PlanUpgradeConfirmationMail;
use App\Mail\PlanUpgradeRequiredMail;
use App\Models\Notification;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantUsage;

class UsageBillingIntegrationService
{
    public function __construct(
        private TenantUsageService $usageService,
        private BillingService $billingService
    ) {}

    /**
     * Process monthly billing cycle transitions
     */
    public function processMonthlyCycle(): void
    {
        Tenant::chunk(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->processTenantBillingCycle($tenant);
            }
        });
    }

    /**
     * Process individual tenant billing cycle
     */
    public function processTenantBillingCycle(Tenant $tenant): void
    {
        // Get current usage record
        $currentUsage = TenantUsage::getCurrentUsage($tenant->id);

        if (! $currentUsage) {
            return;
        }

        // Check if we need to force upgrade
        if ($currentUsage->upgrade_required_next_cycle) {
            $this->handleRequiredUpgrade($tenant, $currentUsage);
        }

        // Create new usage record for next month
        $this->createNextMonthUsage($tenant);

        // Reset Redis counters for new month
        $this->usageService->clearRedisCounters($tenant->id);

        // Clear usage cache
        $this->usageService->clearCache($tenant->id);
    }

    /**
     * Handle required upgrade scenario
     */
    private function handleRequiredUpgrade(Tenant $tenant, TenantUsage $usage): void
    {
        // Send upgrade notification
        $this->sendUpgradeNotification($tenant, $usage);

        // Create task for sales team
        $this->createSalesTask($tenant, $usage);

        // Log the event
        logger()->info('Tenant requires plan upgrade', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'status' => $usage->status,
            'usage_percentages' => [
                'sales' => $usage->sales_percentage,
                'products' => $usage->products_percentage,
                'users' => $usage->users_percentage,
                'storage' => $usage->storage_percentage,
            ],
        ]);
    }

    /**
     * Send upgrade notification to tenant
     */
    private function sendUpgradeNotification(Tenant $tenant, TenantUsage $usage): void
    {
        $notificationData = [
            'tenant' => $tenant,
            'usage' => $usage,
            'recommended_plan' => $this->getRecommendedPlan($tenant, $usage),
            'upgrade_url' => route('billing.upgrade', ['tenant' => $tenant->id]),
        ];

        // Send email notification
        \Mail::to($tenant->owner?->email)->send(
            new PlanUpgradeRequiredMail($notificationData)
        );

        // Send in-app notification
        $this->createInAppNotification($tenant, $notificationData);
    }

    /**
     * Create sales team task
     */
    private function createSalesTask(Tenant $tenant, TenantUsage $usage): void
    {
        $taskData = [
            'tenant_id' => $tenant->id,
            'task_type' => 'upgrade_opportunity',
            'priority' => $usage->status === 'critical' ? 'high' : 'medium',
            'subject' => "Oportunidad de Upgrade - {$tenant->name}",
            'description' => $this->generateSalesTaskDescription($tenant, $usage),
            'due_date' => now()->addDays(3),
            'assigned_to' => 'sales_team',
            'metadata' => [
                'current_usage' => $usage->toArray(),
                'recommended_plan' => $this->getRecommendedPlan($tenant, $usage),
            ],
        ];

        // Create task in your task management system
        // This would integrate with whatever task system you use
        event(new SalesTaskCreated($taskData));
    }

    /**
     * Generate sales task description
     */
    private function generateSalesTaskDescription(Tenant $tenant, TenantUsage $usage): string
    {
        $exceededMetrics = [];

        if ($usage->isOverLimit('sales')) {
            $exceededMetrics[] = "Ventas: {$usage->sales_count} ({$usage->sales_percentage}%)";
        }

        if ($usage->isOverLimit('products')) {
            $exceededMetrics[] = "Productos: {$usage->products_count} ({$usage->products_percentage}%)";
        }

        if ($usage->isOverLimit('users')) {
            $exceededMetrics[] = "Usuarios: {$usage->users_count} ({$usage->users_percentage}%)";
        }

        if ($usage->isOverLimit('storage')) {
            $exceededMetrics[] = "Almacenamiento: {$usage->storage_size_mb}MB ({$usage->storage_percentage}%)";
        }

        $description = "**Cliente:** {$tenant->name}\n\n";
        $description .= "**Métricas Excedidas:**\n".implode("\n", $exceededMetrics)."\n\n";
        $description .= "**Estado Actual:** {$usage->status}\n\n";
        $description .= "**Acción Requerida:** Contactar cliente para upgrade de plan.\n\n";
        $description .= '**Urgencia:** '.($usage->status === 'critical' ? 'ALTA' : 'MEDIA');

        return $description;
    }

    /**
     * Get recommended plan based on usage
     */
    private function getRecommendedPlan(Tenant $tenant, TenantUsage $usage): ?SubscriptionPlan
    {
        $currentSubscription = $tenant->subscription;
        if (! $currentSubscription) {
            return null;
        }

        $currentPlan = $currentSubscription->plan;
        if (! $currentPlan) {
            return null;
        }

        // Calculate required limits based on current usage with buffer
        $requiredLimits = [
            'max_sales_per_month' => max($usage->sales_count, $currentPlan->max_sales_per_month) * 1.5,
            'max_products' => max($usage->products_count, $currentPlan->max_products) * 1.5,
            'max_users' => max($usage->users_count, $currentPlan->max_users) * 1.5,
            'max_storage_mb' => max($usage->storage_size_mb, $currentPlan->max_storage_mb) * 1.5,
        ];

        // Find the smallest plan that meets all requirements
        return SubscriptionPlan::where('is_active', true)
            ->where(function ($query) use ($requiredLimits) {
                $query
                    ->where(function ($q) use ($requiredLimits) {
                        $q->whereNull('max_sales_per_month')
                            ->orWhere('max_sales_per_month', '>=', $requiredLimits['max_sales_per_month']);
                    })
                    ->where(function ($q) use ($requiredLimits) {
                        $q->whereNull('max_products')
                            ->orWhere('max_products', '>=', $requiredLimits['max_products']);
                    })
                    ->where(function ($q) use ($requiredLimits) {
                        $q->whereNull('max_users')
                            ->orWhere('max_users', '>=', $requiredLimits['max_users']);
                    })
                    ->where(function ($q) use ($requiredLimits) {
                        $q->whereNull('max_storage_mb')
                            ->orWhere('max_storage_mb', '>=', $requiredLimits['max_storage_mb']);
                    });
            })
            ->orderBy('price_monthly')
            ->first();
    }

    /**
     * Create usage record for next month
     */
    private function createNextMonthUsage(Tenant $tenant): void
    {
        $nextMonth = now()->addMonth();
        $subscription = $tenant->subscription;
        $plan = $subscription->plan ?? null;

        TenantUsage::create([
            'tenant_id' => $tenant->id,
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
            'max_sales_per_month' => $plan->max_sales_per_month ?? null,
            'max_products' => $plan->max_products ?? null,
            'max_users' => $plan->max_users ?? null,
            'max_storage_mb' => $plan->max_storage_mb ?? null,
        ]);
    }

    /**
     * Create in-app notification
     */
    private function createInAppNotification(Tenant $tenant, array $data): void
    {
        // This would integrate with your notification system
        Notification::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->owner?->id,
            'type' => 'plan_upgrade_required',
            'title' => 'Actualización de Plan Requerida',
            'message' => 'Tu plan actual requiere actualización basado en tu uso reciente.',
            'data' => $data,
            'read_at' => null,
        ]);
    }

    /**
     * Handle plan upgrade
     */
    public function handlePlanUpgrade(Tenant $tenant, SubscriptionPlan $newPlan): void
    {
        // Update subscription
        $subscription = $tenant->subscription;
        if ($subscription) {
            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
                'price' => $newPlan->price_monthly,
                'currency' => $newPlan->currency,
                // Update other subscription fields as needed
            ]);
        }

        // Update current usage limits
        $currentUsage = TenantUsage::getCurrentUsage($tenant->id);
        if ($currentUsage) {
            $currentUsage->update([
                'max_sales_per_month' => $newPlan->max_sales_per_month,
                'max_products' => $newPlan->max_products,
                'max_users' => $newPlan->max_users,
                'max_storage_mb' => $newPlan->max_storage_mb,
            ]);
            $currentUsage->calculatePercentages();
        }

        // Clear cache
        $this->usageService->clearCache($tenant->id);

        // Log upgrade
        logger()->info('Plan upgraded', [
            'tenant_id' => $tenant->id,
            'old_plan' => $subscription->plan->name ?? 'Unknown',
            'new_plan' => $newPlan->name,
        ]);

        // Send confirmation
        $this->sendUpgradeConfirmation($tenant, $newPlan);
    }

    /**
     * Send upgrade confirmation
     */
    private function sendUpgradeConfirmation(Tenant $tenant, SubscriptionPlan $newPlan): void
    {
        \Mail::to($tenant->owner?->email)->send(
            new PlanUpgradeConfirmationMail($tenant, $newPlan)
        );
    }

    /**
     * Check for tenants needing billing intervention
     */
    public function getTenantsNeedingBillingAction(): array
    {
        return [
            'immediate_attention' => TenantUsage::critical()
                ->with('tenant')
                ->get()
                ->map(function ($usage) {
                    return [
                        'tenant_id' => $usage->tenant_id,
                        'tenant_name' => $usage->tenant->name,
                        'status' => $usage->status,
                        'highest_percentage' => max([
                            $usage->sales_percentage,
                            $usage->products_percentage,
                            $usage->users_percentage,
                            $usage->storage_percentage,
                        ]),
                    ];
                })->toArray(),

            'upgrade_opportunities' => TenantUsage::needsUpgrade()
                ->with('tenant')
                ->get()
                ->map(function ($usage) {
                    return [
                        'tenant_id' => $usage->tenant_id,
                        'tenant_name' => $usage->tenant->name,
                        'recommended_plan' => $this->getRecommendedPlan($usage->tenant, $usage)?->name,
                    ];
                })->toArray(),
        ];
    }
}
