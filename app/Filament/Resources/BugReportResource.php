<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources;

use App\Filament\Resources\BugReportResource\Pages;
use App\Models\BugReport;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;

class BugReportResource extends Resource
{
    protected static ?string $model = BugReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    
    protected static ?string $navigationLabel = 'Tickets de Soporte';
    
    protected static ?string $modelLabel = 'Ticket';
    
    protected static ?string $pluralModelLabel = 'Tickets de Soporte';
    
    protected static ?string $navigationGroup = 'Sistema';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Ticket')
                    ->schema([
                        Forms\Components\TextInput::make('ticket_number')
                            ->label('Número de Ticket')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'in_progress' => 'En Progreso',
                                'waiting_feedback' => 'Esperando Feedback',
                                'resolved' => 'Resuelto',
                                'closed' => 'Cerrado',
                            ])
                            ->required()
                            ->default('pending'),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'low' => 'Baja',
                                'normal' => 'Normal',
                                'high' => 'Alta',
                                'urgent' => 'Urgente',
                            ])
                            ->required()
                            ->default('normal'),
                        
                        Forms\Components\Select::make('assigned_to')
                            ->label('Asignado a')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notas Internas')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas para el equipo')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severidad')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'critical' => '🔴 Crítico',
                        'high' => '🟠 Alto',
                        'medium' => '🟡 Medio',
                        'low' => '🟢 Bajo',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'waiting_feedback' => 'Esperando',
                        'resolved' => 'Resuelto',
                        'closed' => 'Cerrado',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'waiting_feedback' => 'gray',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'urgent' => 'Urgente',
                        'high' => 'Alta',
                        'normal' => 'Normal',
                        'low' => 'Baja',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'normal' => 'gray',
                        'low' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('tenant_name')
                    ->label('Tenant')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('reporter_name')
                    ->label('Reportado por')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Asignado')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('Sin asignar'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'waiting_feedback' => 'Esperando Feedback',
                        'resolved' => 'Resuelto',
                        'closed' => 'Cerrado',
                    ]),
                
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severidad')
                    ->options([
                        'critical' => 'Crítico',
                        'high' => 'Alto',
                        'medium' => 'Medio',
                        'low' => 'Bajo',
                    ]),
                
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'urgent' => 'Urgente',
                        'high' => 'Alta',
                        'normal' => 'Normal',
                        'low' => 'Baja',
                    ]),
                
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Asignado a')
                    ->relationship('assignedTo', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_in_progress')
                        ->label('Marcar En Progreso')
                        ->icon('heroicon-o-play')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'in_progress'])),
                    
                    Tables\Actions\BulkAction::make('mark_resolved')
                        ->label('Marcar Resuelto')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'resolved', 'resolved_at' => now()])),
                    
                    Tables\Actions\BulkAction::make('assign_to_me')
                        ->label('Asignarme')
                        ->icon('heroicon-o-user')
                        ->action(fn ($records) => $records->each->update(['assigned_to' => auth()->id()])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Ticket')
                    ->schema([
                        Infolists\Components\TextEntry::make('ticket_number')
                            ->label('Número de Ticket')
                            ->size('lg')
                            ->weight('bold')
                            ->copyable(),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->status_label)
                            ->color(fn ($record) => $record->status_color),
                        
                        Infolists\Components\TextEntry::make('severity')
                            ->label('Severidad')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->severity_label)
                            ->color(fn ($record) => $record->severity_color),
                        
                        Infolists\Components\TextEntry::make('priority')
                            ->label('Prioridad')
                            ->badge(),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de reporte')
                            ->dateTime('d/m/Y H:i'),
                        
                        Infolists\Components\TextEntry::make('assignedTo.name')
                            ->label('Asignado a')
                            ->placeholder('Sin asignar'),
                    ])
                    ->columns(3),
                
                Infolists\Components\Section::make('Detalles del Reporte')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Título')
                            ->size('lg')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->markdown()
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('steps_to_reproduce')
                            ->label('Pasos para reproducir')
                            ->markdown()
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->steps_to_reproduce)),
                    ]),
                
                Infolists\Components\Section::make('Información del Reportero')
                    ->schema([
                        Infolists\Components\TextEntry::make('reporter_name')
                            ->label('Nombre'),
                        
                        Infolists\Components\TextEntry::make('reporter_email')
                            ->label('Email')
                            ->copyable(),
                        
                        Infolists\Components\TextEntry::make('reporter_ip')
                            ->label('IP')
                            ->copyable(),
                        
                        Infolists\Components\TextEntry::make('tenant_name')
                            ->label('Tenant'),
                        
                        Infolists\Components\TextEntry::make('url')
                            ->label('URL del error')
                            ->copyable()
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('Navegador')
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Infolists\Components\Section::make('Notas Internas')
                    ->schema([
                        Infolists\Components\TextEntry::make('internal_notes')
                            ->label('Notas')
                            ->markdown()
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Infolists\Components\Section::make('Capturas de Pantalla')
                    ->schema([
                        Infolists\Components\ViewEntry::make('screenshots')
                            ->label('')
                            ->view('filament.infolists.bug-report-screenshots')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->screenshots))
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBugReports::route('/'),
            'view' => Pages\ViewBugReport::route('/{record}'),
            'edit' => Pages\EditBugReport::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes();
    }
}
