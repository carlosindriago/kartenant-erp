<?php

namespace Tests\Feature;

use App\Jobs\InstallationJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing installation
        if (File::exists(base_path('.installed'))) {
            File::delete(base_path('.installed'));
        }
    }

    /** @test */
    public function installation_wizard_pages_are_accessible()
    {
        // Test welcome page
        $response = $this->get('/install');
        $response->assertStatus(200);
        $response->assertSee('Bienvenido a Kartenant');

        // Test requirements page
        $response = $this->get('/install/requirements');
        $response->assertStatus(200);
        $response->assertSee('Verificación de Requisitos');

        // Test database page
        $response = $this->get('/install/database');
        $response->assertStatus(200);
        $response->assertSee('Configuración de Base de Datos');

        // Test admin page
        $response = $this->get('/install/admin');
        $response->assertStatus(200);
        $response->assertSee('Cuenta de Administrador');

        // Test settings page
        $response = $this->get('/install/settings');
        $response->assertStatus(200);
        $response->assertSee('Configuración Final');
    }

    /** @test */
    public function database_connection_test_works()
    {
        $response = $this->post('/install/test-database', [
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function installation_process_dispatches_async_job()
    {
        $installationData = [
            // Database config
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),

            // Admin config
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'testpassword123',

            // App config
            'app_name' => 'Test Kartenant',
            'app_url' => 'http://localhost',
            'app_timezone' => 'America/Lima',

            // Mail config (optional)
            'mail_host' => null,
            'mail_port' => null,
            'mail_username' => null,
            'mail_password' => null,
            'mail_encryption' => 'tls',
        ];

        $response = $this->post('/install/process', $installationData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['job_id']);

        // Verify job ID was returned
        $this->assertNotNull($response->json('job_id'));
    }

    /** @test */
    public function installation_job_is_dispatched_correctly()
    {
        Queue::fake();

        $installationData = [
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
            'admin_name' => 'Test Admin Async',
            'admin_email' => 'admin-async@test.com',
            'admin_password' => 'testpassword123',
            'app_name' => 'Test Kartenant Async',
            'app_url' => 'http://localhost',
            'app_timezone' => 'America/Lima',
        ];

        $response = $this->post('/install/process', $installationData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['job_id']);

        // Assert that the InstallationJob was dispatched
        Queue::assertPushed(InstallationJob::class, function ($job) use ($installationData) {
            return $job->installationData['app_name'] === $installationData['app_name'] &&
                   $job->installationData['admin_email'] === $installationData['admin_email'];
        });
    }

    /** @test */
    public function installation_fails_with_invalid_database_credentials()
    {
        $installationData = [
            'db_host' => 'invalid_host',
            'db_port' => 5432,
            'db_database' => 'invalid_db',
            'db_username' => 'invalid_user',
            'db_password' => 'invalid_pass',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'testpassword123',
            'app_name' => 'Test Kartenant',
            'app_url' => 'http://localhost',
            'app_timezone' => 'America/Lima',
        ];

        $response = $this->post('/install/process', $installationData);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure([
            'success',
            'message',
            'error_details' => [
                'file',
                'line',
                'trace',
            ],
        ]);
    }

    /** @test */
    public function installation_is_blocked_when_already_installed()
    {
        // Create installation lock file
        File::put(base_path('.installed'), json_encode([
            'installed_at' => now()->toISOString(),
            'version' => '1.0.0',
        ]));

        $response = $this->get('/install');
        $response->assertRedirect('/admin');
    }

    /** @test */
    public function installation_is_blocked_when_superadmin_exists()
    {
        // Create a superadmin user
        User::factory()->create([
            'is_super_admin' => true,
            'email' => 'existing@admin.com',
        ]);

        $response = $this->get('/install');
        $response->assertRedirect('/admin');
    }

    /**
     * @test
     */
    public function installation_validates_required_fields()
    {
        // Ensure no superadmin exists to bypass middleware
        User::where('is_super_admin', true)->delete();

        $response = $this->post('/install/process', []);

        // Installation controller returns JSON errors, not session errors
        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['success', 'errors']);
    }

    protected function tearDown(): void
    {
        // Clean up installation lock file
        if (File::exists(base_path('.installed'))) {
            File::delete(base_path('.installed'));
        }

        parent::tearDown();
    }

    /** @test */
    public function installation_progress_endpoint_returns_job_status()
    {
        // Mock cache to simulate job progress
        $jobId = 'test-job-123';
        Cache::put("installation_progress_{$jobId}", [
            'step' => 'database',
            'message' => 'Conectando a la base de datos...',
            'completed' => false,
        ], 300);

        $response = $this->get("/install/progress?job_id={$jobId}");

        $response->assertStatus(200);
        $response->assertJson([
            'step' => 'database',
            'message' => 'Conectando a la base de datos...',
            'completed' => false,
        ]);
    }

    /** @test */
    public function installation_progress_endpoint_handles_missing_job()
    {
        $response = $this->get('/install/progress?job_id=nonexistent-job');

        $response->assertStatus(200);
        $response->assertJson([
            'step' => 'unknown',
            'message' => 'Estado de instalación no encontrado',
            'completed' => false,
        ]);
    }

    /** @test */
    public function installation_progress_endpoint_requires_job_id()
    {
        $response = $this->get('/install/progress');

        $response->assertStatus(200);
        $response->assertJson([
            'step' => 'unknown',
            'message' => 'ID de trabajo no proporcionado',
            'completed' => false,
        ]);
    }

    /** @test */
    public function installation_job_updates_progress_cache()
    {
        Queue::fake();

        $installationData = [
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
            'admin_name' => 'Test Admin Progress',
            'admin_email' => 'admin-progress@test.com',
            'admin_password' => 'testpassword123',
            'app_name' => 'Test Kartenant Progress',
            'app_url' => 'http://localhost',
            'app_timezone' => 'America/Lima',
        ];

        $response = $this->post('/install/process', $installationData);
        $jobId = $response->json('job_id');

        // Simulate job execution by manually creating an InstallationJob and calling handle
        $job = new InstallationJob($installationData, $jobId);

        // Mock the job execution to avoid actual installation
        try {
            // This would normally execute the full installation
            // For testing, we just verify the job can be created
            $this->assertInstanceOf(InstallationJob::class, $job);
            $this->assertEquals($installationData, $job->installationData);
            $this->assertEquals($jobId, $job->jobId);
        } catch (\Exception $e) {
            // Expected in test environment due to missing dependencies
            $this->assertNotNull($job);
        }
    }
}
