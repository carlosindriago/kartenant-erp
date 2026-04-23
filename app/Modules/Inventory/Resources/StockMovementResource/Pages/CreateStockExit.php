<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources\StockMovementResource\Pages;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Resources\StockMovementResource;
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class CreateStockExit extends Page
{
    protected static string $resource = StockMovementResource::class;

    protected static string $view = 'filament.pages.create-stock-exit';

    protected static ?string $title = 'Registrar Salida de Mercadería';

    public ?array $data = [];

    public function mount(): void
    {
        // Preseleccionar producto si viene desde query string
        $productId = request()->query('product');

        if ($productId && Product::find($productId)) {
            $product = Product::find($productId);
            $this->form->fill([
                'product_id' => $product->id,
                'current_stock' => $product->stock,
                'new_stock' => $product->stock,
            ]);
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Producto')
                    ->description('Selecciona el producto que sale del inventario')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Producto')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(Product::where('stock', '>', 0)->get()->pluck('name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->name} (Stock: {$record->stock})")
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    $set('current_stock', $product?->stock ?? 0);
                                    $set('quantity', null); // Reset quantity
                                    $set('new_stock', $product?->stock ?? 0);
                                }
                            }),

                        Forms\Components\Placeholder::make('current_stock')
                            ->label('Stock Actual')
                            ->content(fn ($get) => number_format($get('current_stock') ?? 0).' unidades')
                            ->extraAttributes(fn ($get) => [
                                'class' => ($get('current_stock') ?? 0) < 10 ? 'text-danger-600 font-bold' : '',
                            ]),
                    ]),

                Forms\Components\Section::make('Detalles de la Salida')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->suffix('unidades')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $currentStock = $get('current_stock') ?? 0;
                                        $newStock = $currentStock - ($state ?? 0);
                                        $set('new_stock', max(0, $newStock));

                                        // Verificar si se necesita autorización
                                        if ($state && $currentStock > 0 && $state > ($currentStock / 2)) {
                                            $set('requires_authorization', true);
                                        } else {
                                            $set('requires_authorization', false);
                                        }
                                    })
                                    ->helperText(fn ($get) => $get('quantity') && $get('current_stock') && $get('quantity') > $get('current_stock')
                                            ? '⚠️ La cantidad excede el stock disponible'
                                            : null
                                    ),

                                Forms\Components\Placeholder::make('new_stock')
                                    ->label('Stock Resultante')
                                    ->content(fn ($get) => number_format($get('new_stock') ?? 0).' unidades')
                                    ->extraAttributes(fn ($get) => [
                                        'class' => ($get('new_stock') ?? 0) < 5 ? 'text-danger-600 font-bold' : '',
                                    ]),
                            ]),

                        Forms\Components\Placeholder::make('authorization_notice')
                            ->label('')
                            ->content(fn ($get) => $get('requires_authorization')
                                ? '⚠️ Esta salida requiere autorización (más del 50% del stock)'
                                : ''
                            )
                            ->hidden(fn ($get) => ! $get('requires_authorization'))
                            ->extraAttributes(['class' => 'text-warning-600 font-semibold']),

                        Forms\Components\Select::make('reason')
                            ->label('Motivo de la Salida')
                            ->required()
                            ->options([
                                'Venta' => 'Venta',
                                'Producto Dañado' => 'Producto Dañado',
                                'Uso Interno' => 'Uso Interno',
                                'Devolución a Proveedor' => 'Devolución a Proveedor',
                                'Ajuste de Inventario (Disminución)' => 'Ajuste de Inventario (Disminución)',
                                'Transferencia entre Almacenes' => 'Transferencia entre Almacenes',
                                'Merma/Pérdida' => 'Merma/Pérdida',
                                'Otro' => 'Otro',
                            ])
                            ->searchable()
                            ->reactive(),
                    ]),

                Forms\Components\Section::make('Notas y Referencias')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia / Número de Venta')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('additional_notes')
                            ->label('Notas Adicionales')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Explica el motivo de la salida, especialmente si es por daño o pérdida'),

                        Forms\Components\Select::make('pdf_format')
                            ->label('Formato de Comprobante Preferido')
                            ->options([
                                'a4' => 'PDF A4 (Archivo/Autorización)',
                                'thermal' => 'Ticket 80mm (Rápido)',
                            ])
                            ->default('a4')
                            ->required(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Registrar Salida')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Salida de Mercadería')
                ->modalDescription(function () {
                    $data = $this->form->getState();
                    $product = Product::find($data['product_id'] ?? null);
                    $quantity = $data['quantity'] ?? 0;
                    $currentStock = $data['current_stock'] ?? 0;

                    $warning = '';
                    if ($quantity > $currentStock) {
                        $warning = "\n\n⚠️ ADVERTENCIA: La cantidad excede el stock disponible.";
                    }

                    return $product
                        ? "¿Confirmas la salida de {$quantity} unidades de {$product->name}?{$warning}"
                        : '¿Confirmas esta salida de mercadería?';
                })
                ->modalSubmitActionLabel('Sí, Registrar Salida')
                ->action('registerExit'),
        ];
    }

    public function registerExit(): void
    {
        $data = $this->form->getState();

        try {
            $service = app(StockMovementService::class);
            $product = Product::findOrFail($data['product_id']);
            $user = Filament::auth()->user();

            // Determinar si necesita autorización
            $requiresAuth = $service->requiresAuthorization($product, $data['quantity']);
            $authorizedBy = $requiresAuth ? $user : null; // Por ahora, auto-autorizado

            $movement = $service->registerExit(
                product: $product,
                quantity: $data['quantity'],
                reason: $data['reason'],
                registeredBy: $user,
                authorizedBy: $authorizedBy,
                additionalNotes: $data['additional_notes'] ?? null,
                reference: $data['reference'] ?? null,
                pdfFormat: $data['pdf_format'] ?? 'a4'
            );

            // Guardar datos en sesión para página de resumen
            session()->flash('stock_movement_registered', [
                'movement_id' => $movement->id,
                'type' => 'salida',
                'success' => true,
            ]);

            // Redirigir a página de resumen
            $this->redirect($this->getResource()::getUrl('view', ['record' => $movement->id]));

        } catch (\Exception $e) {
            \Log::error('Error registrando salida de mercadería', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al Registrar Salida')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
