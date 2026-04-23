<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminInvoicesTest extends DuskTestCase
{
    /**
     * Test admin invoices page accessibility and functionality
     */
    public function test_admin_invoices_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                ->waitForText('Iniciar sesión', 10)
                ->assertSee('Iniciar sesión')
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->visit('/admin/invoices')
                ->waitForText('Facturas', 15)
                ->assertSee('Facturas')
                ->assertSeeIn('h1', 'Facturas')
                ->screenshot('admin-invoices-list');
        });
    }

    /**
     * Test that the invoices navigation item exists
     */
    public function test_invoices_navigation_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                ->waitForText('Iniciar sesión', 10)
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->waitForText('Facturación', 10)
                ->clickLink('Facturas')
                ->waitForLocation('/admin/invoices', 10)
                ->assertPathIs('/admin/invoices')
                ->assertSee('Facturas')
                ->screenshot('admin-invoices-navigation');
        });
    }

    /**
     * Test that we can access the create invoice page
     */
    public function test_can_access_create_invoice_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                ->waitForText('Iniciar sesión', 10)
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->visit('/admin/invoices/create')
                ->waitForText('Crear Factura', 15)
                ->assertSee('Crear Factura')
                ->assertSee('Información General')
                ->assertSee('Número de Factura')
                ->screenshot('admin-invoices-create');
        });
    }

    /**
     * Test that page redirects to login if not authenticated
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/invoices')
                ->waitForLocation('/admin/login', 10)
                ->assertPathIs('/admin/login')
                ->assertSee('Iniciar sesión')
                ->screenshot('admin-invoices-unauthenticated');
        });
    }

    /**
     * Test that JavaScript errors don't occur
     */
    public function test_no_javascript_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                ->waitForText('Iniciar sesión', 10)
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin')
                ->visit('/admin/invoices')
                ->waitForText('Facturas', 15)
                ->assertSee('Facturas');

            // Check for JavaScript errors
            $errors = $browser->driver->manage()->getLog('browser');
            $this->assertEmpty($errors, 'JavaScript errors found: '.json_encode($errors));
        });
    }
}
