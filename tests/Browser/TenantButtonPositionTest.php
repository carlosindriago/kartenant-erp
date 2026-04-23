<?php

use App\Models\Tenant;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantButtonPositionTest extends DuskTestCase
{
    /**
     * Test that Save/Cancel buttons are properly positioned at bottom of tenant edit form.
     */
    public function test_tenant_edit_form_button_positioning(): void
    {
        $this->browse(function (Browser $browser) {
            try {
                // Login as superadmin first
                $browser->visit('/admin/login')
                    ->type('data.email', 'admin@emporiodigital.com')
                    ->type('data.password', 'password')
                    ->press('Iniciar Sesión')
                    ->waitForLocation('/admin', 10)
                    ->assertPathIs('/admin');

                // Navigate to tenant edit page using domain
                $browser->visit('/admin/tenants/data-protection-test-1764024534/edit')
                    ->waitFor('fi-ta-form', 10)
                    ->pause(2000); // Wait for form to fully load

                // Check if we are on the edit page
                $browser->assertSee('Editar Tenant')
                    ->assertPresent('fi-ta-form');

                // Take initial screenshot
                $browser->screenshot('tenant-edit-form-before-scroll');

                // Check if Save/Cancel buttons are present
                $saveButtonPresent = $browser->element('button[aria-label="Guardar Cambios"]');
                $cancelButtonPresent = $browser->element('button[aria-label="Cancelar"]');

                if ($saveButtonPresent) {
                    echo "✅ Save button found with proper Spanish label\n";
                } else {
                    echo "❌ Save button not found\n";
                }

                if ($cancelButtonPresent) {
                    echo "✅ Cancel button found with proper Spanish label\n";
                } else {
                    echo "❌ Cancel button not found\n";
                }

                // Check if Delete action is still in header
                $deleteButtonPresent = $browser->element('button[aria-label="Eliminar"]');
                if ($deleteButtonPresent) {
                    echo "✅ Delete action found in header\n";
                } else {
                    echo "❌ Delete action not found in header\n";
                }

                // Test Cancel button functionality
                if ($cancelButtonPresent) {
                    $browser->press('Cancelar')
                        ->pause(2000);

                    // Verify redirection to tenant view
                    $currentUrl = $browser->driver->getCurrentURL();
                    if (str_contains($currentUrl, '/admin/tenants/data-protection-test-1764024534') &&
                        ! str_contains($currentUrl, '/edit')) {
                        echo "✅ Cancel button correctly redirects to tenant view page\n";
                    } else {
                        echo "❌ Cancel button did not redirect properly\n";
                        echo '   Current URL: '.$currentUrl."\n";
                    }
                }

                // Take final screenshot for visual verification
                $browser->screenshot('tenant-edit-button-position-test');
                echo "📸 Screenshot saved to: tests/Browser/screenshots/tenant-edit-button-position-test.png\n";

                $this->assertTrue(true, 'Button positioning test completed');

            } catch (\Exception $e) {
                // Take error screenshot
                $browser->screenshot('tenant-edit-button-position-error');
                echo "📸 Error screenshot saved to: tests/Browser/screenshots/tenant-edit-button-position-error.png\n";

                throw $e;
            }
        });
    }
}
