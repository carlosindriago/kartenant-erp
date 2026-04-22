<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleTenantEditUxTest extends DuskTestCase
{
    /**
     * Simple UX test for tenant edit page
     */
    public function test_tenant_edit_layout_analysis(): void
    {
        $this->browse(function (Browser $browser) {
            // Login as admin
            $browser->visit("https://localhost/admin/login")
                ->type("email", "admin@emporiodigital.test")
                ->type("password", "emporiodigital123")
                ->press("Ingresar")
                ->waitForLocation("/admin", 10);

            // Go to tenants list first
            $browser->visit("https://localhost/admin/tenants")
                ->pause(2000)
                ->screenshot("tenants-list-page");

            // Find and click the first edit button
            $browser->click(".fi-ta-edit-action, button[title='Editar'], .fi-action-button:contains('Editar')")
                ->pause(2000)
                ->screenshot("tenant-edit-page-layout");

            // Analyze button positioning
            $browser->pause(1000)
                ->screenshot("tenant-edit-full-page");

            // Try to scroll down and capture more
            $browser->script("window.scrollTo(0, document.body.scrollHeight);");
            $browser->pause(2000)
                ->screenshot("tenant-edit-scrolled");
        });
    }
}
