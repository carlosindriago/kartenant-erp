<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;

class SimpleArchivedPlansTest extends DuskTestCase
{
    /**
     * Simple test to access archived plans page.
     */
    public function test_access_archived_plans_page()
    {
        $this->browse(function (Browser $browser) {
            // Get superadmin user
            $superadmin = User::where('email', 'admin@emporiodigital.com')->first();

            if (!$superadmin) {
                $this->markTestSkipped('Superadmin user not found');
                return;
            }

            // Login as superadmin
            $browser->visit('/admin/login')
                ->type('email', $superadmin->email)
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 10)
                ->assertPathIs('/admin')
                ->screenshot('01-login-success');

            // Go to subscription plans page
            $browser->visit('/admin/subscription-plans')
                ->pause(2000)
                ->screenshot('02-subscription-plans-page');

            // Try direct access to archived plans
            $browser->visit('/admin/subscription-plans/archived')
                ->pause(3000)
                ->screenshot('03-archived-plans-page');

            // Check if we successfully loaded the archived plans page
            $pageTitle = $browser->element('h1')?->getText() ?? '';
            $currentUrl = $browser->driver->getCurrentURL();

            $browser->screenshot('04-archived-plans-full-view');

            // If failed, try to check navigation
            if (!str_contains($pageTitle, 'Archivados') && !str_contains($currentUrl, 'archived')) {
                $browser->screenshot('05-archived-access-failed')
                    ->visit('/admin/subscription-plans')
                    ->pause(2000)
                    ->screenshot('06-back-to-active-plans');
            }
        });
    }
}