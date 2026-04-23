<?php

namespace App\Filament\Resources\PaymentTransactionResource\Pages;

use App\Filament\Resources\PaymentTransactionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentTransaction extends ViewRecord
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Pago')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label('Tenant'),
                                Infolists\Components\TextEntry::make('subscription.plan.name')
                                    ->label('Plan'),
                                Infolists\Components\TextEntry::make('gateway_driver')
                                    ->label('Gateway')
                                    ->badge(),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Monto')
                                    ->money(fn ($record) => $record->currency),
                                Infolists\Components\TextEntry::make('currency')
                                    ->label('Moneda'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        Infolists\Components\TextEntry::make('transaction_id')
                            ->label('ID de Transacción')
                            ->placeholder('N/A'),

                        Infolists\Components\ImageEntry::make('proof_of_payment')
                            ->label('Comprobante de Pago')
                            ->height(200)
                            ->visibility('private'),
                    ]),

                Infolists\Components\Section::make('Información de Aprobación')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('approver.name')
                                    ->label('Aprobado Por')
                                    ->placeholder('No aprobado aún'),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Fecha de Aprobación')
                                    ->dateTime()
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->approved_at !== null),

                Infolists\Components\Section::make('Metadatos')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata')
                            ->label('Información Adicional')
                            ->markdown()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT)),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record->metadata)),

                Infolists\Components\Section::make('Fechas')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Creado')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Actualizado')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
