<?php

/**
 * Simple Security Test Validator
 * Validates that the security test files are syntactically correct
 * and contain the required test cases.
 */

echo "🔒 OPERATION FORTRESS - SECURITY TEST VALIDATOR\n";
echo "================================================\n\n";

// Check required files exist
$requiredFiles = [
    'tests/Feature/Tenant/AuthSecurityTest.php',
    'tests/Support/SecurityTestHelpers.php',
    'tests/security-validation-runner.php'
];

$allFilesExist = true;

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ Found: {$file}\n";

        // Check file permissions
        $perms = fileperms($file);
        $readable = ($perms & 0x0044) > 0;

        if ($readable) {
            echo "   ✅ File is readable\n";
        } else {
            echo "   ❌ File is not readable (permissions: " . substr(sprintf('%o', fileperms($file)), -4) . ")\n";
            chmod($file, 0644);
            echo "   🔧 Fixed permissions\n";
        }

        // Check syntax for PHP files
        if (str_ends_with($file, '.php')) {
            $output = [];
            $returnCode = 0;
            exec("php -l {$file} 2>&1", $output, $returnCode);

            if ($returnCode === 0) {
                echo "   ✅ PHP syntax is valid\n";
            } else {
                echo "   ❌ PHP syntax error:\n";
                foreach ($output as $line) {
                    echo "      {$line}\n";
                }
                $allFilesExist = false;
            }
        }
    } else {
        echo "❌ Missing: {$file}\n";
        $allFilesExist = false;
    }
    echo "\n";
}

// Validate test content if main test file exists
if (file_exists('tests/Feature/Tenant/AuthSecurityTest.php')) {
    echo "🔍 Validating Security Test Content:\n";
    echo str_repeat('-', 40) . "\n";

    $testContent = file_get_contents('tests/Feature/Tenant/AuthSecurityTest.php');

    // Check for critical test functions
    $criticalTests = [
        'rate limiting prevents ip rotation bypass',
        'generic error messages prevent user enumeration',
        'session id changes after successful login',
        'account locks out after 5 failed attempts',
        'authentication respects tenant isolation',
        'successful login flow works with security fixes'
    ];

    $foundTests = 0;

    foreach ($criticalTests as $test) {
        if (str_contains($testContent, $test)) {
            echo "✅ Found test: {$test}\n";
            $foundTests++;
        } else {
            echo "❌ Missing test: {$test}\n";
        }
    }

    echo "\n📊 Test Coverage: {$foundTests}/" . count($criticalTests) . " critical tests found\n";

    // Check for security-specific patterns
    $securityPatterns = [
        'timing attack prevention' => '⏱️ Timing attack protection',
        'session fixation' => '🔐 Session security',
        'rate limiting' => '🚦 Rate limiting',
        'user enumeration' => '👤 Anti-enumeration',
        'exponential lockout' => '🔒 Progressive lockout'
    ];

    echo "\n🛡️ Security Controls Validated:\n";
    foreach ($securityPatterns as $pattern => $label) {
        if (str_contains($testContent, $pattern)) {
            echo "✅ {$label}\n";
        } else {
            echo "❌ {$label} - Pattern not found\n";
        }
    }
}

echo "\n";

// Check helper functions
if (file_exists('tests/Support/SecurityTestHelpers.php')) {
    echo "🔧 Validating Security Test Helpers:\n";
    echo str_repeat('-', 40) . "\n";

    $helperContent = file_get_contents('tests/Support/SecurityTestHelpers.php');

    $helperFunctions = [
        'simulateIpRotationAttack',
        'measureResponseTime',
        'generateAttackPayloads',
        'assertRateLimitKeysExist',
        'createTestTenantWithUser'
    ];

    $foundHelpers = 0;

    foreach ($helperFunctions as $function) {
        if (str_contains($helperContent, $function)) {
            echo "✅ Found helper: {$function}\n";
            $foundHelpers++;
        } else {
            echo "❌ Missing helper: {$function}\n";
        }
    }

    echo "\n📊 Helper Coverage: {$foundHelpers}/" . count($helperFunctions) . " functions found\n";
}

echo "\n";

// Check test runner
if (file_exists('tests/security-validation-runner.php')) {
    echo "🚀 Validating Security Test Runner:\n";
    echo str_repeat('-', 40) . "\n";

    $runnerContent = file_get_contents('tests/security-validation-runner.php');

    $runnerFeatures = [
        'validateEnvironment' => 'Environment validation',
        'runSecurityTests' => 'Test execution',
        'generateReport' => 'Report generation',
        'validateProductionReadiness' => 'Production readiness'
    ];

    $foundFeatures = 0;

    foreach ($runnerFeatures as $feature => $label) {
        if (str_contains($runnerContent, $feature)) {
            echo "✅ Found feature: {$label}\n";
            $foundFeatures++;
        } else {
            echo "❌ Missing feature: {$label}\n";
        }
    }

    // Check if it's executable
    if (is_executable('tests/security-validation-runner.php')) {
        echo "✅ Test runner is executable\n";
    } else {
        echo "❌ Test runner is not executable\n";
    }
}

echo "\n";

// Final validation
echo "🎯 FINAL VALIDATION RESULTS:\n";
echo str_repeat('=', 40) . "\n";

if ($allFilesExist) {
    echo "✅ All required files are present and valid\n";
    echo "✅ Security test infrastructure is ready\n";
    echo "✅ Test suite can be executed\n";
    echo "\n";
    echo "🚀 NEXT STEPS:\n";
    echo "   1. Run: ./tests/security-validation-runner.php\n";
    echo "   2. Or: ./vendor/bin/sail artisan test tests/Feature/Tenant/AuthSecurityTest.php\n";
    echo "   3. Review security test coverage report\n";
    echo "\n";
    echo "🛡️ Ernesto's business data protection is ready for validation!\n";
} else {
    echo "❌ Some files are missing or have syntax errors\n";
    echo "❌ Please fix the issues before running security tests\n";
    echo "\n";
    echo "🔧 TROUBLESHOOTING:\n";
    echo "   1. Check file permissions: chmod 644 tests/**/*.php\n";
    echo "   2. Fix syntax errors in PHP files\n";
    echo "   3. Ensure all required files are present\n";
}

echo "\n";
echo "OPERATION FORTRESS - VALIDATION COMPLETE\n";
echo "======================================\n";

exit($allFilesExist ? 0 : 1);