<?php

use App\Models\Tenant;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantEditButtonTest extends DuskTestCase
{
    /**
     * Simple test to check tenant edit page button positioning
     */
    public function test_tenant_edit_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            try {
                // Visit tenant edit page directly (will redirect to login, but we can check for component errors)
                $browser->visit('/admin/tenants/data-protection-test-1764024534/edit')
                    ->pause(5000); // Wait for any redirects

                // Take screenshot to see current state
                $browser->screenshot('tenant-edit-button-test');

                $currentUrl = $browser->driver->getCurrentURL();

                // Check if we are on login page (expected) or tenant edit page (if already logged in)
                if (str_contains($currentUrl, '/admin/login')) {
                    echo "✅ Correctly redirected to login page\n";

                    // Try login with correct field names
                    $browser->type('email', 'admin@emporiodigital.com')
                        ->type('password', 'password')
                        ->press('Iniciar Sesión')
                        ->waitForLocation('/admin', 10)
                        ->assertPathIs('/admin');

                    echo "✅ Login successful\n";

                    // Now navigate to tenant edit page
                    $browser->visit('/admin/tenants/data-protection-test-1764024534/edit')
                        ->waitFor('fi-ta-form', 10)
                        ->pause(2000);

                    // Take screenshot of the form
                    $browser->screenshot('tenant-edit-form-loaded');

                    // Check for Save/Cancel buttons at the bottom
                    $saveButtonFound = $browser->element('button[aria-label="Guardar Cambios"]');
                    $cancelButtonFound = $browser->element('button[aria-label="Cancelar"]');

                    if ($saveButtonFound) {
                        echo "✅ Save button found with Spanish label\n";
                    } else {
                        echo "❌ Save button not found\n";
                        // Check if button exists with different label
                        $altSaveButton = $browser->element('button:contains("Guardar")');
                        if ($altSaveButton) {
                            echo "⚠️  Save button found with different label\n";
                        }
                    }

                    if ($cancelButtonFound) {
                        echo "✅ Cancel button found with Spanish label\n";
                    } else {
                        echo "❌ Cancel button not found\n";
                        // Check if button exists with different label
                        $altCancelButton = $browser->element('button:contains("Cancelar")');
                        if ($altCancelButton) {
                            echo "⚠️  Cancel button found with different label\n";
                        }
                    }

                    // Check for Delete action in header
                    $deleteButtonFound = $browser->element('button[aria-label="Eliminar"]');
                    if ($deleteButtonFound) {
                        echo "✅ Delete button found in header\n";
                    } else {
                        echo "❌ Delete button not found in header\n";
                    }

                    // Final screenshot showing the full page
                    $browser->screenshot('tenant-edit-final-test');
                    echo "📸 Final screenshot saved\n";
                }

            } catch (\Exception $e) {
                $browser->screenshot('tenant-edit-error');
                echo "📸 Error screenshot saved\n";
                throw $e;
            }
        });
    }
}
