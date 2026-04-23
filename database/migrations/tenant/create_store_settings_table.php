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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            // Logo y branding
            $table->string('logo_path')->nullable()
                ->comment('Ruta al logo del tenant');
            $table->string('background_image_path')->nullable()
                ->comment('Ruta a la imagen de fondo del landing page');
            $table->string('brand_color', 7)->nullable()
                ->comment('Color principal de la marca en formato hex (#FF5733)');

            // Mensajes y contenido
            $table->text('welcome_message')->nullable()
                ->comment('Mensaje de bienvenida personalizado en español');
            $table->string('store_name')->nullable()
                ->comment('Nombre visible de la tienda');
            $table->string('store_slogan')->nullable()
                ->comment('Eslogan o descripción corta de la tienda');

            // Configuración
            $table->boolean('is_active')->default(true)
                ->comment('Activa/desactiva la personalización del landing page');
            $table->boolean('show_background_image')->default(true)
                ->comment('Muestra la imagen de fondo en el landing page');
            $table->string('primary_font')->default('Inter')
                ->comment('Fuente principal para la interfaz');

            // Redes sociales y contacto
            $table->string('facebook_url')->nullable()
                ->comment('URL de Facebook del negocio');
            $table->string('instagram_url')->nullable()
                ->comment('URL de Instagram del negocio');
            $table->string('whatsapp_number')->nullable()
                ->comment('Número de WhatsApp para contacto');
            $table->string('contact_email')->nullable()
                ->comment('Email de contacto público');

            $table->timestamps();

            // Índices para rendimiento
            $table->index(['is_active']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
