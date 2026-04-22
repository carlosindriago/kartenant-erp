<?php

namespace App\Filament\Tenant\Resources\TenantUsageResource\Widgets;

use App\Models\TenantUsage;
use App\Services\TenantUsageService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenantId = tenant()->id;
        $usageService = app(TenantUsageService::class);
        $usageStatus = $usageService->getUsageStatus($tenantId);
        $currentUsage = TenantUsage::getCurrentUsage($tenantId);

        if (!$currentUsage) {
            return [
                Stat::make('Estado del Uso', 'Sin Datos')
                    ->description('No hay actividad registrada')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        $stats = [];

        // Overall Status
        $stats[] = $this->createStatusStat($usageStatus['status'], $currentUsage);

        // Sales Usage
        $salesInfo = $usageStatus['metrics']['sales'];
        $stats[] = Stat::make('Ventas Mensuales', "{$salesInfo['current']} / {$this->formatLimit($salesInfo['limit'])}")
            ->description($this->getUsageDescription($salesInfo, 'ventas'))
            ->descriptionIcon($this->getUsageIcon($salesInfo['zone']))
            ->color($this->getZoneColor($salesInfo['zone']))
            ->chart([7, 2, 10, 3, 15, 4, 17]) // Sample chart data - would come from actual data
            ->extraAttributes([
                'x-data' => '{ showDetails: false }',
                'class' => 'cursor-pointer',
                '@click' => 'showDetails = !showDetails',
            ]);

        // Products Usage
        $productsInfo = $usageStatus['metrics']['products'];
        $stats[] = Stat::make('Productos', "{$productsInfo['current']} / {$this->formatLimit($productsInfo['limit'])}")
            ->description($this->getUsageDescription($productsInfo, 'productos'))
            ->descriptionIcon($this->getUsageIcon($productsInfo['zone']))
            ->color($this->getZoneColor($productsInfo['zone']))
            ->extraAttributes([
                'x-data' => '{ showDetails: false }',
                'class' => 'cursor-pointer',
                '@click' => 'showDetails = !showDetails',
            ]);

        // Users Usage
        $usersInfo = $usageStatus['metrics']['users'];
        $stats[] = Stat::make('Usuarios Activos', "{$usersInfo['current']} / {$this->formatLimit($usersInfo['limit'])}")
            ->description($this->getUsageDescription($usersInfo, 'usuarios'))
            ->descriptionIcon($this->getUsageIcon($usersInfo['zone']))
            ->color($this->getZoneColor($usersInfo['zone']))
            ->extraAttributes([
                'x-data' => '{ showDetails: false }',
                'class' => 'cursor-pointer',
                '@click' => 'showDetails = !showDetails',
            ]);

        return $stats;
    }

    private function createStatusStat(string $status, TenantUsage $usage): Stat
    {
        $statusConfig = [
            'normal' => [
                'label' => 'Uso Normal',
                'description' => "{$usage->getDaysRemainingInPeriod()} días restantes",
                'icon' => 'heroicon-m-check-circle',
                'color' => 'success',
            ],
            'warning' => [
                'label' => 'Advertencia de Uso',
                'description' => 'Acercándose a los límites del plan',
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => 'warning',
            ],
            'overdraft' => [
                'label' => 'Límites Excedidos',
                'description' => 'Se requiere actualización de plan',
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => 'danger',
            ],
            'critical' => [
                'label' => 'Uso Crítico',
                'description' => 'Funcionalidades limitadas',
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger',
            ],
        ];

        $config = $statusConfig[$status] ?? $statusConfig['normal'];

        return Stat::make('Estado General', $config['label'])
            ->description($config['description'])
            ->descriptionIcon($config['icon'])
            ->color($config['color'])
            ->extraAttributes([
                'class' => $status !== 'normal' ? 'animate-pulse' : '',
            ]);
    }

    private function getUsageDescription(array $metricInfo, string $metricName): string
    {
        $percentage = $metricInfo['percentage'];
        $remaining = $metricInfo['remaining'];

        if ($remaining === PHP_INT_MAX) {
            return "Ilimitado";
        }

        $description = "{$percentage}% utilizado";

        if ($remaining > 0) {
            $description .= " • {$remaining} {$metricName} restantes";
        }

        return $description;
    }

    private function getUsageIcon(string $zone): string
    {
        return match($zone) {
            'normal' => 'heroicon-m-arrow-trending-down',
            'warning' => 'heroicon-m-exclamation-triangle',
            'overdraft' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-information-circle',
        };
    }

    private function getZoneColor(string $zone): string
    {
        return match($zone) {
            'normal' => 'success',
            'warning' => 'warning',
            'overdraft' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    private function formatLimit(?int $limit): string
    {
        return $limit ? number_format($limit) : '∞';
    }

    protected function getColumns(): int
    {
        return 4;
    }
}