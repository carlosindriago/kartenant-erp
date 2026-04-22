<?php

namespace Database\Factories;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = StoreSetting::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $businessNames = [
            'Ferretería El Constructor',
            'Herramienta Pro',
            'Todo Bienes y Raíces',
            'Constructora del Pueblo',
            'Ferretería La Central',
            'Herramientas y Más',
            'El Constructor Feliz',
            'Ferretería Express',
            'Tu Ferretería de Confianza',
            'Herramientas Proveedor',
        ];

        $slogans = [
            'Las mejores herramientas para tus proyectos',
            'Construimos sueños, proyecto a proyecto',
            'Calidad y confianza en cada herramienta',
            'Tu socio en construcción',
            'Herramientas que trabajan contigo',
            'El alma de la construcción',
            'Profesionales eligen profesionales',
            'La herramienta correcta, al precio correcto',
            'Construimos el futuro',
            'Tu proyecto, nuestra pasión',
        ];

        $welcomeMessages = [
            '¡Bienvenido a tu sistema de gestión! Administra tu inventario, ventas y clientes en un solo lugar.',
            '¡Hola! Empieza a gestionar tu ferretería de manera profesional y eficiente.',
            '¡Bienvenido! Tu sistema de punto de venta e inventario está listo para usar.',
            '¡Hola! Optimiza tus operaciones y haz crecer tu negocio con nuestra plataforma.',
            '¡Bienvenido de vuelta! Continúa gestionando tu negocio de forma inteligente.',
            '¡Hola! Administra tu ferretería con herramientas modernas y fáciles de usar.',
            '¡Bienvenido! Tu sistema completo para gestión comercial está aquí.',
            '¡Hola! Lleva tu ferretería al siguiente nivel con nuestra tecnología.',
            '¡Bienvenido! La forma más sencilla de gestionar tu inventario y ventas.',
            '¡Hola! Potencia tu negocio con nuestro sistema de gestión integral.',
        ];

        $brandColors = [
            '#2563eb', // Blue
            '#dc2626', // Red
            '#16a34a', // Green
            '#ca8a04', // Yellow/Orange
            '#7c3aed', // Purple
            '#0891b2', // Cyan
            '#ea580c', // Orange
            '#be123c', // Pink/Red
            '#0f766e', // Teal
            '#4338ca', // Indigo
        ];

        $fonts = [
            'Inter',
            'Roboto',
            'Open Sans',
            'Poppins',
            'Montserrat',
            'Nunito',
            'Lato',
            'Source Sans Pro',
            'Work Sans',
            'Manrope',
        ];

        return [
            'logo_path' => 'store-settings/logos/' . $this->faker->uuid() . '.png',
            'background_image_path' => 'store-settings/backgrounds/' . $this->faker->uuid() . '.jpg',
            'brand_color' => $this->faker->randomElement($brandColors),
            'welcome_message' => $this->faker->randomElement($welcomeMessages),
            'store_name' => $this->faker->randomElement($businessNames),
            'store_slogan' => $this->faker->randomElement($slogans),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'show_background_image' => $this->faker->boolean(80), // 80% chance of showing background
            'primary_font' => $this->faker->randomElement($fonts),
            'facebook_url' => $this->faker->optional(0.7)->url(), // 70% chance of having Facebook
            'instagram_url' => $this->faker->optional(0.6)->url(), // 60% chance of having Instagram
            'whatsapp_number' => $this->faker->optional(0.8)->numerify('##########'), // 80% chance of having WhatsApp
            'contact_email' => $this->faker->optional(0.9)->companyEmail(), // 90% chance of having email
        ];
    }

    /**
     * Indicate that the store setting is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the store setting has no background image.
     */
    public function noBackground(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_background_image' => false,
            'background_image_path' => null,
        ]);
    }

    /**
     * Indicate that the store setting has no logo.
     */
    public function noLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_path' => null,
        ]);
    }

    /**
     * Indicate that the store setting has no social media.
     */
    public function noSocialMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'facebook_url' => null,
            'instagram_url' => null,
            'whatsapp_number' => null,
        ]);
    }

    /**
     * Indicate that the store setting has all social media configured.
     */
    public function fullSocialMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'facebook_url' => 'https://facebook.com/' . $this->faker->userName,
            'instagram_url' => 'https://instagram.com/' . $this->faker->userName,
            'whatsapp_number' => $this->faker->numerify('##########'),
            'contact_email' => $this->faker->companyEmail(),
        ]);
    }

    /**
     * Create a minimalist store setting with just the basics.
     */
    public function minimalist(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_path' => null,
            'background_image_path' => null,
            'store_slogan' => null,
            'show_background_image' => false,
            'facebook_url' => null,
            'instagram_url' => null,
            'whatsapp_number' => null,
        ]);
    }

    /**
     * Create a feature-complete store setting.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'show_background_image' => true,
            'logo_path' => 'store-settings/logos/featured-logo.png',
            'background_image_path' => 'store-settings/backgrounds/featured-bg.jpg',
            'brand_color' => '#2563eb',
            'welcome_message' => '¡Bienvenido al mejor sistema de gestión para ferreterías! Administra tu negocio con profesionalismo y facilidad.',
            'store_name' => 'Ferretería Premium',
            'store_slogan' => 'La excelencia en herramientas y construcción',
            'primary_font' => 'Inter',
            'facebook_url' => 'https://facebook.com/ferreteriapremium',
            'instagram_url' => 'https://instagram.com/ferreteriapremium',
            'whatsapp_number' => '1234567890',
            'contact_email' => 'contacto@ferreteriapremium.com',
        ]);
    }

    /**
     * Create a store setting with a specific color theme.
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_color' => $color,
        ]);
    }

    /**
     * Create a store setting with a specific business type (ferretería).
     */
    public function ferreteria(string $name = null): static
    {
        $ferreteriaNames = [
            'Ferretería El Constructor',
            'Herramienta Pro',
            'Ferretería La Central',
            'Ferretería Express',
            'Tu Ferretería de Confianza',
            'Ferretería del Pueblo',
            'Ferretería Profesional',
            'Ferretería Moderna',
        ];

        $ferreteriaSlogans = [
            'Herramientas de calidad para profesionales',
            'Todo en construcción y ferretería',
            'La herramienta correcta para cada trabajo',
            'Expertos en herramientas y materiales',
            'Tu socio en proyectos de construcción',
        ];

        return $this->state(fn (array $attributes) => [
            'store_name' => $name ?? $this->faker->randomElement($ferreteriaNames),
            'store_slogan' => $this->faker->randomElement($ferreteriaSlogans),
            'brand_color' => $this->faker->randomElement(['#dc2626', '#ea580c', '#0891b2']), // Red, Orange, Teal for hardware stores
        ]);
    }
}