<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Reporting\Services;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InventoryReportService
{
    /**
     * Calculate total current inventory value
     *
     * @return array
     */
    public function calculateTotalInventoryValue(): array
    {
        $totalValue = Product::sum(DB::raw('stock * price'));
        $totalCost = Product::sum(DB::raw('stock * cost_price'));
        $totalUnits = Product::sum('stock');
        $productCount = Product::count();
        $activeProducts = Product::where('stock', '>', 0)->count();

        // Calculate 7-day trend
        $sevenDaysAgo = now()->subDays(7);
        $lastWeekValue = $this->getHistoricalInventoryValue($sevenDaysAgo);
        $valueTrend = $lastWeekValue > 0
            ? (($totalValue - $lastWeekValue) / $lastWeekValue) * 100
            : 0;

        return [
            'total_value' => $totalValue,
            'total_cost' => $totalCost,
            'potential_profit' => $totalValue - $totalCost,
            'profit_margin' => $totalValue > 0 ? (($totalValue - $totalCost) / $totalValue) * 100 : 0,
            'total_units' => $totalUnits,
            'product_count' => $productCount,
            'active_products' => $activeProducts,
            'inactive_products' => $productCount - $activeProducts,
            'average_value_per_product' => $productCount > 0 ? $totalValue / $productCount : 0,
            'trend_7_days' => $valueTrend,
            'last_week_value' => $lastWeekValue,
        ];
    }

    /**
     * Get inventory value by category
     *
     * @return Collection
     */
    public function getInventoryValueByCategory(): Collection
    {
        return Product::select(
                'categories.id',
                'categories.name as category_name',
                DB::raw('COUNT(products.id) as product_count'),
                DB::raw('SUM(products.stock) as total_units'),
                DB::raw('SUM(products.stock * products.price) as total_value'),
                DB::raw('SUM(products.stock * products.cost_price) as total_cost')
            )
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.stock', '>', 0)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_value')
            ->get()
            ->map(function ($item) {
                $item->potential_profit = $item->total_value - $item->total_cost;
                $item->profit_margin = $item->total_value > 0
                    ? (($item->total_value - $item->total_cost) / $item->total_value) * 100
                    : 0;
                $item->average_value_per_product = $item->product_count > 0
                    ? $item->total_value / $item->product_count
                    : 0;
                return $item;
            });
    }

    /**
     * Get inventory value trend for last N days
     *
     * @param int $days
     * @return array
     */
    public function getInventoryValueTrend(int $days = 30): array
    {
        $values = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $values[] = round($this->getHistoricalInventoryValue($date), 2);
            $labels[] = $date->format('d/m');
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Compare inventory between two periods
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function compareInventoryPeriods(Carbon $startDate, Carbon $endDate): array
    {
        $startValue = $this->getHistoricalInventoryValue($startDate);
        $endValue = $this->getHistoricalInventoryValue($endDate);

        $difference = $endValue - $startValue;
        $percentageChange = $startValue > 0
            ? ($difference / $startValue) * 100
            : 0;

        // Get movements in period
        $movements = StockMovement::whereBetween('created_at', [$startDate, $endDate])->get();
        $entries = $movements->where('type', 'entrada')->sum('quantity');
        $exits = $movements->where('type', 'salida')->sum('quantity');
        $totalMovements = $movements->count();

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_value' => $startValue,
            'end_value' => $endValue,
            'difference' => $difference,
            'percentage_change' => $percentageChange,
            'total_movements' => $totalMovements,
            'total_entries' => $entries,
            'total_exits' => $exits,
            'net_movement' => $entries - $exits,
        ];
    }

    /**
     * Get historical inventory value at a specific date
     *
     * @param Carbon $date
     * @return float
     */
    protected function getHistoricalInventoryValue(Carbon $date): float
    {
        // Get movements after the date
        $movementsAfterDate = StockMovement::where('created_at', '>', $date)
            ->with('product')
            ->get();

        $currentValue = Product::sum(DB::raw('stock * price'));

        // Calculate difference by movements
        $valueDifference = 0;
        foreach ($movementsAfterDate as $movement) {
            if ($movement->product) {
                $impact = $movement->quantity * $movement->product->price;
                $valueDifference += ($movement->type === 'entrada' ? $impact : -$impact);
            }
        }

        return max(0, $currentValue - $valueDifference);
    }

    /**
     * Get products with highest value
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopValueProducts(int $limit = 10): Collection
    {
        return Product::select(
                'id',
                'name',
                'sku',
                'stock',
                'price',
                'cost_price',
                DB::raw('stock * price as total_value'),
                DB::raw('stock * cost_price as total_cost')
            )
            ->where('stock', '>', 0)
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                $product->potential_profit = $product->total_value - $product->total_cost;
                $product->profit_margin = $product->total_value > 0
                    ? (($product->total_value - $product->total_cost) / $product->total_value) * 100
                    : 0;
                return $product;
            });
    }
}
