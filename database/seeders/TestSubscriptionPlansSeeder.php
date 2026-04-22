<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class TestSubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan Básico',
                'slug' => 'plan-basico',
                'description' => 'Perfecto para pequeñas tiendas que comienzan. Incluye funcionalidades esenciales de inventario y ventas básicas.',
                'price_monthly' => 9.99,
                'price_yearly' => 99.90,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 14,
                'limits' => [
                    'users' => 1,
                    'products' => 100,
                    'monthly_sales' => 50,
                    'storage_mb' => 500,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => false,
                ],
                'overage_strategy' => 'strict',
                'overage_percentage' => 0,
                'overage_tolerance' => 0,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],

            [
                'name' => 'Plan Profesional',
                'slug' => 'plan-profesional',
                'description' => 'Ideal para negocios en crecimiento con gestión avanzada de inventario y análisis detallados.',
                'price_monthly' => 29.99,
                'price_yearly' => 299.90,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 14,
                'limits' => [
                    'users' => 5,
                    'products' => 1000,
                    'monthly_sales' => 500,
                    'storage_mb' => 2000,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => true,
                    'has_advanced_analytics' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 20,
                'overage_tolerance' => 20,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],

            [
                'name' => 'Plan Empresarial',
                'slug' => 'plan-empresarial',
                'description' => 'Solución completa para grandes empresas con límites generosos y soporte prioritario las 24/7.',
                'price_monthly' => 99.99,
                'price_yearly' => 999.90,
                'currency' => 'USD',
                'has_trial' => false,
                'trial_days' => 0,
                'limits' => [
                    'users' => 20,
                    'products' => null, // Unlimited
                    'monthly_sales' => null, // Unlimited
                    'storage_mb' => 10000,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => true,
                    'has_advanced_analytics' => true,
                    'has_api_access' => true,
                    'has_priority_support' => true,
                    'has_custom_branding' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 50,
                'overage_tolerance' => 50,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],

            [
                'name' => 'Plan Starter',
                'slug' => 'plan-starter',
                'description' => 'Plan de entrada para microempresas. Funcionalidades básicas con límites controlados.',
                'price_monthly' => 4.99,
                'price_yearly' => 49.90,
                'currency' => 'USD',
                'has_trial' => false,
                'trial_days' => 0,
                'limits' => [
                    'users' => 1,
                    'products' => 50,
                    'monthly_sales' => 25,
                    'storage_mb' => 250,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => false,
                    'has_inventory_management' => false,
                ],
                'overage_strategy' => 'strict',
                'overage_percentage' => 0,
                'overage_tolerance' => 0,
                'is_active' => false, // INACTIVE FOR TESTING
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],

            [
                'name' => 'Plan Premium',
                'slug' => 'plan-premium',
                'description' => 'Características premium con límites altos y funcionalidades avanzadas para negocios especializados.',
                'price_monthly' => 49.99,
                'price_yearly' => 499.90,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 21,
                'limits' => [
                    'users' => 10,
                    'products' => 5000,
                    'monthly_sales' => 2000,
                    'storage_mb' => 5000,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => true,
                    'has_advanced_analytics' => true,
                    'has_api_access' => true,
                    'has_advanced_exports' => true,
                    'has_multi_currency' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 25,
                'overage_tolerance' => 25,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true,
                'sort_order' => 5,
            ],

            [
                'name' => 'Plan Personal',
                'slug' => 'plan-personal',
                'description' => 'Para emprendedores individuales que necesitan funcionalidades básicas sin excesos.',
                'price_monthly' => 14.99,
                'price_yearly' => 149.90,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 7,
                'limits' => [
                    'users' => 2,
                    'products' => 500,
                    'monthly_sales' => 200,
                    'storage_mb' => 1000,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => false,
                    'has_advanced_exports' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 15,
                'overage_tolerance' => 15,
                'is_active' => true,
                'is_visible' => false, // NOT VISIBLE FOR TESTING
                'is_featured' => false,
                'sort_order' => 6,
            ],

            [
                'name' => 'Plan Agency',
                'slug' => 'plan-agency',
                'description' => 'Solución para agencias que gestionan múltiples tiendas con capacidades avanzadas de white-label.',
                'price_monthly' => 199.99,
                'price_yearly' => 1999.90,
                'currency' => 'USD',
                'has_trial' => false,
                'trial_days' => 0,
                'limits' => [
                    'users' => 50,
                    'products' => null, // Unlimited
                    'monthly_sales' => null, // Unlimited
                    'storage_mb' => null, // Unlimited
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => true,
                    'has_advanced_analytics' => true,
                    'has_api_access' => true,
                    'has_priority_support' => true,
                    'has_custom_branding' => true,
                    'has_advanced_exports' => true,
                    'has_multi_currency' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 100,
                'overage_tolerance' => 100,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false, // NOT FEATURED FOR TESTING
                'sort_order' => 7,
            ],

            [
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'description' => 'Prueba gratuita de 7 días con funcionalidades básicas para evaluar el sistema.',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 7,
                'limits' => [
                    'users' => 1,
                    'products' => 25,
                    'monthly_sales' => 10,
                    'storage_mb' => 100,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => false,
                ],
                'overage_strategy' => 'strict',
                'overage_percentage' => 0,
                'overage_tolerance' => 0,
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true,
                'sort_order' => 0, // First in list
            ],

            [
                'name' => 'Plan Enterprise Plus',
                'slug' => 'plan-enterprise-plus',
                'description' => 'Máxima capacidad para corporaciones con soporte dedicado y personalizaciones.',
                'price_monthly' => 299.99,
                'price_yearly' => 2999.90,
                'currency' => 'USD',
                'has_trial' => false,
                'trial_days' => 0,
                'limits' => [
                    'users' => 100,
                    'products' => null, // Unlimited
                    'monthly_sales' => null, // Unlimited
                    'storage_mb' => null, // Unlimited
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => true,
                    'has_advanced_analytics' => true,
                    'has_api_access' => true,
                    'has_priority_support' => true,
                    'has_custom_branding' => true,
                    'has_advanced_exports' => true,
                    'has_multi_currency' => true,
                ],
                'overage_strategy' => 'soft',
                'overage_percentage' => 200,
                'overage_tolerance' => 200,
                'is_active' => false, // INACTIVE FOR TESTING
                'is_visible' => false, // NOT VISIBLE FOR TESTING
                'is_featured' => false,
                'sort_order' => 8,
            ],

            [
                'name' => 'Plan Estudiantil',
                'slug' => 'plan-estudiantil',
                'description' => 'Descuento especial para estudiantes con funcionalidades básicas para proyectos académicos.',
                'price_monthly' => 7.99,
                'price_yearly' => 79.90,
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 30,
                'limits' => [
                    'users' => 1,
                    'products' => 75,
                    'monthly_sales' => 30,
                    'storage_mb' => 750,
                ],
                'features' => [
                    'has_pos_system' => true,
                    'has_client_management' => true,
                    'has_inventory_management' => false,
                    'has_advanced_analytics' => false,
                ],
                'overage_strategy' => 'strict',
                'overage_percentage' => 0,
                'overage_tolerance' => 0,
                'is_active' => false, // INACTIVE FOR TESTING
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 9,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::create($planData);
            $this->command->info("Created plan: {$planData['name']}");
        }
    }
}