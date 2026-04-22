<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleAdminLoginTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function test_basic_admin_login(): void
    {
        $this->browse(function (Browser $browser) {
            // Just take a screenshot of the login page to verify it loads
            $browser->visit('/admin/login')
                    ->screenshot('admin-login-page-2')
                    ->pause(3);

            // Try to access invoices page directly (should redirect to login)
            $browser->visit('/admin/invoices')
                    ->screenshot('invoices-redirect-test')
                    ->pause(2);
        });
    }
}