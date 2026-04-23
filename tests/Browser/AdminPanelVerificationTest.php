<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminPanelVerificationTest extends DuskTestCase
{
    /**
     * Test authentication and critical admin pages after fixes
     */
    public function test_admin_panel_critical_pages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://localhost/admin')
                ->waitForLocation('/admin/login', 10)
                ->assertSee('Ingresar')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'emporiodigital123')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->screenshot('admin-dashboard-success');

            // TEST TARGET 1: System Settings Page
            $browser->visit('https://localhost/admin/system-settings')
                ->waitUntilMissing('.loading-indicator', 10)
                ->pause(1000)
                ->screenshot('system-settings-page')
                ->assertSee('Configuración')
                ->assertDontSee('500')
                ->assertDontSee('Server Error')
                ->assertDontSee('htmlspecialchars()');

            // Check for form elements that should be present
            $settingsFormWorking = $browser->element('form') !== null;
            $this->assertTrue($settingsFormWorking, 'System settings form should be present');

            // TEST TARGET 2: Invoice Management Page
            $browser->visit('https://localhost/admin/invoices')
                ->waitUntilMissing('.loading-indicator', 10)
                ->pause(1000)
                ->screenshot('invoices-page')
                ->assertSee('Facturas')
                ->assertDontSee('500')
                ->assertDontSee('Server Error')
                ->assertDontSee('Class Tab not found');

            // Check for tabs functionality
            $tabsVisible = $browser->element('.fi-tabs') !== null;
            $this->assertTrue($tabsVisible, 'Invoice tabs should be visible');

            // Test tab navigation if tabs are present
            if ($tabsVisible) {
                $browser->click('.fi-tabs a[data-target="draft"]')
                    ->pause(500)
                    ->screenshot('invoices-draft-tab')
                    ->assertDontSee('500');

                $browser->click('.fi-tabs a[data-target="sent"]')
                    ->pause(500)
                    ->screenshot('invoices-sent-tab')
                    ->assertDontSee('500');
            }

            // REGRESSION TEST: Check other admin sections
            $browser->visit('https://localhost/admin/tenants')
                ->pause(1000)
                ->assertDontSee('500')
                ->screenshot('tenants-page');

            $browser->visit('https://localhost/admin/users')
                ->pause(1000)
                ->assertDontSee('500')
                ->screenshot('users-page');
        });
    }

    /**
     * Test login functionality specifically
     */
    public function test_admin_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://localhost/admin/login')
                ->assertSee('Ingresar')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'emporiodigital123')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->screenshot('admin-login-success');
        });
    }
}
