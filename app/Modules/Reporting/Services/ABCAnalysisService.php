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
use App\Modules\POS\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ABCAnalysisService
{
    /**
     * Classify all products into ABC categories based on revenue
     *
     * A: Top 80% of revenue (usually ~20% of products)
     * B: Next 15% of revenue (usually ~30% of products)
     * C: Last 5% of revenue (usually ~50% of products)
     *
     * @param int|null $days Optional: Only consider sales from last N days
     * @return Collection
     */
    public function classifyProducts(?int $days = null): Collection
    {
        // Get products with their revenue
        $query = Product::select(
                'products.id',
                'products.name',
                'products.sku',
                'products.stock',
                'products.price',
                'products.cost_price',
                'categories.name as category_name',
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) as total_sold'),
                DB::raw('COALESCE(SUM(sale_items.total), 0) as total_revenue'),
                DB::raw('COALESCE(SUM(sale_items.quantity * products.cost_price), 0) as total_cost')
            )
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function($join) use ($days) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', '=', 'completed');

                if ($days) {
                    $join->where('sales.created_at', '>=', now()->subDays($days));
                }
            })
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.stock', 'products.price', 'products.cost_price', 'categories.name')
            ->orderByDesc('total_revenue');

        $products = $query->get();

        // Calculate total revenue
        $totalRevenue = $products->sum('total_revenue');

        if ($totalRevenue == 0) {
            // No sales data, return empty classification
            return $products->map(function ($product) {
                $product->abc_class = 'C';
                $product->profit = 0;
                $product->profit_margin = 0;
                $product->revenue_percentage = 0;
                $product->cumulative_percentage = 0;
                return $product;
            });
        }

        // Calculate cumulative percentage and classify
        $cumulative = 0;
        $classified = $products->map(function ($product) use ($totalRevenue, &$cumulative) {
            $product->profit = $product->total_revenue - $product->total_cost;
            $product->profit_margin = $product->total_revenue > 0
                ? (($product->total_revenue - $product->total_cost) / $product->total_revenue) * 100
                : 0;

            $product->revenue_percentage = $totalRevenue > 0
                ? ($product->total_revenue / $totalRevenue) * 100
                : 0;

            $cumulative += $product->revenue_percentage;
            $product->cumulative_percentage = $cumulative;

            // Classify into ABC
            if ($cumulative <= 80) {
                $product->abc_class = 'A';
            } elseif ($cumulative <= 95) {
                $product->abc_class = 'B';
            } else {
                $product->abc_class = 'C';
            }

            return $product;
        });

        return $classified;
    }

    /**
     * Get ABC distribution summary
     *
     * @param int|null $days
     * @return array
     */
    public function getABCDistribution(?int $days = null): array
    {
        $classified = $this->classifyProducts($days);

        $classA = $classified->where('abc_class', 'A');
        $classB = $classified->where('abc_class', 'B');
        $classC = $classified->where('abc_class', 'C');

        $total = $classified->count();

        return [
            'class_a' => [
                'count' => $classA->count(),
                'percentage' => $total > 0 ? ($classA->count() / $total) * 100 : 0,
                'total_revenue' => $classA->sum('total_revenue'),
                'revenue_percentage' => $classified->sum('total_revenue') > 0
                    ? ($classA->sum('total_revenue') / $classified->sum('total_revenue')) * 100
                    : 0,
                'products' => $classA->values(),
            ],
            'class_b' => [
                'count' => $classB->count(),
                'percentage' => $total > 0 ? ($classB->count() / $total) * 100 : 0,
                'total_revenue' => $classB->sum('total_revenue'),
                'revenue_percentage' => $classified->sum('total_revenue') > 0
                    ? ($classB->sum('total_revenue') / $classified->sum('total_revenue')) * 100
                    : 0,
                'products' => $classB->values(),
            ],
            'class_c' => [
                'count' => $classC->count(),
                'percentage' => $total > 0 ? ($classC->count() / $total) * 100 : 0,
                'total_revenue' => $classC->sum('total_revenue'),
                'revenue_percentage' => $classified->sum('total_revenue') > 0
                    ? ($classC->sum('total_revenue') / $classified->sum('total_revenue')) * 100
                    : 0,
                'products' => $classC->values(),
            ],
            'total_products' => $total,
            'total_revenue' => $classified->sum('total_revenue'),
            'period_days' => $days,
        ];
    }

    /**
     * Get products by specific ABC class
     *
     * @param string $class A, B, or C
     * @param int|null $days
     * @return Collection
     */
    public function getProductsByClass(string $class, ?int $days = null): Collection
    {
        $classified = $this->classifyProducts($days);
        return $classified->where('abc_class', strtoupper($class))->values();
    }

    /**
     * Get recommendations based on ABC analysis
     *
     * @param int|null $days
     * @return array
     */
    public function getRecommendations(?int $days = null): array
    {
        $distribution = $this->getABCDistribution($days);

        $recommendations = [];

        // Class A recommendations
        $classAProducts = $distribution['class_a']['products'];
        $lowStockA = $classAProducts->where('stock', '<=', DB::raw('min_stock'))->count();

        if ($lowStockA > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'Clase A',
                'message' => "¡URGENTE! {$lowStockA} productos de clase A (alta rotación) tienen stock bajo. Reabastecer inmediatamente.",
                'action' => 'restock_class_a',
            ];
        }

        // Class C recommendations
        $classCProducts = $distribution['class_c']['products'];
        $highStockC = $classCProducts->where('stock', '>', 50)->count();

        if ($highStockC > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Clase C',
                'message' => "{$highStockC} productos de clase C (baja rotación) tienen stock alto. Considerar ofertas o descuentos.",
                'action' => 'reduce_class_c_stock',
            ];
        }

        // Overall recommendations
        if ($distribution['class_a']['count'] < $distribution['total_products'] * 0.1) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'Estrategia',
                'message' => 'Muy pocos productos en clase A. Considerar ampliar el catálogo de productos rentables.',
                'action' => 'expand_catalog',
            ];
        }

        return $recommendations;
    }
}
