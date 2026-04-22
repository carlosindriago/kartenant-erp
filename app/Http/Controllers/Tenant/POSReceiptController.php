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
use App\Modules\POS\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class POSReceiptController extends Controller
{
    /**
     * Descargar comprobante en PDF
     * 
     * @param string $tenant El tenant del dominio (inyectado automáticamente por el grupo de rutas)
     * @param int $saleId El ID de la venta
     */
    public function downloadPDF($tenant, $saleId)
    {
        $sale = Sale::with(['items.product', 'customer', 'user'])->findOrFail($saleId);
        $currentTenant = SpatieTenant::current();
        
        // Si no tiene hash de verificación, generarlo ahora
        if (!$sale->verification_hash && $sale->status === 'completed') {
            $sale->generateVerificationHash();
            $sale->refresh();
        }
        
        $pdf = Pdf::loadView('pdf.receipt-thermal', [
            'sale' => $sale,
            'tenant' => $currentTenant,
            'qrCode' => $sale->verification_hash ? $sale->getVerificationQRCode() : null,
            'verificationUrl' => $sale->verification_hash ? $sale->getVerificationUrl() : null,
        ])->setPaper([0, 0, 226.77, 708.66], 'portrait'); // 80mm x 250mm
                  
        return $pdf->download("comprobante-{$sale->invoice_number}.pdf");
    }

    /**
     * Abrir ventana de impresión
     * 
     * @param string $tenant El tenant del dominio (inyectado automáticamente por el grupo de rutas)
     * @param int $saleId El ID de la venta
     */
    public function print($tenant, $saleId)
    {
        $sale = Sale::with(['items.product', 'customer', 'user'])->findOrFail($saleId);
        $currentTenant = SpatieTenant::current();
        
        // Si no tiene hash de verificación, generarlo ahora
        if (!$sale->verification_hash && $sale->status === 'completed') {
            $sale->generateVerificationHash();
            $sale->refresh();
        }
        
        return view('pdf.receipt-thermal', [
            'sale' => $sale,
            'tenant' => $currentTenant,
            'qrCode' => $sale->verification_hash ? $sale->getVerificationQRCode() : null,
            'verificationUrl' => $sale->verification_hash ? $sale->getVerificationUrl() : null,
        ]);
    }
}
