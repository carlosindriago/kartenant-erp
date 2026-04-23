<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ModuleCacheService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const FAST_CACHE_TTL = 300; // 5 minutes

    /**
     * Get tenant's active modules with caching
     */
    public function getTenantModules(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "tenant.{$tenant->id}.modules.active";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return $tenant->activeModules()
                ->with(['featureFlags', 'limits'])
                ->get();
        });
    }

    /**
     * Get tenant's feature flags with caching
     */
    public function getTenantFeatures(Tenant $tenant): array
    {
        $cacheKey = "tenant.{$tenant->id}.features.flags";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return $tenant->getEnabledFeatureFlags();
        });
    }

    /**
     * Check if tenant has feature access with caching
     */
    public function hasFeatureAccess(Tenant $tenant, string $feature): bool
    {
        $cacheKey = "tenant.{$tenant->id}.feature.{$feature}";

        return Cache::remember($cacheKey, self::FAST_CACHE_TTL, function () use ($tenant, $feature) {
            return $tenant->hasFeatureAccess($feature);
        });
    }

    /**
     * Get module by slug with caching
     */
    public function getModuleBySlug(string $slug): ?Module
    {
        $cacheKey = "module.slug.{$slug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return Module::where('slug', $slug)
                ->with(['featureFlags', 'limits'])
                ->first();
        });
    }

    /**
     * Get available modules for tenant with caching
     */
    public function getAvailableModulesForTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "tenant.{$tenant->id}.modules.available";

        return Cache::remember($cacheKey, self::FAST_CACHE_TTL, function () use ($tenant) {
            return Module::active()
                ->visible()
                ->whereNotIn('id', $tenant->activeModules()->pluck('id'))
                ->ordered()
                ->get();
        });
    }

    /**
     * Get tenant module limits with caching
     */
    public function getTenantModuleLimits(Tenant $tenant, string $moduleSlug): ?array
    {
        $cacheKey = "tenant.{$tenant->id}.limits.{$moduleSlug}";

        return Cache::remember($cacheKey, self::FAST_CACHE_TTL, function () use ($tenant, $moduleSlug) {
            $tenantModule = $tenant->getTenantModule($moduleSlug);

            return $tenantModule ? $tenantModule->getEffectiveLimits() : null;
        });
    }

    /**
     * Cache module usage statistics
     */
    public function cacheModuleUsage(Tenant $tenant, Module $module, array $usageData): void
    {
        $cacheKey = "tenant.{$tenant->id}.usage.{$module->slug}";

        // Get existing usage data
        $existingUsage = Cache::get($cacheKey, []);

        // Merge with new usage data
        foreach ($usageData as $metric => $value) {
            if (! isset($existingUsage[$metric])) {
                $existingUsage[$metric] = 0;
            }

            if (is_numeric($value)) {
                $existingUsage[$metric] += $value;
            }
        }

        // Cache for a short period (5 minutes) for real-time updates
        Cache::put($cacheKey, $existingUsage, self::FAST_CACHE_TTL);

        // Also update in Redis for faster access if available
        if (app()->bound('redis')) {
            $redisKey = "modules:usage:{$tenant->id}:{$module->slug}";
            Redis::hmset($redisKey, $existingUsage);
            Redis::expire($redisKey, self::FAST_CACHE_TTL);
        }
    }

    /**
     * Get cached module usage statistics
     */
    public function getModuleUsage(Tenant $tenant, Module $module): array
    {
        $cacheKey = "tenant.{$tenant->id}.usage.{$module->slug}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Clear tenant cache
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        $patterns = [
            "tenant.{$tenant->id}.*",
            "modules:usage:{$tenant->id}:*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Clear Redis keys if available
        if (app()->bound('redis')) {
            $redisKeys = Redis::keys("modules:usage:{$tenant->id}:*");
            if (! empty($redisKeys)) {
                Redis::del($redisKeys);
            }
        }
    }

    /**
     * Clear module cache
     */
    public function clearModuleCache(Module $module): void
    {
        $patterns = [
            "module.slug.{$module->slug}",
            "modules:*:{$module->slug}:*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Preload module data for performance
     */
    public function preloadModuleData(Tenant $tenant): void
    {
        // Preload active modules
        $this->getTenantModules($tenant);

        // Preload feature flags
        $this->getTenantFeatures($tenant);

        // Preload available modules
        $this->getAvailableModulesForTenant($tenant);

        // Preload usage data for active modules
        foreach ($tenant->activeModules() as $module) {
            $this->getModuleUsage($tenant, $module);
        }
    }

    /**
     * Warm up cache for all active tenants
     */
    public function warmupCacheForAllTenants(): void
    {
        Tenant::active()->chunk(50, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->preloadModuleData($tenant);
            }
        });
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'module_count' => Module::active()->count(),
            'tenant_count' => Tenant::active()->count(),
            'active_assignments' => 0,
            'cache_keys' => 0,
        ];

        // Count active tenant-module assignments
        Tenant::active()->chunk(50, function ($tenants) use (&$stats) {
            foreach ($tenants as $tenant) {
                $stats['active_assignments'] += $tenant->activeModules()->count();
            }
        });

        // Count cache keys (approximate)
        if (app()->bound('redis')) {
            $keys = Redis::keys('modules:*');
            $stats['cache_keys'] = count($keys);
        }

        return $stats;
    }

    /**
     * Optimize cache storage
     */
    public function optimizeCache(): void
    {
        // Clear expired cache entries
        if (app()->bound('redis')) {
            $keys = Redis::keys('modules:*');
            foreach ($keys as $key) {
                $ttl = Redis::ttl($key);
                if ($ttl === -1) {
                    // Key exists but has no expiration
                    Redis::expire($key, self::CACHE_TTL);
                }
            }
        }
    }

    /**
     * Cache module configuration for tenant
     */
    public function cacheTenantModuleConfiguration(Tenant $tenant, Module $module, array $config): void
    {
        $cacheKey = "tenant.{$tenant->id}.config.{$module->slug}";
        Cache::put($cacheKey, $config, self::CACHE_TTL);
    }

    /**
     * Get cached module configuration for tenant
     */
    public function getTenantModuleConfiguration(Tenant $tenant, Module $module): array
    {
        $cacheKey = "tenant.{$tenant->id}.config.{$module->slug}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Cache module permissions for tenant
     */
    public function cacheTenantModulePermissions(Tenant $tenant, Module $module, array $permissions): void
    {
        $cacheKey = "tenant.{$tenant->id}.permissions.{$module->slug}";
        Cache::put($cacheKey, $permissions, self::FAST_CACHE_TTL);
    }

    /**
     * Get cached module permissions for tenant
     */
    public function getTenantModulePermissions(Tenant $tenant, Module $module): array
    {
        $cacheKey = "tenant.{$tenant->id}.permissions.{$module->slug}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Invalidate related caches when module changes
     */
    public function invalidateRelatedCaches(Module $module): void
    {
        // Clear module-specific cache
        $this->clearModuleCache($module);

        // Clear caches for all tenants that have this module
        Tenant::whereHas('modules', function ($query) use ($module) {
            $query->where('modules.id', $module->id);
        })->chunk(50, function ($tenants) {
            foreach ($tenants as $tenant) {
                $this->clearTenantCache($tenant);
            }
        });
    }
}
