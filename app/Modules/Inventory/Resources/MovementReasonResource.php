<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources;

use App\Modules\Inventory\Models\MovementReason;
use App\Modules\Inventory\Resources\MovementReasonResource\Pages\CreateMovementReason;
use App\Modules\Inventory\Resources\MovementReasonResource\Pages\EditMovementReason;
use App\Modules\Inventory\Resources\MovementReasonResource\Pages\ListMovementReasons;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovementReasonResource extends Resource
{
    protected static ?string $model = MovementReason::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Motivos de Movimiento';

    protected static ?string $modelLabel = 'Motivo';

    protected static ?string $pluralModelLabel = 'Motivos de Movimiento';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 4;

    // Disable Filament's tenant scoping
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        // En database-per-tenant, no necesitamos filtrar por tenant_id
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Motivo')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Motivo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Compra a Proveedor')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo de Movimiento')
                            ->required()
                            ->options([
                                'entrada' => 'Entrada (Aumenta Stock)',
                                'salida' => 'Salida (Disminuye Stock)',
                            ])
                            ->helperText('Indica si este motivo es para entradas o salidas de stock'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Los motivos inactivos no aparecerán en los formularios'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No hay motivos registrados')
            ->emptyStateDescription('Crea motivos personalizados para registrar entradas y salidas de stock')
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovementReasons::route('/'),
            'create' => CreateMovementReason::route('/create'),
            'edit' => EditMovementReason::route('/{record}/edit'),
        ];
    }
}
