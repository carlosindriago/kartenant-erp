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

use App\Modules\POS\Models\SaleReturn;
use Illuminate\Support\Facades\Log;

class SaleReturnObserver
{
    /**
     * Generar hash de verificación después de crear la devolución
     */
    public function created(SaleReturn $saleReturn): void
    {
        // Solo generar hash para devoluciones completadas
        if ($saleReturn->status === 'completed' && ! $saleReturn->verification_hash) {
            $this->generateVerificationHash($saleReturn);
        }
    }

    /**
     * Generar hash cuando la devolución se completa
     */
    public function updated(SaleReturn $saleReturn): void
    {
        // Si el estado cambió a completado y no tiene hash, generarlo
        if ($saleReturn->status === 'completed' &&
            $saleReturn->wasChanged('status') &&
            ! $saleReturn->verification_hash) {
            $this->generateVerificationHash($saleReturn);
        }
    }

    /**
     * Genera y almacena el hash de verificación
     */
    protected function generateVerificationHash(SaleReturn $saleReturn): void
    {
        $success = $saleReturn->generateVerificationHash();

        if ($success) {
            Log::info('Hash de verificación generado para devolución', [
                'sale_return_id' => $saleReturn->id,
                'return_number' => $saleReturn->return_number,
                'hash' => $saleReturn->verification_hash,
            ]);
        }
    }
}
