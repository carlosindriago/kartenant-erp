<?php

namespace App\Models;

use Carbon\Carbon;
// Temporarily disabled for testing - use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantUsage extends Model
{
    // use SoftDeletes; // Temporarily disabled for testing

    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',
        'year',
        'month',
        'sales_count',
        'products_count',
        'users_count',
        'storage_size_mb',
        'max_sales_per_month',
        'max_products',
        'max_users',
        'max_storage_mb',
        'sales_percentage',
        'products_percentage',
        'users_percentage',
        'storage_percentage',
        'status',
        'upgrade_required_next_cycle',
        'warning_sent',
        'overdraft_sent',
        'critical_sent',
        'last_calculated_at',
        'last_alert_sent_at',
    ];

    protected $casts = [
        'sales_percentage' => 'decimal:2',
        'products_percentage' => 'decimal:2',
        'users_percentage' => 'decimal:2',
        'storage_percentage' => 'decimal:2',
        'last_calculated_at' => 'datetime',
        'last_alert_sent_at' => 'datetime',
        'upgrade_required_next_cycle' => 'boolean',
        'warning_sent' => 'boolean',
        'overdraft_sent' => 'boolean',
        'critical_sent' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(UsageAlert::class);
    }

    public function metricsLog(): HasMany
    {
        return $this->hasMany(UsageMetricsLog::class);
    }

    // Scopes for different status levels
    public function scopeNormal($query)
    {
        return $query->where('status', 'normal');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    public function scopeOverdraft($query)
    {
        return $query->where('status', 'overdraft');
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    public function scopeNeedsUpgrade($query)
    {
        return $query->where('upgrade_required_next_cycle', true);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->forPeriod(
            now()->year,
            now()->month
        );
    }

    // Business logic methods
    public function isOverLimit(string $metric): bool
    {
        return $this->getPercentage($metric) > 100;
    }

    public function isWarningZone(string $metric): bool
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            $percentage = $this->getPercentage($metric);

            return $percentage >= 80 && $percentage <= 100;
        }

        $subscription = $tenant->subscription;
        $plan = $subscription->plan ?? null;

        if ($plan && $plan->hasConfigurableLimits()) {
            // Check if we've exceeded base limit but not yet reached overage limit
            $baseLimit = $this->getLimit($metric);
            $current = $this->getCurrent($metric);

            if ($baseLimit === null) {
                return false; // Unlimited
            }

            return $current > $baseLimit && ! $this->isCriticalZone($metric);
        }

        // Fallback to hardcoded percentages for legacy compatibility
        $percentage = $this->getPercentage($metric);

        return $percentage >= 80 && $percentage <= 100;
    }

    public function isOverdraftZone(string $metric): bool
    {
        return $this->isWarningZone($metric); // Overage and warning are the same concept
    }

    public function isCriticalZone(string $metric): bool
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            return $this->getPercentage($metric) > 120;
        }

        $subscription = $tenant->subscription;
        $plan = $subscription->plan ?? null;

        if ($plan && $plan->hasConfigurableLimits()) {
            // Check if we've exceeded the overage limit (hard limit)
            $overageLimit = $this->getOverageLimit($metric);
            $current = $this->getCurrent($metric);

            if ($overageLimit === null) {
                return false; // Unlimited
            }

            return $current > $overageLimit;
        }

        // Fallback to hardcoded percentage for legacy compatibility
        return $this->getPercentage($metric) > 120;
    }

    public function getPercentage(string $metric): float
    {
        $limit = $this->getOverageLimit($metric);
        $current = $this->getCurrent($metric);

        if ($limit === null || $limit === 0) {
            return 0;
        }

        return min(($current / $limit) * 100, 999.99);
    }

    public function getLimit(string $metric): ?int
    {
        return match ($metric) {
            'sales' => $this->max_sales_per_month,
            'products' => $this->max_products,
            'users' => $this->max_users,
            'storage' => $this->max_storage_mb,
            default => null,
        };
    }

    /**
     * Get overage limit based on subscription plan configuration
     */
    public function getOverageLimit(string $metric): ?int
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            return $this->getLimit($metric);
        }

        $subscription = $tenant->subscription;
        if (! $subscription) {
            return $this->getLimit($metric);
        }

        $plan = $subscription->plan;
        if (! $plan || ! $plan->hasConfigurableLimits()) {
            return $this->getLimit($metric);
        }

        // Map metric names to plan limit keys
        $metricMap = [
            'sales' => 'monthly_sales',
            'products' => 'products',
            'users' => 'users',
            'storage' => 'storage_mb',
        ];

        $planMetric = $metricMap[$metric] ?? $metric;

        return $plan->getOverageLimit($planMetric);
    }

    public function getCurrent(string $metric): int
    {
        return match ($metric) {
            'sales' => $this->sales_count,
            'products' => $this->products_count,
            'users' => $this->users_count,
            'storage' => $this->storage_size_mb,
            default => 0,
        };
    }

    public function getRemaining(string $metric): int
    {
        $limit = $this->getLimit($metric);
        $current = $this->getCurrent($metric);

        if ($limit === null) {
            return PHP_INT_MAX; // Unlimited
        }

        return max(0, $limit - $current);
    }

    public function canCreateProduct(): bool
    {
        // Always allow if no limit set
        if ($this->max_products === null) {
            return true;
        }

        // Block only in critical zone (>120%)
        return ! $this->isCriticalZone('products');
    }

    public function canCreateUser(): bool
    {
        // Always allow if no limit set
        if ($this->max_users === null) {
            return true;
        }

        // Block only in critical zone (>120%)
        return ! $this->isCriticalZone('users');
    }

    public function canMakeSale(): bool
    {
        // SALES SHOULD NEVER BE BLOCKED - Business continuity priority
        return true;
    }

    public function getZoneForMetric(string $metric): string
    {
        $tenant = $this->tenant;
        if (! $tenant) {
            // Fallback to percentage-based zones for testing/safety
            $percentage = $this->getPercentage($metric);
            if ($percentage > 120) {
                return 'critical';
            }
            if ($percentage > 100) {
                return 'overdraft';
            }
            if ($percentage >= 80) {
                return 'warning';
            }

            return 'normal';
        }

        $subscription = $tenant->subscription;
        $plan = $subscription->plan ?? null;

        if ($plan && $plan->hasConfigurableLimits()) {
            // Use configurable limit zones
            if ($this->isCriticalZone($metric)) {
                return 'critical';
            }
            if ($this->isWarningZone($metric)) {
                return 'warning';
            }

            return 'normal';
        }

        // Fallback to percentage-based zones for legacy compatibility
        $percentage = $this->getPercentage($metric);
        if ($percentage > 120) {
            return 'critical';
        }
        if ($percentage > 100) {
            return 'overdraft';
        }
        if ($percentage >= 80) {
            return 'warning';
        }

        return 'normal';
    }

    public function getHighestZone(): string
    {
        $zones = [
            $this->getZoneForMetric('sales'),
            $this->getZoneForMetric('products'),
            $this->getZoneForMetric('users'),
            $this->getZoneForMetric('storage'),
        ];

        $priority = ['critical' => 4, 'overdraft' => 3, 'warning' => 2, 'normal' => 1];

        $highestZone = 'normal';
        $highestPriority = 1;

        foreach ($zones as $zone) {
            if ($priority[$zone] > $highestPriority) {
                $highestZone = $zone;
                $highestPriority = $priority[$zone];
            }
        }

        return $highestZone;
    }

    public function updateStatus(): void
    {
        $this->status = $this->getHighestZone();

        // Set upgrade flag if in overdraft or critical
        $this->upgrade_required_next_cycle = in_array($this->status, ['overdraft', 'critical']);

        $this->last_calculated_at = now();
        $this->saveQuietly();
    }

    public function calculatePercentages(): void
    {
        $this->sales_percentage = $this->getPercentage('sales');
        $this->products_percentage = $this->getPercentage('products');
        $this->users_percentage = $this->getPercentage('users');
        $this->storage_percentage = $this->getPercentage('storage');

        $this->updateStatus();
    }

    public function incrementUsage(string $metric, int $amount = 1): void
    {
        $field = match ($metric) {
            'sales' => 'sales_count',
            'products' => 'products_count',
            'users' => 'users_count',
            'storage' => 'storage_size_mb',
            default => null,
        };

        if ($field) {
            $this->increment($field, $amount);
            $this->calculatePercentages();
        }
    }

    public function decrementUsage(string $metric, int $amount = 1): void
    {
        $field = match ($metric) {
            'sales' => 'sales_count',
            'products' => 'products_count',
            'users' => 'users_count',
            'storage' => 'storage_size_mb',
            default => null,
        };

        if ($field) {
            $this->decrement($field, $amount);
            $this->calculatePercentages();
        }
    }

    public static function getCurrentUsage(int $tenantId): ?self
    {
        return static::where('tenant_id', $tenantId)
            ->currentPeriod()
            ->first();
    }

    public static function getOrCreateCurrentUsage(int $tenantId): self
    {
        $usage = static::getCurrentUsage($tenantId);

        if (! $usage) {
            $tenant = Tenant::findOrFail($tenantId);
            $subscription = $tenant->subscription;
            $plan = $subscription->plan ?? null;

            // Use configurable limits if available, otherwise fall back to legacy limits
            $limits = [
                'max_sales_per_month' => null,
                'max_products' => null,
                'max_users' => null,
                'max_storage_mb' => null,
            ];

            if ($plan && $plan->hasConfigurableLimits()) {
                // Use new configurable limits system
                $limits['max_sales_per_month'] = $plan->getConfigurableLimit('monthly_sales');
                $limits['max_products'] = $plan->getConfigurableLimit('products');
                $limits['max_users'] = $plan->getConfigurableLimit('users');
                $limits['max_storage_mb'] = $plan->getConfigurableLimit('storage_mb');
            } elseif ($plan) {
                // Fall back to legacy system for backward compatibility
                $limits['max_sales_per_month'] = $plan->max_sales_per_month;
                $limits['max_products'] = $plan->max_products;
                $limits['max_users'] = $plan->max_users;
                $limits['max_storage_mb'] = $plan->max_storage_mb;
            }

            $usage = static::create(array_merge([
                'tenant_id' => $tenantId,
                'year' => now()->year,
                'month' => now()->month,
            ], $limits));
        }

        return $usage;
    }

    // Methods for alert management
    public function hasPendingAlerts(): bool
    {
        if ($this->status === 'warning' && ! $this->warning_sent) {
            return true;
        }

        if ($this->status === 'overdraft' && ! $this->overdraft_sent) {
            return true;
        }

        if ($this->status === 'critical' && ! $this->critical_sent) {
            return true;
        }

        return false;
    }

    public function getAlertsToSend(): array
    {
        $alerts = [];

        if ($this->status === 'warning' && ! $this->warning_sent) {
            $alerts[] = 'warning';
        }

        if ($this->status === 'overdraft' && ! $this->overdraft_sent) {
            $alerts[] = 'overdraft';
        }

        if ($this->status === 'critical' && ! $this->critical_sent) {
            $alerts[] = 'critical';
        }

        return $alerts;
    }

    public function markAlertSent(string $alertType): void
    {
        $field = match ($alertType) {
            'warning' => 'warning_sent',
            'overdraft' => 'overdraft_sent',
            'critical' => 'critical_sent',
            default => null,
        };

        if ($field) {
            $this->$field = true;
            $this->last_alert_sent_at = now();
            $this->saveQuietly();
        }
    }

    // Utility methods
    public function getPeriodLabel(): string
    {
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F');

        return "{$monthName} {$this->year}";
    }

    public function getDaysRemainingInPeriod(): int
    {
        $periodEnd = Carbon::createFromDate($this->year, $this->month, 1)
            ->endOfMonth()
            ->endOfDay();

        return now()->diffInDays($periodEnd, false);
    }

    public function isNearPeriodEnd(): bool
    {
        return $this->getDaysRemainingInPeriod() <= 3;
    }
}
