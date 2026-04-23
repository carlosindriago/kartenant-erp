<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources\SaleResource\Pages;

use App\Modules\POS\Models\Sale;
use App\Modules\POS\Resources\SaleResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No permitimos crear ventas desde aquí, solo desde el POS
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => Sale::count()),

            'hoy' => Tab::make('Hoy')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => Sale::whereDate('created_at', today())->count())
                ->badgeColor('success'),

            'completadas' => Tab::make('Completadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => Sale::where('status', 'completed')->count())
                ->badgeColor('success'),

            'con_devoluciones' => Tab::make('Con Devoluciones')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('returns'))
                ->badge(fn () => Sale::has('returns')->count())
                ->badgeColor('warning'),
        ];
    }
}
