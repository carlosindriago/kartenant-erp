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
use Illuminate\Database\Eloquent\Builder;

/**
 * TopProductsWidget: Widget de Top Productos con Insights
 * 
 * NO solo muestra productos más vendidos.
 * Incluye insights accionables:
 * - Margen de ganancia (con alertas si es bajo)
 * - Stock actual y alerta si está bajo
 * - Sugerencias de precio si el margen es muy bajo
 * - Link directo para editar
 */
class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'lg' => 4,  // Full width for better table readability
    ];

    protected static ?string $heading = '🏆 Top 5 Productos del Mes';
    
    protected static ?string $description = 'Los más vendidos con insights de rentabilidad';
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTopProductsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        default => 'gray',
                    })
                    ->size('sm'),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->formatStateUsing(function ($record) {
                        $name = $record->name;
                        
                        // Agregar emoji según stock
                        if ($record->stock <= 0) {
                            return "⚠️ {$name}";
                        } elseif ($record->stock <= $record->min_stock) {
                            return "🟡 {$name}";
                        }
                        
                        return $name;
                    }),
                
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Vendidas')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' un.')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Ingresos')
                    ->money('ARS')
                    ->alignRight()
                    ->weight('bold')
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('margin_percent')
                    ->label('Margen')
                    ->formatStateUsing(function ($record) {
                        if (!$record->cost_price || $record->cost_price <= 0) {
                            return 'N/D';
                        }
                        
                        $margin = (($record->price - $record->cost_price) / $record->price) * 100;
                        return round($margin, 1) . '%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->cost_price || $record->cost_price <= 0) return 'gray';
                        
                        $margin = (($record->price - $record->cost_price) / $record->price) * 100;
                        
                        if ($margin >= 25) return 'success';
                        if ($margin >= 15) return 'warning';
                        return 'danger';
                    })
                    ->icon(function ($record) {
                        if (!$record->cost_price || $record->cost_price <= 0) return null;
                        
                        $margin = (($record->price - $record->cost_price) / $record->price) * 100;
                        
                        if ($margin >= 25) return 'heroicon-o-arrow-trending-up';
                        if ($margin >= 15) return 'heroicon-o-minus';
                        return 'heroicon-o-arrow-trending-down';
                    })
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->badge()
                    ->color(function ($record) {
                        if ($record->stock <= 0) return 'danger';
                        if ($record->stock <= $record->min_stock) return 'warning';
                        return 'success';
                    })
                    ->icon(function ($record) {
                        if ($record->stock <= 0) return 'heroicon-o-x-circle';
                        if ($record->stock <= $record->min_stock) return 'heroicon-o-exclamation-triangle';
                        return 'heroicon-o-check-circle';
                    })
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Modules\ProductResource::getUrl('view', ['record' => $record]))
                    ->color('info'),
                
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => \App\Modules\ProductResource::getUrl('edit', ['record' => $record]))
                    ->color('warning'),
            ])
            ->paginated(false);
    }
    
    protected function getTopProductsQuery(): Builder
    {
        return Product::query()
            ->select([
                'products.*',
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(sale_items.quantity * sale_items.unit_price), 0) as total_revenue'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(sale_items.quantity), 0) DESC) as position')
            ])
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', function ($join) {
                $join->on('sale_items.sale_id', '=', 'sales.id')
                    ->where('sales.status', '=', 'completed')
                    ->where('sales.created_at', '>=', now()->startOfMonth());
            })
            ->where('products.status', true)
            ->groupBy('products.id')
            ->havingRaw('COALESCE(SUM(sale_items.quantity), 0) > 0')
            ->orderByRaw('COALESCE(SUM(sale_items.quantity), 0) DESC')
            ->limit(5);
    }
}
