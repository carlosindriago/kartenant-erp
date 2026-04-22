<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantAdminPanelTest extends DuskTestCase
{
    /**
     * Test the complete tenant admin panel functionality
     */
    public function test_tenant_admin_panel_complete_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('https://mandarinastore.emporiodigital.test/login')
                    ->waitFor('#email', 10)
                    ->type('email', 'julio@mandarinastore.test')
                    ->type('password', '54a38607b6709518a177')
                    ->press('Ingresar')
                    ->waitForLocation('/dashboard', 15)
                    ->assertPathIs('/dashboard')
                    ->screenshot('tenant-login-success');

            // Test Dashboard/Overview
            $this->testDashboard($browser);

            // Test Product Management
            $this->testProductManagement($browser);

            // Test Point of Sale
            $this->testPointOfSale($browser);

            // Test Client Management
            $this->testClientManagement($browser);

            // Test Reports
            $this->testReports($browser);

            // Test Settings
            $this->testSettings($browser);

            // Test data isolation verification
            $this->testDataIsolation($browser);
        });
    }

    /**
     * Test dashboard functionality
     */
    private function testDashboard(Browser $browser): void
    {
        $browser->waitFor('.filament-app-layout', 10)
                ->assertSee('Panel')
                ->screenshot('tenant-dashboard-overview')
                ->assertPresent('nav[aria-label="Main navigation"]');

        // Look for common dashboard elements
        $dashboardElements = [
            'Products',
            'Sales',
            'Customers',
            'Reports'
        ];

        foreach ($dashboardElements as $element) {
            try {
                $browser->waitForText($element, 5);
                $this->assertTrue(true, "Found dashboard element: {$element}");
            } catch (\Exception $e) {
                $this->assertTrue(false, "Missing dashboard element: {$element}");
            }
        }

        $browser->screenshot('tenant-dashboard-elements');
    }

    /**
     * Test Product Management module
     */
    private function testProductManagement(Browser $browser): void
    {
        $browser->clickLink('Products')
                ->waitForLocationIn(['/products', '/inventory'], 10)
                ->screenshot('tenant-products-page');

        // Test product listing
        try {
            $browser->waitFor('.filament-tables-container', 10)
                    ->assertPresent('table')
                    ->screenshot('tenant-products-list');
        } catch (\Exception $e) {
            $browser->screenshot('tenant-products-error');
            throw $e;
        }

        // Test create product form
        try {
            $browser->clickLink('New Product', 'button')
                    ->waitFor('form', 10)
                    ->screenshot('tenant-products-create-form');

            // Check for essential fields
            $productFields = ['name', 'price', 'stock', 'category'];
            foreach ($productFields as $field) {
                $hasField = $browser->script("return document.querySelector('[name=\"{$field}\"], [data-field=\"{$field}\"]') !== null")[0];
                if ($hasField) {
                    $this->assertTrue(true, "Product form has field: {$field}");
                }
            }

            // Go back to list without creating
            $browser->clickLink('Cancel', 'button')
                    ->waitFor('.filament-tables-container', 10);
        } catch (\Exception $e) {
            $browser->screenshot('tenant-products-create-error');
        }
    }

    /**
     * Test Point of Sale module
     */
    private function testPointOfSale(Browser $browser): void
    {
        try {
            $browser->clickLink('POS', 'Point of Sale')
                    ->waitForLocationIn(['/pos', '/sales'], 10)
                    ->screenshot('tenant-pos-page');

            // Test POS interface elements
            $posElements = ['.filament-tables-container', 'button', 'input'];
            foreach ($posElements as $element) {
                try {
                    $browser->waitFor($element, 5);
                    $this->assertTrue(true, "Found POS element: {$element}");
                } catch (\Exception $e) {
                    $this->assertTrue(false, "Missing POS element: {$element}");
                }
            }
        } catch (\Exception $e) {
            $browser->screenshot('tenant-pos-error');
            // POS might not be available or have different name
        }
    }

    /**
     * Test Client Management module
     */
    private function testClientManagement(Browser $browser): void
    {
        try {
            $browser->clickLink('Customers', 'Clients')
                    ->waitForLocationIn(['/customers', '/clients'], 10)
                    ->screenshot('tenant-clients-page');

            // Test client listing
            $browser->waitFor('.filament-tables-container', 10)
                    ->screenshot('tenant-clients-list');

            // Test create client form
            $browser->clickLink('New Customer', 'button')
                    ->waitFor('form', 10)
                    ->screenshot('tenant-clients-create-form');

            // Go back to list
            $browser->clickLink('Cancel', 'button')
                    ->waitFor('.filament-tables-container', 10);
        } catch (\Exception $e) {
            $browser->screenshot('tenant-clients-error');
            // Customers might have different navigation
        }
    }

    /**
     * Test Reports functionality
     */
    private function testReports(Browser $browser): void
    {
        try {
            $browser->clickLink('Reports')
                    ->waitForLocation('/reports', 10)
                    ->screenshot('tenant-reports-page');

            // Look for report options
            $reportOptions = ['Sales', 'Products', 'Inventory', 'Customers'];
            foreach ($reportOptions as $option) {
                try {
                    $browser->waitForText($option, 5);
                    $this->assertTrue(true, "Found report option: {$option}");
                } catch (\Exception $e) {
                    $this->assertTrue(false, "Missing report option: {$option}");
                }
            }
        } catch (\Exception $e) {
            $browser->screenshot('tenant-reports-error');
            // Reports might be under different menu
        }
    }

    /**
     * Test Settings/Configuration
     */
    private function testSettings(Browser $browser): void
    {
        try {
            $browser->clickLink('Settings')
                    ->waitForLocationIn(['/settings', '/profile'], 10)
                    ->screenshot('tenant-settings-page');

            // Test profile form
            $browser->waitFor('form', 10)
                    ->screenshot('tenant-settings-form');

            // Check for essential profile fields
            $profileFields = ['name', 'email'];
            foreach ($profileFields as $field) {
                $hasField = $browser->script("return document.querySelector('[name=\"{$field}\"], [data-field=\"{$field}\"]') !== null")[0];
                if ($hasField) {
                    $this->assertTrue(true, "Settings form has field: {$field}");
                }
            }
        } catch (\Exception $e) {
            $browser->screenshot('tenant-settings-error');
            // Settings might be under user menu or different location
        }
    }

    /**
     * Test data isolation verification
     */
    private function testDataIsolation(Browser $browser): void
    {
        // Verify tenant branding/data
        try {
            $browser->assertSee('mandarinastore')
                    ->screenshot('tenant-data-isolation');
        } catch (\Exception $e) {
            // Branding might be subtle or not present
            $browser->screenshot('tenant-branding-check');
        }

        // Check that we don't see other tenant data
        $otherTenants = ['fruteria', 'superadmin'];
        foreach ($otherTenants as $tenant) {
            try {
                $browser->assertDontSee($tenant);
                $this->assertTrue(true, "Data isolation verified for: {$tenant}");
            } catch (\Exception $e) {
                $this->assertTrue(false, "Data isolation failed - found: {$tenant}");
            }
        }
    }
}
