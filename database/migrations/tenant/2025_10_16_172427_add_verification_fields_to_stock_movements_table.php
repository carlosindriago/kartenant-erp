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
        Schema::connection('tenant')->table('stock_movements', function (Blueprint $table) {
            // Campos para verificación interna
            $table->string('document_number', 50)->nullable()->unique()->after('reference');
            $table->string('verification_hash', 64)->nullable()->after('document_number');
            $table->timestamp('verification_generated_at')->nullable()->after('verification_hash');

            // Campos adicionales para detalles del movimiento
            $table->string('supplier', 200)->nullable()->after('verification_generated_at');
            $table->string('invoice_reference', 100)->nullable()->after('supplier');
            $table->string('batch_number', 100)->nullable()->after('invoice_reference');
            $table->date('expiry_date')->nullable()->after('batch_number');
            $table->text('additional_notes')->nullable()->after('expiry_date');

            // Usuario que autorizó (para salidas que requieren autorización)
            $table->unsignedBigInteger('authorized_by')->nullable()->after('additional_notes');
            $table->timestamp('authorized_at')->nullable()->after('authorized_by');

            // Formato PDF preferido (thermal o a4)
            $table->string('pdf_format', 10)->default('a4')->after('authorized_at');

            // Índices para búsqueda rápida
            $table->index('document_number');
            $table->index('verification_hash');
            $table->index('batch_number');
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['document_number']);
            $table->dropIndex(['verification_hash']);
            $table->dropIndex(['batch_number']);
            $table->dropIndex(['type', 'created_at']);

            $table->dropColumn([
                'document_number',
                'verification_hash',
                'verification_generated_at',
                'supplier',
                'invoice_reference',
                'batch_number',
                'expiry_date',
                'additional_notes',
                'authorized_by',
                'authorized_at',
                'pdf_format',
            ]);
        });
    }
};
