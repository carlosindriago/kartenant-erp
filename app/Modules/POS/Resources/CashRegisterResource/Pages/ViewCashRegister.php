<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources\CashRegisterResource\Pages;

use App\Modules\POS\Resources\CashRegisterResource;
use App\Modules\POS\Services\CashRegisterService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCashRegister extends ViewRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('force_close')
                ->label('Forzar Cierre')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn () => $this->record->isOpen())
                ->requiresConfirmation()
                ->modalHeading('Forzar Cierre de Caja')
                ->modalDescription(fn () => '¿Estás seguro de forzar el cierre de esta caja? '.
                    "Esta acción cerrará la caja del usuario {$this->record->openedBy->name}."
                )
                ->form([
                    TextInput::make('actual_amount')
                        ->label('Monto Contado en Caja')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Ingresa el monto real contado en la caja'),

                    Textarea::make('reason')
                        ->label('Motivo del Cierre Forzado')
                        ->required()
                        ->rows(3)
                        ->placeholder('Ej: Fin de turno sin cerrar, emergencia, error operativo, ausencia del cajero...')
                        ->helperText('Este motivo será enviado al cajero por notificación'),
                ])
                ->action(function (array $data): void {
                    try {
                        $service = app(CashRegisterService::class);

                        $service->forceClosureByAdmin(
                            cashRegister: $this->record,
                            actualAmount: $data['actual_amount'],
                            reason: $data['reason'],
                            forcedByUserId: auth('tenant')->id()
                        );

                        Notification::make()
                            ->success()
                            ->title('✅ Caja Cerrada Forzadamente')
                            ->body("La caja {$this->record->register_number} ha sido cerrada.")
                            ->send();

                        $this->redirect(self::getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('❌ Error al Cerrar Caja')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $summary = $this->record->getSalesSummary();

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('register_number')
                                    ->label('Número de Registro')
                                    ->badge()
                                    ->color('primary')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                Infolists\Components\TextEntry::make('openedBy.name')
                                    ->label('Cajero')
                                    ->icon('heroicon-m-user')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'open' => 'success',
                                        'closed' => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'open' => '🟢 Abierta',
                                        'closed' => '🔒 Cerrada',
                                        default => $state,
                                    })
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            ]),
                    ]),

                Infolists\Components\Section::make('Detalles de Apertura')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('opened_at')
                                    ->label('Fecha/Hora Apertura')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-m-calendar'),

                                Infolists\Components\TextEntry::make('initial_amount')
                                    ->label('Monto Inicial')
                                    ->money('CLP', locale: 'es_CL')
                                    ->icon('heroicon-m-banknotes'),

                                Infolists\Components\TextEntry::make('hours_open')
                                    ->label('Tiempo Abierta')
                                    ->state(fn () => $this->record->opened_at->diffForHumans(null, true))
                                    ->icon('heroicon-m-clock'),
                            ]),

                        Infolists\Components\TextEntry::make('opening_notes')
                            ->label('Notas de Apertura')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),

                // Total Esperado en Efectivo - Destacado
                Infolists\Components\Section::make('💰 Total Esperado en Efectivo')
                    ->description('Monto que debería estar en caja al contar (Inicial + Ventas Completadas)')
                    ->schema([
                        Infolists\Components\TextEntry::make('expected_cash_total')
                            ->label('Total en Efectivo')
                            ->state(function () use ($summary) {
                                $expectedCashTotal = $this->record->initial_amount + ($summary['cash_sales'] ?? 0);

                                return '$'.number_format($expectedCashTotal, 0);
                            })
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success')
                            ->icon('heroicon-m-banknotes')
                            ->columnSpanFull(),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('initial_breakdown')
                                    ->label('Monto Inicial')
                                    ->state(fn () => '$'.number_format($this->record->initial_amount, 0))
                                    ->icon('heroicon-m-currency-dollar'),

                                Infolists\Components\TextEntry::make('cash_sales_breakdown')
                                    ->label('+ Ventas en Efectivo')
                                    ->state(fn () => '$'.number_format($summary['cash_sales'] ?? 0, 0))
                                    ->icon('heroicon-m-plus-circle')
                                    ->color('success'),
                            ]),
                    ])
                    ->visible(fn () => $this->record->isOpen())
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Resumen de Ventas')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_sales')
                                    ->label('Transacciones')
                                    ->state(fn () => $summary['total_sales'] ?? 0)
                                    ->icon('heroicon-m-shopping-cart')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Total Ventas')
                                    ->state(fn () => '$'.number_format($summary['total_amount'] ?? 0, 0))
                                    ->icon('heroicon-m-currency-dollar')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('cancelled_sales')
                                    ->label('Cancelaciones')
                                    ->state(fn () => $summary['cancelled_sales'] ?? 0)
                                    ->icon('heroicon-m-x-circle')
                                    ->color('danger'),

                                Infolists\Components\TextEntry::make('cash_returns')
                                    ->label('Devoluciones')
                                    ->state(fn () => '$'.number_format($summary['cash_returns'] ?? 0, 0))
                                    ->icon('heroicon-m-arrow-uturn-left')
                                    ->color('warning'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('cash_sales')
                                    ->label('💵 Efectivo')
                                    ->state(fn () => '$'.number_format($summary['cash_sales'] ?? 0, 0)),

                                Infolists\Components\TextEntry::make('card_sales')
                                    ->label('💳 Tarjeta')
                                    ->state(fn () => '$'.number_format($summary['card_sales'] ?? 0, 0)),

                                Infolists\Components\TextEntry::make('transfer_sales')
                                    ->label('🏦 Transferencia')
                                    ->state(fn () => '$'.number_format($summary['transfer_sales'] ?? 0, 0)),
                            ]),
                    ])
                    ->visible(fn () => ! empty($summary)),

                Infolists\Components\Section::make('Detalles de Cierre')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('closed_at')
                                    ->label('Fecha/Hora Cierre')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-m-calendar')
                                    ->placeholder('No cerrada'),

                                Infolists\Components\TextEntry::make('closedBy.name')
                                    ->label('Cerrado Por')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('duration')
                                    ->label('Duración Turno')
                                    ->state(fn () => $this->record->closed_at
                                        ? $this->record->opened_at->diffForHumans($this->record->closed_at, true)
                                        : 'En proceso'
                                    )
                                    ->icon('heroicon-m-clock'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('expected_amount')
                                    ->label('Monto Esperado')
                                    ->money('CLP', locale: 'es_CL')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('actual_amount')
                                    ->label('Monto Real')
                                    ->money('CLP', locale: 'es_CL')
                                    ->placeholder('N/A'),

                                Infolists\Components\TextEntry::make('difference')
                                    ->label('Diferencia')
                                    ->money('CLP', locale: 'es_CL')
                                    ->color(fn ($state) => match (true) {
                                        $state > 0 => 'warning',
                                        $state < 0 => 'danger',
                                        default => 'success',
                                    })
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->badge()
                                    ->placeholder('N/A'),
                            ]),

                        Infolists\Components\TextEntry::make('closing_notes')
                            ->label('Notas de Cierre')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->record->isClosed()),
            ]);
    }
}
