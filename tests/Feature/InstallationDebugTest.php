<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class InstallationDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_migration_execution_step_by_step()
    {
        echo "\n=== DEBUGGING MIGRATION EXECUTION ===\n";
        
        // Test individual migration commands
        echo "1. Testing migrate:fresh command...\n";
        try {
            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
            $output = Artisan::output();
            echo "Exit Code: $exitCode\n";
            echo "Output: $output\n";
            $this->assertEquals(0, $exitCode, "Migration should succeed");
        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        echo "2. Testing seeder execution...\n";
        try {
            $exitCode = Artisan::call('db:seed', ['--class' => 'LandlordAdminSeeder', '--force' => true]);
            $output = Artisan::output();
            echo "Seeder Exit Code: $exitCode\n";
            echo "Seeder Output: $output\n";
            $this->assertEquals(0, $exitCode, "Seeding should succeed");
        } catch (\Exception $e) {
            echo "Seeding failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        echo "3. Testing superadmin creation...\n";
        try {
            $user = User::create([
                'name' => 'Test Admin',
                'email' => 'test@admin.com',
                'password' => bcrypt('testpassword123'),
                'email_verified_at' => now(),
                'is_super_admin' => true,
                'must_change_password' => false,
            ]);
            echo "Superadmin created with ID: " . $user->id . "\n";
            $this->assertNotNull($user);
        } catch (\Exception $e) {
            echo "Superadmin creation failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        echo "=== ALL STEPS COMPLETED SUCCESSFULLY ===\n";
    }

    /** @test */
    public function debug_database_connection_with_different_configs()
    {
        echo "\n=== DEBUGGING DATABASE CONNECTIONS ===\n";
        
        $configs = [
            [
                'host' => env('DB_HOST', 'pgsql'),
                'port' => env('DB_PORT', 5432),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'sail'),
                'password' => env('DB_PASSWORD', 'password'),
            ],
            [
                'host' => 'pgsql',
                'port' => 5432,
                'database' => 'laravel',
                'username' => 'sail',
                'password' => 'password',
            ]
        ];

        foreach ($configs as $index => $config) {
            echo "Testing config " . ($index + 1) . ":\n";
            echo "Host: {$config['host']}, Port: {$config['port']}, DB: {$config['database']}\n";
            
            try {
                $pdo = new \PDO(
                    "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                    $config['username'],
                    $config['password'],
                    [
                        \PDO::ATTR_TIMEOUT => 10,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                    ]
                );
                
                $result = $pdo->query('SELECT 1 as test')->fetch();
                echo "✓ Connection successful, test query result: " . $result['test'] . "\n";
                
            } catch (\Exception $e) {
                echo "✗ Connection failed: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
    }

    /** @test */
    public function debug_full_installation_process_with_logging()
    {
        echo "\n=== DEBUGGING FULL INSTALLATION PROCESS ===\n";
        
        // Clean up first
        if (File::exists(base_path('.installed'))) {
            File::delete(base_path('.installed'));
        }

        $installationData = [
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
            'admin_name' => 'Debug Admin',
            'admin_email' => 'debug@admin.com',
            'admin_password' => 'debugpassword123',
            'app_name' => 'Debug Kartenant',
            'app_url' => 'http://localhost',
            'app_timezone' => 'America/Lima',
            'mail_host' => null,
            'mail_port' => null,
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
        ];

        echo "Sending installation request with data:\n";
        foreach ($installationData as $key => $value) {
            if ($key !== 'admin_password') {
                echo "  $key: $value\n";
            } else {
                echo "  $key: [HIDDEN]\n";
            }
        }

        $response = $this->post('/install/process', $installationData);
        
        echo "\nResponse Status: " . $response->getStatusCode() . "\n";
        echo "Response Content: " . $response->getContent() . "\n";

        // Check what was logged
        $logFile = storage_path('logs/laravel.log');
        if (File::exists($logFile)) {
            $logs = File::get($logFile);
            $recentLogs = collect(explode("\n", $logs))
                ->filter(function ($line) {
                    return strpos($line, date('Y-m-d')) !== false;
                })
                ->take(-20)
                ->implode("\n");
            
            echo "\nRecent logs:\n";
            echo $recentLogs . "\n";
        }

        if ($response->isSuccessful()) {
            $data = $response->json();
            if (isset($data['success']) && $data['success']) {
                echo "✓ Installation completed successfully\n";
            } else {
                echo "✗ Installation failed: " . ($data['message'] ?? 'Unknown error') . "\n";
                if (isset($data['error_details'])) {
                    echo "Error details: " . json_encode($data['error_details'], JSON_PRETTY_PRINT) . "\n";
                }
            }
        } else {
            echo "✗ HTTP request failed\n";
        }
    }

    /** @test */
    public function debug_session_storage_simulation()
    {
        echo "\n=== DEBUGGING SESSION STORAGE SIMULATION ===\n";
        
        // Simulate what should be in sessionStorage
        $dbConfig = [
            'db_host' => 'pgsql',
            'db_port' => '5432',
            'db_database' => 'laravel',
            'db_username' => 'sail',
            'db_password' => 'password'
        ];
        
        $adminConfig = [
            'admin_name' => 'Carlos Indriago',
            'admin_email' => 'carlos@kartenant.test',
            'admin_password' => 'Cj18279116..'
        ];
        
        $settingsConfig = [
            'app_name' => 'Kartenant',
            'app_url' => 'https://kartenant.test',
            'app_timezone' => 'America/Lima',
            'mail_host' => null,
            'mail_port' => null,
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls'
        ];

        echo "Simulated DB Config:\n";
        print_r($dbConfig);
        
        echo "\nSimulated Admin Config:\n";
        $adminConfigSafe = $adminConfig;
        $adminConfigSafe['admin_password'] = '[HIDDEN]';
        print_r($adminConfigSafe);
        
        echo "\nSimulated Settings Config:\n";
        print_r($settingsConfig);

        // Combine all configs like the JavaScript does
        $combinedData = array_merge($dbConfig, $adminConfig, $settingsConfig);
        
        echo "\nCombined data that should be sent:\n";
        $combinedDataSafe = $combinedData;
        $combinedDataSafe['admin_password'] = '[HIDDEN]';
        print_r($combinedDataSafe);

        // Test the actual request
        $response = $this->post('/install/process', $combinedData);
        
        echo "\nRequest result:\n";
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Response: " . $response->getContent() . "\n";
    }
}
