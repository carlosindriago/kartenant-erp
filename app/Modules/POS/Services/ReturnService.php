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

use App\Modules\Inventory\Models\MovementReason;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleItem;
use App\Modules\POS\Models\SaleReturn;
use App\Modules\POS\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class ReturnService
{
    /**
     * 📝 El Método Maestro: Crear Nota de Crédito
     *
     * Este método es el "asiento contable de reversión".
     * Nunca borra la venta original, crea un nuevo documento que la revierte.
     *
     * @param  Sale  $sale  La venta original
     * @param  array  $itemsToReturn  ['sale_item_id' => ['quantity' => X, 'reason' => '...']]
     * @param  string  $refundMethod  Método de reembolso
     * @param  string|null  $generalReason  Razón general de la devolución
     * @return SaleReturn La nota de crédito creada
     *
     * @throws \Exception Si algo falla en el proceso
     */
    public function recordReturn(
        Sale $sale,
        array $itemsToReturn,
        string $refundMethod = 'cash',
        ?string $generalReason = null
    ): SaleReturn {
        return DB::transaction(function () use ($sale, $itemsToReturn, $refundMethod, $generalReason) {
            // 1️⃣ Validar que la venta exista y esté completada
            if ($sale->status !== 'completed') {
                throw new \Exception('Solo se pueden devolver ventas completadas');
            }

            // 2️⃣ Determinar si es devolución completa o parcial
            $returnType = $this->determineReturnType($sale, $itemsToReturn);

            // 3️⃣ Crear la Nota de Crédito (el documento de reversión)
            $saleReturn = SaleReturn::create([
                'original_sale_id' => $sale->id,
                'return_number' => SaleReturn::generateReturnNumber(),
                'status' => 'completed',
                'return_type' => $returnType,
                'reason' => $generalReason,
                'refund_method' => $refundMethod,
                'processed_by_user_id' => auth('tenant')->id() ?? auth('web')->id(),
                'processed_at' => now(),
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
            ]);

            $totalAmount = 0;
            $totalTax = 0;

            // 4️⃣ Procesar cada producto devuelto
            foreach ($itemsToReturn as $saleItemId => $returnData) {
                $quantity = $returnData['quantity'] ?? 0;
                $itemReason = $returnData['reason'] ?? null;

                if ($quantity <= 0) {
                    continue; // Saltar items sin cantidad
                }

                // Obtener el item original de la venta
                $originalItem = $sale->items()->with('product')->findOrFail($saleItemId);

                // Validar cantidad a devolver
                if ($quantity > $originalItem->quantity) {
                    throw new \Exception("No se pueden devolver más unidades de las vendidas para {$originalItem->product_name}");
                }

                // Calcular montos
                $lineTotal = round($quantity * $originalItem->unit_price, 2);
                $lineTax = 0;

                if ($originalItem->product && $originalItem->product->tax) {
                    $taxRate = $originalItem->product->tax->rate;
                    $lineTax = round($lineTotal * ($taxRate / 100), 2);
                }

                $totalAmount += $lineTotal;
                $totalTax += $lineTax;

                // 5️⃣ Crear el item de devolución
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'original_sale_item_id' => $originalItem->id,
                    'product_id' => $originalItem->product_id,
                    'product_name' => $originalItem->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $originalItem->unit_price,
                    'tax_rate' => $originalItem->product->tax->rate ?? 0,
                    'line_total' => $lineTotal,
                    'return_reason' => $itemReason,
                ]);

                // 🎯 6️⃣ CRÍTICO: Obtener producto y stock actual
                $product = Product::find($originalItem->product_id);
                $previousStock = $product ? $product->stock : 0;
                $newStock = $previousStock + $quantity;

                // 🎯 7️⃣ Crear movimiento de entrada al inventario
                // Este es el "asiento contable" que devuelve el stock
                $currentUser = auth('tenant')->user() ?? auth('web')->user();

                StockMovement::create([
                    'product_id' => $originalItem->product_id,
                    'movement_reason_id' => $this->getReturnReasonId(),
                    'type' => 'entrada',
                    'quantity' => $quantity,
                    'reason' => 'Devolución de venta',
                    'reference' => "NCR: {$saleReturn->return_number} (Venta #{$sale->invoice_number})",
                    'user_name' => $currentUser?->name ?? 'Sistema',
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                ]);

                // 8️⃣ Actualizar stock del producto (quietly to avoid triggering observer)
                if ($product) {
                    $product->updateQuietly(['stock' => $product->stock + $quantity]);
                }
            }

            // 9️⃣ Actualizar totales de la devolución
            $saleReturn->update([
                'subtotal' => $totalAmount,
                'tax_amount' => $totalTax,
                'total' => $totalAmount + $totalTax,
            ]);

            // 🔟 Marcar la venta original como ANULADA si es devolución completa
            if ($returnType === 'full') {
                $sale->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth('tenant')->id() ?? auth('web')->id(),
                    'cancellation_reason' => $generalReason ?? 'Anulación completa de venta',
                ]);

                \Log::info('🚫 VENTA MARCADA COMO ANULADA', [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'cancelled_by' => (auth('tenant')->user() ?? auth('web')->user())?->name,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            // 1️⃣1️⃣ 📹 CÁMARA DE SEGURIDAD: Registrar en log para auditoría
            \Log::info('📦 DEVOLUCIÓN PROCESADA EN ReturnService', [
                'event_type' => 'SALE_RETURN',
                'return_id' => $saleReturn->id,
                'return_number' => $saleReturn->return_number,
                'return_type' => $saleReturn->return_type,
                'original_sale_id' => $sale->id,
                'original_sale_number' => $sale->invoice_number,
                'refund_amount' => $saleReturn->total,
                'refund_method' => $refundMethod,
                'items_count' => count($itemsToReturn),
                'items_detail' => collect($itemsToReturn)->map(function ($data, $itemId) {
                    $item = SaleItem::find($itemId);

                    return [
                        'product' => $item->product_name,
                        'quantity_returned' => $data['quantity'],
                        'reason' => $data['reason'] ?? 'N/A',
                    ];
                })->values()->toArray(),
                'stock_movements_created' => count($itemsToReturn),
                'processed_by_user_id' => auth('tenant')->id() ?? auth('web')->id(),
                'processed_by_user_name' => (auth('tenant')->user() ?? auth('web')->user())->name,
                'processed_by_user_email' => (auth('tenant')->user() ?? auth('web')->user())->email,
                'tenant_id' => Tenant::current()->id,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $reason ?? 'N/A',
                'status' => 'COMPLETED',
            ]);

            return $saleReturn->load(['items.product', 'originalSale']);
        });
    }

    /**
     * Determinar si la devolución es completa o parcial
     */
    protected function determineReturnType(Sale $sale, array $itemsToReturn): string
    {
        $originalItems = $sale->items;
        $returningItemsCount = 0;
        $originalItemsCount = 0;

        foreach ($originalItems as $item) {
            $originalItemsCount += $item->quantity;
            $returnQuantity = $itemsToReturn[$item->id]['quantity'] ?? 0;
            $returningItemsCount += $returnQuantity;
        }

        return ($returningItemsCount >= $originalItemsCount) ? 'full' : 'partial';
    }

    /**
     * Obtener el ID de la razón de movimiento "Devolución de venta"
     * Si no existe, la crea
     */
    protected function getReturnReasonId(): int
    {
        $reason = MovementReason::firstOrCreate([
            'name' => 'Devolución de venta',
            'type' => 'entrada',
        ], [
            'is_active' => true,
        ]);

        return $reason->id;
    }

    /**
     * 🚨 Método de Pánico: Anular última venta completa
     *
     * Este es el "botón de deshacer" para errores inmediatos.
     * Devuelve TODOS los productos de la venta más reciente.
     *
     * @param  Sale  $sale  La venta a anular
     * @param  string  $reason  Razón de la anulación
     */
    public function quickCancelLastSale(Sale $sale, string $reason = 'Anulación de venta por error'): SaleReturn
    {
        // 📹 CÁMARA DE SEGURIDAD: Registrar inicio de anulación rápida
        \Log::info('🚨 QUICK CANCEL iniciado en ReturnService', [
            'event_type' => 'QUICK_CANCEL_SALE',
            'sale_id' => $sale->id,
            'sale_number' => $sale->invoice_number,
            'sale_total' => $sale->total,
            'sale_items_count' => $sale->items->count(),
            'reason' => $reason,
            'triggered_by_user_id' => auth('tenant')->id() ?? auth('web')->id(),
            'triggered_by_user_name' => (auth('tenant')->user() ?? auth('web')->user())?->name ?? 'System',
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Preparar array de todos los items para devolución completa
        $itemsToReturn = [];
        foreach ($sale->items as $item) {
            $itemsToReturn[$item->id] = [
                'quantity' => $item->quantity,
                'reason' => $reason,
            ];
        }

        return $this->recordReturn($sale, $itemsToReturn, 'cash', $reason);
    }

    /**
     * Verificar si una venta es elegible para anulación rápida
     * (completada en los últimos 5 minutos)
     */
    public function isEligibleForQuickCancel(Sale $sale): bool
    {
        if ($sale->status !== 'completed') {
            return false;
        }

        $fiveMinutesAgo = now()->subMinutes(5);

        return $sale->created_at->gte($fiveMinutesAgo);
    }
}
