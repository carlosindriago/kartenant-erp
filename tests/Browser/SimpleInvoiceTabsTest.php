<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SimpleInvoiceTabsTest extends DuskTestCase
{
    /**
     * Test that InvoiceResource page loads with proper Filament v3 tabs
     */
    public function test_invoice_page_loads_with_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://nginx/admin/login')
                    ->waitForText('Iniciar Sesión', 10)
                    ->assertSee('Iniciar Sesión')
                    ->type('email', 'admin@emporiodigital.test')
                    ->type('password', 'emporiodigital123')
                    ->press('Iniciar Sesión')
                    ->waitForLocation('/admin', 15)
                    ->assertPathIs('/admin')
                    ->visit('https://nginx/admin/invoices')
                    ->waitFor('.filament-resources-page', 15)
                    ->assertSee('Facturas')
                    ->screenshot('invoice-page-success');

            // Check for the 7 Spanish tabs
            $expectedTabs = [
                'Todas',
                'Borrador', 
                'Enviadas',
                'Pagadas',
                'Vencidas',
                'Este Mes',
                'Vencen este Mes'
            ];

            foreach ($expectedTabs as $tabLabel) {
                $browser->assertSee($tabLabel);
            }

            // Verify no Filament compatibility errors
            $browser->assertDontSee('Method does not exist')
                    ->assertDontSee('Whoops')
                    ->assertDontSee('Server Error')
                    ->screenshot('invoice-tabs-verified');

            echo "✅ InvoiceResource tabs test passed - all tabs visible and working\n";
        });
    }
}
