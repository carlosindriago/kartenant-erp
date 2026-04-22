<?php

namespace App\Console\Commands;

use App\Services\ErrorMonitoringService;
use Illuminate\Console\Command;
use RuntimeException;

class TestErrorMonitoring extends Command
{
    protected $signature = 'monitoring:test {--type=critical : Type of test (critical, warning, database)}';

    protected $description = 'Test error monitoring and Slack notifications';

    public function handle(ErrorMonitoringService $errorMonitoring): int
    {
        $type = $this->option('type');

        $this->info('🧪 Testing Error Monitoring System...');
        $this->newLine();

        match($type) {
            'critical' => $this->testCriticalError($errorMonitoring),
            'warning' => $this->testWarning($errorMonitoring),
            'database' => $this->testDatabaseError($errorMonitoring),
            default => $this->testCriticalError($errorMonitoring),
        };

        $this->newLine();
        $this->info('✅ Test completed! Check your Slack channel for the alert.');

        return self::SUCCESS;
    }

    protected function testCriticalError(ErrorMonitoringService $errorMonitoring): void
    {
        $this->warn('Generating CRITICAL error test...');

        $exception = new RuntimeException(
            'This is a TEST critical error from Kartenant monitoring system. ' .
            'If you see this in Slack, your error monitoring is working correctly!'
        );

        $errorMonitoring->sendCriticalErrorToSlack($exception, [
            'url' => 'https://kartenant.test/test',
            'method' => 'CLI',
            'ip' => '127.0.0.1',
            'user_agent' => 'Artisan Command',
            'test' => true,
        ]);

        $this->line('📤 Critical error test sent to Slack');
    }

    protected function testWarning(ErrorMonitoringService $errorMonitoring): void
    {
        $this->warn('Generating WARNING error test...');

        $exception = new \Exception(
            'This is a TEST warning from Kartenant. This is a non-critical issue.'
        );

        $errorMonitoring->sendCriticalErrorToSlack($exception, [
            'url' => 'https://kartenant.test/test-warning',
            'method' => 'CLI',
            'ip' => '127.0.0.1',
            'user_agent' => 'Artisan Command',
            'test' => true,
        ]);

        $this->line('📤 Warning test sent to Slack');
    }

    protected function testDatabaseError(ErrorMonitoringService $errorMonitoring): void
    {
        $this->warn('Generating DATABASE error test...');

        $exception = new \Illuminate\Database\QueryException(
            'landlord',
            'SELECT * FROM test_table',
            [],
            new \PDOException('SQLSTATE[42S02]: Base table or view not found: 1146 Table \'test_table\' doesn\'t exist')
        );

        $errorMonitoring->sendCriticalErrorToSlack($exception, [
            'url' => 'https://kartenant.test/test-database',
            'method' => 'CLI',
            'ip' => '127.0.0.1',
            'user_agent' => 'Artisan Command',
            'test' => true,
        ]);

        $this->line('📤 Database error test sent to Slack');
    }
}
