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
use App\Modules\Inventory\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * Descargar PDF del movimiento de stock
     *
     * IMPORTANTE: El parámetro $tenant DEBE estar primero porque viene del Route::domain('{tenant}...')
     * Laravel inyecta los parámetros en orden: primero los del dominio, luego los de la ruta
     */
    public function download(Request $request, string $tenant, string $movement)
    {
        \Log::info('🔍 StockMovementController::download llamado', [
            'tenant' => $tenant,
            'movement_id' => $movement,
            'format' => $request->query('format'),
            'url' => $request->fullUrl(),
            'user_id' => auth('tenant')->id() ?? auth('web')->id(),
        ]);

        // Obtener el movimiento
        $stockMovement = StockMovement::with(['product', 'authorizedBy'])->findOrFail((int) $movement);
        
        // Obtener formato desde query string (thermal o a4)
        $format = $request->query('format', 'a4');
        
        // Validar formato
        if (!in_array($format, ['thermal', 'a4'])) {
            $format = 'a4';
        }
        
        try {
            // Usar el servicio para generar y descargar el PDF
            $service = app(StockMovementService::class);
            return $service->downloadMovementPdf($stockMovement, $format);
        } catch (\Exception $e) {
            \Log::error('Error descargando PDF de movimiento de stock', [
                'movement_id' => $movement,
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Error al generar el PDF. Por favor, intenta nuevamente.');
        }
    }
}
