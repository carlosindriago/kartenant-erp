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
        Schema::table('tenants', function (Blueprint $table) {
            // Tipo de acceso: 'public', 'private', 'role_based'
            $table->string('verification_access_type', 20)->default('public')->after('database');
            
            // Array de IDs de roles permitidos (solo para role_based)
            $table->json('verification_allowed_roles')->nullable()->after('verification_access_type');
            
            // Habilitar/deshabilitar completamente la verificación
            $table->boolean('verification_enabled')->default(true)->after('verification_allowed_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'verification_access_type',
                'verification_allowed_roles',
                'verification_enabled',
            ]);
        });
    }
};
