<?php

namespace Tests\Feature;

use App\Jobs\InstallationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsyncInstallationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing installation
        if (File::exists(base_path('.installed'))) {
            File::delete(base_path('.installed'));
        }

        // Clear any existing cache
        Cache::flush();
    }

    /** @test */
    public function installation_controller_dispatches_job_with_correct_data()
    {
        Queue::fake();

        $installationData = [
            'db_host' => 'localhost',
            'db_port' => '5432',
            'db_database' => 'kartenant_test',
            'db_username' => 'postgres',
            'db_password' => 'password',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'SecurePassword123!',
            'admin_password_confirmation' => 'SecurePassword123!',
            'app_name' => 'Test Kartenant',
            'app_url' => 'http://test.local',
            'app_timezone' => 'America/Argentina/Buenos_Aires',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => '587',
            'mail_username' => 'test@gmail.com',
            'mail_password' => 'testpassword',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@test.com',
            'mail_from_name' => 'Test Kartenant',
        ];

        $response = $this->post('/install/process', $installationData);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['job_id']);

        // Verify job was dispatched with correct data
        Queue::assertPushed(InstallationJob::class, function ($job) use ($installationData) {
            return $job->installationData['app_name'] === $installationData['app_name'] &&
                   $job->installationData['admin_email'] === $installationData['admin_email'] &&
                   $job->installationData['db_database'] === $installationData['db_database'];
        });
    }

    /** @test */
    public function installation_job_can_be_created_with_valid_data()
    {
        $installationData = [
            'app_name' => 'Test Kartenant Job',
            'admin_email' => 'admin-job@test.com',
            'admin_password' => 'SecurePassword123!',
            'db_host' => 'localhost',
            'db_database' => 'kartenant_test',
        ];

        $jobId = 'test-job-'.uniqid();

        $job = new InstallationJob($installationData, $jobId);

        $this->assertInstanceOf(InstallationJob::class, $job);
        $this->assertEquals($installationData, $job->installationData);
        $this->assertEquals($jobId, $job->jobId);
    }

    /** @test */
    public function progress_endpoint_returns_cached_job_status()
    {
        $jobId = 'test-progress-job-123';

        // Simulate job progress in cache
        Cache::put("installation_progress_{$jobId}", [
            'step' => 'migrations',
            'message' => 'Ejecutando migraciones de base de datos...',
            'completed' => false,
            'progress' => 60,
        ], 300);

        $response = $this->get("/install/progress?job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'step' => 'migrations',
                'message' => 'Ejecutando migraciones de base de datos...',
                'completed' => false,
                'progress' => 60,
            ]);
    }

    /** @test */
    public function progress_endpoint_handles_nonexistent_job()
    {
        $response = $this->get('/install/progress?job_id=nonexistent-job-456');

        $response->assertStatus(200)
            ->assertJson([
                'step' => 'unknown',
                'message' => 'Estado de instalación no encontrado',
                'completed' => false,
            ]);
    }

    /** @test */
    public function progress_endpoint_requires_job_id_parameter()
    {
        $response = $this->get('/install/progress');

        $response->assertStatus(200)
            ->assertJson([
                'step' => 'unknown',
                'message' => 'ID de trabajo no proporcionado',
                'completed' => false,
            ]);
    }

    /** @test */
    public function installation_job_updates_progress_during_execution()
    {
        $installationData = [
            'app_name' => 'Test Progress Update',
            'admin_email' => 'progress@test.com',
            'admin_password' => 'SecurePassword123!',
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_port' => env('DB_PORT', 5432),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
        ];

        $jobId = 'progress-update-job-'.uniqid();
        $job = new InstallationJob($installationData, $jobId);

        // Test that job can update progress (method exists)
        $this->assertTrue(method_exists($job, 'updateProgress'));

        // Simulate progress update
        $job->updateProgress('starting', 'Iniciando instalación...');

        // Verify progress was cached
        $progress = Cache::get("installation_progress_{$jobId}");
        $this->assertNotNull($progress);
        $this->assertEquals('starting', $progress['step']);
        $this->assertEquals('Iniciando instalación...', $progress['message']);
    }

    /** @test */
    public function multiple_concurrent_installations_have_unique_job_ids()
    {
        Queue::fake();

        $installationData1 = [
            'app_name' => 'Test Kartenant 1',
            'admin_name' => 'Test Admin 1',
            'admin_email' => 'admin1@test.com',
            'admin_password' => 'SecurePassword123!',
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_port' => env('DB_PORT', 5432),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
            'app_url' => 'http://test1.local',
            'app_timezone' => 'America/Argentina/Buenos_Aires',
        ];

        $installationData2 = [
            'app_name' => 'Test Kartenant 2',
            'admin_name' => 'Test Admin 2',
            'admin_email' => 'admin2@test.com',
            'admin_password' => 'SecurePassword123!',
            'db_host' => env('DB_HOST', 'pgsql'),
            'db_database' => env('DB_DATABASE', 'testing'),
            'db_port' => env('DB_PORT', 5432),
            'db_username' => env('DB_USERNAME', 'sail'),
            'db_password' => env('DB_PASSWORD', 'password'),
            'app_url' => 'http://test2.local',
            'app_timezone' => 'America/Argentina/Buenos_Aires',
        ];

        $response1 = $this->post('/install/process', $installationData1);
        $response2 = $this->post('/install/process', $installationData2);

        // Both should succeed since we're using Queue::fake()
        $this->assertTrue($response1->json('success'), 'First installation should succeed: '.json_encode($response1->json()));
        $this->assertTrue($response2->json('success'), 'Second installation should succeed: '.json_encode($response2->json()));

        $jobId1 = $response1->json('job_id');
        $jobId2 = $response2->json('job_id');

        // Verify both jobs have unique IDs
        $this->assertNotNull($jobId1, 'First job ID should not be null');
        $this->assertNotNull($jobId2, 'Second job ID should not be null');
        $this->assertNotEquals($jobId1, $jobId2, 'Job IDs should be unique');

        // Verify both jobs were dispatched
        Queue::assertPushed(InstallationJob::class, 2);
    }

    /** @test */
    public function installation_validation_errors_prevent_job_dispatch()
    {
        Queue::fake();

        // Send incomplete data (missing required fields)
        $incompleteData = [
            'app_name' => 'Test Kartenant',
            // Missing admin_email, admin_password, db_* fields
        ];

        $response = $this->post('/install/process', $incompleteData);

        $response->assertStatus(200)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['errors']);

        // Verify no job was dispatched due to validation failure
        Queue::assertNotPushed(InstallationJob::class);
    }

    /** @test */
    public function completed_installation_progress_shows_success_state()
    {
        $jobId = 'completed-job-789';

        // Simulate completed installation
        Cache::put("installation_progress_{$jobId}", [
            'step' => 'completed',
            'message' => 'Instalación completada exitosamente',
            'completed' => true,
            'progress' => 100,
            'success' => true,
        ], 300);

        $response = $this->get("/install/progress?job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'step' => 'completed',
                'message' => 'Instalación completada exitosamente',
                'completed' => true,
                'progress' => 100,
                'success' => true,
            ]);
    }

    /** @test */
    public function failed_installation_progress_shows_error_state()
    {
        $jobId = 'failed-job-999';

        // Simulate failed installation
        Cache::put("installation_progress_{$jobId}", [
            'step' => 'error',
            'message' => 'Error durante la instalación',
            'completed' => true,
            'success' => false,
            'error_details' => [
                'file' => '/path/to/file.php',
                'line' => 123,
                'message' => 'Database connection failed',
            ],
        ], 300);

        $response = $this->get("/install/progress?job_id={$jobId}");

        $response->assertStatus(200)
            ->assertJson([
                'step' => 'error',
                'message' => 'Error durante la instalación',
                'completed' => true,
                'success' => false,
            ])
            ->assertJsonStructure([
                'error_details' => ['file', 'line', 'message'],
            ]);
    }

    protected function tearDown(): void
    {
        // Clean up installation lock file
        if (File::exists(base_path('.installed'))) {
            File::delete(base_path('.installed'));
        }

        // Clear cache
        Cache::flush();

        parent::tearDown();
    }
}
