<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantArchiveOTPSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    private TenantSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@emporiodigital.test',
            'is_super_admin' => true,
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test',
        ]);

        $this->securityService = app(TenantSecurityService::class);
    }

    /**
     * Test OTP generation for archive operation
     */
    public function test_can_generate_archive_otp(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $this->assertArrayHasKey('otp_code', $otpData);
        $this->assertArrayHasKey('context_code', $otpData);
        $this->assertArrayHasKey('email_token', $otpData);
        $this->assertArrayHasKey('expires_at', $otpData);
        $this->assertArrayHasKey('max_attempts', $otpData);

        // Check OTP code is 6 digits
        $this->assertEquals(6, strlen($otpData['otp_code']));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otpData['otp_code']);

        // Check context code format
        $expectedContextCode = 'ARCHIVETEST';
        $this->assertEquals($expectedContextCode, $otpData['context_code']);

        // Check email token is generated
        $this->assertNotEmpty($otpData['email_token']);
        $this->assertEquals(32, strlen($otpData['email_token']));

        // Check attempts limit
        $this->assertEquals(3, $otpData['max_attempts']);
    }

    /**
     * Test OTP validation with correct code
     */
    public function test_can_validate_correct_archive_otp(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            $otpData['otp_code'],
            $this->tenant
        );

        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('attempts_remaining', $result);
    }

    /**
     * Test OTP validation with context code
     */
    public function test_can_validate_archive_context_code(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            $otpData['context_code'],
            $this->tenant
        );

        $this->assertTrue($result['valid']);
    }

    /**
     * Test OTP validation with incorrect code
     */
    public function test_cannot_validate_incorrect_archive_otp(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            '999999',
            $this->tenant
        );

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('attempts_remaining', $result);
        $this->assertEquals(2, $result['attempts_remaining']); // 3 - 1 attempt used
    }

    /**
     * Test OTP validation with expired code
     */
    public function test_cannot_validate_expired_archive_otp(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        // Simulate OTP expiration by manually clearing cache
        Cache::forget("archive_otp_{$this->admin->id}");

        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            $otpData['otp_code'],
            $this->tenant
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContains('expirado', $result['error']);
    }

    /**
     * Test OTP validation with maximum attempts exceeded
     */
    public function test_cannot_validate_archive_otp_after_max_attempts(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        // Use all attempts
        for ($i = 0; $i < 3; $i++) {
            $this->securityService->validateArchiveOTP(
                $this->admin,
                '999999', // Incorrect code
                $this->tenant
            );
        }

        // Fourth attempt should fail
        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            $otpData['otp_code'], // Now using correct code
            $this->tenant
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContains('Demasiados intentos', $result['error']);
    }

    /**
     * Test OTP validation with tenant mismatch
     */
    public function test_cannot_validate_archive_otp_for_different_tenant(): void
    {
        $otherTenant = Tenant::factory()->create(['name' => 'Other Tenant']);

        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $result = $this->securityService->validateArchiveOTP(
            $this->admin,
            $otpData['otp_code'],
            $otherTenant
        );

        $this->assertFalse($result['valid']);
        $this->assertStringContains('válido para esta tienda', $result['error']);
    }

    /**
     * Test email token generation and validation
     */
    public function test_can_generate_and_validate_email_token(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);
        $emailToken = $otpData['email_token'];

        $isValid = $this->securityService->validateArchiveEmailToken(
            $this->admin,
            $emailToken,
            $this->tenant
        );

        $this->assertTrue($isValid);
    }

    /**
     * Test email token validation with incorrect token
     */
    public function test_cannot_validate_incorrect_email_token(): void
    {
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $isValid = $this->securityService->validateArchiveEmailToken(
            $this->admin,
            'incorrect_token',
            $this->tenant
        );

        $this->assertFalse($isValid);
    }

    /**
     * Test pending OTP status
     */
    public function test_can_check_pending_otp_status(): void
    {
        // No OTP generated initially
        $pending = $this->securityService->hasPendingArchiveOTP($this->admin);
        $this->assertNull($pending);

        // Generate OTP
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        // Check pending status
        $pending = $this->securityService->hasPendingArchiveOTP($this->admin);
        $this->assertNotNull($pending);
        $this->assertTrue($pending['has_pending']);
        $this->assertArrayHasKey('expires_at', $pending);
        $this->assertArrayHasKey('attempts_remaining', $pending);
        $this->assertEquals(3, $pending['attempts_remaining']);
    }

    /**
     * Test rate limiting for OTP generation
     */
    public function test_otp_generation_rate_limiting(): void
    {
        // Generate first OTP (should succeed)
        $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        // Attempt to generate second OTP (should fail due to rate limiting)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Límite de archivado alcanzado. Solo se permite 1 archivado cada 24 horas.');

        $this->securityService->generateArchiveOTP($this->admin, $this->tenant);
    }

    /**
     * Test archive operation rate limiting
     */
    public function test_archive_operation_rate_limiting(): void
    {
        // Generate OTP
        $otpData = $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        // Validate OTP successfully (this should set archive operation lock)
        $this->securityService->validateArchiveOTP($this->admin, $otpData['otp_code'], $this->tenant);

        // Attempt to generate another OTP for any tenant (should fail)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo puedes realizar una operación de archivado cada 24 horas.');

        $this->securityService->generateArchiveOTP($this->admin, $this->tenant);
    }

    /**
     * Test OTP generation route
     */
    public function test_otp_generation_route(): void
    {
        $this->actingAs($this->admin, 'superadmin');

        $response = $this->postJson('/admin/tenants/generate-archive-otp', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'context_code',
            'expires_at',
            'max_attempts',
        ]);

        // In non-production environment, should include OTP code and email token
        if (config('app.env') !== 'production') {
            $response->assertJsonStructure([
                'otp_code',
                'email_token',
            ]);
        }
    }

    /**
     * Test OTP validation route
     */
    public function test_otp_validation_route(): void
    {
        $this->actingAs($this->admin, 'superadmin');

        // Generate OTP first
        $this->securityService->generateArchiveOTP($this->admin, $this->tenant);

        $response = $this->postJson('/admin/tenants/validate-archive-otp', [
            'tenant_id' => $this->tenant->id,
            'otp_code' => '999999', // Invalid code
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'attempts_remaining',
        ]);
    }

    /**
     * Test OTP status route
     */
    public function test_otp_status_route(): void
    {
        $this->actingAs($this->admin, 'superadmin');

        $response = $this->getJson('/admin/tenants/archive-otp-status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'has_pending',
            'data',
        ]);

        $response->assertJson([
            'success' => true,
            'has_pending' => false,
        ]);
    }

    /**
     * Test route authentication requirements
     */
    public function test_routes_require_superadmin_authentication(): void
    {
        // Test without authentication
        $response = $this->postJson('/admin/tenants/generate-archive-otp', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(401);

        // Test with regular user (not superadmin)
        $regularUser = User::factory()->create(['is_super_admin' => false]);
        $this->actingAs($regularUser, 'superadmin');

        $response = $this->postJson('/admin/tenants/generate-archive-otp', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(403);
    }
}
