<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Observers;

use App\Modules\Inventory\Models\StockMovement;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     * El Vigilante de Inventario NO actualiza el stock aquí porque ya se actualiza
     * en las acciones modales de ListStockMovements para evitar duplicación
     * Este observer queda preparado para futuros casos donde sí sea necesario
     */
    public function created(StockMovement $stockMovement): void
    {
        // El stock ya se actualiza en las acciones modales
        // Este espacio queda disponible para auditoría o notificaciones adicionales

        // Por ejemplo, podríamos enviar notificaciones si el stock es bajo
        // o registrar en activity log, etc.
    }

    /**
     * Handle the StockMovement "updated" event.
     */
    public function updated(StockMovement $stockMovement): void
    {
        //
    }

    /**
     * Handle the StockMovement "deleted" event.
     */
    public function deleted(StockMovement $stockMovement): void
    {
        //
    }

    /**
     * Handle the StockMovement "restored" event.
     */
    public function restored(StockMovement $stockMovement): void
    {
        //
    }

    /**
     * Handle the StockMovement "force deleted" event.
     */
    public function forceDeleted(StockMovement $stockMovement): void
    {
        //
    }
}
