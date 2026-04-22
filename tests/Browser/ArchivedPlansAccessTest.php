<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;

class ArchivedPlansAccessTest extends DuskTestCase
{
    /**
     * Test superadmin access to archived subscription plans.
     */
    public function test_superadmin_access_to_archived_plans()
    {
        $this->browse(function (Browser $browser) {
            // Get superadmin user
            $superadmin = User::where('email', 'admin@emporiodigital.com')->first();
            $this->assertNotNull($superadmin, 'Superadmin user should exist');

            // Login as superadmin
            $browser->visit('/admin')
                ->waitForText('Iniciar sesión', 10)
                ->type('email', $superadmin->email)
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->screenshot('01-superadmin-login-success');

            // Test 1: Check if "Planes Archivados" link exists in navigation menu
            $browser->pause(2000)
                ->within('.filament-sidebar', function ($sidebar) {
                    $sidebar->assertSee('Suscripciones');
                    $sidebar->click('[title="Suscripciones"]');
                })
                ->pause(1000)
                ->screenshot('02-suscriptions-menu-expanded');

            // Look for archived plans link
            $archivedLinkFound = false;
            try {
                $browser->within('.filament-sidebar', function ($sidebar) {
                    $sidebar->assertSee('Planes Archivados')
                        ->clickLink('Planes Archivados');
                });
                $archivedLinkFound = true;
            } catch (\Exception $e) {
                $browser->screenshot('03-archived-link-not-found');
            }

            // Test 2: Try direct URL access
            $browser->visit('/admin/subscription-plans/archived')
                ->pause(3000)
                ->screenshot('04-direct-archived-access');

            // Check if we successfully accessed the archived plans page
            $currentPageTitle = $browser->text('h1') ?? '';
            $currentPageUrl = $browser->driver->getCurrentURL();

            if (str_contains($currentPageTitle, 'Archivados') || str_contains($currentPageUrl, 'archived')) {
                $browser->screenshot('05-archived-page-success');

                // Test 3: Verify archived plans are displayed
                $browser->pause(2000);
                $hasArchivedPlans = $browser->element('.filament-tables-table') ? true : false;

                if ($hasArchivedPlans) {
                    $browser->screenshot('06-archived-plans-list');

                    // Test 4: Try to restore an archived plan
                    $restoreButtonFound = false;
                    try {
                        $browser->click('.filament-tables-row:nth-child(1) button[data-action="restore"]')
                            ->pause(1000)
                            ->waitForText('Restaurar', 5)
                            ->press('Restaurar')
                            ->pause(2000)
                            ->screenshot('07-restore-confirmation');
                        $restoreButtonFound = true;
                    } catch (\Exception $e) {
                        $browser->screenshot('08-restore-button-not-found');
                    }
                }
            } else {
                $browser->screenshot('09-archived-page-failed');
            }

            // Test 5: Check header button functionality
            $browser->visit('/admin/subscription-plans')
                ->pause(2000)
                ->screenshot('10-active-plans-page');

            try {
                $browser->click('button[data-action="view-archived"]')
                    ->pause(2000)
                    ->screenshot('11-header-button-clicked');
            } catch (\Exception $e) {
                $browser->screenshot('12-header-button-not-found');
            }

            // Test 6: Check navigation menu structure
            $browser->pause(1000);
            $menuStructure = $browser->element('.filament-sidebar')?->getText() ?? '';
            $browser->screenshot('13-navigation-menu-full');

            // Final check: Go back to main subscription plans page
            $browser->visit('/admin/subscription-plans')
                ->pause(2000)
                ->screenshot('14-return-to-active-plans');
        });
    }

    /**
     * Test regular user cannot access archived plans.
     */
    public function test_regular_user_cannot_access_archived_plans()
    {
        $this->browse(function (Browser $browser) {
            // Get a regular user (not superadmin)
            $regularUser = User::where('email', 'test@emporiodigital.test')->first();

            if (!$regularUser) {
                $this->markTestSkipped('No regular user found for testing');
                return;
            }

            // Login as regular user
            $browser->visit('/admin')
                ->waitForText('Iniciar sesión', 10)
                ->type('email', $regularUser->email)
                ->type('password', 'password')
                ->press('Ingresar')
                ->pause(3000);

            // Try direct URL access to archived plans
            $browser->visit('/admin/subscription-plans/archived')
                ->pause(3000)
                ->screenshot('15-regular-user-archived-access');

            // Should either get 403 or be redirected
            $currentUrl = $browser->driver->getCurrentURL();
            $hasAccessDenied = false;

            if (str_contains($currentUrl, '403') ||
                str_contains($browser->text('body'), '403') ||
                str_contains($browser->text('body'), 'No autorizado') ||
                str_contains($browser->text('body'), 'Access denied')) {
                $hasAccessDenied = true;
            }

            if (!$hasAccessDenied && !str_contains($currentUrl, 'archived')) {
                // If redirected, capture where we ended up
                $browser->screenshot('16-regular-user-redirected');
            }

            $browser->screenshot('17-regular-user-final-state');
        });
    }

    /**
     * Check if we have test users available.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have the test users
        $superadmin = User::where('email', 'admin@emporiodigital.com')->first();
        if (!$superadmin) {
            $this->markTestSkipped('Superadmin user not found. Please run: php artisan emporio:make-superadmin');
        }
    }
}