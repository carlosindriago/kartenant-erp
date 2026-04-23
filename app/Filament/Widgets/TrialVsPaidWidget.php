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
use Filament\Widgets\ChartWidget;

class TrialVsPaidWidget extends ChartWidget
{
    protected static ?string $heading = 'Tenants: Trial vs Pago';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    /**
     * Solo mostrar en panel admin
     */
    public static function canView(): bool
    {
        // Use filament() helper for proper panel context with null checks
        $panel = filament()->getCurrentPanel();

        return $panel && $panel->getId() === 'admin' && filament()->auth()->check();
    }

    protected function getData(): array
    {
        $trial = Tenant::where('is_trial', true)->where('status', 'active')->count();
        $paid = Tenant::where('is_trial', false)->where('status', 'active')->count();
        $inactive = Tenant::where('status', 'inactive')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Tenants',
                    'data' => [$trial, $paid, $inactive],
                    'backgroundColor' => [
                        'rgb(245, 158, 11)', // warning - trial
                        'rgb(16, 185, 129)', // success - paid
                        'rgb(107, 114, 128)', // gray - inactive
                    ],
                ],
            ],
            'labels' => ['Trial', 'Pago', 'Inactivos'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
