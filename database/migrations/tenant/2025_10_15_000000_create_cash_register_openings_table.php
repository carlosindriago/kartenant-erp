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
        Schema::create('cash_register_openings', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('opening_number')->unique()->comment('Número de apertura');

            // Usuario que abrió la caja (no foreign key - users table is in landlord DB)
            $table->unsignedBigInteger('opened_by')->comment('Usuario que abrió');
            $table->timestamp('opened_at')->comment('Fecha y hora de apertura');

            // Montos
            $table->decimal('opening_balance', 15, 2)->default(0)->comment('Saldo inicial');

            // Información adicional
            $table->text('notes')->nullable()->comment('Notas u observaciones');
            $table->enum('status', ['open', 'closed'])->default('open')->comment('Estado de la caja');

            // Verificación interna
            $table->string('verification_hash', 64)->unique()->nullable()->comment('Hash para verificación');
            $table->timestamp('verification_generated_at')->nullable()->comment('Fecha generación hash');

            // PDF format preference
            $table->enum('pdf_format', ['thermal', 'a4'])->default('thermal')->comment('Formato PDF');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('opened_by');
            $table->index('opened_at');
            $table->index('status');
            $table->index('verification_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_openings');
    }
};
