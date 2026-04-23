<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources\StockMovementResource\Pages;

use App\Modules\Inventory\Resources\StockMovementResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Movimiento')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha y Hora')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('type')
                                    ->label('Tipo de Movimiento')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'entrada' => 'success',
                                        'salida' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => \App\Modules\Inventory\Models\StockMovement::TYPES[$state] ?? $state
                                    ),

                                Infolists\Components\TextEntry::make('user_name')
                                    ->label('Registrado por')
                                    ->icon('heroicon-o-user'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detalles del Producto')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Producto')
                                    ->icon('heroicon-o-cube')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('product.sku')
                                    ->label('SKU')
                                    ->placeholder('Sin SKU'),

                                Infolists\Components\TextEntry::make('product.category.name')
                                    ->label('Categoría')
                                    ->placeholder('Sin categoría'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Movimiento de Stock')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('previous_stock')
                                    ->label('Stock Anterior')
                                    ->numeric()
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Cantidad del Movimiento')
                                    ->badge()
                                    ->color(fn ($record): string => $record->type === 'entrada' ? 'success' : 'danger')
                                    ->formatStateUsing(fn ($record): string => $record->type === 'entrada' ? "+{$record->quantity}" : "-{$record->quantity}"
                                    )
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('new_stock')
                                    ->label('Stock Nuevo')
                                    ->numeric()
                                    ->badge()
                                    ->color('info')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Información Adicional')
                    ->schema([
                        Infolists\Components\TextEntry::make('reason')
                            ->label('Motivo')
                            ->columnSpanFull(),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('Referencia')
                                    ->placeholder('Sin referencia')
                                    ->icon('heroicon-o-document-text'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Última Actualización')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('No modificado'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
