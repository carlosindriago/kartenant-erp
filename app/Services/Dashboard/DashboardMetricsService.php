<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services\Dashboard;

use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\Customer;
use App\Modules\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardMetricsService: Servicio de Métricas del Dashboard
 * 
 * Calcula todas las métricas del dashboard con contexto y comparaciones.
 * Todas las métricas incluyen:
 * - Valor actual
 * - Comparación con período anterior
 * - Emoji y color según performance
 * - Descripción contextual
 */
class DashboardMetricsService
{
    /**
     * Obtener ventas del día con contexto
     */
    public function getTodaySales(): array
    {
        return Cache::remember('metrics.today_sales', 300, function () {
            $today = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('total');
            
            $yesterday = Sale::whereDate('created_at', today()->subDay())
                ->where('status', 'completed')
                ->sum('total');
            
            $change = $yesterday > 0 ? (($today - $yesterday) / $yesterday) * 100 : 0;
            
            return [
                'value' => $today,
                'formatted' => '$' . number_format($today, 2),
                'change' => $change,
                'change_formatted' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
                'description' => $this->getDescription($change, 'vs ayer'),
                'color' => $this->getColor($change),
                'icon' => $this->getIcon($change),
                'trend' => $this->getTodayTrend(),
            ];
        });
    }
    
    /**
     * Obtener ventas de la semana con contexto
     */
    public function getWeekSales(): array
    {
        return Cache::remember('metrics.week_sales', 300, function () {
            $thisWeek = Sale::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
                ->where('status', 'completed')
                ->sum('total');
            
            $lastWeek = Sale::whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
                ->where('status', 'completed')
                ->sum('total');
            
            $change = $lastWeek > 0 ? (($thisWeek - $lastWeek) / $lastWeek) * 100 : 0;
            
            return [
                'value' => $thisWeek,
                'formatted' => '$' . number_format($thisWeek, 2),
                'change' => $change,
                'change_formatted' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
                'description' => $this->getDescription($change, 'vs sem. anterior'),
                'color' => $this->getColor($change),
                'icon' => $this->getIcon($change),
                'trend' => $this->getWeekTrend(),
                'daily_breakdown' => $this->getWeeklyBreakdown(),
            ];
        });
    }
    
    /**
     * Obtener ventas del mes con contexto y proyección
     */
    public function getMonthSales(): array
    {
        return Cache::remember('metrics.month_sales', 300, function () {
            $thisMonth = Sale::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->where('status', 'completed')
                ->sum('total');
            
            $lastMonth = Sale::whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->where('status', 'completed')
                ->sum('total');
            
            $change = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;
            
            $daysInMonth = now()->daysInMonth;
            $daysPassed = now()->day;
            $avgDailySales = $daysPassed > 0 ? $thisMonth / $daysPassed : 0;
            $projection = $avgDailySales * $daysInMonth;
            
            return [
                'value' => $thisMonth,
                'formatted' => '$' . number_format($thisMonth, 2),
                'change' => $change,
                'change_formatted' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
                'description' => $this->getDescription($change, 'vs mes anterior'),
                'color' => $this->getColor($change),
                'icon' => $this->getIcon($change),
                'days_passed' => $daysPassed,
                'days_in_month' => $daysInMonth,
                'progress_percent' => round(($daysPassed / $daysInMonth) * 100, 1),
                'projection' => $projection,
                'projection_formatted' => '$' . number_format($projection, 0),
                'projection_vs_last_month' => $lastMonth > 0 ? (($projection - $lastMonth) / $lastMonth) * 100 : 0,
            ];
        });
    }
    
    /**
     * Obtener clientes VIP activos
     */
    public function getVIPCustomers(): array
    {
        return Cache::remember('metrics.vip_customers', 300, function () {
            $vipThreshold = 1000; // >$1000 en últimos 30 días = VIP
            
            // Obtener IDs de clientes VIP usando subquery
            $vipIds = Sale::where('created_at', '>', now()->subDays(30))
                ->where('status', 'completed')
                ->selectRaw('customer_id, SUM(total) as total_spent')
                ->groupBy('customer_id')
                ->havingRaw('SUM(total) > ?', [$vipThreshold])
                ->pluck('customer_id')
                ->toArray();
            
            $vipCount = count($vipIds);
            
            // Obtener IDs de clientes VIP del mes anterior
            $lastMonthVIPIds = Sale::whereBetween('created_at', [
                    now()->subDays(60),
                    now()->subDays(30)
                ])
                ->where('status', 'completed')
                ->selectRaw('customer_id, SUM(total) as total_spent')
                ->groupBy('customer_id')
                ->havingRaw('SUM(total) > ?', [$vipThreshold])
                ->pluck('customer_id')
                ->toArray();
            
            $lastMonthVIP = count($lastMonthVIPIds);
            
            $change = $lastMonthVIP > 0 ? (($vipCount - $lastMonthVIP) / $lastMonthVIP) * 100 : 0;
            
            return [
                'value' => $vipCount,
                'formatted' => $vipCount,
                'change' => $change,
                'change_formatted' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
                'description' => 'Activos últimos 30 días',
                'color' => 'success',
                'icon' => '💎',
            ];
        });
    }
    
    /**
     * Obtener tendencia de ventas por hora (hoy)
     */
    protected function getTodayTrend(): array
    {
        $hourly = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, SUM(total) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();
        
        // Rellenar horas faltantes con 0
        $trend = [];
        for ($h = 0; $h < 24; $h++) {
            $trend[] = $hourly[$h] ?? 0;
        }
        
        return $trend;
    }
    
    /**
     * Obtener tendencia de ventas por día (esta semana)
     */
    protected function getWeekTrend(): array
    {
        $daily = Sale::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])
            ->where('status', 'completed')
            ->selectRaw('created_at::date as date, SUM(total) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();
        
        $trend = [];
        for ($d = 0; $d < 7; $d++) {
            $date = now()->startOfWeek()->addDays($d)->format('Y-m-d');
            $trend[] = $daily[$date] ?? 0;
        }
        
        return $trend;
    }
    
    /**
     * Obtener desglose diario de la semana
     */
    protected function getWeeklyBreakdown(): array
    {
        $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $sales = [];
        
        for ($d = 0; $d < 7; $d++) {
            $date = now()->startOfWeek()->addDays($d);
            $dayName = $days[$d];
            
            $total = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total');
            
            $sales[] = [
                'day' => $dayName,
                'date' => $date->format('d/m'),
                'total' => $total,
                'formatted' => '$' . number_format($total, 0),
                'is_today' => $date->isToday(),
                'is_weekend' => $date->isWeekend(),
            ];
        }
        
        // Identificar mejor y peor día
        $totals = array_column($sales, 'total');
        $nonZeroTotals = array_filter($totals);
        
        // Solo calcular si hay ventas
        if (!empty($nonZeroTotals)) {
            $maxSale = max($totals);
            $minSale = min($nonZeroTotals);
            
            foreach ($sales as &$sale) {
                $sale['is_best'] = $sale['total'] == $maxSale && $maxSale > 0;
                $sale['is_worst'] = $sale['total'] == $minSale && $minSale > 0 && count($nonZeroTotals) > 1;
            }
        }
        
        return $sales;
    }
    
    /**
     * Generar descripción contextual según el cambio porcentual
     */
    protected function getDescription(float $change, string $comparison): string
    {
        $emoji = $this->getIcon($change);
        $text = abs(round($change, 1)) . '% ' . $comparison;
        
        if ($change > 15) {
            return $emoji . ' Excelente! ' . $text;
        } elseif ($change > 5) {
            return $emoji . ' Bien. ' . $text;
        } elseif ($change >= -5) {
            return $emoji . ' Normal. ' . $text;
        } else {
            return $emoji . ' Atención. ' . $text;
        }
    }
    
    /**
     * Obtener color según performance
     */
    protected function getColor(float $change): string
    {
        if ($change > 10) return 'success';
        if ($change > 0) return 'warning';
        if ($change > -10) return 'info';
        return 'danger';
    }
    
    /**
     * Obtener emoji/icono según performance
     */
    protected function getIcon(float $change): string
    {
        if ($change > 10) return '🟢';
        if ($change > 0) return '🟡';
        if ($change > -10) return '🔵';
        return '🔴';
    }
}
