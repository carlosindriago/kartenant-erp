<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SubscriptionStatsWidget extends BaseWidget
{
    protected static ?int $sort = -5;

    /**
     * Solo mostrar en panel admin
     */
    public static function canView(): bool
    {
        // Use filament() helper for proper panel context with null checks
        $panel = filament()->getCurrentPanel();
        return $panel && $panel->getId() === 'admin' && filament()->auth()->check();
    }

    protected function getStats(): array
    {
        // Total tenants with active subscriptions
        $activeSubscriptions = TenantSubscription::where('status', 'active')->count();
        $totalTenants = Tenant::count();

        // Trial vs Paid
        $onTrial = TenantSubscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->count();

        $paidSubscriptions = $activeSubscriptions - $onTrial;

        // Revenue estimation (only paid, active subscriptions)
        $monthlyRevenue = TenantSubscription::where('status', 'active')
            ->whereNull('trial_ends_at')
            ->where('billing_cycle', 'monthly')
            ->sum('price');

        $yearlyRevenue = TenantSubscription::where('status', 'active')
            ->whereNull('trial_ends_at')
            ->where('billing_cycle', 'yearly')
            ->sum('price');

        // Convert yearly to monthly for MRR
        $mrr = $monthlyRevenue + ($yearlyRevenue / 12);

        // Churn (cancelled + expired in last 30 days)
        $churnedLast30Days = TenantSubscription::whereIn('status', ['cancelled', 'expired'])
            ->where('updated_at', '>', now()->subDays(30))
            ->count();

        // Most popular plan
        $popularPlan = DB::connection('landlord')
            ->table('tenant_subscriptions')
            ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->select('subscription_plans.name', DB::raw('count(*) as total'))
            ->where('tenant_subscriptions.status', 'active')
            ->groupBy('subscription_plans.name', 'subscription_plans.id')
            ->orderBy('total', 'desc')
            ->first();

        return [
            Stat::make('Suscripciones Activas', $activeSubscriptions . ' / ' . $totalTenants)
                ->description($totalTenants > 0 ? round(($activeSubscriptions / $totalTenants) * 100, 1) . '% de tenants' : 'No hay tenants')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart($this->getSubscriptionTrend()),

            Stat::make('Trial vs Pago', $paidSubscriptions . ' pagando')
                ->description($onTrial . ' en trial')
                ->descriptionIcon('heroicon-o-clock')
                ->color($onTrial > $paidSubscriptions ? 'warning' : 'success'),

            Stat::make('MRR (Ingreso Mensual Recurrente)', '$' . number_format($mrr, 2))
                ->description('ARR: $' . number_format($mrr * 12, 2))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart($this->getRevenueTrend()),

            Stat::make('Plan Más Popular', $popularPlan->name ?? 'N/A')
                ->description(($popularPlan->total ?? 0) . ' suscripciones activas')
                ->descriptionIcon('heroicon-o-star')
                ->color('info'),

            Stat::make('Churn (Últimos 30 días)', $churnedLast30Days)
                ->description($churnedLast30Days > 5 ? 'Revisar retención' : 'Dentro de lo normal')
                ->descriptionIcon($churnedLast30Days > 5 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($churnedLast30Days > 5 ? 'danger' : 'success'),

            Stat::make('Próximos a Vencer (7 días)', $this->getExpiringSoonCount())
                ->description('Requieren renovación')
                ->descriptionIcon('heroicon-o-bell-alert')
                ->color('warning'),
        ];
    }

    protected function getSubscriptionTrend(): array
    {
        // Last 7 days subscription count
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = TenantSubscription::where('status', 'active')
                ->where('created_at', '<=', $date->endOfDay())
                ->count();
            $trend[] = $count;
        }
        return $trend;
    }

    protected function getRevenueTrend(): array
    {
        // Last 7 days MRR
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $monthly = TenantSubscription::where('status', 'active')
                ->whereNull('trial_ends_at')
                ->where('billing_cycle', 'monthly')
                ->where('created_at', '<=', $date->endOfDay())
                ->sum('price');

            $yearly = TenantSubscription::where('status', 'active')
                ->whereNull('trial_ends_at')
                ->where('billing_cycle', 'yearly')
                ->where('created_at', '<=', $date->endOfDay())
                ->sum('price');

            $mrr = $monthly + ($yearly / 12);
            $trend[] = round($mrr, 2);
        }
        return $trend;
    }

    protected function getExpiringSoonCount(): int
    {
        return TenantSubscription::where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();
    }
}
