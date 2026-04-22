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
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Datos actuales
        $totalProducts = Product::count();
        $totalValue = Product::sum(DB::raw('stock * price'));
        $lowStockCount = Product::whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count();
        $outOfStockCount = Product::where('stock', '<=', 0)->count();
        $activeProducts = Product::where('stock', '>', 0)->count();
        
        // Datos históricos (hace 7 días)
        $sevenDaysAgo = now()->subDays(7);
        $lastWeekValue = $this->getHistoricalInventoryValue($sevenDaysAgo);
        
        // Movimientos de hoy
        $todayMovements = StockMovement::whereDate('created_at', today())->count();
        $todayEntries = StockMovement::whereDate('created_at', today())->where('type', 'entrada')->sum('quantity');
        $todayExits = StockMovement::whereDate('created_at', today())->where('type', 'salida')->sum('quantity');
        
        // Calcular tendencia del valor
        $valueTrend = $lastWeekValue > 0 
            ? (($totalValue - $lastWeekValue) / $lastWeekValue) * 100 
            : 0;
        
        // Mini-gráfico de últimos 7 días (valor del inventario)
        $last7DaysValues = $this->getLast7DaysInventoryValues();

        return [
            Stat::make('Valor del Inventario', '$' . number_format($totalValue, 2))
                ->description($this->getValueTrendDescription($valueTrend))
                ->descriptionIcon($valueTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($last7DaysValues)
                ->color($valueTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Productos Activos', $activeProducts . ' / ' . $totalProducts)
                ->description('Productos con stock disponible')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-help',
                ]),

            Stat::make('Movimientos Hoy', $todayMovements)
                ->description($this->getTodayMovementsDescription($todayEntries, $todayExits))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Alertas de Stock', $lowStockCount + $outOfStockCount)
                ->description($this->getAlertsDescription($lowStockCount, $outOfStockCount))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outOfStockCount > 0 ? 'danger' : ($lowStockCount > 0 ? 'warning' : 'success')),
        ];
    }
    
    protected function getHistoricalInventoryValue($date): float
    {
        // Obtener el valor aproximado del inventario en una fecha pasada
        // Esta es una aproximación basada en el stock actual menos los movimientos posteriores
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
    
    protected function getLast7DaysInventoryValues(): array
    {
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $values[] = $this->getHistoricalInventoryValue($date);
        }
        return $values;
    }
    
    protected function getValueTrendDescription(float $trend): string
    {
        if ($trend == 0) {
            return 'Sin cambios vs. semana anterior';
        }
        
        $percentage = abs(round($trend, 1));
        $direction = $trend > 0 ? 'Aumentó' : 'Disminuyó';
        
        return "{$direction} {$percentage}% vs. semana anterior";
    }
    
    protected function getTodayMovementsDescription(int $entries, int $exits): string
    {
        if ($entries > 0 && $exits > 0) {
            return "+{$entries} entradas, -{$exits} salidas";
        } elseif ($entries > 0) {
            return "+{$entries} entradas registradas";
        } elseif ($exits > 0) {
            return "-{$exits} salidas registradas";
        }
        
        return 'Sin movimientos registrados hoy';
    }
    
    protected function getAlertsDescription(int $low, int $out): string
    {
        if ($out > 0 && $low > 0) {
            return "{$out} sin stock, {$low} stock bajo";
        } elseif ($out > 0) {
            return "{$out} productos sin stock";
        } elseif ($low > 0) {
            return "{$low} productos con stock bajo";
        }
        
        return '¡Todo el inventario está bien! 🎉';
    }
}
