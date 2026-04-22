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
        if (!Schema::connection('tenant')->hasColumn('cash_registers', 'verification_hash')) {
            Schema::connection('tenant')->table('cash_registers', function (Blueprint $table) {
                // Campos para verificación interna
                $table->string('verification_hash')->nullable()->unique()->after('forced_reason');
                $table->timestamp('verification_generated_at')->nullable()->after('verification_hash');
                $table->string('pdf_format')->default('thermal')->after('verification_generated_at'); // 'thermal' o 'a4'
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['verification_hash', 'verification_generated_at', 'pdf_format']);
        });
    }
};
