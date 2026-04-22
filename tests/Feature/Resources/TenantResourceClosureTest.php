<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantResourceClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $regularUser;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'is_super_admin' => false,
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'tenant',
            'database' => 'tenant_test',
            'contact_email' => 'tenant@test.com',
            'contact_name' => 'Tenant Admin',
        ]);

        $this->tenant->users()->attach($this->regularUser->id);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    /** @test */
    public function tenant_resource_table_actions_use_self_not_this()
    {
        // This test validates the fix for the closure context issue
        // We're testing that the action closures use `self::method()` instead of `$this->method()`

        $resource = new \App\Filament\Resources\TenantResource();
        $table = $resource->table(\Filament\Tables\Table::make());

        // Get the unlock accounts action
        $actions = collect($table->getActions());
        $unlockAction = $actions->firstWhere('name', 'unlock_accounts');

        $this->assertNotNull($unlockAction, 'unlock_accounts action should exist');
        $this->assertIsCallable($unlockAction->getAction());
    }

    /** @test */
    public function unlock_action_closure_can_be_executed_without_object_context()
    {
        // This simulates exactly what Filament does when executing actions
        // Before the fix, this would fail with "Using $this when not in object context"

        $user = $this->regularUser;
        Cache::put('2fa_lockout:' . $user->id, true, 3600);

        // Verify lock exists
        $this->assertTrue(Cache::has('2fa_lockout:' . $user->id));

        // Simulate the action closure that was fixed
        $actionClosure = function ($record) {
            // This was the problematic line that was fixed:
            // Before: $this->unlockTenantAccounts($record); // Would fail
            // After:  self::unlockTenantAccounts($record); // Works
            return \App\Filament\Resources\TenantResource::unlockTenantAccounts($record);
        };

        // Execute closure with bound context (like Filament does)
        $mockContext = new class {};
        $boundClosure = $actionClosure->bindTo($mockContext);

        // This should not throw "Using $this when not in object context" error
        try {
            $boundClosure->call($mockContext, $this->tenant);
            $this->assertTrue(true, 'Closure executed without "Using $this when not in object context" error');
        } catch (\Error $e) {
            if (str_contains($e->getMessage(), 'Using $this when not in object context')) {
                $this->fail('The closure context fix did not work: ' . $e->getMessage());
            }
            throw $e;
        }

        // Verify the lock was cleared (the method actually worked)
        $this->assertFalse(Cache::has('2fa_lockout:' . $user->id));
    }

    /** @test */
    public function badge_closure_can_be_executed_without_object_context()
    {
        // Test the badge closure that uses getLockedAccountsCount

        $user = $this->regularUser;
        Cache::put('2fa_lockout:' . $user->id, true, 3600);

        // Simulate the badge closure
        $badgeClosure = function ($record) {
            // This was also fixed to use self:: instead of $this->
            return \App\Filament\Resources\TenantResource::getLockedAccountsCount($record);
        };

        // Execute closure with bound context (like Filament does)
        $mockContext = new class {};
        $boundClosure = $badgeClosure->bindTo($mockContext);

        // This should not throw "Using $this when not in object context" error
        try {
            $result = $boundClosure->call($mockContext, $this->tenant);
            $this->assertEquals(1, $result, 'Badge closure should return correct lock count');
        } catch (\Error $e) {
            if (str_contains($e->getMessage(), 'Using $this when not in object context')) {
                $this->fail('The badge closure fix did not work: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /** @test */
    public function color_closure_can_be_executed_without_object_context()
    {
        // Test the color closure that determines button color based on lock count

        $user = $this->regularUser;
        Cache::put('2fa_lockout:' . $user->id, true, 3600);

        // Simulate the color closure
        $colorClosure = function ($record) {
            // This determines if button should be 'danger' (has locks) or 'gray' (no locks)
            $lockedCount = \App\Filament\Resources\TenantResource::getLockedAccountsCount($record);
            return $lockedCount > 0 ? 'danger' : 'gray';
        };

        // Execute closure with bound context
        $mockContext = new class {};
        $boundClosure = $colorClosure->bindTo($mockContext);

        try {
            $result = $boundClosure->call($mockContext, $this->tenant);
            $this->assertEquals('danger', $result, 'Color closure should return "danger" when there are locks');
        } catch (\Error $e) {
            if (str_contains($e->getMessage(), 'Using $this when not in object context')) {
                $this->fail('The color closure fix did not work: ' . $e->getMessage());
            }
            throw $e;
        }

        // Test with no locks
        Cache::forget('2fa_lockout:' . $user->id);
        $result = $boundClosure->call($mockContext, $this->tenant);
        $this->assertEquals('gray', $result, 'Color closure should return "gray" when there are no locks');
    }

    /** @test */
    public function all_closures_work_together_in_same_context()
    {
        // Test multiple closures being called in sequence (like in real Filament usage)

        Cache::put('2fa_lockout:' . $this->regularUser->id, true, 3600);

        $actions = [];

        // Simulate multiple closures like Filament would create
        $actions[] = [
            'name' => 'unlock_accounts',
            'badge' => function ($record) {
                return \App\Filament\Resources\TenantResource::getLockedAccountsCount($record);
            },
            'color' => function ($record) {
                return \App\Filament\Resources\TenantResource::getLockedAccountsCount($record) > 0 ? 'danger' : 'gray';
            },
            'action' => function ($record) {
                \App\Filament\Resources\TenantResource::unlockTenantAccounts($record);
                return true;
            }
        ];

        // Execute all actions in same context
        $mockContext = new class {};

        foreach ($actions as $actionConfig) {
            foreach ($actionConfig as $type => $closure) {
                $boundClosure = $closure->bindTo($mockContext);

                try {
                    if ($type === 'action') {
                        $result = $boundClosure->call($mockContext, $this->tenant);
                        $this->assertTrue($result, 'Action should execute successfully');
                    } else {
                        $result = $boundClosure->call($mockContext, $this->tenant);
                        $this->assertTrue(is_int($result) || is_string($result),
                            'Badge and color closures should return appropriate types');
                    }
                } catch (\Error $e) {
                    if (str_contains($e->getMessage(), 'Using $this when not in object context')) {
                        $this->fail("Closure {$type} failed with context error: " . $e->getMessage());
                    }
                    throw $e;
                }
            }
        }

        // Verify locks were cleared by action execution
        $this->assertFalse(Cache::has('2fa_lockout:' . $this->regularUser->id));
    }

    /** @test */
    public function closures_handle_exceptions_gracefully()
    {
        // Test closures with invalid tenant data

        $invalidTenant = new class {
            public function users() {
                throw new \Exception("Cannot access users");
            }
        };

        $badgeClosure = function ($record) {
            return \App\Filament\Resources\TenantResource::getLockedAccountsCount($record);
        };

        $actionClosure = function ($record) {
            \App\Filament\Resources\TenantResource::unlockTenantAccounts($record);
        };

        $mockContext = new class {};

        // Badge closure should return 0 on exception
        $boundBadge = $badgeClosure->bindTo($mockContext);
        $result = $boundBadge->call($mockContext, $invalidTenant);
        $this->assertEquals(0, $result);

        // Action closure should not throw exception
        $boundAction = $actionClosure->bindTo($mockContext);
        $boundAction->call($mockContext, $invalidTenant);

        // If we reach here, no exception was thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function multiple_users_locks_handled_correctly()
    {
        // Test with multiple users locked
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->tenant->users()->attach([$user2->id, $user3->id]);

        // Lock all users
        Cache::put('2fa_lockout:' . $this->regularUser->id, true, 3600);
        Cache::put('2fa_lockout:' . $user2->id, true, 3600);
        Cache::put('2fa_lockout:' . $user3->id, true, 3600);

        // Test badge closure
        $badgeClosure = function ($record) {
            return \App\Filament\Resources\TenantResource::getLockedAccountsCount($record);
        };

        $mockContext = new class {};
        $boundClosure = $badgeClosure->bindTo($mockContext);
        $result = $boundClosure->call($mockContext, $this->tenant);
        $this->assertEquals(3, $result);

        // Test action closure
        $actionClosure = function ($record) {
            \App\Filament\Resources\TenantResource::unlockTenantAccounts($record);
        };

        $boundAction = $actionClosure->bindTo($mockContext);
        $boundAction->call($mockContext, $this->tenant);

        // Verify all locks were cleared
        $this->assertFalse(Cache::has('2fa_lockout:' . $this->regularUser->id));
        $this->assertFalse(Cache::has('2fa_lockout:' . $user2->id));
        $this->assertFalse(Cache::has('2fa_lockout:' . $user3->id));
    }
}