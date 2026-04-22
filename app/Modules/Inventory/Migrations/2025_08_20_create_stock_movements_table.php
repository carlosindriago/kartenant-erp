<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sale', 'purchase', 'adjustment', 'return', 'damage', 'transfer']);
            $table->integer('quantity'); // Puede ser negativo para salidas
            $table->string('reason')->nullable();
            $table->string('reference')->nullable(); // Número de factura, orden, etc.
            $table->string('user_name'); // Nombre del usuario que hizo el movimiento
            $table->integer('previous_stock');
            $table->integer('new_stock');

            // Identificador del tenant dueño del registro
            $table->unsignedBigInteger('tenant_id')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
