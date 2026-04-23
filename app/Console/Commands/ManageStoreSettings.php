<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManageStoreSettings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'emporio:store-settings
                            {action : Action to perform (migrate, seed, reset)}
                            {--tenant= : Specific tenant ID (optional)}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage StoreSettings for tenants - Operation Ernesto Freedom';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $tenantId = $this->option('tenant');
        $force = $this->option('force');

        switch ($action) {
            case 'migrate':
                return $this->migrateStoreSettings($tenantId, $force);

            case 'seed':
                return $this->seedStoreSettings($tenantId, $force);

            case 'reset':
                return $this->resetStoreSettings($tenantId, $force);

            default:
                $this->error("Acción inválida: {$action}");
                $this->info('Acciones disponibles: migrate, seed, reset');

                return 1;
        }
    }

    /**
     * Migrate StoreSettings table for tenants.
     */
    private function migrateStoreSettings(?int $tenantId, bool $force): int
    {
        $tenants = $tenantId ? [Tenant::find($tenantId)] : Tenant::all();

        if ($tenantId && ! $tenants[0]) {
            $this->error("Tenant con ID {$tenantId} no encontrado");

            return 1;
        }

        $this->info('Iniciando migración de StoreSettings...');

        foreach ($tenants as $tenant) {
            if (! $tenant) {
                continue;
            }

            $this->line("Procesando tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                $tenant->makeCurrent();

                // Check if table already exists
                if (Schema::hasTable('store_settings')) {
                    $this->comment("  La tabla 'store_settings' ya existe");

                    continue;
                }

                // Create the table manually (since we can't run migrations in production)
                $this->createStoreSettingsTable();

                $this->info("  ✅ Tabla 'store_settings' creada exitosamente");

                Tenant::forgetCurrent();
            } catch (\Exception $e) {
                $this->error("  ❌ Error procesando tenant {$tenant->id}: {$e->getMessage()}");
                Tenant::forgetCurrent();

                return 1;
            }
        }

        $this->info('✅ Migración de StoreSettings completada');

        return 0;
    }

    /**
     * Seed StoreSettings for tenants.
     */
    private function seedStoreSettings(?int $tenantId, bool $force): int
    {
        $tenants = $tenantId ? [Tenant::find($tenantId)] : Tenant::all();

        if ($tenantId && ! $tenants[0]) {
            $this->error("Tenant con ID {$tenantId} no encontrado");

            return 1;
        }

        $this->info('Iniciando seed de StoreSettings...');

        foreach ($tenants as $tenant) {
            if (! $tenant) {
                continue;
            }

            $this->line("Procesando tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                $tenant->makeCurrent();

                // Check if table exists
                if (! Schema::hasTable('store_settings')) {
                    $this->error("  ❌ La tabla 'store_settings' no existe. Ejecuta 'migrate' primero.");

                    continue;
                }

                // Check if settings already exist using DB directly
                $existing = DB::table('store_settings')->first();
                if ($existing && ! $force) {
                    $this->comment('  StoreSettings ya existen (usa --force para sobreescribir)');

                    continue;
                }

                if ($existing && $force) {
                    DB::table('store_settings')->delete();
                    $this->comment('  StoreSettings existentes eliminados');
                }

                // Create default settings using DB directly
                DB::table('store_settings')->insert([
                    'store_name' => $tenant->name ?? config('app.name', 'Mi Tienda'),
                    'welcome_message' => "¡Bienvenido a {$tenant->name}! Gestiona tu inventario de forma sencilla y eficiente.",
                    'brand_color' => '#2563eb',
                    'store_slogan' => 'Tu sistema de gestión comercial',
                    'is_active' => true,
                    'show_background_image' => true,
                    'primary_font' => 'Inter',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info('  ✅ StoreSettings creados exitosamente');

                Tenant::forgetCurrent();
            } catch (\Exception $e) {
                $this->error("  ❌ Error procesando tenant {$tenant->id}: {$e->getMessage()}");
                Tenant::forgetCurrent();

                return 1;
            }
        }

        $this->info('✅ Seed de StoreSettings completado');

        return 0;
    }

    /**
     * Reset StoreSettings for tenants.
     */
    private function resetStoreSettings(?int $tenantId, bool $force): int
    {
        if (! $force && ! $this->confirm('¿Estás seguro de que quieres eliminar todos los StoreSettings? Esta acción no se puede deshacer.')) {
            $this->info('Operación cancelada');

            return 0;
        }

        $tenants = $tenantId ? [Tenant::find($tenantId)] : Tenant::all();

        if ($tenantId && ! $tenants[0]) {
            $this->error("Tenant con ID {$tenantId} no encontrado");

            return 1;
        }

        $this->info('Iniciando reset de StoreSettings...');

        foreach ($tenants as $tenant) {
            if (! $tenant) {
                continue;
            }

            $this->line("Procesando tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                $tenant->makeCurrent();

                // Drop table if exists
                if (Schema::hasTable('store_settings')) {
                    Schema::dropIfExists('store_settings');
                    $this->info("  ✅ Tabla 'store_settings' eliminada");
                } else {
                    $this->comment("  La tabla 'store_settings' no existe");
                }

                Tenant::forgetCurrent();
            } catch (\Exception $e) {
                $this->error("  ❌ Error procesando tenant {$tenant->id}: {$e->getMessage()}");
                Tenant::forgetCurrent();

                return 1;
            }
        }

        $this->info('✅ Reset de StoreSettings completado');

        return 0;
    }

    /**
     * Create the store_settings table manually.
     */
    private function createStoreSettingsTable(): void
    {
        Schema::create('store_settings', function ($table) {
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
}
