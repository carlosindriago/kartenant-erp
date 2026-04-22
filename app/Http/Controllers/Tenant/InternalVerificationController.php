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
use App\Models\Tenancy\CashRegister\CashRegisterOpening;
use App\Models\Tenancy\CashRegister\CashRegisterClosing;
use App\Modules\POS\Models\CashRegister;
use App\Models\Tenancy\Sale;
use App\Models\Tenancy\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de Verificación Interna
 * 
 * Maneja la verificación de documentos internos que requieren
 * autenticación y permisos específicos.
 */
class InternalVerificationController extends Controller
{
    /**
     * Modelos que soportan verificación interna
     */
    private array $verifiableModels = [
        'cash_register_opening' => CashRegisterOpening::class,
        'cash_register_closing' => CashRegisterClosing::class,
        'cash_register' => CashRegister::class, // POS CashRegister (apertura + cierre)
        // Futuras expansiones:
        // 'stock_movement' => StockMovement::class,
        // 'stock_adjustment' => StockAdjustment::class,
    ];

    /**
     * Muestra la página de verificación interna
     */
    public function show(Request $request, string $hash)
    {
        $user = auth()->user();

        // Buscar el documento por hash en todos los modelos verificables
        $document = $this->findDocumentByHash($hash);

        if (!$document) {
            return view('tenant.verification.not-found', [
                'hash' => $hash,
                'message' => 'Documento no encontrado o hash inválido.',
            ]);
        }

        // Verificar que el modelo implemente HasInternalVerification
        if (!method_exists($document, 'canBeVerifiedBy')) {
            Log::error('Modelo no implementa HasInternalVerification', [
                'model' => get_class($document),
                'hash' => $hash,
            ]);
            abort(500, 'El documento no soporta verificación interna.');
        }

        // Verificar permisos del usuario
        if (!$document->canBeVerifiedBy($user)) {
            return view('tenant.verification.forbidden', [
                'document' => $document,
                'required_permission' => $document->getVerificationPermission(),
                'user_roles' => $user->roles->pluck('name')->toArray(),
            ]);
        }

        // Registrar el acceso en activities
        $document->logVerificationAccess($user);

        // Obtener datos del documento
        $documentData = $this->getDocumentData($document);

        // Obtener historial de verificaciones
        $verificationHistory = $document->getVerificationAuditTrail();

        return view('tenant.verification.show', [
            'document' => $document,
            'documentType' => $this->getDocumentTypeName($document),
            'documentData' => $documentData,
            'verificationHistory' => $verificationHistory,
            'verificationCount' => $document->getVerificationCount(),
            'lastVerification' => $document->getLastVerification(),
            'user' => $user,
        ]);
    }

    /**
     * Busca un documento por hash en todos los modelos verificables
     */
    private function findDocumentByHash(string $hash)
    {
        foreach ($this->verifiableModels as $type => $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $document = $modelClass::where('verification_hash', $hash)->first();
            
            if ($document) {
                return $document;
            }
        }

        return null;
    }

    /**
     * Obtiene datos específicos del documento según su tipo
     */
    private function getDocumentData($document): array
    {
        $className = get_class($document);

        return match ($className) {
            CashRegisterOpening::class => $this->getCashRegisterOpeningData($document),
            CashRegisterClosing::class => $this->getCashRegisterClosingData($document),
            default => [
                'id' => $document->id,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ],
        };
    }

    /**
     * Obtiene datos de apertura de caja
     */
    private function getCashRegisterOpeningData(CashRegisterOpening $opening): array
    {
        return [
            'id' => $opening->id,
            'opened_at' => $opening->opened_at,
            'opened_by' => $opening->openedBy?->name,
            'opening_balance' => $opening->opening_balance,
            'notes' => $opening->notes,
            'status' => $opening->status,
        ];
    }

    /**
     * Obtiene datos de cierre de caja
     */
    private function getCashRegisterClosingData(CashRegisterClosing $closing): array
    {
        return [
            'id' => $closing->id,
            'closed_at' => $closing->closed_at,
            'closed_by' => $closing->closedBy?->name,
            'opening_balance' => $closing->opening_balance,
            'closing_balance' => $closing->closing_balance,
            'expected_balance' => $closing->expected_balance,
            'difference' => $closing->difference,
            'total_sales' => $closing->total_sales,
            'total_cash' => $closing->total_cash,
            'total_card' => $closing->total_card,
            'total_other' => $closing->total_other,
            'notes' => $closing->notes,
            'status' => $closing->status,
        ];
    }

    /**
     * Obtiene el nombre legible del tipo de documento
     */
    private function getDocumentTypeName($document): string
    {
        $className = get_class($document);

        return match ($className) {
            CashRegisterOpening::class => 'Apertura de Caja',
            CashRegisterClosing::class => 'Cierre de Caja',
            default => 'Documento Interno',
        };
    }

    /**
     * Descarga el PDF del documento interno
     */
    public function downloadPdf(Request $request, string $hash)
    {
        $user = auth()->user();

        // Buscar el documento
        $document = $this->findDocumentByHash($hash);

        if (!$document) {
            abort(404, 'Documento no encontrado');
        }

        // Verificar permisos
        if (!$document->canBeVerifiedBy($user)) {
            abort(403, 'No tiene permisos para descargar este documento');
        }

        // Registrar acceso
        activity()
            ->causedBy($user)
            ->performedOn($document)
            ->withProperties([
                'action' => 'download_internal_document_pdf',
                'verification_hash' => $document->verification_hash,
                'document_type' => get_class($document),
            ])
            ->log('PDF de documento interno descargado');

        // Generar y retornar PDF
        return $document->downloadPdf();
    }

    /**
     * Endpoint API para verificar documento (para uso en aplicaciones móviles)
     */
    public function verify(Request $request, string $hash)
    {
        $user = auth()->user();

        $document = $this->findDocumentByHash($hash);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado',
            ], 404);
        }

        if (!$document->canBeVerifiedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para verificar este documento',
                'required_permission' => $document->getVerificationPermission(),
            ], 403);
        }

        // Registrar acceso
        $document->logVerificationAccess($user);

        return response()->json([
            'success' => true,
            'message' => 'Documento verificado exitosamente',
            'document' => [
                'type' => $this->getDocumentTypeName($document),
                'data' => $this->getDocumentData($document),
                'verification_count' => $document->getVerificationCount(),
                'last_verification' => $document->getLastVerification(),
            ],
        ]);
    }
}
