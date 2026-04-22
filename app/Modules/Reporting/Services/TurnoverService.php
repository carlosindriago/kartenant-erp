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
use App\Modules\POS\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TurnoverService
{
    /**
     * Calculate inventory turnover rate for a product
     *
     * Turnover Rate = Units Sold / Average Units in Stock
     * Days Sales of Inventory (DSI) = (Average Stock / Units Sold) * Days
     *
     * @param int $productId
     * @param int $days
     * @return array
     */
    public function calculateTurnoverRate(int $productId, int $days = 30): array
    {
        $product = Product::find($productId);

        if (!$product) {
            return [
                'error' => 'Product not found',
            ];
        }

        $startDate = now()->subDays($days);

        // Get units sold in period
        $unitsSold = SaleItem::where('product_id', $productId)
            ->whereHas('sale', function ($query) use ($startDate) {
                $query->where('status', 'completed')
                    ->where('created_at', '>=', $startDate);
            })
            ->sum('quantity');

        // Get stock history
        $movements = StockMovement::where('product_id', $productId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        // Calculate average stock
        $currentStock = $product->stock;
        $startingStock = $currentStock;

        // Reconstruct starting stock by reversing movements
        foreach ($movements as $movement) {
            if ($movement->type === 'entrada') {
                $startingStock -= $movement->quantity;
            } else {
                $startingStock += $movement->quantity;
            }
        }

        $startingStock = max(0, $startingStock);
        $averageStock = ($startingStock + $currentStock) / 2;

        // Calculate turnover rate
        $turnoverRate = $averageStock > 0 ? $unitsSold / $averageStock : 0;

        // Calculate days sales of inventory (DSI)
        $daysInPeriod = $days;
        $dsi = $unitsSold > 0 ? ($averageStock / $unitsSold) * $daysInPeriod : 0;

        // Annualized turnover rate
        $annualizedTurnover = ($days > 0) ? ($turnoverRate / $days) * 365 : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'period_days' => $days,
            'starting_stock' => $startingStock,
            'ending_stock' => $currentStock,
            'average_stock' => round($averageStock, 2),
            'units_sold' => $unitsSold,
            'turnover_rate' => round($turnoverRate, 2),
            'days_sales_of_inventory' => round($dsi, 1),
            'annualized_turnover' => round($annualizedTurnover, 2),
            'stock_status' => $this->getStockStatus($dsi, $unitsSold),
            'recommendation' => $this->getRecommendation($dsi, $unitsSold, $currentStock),
        ];
    }

    /**
     * Get slow-moving products (low turnover)
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getSlowMovingProducts(int $days = 90, int $limit = 20): Collection
    {
        $products = Product::where('stock', '>', 0)->get();

        $slowMovers = $products->map(function ($product) use ($days) {
            $turnover = $this->calculateTurnoverRate($product->id, $days);
            return [
                'product' => $product,
                'turnover_data' => $turnover,
            ];
        })
        ->filter(function ($item) {
            return isset($item['turnover_data']['units_sold']) && $item['turnover_data']['units_sold'] >= 0;
        })
        ->sortBy(function ($item) {
            return $item['turnover_data']['turnover_rate'];
        })
        ->take($limit)
        ->values();

        return $slowMovers;
    }

    /**
     * Get fast-moving products (high turnover)
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getFastMovingProducts(int $days = 30, int $limit = 20): Collection
    {
        $products = Product::where('stock', '>', 0)->get();

        $fastMovers = $products->map(function ($product) use ($days) {
            $turnover = $this->calculateTurnoverRate($product->id, $days);
            return [
                'product' => $product,
                'turnover_data' => $turnover,
            ];
        })
        ->filter(function ($item) {
            return isset($item['turnover_data']['units_sold'])
                && $item['turnover_data']['units_sold'] > 0
                && $item['turnover_data']['turnover_rate'] > 0;
        })
        ->sortByDesc(function ($item) {
            return $item['turnover_data']['turnover_rate'];
        })
        ->take($limit)
        ->values();

        return $fastMovers;
    }

    /**
     * Get days sales of inventory for a product
     *
     * @param int $productId
     * @return float
     */
    public function getDaysSalesOfInventory(int $productId): float
    {
        $turnover = $this->calculateTurnoverRate($productId, 30);
        return $turnover['days_sales_of_inventory'] ?? 0;
    }

    /**
     * Get turnover summary for all products
     *
     * @param int $days
     * @return array
     */
    public function getTurnoverSummary(int $days = 30): array
    {
        $products = Product::where('stock', '>', 0)->get();

        $turnovers = $products->map(function ($product) use ($days) {
            return $this->calculateTurnoverRate($product->id, $days);
        })->filter(function ($turnover) {
            return !isset($turnover['error']);
        });

        $fastMovers = $turnovers->where('turnover_rate', '>=', 2)->count();
        $normalMovers = $turnovers->whereBetween('turnover_rate', [0.5, 2])->count();
        $slowMovers = $turnovers->where('turnover_rate', '<', 0.5)->count();
        $stagnant = $turnovers->where('units_sold', 0)->count();

        return [
            'period_days' => $days,
            'total_products_analyzed' => $turnovers->count(),
            'fast_movers' => $fastMovers,
            'normal_movers' => $normalMovers,
            'slow_movers' => $slowMovers,
            'stagnant' => $stagnant,
            'average_turnover_rate' => round($turnovers->avg('turnover_rate'), 2),
            'average_dsi' => round($turnovers->avg('days_sales_of_inventory'), 1),
            'total_units_sold' => $turnovers->sum('units_sold'),
            'products_needing_attention' => $slowMovers + $stagnant,
        ];
    }

    /**
     * Get turnover comparison between periods
     *
     * @param int $period1Days
     * @param int $period2Days
     * @return array
     */
    public function compareTurnoverPeriods(int $period1Days = 30, int $period2Days = 90): array
    {
        $summary1 = $this->getTurnoverSummary($period1Days);
        $summary2 = $this->getTurnoverSummary($period2Days);

        $turnoverChange = $summary2['average_turnover_rate'] > 0
            ? (($summary1['average_turnover_rate'] - $summary2['average_turnover_rate']) / $summary2['average_turnover_rate']) * 100
            : 0;

        return [
            'period_1' => $summary1,
            'period_2' => $summary2,
            'turnover_change_percentage' => round($turnoverChange, 2),
            'trend' => $turnoverChange > 0 ? 'improving' : ($turnoverChange < 0 ? 'declining' : 'stable'),
        ];
    }

    /**
     * Get stock status description
     *
     * @param float $dsi
     * @param int $unitsSold
     * @return string
     */
    protected function getStockStatus(float $dsi, int $unitsSold): string
    {
        if ($unitsSold == 0) {
            return 'estancado';
        }

        if ($dsi < 15) {
            return 'rotación_alta';
        } elseif ($dsi < 30) {
            return 'rotación_normal';
        } elseif ($dsi < 60) {
            return 'rotación_lenta';
        } else {
            return 'rotación_muy_lenta';
        }
    }

    /**
     * Get recommendation based on turnover data
     *
     * @param float $dsi
     * @param int $unitsSold
     * @param int $currentStock
     * @return string
     */
    protected function getRecommendation(float $dsi, int $unitsSold, int $currentStock): string
    {
        if ($unitsSold == 0 && $currentStock > 0) {
            return 'Producto estancado. Considerar promociones o descuentos para mover inventario.';
        }

        if ($dsi < 15 && $currentStock < 10) {
            return '¡Producto de alta rotación! Considerar aumentar stock para evitar agotamiento.';
        } elseif ($dsi < 30) {
            return 'Rotación saludable. Mantener niveles de inventario actuales.';
        } elseif ($dsi < 60) {
            return 'Rotación lenta. Evaluar si el nivel de stock es apropiado.';
        } else {
            return 'Rotación muy lenta. Considerar reducir pedidos y promover el producto.';
        }
    }
}
