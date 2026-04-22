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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Número de factura/ticket
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('user_id'); // No foreign key - users table is in landlord DB
            $table->enum('status', ['completed', 'cancelled', 'refunded'])->default('completed');
            $table->decimal('subtotal', 10, 2); // Subtotal sin impuestos
            $table->decimal('tax_amount', 10, 2)->default(0); // Monto de impuestos
            $table->decimal('discount_amount', 10, 2)->default(0); // Descuentos aplicados
            $table->decimal('total', 10, 2); // Total final
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'mixed'])->default('cash');
            $table->decimal('amount_paid', 10, 2); // Monto pagado
            $table->decimal('change_amount', 10, 2)->default(0); // Vuelto
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable(); // No foreign key - users table is in landlord DB
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('invoice_number');
            $table->index('customer_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
