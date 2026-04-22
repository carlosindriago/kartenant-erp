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
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                // --- Datos de Perfil de la Empresa ---
                $table->string('address')->nullable()->after('database');
                $table->string('phone')->nullable()->after('address');
                $table->string('cuit')->nullable()->after('phone'); // CUIT o ID Fiscal

                // --- Datos del Contacto Principal ---
                $table->string('contact_name')->nullable()->after('cuit');
                $table->string('contact_email')->nullable()->after('contact_name');

                // --- Datos de Suscripción y Prueba ---
                $table->string('plan')->default('trial')->after('contact_email'); // trial, basico, pro
                $table->timestamp('trial_ends_at')->nullable()->after('plan');
            });
        }
    }
};
