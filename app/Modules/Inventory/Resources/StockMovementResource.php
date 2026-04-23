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

use App\Filament\Actions\HasStandardActionGroup;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Resources\StockMovementResource\Pages\CreateStockEntry;
use App\Modules\Inventory\Resources\StockMovementResource\Pages\CreateStockExit;
use App\Modules\Inventory\Resources\StockMovementResource\Pages\ListStockMovements;
use App\Modules\Inventory\Resources\StockMovementResource\Pages\StockMovementSummary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class StockMovementResource extends Resource
{
    use HasStandardActionGroup;

    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Movimientos';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    // Disable Filament's tenant scoping to avoid ownership relationship checks
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
                // Este recurso es principalmente de solo lectura
                // Los movimientos se crean automáticamente
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('N° Documento')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-document-text'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->product->sku ?? null),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—')
                    ->visible(fn ($record) => $record && $record->type === 'entrada'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entrada' => 'success',
                        'salida' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => StockMovement::TYPES[$state] ?? $state),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->badge()
                    ->color(fn ($record): string => $record->type === 'entrada' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($record): string => $record->type === 'entrada' ? "+{$record->quantity}" : "-{$record->quantity}"
                    ),

                Tables\Columns\TextColumn::make('previous_stock')
                    ->label('Stock Anterior')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('new_stock')
                    ->label('Stock Nuevo')
                    ->numeric()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Registrado por')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Salud del Movimiento')
                    ->formatStateUsing(fn ($record) => self::calculateStockMovementHealthScore($record))
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->tooltip(fn ($record) => self::getStockMovementHealthTooltip($record))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Movimiento')
                    ->options(StockMovement::TYPES),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('date_range')
                    ->label('Rango de Fechas')
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
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('Ver información completa del movimiento'),

                // ACCIONES ESPECÍFICAS DE MOVIMIENTOS
                Action::make('download_thermal')
                    ->label('Ticket Térmico')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->tooltip('Descargar ticket 80mm')
                    ->url(fn (StockMovement $record): string => route('tenant.stock-movements.download', [
                        'record' => $record->id,
                        'format' => 'thermal',
                    ]))
                    ->openUrlInNewTab(),

                Action::make('download_a4')
                    ->label('PDF A4')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->tooltip('Descargar documento A4')
                    ->url(fn (StockMovement $record): string => route('tenant.stock-movements.download', [
                        'record' => $record->id,
                        'format' => 'a4',
                    ]))
                    ->openUrlInNewTab(),

                Action::make('view_product_history')
                    ->label('Historial del Producto')
                    ->icon('heroicon-o-clock')
                    ->color('secondary')
                    ->tooltip('Ver todos los movimientos de este producto')
                    ->visible(fn ($record) => $record && $record->product)
                    ->url(fn (StockMovement $record): string => route('tenant.stock-movements.index', [
                        'product' => $record->product_id,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('create_correction')
                    ->label('Crear Corrección')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Crear movimiento de corrección')
                    ->visible(fn (StockMovement $record) => auth()->user()->can('create_stock_correction') &&
                        $record && $record->product
                    )
                    ->form([
                        Forms\Components\Textarea::make('correction_reason')
                            ->label('Razón de la Corrección')
                            ->placeholder('Describe por qué se necesita esta corrección')
                            ->rows(3)
                            ->required(),
                        Forms\Components\Select::make('correction_type')
                            ->label('Tipo de Corrección')
                            ->options([
                                'manual_error' => 'Error Manual',
                                'system_error' => 'Error del Sistema',
                                'inventory_difference' => 'Diferencia de Inventario',
                                'damage_adjustment' => 'Ajuste por Daño',
                                'other' => 'Otro',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('correction_quantity')
                            ->label('Cantidad de Corrección')
                            ->helperText('Indica la cantidad a ajustar (positivos suman, negativos restan)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (StockMovement $record, array $data) {
                        try {
                            // Lógica para crear movimiento de corrección
                            $correctionType = $data['correction_type'] === 'manual_error' ? 'entrada' : 'salida';

                            $correctionMovement = StockMovement::create([
                                'product_id' => $record->product_id,
                                'type' => $correctionType,
                                'quantity' => abs($data['correction_quantity']),
                                'previous_stock' => $record->new_stock ?? $record->product->stock,
                                'new_stock' => $record->new_stock + $data['correction_quantity'],
                                'reason' => "CORRECCIÓN: {$data['correction_reason']} (Ref: Mov #{$record->id})",
                                'reference' => "CORRECCIÓN-{$record->id}",
                                'user_id' => auth()->id(),
                                'created_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Corrección Creada')
                                ->body("Movimiento de corrección #{$correctionMovement->id} creado exitosamente")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error en Corrección')
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
                        ->color('success')
                        ->icon('heroicon-o-document-arrow-down'),
                ]),
            ])
            ->recordUrl(
                fn (StockMovement $record): string => static::getUrl('view', ['record' => $record->id])
            )
            ->emptyStateHeading('No hay movimientos registrados')
            ->emptyStateDescription('Los movimientos aparecerán cuando hagas ventas, compras o ajustes de inventario')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
            'view' => StockMovementSummary::route('/{record}'),
            'create-entry' => CreateStockEntry::route('/entry/create'),
            'create-exit' => CreateStockExit::route('/exit/create'),
        ];
    }

    public static function canCreate(): bool
    {
        return true; // Ahora permitimos creación manual con páginas específicas
    }

    public static function canEdit($record): bool
    {
        return false; // Los movimientos son inmutables para auditoría
    }

    public static function canDelete($record): bool
    {
        return false; // Los movimientos no se pueden eliminar para auditoría
    }

    /**
     * Calculate stock movement health score (0-100)
     */
    public static function calculateStockMovementHealthScore($record): int
    {
        try {
            if (! $record) {
                return 0;
            }

            $cacheKey = "stock_movement_health_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $score = 100;

                // Movement type validation
                if (empty($record->type)) {
                    $score -= 50; // Sin tipo de movimiento
                } elseif (! in_array($record->type, ['entrada', 'salida'])) {
                    $score -= 30; // Tipo inválido
                }

                // Quantity validation
                if ($record->quantity <= 0) {
                    $score -= 40; // Cantidad inválida
                } elseif ($record->quantity > 10000) {
                    $score -= 10; // Cantidad sospechosa (posible error)
                }

                // Reason completeness
                if (empty($record->reason)) {
                    $score -= 20; // Sin motivo (malas prácticas)
                } elseif (strlen($record->reason) < 5) {
                    $score -= 5; // Motivo muy corto
                }

                // Reference validation
                if (empty($record->reference)) {
                    $score -= 10; // Sin referencia
                }

                // Document validation
                if (empty($record->document_number)) {
                    $score -= 15; // Sin número de documento
                }

                // Product relationship health
                if (! $record->product_id) {
                    $score -= 45; // Sin producto (crítico)
                } elseif (! $record->product) {
                    $score -= 30; // Producto no existe (datos corruptos)
                }

                // Stock consistency check
                try {
                    if ($record->type === 'entrada') {
                        $expectedNewStock = $record->previous_stock + $record->quantity;
                        if ($record->new_stock !== $expectedNewStock) {
                            $score -= 35; // Inconsistencia en stock de entrada
                        }
                    } elseif ($record->type === 'salida') {
                        $expectedNewStock = $record->previous_stock - $record->quantity;
                        if ($record->new_stock !== $expectedNewStock) {
                            $score -= 35; // Inconsistencia en stock de salida
                        }
                    }

                    // Negative stock warning
                    if ($record->new_stock < 0) {
                        $score -= 25; // Stock negativo (error crítico)
                    } elseif ($record->new_stock < 0) {
                        $score -= 15; // Stock en nivel crítico
                    }
                } catch (\Exception $e) {
                    $score -= 20; // Error al verificar consistencia
                }

                // Supplier validation (for entries)
                if ($record->type === 'entrada') {
                    if (! $record->supplier_id) {
                        $score -= 15; // Entrada sin proveedor
                    } elseif (! $record->supplier) {
                        $score -= 10; // Proveedor no existe
                    }
                }

                // User assignment
                if (! $record->user_id) {
                    $score -= 10; // Sin usuario asignado
                }

                // Movement timing analysis
                try {
                    $hour = $record->created_at->hour;
                    if ($hour < 7 || $hour > 22) {
                        $score -= 5; // Movimiento en horario inusual
                    }

                    // Check for duplicate movements
                    $possibleDuplicates = StockMovement::where('product_id', $record->product_id)
                        ->where('quantity', $record->quantity)
                        ->where('type', $record->type)
                        ->whereBetween('created_at', [
                            $record->created_at->copy()->subMinutes(5),
                            $record->created_at->copy()->addMinutes(5),
                        ])
                        ->where('id', '!=', $record->id)
                        ->count();

                    if ($possibleDuplicates > 0) {
                        $score -= 20; // Posible movimiento duplicado
                    }
                } catch (\Exception $e) {
                    // Error al verificar timing - no afectar score significativamente
                }

                // Movement frequency analysis
                try {
                    $recentMovements = StockMovement::where('product_id', $record->product_id)
                        ->where('created_at', '>', now()->subDays(7))
                        ->count();

                    if ($recentMovements > 50) {
                        $score -= 10; // Demasiados movimientos recientes (posible sistema de stock con problemas)
                    }
                } catch (\Exception $e) {
                    // Error al analizar frecuencia
                }

                // Check for corrections (negative movements for same product)
                try {
                    $correctionMovements = StockMovement::where('product_id', $record->product_id)
                        ->where('type', $record->type === 'entrada' ? 'salida' : 'entrada')
                        ->whereBetween('created_at', [
                            $record->created_at->copy()->subHours(24),
                            $record->created_at->copy()->addHours(24),
                        ])
                        ->count();

                    if ($correctionMovements > 0) {
                        $score -= 15; // Hay correcciones cercanas (posible error original)
                    }
                } catch (\Exception $e) {
                    // Error al verificar correcciones
                }

                return max(0, min(100, $score));
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get detailed tooltip for stock movement health score
     */
    public static function getStockMovementHealthTooltip($record): string
    {
        try {
            if (! $record) {
                return 'Error al calcular salud';
            }

            $cacheKey = "stock_movement_health_tooltip_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $factors = [];

                // Type analysis
                if (empty($record->type)) {
                    $factors[] = '🔴 Sin tipo (-50)';
                } elseif (! in_array($record->type, ['entrada', 'salida'])) {
                    $factors[] = '🔴 Tipo inválido (-30)';
                } else {
                    $typeText = $record->type === 'entrada' ? '🟢 Entrada' : '🟢 Salida';
                    $factors[] = $typeText;
                }

                // Quantity analysis
                if ($record->quantity <= 0) {
                    $factors[] = '🔴 Cantidad inválida (-40)';
                } elseif ($record->quantity > 10000) {
                    $factors[] = '🟡 Cantidad sospechosa (-10)';
                } else {
                    $factors[] = "🟢 Cantidad: {$record->quantity}";
                }

                // Reason analysis
                if (empty($record->reason)) {
                    $factors[] = '🔴 Sin motivo (-20)';
                } elseif (strlen($record->reason) < 5) {
                    $factors[] = '🟡 Motivo muy corto (-5)';
                } else {
                    $factors[] = '🟢 Con motivo';
                }

                // Document analysis
                if (empty($record->document_number)) {
                    $factors[] = '🔴 Sin documento (-15)';
                } else {
                    $factors[] = "🟢 Doc: {$record->document_number}";
                }

                // Reference analysis
                if (empty($record->reference)) {
                    $factors[] = '🟡 Sin referencia (-10)';
                } else {
                    $factors[] = '🟢 Con referencia';
                }

                // Product analysis
                if (! $record->product_id) {
                    $factors[] = '🔴 Sin producto (-45)';
                } elseif (! $record->product) {
                    $factors[] = '🔴 Producto no existe (-30)';
                } else {
                    $factors[] = "🟢 {$record->product->name}";
                }

                // Supplier analysis (for entries)
                if ($record->type === 'entrada') {
                    if (! $record->supplier_id) {
                        $factors[] = '🔴 Sin proveedor (-15)';
                    } elseif (! $record->supplier) {
                        $factors[] = '🟡 Proveedor no existe (-10)';
                    } elseif ($record->supplier) {
                        $factors[] = "🟢 Proveedor: {$record->supplier->name}";
                    }
                }

                // User analysis
                if (! $record->user_id) {
                    $factors[] = '🟡 Sin usuario (-10)';
                } else {
                    $factors[] = "🟢 Usuario: {$record->user_name}";
                }

                // Stock consistency analysis
                try {
                    if ($record->type === 'entrada') {
                        $expectedNewStock = $record->previous_stock + $record->quantity;
                        if ($record->new_stock !== $expectedNewStock) {
                            $factors[] = '🔴 Inconsistencia stock entrada (-35)';
                        } else {
                            $factors[] = "🟢 Stock consistente: {$record->previous_stock} → {$record->new_stock}";
                        }
                    } elseif ($record->type === 'salida') {
                        $expectedNewStock = $record->previous_stock - $record->quantity;
                        if ($record->new_stock !== $expectedNewStock) {
                            $factors[] = '🔴 Inconsistencia stock salida (-35)';
                        } else {
                            $factors[] = "🟢 Stock consistente: {$record->previous_stock} → {$record->new_stock}";
                        }
                    }

                    if ($record->new_stock < 0) {
                        $factors[] = '🔴 Stock negativo (-25)';
                    } elseif ($record->new_stock < 0) {
                        $factors[] = '🟡 Stock crítico (-15)';
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Consistencia: Error al verificar (-20)';
                }

                // Timing analysis
                $hour = $record->created_at->hour;
                if ($hour < 7 || $hour > 22) {
                    $factors[] = '🟡 Horario inusual (-5)';
                } else {
                    $factors[] = '🟢 Horario normal';
                }

                // Frequency analysis
                try {
                    $recentMovements = StockMovement::where('product_id', $record->product_id)
                        ->where('created_at', '>', now()->subDays(7))
                        ->count();

                    if ($recentMovements > 50) {
                        $factors[] = '🟡 Muchos movimientos semanales (-10)';
                    } else {
                        $factors[] = "🟢 Frecuencia normal: {$recentMovements}/semana";
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Frecuencia: No disponible';
                }

                // Check for corrections
                try {
                    $correctionMovements = StockMovement::where('product_id', $record->product_id)
                        ->where('type', $record->type === 'entrada' ? 'salida' : 'entrada')
                        ->whereBetween('created_at', [
                            $record->created_at->copy()->subHours(24),
                            $record->created_at->copy()->addHours(24),
                        ])
                        ->count();

                    if ($correctionMovements > 0) {
                        $factors[] = '🟡 Hay correcciones cercanas (-15)';
                    } else {
                        $factors[] = '🟢 Sin correcciones cercanas';
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Correcciones: Error al verificar';
                }

                $score = self::calculateStockMovementHealthScore($record);
                $color = $score >= 80 ? '🟢' : ($score >= 60 ? '🟡' : '🔴');

                return $color." Salud: {$score}/100\n\n".implode("\n", $factors);
            });
        } catch (\Exception $e) {
            return 'Error al calcular detalles de salud';
        }
    }
}
