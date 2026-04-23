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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_sale_id')->constrained('sales')->onDelete('restrict');
            $table->string('return_number')->unique(); // NCR-YYYYMMDD-XXXX
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('return_type', ['full', 'partial'])->default('partial');
            $table->text('reason')->nullable(); // Razón de la devolución
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('refund_method', ['cash', 'card', 'transfer', 'credit_note'])->default('cash');
            $table->unsignedBigInteger('processed_by_user_id'); // Usuario que procesa
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('original_sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
