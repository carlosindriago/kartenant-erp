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
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_sale_item_id')->constrained('sale_items')->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->string('product_name'); // Snapshot del nombre
            $table->integer('quantity'); // Cantidad devuelta
            $table->decimal('unit_price', 10, 2); // Precio al que se vendió
            $table->decimal('tax_rate', 5, 2)->default(0); // % de impuesto
            $table->decimal('line_total', 10, 2); // quantity * unit_price
            $table->text('return_reason')->nullable(); // Razón específica del producto
            $table->timestamps();
            
            $table->index('sale_return_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
