<?php

namespace App\Filament\Actions\SubscriptionPlans;

use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ConfigureLimitsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'configureLimits';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Configurar Límites Flexibles')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('primary')
            ->tooltip('Configurar estrategia de límites y tolerancia')
            ->modalHeading(fn (Model $record): string =>
                'Configurar Límites Flexibles: ' . $record->name
            )
            ->modalDescription('Define cómo el sistema manejará los excesos de límites en este plan')
            ->modalIcon('heroicon-o-shield-check')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Guardar Configuración')
            ->modalCancelActionLabel('Cancelar')
            ->fillForm(function (Model $record): array {
                $limits = $record->limits ?? [];
                return [
                    'max_sales_per_month' => $limits['monthly_sales'] ?? $record->max_sales_per_month,
                    'max_users' => $limits['users'] ?? $record->max_users ?? 1,
                    'max_storage_mb' => $limits['storage_mb'] ?? $record->max_storage_mb ?? 1024,
                    'max_products' => $limits['products'] ?? $record->max_products,
                    'overage_strategy' => $record->overage_strategy ?? 'soft_limit',
                    'overage_percentage' => $record->overage_percentage ?? 20,
                ];
            })
            ->form([
                Forms\Components\Section::make('Límites Base')
                    ->description('Establece los límites base para cada métrica. 0 = ilimitado')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('max_sales_per_month')
                                    ->label('Ventas Mensuales')
                                    ->helperText('Número máximo de ventas permitidas por mes')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->placeholder('0 = Ilimitado')
                                    ->suffixIcon('heroicon-o-currency-dollar')
                                    ->prefixIcon('heroicon-o-shopping-cart'),

                                Forms\Components\TextInput::make('max_users')
                                    ->label('Usuarios')
                                    ->helperText('Número máximo de usuarios permitidos')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->placeholder('1')
                                    ->suffixIcon('heroicon-o-users')
                                    ->prefixIcon('heroicon-o-shield-check'),

                                Forms\Components\TextInput::make('max_storage_mb')
                                    ->label('Almacenamiento (MB)')
                                    ->helperText('Espacio de almacenamiento en megabytes')
                                    ->numeric()
                                    ->required()
                                    ->minValue(100)
                                    ->default(1024)
                                    ->placeholder('1024')
                                    ->suffix('MB')
                                    ->suffixIcon('heroicon-o-server')
                                    ->prefixIcon('heroicon-o-cloud'),

                                Forms\Components\TextInput::make('max_products')
                                    ->label('Productos')
                                    ->helperText('Número máximo de productos en catálogo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->placeholder('0 = Ilimitado')
                                    ->suffixIcon('heroicon-o-cube')
                                    ->prefixIcon('heroicon-o-cube'),
                            ]),
                    ]),

                Forms\Components\Section::make('Estrategia de Exceso')
                    ->description('Define cómo el sistema responderá cuando se alcancen los límites')
                    ->icon('heroicon-o-shield-exclamation')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('overage_strategy')
                                    ->label('Estrategia de Manejo de Excesos')
                                    ->required()
                                    ->default('soft_limit')
                                    ->options([
                                        'strict' => '🔴 Estricto (Bloqueo inmediato)',
                                        'soft_limit' => '🟢 Flexible (Permitir exceso)',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('show_tolerance', $state === 'soft_limit')
                                    )
                                    ->helperText('El POS nunca se bloqueará, solo acciones administrativas'),

                                Forms\Components\Placeholder::make('strategy_info')
                                    ->label('Información Importante')
                                    ->content(function (callable $get) {
                                        $strategy = $get('overage_strategy');
                                        if ($strategy === 'strict') {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-3">
                                                    <div class="flex items-start">
                                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                        </svg>
                                                        <div>
                                                            <p class="text-sm font-medium text-red-800 dark:text-red-200">Bloqueo Inmediato</p>
                                                            <p class="text-xs text-red-700 dark:text-red-300 mt-1">Las acciones administrativas se bloquean al alcanzar el límite. El POS continúa funcionando normalmente.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ');
                                        } else {
                                            return new \Illuminate\Support\HtmlString('
                                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md p-3">
                                                    <div class="flex items-start">
                                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <div>
                                                            <p class="text-sm font-medium text-green-800 dark:text-green-200">Flexibilidad Controlada</p>
                                                            <p class="text-xs text-green-700 dark:text-green-300 mt-1">Permite exceder límites dentro del porcentaje de tolerancia. Ideal para business peaks.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ');
                                        }
                                    })
                                    ->visible(),

                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('overage_percentage')
                                            ->label('Porcentaje de Tolerancia')
                                            ->helperText(fn (callable $get): string =>
                                                $get('max_sales_per_month') && $get('overage_strategy') === 'soft_limit'
                                                    ? sprintf(
                                                        'Con límite de %d ventas y tolerancia de %d%%, el bloqueo ocurrirá en %d ventas',
                                                        $get('max_sales_per_month'),
                                                        $get('overage_percentage') ?? 20,
                                                        $get('max_sales_per_month') * (1 + (($get('overage_percentage') ?? 20) / 100))
                                                    )
                                                    : 'Porcentaje adicional permitido sobre el límite base'
                                            )
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(50)
                                            ->default(20)
                                            ->suffix('%')
                                            ->prefixIcon('heroicon-o-chart-pie')
                                            ->suffixIcon('heroicon-o-light-bulb')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                if ($get('overage_strategy') === 'soft_limit' && $get('max_sales_per_month')) {
                                                    $limit = $get('max_sales_per_month');
                                                    $tolerance = $state;
                                                    $blocking_limit = $limit * (1 + ($tolerance / 100));

                                                    $set('tolerance_preview', sprintf(
                                                        '📊 Límite base: %d | 📈 Límite con tolerancia: %d | 💪 Exceso permitido: %d',
                                                        $limit,
                                                        $blocking_limit,
                                                        $blocking_limit - $limit
                                                    ));
                                                }
                                            }),

                                        Forms\Components\Placeholder::make('tolerance_preview')
                                            ->label('Vista Previa de Límites')
                                            ->content(fn (callable $get): string =>
                                                $get('overage_strategy') === 'soft_limit' && $get('max_sales_per_month') && $get('overage_percentage')
                                                    ? sprintf(
                                                        '📊 Límite base: %d | 📈 Límite con tolerancia: %d | 💪 Exceso permitido: %d',
                                                        $get('max_sales_per_month'),
                                                        $get('max_sales_per_month') * (1 + ($get('overage_percentage') / 100)),
                                                        ($get('max_sales_per_month') * (1 + ($get('overage_percentage') / 100))) - $get('max_sales_per_month')
                                                    )
                                                    : ''
                                            )
                                            ->visible(fn (callable $get) =>
                                                $get('overage_strategy') === 'soft_limit' && $get('max_sales_per_month')
                                            ),
                                    ])
                                    ->visible(fn (callable $get) => $get('overage_strategy') === 'soft_limit'),
                            ]),
                    ]),

                Forms\Components\Section::make('Resumen de Configuración')
                    ->description('Revisa la configuración antes de guardar')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Placeholder::make('configuration_summary')
                            ->label('Configuración Actual')
                            ->content(function (callable $get): string {
                                $strategy = $get('overage_strategy');
                                $strategyText = $strategy === 'soft_limit'
                                    ? '🟢 Flexible con ' . ($get('overage_percentage') ?? 20) . '% tolerancia'
                                    : '🔴 Estricto sin tolerancia';

                                return new \Illuminate\Support\HtmlString('
                                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Resumen de Límites</h4>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div>💰 Ventas: ' . ($get('max_sales_per_month') ?: 'Ilimitado') . '</div>
                                            <div>👥 Usuarios: ' . ($get('max_users') ?? 1) . '</div>
                                            <div>💾 Almacenamiento: ' . ($get('max_storage_mb') ?? 1024) . ' MB</div>
                                            <div>📦 Productos: ' . ($get('max_products') ?: 'Ilimitado') . '</div>
                                        </div>
                                        <div class="mt-3 pt-2 border-t border-blue-300 dark:border-blue-700">
                                            <div class="text-xs font-medium text-blue-800 dark:text-blue-200">
                                                Estrategia: ' . $strategyText . '
                                            </div>
                                            <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                                ⚡ El POS nunca se bloqueará - solo acciones administrativas
                                            </div>
                                        </div>
                                    </div>
                                ');
                            })
                            ->visible(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ])
            ->action(function (array $data, Model $record): void {
                // Build limits JSON array
                $limits = [
                    'monthly_sales' => $data['max_sales_per_month'] ?: null,
                    'users' => $data['max_users'],
                    'storage_mb' => $data['max_storage_mb'],
                    'products' => $data['max_products'] ?: null,
                ];

                $record->update([
                    'limits' => $limits,
                    'overage_strategy' => $data['overage_strategy'],
                    'overage_percentage' => $data['overage_percentage'],
                ]);

                $strategyText = $data['overage_strategy'] === 'soft_limit'
                    ? 'Flexible con ' . $data['overage_percentage'] . '% tolerancia'
                    : 'Estricta sin tolerancia';

                Notification::make()
                    ->title('✅ Configuración Guardada')
                    ->body("Los límites flexibles para '{$record->name}' han sido configurados con estrategia {$strategyText}")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalSubmitAction(fn () =>
                \Filament\Actions\StaticAction::make('save')
                    ->label('💾 Guardar Configuración')
                    ->color('success')
            )
            ->successNotificationTitle('Límites flexibles configurados correctamente');
    }

    public static function make(?string $name = null): static
    {
        $static = app(static::class, [
            'name' => $name ?? static::getDefaultName(),
        ]);

        $static->configure();

        return $static;
    }
}