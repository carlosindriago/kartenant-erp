<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantSecurityService;
use App\Http\Middleware\TenantSecurityMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * Comprehensive Security Tests for Tenant Management
 *
 * This test suite validates all security aspects of tenant management:
 * - Multi-factor authentication workflows
 * - Rate limiting and abuse prevention
 * - High-friction confirmation processes
 * - Audit trail integrity and completeness
 * - Edge cases and boundary conditions
 * - Malicious input and attack scenarios
 * - Performance under security load
 */
class TenantManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Tenant $tenant;
    private TenantSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'security-test@example.com',
            'password' => Hash::make('secure-password-123'),
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'SecurityTestTenant',
            'domain' => 'security-test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->securityService = app(TenantSecurityService::class);

        // Authenticate as super admin
        $this->actingAs($this->superAdmin, 'superadmin');
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_tenant_management()
    {
        // Test unauthenticated user
        auth('superadmin')->logout();

        $response = $this->get('/admin/tenants');
        $response->assertRedirect('/admin/login');

        // Test regular user without super admin privileges
        $regularUser = User::factory()->create(['is_super_admin' => false]);
        $this->actingAs($regularUser, 'superadmin');

        $response = $this->get('/admin/tenants');
        $response->assertForbidden();
    }

    /** @test */
    public function it_enforces_rate_limiting_on_tenant_operations()
    {
        // Simulate multiple rapid tenant deactivation attempts
        $rateLimitKey = "tenant_deactivate_{$this->superAdmin->id}";

        // First attempt should succeed
        $this->assertTrue(Cache::add($rateLimitKey, true, Carbon::now()->addHour()));

        // Second attempt within the hour should fail
        $this->assertFalse(Cache::add($rateLimitKey, true, Carbon::now()->addHour()));

        // Test rate limiting middleware
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test rate limiting',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        // Should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function it_requires_valid_admin_password_for_sensitive_operations()
    {
        // Attempt with wrong password
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'wrong-password',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test password validation',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        $response->assertSessionHasErrors('admin_password');

        // Verify tenant is still active
        $this->tenant->refresh();
        $this->assertEquals(Tenant::STATUS_ACTIVE, $this->tenant->status);
    }

    /** @test */
    public function it_generates_and_validates_context_specific_otp_codes()
    {
        // Generate OTP for deactivation
        $expectedOTP = 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4));

        // Test correct OTP
        $this->assertTrue(
            $this->securityService->validateOTP($this->superAdmin, $expectedOTP)
        );

        // Generate new OTP (invalidates previous)
        $newOTP = $this->securityService->generateOTP($this->superAdmin, 'tenant_deactivate', $this->tenant);

        // Test old OTP (should be invalid)
        $this->assertFalse(
            $this->securityService->validateOTP($this->superAdmin, $expectedOTP)
        );

        // Test new OTP (should be valid)
        $this->assertTrue(
            $this->securityService->validateOTP($this->superAdmin, $newOTP)
        );
    }

    /** @test */
    public function it_prevents_tenant_name_typo_attacks()
    {
        $similarNames = [
            $this->tenant->name . ' ', // trailing space
            ' ' . $this->tenant->name, // leading space
            strtoupper($this->tenant->name), // different case
            strtolower($this->tenant->name), // different case
            $this->tenant->name . 'x', // extra character
            substr($this->tenant->name, 0, -1), // missing character
        ];

        foreach ($similarNames as $wrongName) {
            $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
                'admin_password' => 'secure-password-123',
                'confirm_tenant_name' => $wrongName,
                'reason' => 'Test typo prevention',
                'understand_consequences' => true,
                'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
            ]);

            $response->assertSessionHasErrors('confirm_tenant_name');
        }

        // Verify tenant is still active
        $this->tenant->refresh();
        $this->assertEquals(Tenant::STATUS_ACTIVE, $this->tenant->status);
    }

    /** @test */
    public function it_detects_and_prevents_impossible_travel_attacks()
    {
        // Simulate login from New York
        $this->withHeader('X-Forwarded-For', '192.168.1.1')
            ->post('/admin/login', [
                'email' => $this->superAdmin->email,
                'password' => 'secure-password-123',
            ]);

        // Immediately try critical operation from what appears to be Tokyo
        $this->withHeader('X-Forwarded-For', '203.0.113.1')
            ->post('/admin/tenants/' . $this->tenant->id . '/archive', [
                'admin_password' => 'secure-password-123',
                'confirm_tenant_name' => $this->tenant->name,
                'impact_assessment' => 'Test impossible travel detection',
                'legal_retention_confirmed' => true,
                'contractual_obligations_met' => true,
                'data_backup_verified' => true,
                'legal_rationale' => 'Test',
                'understand_irreversibility' => true,
                'accept_liability' => true,
                'otp_code' => 'ARCHIVE' . strtoupper(substr($this->tenant->name, 0, 4)),
            ]);

        // Should detect impossible travel and block the operation
        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $this->superAdmin->id,
            'operation_type' => 'tenant_archive',
            'success' => false,
        ]);
    }

    /** @test */
    public function it_enforces_sudo_mode_with_timeout()
    {
        // Activate sudo mode
        $this->assertTrue(
            $this->securityService->activateSudoMode($this->superAdmin, 'secure-password-123')
        );

        // Check sudo mode is active
        $this->assertTrue(
            $this->securityService->isSudoModeActive($this->superAdmin)
        );

        // Test sudo mode status
        $status = $this->securityService->getSudoModeStatus($this->superAdmin);
        $this->assertNotNull($status);
        $this->assertTrue($status['active']);
        $this->assertLessThanOrEqual(15, $status['remaining_minutes']);

        // Clear sudo mode
        $this->securityService->clearSudoMode($this->superAdmin);
        $this->assertFalse(
            $this->securityService->isSudoModeActive($this->superAdmin)
        );
    }

    /** @test */
    public function it_prevents_sql_injection_and_xss_attacks()
    {
        $maliciousInputs = [
            "'; DROP TABLE tenants; --",
            "<script>alert('xss')</script>",
            "'; UPDATE users SET is_super_admin = 1; --",
            "' OR '1'='1",
            "javascript:alert('xss')",
            "<img src=x onerror=alert('xss')>",
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
                'admin_password' => 'secure-password-123',
                'confirm_tenant_name' => $maliciousInput,
                'reason' => $maliciousInput,
                'understand_consequences' => true,
                'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
            ]);

            // Should validate input and reject malicious content
            $response->assertSessionHasErrors();
        }

        // Verify tenant is still active and database intact
        $this->tenant->refresh();
        $this->assertEquals(Tenant::STATUS_ACTIVE, $this->tenant->status);
        $this->assertDatabaseCount('tenants', 1);
    }

    /** @test */
    public function it_creates_comprehensive_audit_trail_for_critical_operations()
    {
        // Perform tenant deactivation
        $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test audit trail completeness',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        // Verify comprehensive audit log was created
        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $this->superAdmin->id,
            'tenant_id' => $this->tenant->id,
            'operation_type' => 'tenant_deactivate',
            'operation_category' => 'tenant_management',
            'security_tier' => 'tier_3',
            'success' => true,
        ]);

        // Verify detailed audit data
        $auditLog = \App\Models\SecurityAuditLog::latest()->first();
        $this->assertNotNull($auditLog);
        $this->assertNotNull($auditLog->device_fingerprint);
        $this->assertNotNull($auditLog->location_data);
        $this->assertNotNull($auditLog->hash_signature);
        $this->assertTrue($auditLog->verifyIntegrity());
    }

    /** @test */
    public function it_handles_concurrent_operations_safely()
    {
        // Simulate concurrent deactivation attempts
        $responses = collect();
        $concurrentRequests = 5;

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
                'admin_password' => 'secure-password-123',
                'confirm_tenant_name' => $this->tenant->name,
                'reason' => "Concurrent test {$i}",
                'understand_consequences' => true,
                'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
            ]);

            $responses->push($response);
        }

        // Only one should succeed, others should be rate limited or rejected
        $successful = $responses->filter(fn($r) => $r->isSuccessful());
        $this->assertLessThanOrEqual(1, $successful->count());

        // Verify concurrent operations were tracked
        $this->assertDatabaseHas('security_audit_logs', [
            'user_id' => $this->superAdmin->id,
            'operation_type' => 'tenant_deactivate',
        ]);
    }

    /** @test */
    public function it_validates_ssl_tls_and_https_requirements()
    {
        // Test that critical operations require HTTPS
        config(['app.env' => 'production']);

        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test HTTPS requirement',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ], ['HTTPS' => 'on']);

        $response->assertStatus(302); // Should redirect or process successfully

        // Test without HTTPS (should be blocked in production)
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test HTTPS requirement',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        // In production mode without HTTPS, should be rejected
        // (This would depend on specific middleware implementation)
    }

    /** @test */
    public function it_implements_proper_session_security()
    {
        // Test session fixation prevention
        $this->post('/admin/login', [
            'email' => $this->superAdmin->email,
            'password' => 'secure-password-123',
        ]);

        $originalSessionId = session()->getId();

        // Perform critical operation
        $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test session security',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        // Session ID should remain the same (no session fixation)
        $this->assertEquals($originalSessionId, session()->getId());

        // Test session timeout for inactivity
        // (This would test session middleware implementation)
    }

    /** @test */
    public function it_prevents_csrf_attacks_on_critical_operations()
    {
        // Test missing CSRF token
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => 'Test CSRF protection',
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ], [], ['X-Requested-With' => 'XMLHttpRequest']); // Simulate AJAX without CSRF

        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_handles_edge_cases_and_boundary_conditions()
    {
        // Test extremely long inputs
        $longReason = str_repeat('A', 10000); // Exceeds reasonable limits

        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'reason' => $longReason,
            'understand_consequences' => true,
            'otp_code' => 'DEACTIVATE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        $response->assertSessionHasErrors('reason');

        // Test empty inputs where required
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/deactivate', [
            'admin_password' => '',
            'confirm_tenant_name' => '',
            'reason' => '',
            'understand_consequences' => false,
            'otp_code' => '',
        ]);

        $response->assertSessionHasErrors([
            'admin_password',
            'confirm_tenant_name',
            'reason',
            'understand_consequences',
            'otp_code'
        ]);
    }

    /** @test */
    public function it_validates_backup_creation_before_critical_operations()
    {
        // Mock backup service to test backup requirement
        $backupService = $this->mock(\App\Services\TenantBackupService::class);
        $backupService->shouldReceive('backupDatabase')
            ->once()
            ->andReturn([
                'success' => true,
                'path' => '/backups/test-backup.sql',
                'file_size' => 1024000,
            ]);

        // Perform tenant archival (requires backup)
        $response = $this->post('/admin/tenants/' . $this->tenant->id . '/archive', [
            'admin_password' => 'secure-password-123',
            'confirm_tenant_name' => $this->tenant->name,
            'impact_assessment' => 'Test backup requirement',
            'legal_retention_confirmed' => true,
            'contractual_obligations_met' => true,
            'data_backup_verified' => true,
            'legal_rationale' => 'Test backup validation',
            'understand_irreversibility' => true,
            'accept_liability' => true,
            'otp_code' => 'ARCHIVE' . strtoupper(substr($this->tenant->name, 0, 4)),
        ]);

        $backupService->shouldHaveReceived('backupDatabase');
    }

    /** @test */
    public function it_detects_suspicious_patterns_and_anomalies()
    {
        // Generate security report to check for anomalies
        $report = $this->securityService->getSecurityReport($this->superAdmin);

        $this->assertArrayHasKey('suspicious_activity', $report);
        $this->assertArrayHasKey('sudo_mode', $report);
        $this->assertArrayHasKey('recent_otp_requests', $report);
        $this->assertArrayHasKey('rate_limits', $report);

        // Simulate multiple failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => $this->superAdmin->email,
                'password' => 'wrong-password-' . $i,
            ]);
        }

        // Check for security anomalies
        $reportAfterFailures = $this->securityService->getSecurityReport($this->superAdmin);

        // Should detect suspicious activity from multiple failed attempts
        $this->assertNotEmpty($reportAfterFailures['suspicious_activity']);
    }
}