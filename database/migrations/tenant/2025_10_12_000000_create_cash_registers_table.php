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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('register_number')->unique(); // Ej: REG-20250112-0001

            // Apertura
            $table->timestamp('opened_at');
            $table->unsignedBigInteger('opened_by_user_id'); // No FK - users in landlord DB
            $table->decimal('initial_amount', 10, 2); // Fondo inicial

            // Cierre
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable(); // No FK - users in landlord DB
            $table->decimal('expected_amount', 10, 2)->nullable(); // Lo que debería haber
            $table->decimal('actual_amount', 10, 2)->nullable(); // Lo que se contó
            $table->decimal('difference', 10, 2)->default(0); // Sobrante (+) o Faltante (-)

            // Desglose de efectivo contado (JSON para billetes/monedas)
            $table->json('cash_breakdown')->nullable();

            // Estado
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('opening_notes')->nullable(); // Observaciones al abrir
            $table->text('closing_notes')->nullable(); // Observaciones al cerrar

            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index('opened_at');
        });

        // Agregar relación a la tabla sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_register_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('cash_register_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_register_id']);
            $table->dropColumn('cash_register_id');
        });

        Schema::dropIfExists('cash_registers');
    }
};
