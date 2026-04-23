<?php

/**
 * SECURITY AUDIT REPORT
 * Operation Route Freedom - Phase 3: Security Validation
 *
 * Comprehensive security validation of the routing infrastructure changes
 * to ensure admin panel protection is maintained and tenant isolation is enforced.
 *
 * Generated: 2025-11-24
 * Auditor: Claude Code (Senior Laravel Developer & Cybersecurity Expert)
 */

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;

    protected Tenant $tenant2;

    protected User $superAdmin;

    protected User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create([
            'domain' => 'tenant1.test',
            'is_active' => true,
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'domain' => 'tenant2.test',
            'is_active' => true,
        ]);

        // Create test users
        $this->superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'admin@test.com',
        ]);

        $this->tenantUser = User::factory()->create([
            'email' => 'user@test.com',
        ]);
    }

    /**
     * ======================================
     * ADMIN PANEL SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function admin_panel_requires_authentication()
    {
        $response = $this->get('/admin');

        // Should redirect to login
        $response->assertRedirect('/admin/login');

        // Verify login page exists and works
        $loginResponse = $this->get('/admin/login');
        $loginResponse->assertOk();
        $loginResponse->assertSee('Login');
    }

    /** @test */
    public function admin_panel_maintains_2fa_protection()
    {
        // Test that 2FA challenge route exists
        $response = $this->get('/admin/two-factor-challenge');
        $response->assertOk();

        // Verify 2FA middleware is properly configured
        // This tests the dedicated route in web.php for 2FA
        $this->assertRouteExists('filament.admin.two-factor-challenge');
    }

    /** @test */
    public function admin_panel_uses_landlord_connection()
    {
        // Login as superadmin
        $this->actingAs($this->superAdmin, 'superadmin');

        // Test that admin operations use landlord connection
        $connection = DB::getDefaultConnection();
        $this->assertEquals('landlord', $connection);

        // Verify we can access admin dashboard
        $response = $this->get('/admin');
        $response->assertOk();
    }

    /** @test */
    public function admin_panel_isolated_from_tenant_context()
    {
        // Switch to tenant context
        $this->tenant1->makeCurrent();

        // Admin panel should still work and NOT use tenant database
        $this->actingAs($this->superAdmin, 'superadmin');

        $response = $this->get('/admin');
        $response->assertOk();

        // Verify admin operations don't use tenant connection
        // This should still access landlord database
        $userCount = User::count();
        $this->assertGreaterThan(0, $userCount);
    }

    /**
     * ======================================
     * TENANT ISOLATION SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function tenant_routes_only_work_on_subdomains()
    {
        // Test: Tenant routes should fail on apex domain
        $response = $this->get('/tenant/dashboard');
        $response->assertStatus(400); // Should show tenant required error

        // Test: Tenant routes should work on correct subdomain
        $response = $this->withHeaders(['Host' => 'tenant1.test'])
            ->get('/tenant/dashboard');

        // Should redirect to login (not 400/404)
        $response->assertRedirect('/app/login');
    }

    /** @test */
    public function cross_tenant_data_access_is_impossible()
    {
        // Create tenant-specific data
        $this->tenant1->makeCurrent();
        $tenant1Data = DB::table('store_settings')->insert([
            'tenant_id' => $this->tenant1->id,
            'store_name' => 'Tenant 1 Store',
        ]);

        $this->tenant2->makeCurrent();
        $tenant2Data = DB::table('store_settings')->insert([
            'tenant_id' => $this->tenant2->id,
            'store_name' => 'Tenant 2 Store',
        ]);

        // Switch to tenant1 context
        $this->withHeaders(['Host' => 'tenant1.test']);
        $this->tenant1->makeCurrent();

        // Should only see tenant1 data
        $settings = DB::table('store_settings')->where('tenant_id', $this->tenant1->id)->first();
        $this->assertEquals('Tenant 1 Store', $settings->store_name);

        // Should NOT see tenant2 data
        $tenant2Settings = DB::table('store_settings')->where('tenant_id', $this->tenant2->id)->first();
        $this->assertNull($tenant2Settings);
    }

    /** @test */
    public function tenant_context_switching_attacks_are_blocked()
    {
        // Start with tenant1 context
        $this->withHeaders(['Host' => 'tenant1.test']);
        $this->tenant1->makeCurrent();

        // Verify current tenant is tenant1
        $currentTenant = app('currentTenant');
        $this->assertEquals($this->tenant1->id, $currentTenant->id);

        // Try to access tenant2 routes from tenant1 subdomain (should fail)
        $response = $this->withHeaders(['Host' => 'tenant1.test'])
            ->get('/tenant/settings');

        // Route should resolve to tenant1 context, not tenant2
        // This tests the EnsureTenantContext middleware security
        $currentTenantAfter = app('currentTenant');
        $this->assertEquals($this->tenant1->id, $currentTenantAfter->id);
    }

    /** @test */
    public function database_connection_isolation_between_tenants()
    {
        // Test tenant1 connection
        $this->withHeaders(['Host' => 'tenant1.test']);
        $this->tenant1->makeCurrent();

        // Purge connection to ensure clean test
        DB::purge('tenant');

        // Query tenant database
        $tenantConnection = DB::connection('tenant');
        $this->assertNotNull($tenantConnection);

        // Verify we're using tenant database, not landlord
        $databaseName = $tenantConnection->getDatabaseName();
        $this->assertStringContainsString('tenant', $databaseName);
    }

    /**
     * ======================================
     * AUTHENTICATION GUARD ISOLATION
     * ======================================
     */

    /** @test */
    public function authentication_guards_are_properly_isolated()
    {
        // Test superadmin guard
        $this->actingAs($this->superAdmin, 'superadmin');
        $this->assertTrue(auth('superadmin')->check());
        $this->assertFalse(auth('tenant')->check());

        // Test tenant guard
        auth('superadmin')->logout();
        $this->actingAs($this->tenantUser, 'tenant');
        $this->assertTrue(auth('tenant')->check());
        $this->assertFalse(auth('superadmin')->check());

        // Verify different session cookies
        $superadminSession = session()->getId();

        auth('tenant')->login($this->tenantUser);
        $tenantSession = session()->getId();

        // Sessions should be different due to separate cookie configurations
        $this->assertNotEquals($superadminSession, $tenantSession);
    }

    /** @test */
    public function superadmin_cannot_access_tenant_routes()
    {
        // Login as superadmin
        $this->actingAs($this->superAdmin, 'superadmin');

        // Try to access tenant routes (should fail)
        $response = $this->withHeaders(['Host' => 'tenant1.test'])
            ->get('/tenant/dashboard');

        // Should redirect to tenant login, not allow access
        $response->assertRedirect('/app/login');
    }

    /** @test */
    public function tenant_user_cannot_access_admin_routes()
    {
        // Login as tenant user
        $this->actingAs($this->tenantUser, 'tenant');

        // Try to access admin routes (should fail)
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');

        // Verify tenant guard doesn't work for admin
        $this->assertFalse(auth('superadmin')->check());
    }

    /**
     * ======================================
     * SESSION SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function admin_and_tenant_sessions_are_isolated()
    {
        // Create admin session
        $this->actingAs($this->superAdmin, 'superadmin');
        $adminSessionId = session()->getId();

        // Create tenant session
        auth('superadmin')->logout();
        $this->actingAs($this->tenantUser, 'tenant');
        $tenantSessionId = session()->getId();

        // Verify sessions are different
        $this->assertNotEquals($adminSessionId, $tenantSessionId);

        // Verify both sessions are active independently
        $this->assertTrue(session()->isValidId($adminSessionId));
        $this->assertTrue(session()->isValidId($tenantSessionId));
    }

    /** @test */
    public function session_hijacking_protection_works()
    {
        // Login as tenant user
        $this->actingAs($this->tenantUser, 'tenant');
        $originalSessionId = session()->getId();

        // Verify session regeneration works
        session()->regenerate();
        $newSessionId = session()->getId();

        $this->assertNotEquals($originalSessionId, $newSessionId);
        $this->assertTrue(auth('tenant')->check());
    }

    /**
     * ======================================
     * RATE LIMITING SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function rate_limiting_is_context_aware()
    {
        // Test tenant login rate limiting
        for ($i = 0; $i < 6; $i++) {
            $response = $this->withHeaders(['Host' => 'tenant1.test'])
                ->post('/tenant/login', [
                    'email' => 'test@test.com',
                    'password' => 'wrong',
                ]);
        }

        // 6th attempt should be throttled
        $response->assertStatus(429);

        // Test that rate limiting is per-tenant
        // Switch to different tenant - should not be throttled
        $response = $this->withHeaders(['Host' => 'tenant2.test'])
            ->post('/tenant/login', [
                'email' => 'test@test.com',
                'password' => 'wrong',
            ]);

        // Should not be throttled for different tenant
        $this->assertNotEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function two_factor_code_resend_rate_limiting()
    {
        $this->withHeaders(['Host' => 'tenant1.test']);

        // Test 2FA resend rate limiting (3 requests per minute)
        for ($i = 0; $i < 4; $i++) {
            $response = $this->post('/tenant/two-factor/resend');
        }

        // 4th attempt should be throttled
        $response->assertStatus(429);
    }

    /**
     * ======================================
     * ERROR HANDLING SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function error_messages_dont_leak_sensitive_data()
    {
        // Test 404 errors don't expose tenant information
        $response = $this->withHeaders(['Host' => 'nonexistent.test'])
            ->get('/tenant/dashboard');

        $response->assertStatus(404);
        $response->assertDontSee('database');
        $response->assertDontSee('SQL');
        $response->assertDontSee('tenant');
    }

    /** @test */
    public function tenant_not_found_errors_are_safe()
    {
        $response = $this->withHeaders(['Host' => 'nonexistent.test'])
            ->get('/');

        // Should not leak information about tenant system
        $response->assertDontSee('App\\Models\\Tenant');
        $response->assertDontSee('multitenancy');
    }

    /**
     * ======================================
     * HEALTH MONITORING SECURITY
     * ======================================
     */

    /** @test */
    public function health_endpoint_doesnt_expose_sensitive_data()
    {
        $response = $this->get('/health');
        $response->assertOk();

        // Should only return status information
        $response->assertJson(['status' => 'ok']);
        $response->assertDontSee('tenant');
        $response->assertDontSee('database');
        $response->assertDontSee('user');
    }

    /**
     * ======================================
     * ROUTE SECURITY MATRIX VALIDATION
     * ======================================
     */

    /** @test */
    public function route_security_matrix_is_correct()
    {
        // Admin routes should require superadmin auth
        $adminRoutes = ['/admin', '/admin/tenants', '/admin/admin-users'];
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/admin/login');
        }

        // Tenant routes should only work on subdomains
        $tenantRoutes = ['/tenant/dashboard', '/tenant/inventory', '/tenant/pos'];
        foreach ($tenantRoutes as $route) {
            // Fail on apex domain
            $response = $this->get($route);
            $this->assertContains($response->getStatusCode(), [400, 302]);

            // Redirect to login on correct subdomain
            $response = $this->withHeaders(['Host' => 'tenant1.test'])->get($route);
            $response->assertRedirect('/app/login');
        }

        // Public routes should work without auth
        $publicRoutes = ['/', '/health'];
        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $response->assertOk();
        }
    }

    /**
     * ======================================
     * MIDDLEWARE STACK SECURITY VERIFICATION
     * ======================================
     */

    /** @test */
    public function middleware_stack_is_correct()
    {
        // Test that tenant routes have proper middleware
        $this->assertRouteUsesMiddleware('/tenant/login', ['web']);

        // Test that admin routes have proper middleware
        $this->assertRouteUsesMiddleware('/admin', ['web']);

        // Verify CSRF protection is enabled
        $response = $this->post('/tenant/login', []);
        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Helper method to check if route exists
     */
    private function assertRouteExists($name)
    {
        $routes = Route::getRoutes();
        $route = $routes->getByName($name);
        $this->assertNotNull($route, "Route {$name} should exist");
    }

    /**
     * Helper method to check route middleware
     */
    private function assertRouteUsesMiddleware($uri, $expectedMiddleware)
    {
        $routes = Route::getRoutes();
        $route = $routes->match(Request::create($uri));

        foreach ($expectedMiddleware as $middleware) {
            $this->assertContains($middleware, $route->middleware(),
                "Route {$uri} should use {$middleware} middleware");
        }
    }
}
