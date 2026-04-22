<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantStatsService;
use App\Services\TenantActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'landlord']);
    }

    /**
     * Test that tenant can be soft deleted
     */
    public function test_tenant_can_be_soft_deleted(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test',
            'database' => 'test_db',
        ]);

        $this->assertNull($tenant->deleted_at);
        $this->assertFalse($tenant->trashed());

        $tenant->delete();

        $this->assertNotNull($tenant->deleted_at);
        $this->assertTrue($tenant->trashed());

        // Tenant should not appear in default queries
        $this->assertEquals(0, Tenant::count());

        // But should appear in withTrashed queries
        $this->assertEquals(1, Tenant::withTrashed()->count());
    }

    /**
     * Test that tenant can be restored
     */
    public function test_tenant_can_be_restored(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test',
            'database' => 'test_db',
        ]);

        $tenant->delete();
        $this->assertTrue($tenant->trashed());

        $tenant->restore();
        $this->assertFalse($tenant->trashed());
        $this->assertNull($tenant->deleted_at);

        // Tenant should appear in default queries again
        $this->assertEquals(1, Tenant::count());
    }

    /**
     * Test tenant status management
     */
    public function test_tenant_status_management(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_TRIAL,
        ]);

        $this->assertEquals(Tenant::STATUS_TRIAL, $tenant->status);
        $this->assertEquals('En Prueba', $tenant->status_label);
        $this->assertEquals('info', $tenant->status_color);

        // Test activation
        $tenant->activate();
        $this->assertEquals(Tenant::STATUS_ACTIVE, $tenant->status);
        $this->assertEquals('Activo', $tenant->status_label);
        $this->assertEquals('success', $tenant->status_color);

        // Test suspension
        $tenant->suspend();
        $this->assertEquals(Tenant::STATUS_SUSPENDED, $tenant->status);
        $this->assertEquals('Suspendido', $tenant->status_label);
        $this->assertEquals('warning', $tenant->status_color);
    }

    /**
     * Test tenant access methods
     */
    public function test_tenant_access_methods(): void
    {
        // Active tenant should have access
        $activeTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $this->assertTrue($activeTenant->isActive());
        $this->assertTrue($activeTenant->canAccess());

        // Trial tenant should have access
        $trialTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);
        $this->assertTrue($trialTenant->isTrial());
        $this->assertTrue($trialTenant->canAccess());

        // Suspended tenant should not have access
        $suspendedTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
        $this->assertFalse($suspendedTenant->canAccess());

        // Expired tenant should not have access
        $expiredTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_EXPIRED,
        ]);
        $this->assertFalse($expiredTenant->canAccess());
    }

    /**
     * Test tenant scopes
     */
    public function test_tenant_scopes(): void
    {
        Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        Tenant::factory()->create(['status' => Tenant::STATUS_TRIAL]);
        Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);
        Tenant::factory()->create(['status' => Tenant::STATUS_INACTIVE]);
        Tenant::factory()->create(['status' => Tenant::STATUS_ARCHIVED]);

        // Test status scopes
        $this->assertEquals(1, Tenant::active()->count());
        $this->assertEquals(1, Tenant::trial()->count());
        $this->assertEquals(1, Tenant::suspended()->count());
        $this->assertEquals(1, Tenant::inactive()->count());
        $this->assertEquals(1, Tenant::archived()->count());

        // Test canAccess scope (active + trial)
        $this->assertEquals(2, Tenant::canAccess()->count());
    }

    /**
     * Test tenant activity logging
     */
    public function test_tenant_activity_logging(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Test activity creation
        $activity = TenantActivityService::log(
            tenant: $tenant,
            action: TenantActivity::ACTION_LOGIN,
            description: 'Test login activity',
            user: $user
        );

        $this->assertNotNull($activity);
        $this->assertEquals($tenant->id, $activity->tenant_id);
        $this->assertEquals($user->id, $activity->user_id);
        $this->assertEquals(TenantActivity::ACTION_LOGIN, $activity->action);

        // Test relationship
        $this->assertEquals(1, $tenant->activities()->count());
    }

    /**
     * Test tenant activity relationships
     */
    public function test_tenant_activity_relationships(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Create multiple activities
        TenantActivityService::logTenantCreated($tenant, $user);
        TenantActivityService::logStatusChange($tenant, 'trial', 'active', $user);

        // Test relationship
        $activities = $tenant->recentActivities()->get();
        $this->assertEquals(2, $activities->count());
        $this->assertEquals($user->id, $activities[0]->user_id);
    }

    /**
     * Test tenant stats service error handling
     */
    public function test_tenant_stats_service_error_handling(): void
    {
        $tenant = Tenant::factory()->create([
            'database' => 'non_existent_database'
        ]);

        $stats = app(TenantStatsService::class)->getTenantStats($tenant);

        // Should return empty stats when database doesn't exist
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['users_count']);
        $this->assertEquals(0, $stats['products_count']);
        $this->assertEquals(0, $stats['sales_count']);
    }

    /**
     * Test tenant observer activity logging
     */
    public function test_tenant_observer_logs_activities(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_TRIAL,
        ]);

        // Observer should have logged creation
        $this->assertEquals(1, $tenant->activities()->count());

        // Change status
        $tenant->activate();

        // Observer should have logged status change
        $this->assertEquals(2, $tenant->activities()->count());

        // Soft delete
        $tenant->delete();

        // Observer should have logged deletion
        $this->assertEquals(3, $tenant->activities()->count());
    }
}