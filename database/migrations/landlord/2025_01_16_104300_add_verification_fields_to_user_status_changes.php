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
        if (Schema::hasTable('user_status_changes')) {
            Schema::table('user_status_changes', function (Blueprint $table) {
                // Campos de verificación interna
                $table->string('verification_hash', 64)->nullable()->after('changed_at');
                $table->timestamp('verification_generated_at')->nullable()->after('verification_hash');

                // Campos adicionales para el comprobante
                $table->string('document_number', 50)->nullable()->after('verification_generated_at');
                $table->text('additional_notes')->nullable()->after('document_number');

                // Índices para búsqueda rápida
                $table->index('verification_hash');
                $table->index('document_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_status_changes')) {
            Schema::table('user_status_changes', function (Blueprint $table) {
                $table->dropIndex(['verification_hash']);
                $table->dropIndex(['document_number']);

                $table->dropColumn([
                    'verification_hash',
                    'verification_generated_at',
                    'document_number',
                    'additional_notes',
                ]);
            });
        }
    }
};
