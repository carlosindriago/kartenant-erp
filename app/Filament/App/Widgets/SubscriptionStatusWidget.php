<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Widgets;

use App\Models\Tenant;
use App\Services\SubscriptionLimitService;
use Filament\Widgets\Widget;

class SubscriptionStatusWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.subscription-status';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return config('app.mode') === 'saas';
    }

    protected static ?int $sort = -10; // Show at top

    public function getData(): array
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return [];
        }

        $limitService = new SubscriptionLimitService($tenant);
        $limits = $limitService->getCachedLimitsStatus();
        $warnings = $limitService->hasWarnings();

        return [
            'subscription' => $limits['subscription'] ?? [],
            'users' => $limits['users'] ?? [],
            'products' => $limits['products'] ?? [],
            'sales' => $limits['sales'] ?? [],
            'plan' => $limits['plan'] ?? [],
            'warnings' => $warnings,
            'hasWarnings' => count($warnings) > 0,
            'needsUpgrade' => $limitService->needsUpgrade(),
            'suggestions' => $limitService->getUpgradeSuggestions(),
        ];
    }

    public static function canView(): bool
    {
        // Only show in tenant panel
        return Tenant::current() !== null;
    }
}
