<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminPanelHealthTest extends DuskTestCase
{
    /**
     * Test basic admin panel accessibility and health.
     */
    public function test_admin_panel_basic_health(): void
    {
        $this->browse(function (Browser $browser) {
            // Test login page accessibility
            $browser->visit('https://emporiodigital.test/admin/login')
                ->waitFor('.filament-panel', 15)
                ->assertSee('Iniciar sesión')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]')
                ->screenshot('admin-login-page');

            // Test login functionality
            $browser->type('email', 'admin@emporiodigital.test')
                ->type('password', 'password')
                ->press('Iniciar sesión')
                ->waitForLocation('/admin', 20)
                ->assertPathIs('/admin')
                ->assertAuthenticated('superadmin')
                ->screenshot('admin-dashboard-loaded');

            // Test navigation elements
            $browser->waitFor('.filament-sidebar', 10)
                ->assertPresent('.filament-sidebar')
                ->assertPresent('.filament-topbar')
                ->assertSeeIn('h1', 'Panel de Administración')
                ->screenshot('admin-navigation-loaded');

            // Test key navigation links exist
            $navigationItems = [
                'Tenants',
                'Facturación',
                'Sistema',
            ];

            foreach ($navigationItems as $item) {
                $browser->assertSee($item);
            }

            $browser->screenshot('admin-navigation-items');

            // Test Tenant Management access
            $browser->clickLink('Tenants')
                ->waitForLocation('*/tenants', 10)
                ->assertPathIs('/admin/tenants')
                ->assertSee('Tenants')
                ->screenshot('admin-tenants-page');

            // Test Billing System access
            $browser->clickLink('Comprobantes de Pago')
                ->waitForLocation('*/payment-proofs', 10)
                ->assertPathIs('/admin/payment-proofs')
                ->assertSee('Comprobantes de Pago')
                ->screenshot('admin-payment-proofs-page');

            // Test Invoices access
            $browser->clickLink('Facturas')
                ->waitForLocation('*/invoices', 10)
                ->assertPathIs('/admin/invoices')
                ->assertSee('Facturas')
                ->screenshot('admin-invoices-page');

            // Test System > Backups
            $browser->clickLink('Sistema')
                ->waitForText('Backups', 10)
                ->clickLink('Backups')
                ->waitForLocation('*/backups', 10)
                ->assertPathIs('/admin/backups')
                ->assertSee('Backups')
                ->screenshot('admin-backups-page');

            // Test System > Bug Reports
            $browser->clickLink('Tickets de Soporte')
                ->waitForLocation('*/bug-reports', 10)
                ->assertPathIs('/admin/bug-reports')
                ->assertSee('Tickets de Soporte')
                ->screenshot('admin-bug-reports-page');

            // Test logout
            $browser->waitFor('.filament-user-menu', 10)
                ->click('.filament-user-menu button')
                ->waitForText('Cerrar sesión', 5)
                ->clickLink('Cerrar sesión')
                ->waitForLocation('/admin/login', 10)
                ->assertPathIs('/admin/login')
                ->assertGuest('superadmin')
                ->screenshot('admin-logout-success');
        });
    }

    /**
     * Test for 404 and 500 errors.
     */
    public function test_admin_panel_no_critical_errors(): void
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'password')
                ->press('Iniciar sesión')
                ->waitForLocation('/admin', 20);

            $routes = [
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
                    ->assertDontSee('Page Not Found')
                    ->screenshot('route-'.str_replace('/', '-', $route).'-success');
            }
        });
    }

    /**
     * Test billing system basic functionality.
     */
    public function test_billing_system_basic_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'password')
                ->press('Iniciar sesión')
                ->waitForLocation('/admin', 20);

            // Test Payment Proofs Resource
            $browser->visit('https://emporiodigital.test/admin/payment-proofs')
                ->waitFor('.filament-tables-container', 10)
                ->assertSee('Comprobantes de Pago')
                ->screenshot('payment-proofs-list');

            // Check if create button exists
            $createButton = $browser->element('.filament-button[data-action="create"]');
            if ($createButton) {
                $browser->clickAndWaitForReload('.filament-button[data-action="create"]', 5)
                    ->waitFor('.filament-form', 10)
                    ->assertSee('Crear Comprobante de Pago')
                    ->screenshot('payment-proof-create-form-accessible');
            }

            // Test Invoices Resource
            $browser->visit('https://emporiodigital.test/admin/invoices')
                ->waitFor('.filament-tables-container', 10)
                ->assertSee('Facturas')
                ->screenshot('invoices-list');

            // Check if create button exists
            $createInvoiceButton = $browser->element('.filament-button[data-action="create"]');
            if ($createInvoiceButton) {
                $browser->clickAndWaitForReload('.filament-button[data-action="create"]', 5)
                    ->waitFor('.filament-form', 10)
                    ->assertSee('Crear Factura')
                    ->screenshot('invoice-create-form-accessible');
            }
        });
    }
}
