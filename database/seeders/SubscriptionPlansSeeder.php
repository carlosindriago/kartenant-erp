<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan Básico',
                'slug' => 'basico',
                'description' => 'Ideal para pequeños negocios que están comenzando. Incluye las funcionalidades esenciales para gestionar tu inventario y ventas.',
                'price_monthly' => 29.99,
                'price_yearly' => 299.90, // ~16% descuento
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 14,
                'max_users' => 3,
                'max_products' => 500,
                'max_sales_per_month' => 1000,
                'max_storage_mb' => 1024, // 1 GB
                'enabled_modules' => [
                    'inventory',
                    'pos',
                    'clients',
                ],
                'features' => [
                    'Gestión de inventario básica',
                    'Punto de venta (POS)',
                    'Gestión de clientes',
                    'Reportes básicos',
                    'Soporte por email',
                    'Hasta 3 usuarios',
                    'Hasta 500 productos',
                    '1 GB de almacenamiento',
                ],
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Plan Profesional',
                'slug' => 'profesional',
                'description' => 'Para negocios en crecimiento que necesitan más capacidad y funcionalidades avanzadas. Incluye todos los módulos principales.',
                'price_monthly' => 79.99,
                'price_yearly' => 799.90, // ~16% descuento
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 14,
                'max_users' => 10,
                'max_products' => 2000,
                'max_sales_per_month' => 5000,
                'max_storage_mb' => 5120, // 5 GB
                'enabled_modules' => [
                    'inventory',
                    'pos',
                    'clients',
                    'suppliers',
                    'purchases',
                    'reports',
                ],
                'features' => [
                    'Todo del Plan Básico',
                    'Gestión de proveedores',
                    'Gestión de compras',
                    'Reportes avanzados',
                    'Multi-almacén',
                    'Soporte prioritario',
                    'Hasta 10 usuarios',
                    'Hasta 2,000 productos',
                    '5 GB de almacenamiento',
                    'Exportación a Excel',
                    'API access',
                ],
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true, // Plan destacado
                'sort_order' => 2,
            ],
            [
                'name' => 'Plan Empresarial',
                'slug' => 'empresarial',
                'description' => 'La solución completa para empresas grandes con necesidades avanzadas. Sin límites en funcionalidades.',
                'price_monthly' => 199.99,
                'price_yearly' => 1999.90, // ~16% descuento
                'currency' => 'USD',
                'has_trial' => true,
                'trial_days' => 30, // Trial más largo para empresas
                'max_users' => null, // Unlimited
                'max_products' => null, // Unlimited
                'max_sales_per_month' => null, // Unlimited
                'max_storage_mb' => 51200, // 50 GB
                'enabled_modules' => [
                    'inventory',
                    'pos',
                    'clients',
                    'suppliers',
                    'purchases',
                    'reports',
                    'accounting',
                    'manufacturing',
                    'ecommerce',
                ],
                'features' => [
                    'Todo del Plan Profesional',
                    'Usuarios ilimitados',
                    'Productos ilimitados',
                    'Ventas ilimitadas',
                    '50 GB de almacenamiento',
                    'Módulo de contabilidad',
                    'Módulo de manufactura',
                    'Integración con eCommerce',
                    'Multi-tienda',
                    'Soporte 24/7',
                    'Gerente de cuenta dedicado',
                    'Capacitación personalizada',
                    'Backups diarios automáticos',
                    'API ilimitado',
                    'Webhooks personalizados',
                    'White label (próximamente)',
                ],
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Plan Gratuito',
                'slug' => 'gratuito',
                'description' => 'Plan de prueba gratuito con funcionalidades limitadas. Ideal para probar el sistema antes de comprometerte.',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'currency' => 'USD',
                'has_trial' => false, // No trial, es gratis
                'trial_days' => 0,
                'max_users' => 1,
                'max_products' => 50,
                'max_sales_per_month' => 100,
                'max_storage_mb' => 256, // 256 MB
                'enabled_modules' => [
                    'inventory',
                    'pos',
                ],
                'features' => [
                    'Gestión de inventario limitada',
                    'Punto de venta básico',
                    'Reportes básicos',
                    'Soporte por comunidad',
                    '1 usuario',
                    'Hasta 50 productos',
                    'Hasta 100 ventas/mes',
                    '256 MB de almacenamiento',
                ],
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false,
                'sort_order' => 0, // Aparece primero
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('✅ Subscription plans seeded successfully!');
        $this->command->info('   - Plan Gratuito: $0/mes');
        $this->command->info('   - Plan Básico: $29.99/mes');
        $this->command->info('   - Plan Profesional: $79.99/mes (Featured)');
        $this->command->info('   - Plan Empresarial: $199.99/mes');
    }
}
