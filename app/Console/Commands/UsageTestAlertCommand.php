<?php

namespace App\Console\Commands;

use App\Services\UsageAlertService;
use Illuminate\Console\Command;
use App\Models\Tenant;

class UsageTestAlertCommand extends Command
{
    protected $signature = 'usage:test-alert
                            {--tenant= : Specific tenant ID to test}
                            {--type=warning : Alert type (warning, overdraft, critical)}';

    protected $description = 'Send test usage alert to verify notification system';

    public function handle(UsageAlertService $alertService): int
    {
        $tenantId = $this->option('tenant');
        $alertType = $this->option('type');

        if (!in_array($alertType, ['warning', 'overdraft', 'critical'])) {
            $this->error('Invalid alert type. Must be: warning, overdraft, or critical');
            return Command::FAILURE;
        }

        if ($tenantId) {
            $this->testTenantAlert($tenantId, $alertType, $alertService);
        } else {
            $this->testAllTenantsAlert($alertType, $alertService);
        }

        return Command::SUCCESS;
    }

    private function testTenantAlert(int $tenantId, string $alertType, UsageAlertService $alertService): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found");
            return;
        }

        $this->info("Sending {$alertType} alert to tenant: {$tenant->name}");

        try {
            $alertService->sendTestAlert($tenantId, $alertType);
            $this->info("✅ Test alert sent successfully to: {$tenant->name}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send test alert to {$tenant->name}: {$e->getMessage()}");
        }
    }

    private function testAllTenantsAlert(string $alertType, UsageAlertService $alertService): void
    {
        $this->info("Sending {$alertType} test alert to all tenants...");

        Tenant::chunk(10, function ($tenants) use ($alertType, $alertService) {
            foreach ($tenants as $tenant) {
                $this->testTenantAlert($tenant->id, $alertType, $alertService);
            }
        });

        $this->info("✅ Test alerts sent to all tenants");
    }
}