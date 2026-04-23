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

use App\Modules\Inventory\Models\MovementReason;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Acción: Exportar a Excel
            ExportAction::make()
                ->label('Exportar a Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down'),

            // Acción: Registrar Entrada (nueva página)
            Actions\Action::make('register_entry')
                ->label('Registrar Entrada')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => StockMovementResource::getUrl('create-entry')),

            // Acción: Registrar Salida (nueva página)
            Actions\Action::make('register_exit')
                ->label('Registrar Salida')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->url(fn () => StockMovementResource::getUrl('create-exit')),

            // ACCIONES LEGACY (Mantener por compatibilidad temporal)
            // Acción: Registrar Entrada (Modal Legacy)
            Actions\Action::make('register_entry_quick')
                ->label('Entrada Rápida')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->hidden() // Oculto por defecto, usar las nuevas páginas
                ->form([
                    Forms\Components\Select::make('product_id')
                        ->label('Producto')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship('product', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->name} (Stock actual: {$record->stock})")
                        ->helperText('Selecciona el producto al que deseas agregar stock'),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Cantidad')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->helperText('Cantidad de unidades a agregar'),

                    Forms\Components\Select::make('reason_id')
                        ->label('Motivo')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship(
                            'movementReason',
                            'name',
                            fn ($query) => $query->entrada()
                        )
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre del Motivo')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $reason = MovementReason::create([
                                'name' => $data['name'],
                                'type' => 'entrada',
                            ]);

                            return $reason->id;
                        })
                        ->helperText('Selecciona el motivo de la entrada o crea uno nuevo'),

                    Forms\Components\Textarea::make('reason_detail')
                        ->label('Detalles adicionales (opcional)')
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('Ej: Factura #12345, Proveedor XYZ')
                        ->helperText('Información adicional para referencia futura'),

                    Forms\Components\TextInput::make('reference')
                        ->label('Número de Referencia (opcional)')
                        ->maxLength(255)
                        ->placeholder('Ej: Factura, Orden de Compra, etc.')
                        ->helperText('Número de documento o referencia'),
                ])
                ->action(function (array $data): void {
                    $product = Product::find($data['product_id']);
                    $previousStock = $product->stock;
                    $newStock = $previousStock + $data['quantity'];

                    // Obtener el motivo
                    $movementReason = MovementReason::find($data['reason_id']);
                    $fullReason = $movementReason->name;
                    if (! empty($data['reason_detail'])) {
                        $fullReason .= ': '.$data['reason_detail'];
                    }

                    // Crear el movimiento
                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_reason_id' => $data['reason_id'],
                        'type' => 'entrada',
                        'quantity' => $data['quantity'],
                        'reason' => $fullReason,
                        'reference' => $data['reference'] ?? null,
                        'user_name' => Filament::auth()->user()->name,
                        'previous_stock' => $previousStock,
                        'new_stock' => $newStock,
                    ]);

                    // Actualizar el stock del producto sin triggering de observers
                    $product->updateQuietly(['stock' => $newStock]);

                    // Notificación de éxito
                    Notification::make()
                        ->success()
                        ->title('Entrada registrada exitosamente')
                        ->body("Se agregaron {$data['quantity']} unidades de {$product->name}. Stock nuevo: {$newStock}")
                        ->send();
                }),
        ];
    }
}
