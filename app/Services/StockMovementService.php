<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StockMovementService
{
    /**
     * Registrar entrada de mercadería
     */
    public function registerEntry(
        Product $product,
        int $quantity,
        string $reason,
        User $registeredBy,
        ?int $supplierId = null,
        ?string $invoiceReference = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $additionalNotes = null,
        ?string $reference = null,
        string $pdfFormat = 'a4'
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $quantity,
            $reason,
            $registeredBy,
            $supplierId,
            $invoiceReference,
            $batchNumber,
            $expiryDate,
            $additionalNotes,
            $reference,
            $pdfFormat
        ) {
            // Obtener stock actual
            $previousStock = $product->stock;
            $newStock = $previousStock + $quantity;

            // Crear movimiento
            $movement = new StockMovement([
                'product_id' => $product->id,
                'supplier_id' => $supplierId,
                'type' => 'entrada',
                'quantity' => $quantity,
                'reason' => $reason,
                'reference' => $reference,
                'user_name' => $registeredBy->name,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'invoice_reference' => $invoiceReference,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'additional_notes' => $additionalNotes,
                'pdf_format' => $pdfFormat,
            ]);

            // Generar número de documento
            $movement->document_number = $movement->generateDocumentNumber();

            $movement->save();

            // Generar hash de verificación (después de guardar para tener ID)
            $movement->ensureVerificationHash();

            // Actualizar stock del producto SIN disparar observers ni eventos
            $product->stock = $newStock;
            $product->saveQuietly();

            // Log de auditoría
            activity()
                ->performedOn($movement)
                ->causedBy($registeredBy)
                ->withProperties([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'document_number' => $movement->document_number,
                ])
                ->log('Entrada de mercadería registrada');

            Log::info('Entrada de mercadería registrada', [
                'movement_id' => $movement->id,
                'document_number' => $movement->document_number,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'user_id' => $registeredBy->id,
            ]);

            return $movement->fresh(['product', 'authorizedBy']);
        });
    }

    /**
     * Registrar salida de mercadería
     */
    public function registerExit(
        Product $product,
        int $quantity,
        string $reason,
        User $registeredBy,
        ?User $authorizedBy = null,
        ?string $additionalNotes = null,
        ?string $reference = null,
        string $pdfFormat = 'a4'
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $quantity,
            $reason,
            $registeredBy,
            $authorizedBy,
            $additionalNotes,
            $reference,
            $pdfFormat
        ) {
            // Validar stock disponible
            if ($product->stock < $quantity) {
                throw new \Exception("Stock insuficiente. Disponible: {$product->stock}, Solicitado: {$quantity}");
            }

            // Obtener stock actual
            $previousStock = $product->stock;
            $newStock = $previousStock - $quantity;

            // Crear movimiento
            $movement = new StockMovement([
                'product_id' => $product->id,
                'type' => 'salida',
                'quantity' => $quantity,
                'reason' => $reason,
                'reference' => $reference,
                'user_name' => $registeredBy->name,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'additional_notes' => $additionalNotes,
                'authorized_by' => $authorizedBy?->id,
                'authorized_at' => $authorizedBy ? now() : null,
                'pdf_format' => $pdfFormat,
            ]);

            // Generar número de documento
            $movement->document_number = $movement->generateDocumentNumber();

            $movement->save();

            // Generar hash de verificación (después de guardar para tener ID)
            $movement->ensureVerificationHash();

            // Actualizar stock del producto SIN disparar observers ni eventos
            $product->stock = $newStock;
            $product->saveQuietly();

            // Log de auditoría
            activity()
                ->performedOn($movement)
                ->causedBy($registeredBy)
                ->withProperties([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'document_number' => $movement->document_number,
                    'authorized_by' => $authorizedBy?->name,
                ])
                ->log('Salida de mercadería registrada');

            Log::info('Salida de mercadería registrada', [
                'movement_id' => $movement->id,
                'document_number' => $movement->document_number,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'user_id' => $registeredBy->id,
                'authorized_by' => $authorizedBy?->id,
            ]);

            return $movement->fresh(['product', 'authorizedBy']);
        });
    }

    /**
     * Descargar PDF del movimiento
     */
    public function downloadMovementPdf(StockMovement $movement, ?string $format = null): Response
    {
        // Si se especifica formato, actualizarlo
        if ($format && in_array($format, ['thermal', 'a4'])) {
            $movement->update(['pdf_format' => $format]);
        }

        return $movement->downloadPdf();
    }

    /**
     * Verificar si una salida requiere autorización
     */
    public function requiresAuthorization(Product $product, int $quantity): bool
    {
        // Criterios para requerir autorización:
        // 1. Cantidad mayor al 50% del stock
        // 2. Valor total mayor a umbral configurado
        // 3. Producto crítico

        $halfStock = $product->stock / 2;

        return $quantity > $halfStock;
    }

    /**
     * Obtener resumen de movimientos por período
     */
    public function getMovementsSummary(?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $movements = StockMovement::whereBetween('created_at', [$from, $to])->get();

        return [
            'total_entries' => $movements->where('type', 'entrada')->count(),
            'total_exits' => $movements->where('type', 'salida')->count(),
            'total_quantity_in' => $movements->where('type', 'entrada')->sum('quantity'),
            'total_quantity_out' => $movements->where('type', 'salida')->sum('quantity'),
            'unique_products' => $movements->pluck('product_id')->unique()->count(),
            'period_from' => $from->format('d/m/Y'),
            'period_to' => $to->format('d/m/Y'),
        ];
    }
}
