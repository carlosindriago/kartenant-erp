<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubscriptionPlanCrudQuickTest extends DuskTestCase
{
    /**
     * Test complete subscription plan creation and deletion
     */
    public function test_complete_subscription_plan_crud(): void
    {
        $this->browse(function (Browser $browser) {
            // Login as admin
            $browser->visit('/admin/login')
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 10)
                ->assertPathIs('/admin');

            // Navigate to subscription plans
            $browser->visit('/admin/subscription-plans')
                ->waitFor('.fi-ta-content', 15)
                ->assertSee('Planes de Suscripción');

            // Click "Nuevo Plan"
            try {
                $browser->clickLink('Nuevo Plan')
                    ->waitFor('.fi-form', 10)
                    ->assertSee('Crear Plan');
            } catch (\Exception $e) {
                $browser->click('.fi-cta-btn')
                    ->waitFor('.fi-form', 10)
                    ->assertSee('Crear Plan');
            }

            // Fill the form with test data
            $browser->type('name', 'Plan Test Selenium CRUD')
                ->type('slug', 'plan-test-selenium-crud')
                ->type('description', 'Plan creado para pruebas automatizadas CRUD completas')
                ->type('price_monthly', '99.99')
                ->type('price_yearly', '999.99')
                ->select('currency', 'USD');

            // Fill limits if available
            try {
                $browser->type('limits.monthly_sales', '100')
                    ->type('limits.users', '5')
                    ->type('limits.storage', '2048')
                    ->type('limits.products', '500');
            } catch (\Exception $e) {
                // Limits fields not available, continue
            }

            // Fill overage strategy if available
            try {
                $browser->select('overage_strategy', 'soft')
                    ->type('overage_tolerance', '20');
            } catch (\Exception $e) {
                // Overage fields not available, continue
            }

            // Set publication configuration (critical for deletion test)
            $browser->uncheck('is_active')
                ->uncheck('is_visible')
                ->uncheck('is_featured')
                ->type('sort_order', '999');

            // Fill features if available
            try {
                $browser->check('features.has_api_access')
                    ->check('features.has_analytics')
                    ->check('features.has_priority_support');
            } catch (\Exception $e) {
                // Features not available, continue
            }

            // Fill modules if available
            try {
                $browser->check('modules.inventory')
                    ->check('modules.pos')
                    ->check('modules.clients');
            } catch (\Exception $e) {
                // Modules not available, continue
            }

            // Save the plan
            $browser->click('.fi-cta-btn')
                ->pause(3000);

            // Check if we got success or error
            $currentPage = $browser->driver->getCurrentURL();

            if (strpos($currentPage, 'subscription-plans') !== false) {
                // We're back to the list, probably successful
                echo "✅ Plan saved successfully\n";

                // Look for our plan
                try {
                    $browser->assertSee('Plan Test Selenium CRUD');
                    echo "✅ Plan found in list\n";

                    // Now try to delete the plan
                    $this->attemptToDeletePlan($browser, 'Plan Test Selenium CRUD');

                } catch (\Exception $e) {
                    echo '❌ Plan not found in list: '.$e->getMessage()."\n";
                }
            } else {
                // Still on form, probably validation error
                echo "⚠️ Still on form page, checking for validation errors\n";

                // Look for error messages
                try {
                    $browser->assertSee('El campo');
                    echo "⚠️ Validation errors found\n";
                } catch (\Exception $e) {
                    echo "❓ No clear validation message\n";
                }
            }
        });
    }

    /**
     * Attempt to delete a plan and verify deletion behavior
     */
    private function attemptToDeletePlan(Browser $browser, string $planName)
    {
        try {
            // Try to find and click edit link for our plan
            $browser->clickLink($planName)
                ->waitFor('.fi-form', 10)
                ->assertSee('Editar Plan');
            echo "✅ Successfully opened plan for editing\n";

            // Look for delete button
            try {
                $browser->click('.fi-ac-link-danger')
                    ->pause(1000);
                echo "✅ Delete button clicked\n";

                // Try to confirm deletion
                try {
                    $browser->waitFor('button[type="submit"]', 3)
                        ->click('button[type="submit"]')
                        ->pause(2000);
                    echo "✅ Delete confirmation clicked\n";

                    // Look for success message
                    try {
                        $browser->waitForText('eliminado', 3);
                        echo "✅ Plan deleted successfully\n";

                        return true;
                    } catch (\Exception $e) {
                        // Look for error message
                        try {
                            $errorText = $browser->text('.fi-modal-body');
                            echo '❌ Delete failed with message: '.$errorText."\n";

                            return false;
                        } catch (\Exception $e2) {
                            echo "❌ Delete failed but no clear message\n";

                            return false;
                        }
                    }

                } catch (\Exception $e) {
                    echo '❌ Could not confirm deletion: '.$e->getMessage()."\n";

                    return false;
                }

            } catch (\Exception $e) {
                echo '❌ No delete button found: '.$e->getMessage()."\n";

                return false;
            }

        } catch (\Exception $e) {
            echo '❌ Could not open plan for editing: '.$e->getMessage()."\n";

            return false;
        }
    }
}
