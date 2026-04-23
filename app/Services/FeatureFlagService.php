<?php

namespace App\Services;

use App\Models\Module;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    /**
     * Check if tenant has access to a specific feature
     */
    public function hasFeatureAccess(Tenant $tenant, string $feature): bool
    {
        $cacheKey = "tenant.{$tenant->id}.features";

        $features = Cache::remember($cacheKey, 3600, function () use ($tenant) {
            return $tenant->getEnabledFeatureFlags();
        });

        return in_array($feature, $features);
    }

    /**
     * Check if tenant has access to any of the given features
     */
    public function hasAnyFeatureAccess(Tenant $tenant, array $features): bool
    {
        foreach ($features as $feature) {
            if ($this->hasFeatureAccess($tenant, $feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if tenant has access to all of the given features
     */
    public function hasAllFeatureAccess(Tenant $tenant, array $features): bool
    {
        foreach ($features as $feature) {
            if (! $this->hasFeatureAccess($tenant, $feature)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available features for a tenant
     */
    public function getTenantFeatures(Tenant $tenant): array
    {
        $cacheKey = "tenant.{$tenant->id}.features";

        return Cache::remember($cacheKey, 3600, function () use ($tenant) {
            return $tenant->getEnabledFeatureFlags();
        });
    }

    /**
     * Get feature source information
     */
    public function getFeatureSource(Tenant $tenant, string $feature): ?array
    {
        // Check subscription plan first
        $activeSubscription = $tenant->activeSubscription;
        if ($activeSubscription && $activeSubscription->plan->hasFeature($feature)) {
            return [
                'source' => 'subscription_plan',
                'source_name' => $activeSubscription->plan->name,
                'source_id' => $activeSubscription->plan->id,
            ];
        }

        // Check modules
        $module = $tenant->activeModules()
            ->whereJsonContains('feature_flags', $feature)
            ->first();

        if ($module) {
            return [
                'source' => 'module',
                'source_name' => $module->name,
                'source_id' => $module->id,
            ];
        }

        return null;
    }

    /**
     * Get features by category for a tenant
     */
    public function getFeaturesByCategory(Tenant $tenant): array
    {
        $features = $this->getTenantFeatures($tenant);
        $categorized = [];

        foreach ($features as $feature) {
            $category = $this->getFeatureCategory($feature);
            $categorized[$category][] = $feature;
        }

        return $categorized;
    }

    /**
     * Get category for a feature
     */
    public function getFeatureCategory(string $feature): string
    {
        $categories = [
            'inventory' => [
                'has_inventory_management',
                'has_stock_tracking',
                'has_barcode_scanning',
            ],
            'pos' => [
                'has_pos_system',
                'has_receipt_printing',
                'has_cash_drawer',
            ],
            'analytics' => [
                'has_advanced_analytics',
                'has_custom_reports',
                'has_data_export',
            ],
            'integrations' => [
                'has_api_access',
                'has_webhook_support',
                'has_third_party_integrations',
            ],
            'security' => [
                'has_two_factor_auth',
                'has_advanced_permissions',
                'has_audit_logging',
            ],
            'customization' => [
                'has_custom_branding',
                'has_custom_fields',
                'has_workflow_automation',
            ],
        ];

        foreach ($categories as $category => $featureList) {
            if (in_array($feature, $featureList)) {
                return $category;
            }
        }

        return 'general';
    }

    /**
     * Get all possible features from subscription plans and modules
     */
    public function getAllPossibleFeatures(): array
    {
        $features = [];

        // Get features from subscription plans
        $planFeatures = SubscriptionPlan::active()
            ->pluck('features')
            ->filter()
            ->flatten()
            ->unique()
            ->toArray();

        // Get features from modules
        $moduleFeatures = Module::active()
            ->pluck('feature_flags')
            ->filter()
            ->flatten()
            ->unique()
            ->toArray();

        return array_unique(array_merge($planFeatures, $moduleFeatures));
    }

    /**
     * Get feature display name
     */
    public function getFeatureDisplayName(string $feature): string
    {
        $featureNames = [
            'has_inventory_management' => 'Gestión de Inventario',
            'has_stock_tracking' => 'Seguimiento de Stock',
            'has_barcode_scanning' => 'Escaneo de Códigos de Barras',
            'has_pos_system' => 'Sistema de Punto de Venta',
            'has_receipt_printing' => 'Impresión de Tickets',
            'has_cash_drawer' => 'Caja Registradora',
            'has_advanced_analytics' => 'Análisis Avanzado',
            'has_custom_reports' => 'Reportes Personalizados',
            'has_data_export' => 'Exportación de Datos',
            'has_api_access' => 'Acceso API',
            'has_webhook_support' => 'Soporte Webhooks',
            'has_third_party_integrations' => 'Integraciones de Terceros',
            'has_two_factor_auth' => 'Autenticación de Dos Factores',
            'has_advanced_permissions' => 'Permisos Avanzados',
            'has_audit_logging' => 'Registro de Auditoría',
            'has_custom_branding' => 'Branding Personalizado',
            'has_custom_fields' => 'Campos Personalizados',
            'has_workflow_automation' => 'Automatización de Flujos',
            'has_priority_support' => 'Soporte Prioritario',
            'has_multi_currency' => 'Multi-Moneda',
            'has_advanced_exports' => 'Exportaciones Avanzadas',
            'has_client_management' => 'Gestión de Clientes',
            'has_multi_language' => 'Multi-Idioma',
            'has_backup_automation' => 'Automatización de Backups',
            'has_mobile_app' => 'Aplicación Móvil',
        ];

        return $featureNames[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
    }

    /**
     * Get feature description
     */
    public function getFeatureDescription(string $feature): string
    {
        $descriptions = [
            'has_inventory_management' => 'Control completo de inventario con categorías, movimientos y alertas',
            'has_stock_tracking' => 'Seguimiento en tiempo real del stock y notificaciones de bajo inventario',
            'has_barcode_scanning' => 'Generación y escaneo de códigos de barras para productos',
            'has_pos_system' => 'Sistema completo de punto de venta con gestión de pagos',
            'has_receipt_printing' => 'Impresión de tickets y facturas térmicas y A4',
            'has_cash_drawer' => 'Integración con cajas registradoras y gestión de efectivo',
            'has_advanced_analytics' => 'Análisis avanzado de ventas, productos y tendencias',
            'has_custom_reports' => 'Generador de reportes personalizados con filtros avanzados',
            'has_data_export' => 'Exportación de datos a Excel, CSV y PDF',
            'has_api_access' => 'Acceso completo a la API REST para integraciones',
            'has_webhook_support' => 'Configuración de webhooks para notificaciones en tiempo real',
            'has_third_party_integrations' => 'Integración con servicios externos como contabilidad y envíos',
            'has_two_factor_auth' => 'Autenticación de dos factores para mayor seguridad',
            'has_advanced_permissions' => 'Sistema granular de permisos por rol y usuario',
            'has_audit_logging' => 'Registro completo de auditoría de todas las acciones',
            'has_custom_branding' => 'Personalización de logos, colores y apariencia',
            'has_custom_fields' => 'Campos personalizados en productos, clientes y ventas',
            'has_workflow_automation' => 'Automatización de procesos y flujos de trabajo',
            'has_priority_support' => 'Soporte técnico prioritario con respuesta garantizada',
            'has_multi_currency' => 'Soporte para múltiples monedas y conversión automática',
            'has_advanced_exports' => 'Exportaciones avanzadas con formatos personalizados',
            'has_client_management' => 'Gestión avanzada de clientes con historial y seguimiento',
            'has_multi_language' => 'Soporte para múltiples idiomas en la interfaz',
            'has_backup_automation' => 'Automatización de backups con programación personalizable',
            'has_mobile_app' => 'Acceso móvil completo para gestión remota',
        ];

        return $descriptions[$feature] ?? 'Funcionalidad avanzada del sistema';
    }

    /**
     * Clear feature cache for tenant
     */
    public function clearFeatureCache(Tenant $tenant): void
    {
        $cacheKeys = [
            "tenant.{$tenant->id}.features",
            "tenant.{$tenant->id}.modules",
            "tenant.{$tenant->id}.permissions",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get modules that provide a specific feature
     */
    public function getModulesWithFeature(string $feature): \Illuminate\Database\Eloquent\Collection
    {
        return Module::active()
            ->visible()
            ->whereJsonContains('feature_flags', $feature)
            ->ordered()
            ->get();
    }

    /**
     * Get subscription plans that provide a specific feature
     */
    public function getPlansWithFeature(string $feature): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::active()
            ->visible()
            ->where(function ($query) use ($feature) {
                $query->whereJsonContains('features', $feature)
                    ->orWhere('features->'.$feature, true);
            })
            ->ordered()
            ->get();
    }

    /**
     * Check if feature is available in any plan
     */
    public function isFeatureAvailableInPlans(string $feature): bool
    {
        return SubscriptionPlan::active()
            ->where(function ($query) use ($feature) {
                $query->whereJsonContains('features', $feature)
                    ->orWhere('features->'.$feature, true);
            })
            ->exists();
    }

    /**
     * Check if feature is available in any module
     */
    public function isFeatureAvailableInModules(string $feature): bool
    {
        return Module::active()
            ->visible()
            ->whereJsonContains('feature_flags', $feature)
            ->exists();
    }

    /**
     * Get feature suggestions for tenant based on current usage
     */
    public function getFeatureSuggestions(Tenant $tenant): array
    {
        $currentFeatures = $this->getTenantFeatures($tenant);
        $allFeatures = $this->getAllPossibleFeatures();
        $missingFeatures = array_diff($allFeatures, $currentFeatures);

        $suggestions = [];

        foreach ($missingFeatures as $feature) {
            $modules = $this->getModulesWithFeature($feature);
            $plans = $this->getPlansWithFeature($feature);

            $suggestions[] = [
                'feature' => $feature,
                'name' => $this->getFeatureDisplayName($feature),
                'description' => $this->getFeatureDescription($feature),
                'modules' => $modules->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'price' => $m->getFormattedPrice(),
                    'category' => $m->getDisplayCategory(),
                ])->toArray(),
                'plans' => $plans->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->getFormattedPrice(),
                ])->toArray(),
            ];
        }

        return $suggestions;
    }
}
