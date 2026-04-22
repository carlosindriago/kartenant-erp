<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Services\ModuleService;
use App\Services\ModuleCacheService;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'modules:{action}
                            {--tenant= : Tenant ID or domain (for specific actions)}
                            {--module= : Module ID or slug (for specific actions)}
                            {--force : Force action without confirmation}
                            {--dry-run : Show what would be done without executing}';

    /**
     * The console command description.
     */
    protected $description = 'Manage modules and module installations for tenants';

    public function __construct(
        private ModuleService $moduleService,
        private ModuleCacheService $moduleCacheService,
        private BillingService $billingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match($action) {
            'install' => $this->installModule(),
            'uninstall' => $this->uninstallModule(),
            'list' => $this->listModules(),
            'renew' => $this->renewModules(),
            'cache-clear' => $this->clearCache(),
            'cache-warmup' => $this->warmupCache(),
            'billing-process' => $this->processBilling(),
            'stats' => $this->showStats(),
            'check-limits' => $this->checkLimits(),
            'cleanup' => $this->cleanup(),
            default => $this->error("Unknown action: {$action}") && self::FAILURE,
        };
    }

    /**
     * Install a module for a tenant
     */
    private function installModule(): int
    {
        $tenantIdentifier = $this->option('tenant');
        $moduleIdentifier = $this->option('module');

        if (!$tenantIdentifier || !$moduleIdentifier) {
            $this->error('Both --tenant and --module options are required for install action');
            return self::FAILURE;
        }

        $tenant = $this->getTenant($tenantIdentifier);
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantIdentifier}");
            return self::FAILURE;
        }

        $module = $this->getModule($moduleIdentifier);
        if (!$module) {
            $this->error("Module not found: {$moduleIdentifier}");
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] Would install module '{$module->name}' for tenant '{$tenant->name}'");
            return self::SUCCESS;
        }

        try {
            $tenantModule = $this->moduleService->installModule($tenant, $module, [
                'billing_cycle' => $module->billing_cycle,
                'auto_renew' => $module->auto_renew,
            ]);

            // Generate setup fee invoice if applicable
            if ($module->hasSetupFee()) {
                $this->billingService->generateModuleSetupFeeInvoice($tenant, $module, $tenantModule);
                $this->info("Setup fee invoice generated for module '{$module->name}'");
            }

            $this->info("Module '{$module->name}' installed successfully for tenant '{$tenant->name}'");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to install module: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Uninstall a module from a tenant
     */
    private function uninstallModule(): int
    {
        $tenantIdentifier = $this->option('tenant');
        $moduleIdentifier = $this->option('module');

        if (!$tenantIdentifier || !$moduleIdentifier) {
            $this->error('Both --tenant and --module options are required for uninstall action');
            return self::FAILURE;
        }

        $tenant = $this->getTenant($tenantIdentifier);
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantIdentifier}");
            return self::FAILURE;
        }

        $module = $this->getModule($moduleIdentifier);
        if (!$module) {
            $this->error("Module not found: {$moduleIdentifier}");
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] Would uninstall module '{$module->name}' from tenant '{$tenant->name}'");
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Are you sure you want to uninstall '{$module->name}' from '{$tenant->name}'?")) {
            $this->info('Operation cancelled');
            return self::SUCCESS;
        }

        try {
            $success = $this->moduleService->uninstallModule($tenant, $module, 'Uninstalled via CLI');

            if ($success) {
                $this->info("Module '{$module->name}' uninstalled successfully from tenant '{$tenant->name}'");
                return self::SUCCESS;
            } else {
                $this->error("Failed to uninstall module");
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Failed to uninstall module: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * List modules
     */
    private function listModules(): int
    {
        $tenantIdentifier = $this->option('tenant');

        if ($tenantIdentifier) {
            // List modules for specific tenant
            $tenant = $this->getTenant($tenantIdentifier);
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantIdentifier}");
                return self::FAILURE;
            }

            $this->info("Modules for tenant: {$tenant->name}");
            $this->info(str_repeat('-', 50));

            $activeModules = $tenant->activeModules;
            $availableModules = Module::active()
                ->visible()
                ->whereNotIn('id', $activeModules->pluck('id'))
                ->get();

            if ($activeModules->isNotEmpty()) {
                $this->info("\n🟢 Active Modules:");
                foreach ($activeModules as $module) {
                    $tenantModule = $tenant->getTenantModule($module->slug);
                    $monthlyCost = $tenantModule ? $tenantModule->getMonthlyCost() : 0;
                    $this->line("  • {$module->name} ({$module->slug}) - \${$monthlyCost}/month");
                }
            }

            if ($availableModules->isNotEmpty()) {
                $this->info("\n🔵 Available Modules:");
                foreach ($availableModules as $module) {
                    $this->line("  • {$module->name} ({$module->slug}) - \${$module->getPrice()}/month");
                }
            }

        } else {
            // List all modules
            $this->info("All Available Modules:");
            $this->info(str_repeat('-', 50));

            $modules = Module::active()->ordered()->get();

            foreach ($modules as $module) {
                $installations = $module->activeTenants()->count();
                $status = $module->is_visible ? '🟢 Visible' : '🔴 Hidden';
                $featured = $module->is_featured ? ' ⭐' : '';
                $this->line("  • {$module->name} ({$module->slug}) - \${$module->getPrice()}/month [{$installations} installations] {$status}{$featured}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Process module renewals
     */
    private function renewModules(): int
    {
        $this->info('Processing module renewals...');

        $results = $this->moduleService->processModuleRenewals();

        $this->info("✅ Renewed: {$results['renewed']} modules");
        $this->info("⚠️  Expired: {$results['expired']} modules");
        $this->info("❌ Errors: {$results['errors']} modules");

        return $results['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Clear module cache
     */
    private function clearCache(): int
    {
        $tenantIdentifier = $this->option('tenant');
        $moduleIdentifier = $this->option('module');

        if ($tenantIdentifier) {
            $tenant = $this->getTenant($tenantIdentifier);
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantIdentifier}");
                return self::FAILURE;
            }

            $this->moduleCacheService->clearTenantCache($tenant);
            $this->info("Cache cleared for tenant: {$tenant->name}");

        } elseif ($moduleIdentifier) {
            $module = $this->getModule($moduleIdentifier);
            if (!$module) {
                $this->error("Module not found: {$moduleIdentifier}");
                return self::FAILURE;
            }

            $this->moduleCacheService->clearModuleCache($module);
            $this->info("Cache cleared for module: {$module->name}");

        } else {
            // Clear all module cache
            Module::active()->chunk(50, function ($modules) {
                foreach ($modules as $module) {
                    $this->moduleCacheService->clearModuleCache($module);
                }
            });

            Tenant::active()->chunk(50, function ($tenants) {
                foreach ($tenants as $tenant) {
                    $this->moduleCacheService->clearTenantCache($tenant);
                }
            });

            $this->info("All module cache cleared");
        }

        return self::SUCCESS;
    }

    /**
     * Warm up module cache
     */
    private function warmupCache(): int
    {
        $this->info('Warming up module cache...');

        $this->moduleCacheService->warmupCacheForAllTenants();

        $this->info('✅ Module cache warmed up successfully');
        return self::SUCCESS;
    }

    /**
     * Process module billing
     */
    private function processBilling(): int
    {
        $this->info('Processing module billing...');

        $results = $this->billingService->generateMonthlyInvoices();

        $this->info("✅ Subscription invoices: {$results['subscription_invoices']}");
        $this->info("✅ Module invoices: {$results['module_invoices']}");
        $this->info("❌ Errors: {$results['errors']}");
        $this->info("💰 Total amount: \${$results['total_amount']}");

        return $results['errors'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Show module statistics
     */
    private function showStats(): int
    {
        $stats = $this->moduleCacheService->getCacheStats();

        $this->info('📊 Module System Statistics');
        $this->info(str_repeat('-', 30));
        $this->line("📦 Total Modules: {$stats['module_count']}");
        $this->line("🏢 Total Tenants: {$stats['tenant_count']}");
        $this->line("🔗 Active Assignments: {$stats['active_assignments']}");
        $this->line("🗄️  Cache Keys: {$stats['cache_keys']}");

        // Additional statistics
        $totalRevenue = 0;
        $expiringSoon = 0;

        Tenant::active()->chunk(50, function ($tenants) use (&$totalRevenue, &$expiringSoon) {
            foreach ($tenants as $tenant) {
                $totalRevenue += $tenant->getModulesMonthlyCost();
                $expiringSoon += $tenant->getModulesExpiringSoon(7)->count();
            }
        });

        $this->line("💰 Monthly Revenue: \${$totalRevenue}");
        $this->line("⏰ Expiring Soon (7 days): {$expiringSoon}");

        return self::SUCCESS;
    }

    /**
     * Check module limits
     */
    private function checkLimits(): int
    {
        $this->info('Checking module limits...');
        $violationsFound = false;

        Tenant::active()->chunk(50, function ($tenants) use (&$violationsFound) {
            foreach ($tenants as $tenant) {
                $violations = $tenant->checkModuleLimits();

                if (!empty($violations)) {
                    $violationsFound = true;
                    $this->warn("\n⚠️  Tenant: {$tenant->name}");
                    foreach ($violations as $violation) {
                        $this->line("   • {$violation['module']} - {$violation['limit']}: {$violation['current']}/{$violation['max']}");
                    }
                }
            }
        });

        if (!$violationsFound) {
            $this->info("✅ No limit violations found");
        }

        return self::SUCCESS;
    }

    /**
     * Cleanup expired modules and optimize cache
     */
    private function cleanup(): int
    {
        $this->info('Cleaning up module system...');

        // Expire modules that should be expired
        $expiredCount = TenantModule::where('expires_at', '<', now())
            ->where('status', '!=', TenantModule::STATUS_EXPIRED)
            ->update(['status' => TenantModule::STATUS_EXPIRED, 'is_active' => false]);

        if ($expiredCount > 0) {
            $this->info("🗑️  Expired {$expiredCount} modules");
        }

        // Optimize cache
        $this->moduleCacheService->optimizeCache();
        $this->info("🔧 Cache optimized");

        $this->info("✅ Cleanup completed");
        return self::SUCCESS;
    }

    /**
     * Get tenant by ID or domain
     */
    private function getTenant(string $identifier): ?Tenant
    {
        if (is_numeric($identifier)) {
            return Tenant::find($identifier);
        }

        return Tenant::where('domain', $identifier)->first();
    }

    /**
     * Get module by ID or slug
     */
    private function getModule(string $identifier): ?Module
    {
        if (is_numeric($identifier)) {
            return Module::find($identifier);
        }

        return Module::where('slug', $identifier)->first();
    }
}