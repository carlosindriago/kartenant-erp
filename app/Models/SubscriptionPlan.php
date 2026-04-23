<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';

    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'has_trial',
        'trial_days',
        'max_users',
        'max_products',
        'max_sales_per_month',
        'max_storage_mb',
        'enabled_modules',
        'features',
        'limits',
        'overage_strategy',
        'overage_percentage',
        'overage_tolerance',
        'is_active',
        'is_visible',
        'is_featured',
        'sort_order',
        'stripe_product_id',
        'stripe_price_monthly_id',
        'stripe_price_yearly_id',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'max_users' => 'integer',
        'max_products' => 'integer',
        'max_sales_per_month' => 'integer',
        'max_storage_mb' => 'integer',
        'enabled_modules' => 'array',
        'features' => 'array',
        'limits' => 'array',
        'overage_strategy' => 'string',
        'overage_percentage' => 'integer',
        'overage_tolerance' => 'integer',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'subscription_plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public function getPrice(string $billingCycle = 'monthly'): float
    {
        return $billingCycle === 'yearly'
            ? (float) $this->price_yearly
            : (float) $this->price_monthly;
    }

    public function getFormattedPrice(string $billingCycle = 'monthly'): string
    {
        $price = $this->getPrice($billingCycle);

        return $this->currency.' '.number_format($price, 2);
    }

    public function getYearlySavings(): float
    {
        $monthlyTotal = (float) $this->price_monthly * 12;
        $yearlyTotal = (float) $this->price_yearly;

        return $monthlyTotal - $yearlyTotal;
    }

    public function getYearlySavingsPercentage(): int
    {
        $monthlyTotal = (float) $this->price_monthly * 12;
        if ($monthlyTotal == 0) {
            return 0;
        }
        $savings = $this->getYearlySavings();

        return (int) round(($savings / $monthlyTotal) * 100);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->enabled_modules ?? []);
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        // Handle boolean flag structure (new format): {"has_api_access": true}
        if (is_array($features) && ! empty($features) && ! is_numeric(array_key_first($features))) {
            return isset($features[$feature]) && $features[$feature] === true;
        }

        // Handle legacy string array format: ["API Access", "Advanced Analytics"]
        return in_array($feature, $features);
    }

    /**
     * Get features formatted for display
     * Transforms boolean flags to human-readable labels for both new and legacy formats
     */
    public function getFormattedFeatures(): array
    {
        $features = $this->features ?? [];
        $availableFeatureFlags = self::getAvailableFeatureFlags();

        // Handle boolean flag structure (new format): {"has_api_access": true}
        if (is_array($features) && ! empty($features) && ! is_numeric(array_key_first($features))) {
            $formattedFeatures = [];

            foreach ($features as $featureKey => $isEnabled) {
                if ($isEnabled === true && isset($availableFeatureFlags[$featureKey])) {
                    $formattedFeatures[] = $availableFeatureFlags[$featureKey];
                }
            }

            return $formattedFeatures;
        }

        // Handle legacy string array format: ["API Access", "Advanced Analytics"]
        return is_array($features) ? $features : [];
    }

    public function hasLimit(string $limitType): bool
    {
        return ! is_null($this->{$limitType});
    }

    public function isUnlimited(string $limitType): bool
    {
        return is_null($this->{$limitType});
    }

    public function getLimit(string $limitType): ?int
    {
        return $this->{$limitType};
    }

    // Soft Limits Configuration Methods

    /**
     * Get configurable limit for a specific metric (from new limits configuration)
     */
    public function getConfigurableLimit(string $metric): ?int
    {
        $limits = $this->limits ?? [];

        // Handle backward compatibility for storage key
        if ($metric === 'storage_mb') {
            // First try storage_mb, then fall back to storage for legacy data
            if (isset($limits['storage_mb'])) {
                return $limits['storage_mb'];
            }
            if (isset($limits['storage'])) {
                return is_numeric($limits['storage']) ? (int) $limits['storage'] : null;
            }

            return null;
        }

        // Handle standard metrics
        if (isset($limits[$metric])) {
            $value = $limits[$metric];

            // Convert string numbers to integers, return null for non-numeric
            return is_numeric($value) ? (int) $value : null;
        }

        return null;
    }

    /**
     * Get overage limit for a specific metric based on strategy
     */
    public function getOverageLimit(string $metric): ?int
    {
        $baseLimit = $this->getConfigurableLimit($metric);

        if ($baseLimit === null) {
            return null; // Unlimited
        }

        if (! $this->allowsOverage()) {
            return $baseLimit; // Strict limit
        }

        // Calculate soft limit with buffer
        $buffer = (int) ($baseLimit * ($this->overage_percentage / 100));

        return $baseLimit + $buffer;
    }

    /**
     * Check if plan allows overage for limits
     */
    public function allowsOverage(): bool
    {
        return $this->overage_strategy === 'soft';
    }

    /**
     * Check if plan uses strict limits (no overage allowed)
     */
    public function isStrict(): bool
    {
        return $this->overage_strategy === 'strict';
    }

    /**
     * Calculate usage percentage for a metric
     */
    public function calculateUsagePercentage(string $metric, int $current): float
    {
        $limit = $this->getOverageLimit($metric);

        if ($limit === null || $limit <= 0) {
            return 0.0; // Unlimited
        }

        return min(($current / $limit) * 100, 100.0);
    }

    /**
     * Check if current usage exceeds base limit (before overage buffer)
     */
    public function exceedsBaseLimit(string $metric, int $current): bool
    {
        $baseLimit = $this->getConfigurableLimit($metric);

        if ($baseLimit === null) {
            return false; // Unlimited
        }

        return $current > $baseLimit;
    }

    /**
     * Check if current usage exceeds overage limit (hard limit)
     */
    public function exceedsOverageLimit(string $metric, int $current): bool
    {
        $overageLimit = $this->getOverageLimit($metric);

        if ($overageLimit === null) {
            return false; // Unlimited
        }

        return $current > $overageLimit;
    }

    /**
     * Get remaining capacity before hitting base limit
     */
    public function getRemainingCapacity(string $metric, int $current): int
    {
        $baseLimit = $this->getConfigurableLimit($metric);

        if ($baseLimit === null) {
            return PHP_INT_MAX; // Unlimited
        }

        return max(0, $baseLimit - $current);
    }

    /**
     * Get remaining capacity before hitting overage limit
     */
    public function getRemainingOverageCapacity(string $metric, int $current): int
    {
        $overageLimit = $this->getOverageLimit($metric);

        if ($overageLimit === null) {
            return PHP_INT_MAX; // Unlimited
        }

        return max(0, $overageLimit - $current);
    }

    /**
     * Get all available limit metrics from configuration
     */
    public function getAvailableMetrics(): array
    {
        return array_keys($this->limits ?? []);
    }

    /**
     * Check if plan has configurable limits set
     */
    public function hasConfigurableLimits(): bool
    {
        return ! empty($this->limits) && is_array($this->limits);
    }

    /**
     * Get limit status with human-readable description
     */
    public function getLimitStatus(string $metric, int $current): array
    {
        $baseLimit = $this->getConfigurableLimit($metric);
        $overageLimit = $this->getOverageLimit($metric);

        if ($baseLimit === null) {
            return [
                'status' => 'unlimited',
                'message' => 'Unlimited',
                'percentage' => 0,
                'current' => $current,
                'limit' => null,
            ];
        }

        $percentage = $this->calculateUsagePercentage($metric, $current);
        $exceedsBase = $this->exceedsBaseLimit($metric, $current);
        $exceedsOverage = $this->exceedsOverageLimit($metric, $current);

        if ($exceedsOverage) {
            $status = 'critical';
            $message = "Exceeded limit ({$current}/{$overageLimit})";
        } elseif ($exceedsBase) {
            $status = 'warning';
            $message = "In overage zone ({$current}/{$overageLimit})";
        } else {
            $status = 'normal';
            $message = "{$current}/{$baseLimit}";
        }

        return [
            'status' => $status,
            'message' => $message,
            'percentage' => round($percentage, 2),
            'current' => $current,
            'limit' => $overageLimit,
            'base_limit' => $baseLimit,
            'allows_overage' => $this->allowsOverage(),
        ];
    }

    // Enhanced Form Integration Methods

    /**
     * Get limits data formatted for Filament form
     */
    public function getLimitsForForm(): array
    {
        return [
            'monthly_sales' => $this->limits['monthly_sales'] ?? null,
            'products' => $this->limits['products'] ?? null,
            'users' => $this->limits['users'] ?? null,
            'storage_mb' => $this->limits['storage_mb'] ?? null,
        ];
    }

    /**
     * Set limits data from Filament form state
     */
    public function setLimitsFromForm(array $limitsData): void
    {
        // Filter out null/empty values to keep JSON clean
        $filteredLimits = array_filter($limitsData, function ($value) {
            return $value !== null && $value !== '' && $value !== '0';
        });

        // Convert string numbers to integers for consistency
        $this->limits = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : $value;
        }, $filteredLimits);
    }

    /**
     * Get features data formatted for Filament form
     */
    public function getFeaturesForForm(): array
    {
        return $this->features ?? [];
    }

    /**
     * Set features data from Filament form state
     */
    public function setFeaturesFromForm(array $featuresData): void
    {
        $this->features = $featuresData;
    }

    /**
     * Get unified configuration for form rendering
     * Returns all configurable settings in a single array
     */
    public function getUnifiedConfiguration(): array
    {
        return [
            'limits' => $this->getLimitsForForm(),
            'features' => $this->getFeaturesForForm(),
            'overage_strategy' => $this->overage_strategy,
            'overage_percentage' => $this->overage_percentage,
            'overage_tolerance' => $this->overage_tolerance,
        ];
    }

    /**
     * Set unified configuration from form data
     * Updates all configurable settings from form submission
     */
    public function setUnifiedConfiguration(array $config): void
    {
        if (isset($config['limits'])) {
            $this->setLimitsFromForm($config['limits']);
        }

        if (isset($config['features'])) {
            $this->setFeaturesFromForm($config['features']);
        }

        if (isset($config['overage_strategy'])) {
            $this->overage_strategy = $config['overage_strategy'];
        }

        if (isset($config['overage_percentage'])) {
            $this->overage_percentage = (int) $config['overage_percentage'];
        }

        if (isset($config['overage_tolerance'])) {
            $this->overage_tolerance = (int) $config['overage_tolerance'];
        }
    }

    // Enhanced Helper Methods

    /**
     * Get all available limit metrics with labels
     */
    public static function getAvailableLimitMetrics(): array
    {
        return [
            'monthly_sales' => 'Ventas Mensuales',
            'products' => 'Productos',
            'users' => 'Usuarios',
            'storage_mb' => 'Almacenamiento (MB)',
        ];
    }

    /**
     * Get available feature flags with labels
     */
    public static function getAvailableFeatureFlags(): array
    {
        return [
            'has_api_access' => 'Acceso API',
            'has_advanced_analytics' => 'Análisis Avanzado',
            'has_priority_support' => 'Soporte Prioritario',
            'has_custom_branding' => 'Branding Personalizado',
            'has_advanced_exports' => 'Exportaciones Avanzadas',
            'has_multi_currency' => 'Multi-Moneda',
            'has_inventory_management' => 'Gestión de Inventario Avanzada',
            'has_pos_system' => 'Sistema de Punto de Venta',
            'has_client_management' => 'Gestión de Clientes',
        ];
    }

    /**
     * Validate JSON structure for limits
     */
    public function validateLimitsStructure(array $limits): array
    {
        $errors = [];
        $availableMetrics = array_keys(self::getAvailableLimitMetrics());

        foreach ($limits as $metric => $value) {
            if (! in_array($metric, $availableMetrics)) {
                $errors[$metric] = "Métrica de límite no válida: {$metric}";

                continue;
            }

            if ($value !== null && $value !== '' && ! is_numeric($value)) {
                $errors[$metric] = 'El valor debe ser numérico o nulo para límites ilimitados';
            }
        }

        return $errors;
    }

    /**
     * Validate JSON structure for features
     */
    public function validateFeaturesStructure(array $features): array
    {
        $errors = [];
        $availableFeatures = array_keys(self::getAvailableFeatureFlags());

        foreach ($features as $feature => $value) {
            if (! in_array($feature, $availableFeatures)) {
                $errors[$feature] = "Característica no válida: {$feature}";
            }

            if (! is_bool($value)) {
                $errors[$feature] = 'El valor debe ser booleano (true/false)';
            }
        }

        return $errors;
    }

    // Backward Compatibility Methods

    /**
     * Migrate legacy limit columns to new JSON structure
     * This method helps transition from individual columns to unified limits JSON
     */
    public function migrateLegacyLimits(): bool
    {
        if ($this->limits !== null && ! empty($this->limits)) {
            return true; // Already has modern limits
        }

        $legacyLimits = [];
        $hasLegacyData = false;

        // Check for legacy limit columns
        if ($this->max_users !== null) {
            $legacyLimits['users'] = $this->max_users;
            $hasLegacyData = true;
        }

        if ($this->max_products !== null) {
            $legacyLimits['products'] = $this->max_products;
            $hasLegacyData = true;
        }

        if ($this->max_sales_per_month !== null) {
            $legacyLimits['monthly_sales'] = $this->max_sales_per_month;
            $hasLegacyData = true;
        }

        if ($this->max_storage_mb !== null) {
            $legacyLimits['storage_mb'] = $this->max_storage_mb;
            $hasLegacyData = true;
        }

        if ($hasLegacyData) {
            $this->limits = $legacyLimits;
            $this->saveQuietly();

            return true;
        }

        return false;
    }

    /**
     * Get effective limit considering tolerance and overage strategy
     */
    public function getEffectiveLimit(string $metric): ?int
    {
        $baseLimit = $this->getConfigurableLimit($metric);

        if ($baseLimit === null || $baseLimit <= 0) {
            return null; // Unlimited
        }

        if (! $this->allowsOverage()) {
            return $baseLimit; // Strict limit
        }

        // Apply overage percentage if tolerance is not set
        if ($this->overage_tolerance > 0) {
            $buffer = (int) ($baseLimit * ($this->overage_tolerance / 100));

            return $baseLimit + $buffer;
        }

        // Fall back to overage_percentage for backward compatibility
        if ($this->overage_percentage > 0) {
            $buffer = (int) ($baseLimit * ($this->overage_percentage / 100));

            return $baseLimit + $buffer;
        }

        return $baseLimit;
    }

    // Static helpers
    public static function getDefault(): ?self
    {
        return self::active()->ordered()->first();
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }
}
