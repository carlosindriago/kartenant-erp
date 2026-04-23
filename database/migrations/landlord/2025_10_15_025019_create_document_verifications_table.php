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
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique()->comment('SHA-256 hash del documento');
            $table->string('document_type', 50)->comment('Tipo: sale_report, inventory_report, etc.');
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('Tenant que generó el documento');
            $table->unsignedBigInteger('generated_by')->nullable()->comment('Usuario que generó');
            $table->timestamp('generated_at')->comment('Cuándo se generó');
            $table->json('metadata')->nullable()->comment('Datos adicionales sanitizados');
            $table->unsignedInteger('verification_count')->default(0)->comment('Cuántas veces se verificó');
            $table->timestamp('last_verified_at')->nullable()->comment('Última verificación');
            $table->timestamp('expires_at')->nullable()->comment('Expiración opcional');
            $table->boolean('is_valid')->default(true)->comment('Puede invalidarse manualmente');
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('hash', 'idx_hash');
            $table->index('document_type', 'idx_document_type');
            $table->index('tenant_id', 'idx_tenant_id');
            $table->index('generated_at', 'idx_generated_at');
            $table->index('is_valid', 'idx_is_valid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_verifications');
    }
};
