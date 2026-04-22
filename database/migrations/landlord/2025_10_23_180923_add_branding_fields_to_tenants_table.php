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
            // Tipo de logo: 'text' para mostrar nombre, 'image' para mostrar imagen
            $table->enum('logo_type', ['text', 'image'])->default('text')->after('currency');

            // Nombre de la empresa a mostrar (usado cuando logo_type = 'text')
            $table->string('company_display_name')->nullable()->after('logo_type');

            // Ruta del archivo de logo (usado cuando logo_type = 'image')
            $table->string('logo_path')->nullable()->after('company_display_name');

            // Color de fondo para el logo (hexadecimal) - opcional
            $table->string('logo_background_color', 7)->nullable()->after('logo_path');

            // Color del texto para logo tipo texto (hexadecimal) - opcional
            $table->string('logo_text_color', 7)->nullable()->after('logo_background_color');

            // Índices para búsquedas
            $table->index('logo_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['logo_type']);
            $table->dropColumn([
                'logo_type',
                'company_display_name',
                'logo_path',
                'logo_background_color',
                'logo_text_color',
            ]);
        });
    }
};
