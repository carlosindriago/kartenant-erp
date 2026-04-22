<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->string('product_name'); // Snapshot del nombre del producto al momento de la venta
            $table->string('product_code')->nullable(); // Snapshot del código
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Precio unitario en el momento de la venta
            $table->decimal('tax_rate', 5, 2)->default(0); // Tasa de impuesto aplicada
            $table->decimal('tax_amount', 10, 2)->default(0); // Monto de impuesto
            $table->decimal('discount_amount', 10, 2)->default(0); // Descuento en este item
            $table->decimal('subtotal', 10, 2); // Subtotal (quantity * unit_price)
            $table->decimal('total', 10, 2); // Total con impuestos y descuentos
            $table->timestamps();
            
            // Indexes
            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
