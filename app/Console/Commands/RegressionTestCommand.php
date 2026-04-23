<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegressionTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emporio:regression-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run manual SuperAdmin regression test';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $adminUrl = 'http://nginx';
        $adminEmail = 'admin@emporiodigital.test';
        $sessionCookie = null;

        $this->info('🚀 MANUAL SUPERADMIN REGRESSION TEST');
        $this->info('====================================');
        $this->info("Target: {$adminUrl}");
        $this->newLine();

        // Test results tracking
        $tests = [
            'passed' => 0,
            'failed' => 0,
            'details' => [],
        ];

        /**
         * Record test result
         */
        $recordTest = function (&$tests, $name, $passed, $details = '', $statusCode = null) {
            if ($passed) {
                $tests['passed']++;
                $this->info("✅ {$name}: PASSED");
            } else {
                $tests['failed']++;
                $this->error("❌ {$name}: FAILED");
            }

            if ($details) {
                $this->line("   - {$details}");
            }
            if ($statusCode) {
                $this->line("   (HTTP {$statusCode})");
            }

            $tests['details'][] = [
                'name' => $name,
                'passed' => $passed,
                'details' => $details,
                'status_code' => $statusCode,
            ];
        };

        $this->info('🔐 Step 1: Testing SuperAdmin Login');

        try {
            // Test 1: Login page accessibility
            $loginResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->get("{$adminUrl}/admin/login");

            $loginPageAccessible = $loginResponse->status() === 200 &&
                                   strpos($loginResponse->body(), 'Iniciar Sesión') !== false;

            $recordTest($tests, 'Login Page Access', $loginPageAccessible,
                $loginPageAccessible ? 'Page loads correctly' : 'Page not accessible',
                $loginResponse->status());

            if (! $loginPageAccessible) {
                $this->newLine();
                $this->error('❌ CRITICAL: Cannot access login page. Aborting regression test.');

                return 1;
            }

            // Test 2: Extract CSRF token and perform login
            $this->newLine();
            $this->info('🔐 Step 2: Extracting CSRF token and logging in...');

            $csrfToken = null;
            $body = $loginResponse->body();

            // Try multiple CSRF token extraction patterns
            $patterns = [
                '/<meta\s+name="csrf-token"\s+content="([^"]+)"/',
                '/<input\s+type="hidden"\s+name="_token"\s+value="([^"]+)"[^>]*>/',
                '/name="_token"\s+value="([^"]+)"/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $body, $matches)) {
                    $csrfToken = $matches[1];
                    $this->info('✅ CSRF Token extracted successfully');
                    break;
                }
            }

            if (! $csrfToken) {
                $this->error('❌ Could not extract CSRF token');
                $this->line('Response preview: '.substr($body, 0, 500).'...');
                $recordTest($tests, 'CSRF Token Extraction', false, 'No CSRF token found');

                return 1;
            }

            // Perform login
            $loginData = [
                'email' => $adminEmail,
                'password' => 'password',
                '_token' => $csrfToken,
            ];

            $sessionCookies = [];
            $loginPostResponse = Http::asForm()->withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->post("{$adminUrl}/admin/login", $loginData);

            // Get session cookies from the login response
            $setCookieHeaders = $loginPostResponse->headers();
            $cookies = [];

            if (isset($setCookieHeaders['Set-Cookie'])) {
                $cookieHeaders = $setCookieHeaders['Set-Cookie'];
                if (! is_array($cookieHeaders)) {
                    $cookieHeaders = [$cookieHeaders];
                }

                foreach ($cookieHeaders as $cookie) {
                    if (is_string($cookie) && strpos($cookie, 'emporio_digital_session') !== false) {
                        $sessionCookie = $cookie;
                        break;
                    }
                }
            }

            if ($sessionCookie) {
                $this->info('✅ Session cookie extracted');
            } else {
                $this->error('❌ Could not extract session cookie');
                $recordTest($tests, 'Session Cookie Extraction', false, 'No session cookie found');

                return 1;
            }

            // Test 3: Verify login success
            $dashboardResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin");

            // Debug: Check if we got redirected back to login (failed login)
            $body = $dashboardResponse->body();
            $isLoginAgain = strpos($body, 'Iniciar Sesión') !== false;
            $isDashboard = strpos($body, 'Panel de Administración') !== false;

            $loginSuccess = $dashboardResponse->status() === 200 && $isDashboard && ! $isLoginAgain;

            if (! $loginSuccess) {
                // Debug information
                $this->line('Debug - Response body preview: '.substr($body, 0, 300));
                $this->line('Debug - Found "Iniciar Sesión": '.($isLoginAgain ? 'Yes' : 'No'));
                $this->line('Debug - Found "Panel de Administración": '.($isDashboard ? 'Yes' : 'No'));
                $this->line('Debug - Status Code: '.$dashboardResponse->status());
                $this->line('Debug - Cookie used: '.substr($sessionCookie, 0, 100).'...');
            }

            $recordTest($tests, 'SuperAdmin Login', $loginSuccess,
                $loginSuccess ? 'Login successful' : 'Login failed - redirected back to login page',
                $dashboardResponse->status());

            if (! $loginSuccess) {
                $this->newLine();
                $this->error('❌ CRITICAL: Login failed. Aborting regression test.');

                return 1;
            }

            $this->newLine();
            $this->info('📊 Step 3: Testing Dashboard Health');

            // Test 4: Dashboard widgets load
            $widgetsLoaded = strpos($dashboardResponse->body(), 'filament-widget') !== false;
            $recordTest($tests, 'Dashboard Widgets', $widgetsLoaded,
                $widgetsLoaded ? 'Widgets present' : 'Widgets missing');

            $this->newLine();
            $this->info('🏢 Step 4: Testing Tenant Management');

            // Test 5: Tenants list
            $tenantsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/tenants");

            $tenantsAccessible = $tenantsResponse->status() === 200;
            $recordTest($tests, 'Tenants List', $tenantsAccessible,
                $tenantsAccessible ? 'Tenants list loads' : 'Tenants list failed',
                $tenantsResponse->status());

            // Test 6: Archived Tenants (CRITICAL 404 FIX TEST)
            $archivedTenantsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/archived-tenants");

            $archivedTenantsAccessible = $archivedTenantsResponse->status() === 200;

            // Check for 404 indicators
            $is404Error = $archivedTenantsResponse->status() === 404 ||
                          strpos($archivedTenantsResponse->body(), '404') !== false ||
                          strpos($archivedTenantsResponse->body(), 'Not Found') !== false;

            $recordTest($tests, 'Archived Tenants Access (CRITICAL 404 FIX)',
                $archivedTenantsAccessible && ! $is404Error,
                $archivedTenantsAccessible ? 'Accessible' : 'Not accessible or 404 error',
                $archivedTenantsResponse->status());

            $this->newLine();
            $this->info('💳 Step 5: Testing Billing Module');

            // Test 7: Payment Proofs
            $paymentProofsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/payment-proofs");

            $paymentProofsAccessible = $paymentProofsResponse->status() === 200;
            $recordTest($tests, 'Payment Proofs List', $paymentProofsAccessible,
                $paymentProofsAccessible ? 'Payment proofs loads' : 'Payment proofs failed',
                $paymentProofsResponse->status());

            // Test 8: Invoices
            $invoicesResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/invoices");

            $invoicesAccessible = $invoicesResponse->status() === 200;
            $recordTest($tests, 'Invoices List', $invoicesAccessible,
                $invoicesAccessible ? 'Invoices loads' : 'Invoices failed',
                $invoicesResponse->status());

            $this->newLine();
            $this->info('🏥 Step 6: Testing System Health Pages');

            // Test 9: Error Logs
            $errorLogsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/error-logs");

            $errorLogsAccessible = $errorLogsResponse->status() === 200;
            $recordTest($tests, 'Error Logs', $errorLogsAccessible,
                $errorLogsAccessible ? 'Error logs loads' : 'Error logs failed',
                $errorLogsResponse->status());

            // Test 10: Backups
            $backupsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/backups");

            $backupsAccessible = $backupsResponse->status() === 200;
            $recordTest($tests, 'Backups Page', $backupsAccessible,
                $backupsAccessible ? 'Backups page loads' : 'Backups page failed',
                $backupsResponse->status());

            // Test 11: Support Tickets
            $supportTicketsResponse = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders([
                'Cookie' => $sessionCookie,
            ])->get("{$adminUrl}/admin/support-tickets");

            $supportTicketsAccessible = $supportTicketsResponse->status() === 200;
            $recordTest($tests, 'Support Tickets', $supportTicketsAccessible,
                $supportTicketsAccessible ? 'Support tickets loads' : 'Support tickets failed',
                $supportTicketsResponse->status());

            $this->newLine();
            $this->info('🎯 REGRESSION TEST SUMMARY');
            $this->info('===========================');
            $this->line("Tests Passed: {$tests['passed']}");
            $this->line("Tests Failed: {$tests['failed']}");
            $this->line('Total Tests: '.($tests['passed'] + $tests['failed']));
            $this->newLine();

            // Critical issues check
            $criticalIssues = [];
            foreach ($tests['details'] as $test) {
                if (! $test['passed'] && strpos($test['name'], 'CRITICAL') !== false) {
                    $criticalIssues[] = $test;
                }
            }

            if (empty($criticalIssues)) {
                $this->info('🟢 HEALTH STATUS: SYSTEM NOMINAL');
                $this->info('✅ All critical systems are operational');
                $this->info('✅ No blocking issues detected');
                $this->newLine();
                $this->info('🎉 REGRESSION TEST PASSED: System is ready for production');

                return 0;
            } else {
                $this->error('🔴 HEALTH STATUS: CRITICAL ISSUES DETECTED');
                $this->error('❌ The following critical issues need immediate attention:');
                foreach ($criticalIssues as $issue) {
                    $this->error("   - {$issue['name']}: {$issue['details']} (HTTP {$issue['status_code']})");
                }
                $this->newLine();
                $this->error('🚨 REGRESSION TEST FAILED: System requires immediate fixes');

                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ REGRESSION TEST ERROR: '.$e->getMessage());
            $this->error('Location: '.$e->getFile().':'.$e->getLine());

            return 1;
        }
    }
}
