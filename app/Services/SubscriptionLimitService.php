<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SubscriptionLimitService
{
    protected ?Tenant $tenant = null;
    protected ?TenantSubscription $subscription = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? tenant();

        if ($this->tenant) {
            $this->subscription = $this->tenant->activeSubscription;
        }
    }

    /**
     * Check if tenant has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription && $this->subscription->isActive();
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->subscription && $this->subscription->isOnTrial();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return !$this->subscription || $this->subscription->isExpired();
    }

    /**
     * Check if subscription is suspended
     */
    public function isSuspended(): bool
    {
        return $this->subscription && $this->subscription->status === 'suspended';
    }

    /**
     * Get the subscription plan
     */
    public function getPlan()
    {
        return $this->subscription?->plan;
    }

    /**
     * Check if a module is enabled for the current plan
     */
    public function hasModule(string $module): bool
    {
        $plan = $this->getPlan();

        if (!$plan) {
            return false; // No plan = no modules
        }

        return $plan->hasModule($module);
    }

    /**
     * Check if users limit is reached
     */
    public function canAddUser(): array
    {
        $plan = $this->getPlan();

        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No hay plan activo'];
        }

        // If unlimited, always allow
        if ($plan->isUnlimited('max_users')) {
            return ['allowed' => true];
        }

        // Count current users for this tenant (landlord DB)
        $currentUsers = DB::connection('landlord')
            ->table('tenant_user')
            ->where('tenant_id', $this->tenant->id)
            ->count();

        $limit = $plan->max_users;
        $allowed = $currentUsers < $limit;

        return [
            'allowed' => $allowed,
            'current' => $currentUsers,
            'limit' => $limit,
            'remaining' => max(0, $limit - $currentUsers),
            'percentage' => $limit > 0 ? round(($currentUsers / $limit) * 100, 1) : 0,
            'reason' => !$allowed ? "Has alcanzado el límite de {$limit} usuarios de tu plan" : null,
        ];
    }

    /**
     * Check if products limit is reached
     */
    public function canAddProduct(): array
    {
        $plan = $this->getPlan();

        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No hay plan activo'];
        }

        // If unlimited, always allow
        if ($plan->isUnlimited('max_products')) {
            return ['allowed' => true];
        }

        // Count current products in tenant DB
        $currentProducts = DB::connection('tenant')
            ->table('products')
            ->count();

        $limit = $plan->max_products;
        $allowed = $currentProducts < $limit;

        return [
            'allowed' => $allowed,
            'current' => $currentProducts,
            'limit' => $limit,
            'remaining' => max(0, $limit - $currentProducts),
            'percentage' => $limit > 0 ? round(($currentProducts / $limit) * 100, 1) : 0,
            'reason' => !$allowed ? "Has alcanzado el límite de {$limit} productos de tu plan" : null,
        ];
    }

    /**
     * Check if sales limit is reached for current month
     */
    public function canAddSale(): array
    {
        $plan = $this->getPlan();

        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No hay plan activo'];
        }

        // If unlimited, always allow
        if ($plan->isUnlimited('max_sales_per_month')) {
            return ['allowed' => true];
        }

        // Count sales for current month in tenant DB
        $currentSales = DB::connection('tenant')
            ->table('sales')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $limit = $plan->max_sales_per_month;
        $allowed = $currentSales < $limit;

        return [
            'allowed' => $allowed,
            'current' => $currentSales,
            'limit' => $limit,
            'remaining' => max(0, $limit - $currentSales),
            'percentage' => $limit > 0 ? round(($currentSales / $limit) * 100, 1) : 0,
            'reason' => !$allowed ? "Has alcanzado el límite de {$limit} ventas/mes de tu plan" : null,
        ];
    }

    /**
     * Get all limits status at once
     */
    public function getAllLimitsStatus(): array
    {
        return [
            'subscription' => [
                'active' => $this->hasActiveSubscription(),
                'on_trial' => $this->isOnTrial(),
                'expired' => $this->isExpired(),
                'suspended' => $this->isSuspended(),
                'days_until_expiration' => $this->subscription?->daysUntilExpiration(),
            ],
            'users' => $this->canAddUser(),
            'products' => $this->canAddProduct(),
            'sales' => $this->canAddSale(),
            'plan' => [
                'name' => $this->getPlan()?->name,
                'billing_cycle' => $this->subscription?->billing_cycle,
            ],
        ];
    }

    /**
     * Check if any limit is near (>80%)
     */
    public function hasWarnings(): array
    {
        $warnings = [];

        // Check subscription expiration
        if ($this->subscription && !$this->isExpired()) {
            $daysUntilExpiration = $this->subscription->daysUntilExpiration();
            if ($daysUntilExpiration !== null && $daysUntilExpiration <= 7) {
                $warnings[] = [
                    'type' => 'expiration',
                    'severity' => $daysUntilExpiration <= 3 ? 'critical' : 'warning',
                    'message' => "Tu suscripción vence en {$daysUntilExpiration} día(s)",
                    'action' => 'Renovar ahora',
                    'action_url' => '/app/subscription/renew',
                ];
            }
        }

        // Check users limit
        $usersStatus = $this->canAddUser();
        if (isset($usersStatus['percentage']) && $usersStatus['percentage'] >= 80) {
            $warnings[] = [
                'type' => 'users',
                'severity' => $usersStatus['percentage'] >= 100 ? 'critical' : 'warning',
                'message' => "Has usado {$usersStatus['current']} de {$usersStatus['limit']} usuarios ({$usersStatus['percentage']}%)",
                'action' => 'Actualizar Plan',
                'action_url' => '/app/subscription/upgrade',
            ];
        }

        // Check products limit
        $productsStatus = $this->canAddProduct();
        if (isset($productsStatus['percentage']) && $productsStatus['percentage'] >= 80) {
            $warnings[] = [
                'type' => 'products',
                'severity' => $productsStatus['percentage'] >= 100 ? 'critical' : 'warning',
                'message' => "Has usado {$productsStatus['current']} de {$productsStatus['limit']} productos ({$productsStatus['percentage']}%)",
                'action' => 'Actualizar Plan',
                'action_url' => '/app/subscription/upgrade',
            ];
        }

        // Check sales limit
        $salesStatus = $this->canAddSale();
        if (isset($salesStatus['percentage']) && $salesStatus['percentage'] >= 80) {
            $warnings[] = [
                'type' => 'sales',
                'severity' => $salesStatus['percentage'] >= 100 ? 'critical' : 'warning',
                'message' => "Has realizado {$salesStatus['current']} de {$salesStatus['limit']} ventas este mes ({$salesStatus['percentage']}%)",
                'action' => 'Actualizar Plan',
                'action_url' => '/app/subscription/upgrade',
            ];
        }

        return $warnings;
    }

    /**
     * Get cached limits status (cache for 5 minutes)
     */
    public function getCachedLimitsStatus(): array
    {
        if (!$this->tenant) {
            return [];
        }

        $cacheKey = "tenant_{$this->tenant->id}_limits_status";

        return Cache::remember($cacheKey, 300, function () {
            return $this->getAllLimitsStatus();
        });
    }

    /**
     * Clear limits cache
     */
    public function clearCache(): void
    {
        if (!$this->tenant) {
            return;
        }

        Cache::forget("tenant_{$this->tenant->id}_limits_status");
    }

    /**
     * Get days until trial ends
     */
    public function daysUntilTrialEnds(): ?int
    {
        if (!$this->isOnTrial()) {
            return null;
        }

        return $this->subscription->trial_ends_at->diffInDays(now(), false);
    }

    /**
     * Check if tenant needs to upgrade (any critical limit reached)
     */
    public function needsUpgrade(): bool
    {
        $warnings = $this->hasWarnings();

        foreach ($warnings as $warning) {
            if ($warning['severity'] === 'critical' && $warning['type'] !== 'expiration') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get upgrade suggestions based on current usage
     */
    public function getUpgradeSuggestions(): array
    {
        $limits = $this->getAllLimitsStatus();
        $suggestions = [];

        // Check users
        if (isset($limits['users']['percentage']) && $limits['users']['percentage'] > 70) {
            $suggestions[] = "Considera un plan con más usuarios ({$limits['users']['current']}/{$limits['users']['limit']} usados)";
        }

        // Check products
        if (isset($limits['products']['percentage']) && $limits['products']['percentage'] > 70) {
            $suggestions[] = "Necesitas más capacidad de productos ({$limits['products']['current']}/{$limits['products']['limit']} usados)";
        }

        // Check sales
        if (isset($limits['sales']['percentage']) && $limits['sales']['percentage'] > 70) {
            $suggestions[] = "Tu volumen de ventas está creciendo ({$limits['sales']['current']}/{$limits['sales']['limit']} este mes)";
        }

        return $suggestions;
    }
}
