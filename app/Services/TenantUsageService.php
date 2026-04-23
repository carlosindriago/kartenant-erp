<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Models\UsageMetricsLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class TenantUsageService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const COUNTER_TTL = 86400 * 32; // 32 days (covers current + next month)

    private const CACHE_PREFIX = 'tenant_usage:';

    private const COUNTER_PREFIX = 'usage_counter:';

    /**
     * Get current usage for tenant (with caching)
     */
    public function getCurrentUsage(int $tenantId, bool $refresh = false): TenantUsage
    {
        $cacheKey = self::CACHE_PREFIX."current:{$tenantId}";

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return TenantUsage::getOrCreateCurrentUsage($tenantId);
        });
    }

    /**
     * Increment usage counter with Redis for performance
     */
    public function incrementUsage(
        int $tenantId,
        string $metricType,
        int $value = 1,
        string $source = 'observer',
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        // Update Redis counter immediately for performance
        $this->incrementRedisCounter($tenantId, $metricType, $value);

        // For testing/sync mode, process immediately to avoid tenant context issues
        if (app()->environment('testing', 'local')) {
            $this->processUsageIncrement($tenantId, $metricType, $value, $source, $entityType, $entityId, $metadata);
        } else {
            // Queue the database update for async processing with tenant context
            Queue::push(function () use ($tenantId, $metricType, $value, $source, $entityType, $entityId, $metadata) {
                // Set tenant context for queued job
                tenancy()->initialize(Tenant::find($tenantId));
                $this->processUsageIncrement($tenantId, $metricType, $value, $source, $entityType, $entityId, $metadata);
            });
        }

        // Log the metric immediately for audit trail (simplified for testing)
        try {
            UsageMetricsLog::create([
                'tenant_id' => $tenantId,
                'metric_type' => $metricType.'_created',
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'value' => $value,
                'source' => $source,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Ignore logging errors for testing
            logger()->warning('Usage logging failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fast Redis counter increment
     */
    private function incrementRedisCounter(int $tenantId, string $metricType, int $value): void
    {
        $key = $this->getCounterKey($tenantId, $metricType);
        Cache::increment($key, $value);

        // Set expiry if this is a new key
        if (! Cache::has($key)) {
            Cache::put($key, $value, self::COUNTER_TTL);
        }
    }

    /**
     * Get Redis counter value
     */
    public function getRedisCounter(int $tenantId, string $metricType): int
    {
        $key = $this->getCounterKey($tenantId, $metricType);

        return (int) Cache::get($key, 0);
    }

    /**
     * Get all Redis counters for a tenant
     */
    public function getAllRedisCounters(int $tenantId): array
    {
        $metrics = ['sales', 'products', 'users', 'storage'];
        $counters = [];

        foreach ($metrics as $metric) {
            $counters[$metric] = $this->getRedisCounter($tenantId, $metric);
        }

        return $counters;
    }

    /**
     * Process usage increment in database (async)
     */
    private function processUsageIncrement(
        int $tenantId,
        string $metricType,
        int $value,
        string $source,
        ?string $entityType,
        ?int $entityId,
        array $metadata
    ): void {
        try {
            $usage = $this->getCurrentUsage($tenantId, true);

            // Debug: log what we're trying to increment
            if (app()->environment('local', 'testing')) {
                logger()->debug('Processing usage increment', [
                    'tenant_id' => $tenantId,
                    'metric_type' => $metricType,
                    'value' => $value,
                    'current_value' => $usage->getCurrent($metricType),
                ]);
            }

            $usage->incrementUsage($metricType, $value);

            // Check if alerts need to be sent
            if ($usage->hasPendingAlerts()) {
                Queue::push(function () use ($usage) {
                    app(UsageAlertService::class)->processAlerts($usage);
                });
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            logger()->error('Failed to process usage increment', [
                'tenant_id' => $tenantId,
                'metric_type' => $metricType,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Synchronize Redis counters with database
     */
    public function synchronizeCounters(int $tenantId): void
    {
        $redisCounters = $this->getAllRedisCounters($tenantId);
        $usage = $this->getCurrentUsage($tenantId, true);

        $dbCounters = [
            'sales' => $usage->sales_count,
            'products' => $usage->products_count,
            'users' => $usage->users_count,
            'storage' => $usage->storage_size_mb,
        ];

        foreach ($redisCounters as $metric => $redisValue) {
            $dbValue = $dbCounters[$metric] ?? 0;

            if ($redisValue !== $dbValue) {
                // Update database to match Redis (Redis is source of truth for current period)
                $usage->update([
                    "{$metric}_count" => $redisValue,
                ]);
            }
        }

        $usage->calculatePercentages();
        $this->clearCache($tenantId);
    }

    /**
     * Recalculate usage from logs (for fixing discrepancies)
     */
    public function recalculateFromLogs(int $tenantId, int $year, int $month): void
    {
        $summary = UsageMetricsLog::getUsageSummary($tenantId, $year, $month);

        $usage = TenantUsage::where('tenant_id', $tenantId)
            ->forPeriod($year, $month)
            ->first();

        if (! $usage) {
            $usage = TenantUsage::create([
                'tenant_id' => $tenantId,
                'year' => $year,
                'month' => $month,
                // Limits will be set from subscription plan
            ]);
        }

        $usage->update([
            'sales_count' => $summary['sales'],
            'products_count' => $summary['products'],
            'users_count' => $summary['users'],
            'storage_size_mb' => $summary['storage'],
        ]);

        $usage->calculatePercentages();
        $this->clearCache($tenantId);
    }

    /**
     * Reset monthly counters (called on first day of month)
     */
    public function resetMonthlyCounters(): void
    {
        Tenant::chunk(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                // Clear Redis counters for previous month
                $this->clearRedisCounters($tenant->id);

                // Clear usage cache
                $this->clearCache($tenant->id);

                // Queue sync to ensure database is source of truth
                Queue::push(function () use ($tenant) {
                    $this->getCurrentUsage($tenant->id, true);
                });
            }
        });
    }

    /**
     * Check if tenant can perform action (with caching)
     */
    public function canPerformAction(int $tenantId, string $action): bool
    {
        $cacheKey = self::CACHE_PREFIX."can:{$tenantId}:{$action}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $action) { // 5 minutes cache
            $usage = $this->getCurrentUsage($tenantId);

            return match ($action) {
                'create_product' => $usage->canCreateProduct(),
                'create_user' => $usage->canCreateUser(),
                'make_sale' => $usage->canMakeSale(), // Always true
                default => true,
            };
        });
    }

    /**
     * Get usage status with all details
     */
    public function getUsageStatus(int $tenantId): array
    {
        $usage = $this->getCurrentUsage($tenantId);
        $redisCounters = $this->getAllRedisCounters($tenantId);

        return [
            'status' => $usage->status,
            'upgrade_required' => $usage->upgrade_required_next_cycle,
            'days_remaining' => $usage->getDaysRemainingInPeriod(),
            'metrics' => [
                'sales' => [
                    'current' => $usage->sales_count,
                    'redis' => $redisCounters['sales'],
                    'limit' => $usage->max_sales_per_month,
                    'percentage' => $usage->sales_percentage,
                    'remaining' => $usage->getRemaining('sales'),
                    'zone' => $usage->getZoneForMetric('sales'),
                ],
                'products' => [
                    'current' => $usage->products_count,
                    'redis' => $redisCounters['products'],
                    'limit' => $usage->max_products,
                    'percentage' => $usage->products_percentage,
                    'remaining' => $usage->getRemaining('products'),
                    'zone' => $usage->getZoneForMetric('products'),
                ],
                'users' => [
                    'current' => $usage->users_count,
                    'redis' => $redisCounters['users'],
                    'limit' => $usage->max_users,
                    'percentage' => $usage->users_percentage,
                    'remaining' => $usage->getRemaining('users'),
                    'zone' => $usage->getZoneForMetric('users'),
                ],
                'storage' => [
                    'current' => $usage->storage_size_mb,
                    'redis' => $redisCounters['storage'],
                    'limit' => $usage->max_storage_mb,
                    'percentage' => $usage->storage_percentage,
                    'remaining' => $usage->getRemaining('storage'),
                    'zone' => $usage->getZoneForMetric('storage'),
                ],
            ],
            'alerts' => $usage->getAlertsToSend(),
            'period' => $usage->getPeriodLabel(),
        ];
    }

    /**
     * Update storage usage (for file uploads/deletes)
     */
    public function updateStorageUsage(int $tenantId, int $sizeBytes, bool $increment = true): void
    {
        $sizeMb = round($sizeBytes / 1024 / 1024, 2);
        $value = $increment ? $sizeMb : -$sizeMb;

        $this->incrementUsage(
            $tenantId,
            'storage_used',
            abs($value),
            'observer',
            'File',
            null,
            [
                'size_bytes' => $sizeBytes,
                'size_mb' => $sizeMb,
                'increment' => $increment,
            ]
        );
    }

    /**
     * Get tenants that need attention
     */
    public function getTenantsNeedingAttention(): array
    {
        return [
            'overdraft' => TenantUsage::overdraft()
                ->with('tenant')
                ->get()
                ->pluck('tenant.name', 'id')
                ->toArray(),

            'critical' => TenantUsage::critical()
                ->with('tenant')
                ->get()
                ->pluck('tenant.name', 'id')
                ->toArray(),

            'needs_upgrade' => TenantUsage::needsUpgrade()
                ->with('tenant')
                ->get()
                ->pluck('tenant.name', 'id')
                ->toArray(),
        ];
    }

    /**
     * Clear all caches for a tenant
     */
    public function clearCache(int $tenantId): void
    {
        $patterns = [
            self::CACHE_PREFIX."current:{$tenantId}",
            self::CACHE_PREFIX."can:{$tenantId}:*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear Redis counters for a tenant
     */
    public function clearRedisCounters(int $tenantId): void
    {
        $metrics = ['sales', 'products', 'users', 'storage'];

        foreach ($metrics as $metric) {
            $key = $this->getCounterKey($tenantId, $metric);
            Cache::forget($key);
        }
    }

    /**
     * Generate counter key for Redis
     */
    private function getCounterKey(int $tenantId, string $metricType): string
    {
        return self::COUNTER_PREFIX."{$tenantId}:{$metricType}:".now()->format('Y-m');
    }

    /**
     * Get usage statistics for admin dashboard
     */
    public function getAdminStatistics(): array
    {
        return [
            'total_tenants' => Tenant::count(),
            'normal_usage' => TenantUsage::normal()->count(),
            'warning_usage' => TenantUsage::warning()->count(),
            'overdraft_usage' => TenantUsage::overdraft()->count(),
            'critical_usage' => TenantUsage::critical()->count(),
            'upgrades_needed' => TenantUsage::needsUpgrade()->count(),
            'alerts_today' => UsageAlert::whereDate('created_at', today())->count(),
        ];
    }
}
