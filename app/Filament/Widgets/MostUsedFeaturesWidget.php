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
use Filament\Widgets\ChartWidget;

class MostUsedFeaturesWidget extends ChartWidget
{
    protected static ?string $heading = 'Features Más Usados';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

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
        $features = AnalyticsEvent::getMostUsedFeatures(10, 'month');

        $labels = [];
        $data = [];

        foreach ($features as $feature) {
            // Clean up the event name for display
            $cleanName = str_replace(['_', '.'], ' ', $feature['event_name']);
            $cleanName = ucwords($cleanName);

            $labels[] = $cleanName;
            $data[] = $feature['usage_count'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Usos',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                        'rgb(20, 184, 166)',
                        'rgb(251, 146, 60)',
                        'rgb(148, 163, 184)',
                        'rgb(100, 116, 139)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
