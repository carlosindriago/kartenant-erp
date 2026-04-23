<?php

/**
 * System Health Check Script
 * Verifica el estado del sistema multi-tenant después de los fixes
 */

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║       KARTENANT DIGITAL SYSTEM HEALTH CHECK         ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

function test($name, $callback)
{
    global $errors, $warnings, $passed, $total;
    $total++;
    echo "→ Testing: $name ... ";
    try {
        $result = $callback();
        if ($result === true) {
            echo "✅ PASS\n";
            $passed++;
        } elseif (is_string($result)) {
            echo "⚠️  WARNING: $result\n";
            $warnings[] = "$name: $result";
            $passed++;
        } else {
            echo "❌ FAIL\n";
            $errors[] = $name;
        }
    } catch (Exception $e) {
        echo '❌ ERROR: '.$e->getMessage()."\n";
        $errors[] = "$name: ".$e->getMessage();
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  1. LANDLORD DATABASE TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

test('Landlord connection works', function () {
    DB::connection('landlord')->getPdo();

    return true;
});

test('Users table exists with data', function () {
    $count = DB::connection('landlord')->table('users')->count();

    return $count > 0 ? true : 'No users found';
});

test('Superadmin exists', function () {
    $admin = DB::connection('landlord')->table('users')->where('is_super_admin', true)->first();

    return $admin ? true : false;
});

test('Activity log table exists', function () {
    $count = DB::connection('landlord')->table('activity_log')->count();

    return true;
});

test('Tenants table exists', function () {
    $count = DB::connection('landlord')->table('tenants')->count();

    return true;
});

test('Landlord permission tables exist', function () {
    DB::connection('landlord')->table('permissions')->count();
    DB::connection('landlord')->table('roles')->count();

    return true;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  2. MULTITENANCY CONFIG TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

test('Tenant finder is disabled (null)', function () {
    return config('multitenancy.tenant_finder') === null;
});

test('Switch tenant tasks configured', function () {
    $tasks = config('multitenancy.switch_tenant_tasks');

    return count($tasks) > 0;
});

test('Tenant model configured', function () {
    return config('multitenancy.tenant_model') === \App\Models\Tenant::class;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  3. TENANT DATABASE TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

test('At least one tenant exists', function () {
    $count = Tenant::count();

    return $count > 0 ? true : 'No tenants found';
});

test('Tenant database connection works', function () {
    $tenant = Tenant::first();
    if (! $tenant) {
        return 'No tenant to test';
    }

    $tenant->execute(function () {
        DB::connection('tenant')->getPdo();
    });

    return true;
});

test('Tenant has required tables', function () {
    $tenant = Tenant::first();
    if (! $tenant) {
        return 'No tenant to test';
    }

    $tenant->execute(function () {
        DB::connection('tenant')->table('products')->count();
        DB::connection('tenant')->table('permissions')->count();
        DB::connection('tenant')->table('roles')->count();
    });

    return true;
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  4. AUTHENTICATION & GUARDS TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

test('Superadmin guard configured', function () {
    return config('auth.guards.superadmin') !== null;
});

test('Web guard configured', function () {
    return config('auth.guards.web') !== null;
});

test('Tenant guard configured', function () {
    return config('auth.guards.tenant') !== null;
});

test('User model has landlord connection', function () {
    $user = new User;

    return $user->getConnectionName() === 'landlord';
});

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  5. FILE STRUCTURE TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

test('Landlord migrations directory exists', function () {
    return is_dir(database_path('migrations/landlord'));
});

test('Tenant migrations directory exists', function () {
    return is_dir(database_path('migrations/tenant'));
});

test('No duplicate products migration', function () {
    $files = glob(database_path('migrations/tenant/*create_products_table.php'));

    return count($files) === 1;
});

test('Activity log migrations in landlord', function () {
    $files = glob(database_path('migrations/landlord/*activity_log*.php'));

    return count($files) >= 4;
});

test('ARCHITECTURE.md exists', function () {
    return file_exists(base_path('ARCHITECTURE.md'));
});

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║                  RESULTS SUMMARY                   ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total Tests: $total\n";
echo "✅ Passed: $passed\n";
echo '⚠️  Warnings: '.count($warnings)."\n";
echo '❌ Failed: '.count($errors)."\n";
echo "\n";

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ FAILURES:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    exit(1);
}

$percentage = round(($passed / $total) * 100, 1);
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 SUCCESS! System health: {$percentage}%\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

exit(0);
