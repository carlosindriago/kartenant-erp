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

use App\Filament\Resources\BackupLogResource\Pages;
use App\Models\BackupLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class BackupLogResource extends Resource
{
    protected static ?string $model = BackupLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        // No form needed - read-only resource
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('database_name')
                    ->label('Base de Datos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->default('Landlord'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Exitoso',
                        'failed' => 'Fallido',
                        'running' => 'En Proceso',
                        'pending' => 'Pendiente',
                        default => $state,
                    }),
                TextColumn::make('backup_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'info',
                        'manual' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Automático',
                        'manual' => 'Manual',
                        default => $state,
                    }),
                TextColumn::make('formatted_file_size')
                    ->label('Tamaño')
                    ->sortable(query: fn (Builder $query, string $direction): Builder =>
                        $query->orderBy('file_size', $direction)
                    ),
                TextColumn::make('duration')
                    ->label('Duración')
                    ->formatStateUsing(fn (?int $state): string =>
                        $state ? "{$state}s" : '-'
                    )
                    ->toggleable(),
                TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->label('Completado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (BackupLog $record): string =>
                        $record->created_at->diffForHumans()
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'success' => 'Exitoso',
                        'failed' => 'Fallido',
                        'running' => 'En Proceso',
                        'pending' => 'Pendiente',
                    ]),
                SelectFilter::make('backup_type')
                    ->label('Tipo')
                    ->options([
                        'daily' => 'Automático',
                        'manual' => 'Manual',
                    ]),
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->preload(),
                Filter::make('failed_only')
                    ->label('Solo fallidos')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'failed'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackupLogs::route('/'),
            'view' => Pages\ViewBackupLog::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.system.view', 'superadmin') ?? false);
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false; // Read-only
    }

    public static function canEdit($record): bool
    {
        return false; // Read-only
    }

    public static function canDelete($record): bool
    {
        return false; // Read-only
    }
}
