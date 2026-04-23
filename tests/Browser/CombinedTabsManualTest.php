<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CombinedTabsManualTest extends DuskTestCase
{
    /**
     * Manual test to verify combined tabs work by checking page structure
     */
    public function test_manual_combined_tabs_verification()
    {
        $this->browse(function (Browser $browser) {
            // Visit the admin login page first
            $browser->visit('/admin/login')
                ->pause(1000)
                ->assertSee('Iniciar sesión');

            // Try to login with default credentials
            $browser->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Iniciar sesión')
                ->pause(3000);

            // Check if we made it to admin dashboard
            $currentUrl = $browser->driver->getCurrentURL();
            echo "After login URL: $currentUrl\n";

            if (strpos($currentUrl, 'admin') !== false && strpos($currentUrl, 'login') === false) {
                echo "✅ Login successful\n";

                // Now visit the tenant edit page
                $browser->visit('/admin/tenants/10/edit')
                    ->pause(3000);

                $editUrl = $browser->driver->getCurrentURL();
                echo "Edit page URL: $editUrl\n";

                // Get page source and analyze
                $pageSource = $browser->driver->getPageSource();

                // Check for various indicators
                $checks = [
                    'tabs' => 'tab',
                    'filament-tabs' => 'fi-ta',
                    'info-tab' => 'Información',
                    'modules-tab' => 'Módulos y Add-ons',
                    'save-button' => 'Guardar Cambios',
                    'cancel-button' => 'Cancelar',
                    'document-icon' => 'document-text',
                ];

                echo "\n📋 Page Analysis Results:\n";
                foreach ($checks as $name => $search) {
                    if (strpos($pageSource, $search) !== false) {
                        echo "✅ Found '$search' ($name)\n";
                    } else {
                        echo "❌ Missing '$search' ($name)\n";
                    }
                }

                // Check for specific CSS classes that indicate combined tabs
                if (strpos($pageSource, 'fi-ta-content') !== false) {
                    echo "✅ Found combined tabs container (fi-ta-content)\n";
                } else {
                    echo "❌ No combined tabs container found\n";
                }

                if (strpos($pageSource, 'fi-ta-tabs') !== false) {
                    echo "✅ Found tabs wrapper (fi-ta-tabs)\n";
                } else {
                    echo "❌ No tabs wrapper found\n";
                }

                // Check if we can see any form elements
                if (strpos($pageSource, '<form') !== false) {
                    echo "✅ Form element found\n";
                } else {
                    echo "❌ No form element found\n";
                }

                // Check for RelationManager
                if (strpos($pageSource, 'RelationManager') !== false) {
                    echo "✅ RelationManager found\n";
                } else {
                    echo "❌ No RelationManager found\n";
                }

            } else {
                echo "❌ Login failed. Current URL: $currentUrl\n";
            }
        });
    }
}
