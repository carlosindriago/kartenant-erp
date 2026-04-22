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
        // Only add the column if it doesn't exist (prevents duplicate column error)
        if (!Schema::hasColumn('sales', 'cash_register_id')) {
            Schema::table('sales', function (Blueprint $table) {
                // Agregar cash_register_id para asociar cada venta a una caja específica
                $table->foreignId('cash_register_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('cash_registers')
                    ->onDelete('set null');

                // Índice para mejorar consultas por caja
                $table->index('cash_register_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_register_id']);
            $table->dropIndex(['cash_register_id']);
            $table->dropColumn('cash_register_id');
        });
    }
};
