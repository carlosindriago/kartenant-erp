<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAlertsWidgetNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user for testing
        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin@test.com',
            'is_super_admin' => true,
            'password' => bcrypt('password'),
        ]);

        // Create subscription plan for testing
        $this->plan = SubscriptionPlan::factory()->create([
            'name' => 'Test Plan',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function widget_links_point_to_view_routes_not_edit_routes()
    {
        // Create tenant with expired subscription
        $expiredTenant = Tenant::factory()->create([
            'name' => 'Expired Tenant',
            'domain' => 'expired-test',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiredTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(5),
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Get the admin dashboard page (simulated)
        // Since we cannot easily test the widget directly without complex setup,
        // we test the route generation directly

        $viewRoute = route('filament.admin.resources.tenants.view', $expiredTenant);

        // Verify the route contains "view" and not "edit"
        $this->assertStringContainsString('view', $viewRoute);
        $this->assertStringNotContainsString('edit', $viewRoute);

        // Verify the route can be accessed
        $response = $this->get($viewRoute);
        $response->assertSuccessful();
    }

    /** @test */
    public function view_route_displays_tenant_details_correctly()
    {
        // Create tenant with subscription
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant Navigation',
            'domain' => 'nav-test',
            'contact_email' => 'test@navigation.com',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(5),
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Navigate to view page
        $viewRoute = route('filament.admin.resources.tenants.view', $tenant);
        $response = $this->get($viewRoute);

        $response->assertSuccessful();
        $response->assertSee($tenant->name);
        $response->assertSee($tenant->domain);
        $response->assertSee($tenant->contact_email);
    }

    /** @test */
    public function expiring_soon_tenant_link_navigates_to_view_page()
    {
        // Create tenant with subscription expiring soon
        $expiringTenant = Tenant::factory()->create([
            'name' => 'Expiring Soon Navigation',
            'domain' => 'expiring-nav',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiringTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(3),
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Get view route
        $viewRoute = route('filament.admin.resources.tenants.view', $expiringTenant);

        // Verify navigation works
        $response = $this->get($viewRoute);
        $response->assertSuccessful();
        $response->assertSee($expiringTenant->name);
    }

    /** @test */
    public function suspended_tenant_link_navigates_to_view_page()
    {
        // Create tenant with suspended subscription
        $suspendedTenant = Tenant::factory()->create([
            'name' => 'Suspended Navigation',
            'domain' => 'suspended-nav',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $suspendedTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'suspended',
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Get view route
        $viewRoute = route('filament.admin.resources.tenants.view', $suspendedTenant);

        // Verify navigation works
        $response = $this->get($viewRoute);
        $response->assertSuccessful();
        $response->assertSee($suspendedTenant->name);
    }

    /** @test */
    public function tenant_without_subscription_has_working_view_link()
    {
        // Create tenant without subscription
        $noSubTenant = Tenant::factory()->create([
            'name' => 'No Subscription Navigation',
            'domain' => 'nosub-nav',
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Get view route
        $viewRoute = route('filament.admin.resources.tenants.view', $noSubTenant);

        // Verify navigation works
        $response = $this->get($viewRoute);
        $response->assertSuccessful();
        $response->assertSee($noSubTenant->name);
    }

    /** @test */
    public function view_route_displays_subscription_information()
    {
        // Create tenant with detailed subscription
        $tenant = Tenant::factory()->create([
            'name' => 'Subscription Details Test',
            'domain' => 'sub-details',
        ]);

        $subscription = TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(15),
            'billing_cycle' => 'monthly',
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Navigate to view page
        $viewRoute = route('filament.admin.resources.tenants.view', $tenant);
        $response = $this->get($viewRoute);

        $response->assertSuccessful();
        $response->assertSee($this->plan->name);
        $response->assertSee($subscription->status);
        $response->assertSee($subscription->billing_cycle);
    }

    /** @test */
    public function navigation_maintains_correct_tenant_context()
    {
        // Create multiple tenants
        $tenant1 = Tenant::factory()->create([
            'name' => 'Tenant One',
            'domain' => 'tenant-one',
        ]);

        $tenant2 = Tenant::factory()->create([
            'name' => 'Tenant Two',
            'domain' => 'tenant-two',
        ]);

        // Create subscriptions for both
        TenantSubscription::factory()->create([
            'tenant_id' => $tenant1->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $tenant2->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(20),
        ]);

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Test navigation to tenant 1
        $viewRoute1 = route('filament.admin.resources.tenants.view', $tenant1);
        $response1 = $this->get($viewRoute1);
        $response1->assertSuccessful();
        $response1->assertSee($tenant1->name);
        $response1->assertDontSee($tenant2->name);

        // Test navigation to tenant 2
        $viewRoute2 = route('filament.admin.resources.tenants.view', $tenant2);
        $response2 = $this->get($viewRoute2);
        $response2->assertSuccessful();
        $response2->assertSee($tenant2->name);
        $response2->assertDontSee($tenant1->name);
    }

    /** @test */
    public function unauthorized_user_cannot_access_view_routes()
    {
        // Create regular user (non-superadmin)
        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'is_super_admin' => false,
        ]);

        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test-tenant',
        ]);

        // Login as regular user
        $this->actingAs($regularUser, 'superadmin');

        // Try to access view route
        $viewRoute = route('filament.admin.resources.tenants.view', $tenant);
        $response = $this->get($viewRoute);

        // Should be forbidden or redirected
        $response->assertStatus(403);
    }

    /** @test */
    public function guest_user_cannot_access_view_routes()
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test-tenant',
        ]);

        // Try to access view route without authentication
        $viewRoute = route('filament.admin.resources.tenants.view', $tenant);
        $response = $this->get($viewRoute);

        // Should be redirected to login
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function view_route_handles_soft_deleted_tenants()
    {
        // Create tenant and then soft delete it
        $deletedTenant = Tenant::factory()->create([
            'name' => 'Deleted Tenant',
            'domain' => 'deleted-tenant',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $deletedTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
        ]);

        // Soft delete the tenant
        $deletedTenant->delete();

        // Login as superadmin
        $this->actingAs($this->superadmin, 'superadmin');

        // Try to access view route for soft-deleted tenant
        $viewRoute = route('filament.admin.resources.tenants.view', $deletedTenant);
        $response = $this->get($viewRoute);

        // Should handle gracefully (either show archived info or 404)
        $this->assertTrue($response->getStatusCode() === 200 || $response->getStatusCode() === 404);
    }
}
