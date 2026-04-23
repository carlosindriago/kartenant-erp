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
        Schema::create('cash_register_closings', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('closing_number')->unique()->comment('Número de cierre');

            // Relación con apertura
            $table->foreignId('opening_id')->constrained('cash_register_openings')->comment('Apertura relacionada');

            // Usuario que cerró la caja (no foreign key - users table is in landlord DB)
            $table->unsignedBigInteger('closed_by')->comment('Usuario que cerró');
            $table->timestamp('closed_at')->comment('Fecha y hora de cierre');

            // Montos
            $table->decimal('opening_balance', 15, 2)->default(0)->comment('Saldo inicial (del opening)');
            $table->decimal('expected_balance', 15, 2)->default(0)->comment('Saldo esperado');
            $table->decimal('closing_balance', 15, 2)->default(0)->comment('Saldo real contado');
            $table->decimal('difference', 15, 2)->default(0)->comment('Diferencia (positiva o negativa)');

            // Totales por método de pago
            $table->decimal('total_sales', 15, 2)->default(0)->comment('Total ventas del día');
            $table->decimal('total_cash', 15, 2)->default(0)->comment('Total en efectivo');
            $table->decimal('total_card', 15, 2)->default(0)->comment('Total en tarjeta');
            $table->decimal('total_other', 15, 2)->default(0)->comment('Total otros métodos');

            // Estadísticas
            $table->integer('total_transactions')->default(0)->comment('Total transacciones');
            $table->decimal('average_ticket', 15, 2)->default(0)->comment('Ticket promedio');

            // Información adicional
            $table->text('notes')->nullable()->comment('Notas u observaciones');
            $table->text('discrepancy_notes')->nullable()->comment('Notas sobre discrepancias');
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review')->comment('Estado del cierre');

            // Verificación interna
            $table->string('verification_hash', 64)->unique()->nullable()->comment('Hash para verificación');
            $table->timestamp('verification_generated_at')->nullable()->comment('Fecha generación hash');

            // PDF format preference
            $table->enum('pdf_format', ['thermal', 'a4'])->default('thermal')->comment('Formato PDF');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('opening_id');
            $table->index('closed_by');
            $table->index('closed_at');
            $table->index('status');
            $table->index('verification_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_closings');
    }
};
