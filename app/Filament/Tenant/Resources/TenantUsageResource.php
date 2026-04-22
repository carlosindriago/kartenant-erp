<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Resources\TenantUsageResource\Pages;
use App\Models\TenantUsage;
use App\Services\TenantUsageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantUsageResource extends Resource
{
    protected static ?string $model = TenantUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Uso del Plan';

    protected static ?string $modelLabel = 'Uso del Plan';

    protected static ?string $pluralModelLabel = 'Usos del Plan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Período')
                    ->schema([
                        Forms\Components\TextInput::make('year')
                            ->label('Año')
                            ->required()
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('month')
                            ->label('Mes')
                            ->required()
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Placeholder::make('period_label')
                            ->label('Período')
                            ->content(fn ($record) => $record?->getPeriodLabel() ?? '-'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Límites del Plan')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('max_sales_display')
                                    ->label('Ventas Mensuales')
                                    ->content(fn ($record) =>
                                        $record->max_sales_per_month
                                            ? number_format($record->max_sales_per_month)
                                            : 'Ilimitado'
                                    ),
                                Forms\Components\Placeholder::make('max_products_display')
                                    ->label('Productos')
                                    ->content(fn ($record) =>
                                        $record->max_products
                                            ? number_format($record->max_products)
                                            : 'Ilimitado'
                                    ),
                                Forms\Components\Placeholder::make('max_users_display')
                                    ->label('Usuarios')
                                    ->content(fn ($record) =>
                                        $record->max_users
                                            ? number_format($record->max_users)
                                            : 'Ilimitado'
                                    ),
                                Forms\Components\Placeholder::make('max_storage_display')
                                    ->label('Almacenamiento (MB)')
                                    ->content(fn ($record) =>
                                        $record->max_storage_mb
                                            ? number_format($record->max_storage_mb)
                                            : 'Ilimitado'
                                    ),
                            ]),
                    ]),

                Forms\Components\Section::make('Uso Actual')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('sales_usage')
                                    ->label('Ventas Realizadas')
                                    ->content(function ($record) {
                                        if (!$record) return '-';
                                        $percentage = number_format($record->sales_percentage, 1);
                                        $color = $record->getZoneForMetric('sales') === 'critical' ? 'red' :
                                                ($record->getZoneForMetric('sales') === 'overdraft' ? 'orange' :
                                                ($record->getZoneForMetric('sales') === 'warning' ? 'yellow' : 'green'));
                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='color: {$color}; font-weight: bold;'>" .
                                            number_format($record->sales_count) . " ({$percentage}%)</div>"
                                        );
                                    }),
                                Forms\Components\Placeholder::make('products_usage')
                                    ->label('Productos Creados')
                                    ->content(function ($record) {
                                        if (!$record) return '-';
                                        $percentage = number_format($record->products_percentage, 1);
                                        $color = $record->getZoneForMetric('products') === 'critical' ? 'red' :
                                                ($record->getZoneForMetric('products') === 'overdraft' ? 'orange' :
                                                ($record->getZoneForMetric('products') === 'warning' ? 'yellow' : 'green'));
                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='color: {$color}; font-weight: bold;'>" .
                                            number_format($record->products_count) . " ({$percentage}%)</div>"
                                        );
                                    }),
                                Forms\Components\Placeholder::make('users_usage')
                                    ->label('Usuarios Activos')
                                    ->content(function ($record) {
                                        if (!$record) return '-';
                                        $percentage = number_format($record->users_percentage, 1);
                                        $color = $record->getZoneForMetric('users') === 'critical' ? 'red' :
                                                ($record->getZoneForMetric('users') === 'overdraft' ? 'orange' :
                                                ($record->getZoneForMetric('users') === 'warning' ? 'yellow' : 'green'));
                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='color: {$color}; font-weight: bold;'>" .
                                            number_format($record->users_count) . " ({$percentage}%)</div>"
                                        );
                                    }),
                                Forms\Components\Placeholder::make('storage_usage')
                                    ->label('Almacenamiento (MB)')
                                    ->content(function ($record) {
                                        if (!$record) return '-';
                                        $percentage = number_format($record->storage_percentage, 1);
                                        $color = $record->getZoneForMetric('storage') === 'critical' ? 'red' :
                                                ($record->getZoneForMetric('storage') === 'overdraft' ? 'orange' :
                                                ($record->getZoneForMetric('storage') === 'warning' ? 'yellow' : 'green'));
                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='color: {$color}; font-weight: bold;'>" .
                                            number_format($record->storage_size_mb) . " MB ({$percentage}%)</div>"
                                        );
                                    }),
                            ]),
                    ]),

                Forms\Components\Section::make('Estado y Alertas')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('status_display')
                                    ->label('Estado General')
                                    ->content(function ($record) {
                                        if (!$record) return '-';

                                        $statusConfig = [
                                            'normal' => ['color' => 'green', 'icon' => '✅', 'text' => 'Normal'],
                                            'warning' => ['color' => 'yellow', 'icon' => '⚠️', 'text' => 'Advertencia'],
                                            'overdraft' => ['color' => 'orange', 'icon' => '🔴', 'text' => 'Excedido'],
                                            'critical' => ['color' => 'red', 'icon' => '🚨', 'text' => 'Crítico'],
                                        ];

                                        $config = $statusConfig[$record->status] ?? $statusConfig['normal'];

                                        return new \Illuminate\Support\HtmlString(
                                            "<div style='color: {$config['color']}; font-weight: bold; font-size: 1.1em;'>" .
                                            "{$config['icon']} {$config['text']}</div>"
                                        );
                                    }),
                                Forms\Components\Placeholder::make('days_remaining')
                                    ->label('Días Restantes en el Período')
                                    ->content(fn ($record) => $record?->getDaysRemainingInPeriod() ?? '-'),
                                Forms\Components\Toggle::make('upgrade_required_next_cycle')
                                    ->label('Requiere Actualización en Siguiente Ciclo')
                                    ->disabled(),
                                Forms\Components\Placeholder::make('last_calculated_at')
                                    ->label('Última Actualización')
                                    ->content(fn ($record) => $record?->last_calculated_at?->format('d/m/Y H:i') ?? '-'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_label')
                    ->label('Período')
                    ->getStateUsing(fn ($record) => $record->getPeriodLabel())
                    ->sortable(['year', 'month'])
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (TenantUsage $record): string => match ($record->status) {
                        'normal' => 'success',
                        'warning' => 'warning',
                        'overdraft' => 'danger',
                        'critical' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'warning' => '⚠️ Advertencia',
                        'overdraft' => '🔴 Excedido',
                        'critical' => '🚨 Crítico',
                    }),

                Tables\Columns\TextColumn::make('sales_count')
                    ->label('Ventas')
                    ->suffix(' / ')
                    ->formatStateUsing(fn (TenantUsage $record) =>
                        $record->max_sales_per_month ? number_format($record->max_sales_per_month) : '∞'
                    )
                    ->color(fn (TenantUsage $record): string =>
                        $record->getZoneForMetric('sales') === 'critical' ? 'danger' :
                        ($record->getZoneForMetric('sales') === 'overdraft' ? 'warning' :
                        ($record->getZoneForMetric('sales') === 'warning' ? 'warning' : 'success'))
                    ),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Productos')
                    ->suffix(' / ')
                    ->formatStateUsing(fn (TenantUsage $record) =>
                        $record->max_products ? number_format($record->max_products) : '∞'
                    )
                    ->color(fn (TenantUsage $record): string =>
                        $record->getZoneForMetric('products') === 'critical' ? 'danger' :
                        ($record->getZoneForMetric('products') === 'overdraft' ? 'warning' :
                        ($record->getZoneForMetric('products') === 'warning' ? 'warning' : 'success'))
                    ),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->suffix(' / ')
                    ->formatStateUsing(fn (TenantUsage $record) =>
                        $record->max_users ? number_format($record->max_users) : '∞'
                    )
                    ->color(fn (TenantUsage $record): string =>
                        $record->getZoneForMetric('users') === 'critical' ? 'danger' :
                        ($record->getZoneForMetric('users') === 'overdraft' ? 'warning' :
                        ($record->getZoneForMetric('users') === 'warning' ? 'warning' : 'success'))
                    ),

                Tables\Columns\TextColumn::make('storage_size_mb')
                    ->label('Almacenamiento')
                    ->suffix(' MB / ')
                    ->formatStateUsing(fn (TenantUsage $record) =>
                        $record->max_storage_mb ? number_format($record->max_storage_mb) . ' MB' : '∞'
                    )
                    ->color(fn (TenantUsage $record): string =>
                        $record->getZoneForMetric('storage') === 'critical' ? 'danger' :
                        ($record->getZoneForMetric('storage') === 'overdraft' ? 'warning' :
                        ($record->getZoneForMetric('storage') === 'warning' ? 'warning' : 'success'))
                    ),

                Tables\Columns\IconColumn::make('upgrade_required_next_cycle')
                    ->label('Actualización Requerida')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('last_calculated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'normal' => 'Normal',
                        'warning' => 'Advertencia',
                        'overdraft' => 'Excedido',
                        'critical' => 'Crítico',
                    ]),

                Tables\Filters\Filter::make('requires_upgrade')
                    ->label('Requiere Actualización')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('upgrade_required_next_cycle', true)
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('refresh')
                    ->label('Recalcular')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (TenantUsage $record, TenantUsageService $usageService) {
                        $usageService->synchronizeCounters($record->tenant_id);
                        $record->calculatePercentages();
                    })
                    ->successNotificationTitle('Uso recalculado exitosamente'),
            ])
            ->bulkActions([
                // No bulk actions for usage management
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->emptyStateHeading('No hay registros de uso')
            ->emptyStateDescription('Los registros de uso aparecerán aquí cuando haya actividad en tu cuenta.')
            ->emptyStateActions([
                // No actions needed for empty state
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
            'index' => Pages\ListTenantUsages::route('/'),
            'view' => Pages\ViewTenantUsage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Usage records are created automatically
    }

    public static function canEdit($record): bool
    {
        return false; // Usage records are managed automatically
    }

    public static function canDelete($record): bool
    {
        return false; // Usage records should not be deleted
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id)
            ->with('tenant');
    }
}