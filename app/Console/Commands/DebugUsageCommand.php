<?php

namespace App\Console\Commands;

use App\Services\TenantUsageService;
use Illuminate\Console\Command;

class DebugUsageCommand extends Command
{
    protected $signature = 'debug:usage {tenantId}';

    protected $description = 'Debug usage calculations for a specific tenant';

    public function handle(TenantUsageService $usageService): int
    {
        $tenantId = $this->argument('tenantId');

        $this->info("🔍 Debugging usage for tenant {$tenantId}");
        $this->info('=================================');

        try {
            // Get usage record
            $usage = $usageService->getCurrentUsage($tenantId, true);
            $this->info('📊 Current Usage Record:');
            $this->info("  Sales: {$usage->sales_count} / {$usage->max_sales_per_month} ({$usage->sales_percentage}%)");
            $this->info("  Products: {$usage->products_count} / {$usage->max_products} ({$usage->products_percentage}%)");
            $this->info("  Users: {$usage->users_count} / {$usage->max_users} ({$usage->users_percentage}%)");
            $this->info("  Storage: {$usage->storage_size_mb} / {$usage->max_storage_mb} ({$usage->storage_percentage}%)");
            $this->info("  Status: {$usage->status}");

            // Get Redis counters
            $redisCounters = $usageService->getAllRedisCounters($tenantId);
            $this->info("\n🔴 Redis Counters:");
            foreach ($redisCounters as $metric => $value) {
                $this->info("  {$metric}: {$value}");
            }

            // Test zone calculations
            $this->info("\n📈 Zone Calculations:");
            foreach (['sales', 'products', 'users', 'storage'] as $metric) {
                $zone = $usage->getZoneForMetric($metric);
                $percentage = $usage->getPercentage($metric);
                $this->info("  {$metric}: {$percentage}% = {$zone} zone");
            }

            // Test incrementing
            $this->info("\n🧪 Testing Usage Increment:");
            $this->usageService = $usageService;

            // Reset and test users
            $this->resetUsage($usageService, $tenantId);
            $usageService->incrementUsage($tenantId, 'users', 8); // Should be 9 total (80% of 10 = 8 + 1 initial = 9)

            $usageAfter = $usageService->getCurrentUsage($tenantId, true);
            $this->info('  After adding 8 users:');
            $this->info("    Users count: {$usageAfter->users_count}");
            $this->info("    Users percentage: {$usageAfter->users_percentage}%");
            $this->info('    Users zone: '.$usageAfter->getZoneForMetric('users'));
            $this->info('    Expected zone: warning (80%)');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Debug failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function resetUsage(TenantUsageService $usageService, int $tenantId): void
    {
        $usage = $usageService->getCurrentUsage($tenantId, true);
        $usage->update([
            'sales_count' => 0,
            'products_count' => 0,
            'users_count' => 1, // Keep 1 initial user
            'storage_size_mb' => 0,
        ]);
        $usage->calculatePercentages();

        $usageService->clearRedisCounters($tenantId);
        $usageService->clearCache($tenantId);
    }
}
