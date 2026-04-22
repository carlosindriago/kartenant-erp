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

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\Dashboard\DashboardMetricsService;

/**
 * RevenueStatsWidget: Estadísticas de Ventas con Contexto
 * 
 * NO muestra solo números. Cada stat incluye:
 * - Comparación con período anterior
 * - Emoji y color según performance
 * - Descripción contextual (Excelente/Bien/Normal/Atención)
 * - Mini-gráfico de tendencia
 * 
 * Esto es lo que diferencia un dashboard básico de uno premium.
 */
class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $metricsService = new DashboardMetricsService();
        
        // Ventas de HOY
        $today = $metricsService->getTodaySales();
        
        // Ventas de la SEMANA
        $week = $metricsService->getWeekSales();
        
        // Ventas del MES
        $month = $metricsService->getMonthSales();
        
        // Clientes VIP
        $vip = $metricsService->getVIPCustomers();
        
        return [
            // Stat 1: Ventas HOY
            Stat::make('💰 Ventas Hoy', $today['formatted'])
                ->description($today['description'])
                ->descriptionIcon($today['change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($today['color'])
                ->chart($today['trend'])
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-[1.02]',
                ]),
            
            // Stat 2: Ventas SEMANA
            Stat::make('📊 Esta Semana', $week['formatted'])
                ->description($week['description'])
                ->descriptionIcon($week['change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($week['color'])
                ->chart($week['trend'])
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-[1.02]',
                ]),
            
            // Stat 3: Ventas MES con proyección
            Stat::make('🎯 Este Mes', $month['formatted'])
                ->description($month['description'])
                ->descriptionIcon($month['change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($month['color'])
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-[1.02]',
                ]),
            
            // Stat 4: Clientes VIP
            Stat::make('💎 Clientes VIP', $vip['formatted'])
                ->description($vip['description'])
                ->descriptionIcon('heroicon-m-user-group')
                ->color($vip['color'])
                ->url('/app/customers')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all hover:scale-[1.02]',
                ]),
        ];
    }
    
    /**
     * Polling cada 5 minutos para actualizar stats
     */
    protected function getPollingInterval(): ?string
    {
        return '300s'; // 5 minutos
    }
}
