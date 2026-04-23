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
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class CreateStockEntry extends Page
{
    protected static string $resource = \App\Modules\Inventory\Resources\StockMovementResource::class;

    protected static string $view = 'filament.pages.create-stock-entry';

    protected static ?string $title = 'Registrar Entrada de Mercadería';

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
                    ->description('Selecciona el producto que ingresa al inventario')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Producto')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(Product::all()->pluck('name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->name} (SKU: {$record->sku})")
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    $set('current_stock', $product?->stock ?? 0);
                                }
                            }),

                        Forms\Components\Placeholder::make('current_stock')
                            ->label('Stock Actual')
                            ->content(fn ($get) => number_format($get('current_stock') ?? 0).' unidades'),
                    ]),

                Forms\Components\Section::make('Detalles de la Entrada')
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
                                        $set('new_stock', $currentStock + ($state ?? 0));
                                    }),

                                Forms\Components\Placeholder::make('new_stock')
                                    ->label('Stock Resultante')
                                    ->content(fn ($get) => number_format($get('new_stock') ?? 0).' unidades'),
                            ]),

                        Forms\Components\Select::make('reason')
                            ->label('Motivo de la Entrada')
                            ->required()
                            ->options([
                                'Compra a Proveedor' => 'Compra a Proveedor',
                                'Devolución de Cliente' => 'Devolución de Cliente',
                                'Ajuste de Inventario (Aumento)' => 'Ajuste de Inventario (Aumento)',
                                'Producción Interna' => 'Producción Interna',
                                'Transferencia entre Almacenes' => 'Transferencia entre Almacenes',
                                'Otro' => 'Otro',
                            ])
                            ->searchable()
                            ->reactive(),
                    ]),

                Forms\Components\Section::make('Información del Proveedor')
                    ->description('Datos del proveedor y documentación (opcional)')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Proveedor')
                            ->options(\App\Modules\Inventory\Models\Supplier::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la Empresa')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_name')
                                    ->label('Persona de Contacto')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading('Registrar Nuevo Proveedor')
                            ->createOptionUsing(function (array $data) {
                                $supplier = \App\Modules\Inventory\Models\Supplier::create($data);

                                return $supplier->id;
                            })
                            ->helperText('Puedes crear un nuevo proveedor directamente desde aquí'),

                        Forms\Components\TextInput::make('invoice_reference')
                            ->label('Factura / Guía de Remisión')
                            ->maxLength(100),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('batch_number')
                                    ->label('Número de Lote')
                                    ->maxLength(100),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Fecha de Vencimiento')
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Notas y Referencias')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('additional_notes')
                            ->label('Notas Adicionales')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\Select::make('pdf_format')
                            ->label('Formato de Comprobante Preferido')
                            ->options([
                                'a4' => 'PDF A4 (Archivo/Proveedor)',
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
                ->label('Registrar Entrada')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Entrada de Mercadería')
                ->modalDescription(function () {
                    $data = $this->form->getState();
                    $product = Product::find($data['product_id'] ?? null);
                    $quantity = $data['quantity'] ?? 0;

                    return $product
                        ? "¿Confirmas la entrada de {$quantity} unidades de {$product->name}?"
                        : '¿Confirmas esta entrada de mercadería?';
                })
                ->modalSubmitActionLabel('Sí, Registrar Entrada')
                ->action('registerEntry'),
        ];
    }

    public function registerEntry(): void
    {
        $data = $this->form->getState();

        try {
            $service = app(StockMovementService::class);
            $product = Product::findOrFail($data['product_id']);
            $user = \Filament\Facades\Filament::auth()->user();

            $movement = $service->registerEntry(
                product: $product,
                quantity: $data['quantity'],
                reason: $data['reason'],
                registeredBy: $user,
                supplierId: $data['supplier_id'] ?? null,
                invoiceReference: $data['invoice_reference'] ?? null,
                batchNumber: $data['batch_number'] ?? null,
                expiryDate: $data['expiry_date'] ?? null,
                additionalNotes: $data['additional_notes'] ?? null,
                reference: $data['reference'] ?? null,
                pdfFormat: $data['pdf_format'] ?? 'a4'
            );

            // Guardar datos en sesión para página de resumen
            session()->flash('stock_movement_registered', [
                'movement_id' => $movement->id,
                'type' => 'entrada',
                'success' => true,
            ]);

            // Redirigir a página de resumen
            $this->redirect($this->getResource()::getUrl('view', ['record' => $movement->id]));

        } catch (\Exception $e) {
            \Log::error('Error registrando entrada de mercadería', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al Registrar Entrada')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
