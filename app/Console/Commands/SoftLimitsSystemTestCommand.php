<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Models\User;
use App\Services\TenantUsageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SoftLimitsSystemTestCommand extends Command
{
    protected $signature = 'soft-limits:test {--scenario= : Run specific scenario (1,2,3,4,5,business)}';

    protected $description = 'Comprehensive test suite for Soft Limits system';

    private TenantUsageService $usageService;

    private array $results = [];

    public function handle(TenantUsageService $usageService): int
    {
        $this->usageService = $usageService;
        $this->info('🧪 Starting Soft Limits System Test Suite');
        $this->info('=========================================');

        try {
            // Setup test environment
            $this->setupTestEnvironment();

            // Run tests based on option
            $scenario = $this->option('scenario');

            if ($scenario) {
                $this->runSpecificScenario($scenario);
            } else {
                $this->runAllScenarios();
            }

            // Display results
            $this->displayResults();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function setupTestEnvironment(): void
    {
        $this->info('🔧 Setting up test environment...');

        // Clear all caches
        Cache::flush();

        // Create test tenant
        $this->testTenant = Tenant::where('domain', 'test-soft-limits')->first();
        if (! $this->testTenant) {
            $this->testTenant = Tenant::factory()->create([
                'name' => 'Test Tenant - Soft Limits',
                'domain' => 'test-soft-limits',
            ]);
        }

        // Reset or create usage record
        $this->resetUsage();

        $this->info('✅ Test environment ready');
        $this->info("   Tenant ID: {$this->testTenant->id}");
        $this->info("   Tenant Domain: {$this->testTenant->domain}");
    }

    private function resetUsage(): void
    {
        $usage = TenantUsage::where('tenant_id', $this->testTenant->id)
            ->forPeriod(now()->year, now()->month)
            ->first();

        if (! $usage) {
            $usage = TenantUsage::create([
                'tenant_id' => $this->testTenant->id,
                'year' => now()->year,
                'month' => now()->month,
                'sales_count' => 0,
                'products_count' => 0,
                'users_count' => 1,
                'storage_size_mb' => 0,
                'max_sales_per_month' => 100,
                'max_products' => 50,
                'max_users' => 10,
                'max_storage_mb' => 1024,
            ]);
        } else {
            $usage->update([
                'sales_count' => 0,
                'products_count' => 0,
                'users_count' => 1,
                'storage_size_mb' => 0,
            ]);
            $usage->calculatePercentages();
        }

        // Clear Redis counters and cache
        $this->usageService->clearRedisCounters($this->testTenant->id);
        $this->usageService->clearCache($this->testTenant->id);
    }

    private function runAllScenarios(): void
    {
        $this->runScenario1();
        $this->runScenario2();
        $this->runScenario3();
        $this->runScenario4();
        $this->runScenario5();
        $this->runBusinessContinuityTest();
        $this->runPerformanceTest();
        $this->runRedisTest();
    }

    private function runSpecificScenario(string $scenario): void
    {
        switch ($scenario) {
            case '1':
                $this->runScenario1();
                break;
            case '2':
                $this->runScenario2();
                break;
            case '3':
                $this->runScenario3();
                break;
            case '4':
                $this->runScenario4();
                break;
            case '5':
                $this->runScenario5();
                break;
            case 'business':
                $this->runBusinessContinuityTest();
                break;
            default:
                $this->error("Invalid scenario: {$scenario}");

                return;
        }
    }

    private function runScenario1(): void
    {
        $this->info("\n📊 Scenario 1: Normal Operation (0-80%)");
        $this->info('------------------------------------');

        try {
            $this->resetUsage();

            // Set usage to 60% (well within normal zone)
            $this->usageService->incrementUsage($this->testTenant->id, 'products', 30); // 60%
            $this->usageService->incrementUsage($this->testTenant->id, 'users', 5);     // 50%

            $status = $this->usageService->getUsageStatus($this->testTenant->id);

            // Assertions
            $this->assertTrue($status['status'] === 'normal', 'Status should be normal');
            $this->assertTrue(! $status['upgrade_required'], 'Upgrade should not be required');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_product'), 'Should allow product creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_user'), 'Should allow user creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'make_sale'), 'Should allow sales');

            $this->results['scenario1'] = '✅ PASSED';
            $this->info('✅ All assertions passed');

        } catch (\Exception $e) {
            $this->results['scenario1'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Scenario 1 failed: '.$e->getMessage());
        }
    }

    private function runScenario2(): void
    {
        $this->info("\n⚠️  Scenario 2: Warning Zone (80-100%)");
        $this->info('------------------------------------');

        try {
            $this->resetUsage();

            // Set usage to warning zone - simulate by directly updating database for testing
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id, true);
            $this->info("   Debug: Before update - Products: {$usage->products_count}, Users: {$usage->users_count}");

            $usage->update([
                'products_count' => 42, // 84% of 50
                'users_count' => 8,     // 80% of 10 (including initial user)
            ]);
            $usage->calculatePercentages();

            $this->info("   Debug: After update - Products: {$usage->products_count}, Users: {$usage->users_count}");
            $this->info("   Debug: Status: {$usage->status}");

            // Clear cache to get fresh data
            $this->usageService->clearCache($this->testTenant->id);
            $status = $this->usageService->getUsageStatus($this->testTenant->id);

            // Debug output
            $this->info("   Debug: Status: Products {$status['metrics']['products']['current']} / {$status['metrics']['products']['limit']} = {$status['metrics']['products']['percentage']}%");
            $this->info("   Debug: Status: Users {$status['metrics']['users']['current']} / {$status['metrics']['users']['limit']} = {$status['metrics']['users']['percentage']}%");
            $this->info("   Debug: Overall status: {$status['status']}");

            // Assertions
            $this->assertTrue($status['status'] === 'warning', 'Status should be warning, got: '.$status['status']);
            $this->assertTrue($status['metrics']['products']['zone'] === 'warning', 'Products should be in warning zone, got: '.$status['metrics']['products']['zone']);
            $this->assertTrue($status['metrics']['users']['zone'] === 'warning', 'Users should be in warning zone, got: '.$status['metrics']['users']['zone']);
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_product'), 'Should still allow product creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_user'), 'Should still allow user creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'make_sale'), 'Should allow sales');

            $this->results['scenario2'] = '✅ PASSED';
            $this->info('✅ All assertions passed');

        } catch (\Exception $e) {
            $this->results['scenario2'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Scenario 2 failed: '.$e->getMessage());
        }
    }

    private function runScenario3(): void
    {
        $this->info("\n📈 Scenario 3: Overdraft Zone (100-120%)");
        $this->info('---------------------------------------');

        try {
            $this->resetUsage();

            // Set usage to overdraft zone - simulate by directly updating database for testing
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id, true);
            $usage->update([
                'products_count' => 55, // 110% of 50
                'users_count' => 11,    // 110% of 10
            ]);
            $usage->calculatePercentages();

            // Clear cache to get fresh data
            $this->usageService->clearCache($this->testTenant->id);
            $status = $this->usageService->getUsageStatus($this->testTenant->id);
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id);

            // Assertions
            $this->assertTrue($status['status'] === 'overdraft', 'Status should be overdraft');
            $this->assertTrue($status['upgrade_required'], 'Upgrade should be required');
            $this->assertTrue($usage->upgrade_required_next_cycle, 'Upgrade flag should be set');
            $this->assertTrue($status['metrics']['products']['zone'] === 'overdraft', 'Products should be in overdraft zone');
            $this->assertTrue($status['metrics']['users']['zone'] === 'overdraft', 'Users should be in overdraft zone');

            // ** CRITICAL: Sales must always be allowed **
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'make_sale'), '🚨 SALES MUST ALWAYS BE ALLOWED - Business continuity');

            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_product'), 'Should still allow product creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_user'), 'Should still allow user creation');

            $this->results['scenario3'] = '✅ PASSED';
            $this->info('✅ All assertions passed');

        } catch (\Exception $e) {
            $this->results['scenario3'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Scenario 3 failed: '.$e->getMessage());
        }
    }

    private function runScenario4(): void
    {
        $this->info("\n🚨 Scenario 4: Hard Stop (>120%)");
        $this->info('---------------------------------');

        try {
            $this->resetUsage();

            // Set usage to critical zone - simulate by directly updating database for testing
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id, true);
            $usage->update([
                'products_count' => 65, // 130% of 50
                'users_count' => 13,    // 130% of 10
            ]);
            $usage->calculatePercentages();

            // Clear cache to get fresh data
            $this->usageService->clearCache($this->testTenant->id);
            $status = $this->usageService->getUsageStatus($this->testTenant->id);

            // Assertions
            $this->assertTrue($status['status'] === 'critical', 'Status should be critical');
            $this->assertTrue($status['upgrade_required'], 'Upgrade should be required');
            $this->assertTrue($status['metrics']['products']['zone'] === 'critical', 'Products should be in critical zone');
            $this->assertTrue($status['metrics']['users']['zone'] === 'critical', 'Users should be in critical zone');

            // ** CRITICAL BUSINESS CONTINUITY: Sales must NEVER be blocked **
            $this->assertTrue(
                $this->usageService->canPerformAction($this->testTenant->id, 'make_sale'),
                '🚨🚨 SALES MUST NEVER BE BLOCKED - Business continuity requirement'
            );

            // New creations should be blocked
            $this->assertTrue(
                ! $this->usageService->canPerformAction($this->testTenant->id, 'create_product'),
                'Product creation should be blocked in critical zone'
            );
            $this->assertTrue(
                ! $this->usageService->canPerformAction($this->testTenant->id, 'create_user'),
                'User creation should be blocked in critical zone'
            );

            $this->results['scenario4'] = '✅ PASSED';
            $this->info('✅ All assertions passed - Business continuity maintained!');

        } catch (\Exception $e) {
            $this->results['scenario4'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Scenario 4 failed: '.$e->getMessage());
        }
    }

    private function runScenario5(): void
    {
        $this->info("\n🔄 Scenario 5: Recovery Testing");
        $this->info('-------------------------------');

        try {
            $this->resetUsage();

            // Get to critical zone first - simulate by directly updating database for testing
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id, true);
            $usage->update([
                'products_count' => 65, // 130% of 50
            ]);
            $usage->calculatePercentages();

            $status = $this->usageService->getUsageStatus($this->testTenant->id);
            $this->assertTrue($status['status'] === 'critical', 'Should start in critical zone');

            // Simulate plan upgrade (reset usage and increase limits)
            $usage = $this->usageService->getCurrentUsage($this->testTenant->id);
            $usage->update([
                'products_count' => 10, // Reset to 20% of new limit
                'users_count' => 2,     // Reset to 20% of new limit
                'max_products' => 100,  // Increased limits
                'max_users' => 20,      // Increased limits
            ]);
            $usage->calculatePercentages();

            // Clear caches to simulate fresh calculation
            $this->usageService->clearCache($this->testTenant->id);

            // Verify recovery
            $newStatus = $this->usageService->getUsageStatus($this->testTenant->id);
            $this->assertTrue($newStatus['status'] === 'normal', 'Should return to normal');
            $this->assertTrue(! $newStatus['upgrade_required'], 'Upgrade should not be required after plan upgrade');

            // All actions should be allowed
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_product'), 'Should allow product creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'create_user'), 'Should allow user creation');
            $this->assertTrue($this->usageService->canPerformAction($this->testTenant->id, 'make_sale'), 'Should allow sales');

            $this->results['scenario5'] = '✅ PASSED';
            $this->info('✅ Recovery test passed');

        } catch (\Exception $e) {
            $this->results['scenario5'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Scenario 5 failed: '.$e->getMessage());
        }
    }

    private function runBusinessContinuityTest(): void
    {
        $this->info("\n💰 Business Continuity Test - Sales Never Fail");
        $this->info('==============================================');

        try {
            $zones = [
                'normal' => ['products' => 25, 'users' => 5],     // 50% usage
                'warning' => ['products' => 42, 'users' => 8],   // 84% usage
                'overdraft' => ['products' => 55, 'users' => 11], // 110% usage
                'critical' => ['products' => 65, 'users' => 13],  // 130% usage
            ];

            foreach ($zones as $zoneName => $usage) {
                $this->resetUsage();

                // Set usage directly in database for testing
                $usageRecord = $this->usageService->getCurrentUsage($this->testTenant->id, true);
                $usageRecord->update([
                    'products_count' => $usage['products'],
                    'users_count' => $usage['users'],
                ]);
                $usageRecord->calculatePercentages();

                $status = $this->usageService->getUsageStatus($this->testTenant->id);
                $this->assertTrue($status['status'] === $zoneName, "Should be in {$zoneName} zone");

                $this->assertTrue(
                    $this->usageService->canPerformAction($this->testTenant->id, 'make_sale'),
                    "🚨 Sales must be allowed in {$zoneName} zone - Business continuity violated"
                );

                $this->info("   ✅ {$zoneName} zone: Sales allowed");
            }

            $this->results['business_continuity'] = '✅ PASSED';
            $this->info('✅ Business continuity maintained across all zones');

        } catch (\Exception $e) {
            $this->results['business_continuity'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Business continuity test failed: '.$e->getMessage());
        }
    }

    private function runPerformanceTest(): void
    {
        $this->info("\n⚡ Performance Test - Usage Tracking Speed");
        $this->info('=========================================');

        try {
            $this->resetUsage();
            $iterations = 100;

            $startTime = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $this->usageService->incrementUsage($this->testTenant->id, 'sales', 1);
            }
            $endTime = microtime(true);

            $totalTime = $endTime - $startTime;
            $avgTime = ($totalTime / $iterations) * 1000; // Convert to milliseconds

            $this->info("   Total time for {$iterations} increments: ".number_format($totalTime, 4).'s');
            $this->info('   Average time per increment: '.number_format($avgTime, 2).'ms');

            $this->assertTrue($avgTime < 5, "Usage increments should be fast (< 5ms avg, got {$avgTime}ms)");

            // Verify tracking accuracy
            $redisCounters = $this->usageService->getAllRedisCounters($this->testTenant->id);
            $this->assertTrue($redisCounters['sales'] === $iterations, "Should track all {$iterations} increments");

            $this->results['performance'] = '✅ PASSED';
            $this->info('✅ Performance test passed - Redis tracking is fast and accurate');

        } catch (\Exception $e) {
            $this->results['performance'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Performance test failed: '.$e->getMessage());
        }
    }

    private function runRedisTest(): void
    {
        $this->info("\n🔴 Redis Real-time Tracking Test");
        $this->info('===============================');

        try {
            $this->resetUsage();

            // Test Redis increment
            $this->usageService->incrementUsage($this->testTenant->id, 'products', 5);
            $this->usageService->incrementUsage($this->testTenant->id, 'products', 3);

            $redisCounters = $this->usageService->getAllRedisCounters($this->testTenant->id);
            $this->assertTrue($redisCounters['products'] === 8, 'Redis should track 8 products');
            $this->assertTrue($this->usageService->getRedisCounter($this->testTenant->id, 'products') === 8, 'Direct Redis counter should be 8');

            // Test caching performance
            $startTime = microtime(true);
            $firstCall = $this->usageService->getUsageStatus($this->testTenant->id);
            $firstCallTime = microtime(true) - $startTime;

            $startTime = microtime(true);
            $secondCall = $this->usageService->getUsageStatus($this->testTenant->id);
            $secondCallTime = microtime(true) - $startTime;

            $this->assertTrue($secondCallTime < $firstCallTime * 0.5, 'Cached call should be significantly faster');

            $this->results['redis'] = '✅ PASSED';
            $this->info('✅ Redis real-time tracking works correctly');

        } catch (\Exception $e) {
            $this->results['redis'] = '❌ FAILED: '.$e->getMessage();
            $this->error('❌ Redis test failed: '.$e->getMessage());
        }
    }

    private function displayResults(): void
    {
        $this->info("\n📋 TEST RESULTS SUMMARY");
        $this->info('=======================');

        foreach ($this->results as $test => $result) {
            $this->info($result);
        }

        $passed = count(array_filter($this->results, fn ($r) => str_starts_with($r, '✅')));
        $total = count($this->results);

        $this->info("\n📊 Overall: {$passed}/{$total} tests passed");

        if ($passed === $total) {
            $this->info('🎉 ALL TESTS PASSED - Soft Limits System is ready for production!');
        } else {
            $this->error('⚠️  Some tests failed - Please review the issues above');
        }
    }

    private function assertTrue($condition, $message): void
    {
        if (! $condition) {
            throw new \Exception($message);
        }
    }
}
