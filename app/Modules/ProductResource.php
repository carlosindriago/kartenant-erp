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

use App\Filament\Actions\HasStandardActionGroup;
use App\Modules\Inventory\Models\Product;
use App\Modules\ProductResource\Pages;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProductResource extends Resource
{
    use HasStandardActionGroup;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    // Products are tenant-scoped but don't have user ownership
    // Disable Filament's tenant scoping to avoid ownership relationship checks
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Principal')
                    ->columns(2)
                    ->schema([
                        // GRID PARA IMAGEN Y UPLOAD (Desktop: lado a lado, Mobile: columna)
                        Grid::make()
                            ->schema([
                                // MOSTRAR IMAGEN ACTUAL
                                Forms\Components\Placeholder::make('current_image')
                                    ->label('Imagen Actual')
                                    ->content(function ($record) {
                                        if (! $record || ! $record->image) {
                                            return new HtmlString('
                                                <div class="flex items-center justify-center md:justify-start">
                                                    <img src="'.asset('images/placeholder-product.svg').'" 
                                                         class="w-32 h-32 object-contain rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                                                         alt="Sin imagen">
                                                </div>
                                            ');
                                        }

                                        $imageName = str_replace('products/', '', $record->image);
                                        $imageUrl = Storage::disk('public')->url('products/'.$imageName);

                                        return new HtmlString('
                                            <div class="flex items-center justify-center md:justify-start">
                                                <img src="'.$imageUrl.'" 
                                                     class="w-32 h-32 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-700 shadow-sm"
                                                     alt="Imagen del producto">
                                            </div>
                                        ');
                                    })
                                    ->visibleOn('edit'),

                                // CAMPO PARA SUBIR NUEVA IMAGEN
                                FileUpload::make('image')
                                    ->label(fn ($record) => $record ? 'Cambiar Imagen' : 'Imagen del Producto')
                                    ->helperText(fn ($record) => $record ? 'Sube una nueva imagen para reemplazar la actual (opcional)' : 'Sube una imagen del producto')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/*'])
                                    ->maxSize(5120)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        // En edición, limpiar el estado para evitar carga
                                        if ($record && $record->exists) {
                                            $component->state(null);
                                        }
                                    })
                                    ->dehydrated(fn ($state) => filled($state)),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nombre del Producto')
                            ->placeholder('Ej: Martillo de Carpintero')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(255),

                        TextInput::make('price')
                            ->label('Precio de Venta')
                            ->prefix('$')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),

                        TextInput::make('initial_stock')
                            ->label('Stock Inicial')
                            ->numeric()
                            ->default(0)
                            ->helperText('Solo para crear. El stock se gestiona en "Movimientos".')
                            ->visibleOn('create')
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),

                        TextInput::make('min_stock')
                            ->label('Stock Mínimo')
                            ->numeric()
                            ->default(0)
                            ->helperText('Recibirás alertas al llegar a este nivel.')
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),

                        Select::make('unit_of_measure')
                            ->label('Unidad de Medida')
                            ->options([
                                'unidad' => 'Unidad',
                                'par' => 'Par',
                                'caja' => 'Caja',
                                'pack' => 'Pack',
                                'kg' => 'Kg',
                                'litro' => 'Litro',
                                'metro' => 'Metro',
                            ])
                            ->default('unidad')
                            ->required()
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),

                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre de la Categoría')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),

                        Select::make('tax_id')
                            ->label('Impuesto')
                            ->relationship('tax', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre del Impuesto')
                                    ->placeholder('ej: IVA 21%')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('rate')
                                    ->label('Tasa (%)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('ej: 21.00')
                                    ->required(),
                            ])
                            ->columnSpan([
                                'default' => 2,
                                'md' => 1,
                            ]),
                    ]),

                // Sección colapsable con más detalles
                Section::make('Detalles Adicionales')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                TextInput::make('sku')
                                    ->label('Código Interno (SKU)')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Se autogenera si se deja vacío'),

                                TextInput::make('barcode')
                                    ->label('Código de Barras')
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->placeholder('Describe las características principales del producto')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->maxLength(500),

                                TextInput::make('cost_price')
                                    ->label('Precio de Costo')
                                    ->prefix('$')
                                    ->numeric()
                                    ->step(0.01)
                                    ->helperText('Lo que te cuesta este producto'),

                                Toggle::make('status')
                                    ->label('Activo')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        if (! $record->image) {
                            return null;
                        }
                        // Si ya tiene el prefijo products/, usarlo directamente
                        if (str_starts_with($record->image, 'products/')) {
                            return $record->image;
                        }

                        // Si no, agregarlo
                        return 'products/'.$record->image;
                    })
                    ->defaultImageUrl(asset('images/placeholder-product.svg'))
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->description(fn ($record) => $record->barcode)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('ARS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('En Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Valor en Stock')
                    ->getStateUsing(fn ($record) => $record->stock * $record->price)
                    ->money('ARS')
                    ->sortable(),

                Tables\Columns\IconColumn::make('low_stock_alert')
                    ->label('Alerta')
                    ->getStateUsing(fn ($record) => $record->stock <= $record->min_stock)
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Salud del Producto')
                    ->formatStateUsing(fn ($record) => self::calculateProductHealthScore($record))
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'))
                    ->tooltip(fn ($record) => self::getProductHealthTooltip($record))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock')),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Sin Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0)),
            ])
            ->actions([
                // ACCESO RÁPIDO (Acciones principales)
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('Ver información completa del producto'),

                Action::make('quick_stock')
                    ->label('Ajustar Stock')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->tooltip('Ajustar stock rápidamente')
                    ->form([
                        TextInput::make('adjustment')
                            ->label('Cantidad')
                            ->helperText('Positivos suman, negativos restan')
                            ->numeric()
                            ->required(),
                        Select::make('reason')
                            ->label('Motivo')
                            ->options([
                                'inventory' => 'Ajuste de Inventario',
                                'damage' => 'Producto Dañado',
                                'return' => 'Devolución',
                                'purchase' => 'Compra',
                                'other' => 'Otro',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update(['stock' => $record->stock + $data['adjustment']]);
                        Notification::make()
                            ->title('Stock Actualizado')
                            ->body("El stock del producto {$record->name} ahora es {$record->stock}")
                            ->success()
                            ->send();
                    }),

                // ACTION GROUP ESTÁNDAR
                static::getStandardActionGroup(),
            ])
            ->recordUrl(
                fn ($record): string => ProductResource::getUrl('view', ['record' => $record])
            )
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                    ExportBulkAction::make()
                        ->label('Exportar')
                        ->color('success')
                        ->icon('heroicon-o-document-arrow-down'),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Exportar todos los productos')
                    ->modalDescription('¿Deseas exportar todos los productos de la base de datos a Excel?')
                    ->modalSubmitActionLabel('Sí, Exportar Todos')
                    ->modalCancelActionLabel('No, Cancelar'),
            ])
            ->emptyStateHeading('¡Empecemos con tu primer producto!')
            ->emptyStateDescription('Agrega productos para empezar a gestionar tu inventario')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('name');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * Calculate product health score (0-100)
     */
    public static function calculateProductHealthScore($record): int
    {
        try {
            if (! $record) {
                return 0;
            }

            $cacheKey = "product_health_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $score = 100;

                // Stock health factors
                if ($record->stock <= 0) {
                    $score -= 35; // Sin stock
                } elseif ($record->stock <= $record->min_stock) {
                    $score -= 20; // Stock bajo
                } elseif ($record->stock <= $record->min_stock * 2) {
                    $score -= 10; // Stock en nivel precautorio
                }

                // Price validation
                if ($record->price <= 0) {
                    $score -= 25; // Precio inválido
                } elseif ($record->price < 100) { // Assuming ARS and minimum reasonable price
                    $score -= 10; // Precio muy bajo (posible error)
                }

                // Image completeness
                if (! $record->image) {
                    $score -= 15; // Sin imagen
                }

                // Category assignment
                if (! $record->category_id) {
                    $score -= 10; // Sin categoría
                }

                // SKU/Barcode completeness
                if (! $record->sku && ! $record->barcode) {
                    $score -= 5; // Sin código de identificación
                }

                // Check for recent sales (requires connection to tenant database)
                try {
                    $recentSales = DB::connection('tenant')
                        ->table('sales')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sale_items.product_id', $record->id)
                        ->where('sales.created_at', '>', now()->subDays(30))
                        ->count();

                    if ($recentSales == 0) {
                        $score -= 15; // Sin ventas recientes
                    } elseif ($recentSales < 5) {
                        $score -= 5; // Pocas ventas recientes
                    }
                } catch (\Exception $e) {
                    // Si no hay conexión o las tablas no existen, no afectar el score
                }

                // Check for stock movements
                try {
                    $recentMovements = DB::connection('tenant')
                        ->table('stock_movements')
                        ->where('product_id', $record->id)
                        ->where('created_at', '>', now()->subDays(30))
                        ->count();

                    if ($recentMovements == 0) {
                        $score -= 10; // Sin movimientos recientes (posible producto inactivo)
                    }
                } catch (\Exception $e) {
                    // Si no hay conexión o las tablas no existen, no afectar el score
                }

                return max(0, min(100, $score));
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get detailed tooltip for product health score
     */
    public static function getProductHealthTooltip($record): string
    {
        try {
            if (! $record) {
                return 'Error al calcular salud';
            }

            $cacheKey = "product_health_tooltip_{$record->id}";

            return Cache::remember($cacheKey, 300, function () use ($record) {
                $factors = [];

                // Stock analysis
                if ($record->stock <= 0) {
                    $factors[] = '🔴 Sin stock (-35)';
                } elseif ($record->stock <= $record->min_stock) {
                    $factors[] = '🟡 Stock bajo (-20)';
                } elseif ($record->stock <= $record->min_stock * 2) {
                    $factors[] = '🟡 Stock limitado (-10)';
                } else {
                    $factors[] = '🟢 Stock adecuado';
                }

                // Price analysis
                if ($record->price <= 0) {
                    $factors[] = '🔴 Precio inválido (-25)';
                } elseif ($record->price < 100) {
                    $factors[] = '🟡 Precio muy bajo (-10)';
                } else {
                    $factors[] = '🟢 Precio válido';
                }

                // Image analysis
                if (! $record->image) {
                    $factors[] = '🟡 Sin imagen (-15)';
                } else {
                    $factors[] = '🟢 Con imagen';
                }

                // Category analysis
                if (! $record->category_id) {
                    $factors[] = '🟡 Sin categoría (-10)';
                } else {
                    $factors[] = '🟢 Categorizado';
                }

                // Sales analysis
                try {
                    $recentSales = DB::connection('tenant')
                        ->table('sales')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sale_items.product_id', $record->id)
                        ->where('sales.created_at', '>', now()->subDays(30))
                        ->count();

                    if ($recentSales == 0) {
                        $factors[] = '🔴 Sin ventas en 30 días (-15)';
                    } elseif ($recentSales < 5) {
                        $factors[] = '🟡 Pocas ventas (-5)';
                    } else {
                        $factors[] = '🟢 Con ventas recientes';
                    }
                } catch (\Exception $e) {
                    $factors[] = '❓ Ventas: No disponible';
                }

                $score = self::calculateProductHealthScore($record);
                $color = $score >= 80 ? '🟢' : ($score >= 60 ? '🟡' : '🔴');

                return $color." Salud: {$score}/100\n\n".implode("\n", $factors);
            });
        } catch (\Exception $e) {
            return 'Error al calcular detalles de salud';
        }
    }
}
