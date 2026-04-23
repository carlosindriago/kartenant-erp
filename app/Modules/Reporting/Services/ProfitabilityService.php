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
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitabilityService
{
    /**
     * Get most profitable products
     *
     * @param  array|null  $dateRange  ['start' => Carbon, 'end' => Carbon]
     */
    public function getMostProfitableProducts(int $limit = 10, ?array $dateRange = null): Collection
    {
        return $this->getProductProfitability($dateRange)
            ->sortByDesc('profit')
            ->take($limit)
            ->values();
    }

    /**
     * Get least profitable products (or those with losses)
     */
    public function getLeastProfitableProducts(int $limit = 10, ?array $dateRange = null): Collection
    {
        return $this->getProductProfitability($dateRange)
            ->sortBy('profit')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate profitability for a specific product
     */
    public function calculateProductProfitability(int $productId, ?array $dateRange = null): array
    {
        $product = Product::find($productId);

        if (! $product) {
            return [
                'error' => 'Product not found',
            ];
        }

        $query = SaleItem::where('sale_items.product_id', $productId)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed');

        if ($dateRange) {
            $query->whereBetween('sales.created_at', [$dateRange['start'], $dateRange['end']]);
        }

        $sales = $query->select(
            DB::raw('COUNT(DISTINCT sales.id) as transaction_count'),
            DB::raw('SUM(sale_items.quantity) as total_sold'),
            DB::raw('SUM(sale_items.total) as total_revenue'),
            DB::raw('AVG(sale_items.unit_price) as average_selling_price')
        )
            ->first();

        $totalCost = ($sales->total_sold ?? 0) * $product->cost_price;
        $profit = ($sales->total_revenue ?? 0) - $totalCost;
        $profitMargin = ($sales->total_revenue ?? 0) > 0
            ? ($profit / $sales->total_revenue) * 100
            : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'current_stock' => $product->stock,
            'cost_price' => $product->cost_price,
            'selling_price' => $product->price,
            'transaction_count' => $sales->transaction_count ?? 0,
            'total_sold' => $sales->total_sold ?? 0,
            'total_revenue' => $sales->total_revenue ?? 0,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_margin' => $profitMargin,
            'average_selling_price' => $sales->average_selling_price ?? 0,
            'roi' => $totalCost > 0 ? ($profit / $totalCost) * 100 : 0,
            'date_range' => $dateRange ? [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ] : 'all_time',
        ];
    }

    /**
     * Get profitability by category
     */
    public function getProfitabilityByCategory(?array $dateRange = null): Collection
    {
        $query = DB::connection('tenant')
            ->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed');

        if ($dateRange) {
            $query->whereBetween('sales.created_at', [$dateRange['start'], $dateRange['end']]);
        }

        return $query->select(
            'categories.id as category_id',
            'categories.name as category_name',
            DB::raw('COUNT(DISTINCT products.id) as product_count'),
            DB::raw('COUNT(DISTINCT sales.id) as transaction_count'),
            DB::raw('SUM(sale_items.quantity) as total_sold'),
            DB::raw('SUM(sale_items.total) as total_revenue'),
            DB::raw('SUM(sale_items.quantity * products.cost_price) as total_cost')
        )
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->map(function ($item) {
                $item->profit = $item->total_revenue - $item->total_cost;
                $item->profit_margin = $item->total_revenue > 0
                    ? (($item->total_revenue - $item->total_cost) / $item->total_revenue) * 100
                    : 0;
                $item->average_revenue_per_product = $item->product_count > 0
                    ? $item->total_revenue / $item->product_count
                    : 0;
                $item->roi = $item->total_cost > 0
                    ? ($item->profit / $item->total_cost) * 100
                    : 0;

                return $item;
            })
            ->sortByDesc('profit')
            ->values();
    }

    /**
     * Get profitability summary for all products
     */
    public function getProfitabilitySummary(?array $dateRange = null): array
    {
        $products = $this->getProductProfitability($dateRange);

        $profitable = $products->where('profit', '>', 0);
        $unprofitable = $products->where('profit', '<=', 0);

        return [
            'total_products_sold' => $products->count(),
            'profitable_products' => $profitable->count(),
            'unprofitable_products' => $unprofitable->count(),
            'total_revenue' => $products->sum('total_revenue'),
            'total_cost' => $products->sum('total_cost'),
            'total_profit' => $products->sum('profit'),
            'overall_margin' => $products->sum('total_revenue') > 0
                ? (($products->sum('profit')) / $products->sum('total_revenue')) * 100
                : 0,
            'average_profit_per_product' => $products->count() > 0
                ? $products->sum('profit') / $products->count()
                : 0,
            'best_performer' => $profitable->sortByDesc('profit')->first(),
            'worst_performer' => $unprofitable->sortBy('profit')->first(),
            'date_range' => $dateRange ? [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ] : 'all_time',
        ];
    }

    /**
     * Get profit trend over time
     */
    public function getProfitTrend(int $days = 30): array
    {
        $labels = [];
        $revenue = [];
        $cost = [];
        $profit = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');

            $daySales = Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->with('items.product')
                ->get();

            $dayRevenue = $daySales->sum('total');
            $dayCost = $daySales->sum(function ($sale) {
                return $sale->items->sum(function ($item) {
                    return $item->quantity * ($item->product->cost_price ?? 0);
                });
            });

            $revenue[] = round($dayRevenue, 2);
            $cost[] = round($dayCost, 2);
            $profit[] = round($dayRevenue - $dayCost, 2);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
        ];
    }

    /**
     * Get product profitability data
     */
    protected function getProductProfitability(?array $dateRange = null): Collection
    {
        $query = Product::select(
            'products.id',
            'products.name',
            'products.sku',
            'products.stock',
            'products.price',
            'products.cost_price',
            'categories.name as category_name',
            DB::raw('COALESCE(COUNT(DISTINCT sales.id), 0) as transaction_count'),
            DB::raw('COALESCE(SUM(sale_items.quantity), 0) as total_sold'),
            DB::raw('COALESCE(SUM(sale_items.total), 0) as total_revenue'),
            DB::raw('COALESCE(AVG(sale_items.unit_price), 0) as average_selling_price')
        )
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function ($join) use ($dateRange) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', '=', 'completed');

                if ($dateRange) {
                    $join->whereBetween('sales.created_at', [$dateRange['start'], $dateRange['end']]);
                }
            })
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock', 'products.price', 'products.cost_price', 'categories.name');

        return $query->get()->map(function ($product) {
            $totalCost = $product->total_sold * $product->cost_price;
            $profit = $product->total_revenue - $totalCost;
            $profitMargin = $product->total_revenue > 0
                ? ($profit / $product->total_revenue) * 100
                : 0;

            $product->total_cost = $totalCost;
            $product->profit = $profit;
            $product->profit_margin = $profitMargin;
            $product->roi = $totalCost > 0 ? ($profit / $totalCost) * 100 : 0;

            return $product;
        });
    }
}
