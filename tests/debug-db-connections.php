<?php

/**
 * Debug Database Connections Script
 * Ayuda a identificar problemas de conexión y contexto tenant
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║      DATABASE CONNECTIONS DEBUG SCRIPT            ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  1. CONFIGURATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "Default Connection: " . config('database.default') . "\n";
echo "Current DB Connection: " . DB::getDefaultConnection() . "\n";
echo "Tenant Finder: " . (config('multitenancy.tenant_finder') ?? 'null') . "\n";
echo "Switch Tenant Tasks: " . count(config('multitenancy.switch_tenant_tasks')) . "\n";

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  2. CONNECTIONS CONFIG\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$connections = ['landlord', 'tenant', 'pgsql'];
foreach ($connections as $conn) {
    $config = config("database.connections.$conn");
    echo "\n[$conn]\n";
    echo "  Driver: " . ($config['driver'] ?? 'N/A') . "\n";
    echo "  Database: " . ($config['database'] ?? 'null') . "\n";
    echo "  Host: " . ($config['host'] ?? 'N/A') . "\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  3. CURRENT TENANT CONTEXT\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$containerKey = config('multitenancy.current_tenant_container_key', 'currentTenant');
if (app()->bound($containerKey)) {
    $currentTenant = app($containerKey);
    echo "⚠️  WARNING: Tenant is ACTIVE in container!\n";
    echo "  Tenant ID: " . $currentTenant->id . "\n";
    echo "  Tenant Name: " . $currentTenant->name . "\n";
    echo "  Tenant Database: " . $currentTenant->database . "\n";
} else {
    echo "✅ No tenant active in container\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  4. CONNECTION TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Test landlord connection
echo "\n[LANDLORD Connection Test]\n";
try {
    $landlordDb = DB::connection('landlord')->getDatabaseName();
    echo "✅ Database Name: $landlordDb\n";
    $usersCount = DB::connection('landlord')->table('users')->count();
    echo "✅ Users Count: $usersCount\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test tenant connection
echo "\n[TENANT Connection Test]\n";
try {
    $tenantDb = DB::connection('tenant')->getDatabaseName();
    if ($tenantDb) {
        echo "⚠️  Tenant Database: $tenantDb\n";
        echo "⚠️  This should be NULL when no tenant is active!\n";
    } else {
        echo "✅ Tenant Database: null (correct when no tenant active)\n";
    }
} catch (Exception $e) {
    echo "⚠️  Expected error (no tenant active): " . $e->getMessage() . "\n";
}

// Test default connection behavior
echo "\n[DEFAULT Connection Test]\n";
echo "Testing what happens when we DON'T specify connection:\n";
try {
    $defaultConn = DB::connection()->getName();
    echo "Default connection name: $defaultConn\n";
    $defaultDb = DB::connection()->getDatabaseName();
    echo "Default database: $defaultDb\n";
    
    if ($defaultConn === 'landlord') {
        echo "✅ Default connection is landlord (CORRECT for admin context)\n";
    } elseif ($defaultConn === 'tenant') {
        echo "❌ WARNING: Default connection is tenant! This will cause errors in admin!\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  5. SPATIE PERMISSION CONFIG\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

echo "Permission Model: " . config('permission.models.permission') . "\n";
echo "Role Model: " . config('permission.models.role') . "\n";
echo "Default Guard: " . config('permission.default_guard') . "\n";
echo "Cache Key: " . config('permission.cache.key') . "\n";

// Test Permission model connection
echo "\n[Permission Model Test]\n";
try {
    $permissionModel = config('permission.models.permission');
    $model = new $permissionModel();
    $connection = $model->getConnectionName();
    echo "Permission model connection: " . ($connection ?? 'default') . "\n";
    
    if ($connection === 'landlord') {
        echo "✅ Permission model uses landlord connection (CORRECT)\n";
    } else {
        echo "⚠️  Permission model connection: $connection\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  6. RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$issues = [];

if (config('multitenancy.tenant_finder') !== null) {
    $issues[] = "⚠️  Tenant finder is ENABLED - should be NULL for multi-panel setup";
}

if (DB::getDefaultConnection() !== 'landlord') {
    $issues[] = "❌ Default connection is NOT landlord - will cause errors in admin!";
}

if (app()->bound($containerKey)) {
    $issues[] = "⚠️  Tenant is ACTIVE in container - should not be active in admin context";
}

if (empty($issues)) {
    echo "✅ No issues detected! Configuration looks correct.\n";
    echo "\nIf you're still getting errors, the problem may be:\n";
    echo "1. Cache not cleared (try: sail artisan config:clear)\n";
    echo "2. Old session data (try clearing browser cookies)\n";
    echo "3. A middleware executing AFTER the fixes\n";
} else {
    echo "ISSUES DETECTED:\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Done.\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
