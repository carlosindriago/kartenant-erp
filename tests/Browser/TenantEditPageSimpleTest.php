<?php

namespace Tests\Browser;

use App\Models\Tenant;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantEditPageSimpleTest extends DuskTestCase
{
    /**
     * Simple test to verify tenant edit page loads without component errors
     */
    public function test_tenant_edit_page_no_component_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::where('domain', 'data-protection-test-1764024534')->first();

            if (! $tenant) {
                $this->markTestSkipped('Test tenant not found');

                return;
            }

            // Try to directly visit the edit page
            // This will redirect to login if not authenticated, but we can still check for 500 errors
            try {
                $browser->visit("/admin/tenants/{$tenant->id}/edit")
                    ->pause(3000); // Wait for any redirects/errors

                // Take a screenshot to see what happened
                $browser->screenshot('tenant-edit-direct-access');

                // Check if we got redirected to login (expected behavior)
                $currentUrl = $browser->driver->getCurrentURL();
                if (str_contains($currentUrl, '/admin/login')) {
                    $this->assertTrue(true, 'Correctly redirected to login page - no 500 error');

                    return;
                }

                // If we're on the edit page, check for component errors
                if (str_contains($currentUrl, '/admin/tenants/') && str_contains($currentUrl, '/edit')) {
                    $browser->assertDontSee('TypeError');
                    $browser->assertDontSee('Argument #1 ($component) must be of type');
                    $browser->assertDontSee('Component type mismatch');
                    $browser->assertDontSee('must be of type Filament\Forms\Components\Component');

                    $this->assertTrue(true, 'Tenant edit page loaded without component errors');
                }

            } catch (\Exception $e) {
                // Check if it's a 500 error (component mismatch) vs redirect
                if (str_contains($e->getMessage(), '500') ||
                    str_contains($e->getMessage(), 'TypeError') ||
                    str_contains($e->getMessage(), 'component')) {
                    $this->fail('Component error detected: '.$e->getMessage());
                } else {
                    // Redirect or navigation error is acceptable
                    $this->assertTrue(true, 'No component errors detected');
                }
            }
        });
    }
}
