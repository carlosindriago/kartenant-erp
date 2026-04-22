<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Observers;

use App\Modules\POS\Models\Sale;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    
    /**
     * Generar hash de verificación después de crear la venta
     */
    public function created(Sale $sale): void
    {
        // Solo generar hash para ventas completadas
        if ($sale->status === 'completed' && !$sale->verification_hash) {
            $this->generateVerificationHash($sale);
        }
    }

    /**
     * Generar hash cuando la venta se completa
     */
    public function updated(Sale $sale): void
    {
        // Si el estado cambió a completado y no tiene hash, generarlo
        if ($sale->status === 'completed' && 
            $sale->wasChanged('status') && 
            !$sale->verification_hash) {
            $this->generateVerificationHash($sale);
        }
    }
    
    /**
     * Genera y almacena el hash de verificación
     */
    protected function generateVerificationHash(Sale $sale): void
    {
        $success = $sale->generateVerificationHash();
        
        if ($success) {
            Log::info('Hash de verificación generado para venta', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'hash' => $sale->verification_hash,
            ]);
        }
    }
}
