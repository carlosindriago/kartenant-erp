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

use App\Modules\Inventory\Models\StockMovement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Actividad Reciente';
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->size('sm')
                    ->weight('medium'),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        'adjustment' => 'Ajuste',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                        'warning' => 'adjustment',
                    ])
                    ->icon(fn (string $state): string => match ($state) {
                        'entrada' => 'heroicon-m-arrow-down-tray',
                        'salida' => 'heroicon-m-arrow-up-tray',
                        'adjustment' => 'heroicon-m-wrench-screwdriver',
                        default => 'heroicon-m-minus',
                    }),
                
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('semibold')
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold')
                    ->formatStateUsing(function ($record) {
                        $prefix = $record->type === 'entrada' ? '+' : '-';
                        return $prefix . number_format($record->quantity, 0);
                    })
                    ->color(fn ($record) => match ($record->type) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable()
                    ->formatStateUsing(function (StockMovement $record): string {
                        // Mejorar la descripción del motivo basándose en la referencia
                        if ($record->reference) {
                            // Si hay una referencia de venta
                            if (str_starts_with($record->reference, 'FAC-') || str_starts_with($record->reference, '#FAC-')) {
                                return 'Venta';
                            }
                            // Si hay una referencia de devolución
                            if (str_starts_with($record->reference, 'DEV-') || str_contains($record->reason, 'Devolución')) {
                                return 'Devolución de venta';
                            }
                        }
                        
                        // Para ajustes manuales
                        if (str_contains($record->reason, 'Ajuste manual')) {
                            return 'Ajuste manual de inventario';
                        }
                        
                        // Para stock inicial
                        if (str_contains($record->reason, 'Stock inicial') || str_contains($record->reason, 'inicial')) {
                            return 'Stock inicial';
                        }
                        
                        // Retornar el motivo original si no coincide con ningún patrón
                        return $record->reason ?? 'Sin especificar';
                    })
                    ->limit(45)
                    ->tooltip(function (StockMovement $record): ?string {
                        $motivo = $record->reason ?? 'Sin especificar';
                        return strlen($motivo) > 45 ? $motivo : null;
                    }),
                
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->placeholder('—')
                    ->icon('heroicon-m-hashtag')
                    ->formatStateUsing(fn (?string $state): string => $state 
                        ? (strlen($state) > 20 ? substr($state, 0, 20) . '...' : $state)
                        : '—'
                    )
                    ->description(fn (StockMovement $record): ?string => 
                        $record->reference ? 'Click para ver detalle' : null
                    )
                    ->tooltip(function (StockMovement $record): ?string {
                        if (!$record->reference) {
                            return null;
                        }
                        $fullRef = $record->reference;
                        return strlen($fullRef) > 20 
                            ? $fullRef . ' (Click para ver detalle)' 
                            : 'Click para ver detalle de la operación';
                    })
                    ->color(fn (StockMovement $record): string => $record->reference ? 'primary' : 'gray')
                    ->weight(fn (StockMovement $record): string => $record->reference ? 'semibold' : 'normal')
                    ->url(function (StockMovement $record): ?string {
                        if (!$record->reference) {
                            return null;
                        }
                        
                        $tenant = \Filament\Facades\Filament::getTenant();
                        
                        // Si es una venta (FAC-xxx)
                        if (str_starts_with($record->reference, 'FAC-') || str_starts_with($record->reference, '#FAC-')) {
                            $invoiceNumber = str_replace('#', '', $record->reference);
                            $sale = \App\Modules\POS\Models\Sale::where('invoice_number', $invoiceNumber)->first();
                            
                            if ($sale) {
                                return route('filament.app.resources.sales.view', [
                                    'tenant' => $tenant?->domain,
                                    'record' => $sale->id,
                                ]);
                            }
                        }
                        
                        // Si es una devolución (NCR-xxx)
                        if (str_contains($record->reference, 'NCR:')) {
                            // Extraer el número de devolución: "NCR: NCR-20251014-0001 (Venta #FAC-xxx)"
                            preg_match('/NCR:\s*(NCR-\d{8}-\d{4})/', $record->reference, $matches);
                            if (isset($matches[1])) {
                                $returnNumber = $matches[1];
                                $saleReturn = \App\Modules\POS\Models\SaleReturn::where('return_number', $returnNumber)->first();
                                
                                if ($saleReturn) {
                                    return route('filament.app.resources.sale-returns.view', [
                                        'tenant' => $tenant?->domain,
                                        'record' => $saleReturn->id,
                                    ]);
                                }
                            }
                        }
                        
                        return null;
                    })
                    ->openUrlInNewTab(false),
                
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Usuario')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->default('Sistema')
                    ->weight(fn (?string $state): string => $state !== 'Sistema' ? 'semibold' : 'medium')
                    ->size('sm')
                    ->color(fn (?string $state): string => $state === 'Sistema' ? 'gray' : 'primary')
                    ->description(fn (StockMovement $record): ?string => 
                        $record->user_name && $record->user_name !== 'Sistema' 
                            ? 'Click para ver perfil' 
                            : null
                    )
                    ->tooltip(fn (StockMovement $record): ?string => 
                        $record->user_name && $record->user_name !== 'Sistema' 
                            ? 'Click para ver detalles del usuario' 
                            : null
                    )
                    ->url(function (StockMovement $record): ?string {
                        if (!$record->user_name || $record->user_name === 'Sistema') {
                            return null;
                        }
                        
                        $tenant = \Filament\Facades\Filament::getTenant();
                        
                        // Buscar usuario por nombre en el tenant actual
                        $user = \App\Models\User::whereHas('tenants', function ($query) use ($tenant) {
                            $query->where('tenants.id', $tenant->id);
                        })
                        ->where('name', $record->user_name)
                        ->first();
                        
                        if ($user) {
                            return route('filament.app.resources.users.view', [
                                'tenant' => $tenant?->domain,
                                'record' => $user->id,
                            ]);
                        }
                        
                        return null;
                    })
                    ->openUrlInNewTab(false),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s'); // Auto-refresh cada 30 segundos
    }
    
    protected function getTableQuery(): Builder
    {
        return StockMovement::query()
            ->with('product')
            ->latest();
    }
}
