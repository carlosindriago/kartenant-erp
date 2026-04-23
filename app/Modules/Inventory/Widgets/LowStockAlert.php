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
use Filament\Widgets\Widget;

class LowStockAlert extends Widget
{
    protected static string $view = 'inventory::widgets.low-stock-alert';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getLowStockProducts()
    {
        return Product::whereColumn('stock', '<=', 'min_stock')
            ->where('stock', '>', 0)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
    }

    public function getOutOfStockProducts()
    {
        return Product::where('stock', '<=', 0)
            ->orderBy('name')
            ->limit(5)
            ->get();
    }
}
