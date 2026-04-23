<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources\CustomerResource\Pages;

use App\Modules\POS\Resources\CustomerResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Cliente')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre Completo'),
                        Infolists\Components\TextEntry::make('document_type')
                            ->label('Tipo de Documento')
                            ->badge(),
                        Infolists\Components\TextEntry::make('document_number')
                            ->label('Número de Documento'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Teléfono')
                            ->icon('heroicon-m-phone'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Estado')
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Dirección')
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('city')
                            ->label('Ciudad'),
                        Infolists\Components\TextEntry::make('state')
                            ->label('Provincia/Estado'),
                        Infolists\Components\TextEntry::make('postal_code')
                            ->label('Código Postal'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Notas')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Sin notas'),
                    ])
                    ->collapsible()
                    ->collapsed(true),

                Infolists\Components\Section::make('Información del Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}
