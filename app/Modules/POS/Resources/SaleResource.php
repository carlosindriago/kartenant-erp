<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources;

use App\Filament\Actions\HasStandardActionGroup;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Resources\SaleResource\Pages;
use App\Modules\POS\Services\ReturnService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SaleResource extends Resource
{
    use HasStandardActionGroup;

    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'Venta';

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?string $navigationGroup = 'Punto de Venta';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        // En database-per-tenant, no necesitamos filtrar por tenant_id
        return parent::getEloquentQuery()
            ->with(['customer', 'user', 'items.product'])
            ->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Venta')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Número de Factura')
                            ->disabled(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'completed' => 'Completada',
                                'cancelled' => 'Cancelada',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->disabled()
                            ->prefix('$'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nº Factura')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->default('Cliente General')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cajero')
                    ->searchable()
                    ->sortable()
                    ->default('Sin asignar')
                    ->icon('heroicon-m-user-circle')
                    ->toggleable()
                    ->toggledHiddenByDefault(false),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método de Pago')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => '💵 Efectivo',
                        'card' => '💳 Tarjeta',
                        'transfer' => '🏦 Transferencia',
                        default => ucfirst($state),
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD', true)
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\IconColumn::make('has_returns')
                    ->label('Devoluciones')
                    ->boolean()
                    ->getStateUsing(fn (Sale $record) => $record->hasReturns())
                    ->trueIcon('heroicon-o-arrow-uturn-left')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Salud de Venta')
                    ->formatStateUsing(fn ($record) => self::calculateSaleHealthScore($record))
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->tooltip(fn ($record) => self::getSaleHealthTooltip($record))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
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
                // ACCESO RÁPIDO (Acciones principales)
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('Ver información completa de la venta'),

                Action::make('quick_return')
                    ->label('Devolución Rápida')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->tooltip('Procesar devolución rápidamente')
                    ->visible(fn (Sale $record) => $record->status === 'completed')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Razón de Devolución')
                            ->placeholder('Ej: Producto defectuoso, no es lo que esperaba, etc.')
                            ->rows(2)
                            ->required(),
                        Forms\Components\Select::make('refund_method')
                            ->label('Método de Reembolso')
                            ->options([
                                'cash' => '💵 Efectivo',
                                'card' => '💳 Tarjeta',
                                'credit_note' => '📄 Nota de Crédito',
                            ])
                            ->default('cash')
                            ->required(),
                    ])
                    ->action(function (Sale $record, array $data) {
                        try {
                            $returnService = app(ReturnService::class);

                            // Procesar devolución simple (primer ítem)
                            $firstItem = $record->items->first();
                            if (! $firstItem) {
                                Notification::make()
                                    ->title('Error en Devolución')
                                    ->body('No hay productos disponibles para devolver.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $itemsToReturn = [
                                $firstItem->id => [
                                    'quantity' => 1,
                                    'reason' => $data['reason'],
                                ],
                            ];

                            $saleReturn = $returnService->recordReturn(
                                $record,
                                $itemsToReturn,
                                $data['refund_method'],
                                $data['reason']
                            );

                            Notification::make()
                                ->title('Devolución Procesada')
                                ->body("Nota de Crédito: {$saleReturn->return_number}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error en Devolución')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ACCIONES ESPECÍFICAS DE VENTAS
                Action::make('manage_return')
                    ->label('Procesar Devolución Completa')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->tooltip('Proceso formal de devolución de múltiples productos')
                    ->visible(fn (Sale $record) => $record->status === 'completed' &&
                        auth()->user()->can('process_returns')
                    )
                    ->modalHeading('📦 Procesar Devolución de Productos')
                    ->modalDescription(fn (Sale $record) => "Venta: {$record->invoice_number} - Total: \${$record->total}")
                    ->modalWidth('7xl')
                    ->form(function (Sale $record) {
                        $schema = [];

                        $schema[] = Forms\Components\Textarea::make('general_reason')
                            ->label('Razón de la Devolución')
                            ->placeholder('Ej: Producto defectuoso, cambio de producto, etc.')
                            ->rows(2)
                            ->columnSpanFull();

                        $schema[] = Forms\Components\Select::make('refund_method')
                            ->label('Método de Reembolso')
                            ->helperText('Por defecto es el mismo método de pago usado en la venta original')
                            ->options([
                                'cash' => '💵 Efectivo',
                                'card' => '💳 Tarjeta',
                                'transfer' => '🏦 Transferencia',
                                'credit_note' => '📄 Nota de Crédito',
                            ])
                            ->default($record->payment_method)
                            ->required()
                            ->columnSpanFull();

                        $schema[] = Forms\Components\Section::make('Productos a Devolver')
                            ->description('Seleccione la cantidad a devolver de cada producto')
                            ->schema(function () use ($record) {
                                $fields = [];

                                foreach ($record->items as $item) {
                                    $lineTotal = $item->unit_price * $item->quantity;
                                    $fields[] = Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Placeholder::make("product_info_{$item->id}")
                                                ->label('Producto')
                                                ->content(new \Illuminate\Support\HtmlString("
                                                    <div class='space-y-1'>
                                                        <div class='font-semibold text-gray-900 dark:text-white'>{$item->product_name}</div>
                                                        <div class='text-sm text-gray-500'>
                                                            Precio: \${$item->unit_price} × {$item->quantity} = \${$lineTotal}
                                                        </div>
                                                    </div>
                                                "))
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make("items.{$item->id}.quantity")
                                                ->label('Cantidad a Devolver')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue($item->quantity)
                                                ->default(0)
                                                ->suffix("/ {$item->quantity}")
                                                ->reactive()
                                                ->live()
                                                ->columnSpan(1),

                                            Forms\Components\TextInput::make("items.{$item->id}.reason")
                                                ->label('Razón Específica')
                                                ->placeholder('Opcional')
                                                ->columnSpan(1),
                                        ]);
                                }

                                return $fields;
                            })
                            ->columnSpanFull();

                        return $schema;
                    })
                    ->action(function (Sale $record, array $data) {
                        try {
                            $returnService = app(ReturnService::class);

                            $itemsToReturn = [];
                            foreach ($data['items'] ?? [] as $itemId => $itemData) {
                                if (($itemData['quantity'] ?? 0) > 0) {
                                    $itemsToReturn[$itemId] = [
                                        'quantity' => $itemData['quantity'],
                                        'reason' => $itemData['reason'] ?? null,
                                    ];
                                }
                            }

                            if (empty($itemsToReturn)) {
                                Notification::make()
                                    ->title('No hay productos a devolver')
                                    ->body('Debe especificar al menos un producto con cantidad mayor a 0.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $saleReturn = $returnService->recordReturn(
                                $record,
                                $itemsToReturn,
                                $data['refund_method'] ?? 'cash',
                                $data['general_reason'] ?? null
                            );

                            $pdfUrl = route('tenant.pos.credit-note.pdf', [
                                'tenant' => \Spatie\Multitenancy\Models\Tenant::current()->domain,
                                'saleReturn' => $saleReturn->id,
                            ]);

                            Notification::make()
                                ->title('¡Devolución Procesada Exitosamente!')
                                ->body("Nota de Crédito: {$saleReturn->return_number} - Total: \${$saleReturn->total}")
                                ->success()
                                ->duration(10000)
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('📄 Descargar Nota de Crédito')
                                        ->url($pdfUrl)
                                        ->openUrlInNewTab()
                                        ->button(),
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('👁️ Ver en Navegador')
                                        ->url(route('tenant.pos.credit-note.view', [
                                            'tenant' => \Spatie\Multitenancy\Models\Tenant::current()->domain,
                                            'saleReturn' => $saleReturn->id,
                                        ]))
                                        ->openUrlInNewTab(),
                                ])
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al procesar devolución')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ACTION GROUP ESTÁNDAR
                static::getCompleteActionGroup(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSales::route('/'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }

    /**
     * Calculate sale health score (0-100)
     */
    public static function calculateSaleHealthScore($record): int
    {
        try {
            if (! $record) {
                return 0;
            }

            $cacheKey = "sale_health_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $score = 100;

                // Sale status health
                if ($record->status === 'cancelled') {
                    $score -= 50; // Venta cancelada
                } elseif ($record->status === 'pending') {
                    $score -= 20; // Venta pendiente
                }

                // Payment method health
                if (empty($record->payment_method)) {
                    $score -= 15; // Sin método de pago
                }

                // Customer assignment
                if (! $record->customer_id) {
                    $score -= 10; // Sin cliente asignado
                }

                // User assignment
                if (! $record->user_id) {
                    $score -= 10; // Sin cajero asignado
                }

                // Invoice number completeness
                if (empty($record->invoice_number)) {
                    $score -= 15; // Sin número de factura
                }

                // Total amount validation
                if ($record->total <= 0) {
                    $score -= 30; // Total inválido
                } elseif ($record->total < 10) { // Assuming minimum reasonable sale
                    $score -= 10; // Total muy bajo (posible error)
                }

                // Check for returns (negative factor)
                if ($record->hasReturns()) {
                    $score -= 20; // Tiene devoluciones
                }

                // Check sale items completeness
                try {
                    $itemsCount = $record->items()->count();
                    if ($itemsCount === 0) {
                        $score -= 40; // Sin ítems (venta vacía)
                    } elseif ($itemsCount < 2) {
                        $score -= 5; // Venta con solo un ítem
                    }
                } catch (\Exception $e) {
                    $score -= 25; // Error al obtener ítems
                }

                // Check recent sales for business health indicators
                try {
                    $todaySales = Sale::whereDate('created_at', today())->count();
                    $weekSales = Sale::whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])->count();

                    // Business health based on sales volume
                    if ($todaySales === 0 && $record->created_at->isToday()) {
                        // This is the first sale today, that's good!
                        $score += 5;
                    }

                    if ($weekSales < 5) {
                        $score -= 5; // Pocas ventas esta semana (negocio lento)
                    } elseif ($weekSales > 50) {
                        $score += 5; // Buen volumen de ventas semanales
                    }
                } catch (\Exception $e) {
                    // If we can't query sales, don't affect score
                }

                // Check for anomalies in sale timing
                $hour = $record->created_at->hour;
                if ($hour < 6 || $hour > 23) {
                    $score -= 5; // Venta en horario inusual
                }

                return max(0, min(100, $score));
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get detailed tooltip for sale health score
     */
    public static function getSaleHealthTooltip($record): string
    {
        try {
            if (! $record) {
                return 'Error al calcular salud';
            }

            $cacheKey = "sale_health_tooltip_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $factors = [];

                // Status analysis
                if ($record->status === 'cancelled') {
                    $factors[] = '🔴 Venta cancelada (-50)';
                } elseif ($record->status === 'pending') {
                    $factors[] = '🟡 Venta pendiente (-20)';
                } elseif ($record->status === 'completed') {
                    $factors[] = '🟢 Venta completada';
                } else {
                    $factors[] = '❓ Estado desconocido (-15)';
                }

                // Payment method analysis
                if (empty($record->payment_method)) {
                    $factors[] = '🔴 Sin método de pago (-15)';
                } else {
                    $methodMap = [
                        'cash' => '💵 Efectivo',
                        'card' => '💳 Tarjeta',
                        'transfer' => '🏦 Transferencia',
                    ];
                    $method = $methodMap[$record->payment_method] ?? ucfirst($record->payment_method);
                    $factors[] = "🟢 Método: {$method}";
                }

                // Customer analysis
                if (! $record->customer_id) {
                    $factors[] = '🟡 Sin cliente (-10)';
                } else {
                    $factors[] = '🟢 Con cliente asignado';
                }

                // User analysis
                if (! $record->user_id) {
                    $factors[] = '🟡 Sin cajero (-10)';
                } else {
                    $factors[] = '🟢 Cajero asignado';
                }

                // Invoice analysis
                if (empty($record->invoice_number)) {
                    $factors[] = '🔴 Sin factura (-15)';
                } else {
                    $factors[] = '🟢 Factura: '.$record->invoice_number;
                }

                // Total analysis
                if ($record->total <= 0) {
                    $factors[] = '🔴 Total inválido (-30)';
                } elseif ($record->total < 10) {
                    $factors[] = '🟡 Total muy bajo (-10)';
                } else {
                    $factors[] = '🟢 Total válido: $'.number_format($record->total, 2);
                }

                // Returns analysis
                if ($record->hasReturns()) {
                    $factors[] = '🟡 Con devoluciones (-20)';
                } else {
                    $factors[] = '🟢 Sin devoluciones';
                }

                // Items analysis
                try {
                    $itemsCount = $record->items()->count();
                    if ($itemsCount === 0) {
                        $factors[] = '🔴 Sin ítems (-40)';
                    } elseif ($itemsCount < 2) {
                        $factors[] = '🟡 Venta simple (-5)';
                    } else {
                        $factors[] = "🟢 {$itemsCount} ítems";
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Ítems: Error al verificar (-25)';
                }

                // Timing analysis
                $hour = $record->created_at->hour;
                if ($hour < 6 || $hour > 23) {
                    $factors[] = '🟡 Horario inusual (-5)';
                } else {
                    $factors[] = '🟢 Horario normal';
                }

                // Business health indicators
                try {
                    $todaySales = Sale::whereDate('created_at', today())->count();
                    $weekSales = Sale::whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])->count();

                    $factors[] = "📊 Ventas hoy: {$todaySales}";
                    $factors[] = "📊 Ventas semana: {$weekSales}";

                    if ($weekSales < 5) {
                        $factors[] = '🟡 Negocio lento (-5)';
                    } elseif ($weekSales > 50) {
                        $factors[] = '🟢 Buen volumen (+5)';
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Métricas: No disponible';
                }

                $score = self::calculateSaleHealthScore($record);
                $color = $score >= 80 ? '🟢' : ($score >= 60 ? '🟡' : '🔴');

                return $color." Salud: {$score}/100\n\n".implode("\n", $factors);
            });
        } catch (\Exception $e) {
            return 'Error al calcular detalles de salud';
        }
    }
}
