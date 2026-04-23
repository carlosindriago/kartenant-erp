<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\ProductResource\Pages;

use App\Modules\ProductResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Registrar Entrada de Stock
            Actions\Action::make('register_entry')
                ->label('Registrar Entrada')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(function () {
                    $tenant = Filament::getTenant();

                    return route('filament.app.resources.stock-movements.create-entry', [
                        'tenant' => $tenant,
                        'product' => $this->record->id,
                    ]);
                })
                ->tooltip('Registrar entrada de mercadería para este producto'),

            // Registrar Salida de Stock
            Actions\Action::make('register_exit')
                ->label('Registrar Salida')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->url(function () {
                    $tenant = Filament::getTenant();

                    return route('filament.app.resources.stock-movements.create-exit', [
                        'tenant' => $tenant,
                        'product' => $this->record->id,
                    ]);
                })
                ->tooltip('Registrar salida de mercadería de este producto'),

            // Editar Producto
            Actions\EditAction::make()
                ->label('Editar Producto')
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),

            // Inhabilitar/Habilitar Producto
            Actions\Action::make('toggle_status')
                ->label(fn () => $this->record->status ? 'Inhabilitar' : 'Habilitar')
                ->icon(fn () => $this->record->status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->status ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->status ? 'Inhabilitar Producto' : 'Habilitar Producto')
                ->modalDescription(fn () => $this->record->status
                    ? 'El producto se ocultará de las ventas y reportes. Puedes habilitarlo nuevamente en cualquier momento.'
                    : 'El producto volverá a estar disponible para ventas y reportes.')
                ->modalSubmitActionLabel(fn () => $this->record->status ? 'Inhabilitar' : 'Habilitar')
                ->action(function () {
                    Filament::getTenant()?->makeCurrent();

                    $this->record->status = ! $this->record->status;
                    $this->record->save();

                    Notification::make()
                        ->title($this->record->status ? 'Producto Habilitado' : 'Producto Inhabilitado')
                        ->body($this->record->status
                            ? 'El producto está ahora disponible para ventas.'
                            : 'El producto ha sido inhabilitado correctamente.')
                        ->success()
                        ->send();

                    // Refrescar la página para actualizar el estado
                    $this->redirect(static::getUrl('view', ['record' => $this->record]));
                })
                ->tooltip(fn () => $this->record->status
                    ? 'Deshabilitar este producto (no estará disponible para ventas)'
                    : 'Habilitar este producto (estará disponible para ventas)'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $product = $this->record;

        // Calcular estadísticas
        $stats = $this->getProductStats($product);

        return $infolist
            ->schema([
                // Información Básica del Producto
                Components\Section::make('📦 Información del Producto')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('Nombre')
                                    ->weight('bold')
                                    ->size(Components\TextEntry\TextEntrySize::Large),

                                Components\TextEntry::make('sku')
                                    ->label('SKU')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                            ]),

                        Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull()
                            ->placeholder('Sin descripción'),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('category.name')
                                    ->label('Categoría')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('tax.name')
                                    ->label('Impuesto')
                                    ->badge()
                                    ->color('warning'),

                                Components\TextEntry::make('unit')
                                    ->label('Unidad de Medida')
                                    ->badge(),
                            ]),
                    ])
                    ->collapsible(),

                // Métricas Financieras Clave
                Components\Section::make('💰 Métricas Financieras')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('price')
                                    ->label('Precio de Venta')
                                    ->money('ARS')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color('success'),

                                Components\TextEntry::make('cost_price')
                                    ->label('Costo')
                                    ->money('ARS')
                                    ->color('danger'),

                                Components\TextEntry::make('margin')
                                    ->label('Margen')
                                    ->state(function () use ($product) {
                                        if (! $product->cost_price || $product->cost_price <= 0) {
                                            return 'N/D';
                                        }
                                        $margin = (($product->price - $product->cost_price) / $product->price) * 100;

                                        return round($margin, 1).'%';
                                    })
                                    ->badge()
                                    ->color(function () use ($product) {
                                        if (! $product->cost_price || $product->cost_price <= 0) {
                                            return 'gray';
                                        }
                                        $margin = (($product->price - $product->cost_price) / $product->price) * 100;
                                        if ($margin >= 25) {
                                            return 'success';
                                        }
                                        if ($margin >= 15) {
                                            return 'warning';
                                        }

                                        return 'danger';
                                    }),

                                Components\TextEntry::make('inventory_value')
                                    ->label('Valor en Inventario')
                                    ->state(fn () => number_format($product->stock * $product->price, 2).' ARS')
                                    ->weight('bold')
                                    ->color('primary'),
                            ]),
                    ]),

                // Inventario
                Components\Section::make('📊 Inventario')
                    ->schema([
                        Components\Section::make('Stock Actual')
                            ->description('Estado y disponibilidad del producto')
                            ->compact()
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('stock')
                                            ->label('Stock Actual')
                                            ->formatStateUsing(fn ($state) => number_format($state).' unidades')
                                            ->size(Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold')
                                            ->badge()
                                            ->color(function () use ($product) {
                                                if ($product->stock <= 0) {
                                                    return 'danger';
                                                }
                                                if ($product->stock <= $product->min_stock) {
                                                    return 'warning';
                                                }

                                                return 'success';
                                            }),

                                        Components\TextEntry::make('min_stock')
                                            ->label('Stock Mínimo')
                                            ->formatStateUsing(fn ($state) => number_format($state).' unidades'),

                                        Components\TextEntry::make('stock_status')
                                            ->label('Estado de Stock')
                                            ->state(function () use ($product) {
                                                if ($product->stock <= 0) {
                                                    return '🔴 Sin Stock';
                                                }
                                                if ($product->stock <= $product->min_stock) {
                                                    return '🟡 Stock Bajo';
                                                }

                                                return '🟢 Stock Normal';
                                            })
                                            ->badge()
                                            ->color(function () use ($product) {
                                                if ($product->stock <= 0) {
                                                    return 'danger';
                                                }
                                                if ($product->stock <= $product->min_stock) {
                                                    return 'warning';
                                                }

                                                return 'success';
                                            }),

                                        Components\TextEntry::make('days_of_stock')
                                            ->label('Días de Inventario')
                                            ->state(function () use ($stats) {
                                                if ($stats['avg_daily_sales'] <= 0) {
                                                    return 'N/D';
                                                }
                                                $days = round($this->record->stock / $stats['avg_daily_sales']);

                                                return $days.' días';
                                            })
                                            ->badge()
                                            ->color(fn () => $stats['avg_daily_sales'] > 0 ? 'info' : 'gray'),
                                    ]),
                            ]),

                        Components\Section::make('Historial de Reabastecimiento')
                            ->description('Información sobre cuándo y cuánto se ha agregado al inventario')
                            ->compact()
                            ->schema([
                                Components\Grid::make(3)
                                    ->schema([
                                        Components\TextEntry::make('created_at')
                                            ->label('Agregado al Inventario')
                                            ->icon('heroicon-o-calendar')
                                            ->state(fn () => $product->created_at->format('d/m/Y H:i').' ('.$product->created_at->diffForHumans().')'),

                                        Components\TextEntry::make('last_entry_date')
                                            ->label('Última Compra/Entrada')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->state(function () use ($product) {
                                                $entry = \App\Modules\Inventory\Models\StockMovement::where('product_id', $product->id)
                                                    ->where('type', 'entrada')
                                                    ->where('reason', '!=', 'Devolución de venta')
                                                    ->latest()
                                                    ->first();

                                                if (! $entry) {
                                                    return 'Sin entradas registradas';
                                                }

                                                return $entry->created_at->format('d/m/Y H:i').' ('.$entry->created_at->diffForHumans().')';
                                            }),

                                        Components\TextEntry::make('last_entry_qty')
                                            ->label('Cantidad de la Última Entrada')
                                            ->icon('heroicon-o-cube')
                                            ->state(function () use ($product) {
                                                $entry = \App\Modules\Inventory\Models\StockMovement::where('product_id', $product->id)
                                                    ->where('type', 'entrada')
                                                    ->where('reason', '!=', 'Devolución de venta')
                                                    ->latest()
                                                    ->first();

                                                return $entry ? '+'.number_format($entry->quantity).' unidades' : 'N/D';
                                            })
                                            ->badge()
                                            ->color('success'),
                                    ]),
                            ]),
                    ]),

                // Estadísticas de Ventas
                Components\Section::make('📈 Estadísticas de Ventas')
                    ->description('Datos del último mes')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('total_sold')
                                    ->label('Total Vendido (Mes)')
                                    ->state(fn () => number_format($stats['total_sold']).' unidades')
                                    ->badge()
                                    ->color('success')
                                    ->size(Components\TextEntry\TextEntrySize::Large),

                                Components\TextEntry::make('total_revenue')
                                    ->label('Ingresos Generados')
                                    ->state(fn () => '$'.number_format($stats['total_revenue'], 2))
                                    ->weight('bold')
                                    ->color('success')
                                    ->size(Components\TextEntry\TextEntrySize::Large),

                                Components\TextEntry::make('avg_daily_sales')
                                    ->label('Promedio Diario')
                                    ->state(fn () => number_format($stats['avg_daily_sales'], 1).' un/día')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('sale_frequency')
                                    ->label('Frecuencia de Venta')
                                    ->state(fn () => $stats['total_transactions'].' transacciones')
                                    ->badge()
                                    ->color('primary'),
                            ]),
                    ]),

                // Análisis Temporal
                Components\Section::make('⏰ Cuándo se Vende Más')
                    ->description('Análisis de patrones de venta')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('best_selling_day')
                                    ->label('Día de Mayor Venta')
                                    ->state(function () use ($stats) {
                                        if (empty($stats['best_day'])) {
                                            return 'Sin datos';
                                        }
                                        $days = [
                                            'Monday' => 'Lunes',
                                            'Tuesday' => 'Martes',
                                            'Wednesday' => 'Miércoles',
                                            'Thursday' => 'Jueves',
                                            'Friday' => 'Viernes',
                                            'Saturday' => 'Sábado',
                                            'Sunday' => 'Domingo',
                                        ];
                                        $day = $days[$stats['best_day']['day']] ?? $stats['best_day']['day'];

                                        return "📅 {$day} ({$stats['best_day']['quantity']} unidades)";
                                    })
                                    ->badge()
                                    ->color('success'),

                                Components\TextEntry::make('best_selling_hour')
                                    ->label('Hora de Mayor Venta')
                                    ->state(function () use ($stats) {
                                        if (empty($stats['best_hour'])) {
                                            return 'Sin datos';
                                        }
                                        $hour = $stats['best_hour']['hour'];
                                        $quantity = $stats['best_hour']['quantity'];

                                        return "🕐 {$hour}:00 - ".($hour + 1).":00 ({$quantity} unidades)";
                                    })
                                    ->badge()
                                    ->color('primary'),
                            ]),

                        Components\TextEntry::make('selling_pattern')
                            ->label('Patrón de Venta')
                            ->state(function () use ($stats) {
                                $daysInInventory = $stats['days_in_inventory'];
                                $daysAnalyzed = $stats['days_analyzed'];
                                $avgSales = $stats['avg_daily_sales'];
                                $salesVelocity = $stats['sales_velocity'];
                                $daysWithSales = $stats['days_with_sales'];

                                // Producto muy nuevo (menos de 7 días)
                                if ($daysInInventory < 7) {
                                    if ($stats['total_sold'] > 0) {
                                        return "🆕 **Producto Nuevo** - {$stats['total_sold']} ventas en {$daysInInventory} días - Requiere más tiempo para análisis definitivo";
                                    }

                                    return "🆕 **Producto Nuevo** - Sin ventas aún en {$daysInInventory} días - Esperar más tiempo antes de evaluar";
                                }

                                // Sin ventas después de período razonable
                                if ($stats['total_sold'] == 0 && $daysInInventory >= 7) {
                                    return "⚠️ **Sin Ventas** - {$daysInInventory} días en inventario sin ventas - Requiere atención";
                                }

                                // Análisis por velocidad de venta (cuando se vende)
                                if ($salesVelocity >= 10) {
                                    return "🔥 **Alta Rotación** - {$avgSales} un/día promedio - Ventas en {$daysWithSales}/{$daysAnalyzed} días";
                                }

                                if ($salesVelocity >= 5) {
                                    return "📈 **Buena Rotación** - {$avgSales} un/día promedio - Ventas en {$daysWithSales}/{$daysAnalyzed} días";
                                }

                                if ($salesVelocity >= 2) {
                                    return "📊 **Rotación Media** - {$avgSales} un/día promedio - Ventas en {$daysWithSales}/{$daysAnalyzed} días";
                                }

                                // Rotación baja pero con contexto
                                $percentage = $daysAnalyzed > 0 ? round(($daysWithSales / $daysAnalyzed) * 100) : 0;
                                if ($avgSales >= 0.3) {
                                    return "⏳ **Rotación Baja** - {$avgSales} un/día - Vende solo el {$percentage}% de los días ({$daysWithSales}/{$daysAnalyzed})";
                                }

                                return "🐌 **Rotación Muy Baja** - {$avgSales} un/día - Vende solo el {$percentage}% de los días ({$daysWithSales}/{$daysAnalyzed})";
                            })
                            ->columnSpanFull()
                            ->badge()
                            ->color(function () use ($stats) {
                                $daysInInventory = $stats['days_in_inventory'];

                                // Productos nuevos en azul
                                if ($daysInInventory < 7) {
                                    return 'info';
                                }

                                // Sin ventas en rojo
                                if ($stats['total_sold'] == 0) {
                                    return 'danger';
                                }

                                // Por velocidad de venta
                                if ($stats['sales_velocity'] >= 5) {
                                    return 'success';
                                }
                                if ($stats['sales_velocity'] >= 2) {
                                    return 'primary';
                                }
                                if ($stats['avg_daily_sales'] >= 0.3) {
                                    return 'warning';
                                }

                                return 'danger';
                            }),
                    ]),

                // Top Clientes
                Components\Section::make('👥 Top Clientes que lo Compran')
                    ->description('Los 5 clientes que más compran este producto')
                    ->schema([
                        Components\ViewEntry::make('top_customers_table')
                            ->label('')
                            ->view('filament.infolists.entries.top-customers-table')
                            ->state(fn () => $stats['top_customers'])
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn () => count($stats['top_customers']) > 0),

                // Historial de Ventas Recientes
                Components\Section::make('📜 Historial de Ventas Recientes')
                    ->description('Últimas 10 transacciones')
                    ->schema([
                        Components\ViewEntry::make('recent_sales_table')
                            ->label('')
                            ->view('filament.infolists.entries.recent-sales-table')
                            ->state(fn () => $stats['recent_sales'])
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn () => count($stats['recent_sales']) > 0),

                // Recomendaciones
                Components\Section::make('💡 Insights y Recomendaciones')
                    ->schema([
                        Components\TextEntry::make('insights')
                            ->label('')
                            ->state(function () use ($product, $stats) {
                                $insights = [];
                                $daysInInventory = $stats['days_in_inventory'];
                                $daysAnalyzed = $stats['days_analyzed'];

                                // === ANÁLISIS DE PRODUCTO NUEVO ===
                                if ($daysInInventory < 7) {
                                    $insights[] = "🆕 **PRODUCTO RECIENTE**: Agregado hace {$daysInInventory} días. Los análisis son preliminares y necesitan más tiempo para ser concluyentes.";

                                    if ($stats['total_sold'] > 0) {
                                        $insights[] = "✨ **INICIO PROMETEDOR**: Ya tiene {$stats['total_sold']} ventas. Monitorear en las próximas semanas.";
                                    } else {
                                        $insights[] = '⏳ **EN EVALUACIÓN**: Sin ventas aún. Normal para productos nuevos. Esperar al menos 2 semanas antes de tomar decisiones.';
                                    }

                                    return implode("\n\n", $insights);
                                }

                                // === ANÁLISIS DE STOCK (CRÍTICO) ===
                                if ($product->stock <= 0) {
                                    if ($stats['avg_daily_sales'] > 0) {
                                        $lostSales = round($stats['avg_daily_sales'] * 7, 1);
                                        $lostRevenue = $lostSales * $product->price;
                                        $insights[] = "🔴 **ALERTA CRÍTICA - SIN STOCK**: Producto con demanda ({$stats['avg_daily_sales']} un/día) sin inventario. Pérdida estimada: {$lostSales} unidades (~\$".number_format($lostRevenue, 0).') en 1 semana. **REABASTECER URGENTEMENTE**.';
                                    } else {
                                        $insights[] = '🔴 **SIN STOCK**: Reabastecer para evaluar demanda real.';
                                    }
                                } elseif ($product->stock <= $product->min_stock) {
                                    if ($stats['avg_daily_sales'] > 0) {
                                        $daysLeft = round($product->stock / $stats['avg_daily_sales']);
                                        $insights[] = "🟡 **STOCK BAJO**: Quedan aproximadamente {$daysLeft} días de inventario. Programar reabastecimiento pronto.";
                                    } else {
                                        $insights[] = '🟡 **STOCK BAJO**: Por debajo del mínimo configurado.';
                                    }
                                }

                                // === ANÁLISIS DE RENTABILIDAD ===
                                if ($product->cost_price > 0) {
                                    $margin = (($product->price - $product->cost_price) / $product->price) * 100;
                                    if ($margin < 15) {
                                        $suggestedPrice = $product->cost_price / 0.75; // 25% margen
                                        $insights[] = '💰 **MARGEN BAJO**: Margen del '.round($margin, 1).'%. **ACCIÓN REQUERIDA**: Considerar aumentar precio a $'.number_format($suggestedPrice, 2).' (margen 25%) o negociar mejor costo con proveedor.';
                                    } elseif ($margin >= 40 && $stats['avg_daily_sales'] < 1) {
                                        $insights[] = '💡 **OPORTUNIDAD**: Margen alto ('.round($margin, 1).'%) pero ventas bajas. Considerar reducir precio ligeramente para aumentar volumen.';
                                    }
                                }

                                // === ANÁLISIS DE DEMANDA Y ROTACIÓN ===
                                if ($stats['total_sold'] == 0 && $daysAnalyzed >= 14) {
                                    $inventoryValue = $product->stock * $product->cost_price;
                                    $insights[] = "⚠️ **PRODUCTO ESTANCADO**: Sin ventas en {$daysAnalyzed} días. Capital inmovilizado: \$".number_format($inventoryValue, 0).'. **ACCIONES SUGERIDAS**: 1) Promoción/descuento, 2) Reposicionamiento, 3) Considerar descontinuar si persiste.';
                                } elseif ($stats['sales_velocity'] >= 5) {
                                    $insights[] = '🔥 **PRODUCTO ESTRELLA**: Alta demanda constante. Asegurar que el stock sea suficiente y que el margen sea óptimo.';
                                } elseif ($stats['avg_daily_sales'] > 0 && $stats['days_with_sales'] < ($daysAnalyzed * 0.3)) {
                                    $percentage = round(($stats['days_with_sales'] / $daysAnalyzed) * 100);
                                    $insights[] = "📊 **DEMANDA IRREGULAR**: Solo vende el {$percentage}% de los días. Investigar: ¿Es producto de temporada? ¿Falta de visibilidad? ¿Competencia?";
                                }

                                // === ANÁLISIS DE INVENTARIO EXCESIVO ===
                                if ($stats['avg_daily_sales'] > 0) {
                                    $daysOfStock = $product->stock / $stats['avg_daily_sales'];
                                    if ($daysOfStock > 90) {
                                        $excessUnits = round($product->stock - ($stats['avg_daily_sales'] * 60));
                                        $tiedCapital = $excessUnits * $product->cost_price;
                                        $insights[] = '📦 **SOBREINVENTARIO**: Stock para '.round($daysOfStock)." días. Exceso de ~{$excessUnits} unidades (\$".number_format($tiedCapital, 0).' inmovilizados). **RECOMENDACIÓN**: Reducir pedidos futuros.';
                                    } elseif ($daysOfStock < 14 && $stats['sales_velocity'] >= 2) {
                                        $insights[] = '⚡ **STOCK JUSTO**: Solo '.round($daysOfStock).' días de inventario con buena rotación. Monitorear de cerca para evitar quiebres.';
                                    }
                                }

                                // === ANÁLISIS DE PRECIO Y COMPETITIVIDAD ===
                                if ($stats['days_with_sales'] > 5 && $stats['sales_velocity'] < 2 && $product->price > 0) {
                                    $insights[] = '💭 **EVALUAR COMPETITIVIDAD**: Ventas lentas pese a tener movimiento. Verificar si el precio es competitivo en el mercado.';
                                }

                                // === TODO BIEN ===
                                if (empty($insights)) {
                                    return '✅ **PRODUCTO ÓPTIMO**: Buen desempeño de ventas, stock adecuado y margen saludable. Continuar monitoreando.';
                                }

                                return implode("\n\n", $insights);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getProductStats($product): array
    {
        // Usar conexión tenant explícitamente
        $connection = DB::connection('tenant');

        // Obtener ventas del último mes
        $sales = $connection->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subMonth())
            ->select([
                'sale_items.quantity',
                'sale_items.unit_price',
                'sales.created_at',
                'sales.invoice_number',
                'sales.customer_id',
            ])
            ->get();

        $totalSold = $sales->sum('quantity');
        $totalRevenue = $sales->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
        $avgDailySales = $totalSold / 30;

        // Mejor día de venta (PostgreSQL compatible)
        $bestDay = $connection->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subMonth())
            ->select([
                DB::raw("TRIM(TO_CHAR(sales.created_at, 'Day')) as day"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
            ])
            ->groupBy('day')
            ->orderByDesc('quantity')
            ->first();

        // Mejor hora de venta (PostgreSQL compatible)
        $bestHour = $connection->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subMonth())
            ->select([
                DB::raw('EXTRACT(HOUR FROM sales.created_at) as hour'),
                DB::raw('SUM(sale_items.quantity) as quantity'),
            ])
            ->groupBy('hour')
            ->orderByDesc('quantity')
            ->first();

        // Top clientes
        $topCustomers = $connection->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subMonth())
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_spent'),
                DB::raw('MAX(sales.created_at) as last_purchase'),
            ])
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($customer) use ($connection, $product) {
                // Obtener el ID de la última venta de este cliente para este producto
                $lastSale = $connection->table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->where('sale_items.product_id', $product->id)
                    ->where('sales.customer_id', $customer->customer_id)
                    ->where('sales.status', 'completed')
                    ->orderByDesc('sales.created_at')
                    ->select('sales.id')
                    ->first();

                return [
                    'customer_name' => $customer->customer_name,
                    'total_quantity' => $customer->total_quantity,
                    'total_spent' => $customer->total_spent,
                    'last_purchase' => $customer->last_purchase,
                    'last_sale_id' => $lastSale?->id,
                ];
            })
            ->toArray();

        // Ventas recientes
        $recentSales = $connection->table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->select([
                'sales.id as sale_id',
                'sales.created_at as date',
                'sale_items.quantity',
                'sale_items.unit_price',
                DB::raw('(sale_items.quantity * sale_items.unit_price) as total'),
                'sales.invoice_number',
            ])
            ->orderByDesc('sales.created_at')
            ->limit(10)
            ->get()
            ->map(fn ($sale) => [
                'sale_id' => $sale->sale_id,
                'date' => $sale->date,
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'total' => $sale->total,
                'sale_number' => $sale->invoice_number,
            ])
            ->toArray();

        // Calcular métricas de tiempo
        $daysInInventory = max(1, $product->created_at->diffInDays(now()));
        $daysAnalyzed = min(30, $daysInInventory); // Analizamos máximo 30 días o menos si es nuevo
        $avgDailySalesAdjusted = $daysAnalyzed > 0 ? $totalSold / $daysAnalyzed : 0;

        // Calcular días con ventas reales (no días sin actividad)
        $daysWithSales = $sales->pluck('created_at')
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->count();

        // Velocidad de venta (cuando hay actividad)
        $salesVelocity = $daysWithSales > 0 ? $totalSold / $daysWithSales : 0;

        return [
            'total_sold' => $totalSold,
            'total_revenue' => $totalRevenue,
            'avg_daily_sales' => $avgDailySalesAdjusted,
            'total_transactions' => $sales->count(),
            'days_in_inventory' => $daysInInventory,
            'days_analyzed' => $daysAnalyzed,
            'days_with_sales' => $daysWithSales,
            'sales_velocity' => $salesVelocity,
            'best_day' => $bestDay ? [
                'day' => $bestDay->day,
                'quantity' => $bestDay->quantity,
            ] : null,
            'best_hour' => $bestHour ? [
                'hour' => $bestHour->hour,
                'quantity' => $bestHour->quantity,
            ] : null,
            'top_customers' => $topCustomers,
            'recent_sales' => $recentSales,
        ];
    }
}
