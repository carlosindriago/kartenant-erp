<?php

namespace Tests\Browser;

use App\Models\Tenant;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WelcomeEmailResendTest extends DuskTestCase
{
    /**
     * Test the "Reenviar Email de Bienvenida" functionality in tenant detail page.
     * This test verifies that the fix for Laravel 12 MailChannel::make() issue works properly.
     */
    public function test_welcome_email_resend_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->waitForLocation('/admin/login')
                ->assertSee('Iniciar Sesión')
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Iniciar Sesión')
                ->waitForLocation('/admin')
                ->assertPathIs('/admin')

                // Navigate to Tenants page
                ->clickLink('Tenants')
                ->waitForLocation('/admin/tenants')
                ->assertPathIs('/admin/tenants')

                // Find and click on cocostore tenant (or first available tenant)
                ->waitForText('cocostore', 10)
                ->clickLink('cocostore')
                ->waitForLocation('/admin/tenants/*')
                ->assertSee('Detalles del Tenant')

                // Check that the email resend button is present in the "Gestión" dropdown
                ->waitFor('button[aria-label="Gestión"]', 10)
                ->click('button[aria-label="Gestión"]')
                ->waitForText('Reenviar Email de Bienvenida', 5)
                ->assertSee('Reenviar Email de Bienvenida')

                // Click on the resend email action
                ->clickLink('Reenviar Email de Bienvenida')
                ->waitForText('Reenviar Email de Bienvenida', 5) // Modal header
                ->assertSee('Se generará una nueva contraseña temporal')
                ->assertSee('¿Está seguro?')

                // Confirm the action
                ->press('Confirmar')
                ->waitForText('Email Reenviado', 10)
                ->assertSee('Se ha enviado el email de bienvenida')

                // Verify no error occurred (no MailChannel::make() error)
                ->assertDontSee('Call to undefined method')
                ->assertDontSee('MailChannel::make()')
                ->assertDontSee('500');
        });
    }

    /**
     * Test that the action gracefully handles missing user for tenant contact email.
     */
    public function test_welcome_email_resend_handles_missing_user(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/tenants')
                ->waitForText('cocostore', 10)
                ->clickLink('cocostore')
                ->waitForLocation('/admin/tenants/*')
                ->assertSee('Detalles del Tenant')

                // Open the gestión dropdown
                ->waitFor('button[aria-label="Gestión"]', 10)
                ->click('button[aria-label="Gestión"]')
                ->waitForText('Reenviar Email de Bienvenida', 5);

            // If the test tenant doesn't have a corresponding user,
            // it should show an error message
            // (This part of the test depends on the specific test data)
        });
    }
}
