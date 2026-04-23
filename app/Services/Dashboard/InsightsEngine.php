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

use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Models\Customer;
use Illuminate\Support\Facades\Cache;

/**
 * InsightsEngine: Motor de Análisis Inteligente
 *
 * Genera acciones accionables basadas en el análisis de datos del negocio.
 * Este es el "cerebro" del dashboard premium que identifica problemas
 * y sugiere qué hacer para mejorar el negocio.
 */
class InsightsEngine
{
    /**
     * Generar acciones recomendadas priorizadas
     *
     * @param  int  $limit  Número máximo de acciones a retornar
     * @return array Array de acciones con prioridad, descripción y CTAs
     */
    public function generateTopActions(int $limit = 5): array
    {
        return Cache::remember('insights.top_actions', 300, function () use ($limit) {
            $actions = [];

            // 1. CRÍTICO: Productos sin stock (perdiendo ventas AHORA)
            $actions = array_merge($actions, $this->getOutOfStockActions());

            // 2. CRÍTICO: Stock crítico (< 3 días)
            $actions = array_merge($actions, $this->getCriticalStockActions());

            // 3. ALTO: Productos muertos (capital atrapado)
            $actions = array_merge($actions, $this->getDeadStockActions());

            // 4. ALTO: Clientes VIP en riesgo
            $actions = array_merge($actions, $this->getAtRiskVIPActions());

            // 5. MEDIO: Diferencias de caja sin resolver
            $actions = array_merge($actions, $this->getCashDiscrepancyActions());

            // 6. MEDIO: Oportunidades de precio
            $actions = array_merge($actions, $this->getPricingOpportunities());

            // Ordenar por prioridad
            usort($actions, function ($a, $b) {
                $priority = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

                return ($priority[$a['priority']] ?? 99) <=> ($priority[$b['priority']] ?? 99);
            });

            return array_slice($actions, 0, $limit);
        });
    }

    /**
     * Productos sin stock que se vendieron recientemente
     */
    protected function getOutOfStockActions(): array
    {
        $actions = [];

        $outOfStockProducts = Product::where('stock', '<=', 0)
            ->whereHas('saleItems', function ($query) {
                $query->where('created_at', '>', now()->subDays(7));
            })
            ->with(['saleItems' => function ($query) {
                $query->where('created_at', '>', now()->subDays(30))
                    ->with('sale');
            }])
            ->get();

        foreach ($outOfStockProducts->take(3) as $product) {
            $salesLastMonth = $product->saleItems->count();
            $avgDailySales = $salesLastMonth / 30;
            $lostSalesPerDay = $avgDailySales * $product->price;

            $actions[] = [
                'priority' => 'critical',
                'icon' => '⚠️',
                'title' => "⚡ {$product->name} SIN STOCK",
                'description' => "Se vendió {$salesLastMonth}x en 30 días. Perdiendo ~$".number_format($lostSalesPerDay, 0).'/día',
                'impact' => '$'.number_format($lostSalesPerDay * 7, 0).'/semana en pérdidas',
                'estimated_loss' => $lostSalesPerDay,
                'actions' => [
                    [
                        'label' => 'Ver Producto',
                        'url' => '/app/products/'.$product->id,
                        'type' => 'danger',
                        'icon' => 'heroicon-o-eye',
                    ],
                    [
                        'label' => 'Registrar Entrada',
                        'url' => '/app/stock-movements/entry/create?product='.$product->id,
                        'type' => 'success',
                        'icon' => 'heroicon-o-arrow-down-tray',
                    ],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Productos con stock crítico (próximos a agotarse)
     */
    protected function getCriticalStockActions(): array
    {
        $actions = [];

        // Buscar productos con stock en o por debajo del mínimo
        $criticalProducts = Product::whereRaw('stock > 0 AND stock <= min_stock')
            ->where('status', true)
            ->with(['saleItems' => function ($query) {
                $query->where('created_at', '>', now()->subDays(30))
                    ->with('sale');
            }])
            ->orderBy('stock', 'asc')
            ->get();

        foreach ($criticalProducts->take(10) as $product) {
            $salesLastMonth = $product->saleItems->sum('quantity');
            $avgDailySales = $salesLastMonth > 0 ? $salesLastMonth / 30 : 0;

            // Determinar prioridad basada en el stock actual
            $stockPercentage = ($product->stock / max($product->min_stock, 1)) * 100;

            if ($avgDailySales > 0) {
                // Producto con ventas - calcular días restantes
                $daysLeft = $product->stock / $avgDailySales;
                $suggestedOrder = ceil($avgDailySales * 14); // 2 semanas

                $actions[] = [
                    'priority' => $stockPercentage <= 50 ? 'critical' : 'high',
                    'icon' => $stockPercentage <= 50 ? '🔴' : '🟡',
                    'title' => "⚠️ Stock bajo: {$product->name}",
                    'description' => "Stock: {$product->stock}/{$product->min_stock} un. Duración estimada: ".round($daysLeft, 1).' días',
                    'impact' => "Sugerido: {$suggestedOrder} unidades (14 días de cobertura)",
                    'actions' => [
                        [
                            'label' => 'Ver Producto',
                            'url' => '/app/products/'.$product->id,
                            'type' => $stockPercentage <= 50 ? 'danger' : 'warning',
                            'icon' => 'heroicon-o-eye',
                        ],
                        [
                            'label' => 'Registrar Entrada',
                            'url' => '/app/stock-movements/entry/create?product='.$product->id,
                            'type' => 'success',
                            'icon' => 'heroicon-o-arrow-down-tray',
                        ],
                    ],
                ];
            } else {
                // Producto sin ventas recientes pero con stock bajo
                $suggestedOrder = max($product->min_stock * 2, 10); // Mínimo x2 o al menos 10

                $actions[] = [
                    'priority' => $stockPercentage <= 50 ? 'critical' : 'high',
                    'icon' => $stockPercentage <= 50 ? '🔴' : '🟡',
                    'title' => "⚠️ Stock bajo: {$product->name}",
                    'description' => "Stock: {$product->stock}/{$product->min_stock} un. Sin ventas recientes",
                    'impact' => "Sugerido: Reponer al menos {$suggestedOrder} unidades",
                    'actions' => [
                        [
                            'label' => 'Ver Producto',
                            'url' => '/app/products/'.$product->id,
                            'type' => $stockPercentage <= 50 ? 'danger' : 'warning',
                            'icon' => 'heroicon-o-eye',
                        ],
                        [
                            'label' => 'Registrar Entrada',
                            'url' => '/app/stock-movements/entry/create?product='.$product->id,
                            'type' => 'success',
                            'icon' => 'heroicon-o-arrow-down-tray',
                        ],
                    ],
                ];
            }
        }

        return $actions;
    }

    /**
     * Productos sin rotación (estancados)
     */
    protected function getDeadStockActions(): array
    {
        $actions = [];

        $deadStock = Product::whereDoesntHave('saleItems', function ($query) {
            $query->where('created_at', '>', now()->subDays(60));
        })
            ->where('stock', '>', 0)
            ->where('status', true)
            ->get();

        if ($deadStock->count() >= 5) {
            $totalValue = $deadStock->sum(function ($p) {
                return $p->stock * ($p->cost_price ?? $p->price * 0.7); // Estimar costo si no existe
            });

            $count = $deadStock->count();

            $actions[] = [
                'priority' => 'medium',
                'icon' => '💰',
                'title' => "Liquidar {$count} productos estancados",
                'description' => 'Sin ventas >60 días. Capital atrapado: $'.number_format($totalValue, 0),
                'impact' => 'Liberar espacio y recuperar capital',
                'actions' => [
                    [
                        'label' => 'Ver Lista',
                        'url' => '/app/products?tableFilters[stagnant][value]=1',
                        'type' => 'warning',
                        'icon' => 'heroicon-o-list-bullet',
                    ],
                    [
                        'label' => 'Crear Promoción',
                        'url' => '/app/products',
                        'type' => 'secondary',
                        'icon' => 'heroicon-o-tag',
                    ],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Clientes VIP que no han comprado recientemente
     */
    protected function getAtRiskVIPActions(): array
    {
        $actions = [];

        // Clientes con compras >$2000 en últimos 6 meses pero sin compras en 45 días
        $atRiskVIPs = Customer::whereHas('sales', function ($query) {
            $query->where('created_at', '>', now()->subMonths(6))
                ->where('status', 'completed');
        })
            ->whereDoesntHave('sales', function ($query) {
                $query->where('created_at', '>', now()->subDays(45));
            })
            ->with(['sales' => function ($query) {
                $query->where('status', 'completed')
                    ->latest()
                    ->limit(1);
            }])
            ->get();

        // Filtrar solo VIPs (>$2000 en lifetime)
        $atRiskVIPs = $atRiskVIPs->filter(function ($customer) {
            $lifetime = $customer->sales()->where('status', 'completed')->sum('total');

            return $lifetime > 2000;
        });

        foreach ($atRiskVIPs->take(2) as $customer) {
            $lastSale = $customer->sales->first();
            if (! $lastSale) {
                continue;
            }

            $daysAgo = now()->diffInDays($lastSale->created_at);
            $lifetimeValue = $customer->sales()->where('status', 'completed')->sum('total');

            $actions[] = [
                'priority' => 'high',
                'icon' => '📞',
                'title' => "Contactar a {$customer->name}",
                'description' => "Cliente VIP sin comprar hace {$daysAgo} días",
                'impact' => 'Valor histórico: $'.number_format($lifetimeValue, 0),
                'actions' => [
                    [
                        'label' => 'Enviar WhatsApp',
                        'url' => $customer->phone ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $customer->phone) : '#',
                        'type' => 'success',
                        'icon' => 'heroicon-o-chat-bubble-left-right',
                    ],
                    [
                        'label' => 'Ver Historial',
                        'url' => '/app/customers/'.$customer->id,
                        'type' => 'secondary',
                        'icon' => 'heroicon-o-eye',
                    ],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Diferencias de caja sin resolver
     */
    protected function getCashDiscrepancyActions(): array
    {
        $actions = [];

        // Buscar cierres con diferencias >$50
        $discrepancies = CashRegister::where('status', 'closed')
            ->where('closed_at', '>', now()->subDays(30))
            ->whereRaw('ABS(expected_amount - actual_amount) > 50')
            ->count();

        if ($discrepancies > 2) {
            $totalDiscrepancy = CashRegister::where('status', 'closed')
                ->where('closed_at', '>', now()->subDays(30))
                ->selectRaw('SUM(expected_amount - actual_amount) as total')
                ->value('total');

            $actions[] = [
                'priority' => 'medium',
                'icon' => '💵',
                'title' => "{$discrepancies} cierres con diferencias",
                'description' => 'Diferencias >$50. Total acumulado: $'.number_format(abs($totalDiscrepancy), 0),
                'impact' => 'Revisar procesos de caja',
                'actions' => [
                    [
                        'label' => 'Revisar Cierres',
                        'url' => '/app',
                        'type' => 'warning',
                        'icon' => 'heroicon-o-calculator',
                    ],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Oportunidades de ajuste de precios
     */
    protected function getPricingOpportunities(): array
    {
        $actions = [];

        // Productos con bajo margen (<15%) y ventas altas
        $lowMarginProducts = Product::whereRaw('((price - cost_price) / NULLIF(price, 0)) < 0.15')
            ->whereHas('saleItems', function ($query) {
                $query->where('created_at', '>', now()->subDays(30));
            })
            ->where('cost_price', '>', 0)
            ->where('price', '>', 0)
            ->with(['saleItems' => function ($query) {
                $query->where('created_at', '>', now()->subDays(30));
            }])
            ->get();

        foreach ($lowMarginProducts->take(1) as $product) {
            $currentMargin = (($product->price - $product->cost_price) / $product->price) * 100;
            $suggestedPrice = $product->cost_price * 1.25; // 25% margen
            $increase = (($suggestedPrice - $product->price) / $product->price) * 100;

            if ($increase > 5 && $currentMargin < 15) {
                $salesCount = $product->saleItems->count();

                $actions[] = [
                    'priority' => 'low',
                    'icon' => '💡',
                    'title' => "Optimizar precio de {$product->name}",
                    'description' => 'Margen actual: '.round($currentMargin, 1).'%. Sugerido: $'.number_format($suggestedPrice, 0),
                    'impact' => 'Potencial +$'.number_format(($suggestedPrice - $product->price) * $salesCount, 0).'/mes',
                    'actions' => [
                        [
                            'label' => 'Ver Producto',
                            'url' => '/app/products/'.$product->id,
                            'type' => 'info',
                            'icon' => 'heroicon-o-eye',
                        ],
                    ],
                ];
            }
        }

        return $actions;
    }
}
