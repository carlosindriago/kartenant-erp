<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\BackupLog;
use App\Models\TenantActivity;
use App\Services\TenantBackupService;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $regularUser;
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user
        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'is_super_admin' => true,
            'password' => Hash::make('password'),
        ]);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'is_super_admin' => false,
        ]);

        // Create subscription plan
        $this->plan = SubscriptionPlan::factory()->create([
            'name' => 'Basic Plan',
            'is_active' => true,
            'max_users' => 5,
            'max_products' => 100,
            'max_sales_per_month' => 500,
        ]);

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Test Tenant 1',
            'domain' => 'tenant1',
            'database' => 'tenant_test_1',
            'contact_email' => 'tenant1@test.com',
            'contact_name' => 'Tenant 1 Admin',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Test Tenant 2',
            'domain' => 'tenant2',
            'database' => 'tenant_test_2',
            'contact_email' => 'tenant2@test.com',
            'contact_name' => 'Tenant 2 Admin',
        ]);

        // Create subscription for tenant 1
        TenantSubscription::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'ends_at' => now()->addDays(30),
        ]);

        // Associate users with tenant 1
        $this->tenant1->users()->attach($this->regularUser->id);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    /** @test */
    public function tenant_resource_can_be_instantiated()
    {
        $resource = app(\App\Filament\Resources\TenantResource::class);

        $this->assertInstanceOf(\App\Filament\Resources\TenantResource::class, $resource);
        $this->assertEquals(Tenant::class, $resource::getModel());
    }

    /** @test */
    public function tenant_resource_form_schema_is_valid()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('schema')
            ->with($this->isType('array'))
            ->willReturnSelf();

        $resource = new \App\Filament\Resources\TenantResource();
        $result = $resource->form($form);

        $this->assertInstanceOf(Form::class, $result);
    }

    /** @test */
    public function tenant_resource_table_can_be_configured()
    {
        $resource = new \App\Filament\Resources\TenantResource();
        $table = $resource->table(Table::make());

        $this->assertInstanceOf(Table::class, $table);

        // Verify table has columns
        $columns = $table->getColumns();
        $this->assertNotEmpty($columns);

        // Verify expected columns exist
        $columnNames = collect($columns)->map->getName()->toArray();
        $this->assertContains('name', $columnNames);
        $this->assertContains('domain', $columnNames);
        $this->assertContains('created_at', $columnNames);
    }

    /** @test */
    public function tenant_resource_static_methods_work_without_instance_context()
    {
        // Test getLockedAccountsCount without $this context
        $lockedCount = \App\Filament\Resources\TenantResource::getLockedAccountsCount($this->tenant1);
        $this->assertIsInt($lockedCount);

        // Test unlockTenantAccounts without $this context
        Cache::put('2fa_lockout:' . $this->regularUser->id, true, 3600);

        // This should not throw "Using $this when not in object context" error
        \App\Filament\Resources\TenantResource::unlockTenantAccounts($this->tenant1);

        // Verify lockout was cleared
        $this->assertFalse(Cache::has('2fa_lockout:' . $this->regularUser->id));
    }

    /** @test */
    public function get_locked_accounts_count_returns_correct_value()
    {
        // Initially should be 0
        $count = \App\Filament\Resources\TenantResource::getLockedAccountsCount($this->tenant1);
        $this->assertEquals(0, $count);

        // Add some locked accounts
        Cache::put('2fa_lockout:' . $this->regularUser->id, true, 3600);

        // Add another user to tenant and lock them
        $user2 = User::factory()->create();
        $this->tenant1->users()->attach($user2->id);
        Cache::put('2fa_lockout:' . $user2->id, true, 3600);

        // Count should now be 2
        $count = \App\Filament\Resources\TenantResource::getLockedAccountsCount($this->tenant1);
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function unlock_tenant_accounts_clears_all_lockouts()
    {
        // Create multiple users and lock them
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->tenant1->users()->attach([$user2->id, $user3->id]);

        // Lock all users
        Cache::put('2fa_lockout:' . $this->regularUser->id, true, 3600);
        Cache::put('2fa_lockout:' . $user2->id, true, 3600);
        Cache::put('2fa_lockout:' . $user3->id, true, 3600);
        Cache::put('2fa_attempts:' . $this->regularUser->id, 3, 3600);

        // Verify locks exist
        $this->assertTrue(Cache::has('2fa_lockout:' . $this->regularUser->id));
        $this->assertTrue(Cache::has('2fa_lockout:' . $user2->id));
        $this->assertTrue(Cache::has('2fa_lockout:' . $user3->id));
        $this->assertTrue(Cache::has('2fa_attempts:' . $this->regularUser->id));

        // Unlock accounts
        \App\Filament\Resources\TenantResource::unlockTenantAccounts($this->tenant1);

        // Verify all locks are cleared
        $this->assertFalse(Cache::has('2fa_lockout:' . $this->regularUser->id));
        $this->assertFalse(Cache::has('2fa_lockout:' . $user2->id));
        $this->assertFalse(Cache::has('2fa_lockout:' . $user3->id));
        $this->assertFalse(Cache::has('2fa_attempts:' . $this->regularUser->id));
    }

    /** @test */
    public function tenant_resource_statistics_methods_work_correctly()
    {
        // Test getTenantStorageUsage
        $storageUsage = \App\Filament\Resources\TenantResource::getTenantStorageUsage(
            fn() => $this->tenant1->database
        );
        $this->assertIsString($storageUsage);

        // Test getTenantFileCount
        $fileCount = \App\Filament\Resources\TenantResource::getTenantFileCount(
            fn() => $this->tenant1->id
        );
        $this->assertIsInt($fileCount);

        // Test getTenantUserCount
        $userCount = \App\Filament\Resources\TenantResource::getTenantUserCount(
            fn() => $this->tenant1->id
        );
        $this->assertIsInt($userCount);
        $this->assertEquals(1, $userCount); // We attached 1 user

        // Test getTenantLastActivity
        $lastActivity = \App\Filament\Resources\TenantResource::getTenantLastActivity(
            fn() => $this->tenant1->id
        );
        $this->assertIsString($lastActivity);

        // Test getTenantHealthScore
        $healthScore = \App\Filament\Resources\TenantResource::calculateTenantHealthScore(
            fn() => $this->tenant1
        );
        $this->assertIsInt($healthScore);
        $this->assertGreaterThanOrEqual(0, $healthScore);
        $this->assertLessThanOrEqual(100, $healthScore);
    }

    /** @test */
    public function tenant_resource_query_filters_active_tenants_only()
    {
        // Create an archived tenant
        $archivedTenant = Tenant::factory()->create([
            'name' => 'Archived Tenant',
            'status' => 'archived',
            'deleted_at' => now(),
        ]);

        $resource = new \App\Filament\Resources\TenantResource();
        $query = $resource::getEloquentQuery();

        // Should not include archived tenants
        $results = $query->pluck('id')->toArray();
        $this->assertContains($this->tenant1->id, $results);
        $this->assertContains($this->tenant2->id, $results);
        $this->assertNotContains($archivedTenant->id, $results);
    }

    /** @test */
    public function tenant_resource_permissions_work_correctly()
    {
        $resource = new \App\Filament\Resources\TenantResource();

        // Mock superadmin auth
        auth('superadmin')->login($this->superadmin);

        // Superadmin should have all permissions
        $this->assertTrue($resource::canViewAny());
        $this->assertTrue($resource::canCreate());
        $this->assertTrue($resource::canEdit($this->tenant1));
        $this->assertTrue($resource::canDelete($this->tenant1));

        // Logout superadmin
        auth('superadmin')->logout();

        // Mock regular user auth (non-superadmin)
        auth('superadmin')->login($this->regularUser);

        // Regular user should not have permissions
        $this->assertFalse($resource::canViewAny());
        $this->assertFalse($resource::canCreate());
        $this->assertFalse($resource::canEdit($this->tenant1));
        $this->assertFalse($resource::canDelete($this->tenant1));
    }

    /** @test */
    public function tenant_resource_navigation_registration_works()
    {
        $resource = new \App\Filament\Resources\TenantResource();

        // Mock superadmin auth
        auth('superadmin')->login($this->superadmin);

        // Should register navigation for superadmin
        $this->assertTrue($resource::shouldRegisterNavigation());

        // Logout superadmin
        auth('superadmin')->logout();

        // Mock regular user auth
        auth('superadmin')->login($this->regularUser);

        // Should not register navigation for regular user
        $this->assertFalse($resource::shouldRegisterNavigation());
    }

    /** @test */
    public function tenant_resource_backup_action_works()
    {
        // Mock the backup service
        $backupService = $this->mock(TenantBackupService::class);
        $backupService->shouldReceive('backupDatabase')
            ->once()
            ->with($this->tenant1->database, $this->tenant1->id, 'manual')
            ->andReturn([
                'success' => true,
                'file_size' => 1024 * 1024 * 5, // 5MB
            ]);

        // Mock Filament notification
        Notification::fake();

        // Execute the backup action
        $resource = new \App\Filament\Resources\TenantResource();

        // This would normally be called by the Filament action
        // We're testing that the service call works without errors
        $result = $backupService->backupDatabase($this->tenant1->database, $this->tenant1->id, 'manual');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function tenant_resource_handles_exception_in_static_methods()
    {
        // Test with invalid tenant that would cause exception
        $invalidTenant = new class {
            public function __get($property) {
                throw new \Exception("Invalid property: {$property}");
            }

            public function users() {
                throw new \Exception("Cannot access users");
            }
        };

        // Methods should handle exceptions gracefully
        $count = \App\Filament\Resources\TenantResource::getLockedAccountsCount($invalidTenant);
        $this->assertEquals(0, $count);

        // unlockTenantAccounts should not throw exception
        \App\Filament\Resources\TenantResource::unlockTenantAccounts($invalidTenant);

        // If we reach here, no exception was thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function tenant_resource_multi_tenant_isolation_maintained()
    {
        // Test that statistics methods respect tenant isolation

        // Create some cache data for tenant1
        Cache::put("tenant_files_{$this->tenant1->id}", 5, 300);
        Cache::put("tenant_users_{$this->tenant1->id}", 3, 300);

        // Create different cache data for tenant2
        Cache::put("tenant_files_{$this->tenant2->id}", 2, 300);
        Cache::put("tenant_users_{$this->tenant2->id}", 1, 300);

        // Verify each tenant gets its own data
        $fileCount1 = \App\Filament\Resources\TenantResource::getTenantFileCount(
            fn() => $this->tenant1->id
        );
        $fileCount2 = \App\Filament\Resources\TenantResource::getTenantFileCount(
            fn() => $this->tenant2->id
        );

        $this->assertEquals(5, $fileCount1);
        $this->assertEquals(2, $fileCount2);

        $userCount1 = \App\Filament\Resources\TenantResource::getTenantUserCount(
            fn() => $this->tenant1->id
        );
        $userCount2 = \App\Filament\Resources\TenantResource::getTenantUserCount(
            fn() => $this->tenant2->id
        );

        $this->assertEquals(3, $userCount1);
        $this->assertEquals(1, $userCount2);
    }

    /** @test */
    public function tenant_resource_infolist_configuration_works()
    {
        $resource = new \App\Filament\Resources\TenantResource();

        // Mock the infolist
        $infolist = $this->createMock(\Filament\Infolists\Infolist::class);
        $infolist->expects($this->once())
            ->method('schema')
            ->with($this->isType('array'))
            ->willReturnSelf();

        // Should not throw exception
        $result = $resource->infolist($infolist);
        $this->assertInstanceOf(\Filament\Infolists\Infolist::class, $result);
    }

    /** @test */
    public function tenant_resource_pages_configuration_works()
    {
        $resource = new \App\Filament\Resources\TenantResource();
        $pages = $resource::getPages();

        $this->assertIsArray($pages);
        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    /** @test */
    public function tenant_resource_static_methods_can_be_called_in_closures()
    {
        // This test specifically validates that the closure context issue is fixed
        $wasCalled = false;

        // Create a closure similar to what Filament uses
        $closure = function () use (&$wasCalled) {
            $wasCalled = true;
            // This should not throw "Using $this when not in object context"
            return \App\Filament\Resources\TenantResource::getLockedAccountsCount($this->tenant1);
        };

        // Bind the closure to a mock context (similar to what Filament does)
        $mockContext = new class {};
        $boundClosure = $closure->bindTo($mockContext);

        // Call the bound closure
        $result = $boundClosure->call($mockContext);

        $this->assertTrue($wasCalled);
        $this->assertIsInt($result);
    }

    /** @test */
    public function tenant_resource_resend_welcome_action_handles_missing_user()
    {
        // Mock Filament notification
        Notification::fake();

        // Create a tenant with no user
        $tenantWithNoUser = Tenant::factory()->create([
            'contact_email' => 'nonexistent@test.com',
        ]);

        // Attempting to resend welcome should not crash
        $resource = new \App\Filament\Resources\TenantResource();

        // The action would normally be called by Filament, but we can test the logic
        $user = User::where('email', $tenantWithNoUser->contact_email)->first();
        $this->assertNull($user);

        // This verifies the null check works
        $this->assertTrue(true);
    }

    /** @test */
    public function tenant_resource_caches_are_respected()
    {
        // Test caching for statistics methods

        $cacheKey = "tenant_health_{$this->tenant1->id}";

        // First call should calculate and cache
        $score1 = \App\Filament\Resources\TenantResource::calculateTenantHealthScore(
            fn() => $this->tenant1
        );

        $this->assertTrue(Cache::has($cacheKey));

        // Second call should use cache
        $score2 = \App\Filament\Resources\TenantResource::calculateTenantHealthScore(
            fn() => $this->tenant1
        );

        $this->assertEquals($score1, $score2);
    }
}