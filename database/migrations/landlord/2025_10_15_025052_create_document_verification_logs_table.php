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
        Schema::create('document_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verification_id')->comment('FK a document_verifications');
            $table->string('ip_address', 45)->nullable()->comment('IP del verificador');
            $table->text('user_agent')->nullable()->comment('Browser info');
            $table->timestamp('verified_at')->comment('Cuándo se verificó');
            $table->string('result', 20)->comment('valid, invalid, expired');
            $table->timestamps();

            // Foreign key
            $table->foreign('verification_id')
                ->references('id')
                ->on('document_verifications')
                ->onDelete('cascade');

            // Índices
            $table->index('verification_id', 'idx_verification_id');
            $table->index('verified_at', 'idx_verified_at');
            $table->index('result', 'idx_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_verification_logs');
    }
};
