<?php

use Laravel\Dusk\Browser;
use App\Models\User;

it('can resend welcome email from tenant detail page', function () {
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

            // Find and click on cocostore tenant
            ->waitForText('cocostore', 10)
            ->clickLink('cocostore')
            ->waitForLocation('/admin/tenants/*')
            ->assertSee('Detalles del Tenant')

            // Check that the email resend button is present
            ->waitFor('button[aria-label="Gestión"]', 10)
            ->click('button[aria-label="Gestión"]')
            ->waitForText('Reenviar Email de Bienvenida', 5)
            ->assertSee('Reenviar Email de Bienvenida')

            // Take screenshot before action
            $browser->screenshot('welcome-email-before-action');

            // Click on the resend email action
            ->clickLink('Reenviar Email de Bienvenida')
            ->waitForText('Reenviar Email de Bienvenida', 5) // Modal header
            ->assertSee('Se generará una nueva contraseña temporal')

            // Take screenshot of modal
            $browser->screenshot('welcome-email-modal');

            // Confirm the action
            ->press('Confirmar')
            ->waitForText('Email Reenviado', 10)
            ->assertSee('Se ha enviado el email de bienvenida')

            // Take screenshot after action
            $browser->screenshot('welcome-email-after-action');

            // Verify no error occurred
            ->assertDontSee('Call to undefined method')
            ->assertDontSee('MailChannel::make()')
            ->assertDontSee('500');
    });
});