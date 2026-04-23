<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources\SaleReturnResource\Pages;

use App\Modules\POS\Resources\SaleReturnResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSaleReturns extends ListRecords
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No permitimos crear devoluciones desde aquí
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => \App\Modules\POS\Models\SaleReturn::count()),

            'hoy' => Tab::make('Hoy')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => \App\Modules\POS\Models\SaleReturn::whereDate('created_at', today())->count())
                ->badgeColor('warning'),

            'completadas' => Tab::make('Completadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => \App\Modules\POS\Models\SaleReturn::where('status', 'completed')->count())
                ->badgeColor('success'),

            'completas' => Tab::make('Devoluciones Completas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('return_type', 'full'))
                ->badge(fn () => \App\Modules\POS\Models\SaleReturn::where('return_type', 'full')->count())
                ->badgeColor('warning'),
        ];
    }
}
