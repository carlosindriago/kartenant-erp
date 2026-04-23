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

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

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
        return [
            Stat::make('Usuarios Activos Hoy', AnalyticsEvent::getActiveUsersCount('today'))
                ->description('Únicos que han usado la app hoy')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart($this->getUsersChartData()),

            Stat::make('Usuarios Activos Este Mes', AnalyticsEvent::getActiveUsersCount('month'))
                ->description('Únicos este mes')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Tenants Activos Hoy', AnalyticsEvent::getActiveTenantsCount('today'))
                ->description('Tenants con actividad hoy')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning')
                ->chart($this->getTenantsChartData()),

            Stat::make('Total Tenants', Tenant::count())
                ->description($this->getTenantsGrowthDescription())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Total Usuarios', User::count())
                ->description($this->getUsersGrowthDescription())
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Tenants Trial', Tenant::where('is_trial', true)->where('status', 'active')->count())
                ->description(number_format($this->getTrialPercentage(), 1).'% del total')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    protected function getUsersChartData(): array
    {
        $data = AnalyticsEvent::selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as users')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('users')
            ->toArray();

        return array_pad($data, 7, 0);
    }

    protected function getTenantsChartData(): array
    {
        $data = AnalyticsEvent::selectRaw('DATE(created_at) as date, COUNT(DISTINCT tenant_id) as tenants')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('tenants')
            ->toArray();

        return array_pad($data, 7, 0);
    }

    protected function getTenantsGrowthDescription(): string
    {
        $today = Tenant::whereDate('created_at', today())->count();
        $thisWeek = Tenant::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        if ($today > 0) {
            return "+{$today} hoy";
        }

        if ($thisWeek > 0) {
            return "+{$thisWeek} esta semana";
        }

        return 'Sin cambios recientes';
    }

    protected function getUsersGrowthDescription(): string
    {
        $today = User::whereDate('created_at', today())->count();
        $thisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        if ($today > 0) {
            return "+{$today} hoy";
        }

        if ($thisWeek > 0) {
            return "+{$thisWeek} esta semana";
        }

        return 'Sin cambios recientes';
    }

    protected function getTrialPercentage(): float
    {
        $total = Tenant::count();
        if ($total === 0) {
            return 0;
        }

        $trial = Tenant::where('is_trial', true)->where('status', 'active')->count();

        return ($trial / $total) * 100;
    }
}
