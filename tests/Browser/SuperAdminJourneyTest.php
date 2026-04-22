<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SuperAdminJourneyTest extends DuskTestCase
{
    /**
     * Test complete SuperAdmin journey through the admin panel.
     *
     * This test simulates a "day in the life" of a SuperAdmin to catch
     * hidden crashes, broken links, or logic errors across the entire panel.
     */
    public function test_complete_super_admin_journey(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->waitForLocation('/admin/login', 10)
                    ->assertSee('Iniciar sesión')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]');

            // Phase 1: Access & Auth
            $browser->type('email', 'admin@emporiodigital.test')
                    ->type('password', 'password')
                    ->press('Iniciar sesión')
                    ->waitForLocation('/admin', 15)
                    ->assertPathIs('/admin')
                    ->assertAuthenticated('superadmin');

            // Phase 2: Dashboard Inspection
            $browser->waitFor('.filament-panel', 10)
                    ->assertPresent('.filament-panel')
                    ->screenshot('dashboard-loaded')
                    ->assertSeeIn('h1', 'Panel de Administración')
                    ->assertPresent('.filament-sidebar')
                    ->assertPresent('.filament-topbar');

            // Check for JavaScript errors in console
            $consoleErrors = $browser->script("return window.console.errors || []");
            if (!empty($consoleErrors[0])) {
                $browser->screenshot('dashboard-console-errors');
                throw new \Exception('JavaScript errors found on dashboard: ' . json_encode($consoleErrors[0]));
            }

            // Phase 3: Tenant Management (CRUD Operations)

            // Navigate to Tenants section
            $browser->waitForText('Tenants', 10)
                    ->clickLink('Tenants')
                    ->waitForLocation('*/tenants', 10)
                    ->assertPathIs('/admin/tenants')
                    ->screenshot('tenants-list-loaded');

            // Create Test Tenant
            $testTenantName = 'Dusk Test Store ' . time();
            $testTenantDomain = 'dusk-test-' . time();

            $browser->waitFor('.filament-tables-header-container', 10)
                    ->clickAndWaitForReload('.filament-button[data-action="create"]', 5)
                    ->waitForLocation('*/tenants/create', 10)
                    ->assertPathIs('/admin/tenants/create')
                    ->screenshot('tenant-create-form');

            // Fill tenant creation form
            $browser->waitFor('input[name="data[name]"]', 10)
                    ->type('data[name]', $testTenantName)
                    ->type('data[domain]', $testTenantDomain)
                    ->type('data[database]', $testTenantDomain)
                    ->type('data[email]', 'test@dusk.com')
                    ->type('data[phone]', '+1234567890')
                    ->type('data[address]', '123 Dusk Test Street')
                    ->type('data[city]', 'Test City')
                    ->type('data[state]', 'Test State')
                    ->type('data[country]', 'Test Country')
                    ->type('data[postal_code]', '12345')
                    ->press('Crear')
                    ->waitForLocation('*/tenants', 15)
                    ->screenshot('tenant-created');

            // Verify tenant creation
            $browser->assertSee($testTenantName)
                    ->assertSee($testTenantDomain);

            // Edit Test Tenant
            $browser->clickLink($testTenantName)
                    ->waitForLocation('*/tenants/*', 10)
                    ->assertPathBeginsWith('/admin/tenants/')
                    ->screenshot('tenant-view-page');

            $browser->clickAndWaitForReload('.filament-button[data-action="edit"]', 5)
                    ->waitFor('.filament-form', 10)
                    ->screenshot('tenant-edit-form');

            $editedTenantName = $testTenantName . ' Edited';
            $browser->type('data[name]', $editedTenantName)
                    ->press('Guardar cambios')
                    ->waitForLocation('*/tenants', 15)
                    ->screenshot('tenant-edited');

            // Verify tenant edit
            $browser->assertSee($editedTenantName);

            // Archive Test Tenant
            $browser->clickLink($editedTenantName)
                    ->waitForLocation('*/tenants/*', 10)
                    ->clickAndWaitForReload('.filament-button[data-action="delete"]', 5)
                    ->waitForDialog(5)
                    ->press('Archivar')
                    ->waitForLocation('*/tenants', 15)
                    ->screenshot('tenant-archived');

            // Phase 4: Billing System Verification (NEW)

            // Navigate to Payment Proofs
            $browser->waitForText('Comprobantes de Pago', 10)
                    ->clickLink('Comprobantes de Pago')
                    ->waitForLocation('*/payment-proofs', 10)
                    ->assertPathIs('/admin/payment-proofs')
                    ->screenshot('payment-proofs-loaded')
                    ->assertSee('Comprobantes de Pago');

            // Navigate to Invoices
            $browser->clickLink('Facturas')
                    ->waitForLocation('*/invoices', 10)
                    ->assertPathIs('/admin/invoices')
                    ->screenshot('invoices-loaded')
                    ->assertSee('Facturas');

            // Phase 5: System Health & Operations

            // Navigate to System > Backups
            $browser->clickLink('Sistema')
                    ->waitForText('Backups', 10)
                    ->clickLink('Backups')
                    ->waitForLocation('*/backups', 10)
                    ->assertPathIs('/admin/backups')
                    ->screenshot('backups-loaded')
                    ->assertSee('Backups');

            // Navigate to System > Bug Reports
            $browser->clickLink('Tickets de Soporte')
                    ->waitForLocation('*/bug-reports', 10)
                    ->assertPathIs('/admin/bug-reports')
                    ->screenshot('bug-reports-loaded')
                    ->assertSee('Tickets de Soporte');

            // Navigate to System > Analytics
            $browser->clickLink('Analíticas')
                    ->waitForLocation('*/analytics', 10)
                    ->assertPathIs('/admin/analytics')
                    ->screenshot('analytics-loaded')
                    ->assertSee('Analíticas');

            // Phase 6: Exit & Logout

            // Click User Menu and Logout
            $browser->waitFor('.filament-user-menu', 10)
                    ->click('.filament-user-menu button')
                    ->waitForText('Cerrar sesión', 5)
                    ->clickLink('Cerrar sesión')
                    ->waitForLocation('/admin/login', 10)
                    ->assertPathIs('/admin/login')
                    ->assertGuest('superadmin')
                    ->screenshot('logout-success');

            // Final success screenshot
            $browser->screenshot('journey-complete');
        });
    }

    /**
     * Test for specific broken routes and error handling.
     */
    public function test_admin_panel_route_integrity(): void
    {
        $this->browse(function (Browser $browser) {
            $routes = [
                '/admin',
                '/admin/tenants',
                '/admin/payment-proofs',
                '/admin/invoices',
                '/admin/backups',
                '/admin/bug-reports',
                '/admin/analytics',
            ];

            foreach ($routes as $route) {
                $browser->visit("https://emporiodigital.test{$route}")
                        ->waitFor('.filament-app', 15)
                        ->assertDontSee('404')
                        ->assertDontSee('500')
                        ->assertDontSee('Server Error')
                        ->screenshot('route-' . str_replace('/', '-', $route));
            }
        });
    }

    /**
     * Test billing system specific functionality.
     */
    public function test_billing_system_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            // Login as admin
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('email', 'admin@emporiodigital.test')
                    ->type('password', 'password')
                    ->press('Iniciar sesión')
                    ->waitForLocation('/admin', 15);

            // Test Payment Proofs Resource
            $browser->clickLink('Comprobantes de Pago')
                    ->waitForLocation('*/payment-proofs', 10)
                    ->assertSee('Comprobantes de Pago');

            // Check if we can access the create form
            $createButton = $browser->element('.filament-button[data-action="create"]');
            if ($createButton) {
                $browser->clickAndWaitForReload('.filament-button[data-action="create"]', 5)
                        ->waitForLocation('*/payment-proofs/create', 10)
                        ->assertPathIs('/admin/payment-proofs/create')
                        ->screenshot('payment-proof-create-form')
                        ->assertSee('Crear Comprobante de Pago');
            }

            // Test Invoices Resource
            $browser->visit('https://emporiodigital.test/admin/invoices')
                    ->waitFor('.filament-tables-container', 10)
                    ->assertSee('Facturas')
                    ->screenshot('invoices-list');

            // Check if we can access the create form
            $createInvoiceButton = $browser->element('.filament-button[data-action="create"]');
            if ($createInvoiceButton) {
                $browser->clickAndWaitForReload('.filament-button[data-action="create"]', 5)
                        ->waitForLocation('*/invoices/create', 10)
                        ->assertPathIs('/admin/invoices/create')
                        ->screenshot('invoice-create-form')
                        ->assertSee('Crear Factura');
            }
        });
    }
}