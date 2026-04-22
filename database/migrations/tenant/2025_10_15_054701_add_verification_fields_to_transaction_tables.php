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
        // Agregar campos de verificación a la tabla sales (tenant)
        Schema::connection('tenant')->table('sales', function (Blueprint $table) {
            $table->string('verification_hash', 64)->nullable()->after('notes')->index();
            $table->timestamp('verification_generated_at')->nullable()->after('verification_hash');
        });
        
        // Agregar campos de verificación a la tabla sale_returns (tenant)
        Schema::connection('tenant')->table('sale_returns', function (Blueprint $table) {
            $table->string('verification_hash', 64)->nullable()->after('processed_at')->index();
            $table->timestamp('verification_generated_at')->nullable()->after('verification_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('sales', function (Blueprint $table) {
            $table->dropColumn(['verification_hash', 'verification_generated_at']);
        });
        
        Schema::connection('tenant')->table('sale_returns', function (Blueprint $table) {
            $table->dropColumn(['verification_hash', 'verification_generated_at']);
        });
    }
};
