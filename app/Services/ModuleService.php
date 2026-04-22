<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\ModuleUsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ModuleService
{
    /**
     * Install a module for a tenant
     */
    public function installModule(Tenant $tenant, Module $module, array $options = []): TenantModule
    {
        return DB::connection('landlord')->transaction(function () use ($tenant, $module, $options) {
            // Validate installation requirements
            $validationIssues = $module->canBeInstalledBy($tenant);
            if (!empty($validationIssues)) {
                throw new \InvalidArgumentException(
                    'No se puede instalar el módulo: ' . implode(', ', $validationIssues)
                );
            }

            // Check if module already exists
            $existingTenantModule = $tenant->getTenantModule($module->slug);
            if ($existingTenantModule) {
                if ($existingTenantModule->isActive()) {
                    throw new \InvalidArgumentException('El módulo ya está activo para este tenant.');
                }

                // Reactivate existing module
                $existingTenantModule->activate();
                return $existingTenantModule;
            }

            // Install new module
            $tenantModule = $tenant->addModule($module, $options);

            // Register module features
            $this->registerModuleFeatures($tenant, $module, $tenantModule);

            // Clear tenant cache
            $this->clearTenantCache($tenant);

            Log::info('Module installed', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'options' => $options,
            ]);

            return $tenantModule;
        });
    }

    /**
     * Uninstall a module from a tenant
     */
    public function uninstallModule(Tenant $tenant, Module $module, string $reason = ''): bool
    {
        return DB::connection('landlord')->transaction(function () use ($tenant, $module, $reason) {
            // Validate uninstallation requirements
            $validationIssues = $module->canBeRemovedFrom($tenant);
            if (!empty($validationIssues)) {
                throw new \InvalidArgumentException(
                    'No se puede desinstalar el módulo: ' . implode(', ', $validationIssues)
                );
            }

            $tenantModule = $tenant->getTenantModule($module->slug);
            if (!$tenantModule) {
                throw new \InvalidArgumentException('El módulo no está instalado para este tenant.');
            }

            // Unregister module features
            $this->unregisterModuleFeatures($tenant, $module, $tenantModule);

            // Remove module
            $result = $tenant->removeModule($module, $reason);

            // Clear tenant cache
            $this->clearTenantCache($tenant);

            Log::info('Module uninstalled', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'reason' => $reason,
            ]);

            return $result;
        });
    }

    /**
     * Update module configuration for a tenant
     */
    public function updateModuleConfiguration(
        Tenant $tenant,
        Module $module,
        array $configuration,
        ?User $user = null
    ): TenantModule {
        return DB::connection('landlord')->transaction(function () use ($tenant, $module, $configuration, $user) {
            $tenantModule = $tenant->getTenantModule($module->slug);
            if (!$tenantModule) {
                throw new \InvalidArgumentException('El módulo no está instalado para este tenant.');
            }

            $oldConfiguration = $tenantModule->configuration ?? [];

            // Update configuration
            $tenantModule->update([
                'configuration' => array_merge($oldConfiguration, $configuration),
            ]);

            // Log configuration change
            ModuleUsageLog::logConfiguration($tenant, $module, $configuration, $user);

            // Clear tenant cache
            $this->clearTenantCache($tenant);

            Log::info('Module configuration updated', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'configuration' => $configuration,
            ]);

            return $tenantModule;
        });
    }

    /**
     * Update module limits for a tenant
     */
    public function updateModuleLimits(
        Tenant $tenant,
        Module $module,
        array $limits,
        ?User $user = null
    ): TenantModule {
        return DB::connection('landlord')->transaction(function () use ($tenant, $module, $limits, $user) {
            $tenantModule = $tenant->getTenantModule($module->slug);
            if (!$tenantModule) {
                throw new \InvalidArgumentException('El módulo no está instalado para este tenant.');
            }

            // Validate limits
            $validationErrors = $this->validateLimits($module, $limits);
            if (!empty($validationErrors)) {
                throw new \InvalidArgumentException(
                    'Límites inválidos: ' . implode(', ', $validationErrors)
                );
            }

            // Update limits
            $tenantModule->update([
                'limits_override' => $limits,
            ]);

            // Log limit change
            ModuleUsageLog::logAction(
                $tenant,
                $module,
                'limits_updated',
                $user,
                null,
                ['new_limits' => $limits]
            );

            // Clear tenant cache
            $this->clearTenantCache($tenant);

            Log::info('Module limits updated', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'limits' => $limits,
            ]);

            return $tenantModule;
        });
    }

    /**
     * Record module usage
     */
    public function recordModuleUsage(
        Tenant $tenant,
        Module $module,
        array $usageData,
        ?User $user = null,
        ?string $feature = null
    ): void {
        $tenantModule = $tenant->getTenantModule($module->slug);
        if (!$tenantModule || !$tenantModule->isActive()) {
            return;
        }

        // Record usage in tenant module
        $tenantModule->recordUsage($usageData);

        // Check for limit violations
        $violations = $this->checkUsageViolations($tenantModule, $usageData);
        foreach ($violations as $violation) {
            ModuleUsageLog::logLimitReached(
                $tenant,
                $module,
                $violation['limit'],
                $violation['current'],
                $violation['limit_value'],
                $user
            );

            Log::warning('Module limit reached', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'violation' => $violation,
            ]);
        }

        // Log usage
        ModuleUsageLog::logAction(
            $tenant,
            $module,
            'usage_recorded',
            $user,
            $feature,
            $usageData
        );
    }

    /**
     * Get available modules for tenant
     */
    public function getAvailableModulesForTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $installedModules = $tenant->activeModules()->pluck('id');

        return Module::active()
            ->visible()
            ->whereNotIn('id', $installedModules)
            ->ordered()
            ->get();
    }

    /**
     * Get recommended modules for tenant based on subscription plan
     */
    public function getRecommendedModulesForTenant(Tenant $tenant): \Illuminate\Database\Eloquent\Collection
    {
        $activeSubscription = $tenant->activeSubscription();
        if (!$activeSubscription) {
            return collect();
        }

        $planFeatures = $activeSubscription->plan->features ?? [];

        // Get modules that complement the plan features
        return Module::active()
            ->visible()
            ->featured()
            ->whereNotIn('id', $tenant->activeModules()->pluck('id'))
            ->ordered()
            ->get()
            ->filter(function ($module) use ($planFeatures) {
                // Simple heuristic: recommend modules that provide features not in the plan
                $moduleFeatures = $module->getFeatureFlags();
                $missingFeatures = array_diff($moduleFeatures, array_keys($planFeatures));

                return !empty($missingFeatures);
            })
            ->values();
    }

    /**
     * Process module renewals (scheduled task)
     */
    public function processModuleRenewals(Carbon $date = null): array
    {
        $date = $date ?? now();
        $results = [
            'renewed' => 0,
            'expired' => 0,
            'errors' => 0,
        ];

        // Get modules expiring today
        $expiringModules = TenantModule::whereDate('expires_at', $date->toDateString())
            ->where('auto_renew', true)
            ->where('status', TenantModule::STATUS_ACTIVE)
            ->get();

        foreach ($expiringModules as $tenantModule) {
            try {
                $this->renewModule($tenantModule);
                $results['renewed']++;
            } catch (\Exception $e) {
                Log::error('Error renewing module', [
                    'tenant_module_id' => $tenantModule->id,
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }

        // Process expired modules
        $expiredModules = TenantModule::where('expires_at', '<', $date)
            ->where('status', TenantModule::STATUS_ACTIVE)
            ->get();

        foreach ($expiredModules as $tenantModule) {
            try {
                $this->expireModule($tenantModule);
                $results['expired']++;
            } catch (\Exception $e) {
                Log::error('Error expiring module', [
                    'tenant_module_id' => $tenantModule->id,
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }

        Log::info('Module renewals processed', [
            'date' => $date->toDateString(),
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Get module usage analytics
     */
    public function getModuleUsageAnalytics(
        ?Tenant $tenant = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = ModuleUsageLog::query();

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalEvents = $query->count();
        $errorEvents = (clone $query)->where('was_error', true)->count();
        $limitEvents = (clone $query)->where('event_type', ModuleUsageLog::EVENT_LIMIT_REACHED)->count();

        $moduleUsage = (clone $query)
            ->select('module_id', \DB::raw('count(*) as usage_count'))
            ->groupBy('module_id')
            ->orderByDesc('usage_count')
            ->with('module:id,name,slug')
            ->get()
            ->keyBy('module_id');

        $eventTypeBreakdown = (clone $query)
            ->select('event_type', \DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'total_events' => $totalEvents,
            'error_events' => $errorEvents,
            'limit_events' => $limitEvents,
            'error_rate' => $totalEvents > 0 ? ($errorEvents / $totalEvents) * 100 : 0,
            'module_usage' => $moduleUsage->toArray(),
            'event_type_breakdown' => $eventTypeBreakdown,
        ];
    }

    /**
     * Register module features for tenant
     */
    private function registerModuleFeatures(Tenant $tenant, Module $module, TenantModule $tenantModule): void
    {
        // This is where you would register any module-specific features
        // for the tenant, such as custom routes, resources, etc.

        $features = $module->feature_flags ?? [];

        foreach ($features as $feature) {
            // Example: Enable feature flag for tenant
            // This could integrate with a feature flag service
            Log::debug('Feature enabled for tenant', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'feature' => $feature,
            ]);
        }
    }

    /**
     * Unregister module features for tenant
     */
    private function unregisterModuleFeatures(Tenant $tenant, Module $module, TenantModule $tenantModule): void
    {
        // This is where you would unregister any module-specific features
        // for the tenant

        $features = $module->feature_flags ?? [];

        foreach ($features as $feature) {
            // Example: Disable feature flag for tenant
            // This could integrate with a feature flag service
            Log::debug('Feature disabled for tenant', [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'feature' => $feature,
            ]);
        }
    }

    /**
     * Validate limits
     */
    private function validateLimits(Module $module, array $limits): array
    {
        $errors = [];
        $availableMetrics = array_keys($module->getLimits());

        foreach ($limits as $metric => $value) {
            if (!in_array($metric, $availableMetrics)) {
                $errors[$metric] = "Métrica no válida para este módulo: {$metric}";
                continue;
            }

            if ($value !== null && !is_numeric($value) && $value !== 'unlimited') {
                $errors[$metric] = "El valor debe ser numérico o 'unlimited'";
            }
        }

        return $errors;
    }

    /**
     * Check usage violations
     */
    private function checkUsageViolations(TenantModule $tenantModule, array $usageData): array
    {
        $violations = [];
        $limits = $tenantModule->getEffectiveLimits();

        foreach ($usageData as $metric => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $limitValue = data_get($limits, $metric);
            if ($limitValue !== null && $limitValue !== 'unlimited' && $value > $limitValue) {
                $violations[] = [
                    'limit' => $metric,
                    'current' => $value,
                    'limit_value' => $limitValue,
                ];
            }
        }

        return $violations;
    }

    /**
     * Renew a module
     */
    private function renewModule(TenantModule $tenantModule): void
    {
        $newExpiryDate = match($tenantModule->billing_cycle) {
            'monthly' => $tenantModule->expires_at->addMonth(),
            'yearly' => $tenantModule->expires_at->addYear(),
            default => $tenantModule->expires_at->addMonth(),
        };

        $tenantModule->renew($newExpiryDate);

        Log::info('Module renewed', [
            'tenant_module_id' => $tenantModule->id,
            'new_expiry' => $newExpiryDate,
        ]);
    }

    /**
     * Expire a module
     */
    private function expireModule(TenantModule $tenantModule): void
    {
        $tenantModule->update([
            'status' => TenantModule::STATUS_EXPIRED,
            'is_active' => false,
        ]);

        // Clear tenant cache
        $this->clearTenantCache($tenantModule->tenant);

        Log::info('Module expired', [
            'tenant_module_id' => $tenantModule->id,
            'tenant_id' => $tenantModule->tenant_id,
        ]);
    }

    /**
     * Clear tenant cache
     */
    private function clearTenantCache(Tenant $tenant): void
    {
        $cacheKeys = [
            "tenant.{$tenant->id}.modules",
            "tenant.{$tenant->id}.features",
            "tenant.{$tenant->id}.permissions",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}