<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\CashRegisterOpeningResource\Pages;

use App\Filament\App\Resources\CashRegisterOpeningResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCashRegisterOpening extends ViewRecord
{
    protected static string $resource = CashRegisterOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->record->downloadPdf()),
            
            Actions\Action::make('verify')
                ->label('Verificar Documento')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->url(fn () => $this->record->getInternalVerificationRoute())
                ->openUrlInNewTab(),
            
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('delete', $this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\TextEntry::make('opening_number')
                            ->label('Número de Apertura')
                            ->badge()
                            ->color('primary'),
                        
                        Infolists\Components\TextEntry::make('openedBy.name')
                            ->label('Cajero'),
                        
                        Infolists\Components\TextEntry::make('opened_at')
                            ->label('Fecha y Hora')
                            ->dateTime('d/m/Y H:i:s'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'open' => 'success',
                                'closed' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'open' => 'Abierta',
                                'closed' => 'Cerrada',
                                default => $state,
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Saldo')
                    ->schema([
                        Infolists\Components\TextEntry::make('opening_balance')
                            ->label('Saldo Inicial')
                            ->money('ARS')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                    ]),
                
                Infolists\Components\Section::make('Observaciones')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->notes !== null),
                
                Infolists\Components\Section::make('Verificación')
                    ->schema([
                        Infolists\Components\TextEntry::make('verification_hash')
                            ->label('Hash de Verificación')
                            ->copyable()
                            ->copyMessage('Hash copiado')
                            ->copyMessageDuration(1500),
                        
                        Infolists\Components\TextEntry::make('verification_generated_at')
                            ->label('Generado el')
                            ->dateTime('d/m/Y H:i:s'),
                        
                        Infolists\Components\TextEntry::make('pdf_format')
                            ->label('Formato PDF')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'thermal' => 'Térmico 80mm',
                                'a4' => 'A4 Estándar',
                                default => $state,
                            }),
                    ])
                    ->columns(3),
                
                Infolists\Components\Section::make('Cierre Relacionado')
                    ->schema([
                        Infolists\Components\TextEntry::make('closing.closing_number')
                            ->label('Número de Cierre')
                            ->badge()
                            ->color('warning')
                            ->url(fn ($record) => $record->closing ? route('filament.app.resources.cash-register-closings.view', $record->closing) : null),
                        
                        Infolists\Components\TextEntry::make('closing.closed_at')
                            ->label('Cerrada el')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->visible(fn ($record) => $record->closing !== null)
                    ->columns(2),
            ]);
    }
}
