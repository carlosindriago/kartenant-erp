<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules;

use App\Modules\ActivityResource\Pages;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Registro de Actividad';
    
    protected static ?string $modelLabel = 'Actividad';
    
    protected static ?string $pluralModelLabel = 'Registro de Actividad';
    
    protected static ?string $navigationGroup = 'Sistema';
    
    protected static ?int $navigationSort = 1;
    
    // Activity log no usa tenant scoping de Filament
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Solo lectura - no se permite crear/editar actividades manualmente
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->default('Sistema')
                    ->description(fn ($record) => $record->causer?->email),
                
                Tables\Columns\TextColumn::make('description')
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
                    })
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Recurso')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : 'N/A'
                    )
                    ->badge()
                    ->color('info')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Categoría')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('description')
                    ->label('Acción')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                    ]),
                
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Tipo de Recurso')
                    ->options(function () {
                        return Activity::query()
                            ->distinct()
                            ->whereNotNull('subject_type')
                            ->pluck('subject_type')
                            ->filter()
                            ->unique()
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->sort()
                            ->toArray();
                    }),
                
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->options(function () {
                        $causerIds = Activity::query()
                            ->whereNotNull('causer_id')
                            ->distinct()
                            ->pluck('causer_id');
                        
                        return \App\Models\User::query()
                            ->whereIn('id', $causerIds)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),
                
                Tables\Filters\Filter::make('date_range')
                    ->label('Rango de Fechas')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles'),
            ])
            ->bulkActions([
                // No permitir acciones masivas para preservar integridad del log
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay actividad registrada')
            ->emptyStateDescription('Las actividades del sistema aparecerán aquí')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Modules\ActivityResource\Pages\ListActivities::route('/'),
            'view' => \App\Modules\ActivityResource\Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // No se pueden crear actividades manualmente
    }

    public static function canEdit($record): bool
    {
        return false; // No se pueden editar actividades (inmutables)
    }

    public static function canDelete($record): bool
    {
        return false; // No se pueden eliminar actividades (auditoría)
    }
}
