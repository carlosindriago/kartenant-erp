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

use App\Modules\POS\Resources\SaleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Descargar Comprobante')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('tenant.pos.receipt.pdf', [
                    'tenant' => \Spatie\Multitenancy\Models\Tenant::current()->domain,
                    'sale' => $this->record->id,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Venta')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('invoice_number')
                                    ->label('Número de Factura')
                                    ->copyable()
                                    ->icon('heroicon-m-document-text')
                                    ->weight('bold')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha y Hora')
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

                Infolists\Components\Section::make('Información del Cliente')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Nombre')
                                    ->default('Cliente General')
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('customer.email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->placeholder('No especificado'),

                                Infolists\Components\TextEntry::make('customer.phone')
                                    ->label('Teléfono')
                                    ->icon('heroicon-m-phone')
                                    ->placeholder('No especificado'),

                                Infolists\Components\TextEntry::make('customer.document_number')
                                    ->label('Documento')
                                    ->icon('heroicon-o-identification')
                                    ->placeholder('No especificado'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->customer_id !== null),

                Infolists\Components\Section::make('Productos Vendidos')
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
                                            ->alignEnd()
                                            ->getStateUsing(fn ($record) => $record->quantity * $record->unit_price),
                                    ]),
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
                                    ->label('TOTAL')
                                    ->money('USD')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Información de Pago')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_method')
                                    ->label('Método de Pago')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => '💵 Efectivo',
                                        'card' => '💳 Tarjeta',
                                        'transfer' => '🏦 Transferencia',
                                        default => ucfirst($state),
                                    })
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('amount_paid')
                                    ->label('Monto Pagado')
                                    ->money('USD')
                                    ->visible(fn ($record) => $record->payment_method === 'cash'),

                                Infolists\Components\TextEntry::make('change_amount')
                                    ->label('Cambio')
                                    ->money('USD')
                                    ->visible(fn ($record) => $record->payment_method === 'cash' && $record->change_amount > 0),

                                Infolists\Components\TextEntry::make('transaction_reference')
                                    ->label('Referencia de Transacción')
                                    ->copyable()
                                    ->visible(fn ($record) => in_array($record->payment_method, ['card', 'transfer']) && $record->transaction_reference),
                            ]),
                    ]),

                Infolists\Components\Section::make('Devoluciones')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('returns')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('return_number')
                                            ->label('Número de Devolución')
                                            ->weight('bold')
                                            ->icon('heroicon-m-arrow-uturn-left'),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i'),

                                        Infolists\Components\TextEntry::make('total')
                                            ->label('Total Devuelto')
                                            ->money('USD')
                                            ->color('warning'),

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
                    ])
                    ->visible(fn ($record) => $record->returns->count() > 0),

                Infolists\Components\Section::make('Información Adicional')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Cajero')
                                    ->default('Sin asignar')
                                    ->icon('heroicon-m-user-circle'),

                                Infolists\Components\TextEntry::make('cashRegister.register_number')
                                    ->label('Caja Registradora')
                                    ->default('No especificada')
                                    ->icon('heroicon-m-calculator'),

                                Infolists\Components\TextEntry::make('notes')
                                    ->label('Notas')
                                    ->placeholder('Sin notas')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Código de Seguridad')
                    ->description('Hash único e inmutable que verifica la autenticidad de este comprobante')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('verification_hash')
                            ->label('Hash SHA-256')
                            ->copyable()
                            ->copyMessage('Hash copiado al portapapeles')
                            ->copyMessageDuration(2000)
                            ->icon('heroicon-m-lock-closed')
                            ->weight('mono')
                            ->color('warning')
                            ->placeholder('No generado')
                            ->helperText('Este código aparece en el PDF del comprobante y en el código QR para verificación'),

                        Infolists\Components\TextEntry::make('verification_generated_at')
                            ->label('Fecha de Generación')
                            ->dateTime('d/m/Y H:i:s')
                            ->icon('heroicon-m-clock')
                            ->placeholder('No generado')
                            ->visible(fn ($record) => $record->verification_generated_at !== null),
                    ])
                    ->visible(fn ($record) => $record->verification_hash !== null)
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
