<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantUsageService;
use Illuminate\Console\Command;

class UsageResetCommand extends Command
{
    protected $signature = 'usage:reset-monthly {--tenant= : Specific tenant ID to reset}';

    protected $description = 'Reset monthly usage counters for all tenants or specific tenant';

    public function handle(TenantUsageService $usageService): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $this->resetTenantUsage($tenantId, $usageService);
        } else {
            $this->resetAllTenantUsage($usageService);
        }

        return Command::SUCCESS;
    }

    private function resetTenantUsage(int $tenantId, TenantUsageService $usageService): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found");

            return;
        }

        $this->info("Resetting usage for tenant: {$tenant->name}");

        try {
            // Clear Redis counters
            $usageService->clearRedisCounters($tenantId);

            // Clear usage cache
            $usageService->clearCache($tenantId);

            // Create new usage record for current month
            $usageService->getCurrentUsage($tenantId, true);

            $this->info("✅ Usage reset completed for tenant: {$tenant->name}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to reset usage for tenant {$tenant->name}: {$e->getMessage()}");
        }
    }

    private function resetAllTenantUsage(TenantUsageService $usageService): void
    {
        $this->info('Resetting monthly usage counters for all tenants...');

        Tenant::chunk(100, function ($tenants) use ($usageService) {
            foreach ($tenants as $tenant) {
                $this->resetTenantUsage($tenant->id, $usageService);
            }
        });

        $this->info('✅ Monthly usage reset completed for all tenants');
    }
}
