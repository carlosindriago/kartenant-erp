<?php

namespace Tests\Feature;

use App\Filament\Widgets\SubscriptionAlertsWidget;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SubscriptionAlertsWidgetTest extends TestCase
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
        ]);

        // Create subscription plan for testing
        $this->plan = SubscriptionPlan::factory()->create([
            'name' => 'Test Plan',
            'is_active' => true,
        ]);

        // Mock the filament context for testing
        Auth::guard('superadmin')->login($this->superadmin);
    }

    protected function tearDown(): void
    {
        Auth::guard('superadmin')->logout();
        parent::tearDown();
    }

    /** @test */
    public function widget_can_be_instantiated()
    {
        $widget = new SubscriptionAlertsWidget;

        $this->assertInstanceOf(SubscriptionAlertsWidget::class, $widget);
        $this->assertEquals('filament.widgets.subscription-alerts', $widget->getView());
        $this->assertEquals('full', $widget->getColumnSpan());
        $this->assertEquals(-10, $widget->getSort());
    }

    /** @test */
    public function widget_can_view_only_in_admin_panel()
    {
        $widget = new SubscriptionAlertsWidget;

        // Test with admin panel context
        $this->assertTrue($widget->canView());

        // The widget should be configured to only show in admin panel
        $this->assertStringContainsString('admin', $widget->getView());
    }

    /** @test */
    public function widget_data_returns_correct_structure()
    {
        $widget = new SubscriptionAlertsWidget;
        $data = $widget->getData();

        // Verify data structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('expired', $data);
        $this->assertArrayHasKey('expiring_soon', $data);
        $this->assertArrayHasKey('suspended', $data);
        $this->assertArrayHasKey('cancelled', $data);
        $this->assertArrayHasKey('no_subscription', $data);
        $this->assertArrayHasKey('total_issues', $data);
        $this->assertArrayHasKey('has_critical_issues', $data);

        // Verify collection types
        $this->assertInstanceOf(Collection::class, $data['expired']);
        $this->assertInstanceOf(Collection::class, $data['expiring_soon']);
        $this->assertInstanceOf(Collection::class, $data['suspended']);
        $this->assertInstanceOf(Collection::class, $data['cancelled']);
        $this->assertInstanceOf(Collection::class, $data['no_subscription']);

        // Verify numeric types
        $this->assertIsInt($data['total_issues']);
        $this->assertIsBool($data['has_critical_issues']);
    }

    /** @test */
    public function widget_data_identifies_expired_subscriptions()
    {
        // Create tenant with expired subscription
        $expiredTenant = Tenant::factory()->create([
            'name' => 'Expired Tenant',
            'domain' => 'expired',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiredTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(5),
        ]);

        $widget = new SubscriptionAlertsWidget;
        $data = $widget->getData();

        $this->assertGreaterThan(0, $data['expired']->count());
        $this->assertTrue($data['expired']->contains($expiredTenant));
        $this->assertGreaterThan(0, $data['total_issues']);
        $this->assertTrue($data['has_critical_issues']);
    }

    /** @test */
    public function widget_data_identifies_expiring_soon_subscriptions()
    {
        // Create tenant with subscription expiring in 3 days
        $expiringTenant = Tenant::factory()->create([
            'name' => 'Expiring Soon Tenant',
            'domain' => 'expiring',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiringTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(3),
        ]);

        $widget = new SubscriptionAlertsWidget;
        $data = $widget->getData();

        $this->assertGreaterThan(0, $data['expiring_soon']->count());
        $this->assertTrue($data['expiring_soon']->contains($expiringTenant));
        $this->assertGreaterThan(0, $data['total_issues']);
    }

    /** @test */
    public function widget_data_identifies_suspended_subscriptions()
    {
        // Create tenant with suspended subscription
        $suspendedTenant = Tenant::factory()->create([
            'name' => 'Suspended Tenant',
            'domain' => 'suspended',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $suspendedTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'suspended',
            'ends_at' => now()->addDays(10),
        ]);

        $widget = new SubscriptionAlertsWidget;
        $data = $widget->getData();

        $this->assertGreaterThan(0, $data['suspended']->count());
        $this->assertTrue($data['suspended']->contains($suspendedTenant));
        $this->assertGreaterThan(0, $data['total_issues']);
        $this->assertTrue($data['has_critical_issues']);
    }

    /** @test */
    public function widget_data_includes_relationships()
    {
        // Create tenant with subscription and plan
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        TenantSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
        ]);

        $widget = new SubscriptionAlertsWidget;
        $data = $widget->getData();

        $tenantWithData = $data['expired']->first();

        // Verify relationships are loaded
        $this->assertTrue($tenantWithData->relationLoaded('activeSubscription'));
        $this->assertNotNull($tenantWithData->activeSubscription);
        $this->assertTrue($tenantWithData->activeSubscription->relationLoaded('plan'));
        $this->assertNotNull($tenantWithData->activeSubscription->plan);
        $this->assertEquals($this->plan->name, $tenantWithData->activeSubscription->plan->name);
    }
}
