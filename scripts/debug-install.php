<?php

/**
 * Installation Debug Script
 * Run this script to test the installation process step by step
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Http\Controllers\InstallController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

// Load Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== KARTENANT DIGITAL INSTALLATION DEBUG ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $pdo = new PDO(
        'pgsql:host='.env('DB_HOST', 'pgsql').';port='.env('DB_PORT', 5432).';dbname='.env('DB_DATABASE', 'laravel'),
        env('DB_USERNAME', 'sail'),
        env('DB_PASSWORD', 'password'),
        [
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    $result = $pdo->query('SELECT version()')->fetch();
    echo "✓ Database connection successful\n";
    echo '  PostgreSQL Version: '.$result[0]."\n";
} catch (Exception $e) {
    echo '✗ Database connection failed: '.$e->getMessage()."\n";
    exit(1);
}

// Test 2: Migration Execution
echo "\n2. Testing Migration Execution...\n";
try {
    $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
    $output = Artisan::output();

    if ($exitCode === 0) {
        echo "✓ Migrations executed successfully\n";
        echo '  Output: '.trim($output)."\n";

        // Run landlord migrations if permissions table doesn't exist
        if (! Schema::hasTable('permissions')) {
            echo "  Running landlord migrations...\n";
            $exitCode = Artisan::call('migrate', [
                '--path' => 'database/migrations/landlord',
                '--force' => true,
            ]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                echo "  ✓ Landlord migrations executed successfully\n";
                echo '    Output: '.trim($output)."\n";
            } else {
                echo "  ✗ Landlord migrations failed with exit code: $exitCode\n";
                echo '    Output: '.trim($output)."\n";
            }
        } else {
            echo "  ✓ Permissions tables already exist\n";
        }
    } else {
        echo "✗ Migrations failed with exit code: $exitCode\n";
        echo '  Output: '.trim($output)."\n";
    }
} catch (Exception $e) {
    echo '✗ Migration execution failed: '.$e->getMessage()."\n";
}

// Test 3: Seeder Execution
echo "\n3. Testing Seeder Execution...\n";
try {
    $exitCode = Artisan::call('db:seed', ['--class' => 'LandlordAdminSeeder', '--force' => true]);
    $output = Artisan::output();

    if ($exitCode === 0) {
        echo "✓ Seeder executed successfully\n";
        echo '  Output: '.trim($output)."\n";
    } else {
        echo "✗ Seeder failed with exit code: $exitCode\n";
        echo '  Output: '.trim($output)."\n";
    }
} catch (Exception $e) {
    echo '✗ Seeder execution failed: '.$e->getMessage()."\n";
}

// Test 4: User Creation
echo "\n4. Testing User Creation...\n";
try {
    $user = User::create([
        'name' => 'Debug Admin',
        'email' => 'debug@test.com',
        'password' => Hash::make('debugpassword123'),
        'email_verified_at' => now(),
        'is_super_admin' => true,
        'must_change_password' => false,
    ]);

    echo "✓ User created successfully\n";
    echo '  User ID: '.$user->id."\n";
    echo '  Email: '.$user->email."\n";
    echo '  Is Super Admin: '.($user->is_super_admin ? 'Yes' : 'No')."\n";
} catch (Exception $e) {
    echo '✗ User creation failed: '.$e->getMessage()."\n";
}

// Test 5: Installation Lock
echo "\n5. Testing Installation Lock Creation...\n";
try {
    $lockData = [
        'installed_at' => now()->toISOString(),
        'version' => '1.0.0',
    ];

    File::put(base_path('.installed'), json_encode($lockData));

    if (File::exists(base_path('.installed'))) {
        echo "✓ Installation lock file created successfully\n";
        echo '  Content: '.File::get(base_path('.installed'))."\n";
    } else {
        echo "✗ Installation lock file creation failed\n";
    }
} catch (Exception $e) {
    echo '✗ Installation lock creation failed: '.$e->getMessage()."\n";
}

// Test 6: Full Installation Process Simulation
echo "\n6. Testing Full Installation Process via HTTP...\n";

// Clean up first
if (File::exists(base_path('.installed'))) {
    File::delete(base_path('.installed'));
}

// Simulate the HTTP request
$installData = [
    'db_host' => env('DB_HOST', 'pgsql'),
    'db_port' => env('DB_PORT', 5432),
    'db_database' => env('DB_DATABASE', 'laravel'),
    'db_username' => env('DB_USERNAME', 'sail'),
    'db_password' => env('DB_PASSWORD', 'password'),
    'admin_name' => 'HTTP Test Admin',
    'admin_email' => 'httptest@admin.com',
    'admin_password' => 'httptestpassword123',
    'app_name' => 'HTTP Test Kartenant',
    'app_url' => 'http://localhost',
    'app_timezone' => 'America/Lima',
    'mail_host' => null,
    'mail_port' => null,
    'mail_username' => null,
    'mail_password' => null,
    'mail_encryption' => 'tls',
];

echo "Simulating HTTP POST to /install/process...\n";

try {
    // Create a request instance
    $request = new Request;
    $request->merge($installData);

    // Call the controller method directly
    $controller = new InstallController;
    $response = $controller->install($request);

    $responseData = json_decode($response->getContent(), true);

    if (isset($responseData['success']) && $responseData['success']) {
        echo "✓ HTTP installation process completed successfully\n";
        echo '  Message: '.$responseData['message']."\n";
    } else {
        echo "✗ HTTP installation process failed\n";
        echo '  Message: '.($responseData['message'] ?? 'Unknown error')."\n";
        if (isset($responseData['error_details'])) {
            echo "  Error Details:\n";
            echo '    File: '.$responseData['error_details']['file']."\n";
            echo '    Line: '.$responseData['error_details']['line']."\n";
            echo '    Error: '.substr($responseData['error_details']['trace'], 0, 500)."...\n";
        }
    }
} catch (Exception $e) {
    echo '✗ HTTP installation simulation failed: '.$e->getMessage()."\n";
    echo '  File: '.$e->getFile()."\n";
    echo '  Line: '.$e->getLine()."\n";
}

echo "\n=== DEBUG COMPLETE ===\n";

// Clean up
if (File::exists(base_path('.installed'))) {
    File::delete(base_path('.installed'));
    echo "Cleaned up installation lock file\n";
}
