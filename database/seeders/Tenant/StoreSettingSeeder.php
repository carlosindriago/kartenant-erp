<?php

namespace Database\Seeders\Tenant;

use App\Models\StoreSetting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if store_settings table exists
        if (! DB::getSchemaBuilder()->hasTable('store_settings')) {
            $this->command->error('La tabla "store_settings" no existe. Por favor ejecuta la migración primero.');

            return;
        }

        // Get the current tenant info for personalization
        $tenantInfo = null;
        if (function_exists('tenant')) {
            $tenant = tenant();
            if ($tenant) {
                $tenantInfo = [
                    'name' => $tenant->name ?? 'Tienda',
                    'domain' => $tenant->domain ?? null,
                ];
            }
        }

        // Create default store settings
        $defaultSettings = [
            'logo_path' => null,
            'background_image_path' => null,
            'brand_color' => '#2563eb',
            'welcome_message' => $this->generateWelcomeMessage($tenantInfo),
            'store_name' => $tenantInfo['name'] ?? config('app.name', 'Mi Tienda'),
            'store_slogan' => 'Tu sistema de gestión comercial',
            'is_active' => true,
            'show_background_image' => true,
            'primary_font' => 'Inter',
            'facebook_url' => null,
            'instagram_url' => null,
            'whatsapp_number' => null,
            'contact_email' => null,
        ];

        // Create store settings if they don't exist
        $existingSettings = StoreSetting::first();

        if (! $existingSettings) {
            StoreSetting::create($defaultSettings);
            $this->command->info('✅ StoreSettings por defecto creados exitosamente');
        } else {
            $this->command->info('ℹ️  StoreSettings ya existen, no se realizaron cambios');
        }

        // Create sample data for development (only if environment allows it)
        if (app()->environment('local', 'testing')) {
            $this->createSampleData();
        }
    }

    /**
     * Generate a personalized welcome message.
     */
    private function generateWelcomeMessage(?array $tenantInfo): string
    {
        $storeName = $tenantInfo['name'] ?? 'tu tienda';
        $domain = $tenantInfo['domain'] ?? null;

        $messages = [
            "¡Bienvenido a {$storeName}! Gestiona tu inventario de forma sencilla y eficiente.",
            "¡Hola! Administer tu ferretería {$storeName} con nuestro sistema profesional.",
            "¡Bienvenido! Tu sistema de gestión para {$storeName} está listo para usar.",
            "¡Hola! Potencia tu negocio {$storeName} con herramientas modernas.",
            "¡Bienvenido a {$storeName}! La forma más sencilla de gestionar tu inventario y ventas.",
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Create sample data for development environments.
     */
    private function createSampleData(): void
    {
        $this->command->info('🎨 Creando datos de ejemplo para desarrollo...');

        // Create sample store settings with different configurations
        $samples = [
            [
                'store_name' => 'Ferretería El Constructor',
                'store_slogan' => 'Herramientas de calidad para profesionales',
                'brand_color' => '#dc2626',
                'welcome_message' => '¡Bienvenido a Ferretería El Constructor! Las mejores herramientas para tus proyectos.',
                'primary_font' => 'Inter',
                'facebook_url' => 'https://facebook.com/ferreteriaconstructor',
                'instagram_url' => 'https://instagram.com/ferreteriaconstructor',
                'whatsapp_number' => '1234567890',
                'contact_email' => 'contacto@ferreteriaconstructor.com',
                'is_active' => true,
                'show_background_image' => true,
            ],
            [
                'store_name' => 'Herramienta Pro',
                'store_slogan' => 'Tu socio en construcción',
                'brand_color' => '#0891b2',
                'welcome_message' => '¡Bienvenido a Herramienta Pro! Equipamiento profesional para tus obras.',
                'primary_font' => 'Roboto',
                'facebook_url' => 'https://facebook.com/herramientapro',
                'instagram_url' => null,
                'whatsapp_number' => '0987654321',
                'contact_email' => 'ventas@herramientapro.com',
                'is_active' => true,
                'show_background_image' => false,
            ],
            [
                'store_name' => 'Todo Bienes y Raíces',
                'store_slogan' => 'Construyendo el futuro',
                'brand_color' => '#16a34a',
                'welcome_message' => '¡Bienvenido a Todo Bienes! Materiales de construcción con garantía de calidad.',
                'primary_font' => 'Poppins',
                'facebook_url' => null,
                'instagram_url' => 'https://instagram.com/todobienes',
                'whatsapp_number' => '1122334455',
                'contact_email' => 'info@todobienes.com',
                'is_active' => true,
                'show_background_image' => true,
            ],
        ];

        // Delete existing sample data
        StoreSetting::where('id', '>', 1)->delete();

        // Create new sample data
        foreach ($samples as $sample) {
            StoreSetting::create($sample);
        }

        $this->command->info('✅ Datos de ejemplo creados exitosamente');
    }

    /**
     * Run the seeder for a specific tenant ID.
     */
    public static function runForTenant(int $tenantId): void
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            throw new \Exception("Tenant con ID {$tenantId} no encontrado");
        }

        // Make this tenant current
        tenancy()->initialize($tenant);

        // Run the seeder
        $seeder = new self;
        $seeder->run();

        // Forget the tenant
        tenancy()->end();
    }
}
