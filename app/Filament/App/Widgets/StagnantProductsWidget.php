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

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Modules\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * StagnantProductsWidget: Widget de Productos Estancados
 * 
 * Identifica productos sin rotación que están atrapando capital.
 * Incluye:
 * - Días sin ventas
 * - Capital atrapado (stock × costo)
 * - Sugerencia de descuento
 * - CTAs para liquidar
 */
class StagnantProductsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full'; // Ancho completo siempre

    protected static ?string $heading = '💀 Productos Estancados';
    
    protected static ?string $description = 'Sin ventas en 60+ días - Capital atrapado';
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getStagnantProductsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->formatStateUsing(fn ($record) => "💀 {$record->name}"),
                
                Tables\Columns\TextColumn::make('days_without_sales')
                    ->label('Días sin venta')
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 90) return 'danger';
                        if ($state >= 60) return 'warning';
                        return 'gray';
                    })
                    ->formatStateUsing(fn ($state) => $state ? "{$state} días" : 'Nunca')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' un.')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('trapped_capital')
                    ->label('Capital Atrapado')
                    ->formatStateUsing(function ($record) {
                        $capital = $record->stock * ($record->cost_price ?? $record->price * 0.7);
                        return '$' . number_format($capital, 0);
                    })
                    ->color('danger')
                    ->weight('bold')
                    ->alignRight(),
                
                Tables\Columns\TextColumn::make('suggested_discount')
                    ->label('Descuento Sugerido')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(function ($record) {
                        $days = $record->days_without_sales;
                        
                        if ($days >= 120) return '30% OFF';
                        if ($days >= 90) return '25% OFF';
                        if ($days >= 60) return '20% OFF';
                        return '15% OFF';
                    })
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('liquidate')
                    ->label('Liquidar')
                    ->icon('heroicon-o-tag')
                    ->color('warning')
                    ->url(fn ($record) => "/app/products/{$record->id}/edit")
                    ->tooltip('Aplicar descuento y promocionar'),
            ])
            ->emptyStateHeading('🎉 ¡Excelente!')
            ->emptyStateDescription('No tienes productos estancados. Tu inventario está rotando bien.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
    
    protected function getStagnantProductsQuery()
    {
        return Product::query()
            ->select([
                'products.*',
                DB::raw('COALESCE(MAX(sales.created_at), products.created_at) as last_sale_date'),
                DB::raw('EXTRACT(DAY FROM NOW() - COALESCE(MAX(sales.created_at), products.created_at)) as days_without_sales')
            ])
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function ($join) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', '=', 'completed');
            })
            ->where('products.status', true)
            ->where('products.stock', '>', 0)
            ->groupBy('products.id')
            ->havingRaw('EXTRACT(DAY FROM NOW() - COALESCE(MAX(sales.created_at), products.created_at)) >= 60')
            ->orderByRaw('EXTRACT(DAY FROM NOW() - COALESCE(MAX(sales.created_at), products.created_at)) DESC');
    }
}
