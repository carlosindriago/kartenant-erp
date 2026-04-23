<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ModuleCacheMiddleware
{
    public function __construct(
        private FeatureFlagService $featureFlagService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip caching for admin routes
        if (str_starts_with($request->path(), 'admin/')) {
            return $next($request);
        }

        // Get current tenant
        $tenant = tenant();
        if (! $tenant) {
            return $next($request);
        }

        // Cache tenant's feature flags for the request
        $cacheKey = "tenant.{$tenant->id}.features";
        $features = Cache::remember($cacheKey, 3600, function () use ($tenant) {
            return $this->featureFlagService->getTenantFeatures($tenant);
        });

        // Store in request for easy access
        $request->attributes->set('tenant_features', $features);

        // Cache tenant's modules for the request
        $modulesCacheKey = "tenant.{$tenant->id}.modules";
        $modules = Cache::remember($modulesCacheKey, 3600, function () use ($tenant) {
            return $tenant->activeModules()
                ->with('featureFlags')
                ->get()
                ->keyBy('slug');
        });

        // Store in request for easy access
        $request->attributes->set('tenant_modules', $modules);

        // Cache module limits for the request
        $limitsCacheKey = "tenant.{$tenant->id}.module_limits";
        $limits = Cache::remember($limitsCacheKey, 1800, function () use ($tenant) {
            $limits = [];
            foreach ($tenant->activeTenantModules as $tenantModule) {
                $limits[$tenantModule->module->slug] = $tenantModule->getEffectiveLimits();
            }

            return $limits;
        });

        // Store in request for easy access
        $request->attributes->set('tenant_module_limits', $limits);

        return $next($request);
    }
}
