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
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Multitenancy\Models\Tenant;

class ViewSaleReturn extends ViewRecord
{
    protected static string $resource = SaleReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Descargar Nota de Crédito')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('tenant.pos.credit-note.pdf', [
                    'tenant' => Tenant::current()->domain,
                    'saleReturn' => $this->record->id,
                ]))
                ->openUrlInNewTab(),

            Actions\Action::make('view_original_sale')
                ->label('Ver Venta Original')
                ->icon('heroicon-o-arrow-right')
                ->color('info')
                ->url(fn () => route('filament.app.resources.sales.view', [
                    'record' => $this->record->original_sale_id,
                    'tenant' => Tenant::current()->domain,
                ])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Devolución')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('return_number')
                                    ->label('Número de Nota de Crédito')
                                    ->copyable()
                                    ->icon('heroicon-m-document-text')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha de Devolución')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-m-clock'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'completed' => 'Completada',
                                        'pending' => 'Pendiente',
                                        'cancelled' => 'Cancelada',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Referencia a Venta Original')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('originalSale.invoice_number')
                                    ->label('Factura Original')
                                    ->icon('heroicon-m-document')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('originalSale.created_at')
                                    ->label('Fecha de Venta')
                                    ->dateTime('d/m/Y H:i'),

                                Infolists\Components\TextEntry::make('originalSale.total')
                                    ->label('Total de Venta')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('return_type')
                                    ->label('Tipo de Devolución')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'full' => 'Completa',
                                        'partial' => 'Parcial',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'full' => 'warning',
                                        'partial' => 'info',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Razón de la Devolución')
                    ->schema([
                        Infolists\Components\TextEntry::make('reason')
                            ->label('')
                            ->placeholder('No se especificó una razón')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->reason)),

                Infolists\Components\Section::make('Productos Devueltos')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(5)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('product_name')
                                            ->label('Producto')
                                            ->weight('bold')
                                            ->columnSpan(2),

                                        Infolists\Components\TextEntry::make('quantity')
                                            ->label('Cantidad')
                                            ->alignCenter(),

                                        Infolists\Components\TextEntry::make('unit_price')
                                            ->label('Precio Unit.')
                                            ->money('USD')
                                            ->alignEnd(),

                                        Infolists\Components\TextEntry::make('line_total')
                                            ->label('Subtotal')
                                            ->money('USD')
                                            ->weight('bold')
                                            ->alignEnd(),
                                    ]),
                                Infolists\Components\TextEntry::make('return_reason')
                                    ->label('Razón específica')
                                    ->placeholder('No especificada')
                                    ->columnSpanFull()
                                    ->color('warning')
                                    ->icon('heroicon-m-exclamation-triangle'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Totales')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal (Neto)')
                                    ->money('USD')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('tax_amount')
                                    ->label('IVA')
                                    ->money('USD')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('total')
                                    ->label('TOTAL REEMBOLSADO')
                                    ->money('USD')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('warning'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Información del Reembolso')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('refund_method')
                                    ->label('Método de Reembolso')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => '💵 Efectivo',
                                        'card' => '💳 Tarjeta',
                                        'transfer' => '🏦 Transferencia',
                                        'credit_note' => '📄 Nota de Crédito',
                                        default => ucfirst($state),
                                    })
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('processedBy.name')
                                    ->label('Procesado Por')
                                    ->icon('heroicon-m-user-circle'),

                                Infolists\Components\TextEntry::make('processed_at')
                                    ->label('Fecha de Procesamiento')
                                    ->dateTime('d/m/Y H:i:s'),
                            ]),
                    ]),
            ]);
    }
}
