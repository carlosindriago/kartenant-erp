<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers;

use App\Services\DocumentHashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    protected DocumentHashService $hashService;
    
    public function __construct(DocumentHashService $hashService)
    {
        $this->hashService = $hashService;
    }
    
    /**
     * Muestra el formulario de verificación
     */
    public function index()
    {
        return view('verification.index');
    }
    
    /**
     * Verifica un documento por hash (URL directa o escaneo QR)
     */
    public function verify(string $hash)
    {
        // Validar formato de hash
        if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
            return view('verification.result', [
                'result' => 'invalid_format',
                'message' => 'El código de verificación no tiene un formato válido.',
                'hash' => $hash,
            ]);
        }
        
        // Verificar el documento
        $verification = $this->hashService->verifyHash(
            $hash,
            request()->ip(),
            request()->userAgent()
        );
        
        // Si no se encontró
        if ($verification['result'] === 'not_found') {
            return view('verification.result', [
                'result' => 'not_found',
                'message' => $verification['message'],
                'hash' => $hash,
            ]);
        }
        
        // Documento encontrado - mostrar información
        $documentVerification = $verification['verification'];
        
        return view('verification.result', [
            'result' => $verification['result'],
            'message' => $verification['message'],
            'hash' => $hash,
            'verification' => $documentVerification,
            'metadata' => $documentVerification->getSanitizedMetadata(),
            'document_type' => $this->getDocumentTypeLabel($documentVerification->document_type),
        ]);
    }
    
    /**
     * API endpoint para verificación programática
     */
    public function verifyApi(Request $request)
    {
        $request->validate([
            'hash' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
        ]);
        
        $verification = $this->hashService->verifyHash(
            $request->hash,
            $request->ip(),
            $request->userAgent()
        );
        
        if ($verification['result'] === 'not_found') {
            return response()->json([
                'valid' => false,
                'result' => 'not_found',
                'message' => $verification['message'],
            ], 404);
        }
        
        $doc = $verification['verification'];
        
        return response()->json([
            'valid' => $verification['result'] === 'valid',
            'result' => $verification['result'],
            'message' => $verification['message'],
            'document' => [
                'type' => $doc->document_type,
                'generated_at' => $doc->generated_at->toIso8601String(),
                'verification_count' => $doc->verification_count,
                'is_valid' => $doc->is_valid,
                'is_expired' => $doc->isExpired(),
                'metadata' => $doc->getSanitizedMetadata(),
            ],
        ]);
    }
    
    /**
     * Obtiene etiqueta legible del tipo de documento
     */
    protected function getDocumentTypeLabel(string $type): string
    {
        $labels = [
            'sale_report' => 'Reporte de Ventas',
            'inventory_report' => 'Reporte de Inventario',
            'return_report' => 'Reporte de Devoluciones',
            'financial_report' => 'Reporte Financiero',
            'invoice' => 'Factura',
            'receipt' => 'Recibo',
        ];
        
        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
