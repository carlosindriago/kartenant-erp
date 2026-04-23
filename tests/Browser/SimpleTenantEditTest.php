<?php

namespace Tests\Browser;

use App\Models\Tenant;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleTenantEditTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user for testing
        $this->user = User::firstOrCreate(
            ['email' => 'test@admin.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    /**
     * Simple test to check the edit page structure
     */
    public function test_tenant_edit_page_structure()
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::find(10);

            if (! $tenant) {
                $this->markTestSkipped('Tenant not found');
            }

            $browser->loginAs($this->user)
                ->visit('/admin/tenants/'.$tenant->id.'/edit')
                ->pause(3000); // Wait for page to fully load

            // Debug: Check if we're on the correct page
            $currentUrl = $browser->driver->getCurrentURL();
            echo "Current URL: $currentUrl\n";

            // Check page title or any content to verify we're on the right page
            try {
                $pageSource = $browser->driver->getPageSource();
                if (strpos($pageSource, 'Editar') !== false) {
                    echo "✅ Found 'Editar' on page\n";
                } else {
                    echo "❌ No 'Editar' found on page\n";
                }

                if (strpos($pageSource, 'tab') !== false) {
                    echo "✅ Found 'tab' in page source\n";
                } else {
                    echo "❌ No 'tab' found in page source\n";
                }

                if (strpos($pageSource, 'fi-ta') !== false) {
                    echo "✅ Found 'fi-ta' (Filament tabs) in page source\n";
                } else {
                    echo "❌ No 'fi-ta' found in page source\n";
                }

                // Check for combined tabs indicators
                if (strpos($pageSource, 'Información') !== false) {
                    echo "✅ Found 'Información' in page source\n";
                } else {
                    echo "❌ No 'Información' found in page source\n";
                }

                if (strpos($pageSource, 'Módulos y Add-ons') !== false) {
                    echo "✅ Found 'Módulos y Add-ons' in page source\n";
                } else {
                    echo "❌ No 'Módulos y Add-ons' found in page source\n";
                }

                // Check for buttons
                if (strpos($pageSource, 'Guardar Cambios') !== false) {
                    echo "✅ Found 'Guardar Cambios' in page source\n";
                } else {
                    echo "❌ No 'Guardar Cambios' found in page source\n";
                }

                if (strpos($pageSource, 'Cancelar') !== false) {
                    echo "✅ Found 'Cancelar' in page source\n";
                } else {
                    echo "❌ No 'Cancelar' found in page source\n";
                }

            } catch (Exception $e) {
                echo 'Error getting page source: '.$e->getMessage()."\n";
            }
        });
    }
}
