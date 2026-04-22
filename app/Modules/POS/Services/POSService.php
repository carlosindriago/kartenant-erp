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
use App\Modules\POS\Models\SaleItem;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\MovementReason;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class POSService
{
    /**
     * Process a sale transaction
     * 
     * @param array $saleData
     * @param array $items
     * @return Sale
     * @throws \Exception
     */
    public function processSale(array $saleData, array $items): Sale
    {
        return DB::transaction(function () use ($saleData, $items) {
            // 1. Validate stock availability
            $this->validateStockAvailability($items);
            
            // 2. Create sale record
            $sale = $this->createSale($saleData);
            
            // 3. Create sale items and update inventory
            foreach ($items as $item) {
                $this->createSaleItem($sale, $item);
                $this->updateInventory($sale, $item);
            }
            
            // 4. Generar hash de seguridad único e inmutable para el comprobante
            $sale->verification_hash = hash('sha256', implode('|', [
                'Sale',
                $sale->id,
                $sale->invoice_number,
                $sale->created_at,
                $sale->total,
                config('app.key'),
                random_bytes(16),
            ]));
            $sale->verification_generated_at = now();
            $sale->saveQuietly();
            
            // 5. Load relationships for return
            $sale->load(['items', 'customer', 'user']);
            
            return $sale;
        });
    }
    
    /**
     * Validate that all products have sufficient stock
     */
    protected function validateStockAvailability(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            
            if ($product->stock < $item['quantity']) {
                throw new \Exception(
                    "Stock insuficiente para {$product->name}. " .
                    "Disponible: {$product->stock}, Solicitado: {$item['quantity']}"
                );
            }
        }
    }
    
    /**
     * Create sale record
     */
    protected function createSale(array $data): Sale
    {
        return Sale::create([
            'invoice_number' => $data['invoice_number'] ?? Sale::generateInvoiceNumber(),
            'cash_register_id' => $data['cash_register_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => auth()->id() ?? auth('tenant')->id(),
            'status' => 'completed',
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'total' => $data['total'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'amount_paid' => $data['amount_paid'],
            'change_amount' => $data['change_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);
    }
    
    /**
     * Create sale item with product snapshot
     */
    protected function createSaleItem(Sale $sale, array $itemData): SaleItem
    {
        $product = Product::findOrFail($itemData['product_id']);
        
        $quantity = $itemData['quantity'];
        $unitPrice = $itemData['unit_price'] ?? $product->price;
        $taxRate = $itemData['tax_rate'] ?? 0;
        $discountAmount = $itemData['discount_amount'] ?? 0;
        
        $subtotal = $quantity * $unitPrice;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $total = $subtotal + $taxAmount - $discountAmount;
        
        return SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }
    
    /**
     * Update inventory and create stock movement
     */
    protected function updateInventory(Sale $sale, array $itemData): void
    {
        $product = Product::findOrFail($itemData['product_id']);
        $quantity = $itemData['quantity'];
        
        // Get or create "Venta" movement reason
        $movementReason = MovementReason::firstOrCreate(
            [
                'name' => 'Venta',
                'type' => 'salida',
            ],
            [
                'is_active' => true,
            ]
        );
        
        $previousStock = $product->stock;
        $newStock = $previousStock - $quantity;
        
        // Update product stock (quietly to avoid triggering observer)
        $product->updateQuietly(['stock' => $newStock]);
        
        // Get current authenticated user
        $currentUser = auth('tenant')->user() ?? auth('web')->user();
        
        // Create stock movement record
        StockMovement::create([
            'product_id' => $product->id,
            'movement_reason_id' => $movementReason->id,
            'type' => 'salida',
            'quantity' => $quantity,
            'reason' => "Venta #{$sale->invoice_number}",
            'reference' => $sale->invoice_number,
            'user_name' => $currentUser?->name ?? 'Sistema',
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
        ]);
    }
    
    /**
     * Cancel a sale and restore inventory
     */
    public function cancelSale(Sale $sale, string $reason): Sale
    {
        if (!$sale->canBeCancelled()) {
            throw new \Exception('Esta venta no puede ser cancelada');
        }
        
        return DB::transaction(function () use ($sale, $reason) {
            // Restore inventory for each item
            foreach ($sale->items as $item) {
                $this->restoreInventory($sale, $item);
            }
            
            // Update sale status
            $sale->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id() ?? auth('tenant')->id(),
                'cancellation_reason' => $reason,
            ]);
            
            return $sale->fresh(['items', 'customer', 'user']);
        });
    }
    
    /**
     * Restore inventory after cancellation
     */
    protected function restoreInventory(Sale $sale, SaleItem $item): void
    {
        $product = Product::findOrFail($item->product_id);
        
        // Get or create "Devolución de Venta" movement reason
        $movementReason = MovementReason::firstOrCreate(
            [
                'name' => 'Devolución de Venta',
                'type' => 'entrada',
            ],
            [
                'is_active' => true,
            ]
        );
        
        $previousStock = $product->stock;
        $newStock = $previousStock + $item->quantity;
        
        // Update product stock (quietly to avoid triggering observer)
        $product->updateQuietly(['stock' => $newStock]);
        
        // Get current authenticated user
        $currentUser = auth('tenant')->user() ?? auth('web')->user();
        
        // Create stock movement record
        StockMovement::create([
            'product_id' => $product->id,
            'movement_reason_id' => $movementReason->id,
            'type' => 'entrada',
            'quantity' => $item->quantity,
            'reason' => "Devolución por cancelación de Venta #{$sale->invoice_number}",
            'reference' => $sale->invoice_number,
            'user_name' => $currentUser?->name ?? 'Sistema',
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
        ]);
    }
}
