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

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Auditoría';

    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?string $modelLabel = 'Evento';

    protected static ?string $pluralModelLabel = 'Eventos';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'two_factor_sent' => 'warning',
                        'two_factor_verified' => 'success',
                        'two_factor_invalid' => 'danger',
                        'login_failed' => 'danger',
                        'account_locked' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.email')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard')
                    ->label('Guard')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('route')
                    ->label('Ruta')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('method')
                    ->label('Método')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'login' => 'login',
                        'logout' => 'logout',
                        'two_factor_sent' => 'two_factor_sent',
                        'two_factor_verified' => 'two_factor_verified',
                        'two_factor_invalid' => 'two_factor_invalid',
                        'login_failed' => 'login_failed',
                        'account_locked' => 'account_locked',
                    ]),
                Tables\Filters\SelectFilter::make('guard')
                    ->label('Guard')
                    ->options([
                        'superadmin' => 'superadmin',
                        'tenant' => 'tenant',
                        'web' => 'web',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalle del evento')
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detalles')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')->label('Fecha')->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('event')->label('Evento'),
                        TextEntry::make('description')->label('Descripción')->columnSpanFull(),
                        TextEntry::make('causer.email')->label('Usuario'),
                        TextEntry::make('guard')->label('Guard'),
                        TextEntry::make('ip')->label('IP'),
                        TextEntry::make('route')->label('Ruta'),
                        TextEntry::make('method')->label('Método'),
                        TextEntry::make('tenant_id')->label('Tenant'),
                    ]),
                Section::make('Propiedades')
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('properties')->label('Payload'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('log_name', 'auth');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('superadmin')->user();
        if (! $user) {
            return false;
        }

        // Verificar permiso con el guard superadmin explícitamente
        return $user->is_super_admin || $user->hasPermissionTo('admin.audit.view', 'superadmin');
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }
}
