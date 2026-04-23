<?php

namespace App\Filament\Tenant\Resources\TenantUsageResource\Widgets;

use App\Models\TenantUsage;
use App\Models\UsageMetricsLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UsageChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Tendencia de Uso - Últimos 7 Días';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'sales';

    protected function getData(): array
    {
        $tenantId = tenant()->id;
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(6);

        $metricType = match ($this->filter) {
            'products' => 'product_created',
            'users' => 'user_created',
            'sales' => 'sale_created',
            'storage' => 'storage_used',
            default => 'sale_created',
        };

        // Get daily usage data
        $data = UsageMetricsLog::where('tenant_id', $tenantId)
            ->where('metric_type', $metricType)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(value) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates with 0
        $labels = [];
        $chartData = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $dayData = $data->where('date', $dateString)->first();

            $labels[] = $date->format('d/m');
            $chartData[] = $dayData ? $dayData->total : 0;
        }

        // Get current period limits for reference
        $currentUsage = TenantUsage::getCurrentUsage($tenantId);
        $limit = 0;

        if ($currentUsage) {
            $limit = match ($this->filter) {
                'products' => $currentUsage->max_products ?? 0,
                'users' => $currentUsage->max_users ?? 0,
                'sales' => $currentUsage->max_sales_per_month ?? 0,
                'storage' => $currentUsage->max_storage_mb ?? 0,
                default => 0,
            };
        }

        return [
            'datasets' => [
                [
                    'label' => $this->getMetricLabel(),
                    'data' => $chartData,
                    'backgroundColor' => $this->getChartColor(),
                    'borderColor' => $this->getChartBorderColor(),
                    'fill' => true,
                    'tension' => 0.3,
                ],
                // Add reference line for daily average (if limit exists)
                ...($limit > 0 ? [[
                    'label' => 'Promedio Diario',
                    'data' => array_fill(0, count($labels), $limit / 30), // Assuming 30-day month
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
                ]] : []),
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'sales' => 'Ventas',
            'products' => 'Productos',
            'users' => 'Usuarios',
            'storage' => 'Almacenamiento (MB)',
        ];
    }

    private function getMetricLabel(): string
    {
        return match ($this->filter) {
            'products' => 'Productos Creados',
            'users' => 'Usuarios Creados',
            'sales' => 'Ventas Realizadas',
            'storage' => 'Almacenamiento (MB)',
            default => 'Uso',
        };
    }

    private function getChartColor(): string
    {
        return match ($this->filter) {
            'products' => 'rgba(59, 130, 246, 0.2)',
            'users' => 'rgba(16, 185, 129, 0.2)',
            'sales' => 'rgba(251, 146, 60, 0.2)',
            'storage' => 'rgba(147, 51, 234, 0.2)',
            default => 'rgba(107, 114, 128, 0.2)',
        };
    }

    private function getChartBorderColor(): string
    {
        return match ($this->filter) {
            'products' => 'rgb(59, 130, 246)',
            'users' => 'rgb(16, 185, 129)',
            'sales' => 'rgb(251, 146, 60)',
            'storage' => 'rgb(147, 51, 234)',
            default => 'rgb(107, 114, 128)',
        };
    }

    public function getHeading(): string
    {
        $metricLabel = $this->getMetricLabel();

        return "Tendencia de {$metricLabel} - Últimos 7 Días";
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => $this->filter === 'storage' ? 1 : 0,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $this->filter === 'storage' ? 'MB' : 'Cantidad',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Fecha',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }
}
