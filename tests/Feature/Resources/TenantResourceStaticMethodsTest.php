<?php

namespace Tests\Feature\Resources;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantResourceStaticMethodsTest extends TestCase
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
    public function get_locked_accounts_count_returns_zero_with_no_locks()
    {
        // This tests the critical fix: calling static method without $this context
        $count = TenantResource::getLockedAccountsCount($this->tenant);

        $this->assertEquals(0, $count);
        $this->assertIsInt($count);
    }

    /** @test */
    public function get_locked_accounts_count_returns_correct_with_locks()
    {
        // Add some locked accounts
        Cache::put('2fa_lockout:'.$this->regularUser->id, true, 3600);

        // This should not throw "Using $this when not in object context" error
        $count = TenantResource::getLockedAccountsCount($this->tenant);

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function unlock_tenant_accounts_works_with_no_locks()
    {
        // This should not throw exception or error
        TenantResource::unlockTenantAccounts($this->tenant);

        // Should simply complete without error
        $this->assertTrue(true);
    }

    /** @test */
    public function unlock_tenant_accounts_clears_existing_locks()
    {
        // Lock the user first
        Cache::put('2fa_lockout:'.$this->regularUser->id, true, 3600);
        Cache::put('2fa_attempts:'.$this->regularUser->id, 3, 3600);

        // Verify lock exists
        $this->assertTrue(Cache::has('2fa_lockout:'.$this->regularUser->id));
        $this->assertTrue(Cache::has('2fa_attempts:'.$this->regularUser->id));

        // This should not throw "Using $this when not in object context" error
        TenantResource::unlockTenantAccounts($this->tenant);

        // Verify lock is cleared
        $this->assertFalse(Cache::has('2fa_lockout:'.$this->regularUser->id));
        $this->assertFalse(Cache::has('2fa_attempts:'.$this->regularUser->id));
    }

    /** @test */
    public function static_methods_can_be_called_in_closure_context()
    {
        // This simulates the exact issue that was fixed
        $closure = function () {
            // These calls would previously fail with "Using $this when not in object context"
            $count = TenantResource::getLockedAccountsCount($this->tenant);
            TenantResource::unlockTenantAccounts($this->tenant);

            return $count;
        };

        // Bind to a mock context (like Filament does)
        $mockContext = new class {};
        $boundClosure = $closure->bindTo($mockContext);

        // This should not throw any exceptions
        $result = $boundClosure->call($mockContext);

        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function static_methods_handle_exceptions_gracefully()
    {
        // Test with invalid tenant object
        $invalidTenant = new class
        {
            public function __get($property)
            {
                throw new \Exception("Invalid property: {$property}");
            }

            public function users()
            {
                throw new \Exception('Cannot access users');
            }
        };

        // Should handle exceptions and return default values
        $count = TenantResource::getLockedAccountsCount($invalidTenant);
        $this->assertEquals(0, $count);

        // Should not throw exception
        TenantResource::unlockTenantAccounts($invalidTenant);
        $this->assertTrue(true);
    }

    /** @test */
    public function other_statistics_methods_work_as_static()
    {
        // Test other static methods that use similar callback patterns
        $fileCount = TenantResource::getTenantFileCount(
            fn () => $this->tenant->id
        );
        $this->assertIsInt($fileCount);

        $userCount = TenantResource::getTenantUserCount(
            fn () => $this->tenant->id
        );
        $this->assertIsInt($userCount);

        $lastActivity = TenantResource::getTenantLastActivity(
            fn () => $this->tenant->id
        );
        $this->assertIsString($lastActivity);

        $healthScore = TenantResource::calculateTenantHealthScore(
            fn () => $this->tenant
        );
        $this->assertIsInt($healthScore);
        $this->assertGreaterThanOrEqual(0, $healthScore);
        $this->assertLessThanOrEqual(100, $healthScore);
    }
}
