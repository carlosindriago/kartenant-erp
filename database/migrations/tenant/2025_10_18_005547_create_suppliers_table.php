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
        Schema::connection('tenant')->create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la empresa proveedora
            $table->string('contact_name')->nullable(); // Nombre de la persona de contacto
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('cuit')->nullable(); // ID fiscal
            $table->text('address')->nullable();
            $table->text('notes')->nullable(); // Comentarios adicionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('suppliers');
    }
};
