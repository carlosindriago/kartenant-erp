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

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\ProductResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Recordatorio de Contexto: Asegura que el tenant esté activo antes de crear
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Recordatorio de contexto - asegura que el tenant esté activo
        Filament::getTenant()?->makeCurrent();

        // Capturamos el stock inicial
        $initialStock = $data['initial_stock'] ?? 0;
        unset($data['initial_stock']); // Lo quitamos de los datos del producto

        // Creamos el producto con stock 0
        $data['stock'] = 0;

        $product = parent::handleRecordCreation($data);

        // Si hay stock inicial, creamos el primer movimiento
        if ($initialStock > 0) {
            $user = auth()->user();
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'entrada',
                'quantity' => $initialStock,
                'reason' => 'Stock Inicial',
                'user_name' => $user ? $user->name : 'Sistema',
            ]);

            // Actualizar el stock del producto sin triggering de observers
            $product->updateQuietly(['stock' => $initialStock]);
        }

        return $product;
    }
}
