<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Widgets;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class InventoryTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tendencia del Valor del Inventario';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '60s';

    public ?string $filter = '30';

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $data = $this->getInventoryValueTrend($days);

        return [
            'datasets' => [
                [
                    'label' => 'Valor del Inventario',
                    'data' => $data['values'],
                    'borderColor' => 'rgb(59, 130, 246)', // blue-500
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 días',
            '15' => 'Últimos 15 días',
            '30' => 'Últimos 30 días',
            '60' => 'Últimos 60 días',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + \': $\' + context.parsed.y.toFixed(2).replace(/\\d(?=(\\d{3})+\\.)/g, \'$&,\');
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return \'$\' + value.toFixed(0).replace(/\\d(?=(\\d{3})+$)/g, \'$&,\');
                        }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    protected function getInventoryValueTrend(int $days): array
    {
        $values = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $value = $this->getHistoricalInventoryValue($date);

            $values[] = round($value, 2);
            $labels[] = $date->format('d/m');
        }

        return [
            'values' => $values,
            'labels' => $labels,
        ];
    }

    protected function getHistoricalInventoryValue($date): float
    {
        // Obtener el valor aproximado del inventario en una fecha pasada
        $movementsAfterDate = StockMovement::where('created_at', '>', $date)
            ->with('product')
            ->get();

        $currentValue = Product::sum(DB::raw('stock * price'));

        // Calcular diferencia por movimientos
        $valueDifference = 0;
        foreach ($movementsAfterDate as $movement) {
            if ($movement->product) {
                $impact = $movement->quantity * $movement->product->price;
                $valueDifference += ($movement->type === 'entrada' ? $impact : -$impact);
            }
        }

        return max(0, $currentValue - $valueDifference);
    }
}
