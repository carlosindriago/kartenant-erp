<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Modules\POS\Models\SaleReturn;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Multitenancy\Models\Tenant;

class CreditNoteController extends Controller
{
    /**
     * Descargar Nota de Crédito en PDF
     */
    public function download(SaleReturn $saleReturn)
    {
        // Verificar que la devolución pertenece al tenant actual
        $currentTenant = Tenant::current();

        if ($saleReturn->tenant_id !== $currentTenant->id) {
            abort(403, 'No autorizado');
        }

        // Cargar relaciones necesarias
        $saleReturn->load([
            'items.product',
            'originalSale.customer',
            'processedBy',
        ]);

        // Si no tiene hash de verificación, generarlo ahora
        if (! $saleReturn->verification_hash && $saleReturn->status === 'completed') {
            $saleReturn->generateVerificationHash();
            $saleReturn->refresh();
        }

        // Generar PDF (formato térmico 80mm)
        $pdf = Pdf::loadView('pdf.credit-note-thermal', [
            'saleReturn' => $saleReturn,
            'tenant' => $currentTenant,
            'qrCode' => $saleReturn->verification_hash ? $saleReturn->getVerificationQRCode() : null,
            'verificationUrl' => $saleReturn->verification_hash ? $saleReturn->getVerificationUrl() : null,
        ])->setPaper([0, 0, 226.77, 708.66], 'portrait'); // 80mm x 250mm

        // Descargar con nombre descriptivo
        $filename = "nota-credito-{$saleReturn->return_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Ver Nota de Crédito en el navegador
     */
    public function view(SaleReturn $saleReturn)
    {
        // Verificar que la devolución pertenece al tenant actual
        $currentTenant = Tenant::current();

        if ($saleReturn->tenant_id !== $currentTenant->id) {
            abort(403, 'No autorizado');
        }

        // Cargar relaciones necesarias
        $saleReturn->load([
            'items.product',
            'originalSale.customer',
            'processedBy',
        ]);

        // Si no tiene hash de verificación, generarlo ahora
        if (! $saleReturn->verification_hash && $saleReturn->status === 'completed') {
            $saleReturn->generateVerificationHash();
            $saleReturn->refresh();
        }

        // Generar PDF (formato térmico 80mm)
        $pdf = Pdf::loadView('pdf.credit-note-thermal', [
            'saleReturn' => $saleReturn,
            'tenant' => $currentTenant,
            'qrCode' => $saleReturn->verification_hash ? $saleReturn->getVerificationQRCode() : null,
            'verificationUrl' => $saleReturn->verification_hash ? $saleReturn->getVerificationUrl() : null,
        ])->setPaper([0, 0, 226.77, 708.66], 'portrait'); // 80mm x 250mm

        // Mostrar en navegador
        return $pdf->stream("nota-credito-{$saleReturn->return_number}.pdf");
    }
}
