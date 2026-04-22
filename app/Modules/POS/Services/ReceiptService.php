<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Services;

use App\Modules\POS\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptService
{
    public function generatePDF(Sale $sale)
    {
        $sale->load(['items.product', 'customer', 'user']);
        
        $pdf = Pdf::loadView('pos.receipt', [
            'sale' => $sale,
            'tenant' => \Spatie\Multitenancy\Models\Tenant::current(),
        ]);
        
        return $pdf->stream("ticket-{$sale->invoice_number}.pdf");
    }
}
