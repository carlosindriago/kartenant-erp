<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Observers;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductObserver
{
    public function updating(Product $product): void
    {
        // IMPORTANTE: Este observer solo debe ejecutarse para cambios MANUALES de stock
        // NO debe ejecutarse cuando StockMovementService actualiza el stock
        // porque el servicio ya crea el movimiento correspondiente

        // Detectar cambios en el stock y registrar movimiento
        if ($product->isDirty('stock')) {
            // Verificar si hay un StockMovement siendo creado en esta transacción
            // Si existe, significa que el cambio viene del servicio, no lo duplicamos
            $recentMovement = StockMovement::where('product_id', $product->id)
                ->where('created_at', '>=', now()->subSeconds(2))
                ->latest()
                ->first();

            // Si ya existe un movimiento reciente (últimos 2 segundos), no crear otro
            if ($recentMovement) {
                return;
            }

            $oldStock = $product->getOriginal('stock');
            $newStock = $product->stock;

            // Si el stock no cambió realmente, no hacer nada
            if ($oldStock === $newStock) {
                return;
            }

            $quantity = abs($newStock - $oldStock);

            // Determinar tipo según si aumenta o disminuye
            $type = $newStock > $oldStock ? 'entrada' : 'salida';
            $reason = $type === 'entrada'
                ? 'Ajuste de Inventario (Aumento)'
                : 'Ajuste de Inventario (Disminución)';

            // Crear movimiento de stock
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference' => 'Ajuste Manual',
                'user_name' => Filament::auth()->user()?->name ?? 'Sistema',
                'previous_stock' => $oldStock,
                'new_stock' => $newStock,
            ]);

            // Generar número de documento para el ajuste
            $movement->document_number = $movement->generateDocumentNumber();
            $movement->save();

            // Generar hash de verificación
            $movement->ensureVerificationHash();
        }
    }

    public function saved(Product $product): void
    {
        // Optimizar imagen si cambió y existe
        if ($product->wasChanged('image') && $product->image) {
            // La imagen puede venir con o sin el prefijo products/
            $imageName = str_replace('products/', '', $product->image);
            $imagePath = 'products/'.$imageName;

            if (Storage::disk('public')->exists($imagePath)) {
                $fullPath = Storage::disk('public')->path($imagePath);

                // Solo optimizar si no es WebP
                if (! str_ends_with($imageName, '.webp')) {
                    try {
                        $manager = new ImageManager(new Driver);
                        $image = $manager->read($fullPath);
                        $image->scale(width: 800, height: 800);
                        $encoded = $image->toWebp(quality: 80);

                        // Generar nuevo nombre WebP
                        $newName = pathinfo($imageName, PATHINFO_FILENAME).'.webp';
                        $newPath = 'products/'.$newName;

                        // Guardar como WebP
                        Storage::disk('public')->put($newPath, (string) $encoded);

                        // Eliminar imagen original
                        Storage::disk('public')->delete($imagePath);

                        // Actualizar el nombre en la BD (sin triggear el observer de nuevo)
                        $product->updateQuietly(['image' => $newName]);
                    } catch (\Exception $e) {
                        // Si falla la optimización, mantener la imagen original
                        logger()->error('Error optimizing image: '.$e->getMessage());
                    }
                }
            }
        }
    }
}
