<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashRegisterOpeningResource\Pages;
use App\Models\Tenancy\CashRegister\CashRegisterOpening;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class CashRegisterOpeningResource extends Resource
{
    protected static ?string $model = CashRegisterOpening::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Aperturas de Caja';

    protected static ?string $modelLabel = 'Apertura de Caja';

    protected static ?string $pluralModelLabel = 'Aperturas de Caja';

    protected static ?string $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Apertura')
                    ->schema([
                        Forms\Components\TextInput::make('opening_number')
                            ->label('Número de Apertura')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                        
                        Forms\Components\Select::make('opened_by')
                            ->label('Cajero')
                            ->relationship('openedBy', 'name')
                            ->default(fn () => auth()->id())
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Fecha y Hora de Apertura')
                            ->default(now())
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\TextInput::make('opening_balance')
                            ->label('Saldo Inicial')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\Select::make('pdf_format')
                            ->label('Formato de PDF')
                            ->options([
                                'thermal' => 'Térmico 80mm',
                                'a4' => 'A4 Estándar',
                            ])
                            ->default('thermal')
                            ->required(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opening_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                
                Tables\Columns\TextColumn::make('openedBy.name')
                    ->label('Cajero')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Fecha/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Saldo Inicial')
                    ->money('ARS')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierta',
                        'closed' => 'Cerrada',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abierta',
                        'closed' => 'Cerrada',
                    ]),
                
                Tables\Filters\Filter::make('opened_at')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($query, $date) => $query->whereDate('opened_at', '>=', $date))
                            ->when($data['hasta'], fn ($query, $date) => $query->whereDate('opened_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (CashRegisterOpening $record) => $record->downloadPdf()),
                Tables\Actions\Action::make('verify')
                    ->label('Verificar')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->url(fn (CashRegisterOpening $record) => $record->getInternalVerificationRoute())
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('opened_at', 'desc');
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
            'index' => Pages\ListCashRegisterOpenings::route('/'),
            'create' => Pages\CreateCashRegisterOpening::route('/create'),
            'view' => Pages\ViewCashRegisterOpening::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false; // Las aperturas no se pueden editar una vez creadas
    }
}
