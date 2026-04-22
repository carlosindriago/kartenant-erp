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
use App\Services\TimeFormattingService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SubscriptionAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.subscription-alerts';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10; // Show at top

    /**
     * Solo mostrar en panel admin
     */
    public static function canView(): bool
    {
        // Use filament() helper for proper panel context with null checks
        $panel = filament()->getCurrentPanel();
        return $panel && $panel->getId() === 'admin' && filament()->auth()->check();
    }

    public function getData(): array
    {
        // Get tenants with subscription issues
        $expiredTenants = Tenant::whereHas('activeSubscription', function ($query) {
            $query->where('status', 'expired')
                ->orWhere(function ($q) {
                    $q->where('status', 'active')
                        ->where('ends_at', '<', now());
                });
        })->with(['activeSubscription.plan'])->get();

        $expiringSoonTenants = Tenant::whereHas('activeSubscription', function ($query) {
            $query->where('status', 'active')
                ->whereBetween('ends_at', [now(), now()->addDays(7)]);
        })->with(['activeSubscription.plan'])->get();

        $suspendedTenants = Tenant::whereHas('activeSubscription', function ($query) {
            $query->where('status', 'suspended');
        })->with(['activeSubscription.plan'])->get();

        $cancelledTenants = Tenant::whereHas('activeSubscription', function ($query) {
            $query->where('status', 'cancelled');
        })->with(['activeSubscription.plan'])->get();

        $noSubscriptionTenants = Tenant::doesntHave('activeSubscription')->get();

        return [
            'expired' => $expiredTenants,
            'expiring_soon' => $expiringSoonTenants,
            'suspended' => $suspendedTenants,
            'cancelled' => $cancelledTenants,
            'no_subscription' => $noSubscriptionTenants,
            'total_issues' => $expiredTenants->count() + $expiringSoonTenants->count() + $suspendedTenants->count(),
            'has_critical_issues' => $expiredTenants->count() > 0 || $suspendedTenants->count() > 0,
        ];
    }
}
