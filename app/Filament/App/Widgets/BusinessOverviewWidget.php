<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BusinessOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenant = tenant();

        // Obtener estadísticas reales del tenant
        $todaySales = DB::connection('tenant')
            ->table('sales')
            ->whereDate('created_at', today())
            ->sum('total');

        $todayCount = DB::connection('tenant')
            ->table('sales')
            ->whereDate('created_at', today())
            ->count();

        $avgTicket = $todayCount > 0 ? $todaySales / $todayCount : 0;

        $lowStock = DB::connection('tenant')
            ->table('products')
            ->where('stock', '<', DB::raw('min_stock'))
            ->count();

        return [
            Stat::make('💰 Ventas Hoy', '$' . number_format($todaySales, 0, ',', '.'))
                ->description('vs ayer')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-all duration-200 shadow-lg',
                    'style' => 'background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 1rem;',
                ]),

            Stat::make('🛒 Ticket Promedio', '$' . number_format($avgTicket, 0, ',', '.'))
                ->description('promedio del día')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary')
                ->chart([3, 2, 5, 4, 6, 5, 7])
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-all duration-200 shadow-lg',
                    'style' => 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 1rem;',
                ]),

            Stat::make('📦 Productos', $this->getTotalProducts())
                ->description('en inventario')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-all duration-200 shadow-lg',
                    'style' => 'background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 1rem;',
                ]),

            Stat::make('⚠️ Stock Bajo', $lowStock)
                ->description('productos críticos')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-105 transition-all duration-200 shadow-lg',
                    'style' => $lowStock > 0
                        ? 'background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); border-radius: 1rem;'
                        : 'background: linear-gradient(135deg, #f0fdf4 0%, #bbf7d0 100%); border-radius: 1rem;',
                ]),
        ];
    }

    private function getTotalProducts(): int
    {
        return DB::connection('tenant')
            ->table('products')
            ->count();
    }

    protected function getPollingInterval(): ?string
    {
        return '300s'; // Actualizar cada 5 minutos
    }
}