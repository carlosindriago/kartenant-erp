<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantModule extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';
    protected $table = 'tenant_modules';

    protected $fillable = [
        'tenant_id',
        'module_id',
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
        'next_billing_at',
        'tenant_subscription_id',
        'invoice_line_item_id',
        'added_by',
        'notes',
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
        'configuration' => 'array',
        'limits_override' => 'array',
        'metadata' => 'array',
        'usage_stats' => 'array',
        'last_used_at' => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // Status Constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    // Status Methods
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_SUSPENDED => 'warning',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_PENDING => 'info',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Activo',
            self::STATUS_INACTIVE => 'Inactivo',
            self::STATUS_SUSPENDED => 'Suspendido',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_EXPIRED => 'Expirado',
            self::STATUS_PENDING => 'Pendiente',
            default => ucfirst($this->status),
        };
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Expiration Methods
    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= $days;
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffInDays(now(), false);
    }

    // Pricing Methods
    public function getPrice(): float
    {
        if ($this->price_override !== null) {
            return (float) $this->price_override;
        }

        return $this->module->getPrice($this->billing_cycle);
    }

    public function getFormattedPrice(): string
    {
        $currency = $this->currency_override ?? $this->module->currency;
        $price = $this->getPrice();

        return $currency . ' ' . number_format($price, 2);
    }

    public function getCurrency(): string
    {
        return $this->currency_override ?? $this->module->currency ?? 'USD';
    }

    // Configuration Methods
    public function getConfiguration(): array
    {
        $moduleConfig = $this->module->getConfiguration();
        $tenantConfig = $this->configuration ?? [];

        return array_merge($moduleConfig, $tenantConfig);
    }

    public function getConfigurationValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }

    public function setConfigurationValue(string $key, mixed $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
        $this->save();
    }

    public function getEffectiveLimits(): array
    {
        $moduleLimits = $this->module->getLimits();
        $tenantLimits = $this->limits_override ?? [];

        // Tenant limits override module limits
        return array_merge($moduleLimits, $tenantLimits);
    }

    public function getLimit(string $key, mixed $default = null): mixed
    {
        return data_get($this->limits_override, $key, $this->module->getLimit($key, $default));
    }

    public function setLimit(string $key, mixed $value): void
    {
        $limits = $this->limits_override ?? [];
        data_set($limits, $key, $value);
        $this->limits_override = $limits;
        $this->save();
    }

    // Usage Tracking
    public function recordUsage(array $usageData): void
    {
        $currentStats = $this->usage_stats ?? [];

        // Update usage statistics
        foreach ($usageData as $metric => $value) {
            if (!isset($currentStats[$metric])) {
                $currentStats[$metric] = 0;
            }

            if (is_numeric($value)) {
                $currentStats[$metric] += $value;
            }
        }

        // Update last used timestamp
        $this->update([
            'usage_stats' => $currentStats,
            'last_used_at' => now(),
        ]);

        // Log usage event
        ModuleUsageLog::create([
            'tenant_id' => $this->tenant_id,
            'module_id' => $this->module_id,
            'event_type' => 'usage',
            'usage_data' => $usageData,
            'limits_data' => $this->getEffectiveLimits(),
        ]);
    }

    public function getLastUsageDate(): ?\Carbon\Carbon
    {
        return $this->last_used_at;
    }

    public function getUsageMetric(string $metric): mixed
    {
        return data_get($this->usage_stats, $metric, 0);
    }

    public function isOverLimit(string $metric): bool
    {
        $limit = $this->getLimit($metric);
        $usage = $this->getUsageMetric($metric);

        if ($limit === null) {
            return false; // No limit set
        }

        return $usage > $limit;
    }

    public function getLimitUsagePercentage(string $metric): float
    {
        $limit = $this->getLimit($metric);
        $usage = $this->getUsageMetric($metric);

        if ($limit === null || $limit <= 0) {
            return 0.0; // Unlimited or no limit
        }

        return min(($usage / $limit) * 100, 100.0);
    }

    // Feature Access Methods
    public function hasAccessToFeature(string $feature): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->module->hasFeatureFlag($feature);
    }

    public function canPerformAction(string $action): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if action is allowed by module permissions
        $permissions = $this->module->permissions ?? [];

        if (empty($permissions)) {
            return true; // No specific permissions set, allow all
        }

        return in_array($action, $permissions);
    }

    // Lifecycle Methods
    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function suspend(): bool
    {
        return $this->update([
            'is_active' => false,
            'status' => self::STATUS_SUSPENDED,
        ]);
    }

    public function cancel(): bool
    {
        return $this->update([
            'cancelled_at' => now(),
            'status' => self::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);
    }

    public function renew(?\Carbon\Carbon $newExpiresAt = null): bool
    {
        $expiresAt = $newExpiresAt ?? $this->calculateNewExpiryDate();

        return $this->update([
            'expires_at' => $expiresAt,
            'status' => self::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    private function calculateNewExpiryDate(): \Carbon\Carbon
    {
        $currentDate = $this->expires_at ?? now();

        return match($this->billing_cycle) {
            'monthly' => $currentDate->addMonth(),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };
    }

    // Billing Integration
    public function getMonthlyCost(): float
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->getPrice();
        }

        if ($this->billing_cycle === 'yearly') {
            return $this->getPrice() / 12;
        }

        // For one-time payments, calculate monthly equivalent
        $daysInBillingPeriod = $this->expires_at
            ? $this->starts_at->diffInDays($this->expires_at)
            : 30;

        return $daysInBillingPeriod > 0 ? $this->getPrice() / ($daysInBillingPeriod / 30) : 0;
    }

    public function getYearlyCost(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return $this->getPrice();
        }

        if ($this->billing_cycle === 'monthly') {
            return $this->getPrice() * 12;
        }

        // For one-time payments, estimate yearly cost
        return $this->getMonthlyCost() * 12;
    }

    public function getSetupFeeCost(): float
    {
        if ($this->module->hasSetupFee()) {
            return (float) $this->module->setup_fee;
        }

        return 0;
    }

    public function getNextBillingDate(): ?\Carbon\Carbon
    {
        if ($this->billing_cycle === 'once') {
            return null;
        }

        if ($this->expires_at) {
            return $this->expires_at;
        }

        // Calculate next billing date based on starts_at and billing cycle
        $billingDate = $this->starts_at;

        return match($this->billing_cycle) {
            'monthly' => $billingDate->addMonth(),
            'yearly' => $billingDate->addYear(),
            default => null,
        };
    }

    // Validation Methods
    public function canActivate(): array
    {
        $issues = [];

        if ($this->isActive()) {
            $issues[] = 'El módulo ya está activo.';
        }

        if ($this->isCancelled()) {
            $issues[] = 'No se puede activar un módulo cancelado.';
        }

        if ($this->isExpired()) {
            $issues[] = 'El módulo ha expirado. Debe renovarlo primero.';
        }

        // Check module dependencies
        $dependencyIssues = $this->module->canBeInstalledBy($this->tenant);
        $issues = array_merge($issues, $dependencyIssues);

        return $issues;
    }

    public function canDeactivate(): array
    {
        $issues = [];

        if (!$this->isActive()) {
            $issues[] = 'El módulo ya está inactivo.';
        }

        // Check if other modules depend on this one
        $dependencyIssues = $this->module->canBeRemovedFrom($this->tenant);
        $issues = array_merge($issues, $dependencyIssues);

        return $issues;
    }

    // Helper Methods
    public function getDisplayName(): string
    {
        return $this->module->name;
    }

    public function getDescription(): string
    {
        return $this->module->description ?? '';
    }

    public function getIcon(): ?string
    {
        return $this->module->getIconWithPrefix();
    }

    public function getModuleSlug(): string
    {
        return $this->module->slug;
    }

    public function isPaid(): bool
    {
        return $this->getPrice() > 0;
    }

    public function isFree(): bool
    {
        return !$this->isPaid();
    }

    public function getInstallDate(): \Carbon\Carbon
    {
        return $this->created_at;
    }

    public function getInstallDuration(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function hasCustomConfiguration(): bool
    {
        return !empty($this->configuration);
    }

    public function hasCustomLimits(): bool
    {
        return !empty($this->limits_override);
    }

    public function hasUsageData(): bool
    {
        return !empty($this->usage_stats);
    }

    public function getModuleCategory(): string
    {
        return $this->module->category ?? 'general';
    }

    public function isCustomModule(): bool
    {
        return $this->module->is_custom ?? false;
    }
}