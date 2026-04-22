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

use Filament\Widgets\ChartWidget;
use App\Modules\POS\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * SalesTrendChartWidget: Gráfico de Tendencias de Ventas
 * 
 * Muestra tendencia de ventas con insights automáticos:
 * - Ventas últimos 7 días por defecto
 * - Identifica mejor y peor día
 * - Muestra promedio y tendencia
 * - Colores según performance
 */
class SalesTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'default' => 1,  // Móvil: ancho completo
        'md' => 2,       // Tablet: ancho completo
        'lg' => 4,       // Desktop: ancho completo (mejor visualización del gráfico)
    ];
    
    protected static ?string $heading = '📈 Tendencia de Ventas';
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = '7days';
    
    protected function getData(): array
    {
        $period = $this->filter;
        
        $data = match ($period) {
            '7days' => $this->getLast7DaysData(),
            '30days' => $this->getLast30DaysData(),
            'month' => $this->getCurrentMonthData(),
            default => $this->getLast7DaysData(),
        };
        
        return [
            'datasets' => [
                [
                    'label' => 'Ventas Diarias',
                    'data' => $data['values'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Promedio',
                    'data' => array_fill(0, count($data['values']), $data['average']),
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
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
            '7days' => 'Últimos 7 días',
            '30days' => 'Últimos 30 días',
            'month' => 'Este mes',
        ];
    }
    
    public function getDescription(): ?string
    {
        $period = $this->filter;
        
        $data = match ($period) {
            '7days' => $this->getLast7DaysData(),
            '30days' => $this->getLast30DaysData(),
            'month' => $this->getCurrentMonthData(),
            default => $this->getLast7DaysData(),
        };
        
        $average = $data['average'];
        $trend = $data['trend'];
        $bestDay = $data['best_day'];
        
        $trendEmoji = $trend > 5 ? '📈' : ($trend < -5 ? '📉' : '➡️');
        $trendText = $trend > 0 ? 'creciendo' : ($trend < 0 ? 'decreciendo' : 'estable');
        
        return "{$trendEmoji} Promedio: $" . number_format($average, 0) . "/día · " .
               "Tendencia {$trendText} " . abs(round($trend, 1)) . "% · " .
               "Mejor: {$bestDay}";
    }
    
    protected function getLast7DaysData(): array
    {
        $days = [];
        $labels = [];
        $values = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date;
            $labels[] = $date->isoFormat('ddd D');
            
            $total = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total');
            
            $values[] = round($total, 2);
        }
        
        $average = count($values) > 0 ? array_sum($values) / count($values) : 0;
        
        // Calcular tendencia (primeros 3 días vs últimos 3 días)
        $firstHalf = array_slice($values, 0, 3);
        $secondHalf = array_slice($values, -3);
        $avgFirst = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $avgSecond = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        $trend = $avgFirst > 0 ? (($avgSecond - $avgFirst) / $avgFirst) * 100 : 0;
        
        // Identificar mejor día
        $maxValue = max($values);
        $maxIndex = array_search($maxValue, $values);
        $bestDay = $labels[$maxIndex] ?? 'N/D';
        
        return [
            'labels' => $labels,
            'values' => $values,
            'average' => $average,
            'trend' => $trend,
            'best_day' => $bestDay,
        ];
    }
    
    protected function getLast30DaysData(): array
    {
        $labels = [];
        $values = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            
            $total = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total');
            
            $values[] = round($total, 2);
        }
        
        $average = count($values) > 0 ? array_sum($values) / count($values) : 0;
        
        // Calcular tendencia (primera semana vs última semana)
        $firstWeek = array_slice($values, 0, 7);
        $lastWeek = array_slice($values, -7);
        $avgFirst = count($firstWeek) > 0 ? array_sum($firstWeek) / count($firstWeek) : 0;
        $avgLast = count($lastWeek) > 0 ? array_sum($lastWeek) / count($lastWeek) : 0;
        $trend = $avgFirst > 0 ? (($avgLast - $avgFirst) / $avgFirst) * 100 : 0;
        
        // Identificar mejor día
        $maxValue = max($values);
        $maxIndex = array_search($maxValue, $values);
        $bestDay = $labels[$maxIndex] ?? 'N/D';
        
        return [
            'labels' => $labels,
            'values' => $values,
            'average' => $average,
            'trend' => $trend,
            'best_day' => $bestDay,
        ];
    }
    
    protected function getCurrentMonthData(): array
    {
        $labels = [];
        $values = [];
        
        $daysInMonth = now()->daysInMonth;
        $today = now()->day;
        
        for ($day = 1; $day <= $today; $day++) {
            $date = now()->setDay($day);
            $labels[] = $date->format('d');
            
            $total = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total');
            
            $values[] = round($total, 2);
        }
        
        $average = count($values) > 0 ? array_sum($values) / count($values) : 0;
        
        // Calcular tendencia (primera mitad vs segunda mitad del mes hasta hoy)
        $mid = floor(count($values) / 2);
        $firstHalf = array_slice($values, 0, $mid);
        $secondHalf = array_slice($values, $mid);
        $avgFirst = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $avgSecond = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        $trend = $avgFirst > 0 ? (($avgSecond - $avgFirst) / $avgFirst) * 100 : 0;
        
        // Identificar mejor día
        $maxValue = max($values);
        $maxIndex = array_search($maxValue, $values);
        $bestDay = $labels[$maxIndex] ?? 'N/D';
        
        return [
            'labels' => $labels,
            'values' => $values,
            'average' => $average,
            'trend' => $trend,
            'best_day' => "Día {$bestDay}",
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "$" + context.parsed.y.toFixed(2); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toFixed(0); }',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
