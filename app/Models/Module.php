<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Module extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';
    protected $table = 'modules';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price_monthly',
        'base_price_yearly',
        'currency',
        'setup_fee',
        'is_custom',
        'is_active',
        'is_visible',
        'is_featured',
        'sort_order',
        'category',
        'icon',
        'version',
        'provider',
        'limits',
        'configuration',
        'dependencies',
        'conflicts',
        'permissions',
        'billing_cycle',
        'auto_renew',
        'trial_days',
        'billing_tiers',
        'feature_flags',
        'routes',
        'resources',
        'menu_items',
        'stripe_product_id',
        'stripe_price_monthly_id',
        'stripe_price_yearly_id',
        'api_endpoints',
        'installations_count',
        'average_rating',
        'rating_count',
    ];

    protected $casts = [
        'base_price_monthly' => 'decimal:2',
        'base_price_yearly' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'auto_renew' => 'boolean',
        'trial_days' => 'integer',
        'installations_count' => 'integer',
        'average_rating' => 'decimal:2',
        'rating_count' => 'integer',
        'limits' => 'array',
        'configuration' => 'array',
        'dependencies' => 'array',
        'conflicts' => 'array',
        'permissions' => 'array',
        'billing_tiers' => 'array',
        'feature_flags' => 'array',
        'routes' => 'array',
        'resources' => 'array',
        'menu_items' => 'array',
        'api_endpoints' => 'array',
    ];

    // Relationships
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot([
                'price_override',
                'currency_override',
                'starts_at',
                'expires_at',
                'cancelled_at',
                'is_active',
                'auto_renew',
                'billing_cycle',
                'status',
                'configuration',
                'limits_override',
                'metadata',
                'usage_stats',
                'last_used_at',
                'tenant_subscription_id',
                'invoice_line_item_id',
                'added_by',
                'notes',
            ])
            ->withTimestamps()
            ->withTrashed();
    }

    public function activeTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('is_active', true);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(ModuleUsageLog::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_custom', true);
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where('is_custom', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // Pricing Methods
    public function getPrice(string $billingCycle = 'monthly'): float
    {
        return match($billingCycle) {
            'yearly' => (float) $this->base_price_yearly,
            'monthly' => (float) $this->base_price_monthly,
            default => (float) $this->base_price_monthly,
        };
    }

    public function getFormattedPrice(string $billingCycle = 'monthly'): string
    {
        $price = $this->getPrice($billingCycle);
        return $this->currency . ' ' . number_format($price, 2);
    }

    public function hasSetupFee(): bool
    {
        return $this->setup_fee && $this->setup_fee > 0;
    }

    public function getFormattedSetupFee(): string
    {
        return $this->hasSetupFee()
            ? $this->currency . ' ' . number_format((float) $this->setup_fee, 2)
            : 'Gratis';
    }

    public function getYearlySavings(): float
    {
        $monthlyTotal = (float) $this->base_price_monthly * 12;
        $yearlyTotal = (float) $this->base_price_yearly;
        return $monthlyTotal - $yearlyTotal;
    }

    public function getYearlySavingsPercentage(): int
    {
        $monthlyTotal = (float) $this->base_price_monthly * 12;
        if ($monthlyTotal == 0) {
            return 0;
        }
        $savings = $this->getYearlySavings();
        return (int) round(($savings / $monthlyTotal) * 100);
    }

    // Feature Management
    public function hasFeatureFlag(string $flag): bool
    {
        return in_array($flag, $this->feature_flags ?? []);
    }

    public function getFeatureFlags(): array
    {
        return $this->feature_flags ?? [];
    }

    public function requiresModule(string $moduleSlug): bool
    {
        return in_array($moduleSlug, $this->dependencies ?? []);
    }

    public function conflictsWithModule(string $moduleSlug): bool
    {
        return in_array($moduleSlug, $this->conflicts ?? []);
    }

    // Configuration Methods
    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

    public function getConfigurationValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }

    public function getLimits(): array
    {
        return $this->limits ?? [];
    }

    public function getLimit(string $key, mixed $default = null): mixed
    {
        return data_get($this->limits, $key, $default);
    }

    public function hasLimit(string $key): bool
    {
        return array_key_exists($key, $this->limits ?? []);
    }

    // Installation and Usage
    public function incrementInstallations(): void
    {
        $this->increment('installations_count');
    }

    public function decrementInstallations(): void
    {
        if ($this->installations_count > 0) {
            $this->decrement('installations_count');
        }
    }

    public function updateRating(float $rating): void
    {
        $currentTotal = $this->average_rating * $this->rating_count;
        $newCount = $this->rating_count + 1;
        $newAverage = ($currentTotal + $rating) / $newCount;

        $this->update([
            'average_rating' => round($newAverage, 2),
            'rating_count' => $newCount,
        ]);
    }

    // Validation Methods
    public function canBeInstalledBy(Tenant $tenant): array
    {
        $issues = [];

        // Check if already installed
        if ($tenant->hasModule($this->slug)) {
            $issues[] = 'El módulo ya está instalado para este tenant.';
        }

        // Check dependencies
        foreach ($this->dependencies ?? [] as $requiredModuleSlug) {
            if (!$tenant->hasModule($requiredModuleSlug)) {
                $issues[] = "Requiere el módulo: {$requiredModuleSlug}";
            }
        }

        // Check conflicts
        foreach ($this->conflicts ?? [] as $conflictModuleSlug) {
            if ($tenant->hasModule($conflictModuleSlug)) {
                $issues[] = "Entra en conflicto con el módulo: {$conflictModuleSlug}";
            }
        }

        return $issues;
    }

    public function canBeRemovedFrom(Tenant $tenant): array
    {
        $issues = [];

        // Check if other modules depend on this one
        $dependentModules = Module::active()
            ->where('dependencies', 'like', '%"' . $this->slug . '"%')
            ->get();

        foreach ($dependentModules as $module) {
            if ($tenant->hasModule($module->slug)) {
                $issues[] = "El módulo '{$module->name}' depende de este módulo.";
            }
        }

        return $issues;
    }

    // Usage-Based Pricing
    public function getUsageBasedPrice(array $usageData): float
    {
        if (empty($this->billing_tiers)) {
            return $this->getPrice($this->billing_cycle);
        }

        $totalUsage = $this->calculateTotalUsage($usageData);
        $applicableTier = $this->findApplicableTier($totalUsage);

        return $applicableTier ? (float) $applicableTier['price'] : $this->getPrice($this->billing_cycle);
    }

    private function calculateTotalUsage(array $usageData): float|int
    {
        $total = 0;
        foreach ($usageData as $metric => $value) {
            if (is_numeric($value)) {
                $total += $value;
            }
        }
        return $total;
    }

    private function findApplicableTier(float $usage): ?array
    {
        $tiers = $this->billing_tiers ?? [];

        // Sort tiers by min_usage descending
        usort($tiers, function ($a, $b) {
            return ($b['min_usage'] ?? 0) <=> ($a['min_usage'] ?? 0);
        });

        foreach ($tiers as $tier) {
            $minUsage = $tier['min_usage'] ?? 0;
            $maxUsage = $tier['max_usage'] ?? PHP_INT_MAX;

            if ($usage >= $minUsage && $usage <= $maxUsage) {
                return $tier;
            }
        }

        return null;
    }

    // Helper Methods
    public function getIconWithPrefix(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        // Ensure icon has proper heroicon prefix
        if (!str_starts_with($this->icon, 'heroicon-')) {
            // Try to guess the prefix based on common patterns
            if (str_contains($this->icon, '-o-')) {
                return 'heroicon-o-' . str_replace('-o-', '', $this->icon);
            }
            if (str_contains($this->icon, '-s-')) {
                return 'heroicon-s-' . str_replace('-s-', '', $this->icon);
            }
            if (str_contains($this->icon, '-m-')) {
                return 'heroicon-m-' . str_replace('-m-', '', $this->icon);
            }

            // Default to outline
            return 'heroicon-o-' . $this->icon;
        }

        return $this->icon;
    }

    public function getDisplayCategory(): string
    {
        return match($this->category) {
            'inventory' => 'Gestión de Inventario',
            'pos' => 'Punto de Venta',
            'analytics' => 'Análisis y Reportes',
            'integration' => 'Integraciones',
            'communication' => 'Comunicación',
            'security' => 'Seguridad',
            'automation' => 'Automatización',
            'customization' => 'Personalización',
            default => ucfirst($this->category ?? 'General'),
        };
    }

    public function getDisplayBillingCycle(): string
    {
        return match($this->billing_cycle) {
            'monthly' => 'Mensual',
            'yearly' => 'Anual',
            'once' => 'Una vez',
            default => ucfirst($this->billing_cycle),
        };
    }

    public function isAvailableForTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function getTrialPeriodDisplay(): string
    {
        if (!$this->isAvailableForTrial()) {
            return 'Sin prueba gratuita';
        }

        return $this->trial_days . ' ' . ($this->trial_days == 1 ? 'día' : 'días');
    }

    // Static Methods
    public static function getAvailableCategories(): array
    {
        return [
            'inventory' => 'Gestión de Inventario',
            'pos' => 'Punto de Venta',
            'analytics' => 'Análisis y Reportes',
            'integration' => 'Integraciones',
            'communication' => 'Comunicación',
            'security' => 'Seguridad',
            'automation' => 'Automatización',
            'customization' => 'Personalización',
        ];
    }

    public static function getAvailableIcons(): array
    {
        return [
            'home' => 'heroicon-o-home',
            'users' => 'heroicon-o-users',
            'shopping-cart' => 'heroicon-o-shopping-cart',
            'chart-bar' => 'heroicon-o-chart-bar',
            'cog' => 'heroicon-o-cog-6-tooth',
            'book' => 'heroicon-o-book-open',
            'document' => 'heroicon-o-document',
            'folder' => 'heroicon-o-folder',
            'cloud' => 'heroicon-o-cloud',
            'shield' => 'heroicon-o-shield-check',
            'key' => 'heroicon-o-key',
            'puzzle' => 'heroicon-o-puzzle-piece',
            'lightning' => 'heroicon-o-bolt',
            'star' => 'heroicon-o-star',
        ];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getFeatured(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->visible()->featured()->ordered()->get();
    }

    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->visible()->byCategory($category)->ordered()->get();
    }

    public static function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->visible()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%");
            })
            ->ordered()
            ->get();
    }
}