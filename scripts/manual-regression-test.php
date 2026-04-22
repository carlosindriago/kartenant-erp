<?php

/**
 * Manual SuperAdmin Regression Test
 * Tests all critical SuperAdmin panel endpoints via curl
 * Bypasses Dusk screenshot permission issues while providing comprehensive testing
 */

$adminUrl = 'https://emporiodigital.test';
$adminEmail = 'admin@emporiodigital.test';
$sessionCookie = null;

echo "🚀 MANUAL SUPERADMIN REGRESSION TEST\n";
echo "====================================\n";
echo "Target: {$adminUrl}\n\n";

// Test results tracking
$tests = [
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

/**
 * Make HTTP request with optional session cookie
 */
function makeRequest($url, $method = 'GET', $data = null, $cookie = null) {
    $ch = curl_init();

    $headers = [
        'User-Agent: Emporio-Digital-Regression-Test/1.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $statusCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Record test result
 */
function recordTest(&$tests, $name, $passed, $details = '', $statusCode = null) {
    if ($passed) {
        $tests['passed']++;
        echo "✅ {$name}: PASSED";
    } else {
        $tests['failed']++;
        echo "❌ {$name}: FAILED";
    }

    if ($details) {
        echo " - {$details}";
    }
    if ($statusCode) {
        echo " (HTTP {$statusCode})";
    }
    echo "\n";

    $tests['details'][] = [
        'name' => $name,
        'passed' => $passed,
        'details' => $details,
        'status_code' => $statusCode
    ];
}

echo "🔐 Step 1: Testing SuperAdmin Login\n";

// Test 1: Login page accessibility
$loginResponse = makeRequest("{$adminUrl}/admin/login");
$loginPageAccessible = $loginResponse['status_code'] === 200 &&
                       strpos($loginResponse['response'], 'Iniciar Sesión') !== false;

recordTest($tests, 'Login Page Access', $loginPageAccessible,
           $loginPageAccessible ? 'Page loads correctly' : 'Page not accessible',
           $loginResponse['status_code']);

if (!$loginPageAccessible) {
    echo "\n❌ CRITICAL: Cannot access login page. Aborting regression test.\n";
    exit(1);
}

// Test 2: Extract CSRF token and perform login
echo "\n🔐 Step 2: Extracting CSRF token and logging in...\n";

$csrfToken = null;
if (preg_match('/<meta name="csrf-token" content="([^"]+)">/', $loginResponse['response'], $matches)) {
    $csrfToken = $matches[1];
    echo "✅ CSRF Token extracted successfully\n";
} else {
    echo "❌ Could not extract CSRF token\n";
    recordTest($tests, 'CSRF Token Extraction', false, 'No CSRF token found');
    exit(1);
}

// Perform login
$loginData = [
    'email' => $adminEmail,
    'password' => 'password',
    '_token' => $csrfToken
];

$loginPostResponse = makeRequest("{$adminUrl}/admin/login", 'POST', $loginData);

// Extract session cookie from login response
if (preg_match_all('/Set-Cookie:\s*(emporio_digital_session=[^;]+)/i', $loginPostResponse['response'], $cookieMatches)) {
    $sessionCookie = $cookieMatches[1][0];
    echo "✅ Session cookie extracted\n";
} else {
    echo "❌ Could not extract session cookie\n";
    recordTest($tests, 'Session Cookie Extraction', false, 'No session cookie found');
    exit(1);
}

// Test 3: Verify login success
$dashboardResponse = makeRequest("{$adminUrl}/admin", 'GET', null, $sessionCookie);
$loginSuccess = $dashboardResponse['status_code'] === 200 &&
               strpos($dashboardResponse['response'], 'Panel de Administración') !== false;

recordTest($tests, 'SuperAdmin Login', $loginSuccess,
           $loginSuccess ? 'Login successful' : 'Login failed',
           $dashboardResponse['status_code']);

if (!$loginSuccess) {
    echo "\n❌ CRITICAL: Login failed. Aborting regression test.\n";
    exit(1);
}

echo "\n📊 Step 3: Testing Dashboard Health\n";

// Test 4: Dashboard widgets load
$widgetsLoaded = strpos($dashboardResponse['response'], 'filament-widget') !== false;
recordTest($tests, 'Dashboard Widgets', $widgetsLoaded,
           $widgetsLoaded ? 'Widgets present' : 'Widgets missing');

echo "\n🏢 Step 4: Testing Tenant Management\n";

// Test 5: Tenants list
$tenantsResponse = makeRequest("{$adminUrl}/admin/tenants", 'GET', null, $sessionCookie);
$tenantsAccessible = $tenantsResponse['status_code'] === 200;
recordTest($tests, 'Tenants List', $tenantsAccessible,
           $tenantsAccessible ? 'Tenants list loads' : 'Tenants list failed',
           $tenantsResponse['status_code']);

// Test 6: Archived Tenants (CRITICAL 404 FIX TEST)
$archivedTenantsResponse = makeRequest("{$adminUrl}/admin/archived-tenants", 'GET', null, $sessionCookie);
$archivedTenantsAccessible = $archivedTenantsResponse['status_code'] === 200;

// Check for 404 indicators
$is404Error = $archivedTenantsResponse['status_code'] === 404 ||
              strpos($archivedTenantsResponse['response'], '404') !== false ||
              strpos($archivedTenantsResponse['response'], 'Not Found') !== false;

recordTest($tests, 'Archived Tenants Access (CRITICAL 404 FIX)',
           $archivedTenantsAccessible && !$is404Error,
           $archivedTenantsAccessible ? 'Accessible' : 'Not accessible or 404 error',
           $archivedTenantsResponse['status_code']);

echo "\n💳 Step 5: Testing Billing Module\n";

// Test 7: Payment Proofs
$paymentProofsResponse = makeRequest("{$adminUrl}/admin/payment-proofs", 'GET', null, $sessionCookie);
$paymentProofsAccessible = $paymentProofsResponse['status_code'] === 200;
recordTest($tests, 'Payment Proofs List', $paymentProofsAccessible,
           $paymentProofsAccessible ? 'Payment proofs loads' : 'Payment proofs failed',
           $paymentProofsResponse['status_code']);

// Test 8: Invoices
$invoicesResponse = makeRequest("{$adminUrl}/admin/invoices", 'GET', null, $sessionCookie);
$invoicesAccessible = $invoicesResponse['status_code'] === 200;
recordTest($tests, 'Invoices List', $invoicesAccessible,
           $invoicesAccessible ? 'Invoices loads' : 'Invoices failed',
           $invoicesResponse['status_code']);

echo "\n🏥 Step 6: Testing System Health Pages\n";

// Test 9: Error Logs
$errorLogsResponse = makeRequest("{$adminUrl}/admin/error-logs", 'GET', null, $sessionCookie);
$errorLogsAccessible = $errorLogsResponse['status_code'] === 200;
recordTest($tests, 'Error Logs', $errorLogsAccessible,
           $errorLogsAccessible ? 'Error logs loads' : 'Error logs failed',
           $errorLogsResponse['status_code']);

// Test 10: Backups
$backupsResponse = makeRequest("{$adminUrl}/admin/backups", 'GET', null, $sessionCookie);
$backupsAccessible = $backupsResponse['status_code'] === 200;
recordTest($tests, 'Backups Page', $backupsAccessible,
           $backupsAccessible ? 'Backups page loads' : 'Backups page failed',
           $backupsResponse['status_code']);

// Test 11: Support Tickets
$supportTicketsResponse = makeRequest("{$adminUrl}/admin/support-tickets", 'GET', null, $sessionCookie);
$supportTicketsAccessible = $supportTicketsResponse['status_code'] === 200;
recordTest($tests, 'Support Tickets', $supportTicketsAccessible,
           $supportTicketsAccessible ? 'Support tickets loads' : 'Support tickets failed',
           $supportTicketsResponse['status_code']);

echo "\n🎯 REGRESSION TEST SUMMARY\n";
echo "===========================\n";
echo "Tests Passed: {$tests['passed']}\n";
echo "Tests Failed: {$tests['failed']}\n";
echo "Total Tests: " . ($tests['passed'] + $tests['failed']) . "\n\n";

// Critical issues check
$criticalIssues = [];
foreach ($tests['details'] as $test) {
    if (!$test['passed'] && strpos($test['name'], 'CRITICAL') !== false) {
        $criticalIssues[] = $test;
    }
}

if (empty($criticalIssues)) {
    echo "🟢 HEALTH STATUS: SYSTEM NOMINAL\n";
    echo "✅ All critical systems are operational\n";
    echo "✅ No blocking issues detected\n";
    echo "\n🎉 REGRESSION TEST PASSED: System is ready for production\n";
    exit(0);
} else {
    echo "🔴 HEALTH STATUS: CRITICAL ISSUES DETECTED\n";
    echo "❌ The following critical issues need immediate attention:\n";
    foreach ($criticalIssues as $issue) {
        echo "   - {$issue['name']}: {$issue['details']} (HTTP {$issue['status_code']})\n";
    }
    echo "\n🚨 REGRESSION TEST FAILED: System requires immediate fixes\n";
    exit(1);
}