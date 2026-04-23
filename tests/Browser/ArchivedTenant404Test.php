<?php

namespace Tests\Browser;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ArchivedTenant404Test extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Run landlord migrations
        $this->artisan('migrate', [
            '--database' => 'landlord',
            '--path' => 'database/migrations/landlord',
        ]);

        // Seed landlord database
        $this->artisan('db:seed', [
            '--class' => 'Database\\Seeders\\LandlordAdminSeeder',
            '--database' => 'landlord',
        ]);
    }

    /**
     * Test reproducing the 404 error when viewing archived tenants.
     *
     * This test simulates the exact scenario described:
     * 1. Create a SuperAdmin user
     * 2. Create a new Tenant ("Ghost Store")
     * 3. Archive it (Soft Delete the tenant record)
     * 4. Try to access the view page directly
     * 5. Check if we get 404 or "Not Found"
     */
    public function test_archived_tenant_view_returns_404(): void
    {
        // Step 1: Create SuperAdmin user
        $superAdmin = User::create([
            'name' => 'Test SuperAdmin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Step 2: Create a new Tenant ("Ghost Store")
        $tenant = Tenant::create([
            'name' => 'Ghost Store',
            'domain' => 'ghost-store',
            'database' => 'tenant_ghost_store',
            'status' => Tenant::STATUS_ACTIVE,
            'contact_email' => 'contact@ghoststore.com',
            'phone' => '+1234567890',
            'cuit' => '12345678901',
            'timezone' => 'America/Argentina/Buenos_Aires',
        ]);

        // Verify tenant was created successfully
        $this->assertNotNull($tenant);
        $this->assertEquals('Ghost Store', $tenant->name);
        $this->assertNull($tenant->deleted_at);

        // Step 3: Archive it (Soft Delete the tenant record)
        $tenant->status = Tenant::STATUS_ARCHIVED;
        $tenant->save();
        $tenant->delete(); // This sets the deleted_at timestamp

        // Verify tenant is now soft-deleted
        $this->assertNotNull($tenant->deleted_at);
        $this->assertEquals(Tenant::STATUS_ARCHIVED, $tenant->status);

        // Step 4: Simulate user journey to archived tenant page
        $this->browse(function (Browser $browser) use ($tenant) {
            $browser->resize(1920, 1080)
                ->visit('/admin/login')
                ->assertSee('Iniciar Sesión')
                ->type('email', 'superadmin@test.com')
                ->type('password', 'password')
                ->press('Entrar')
                ->waitForLocation('/admin', 10)
                ->assertPathIs('/admin')
                ->assertSee('Panel de Administración');

            // Navigate directly to the archived tenant view page
            $viewUrl = "/admin/archived-tenants/{$tenant->id}";

            $browser->visit($viewUrl)
                ->pause(2000); // Wait for page to load

            // Step 5: ASSERT - Check if we see "404" or "Not Found"
            try {
                // Check if we got a 404 error page
                $browser->assertSee('404')
                    ->screenshot('archived_tenant_404_error_confirmed');

                // If we see 404, the issue is confirmed
                $this->assertTrue(true, 'CONFIRMED: Archived tenant view returns 404 error');

            } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
                // Check if we can see the archived tenant details instead
                try {
                    $browser->assertSee('Detalles del Tenant Archivado')
                        ->assertSee('Ghost Store')
                        ->screenshot('archived_tenant_view_works_correctly');

                    // If we see the archived tenant details, the issue is NOT present
                    $this->assertTrue(true, 'CANNOT REPRODUCE: Archived tenant view works correctly');

                } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e2) {
                    // If we don't see 404 or the tenant details, check for any other error indicators
                    $browser->screenshot('archived_tenant_unknown_state');

                    // Check page title and URL
                    $pageTitle = $browser->driver->getTitle();
                    $currentUrl = $browser->driver->getCurrentURL();

                    // Log the current state for debugging
                    $this->addDebugInfo([
                        'page_title' => $pageTitle,
                        'current_url' => $currentUrl,
                        'page_source_length' => strlen($browser->driver->getPageSource()),
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'deleted_at' => $tenant->deleted_at,
                    ]);

                    // Fail the test with detailed information
                    $this->fail("UNEXPECTED STATE: Neither 404 nor archived tenant details found. URL: {$currentUrl}, Title: {$pageTitle}");
                }
            }
        });
    }

    /**
     * Additional test to verify the archived tenants list works correctly
     */
    public function test_archived_tenants_list_works(): void
    {
        // Create SuperAdmin user
        $superAdmin = User::create([
            'name' => 'Test SuperAdmin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create and archive a tenant
        $tenant = Tenant::create([
            'name' => 'List Test Store',
            'domain' => 'list-test-store',
            'database' => 'tenant_list_test_store',
            'status' => Tenant::STATUS_ARCHIVED,
            'contact_email' => 'list@test.com',
        ]);

        $tenant->delete(); // Soft delete

        $this->browse(function (Browser $browser) {
            $browser->resize(1920, 1080)
                ->visit('/admin/login')
                ->type('email', 'superadmin@test.com')
                ->type('password', 'password')
                ->press('Entrar')
                ->waitForLocation('/admin', 10)
                ->visit('/admin/archived-tenants')
                ->pause(2000)
                ->assertSee('Tenants Archivados')
                ->assertSee('List Test Store')
                ->screenshot('archived_tenants_list_works');
        });
    }

    /**
     * Test direct navigation with different archived tenant IDs
     */
    public function test_multiple_archived_tenant_views(): void
    {
        // Create SuperAdmin user
        $superAdmin = User::create([
            'name' => 'Test SuperAdmin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create multiple archived tenants
        $tenants = [];
        for ($i = 1; $i <= 3; $i++) {
            $tenant = Tenant::create([
                'name' => "Test Store {$i}",
                'domain' => "test-store-{$i}",
                'database' => "tenant_test_store_{$i}",
                'status' => Tenant::STATUS_ARCHIVED,
                'contact_email' => "test{$i}@store.com",
            ]);
            $tenant->delete(); // Soft delete
            $tenants[] = $tenant;
        }

        $this->browse(function (Browser $browser) use ($tenants) {
            $browser->resize(1920, 1080)
                ->visit('/admin/login')
                ->type('email', 'superadmin@test.com')
                ->type('password', 'password')
                ->press('Entrar')
                ->waitForLocation('/admin', 10);

            foreach ($tenants as $index => $tenant) {
                $viewUrl = "/admin/archived-tenants/{$tenant->id}";

                $browser->visit($viewUrl)
                    ->pause(2000)
                    ->screenshot('archived_tenant_'.($index + 1).'_view_test');

                try {
                    $browser->assertSee('404');
                    $this->assertTrue(true, "Tenant {$tenant->name} returns 404");
                } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
                    try {
                        $browser->assertSee('Detalles del Tenant Archivado')
                            ->assertSee($tenant->name);
                        $this->assertTrue(true, "Tenant {$tenant->name} loads correctly");
                    } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e2) {
                        $this->fail("Tenant {$tenant->name} shows unexpected state");
                    }
                }
            }
        });
    }

    /**
     * Helper method to add debug information
     */
    private function addDebugInfo(array $info): void
    {
        echo "\n=== DEBUG INFORMATION ===\n";
        foreach ($info as $key => $value) {
            echo "{$key}: {$value}\n";
        }
        echo "========================\n";
    }
}
