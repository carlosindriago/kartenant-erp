<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\ActivityResource\Pages;

use App\Modules\ActivityResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Actividad')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha y Hora')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->icon('heroicon-o-calendar'),
                                
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Acción')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'created' => 'Creado',
                                        'updated' => 'Actualizado',
                                        'deleted' => 'Eliminado',
                                        default => ucfirst($state),
                                    }),
                                
                                Infolists\Components\TextEntry::make('event')
                                    ->label('Evento')
                                    ->badge()
                                    ->placeholder('N/A'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Usuario y Contexto')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('causer.name')
                                    ->label('Usuario que Ejecutó la Acción')
                                    ->default('Sistema')
                                    ->icon('heroicon-o-user')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('causer.email')
                                    ->label('Email del Usuario')
                                    ->placeholder('N/A')
                                    ->icon('heroicon-o-envelope'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Recurso Afectado')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('subject_type')
                                    ->label('Tipo de Recurso')
                                    ->formatStateUsing(fn (?string $state): string => 
                                        $state ? class_basename($state) : 'N/A'
                                    )
                                    ->badge()
                                    ->color('info'),
                                
                                Infolists\Components\TextEntry::make('subject_id')
                                    ->label('ID del Recurso')
                                    ->badge()
                                    ->placeholder('N/A'),
                                
                                Infolists\Components\TextEntry::make('log_name')
                                    ->label('Categoría del Log')
                                    ->badge()
                                    ->placeholder('default'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Detalles de los Cambios')
                    ->schema([
                        Infolists\Components\ViewEntry::make('properties')
                            ->label('')
                            ->view('filament.infolists.components.activity-properties')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Infolists\Components\Section::make('Información Técnica')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('batch_uuid')
                                    ->label('UUID del Batch')
                                    ->placeholder('N/A')
                                    ->copyable(),
                                
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID del Registro')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
