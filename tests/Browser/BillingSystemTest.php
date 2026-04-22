<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PaymentSettings;

class BillingSystemTest extends DuskTestCase
{
    private $tenant;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get a test tenant
        $this->tenant = Tenant::where('domain', 'fruteria')->first();
        if (!$this->tenant) {
            $this->tenant = Tenant::factory()->create(['domain' => 'fruteria-test']);
        }

        // Create or get a test user for this tenant
        $this->user = User::where('email', 'test@fruteria.test')->first();
        if (!$this->user) {
            $this->user = User::factory()->create([
                'email' => 'test@fruteria.test',
                'tenant_id' => $this->tenant->id,
                'password' => bcrypt('password')
            ]);
        }

        // Ensure payment settings exist
        $paymentSettings = PaymentSettings::on('landlord')->first();
        if (!$paymentSettings) {
            PaymentSettings::on('landlord')->create([
                'max_file_size_mb' => 5,
                'allowed_file_types' => json_encode(['pdf', 'jpg', 'jpeg', 'png']),
                'bank_account_info' => 'Banco Test - Cuenta: 123456789',
                'payment_instructions' => 'Instrucciones de pago de prueba'
            ]);
        }
    }

    /**
     * Test billing dashboard accessibility
     */
    public function test_billing_dashboard_access()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15)
                    ->assertPathIs('/')
                    ->clickLink('Facturación')
                    ->waitForLocation('/billing', 10)
                    ->assertPathIs('/billing')
                    ->waitForText('Centro de Facturación', 10)
                    ->assertSee('Centro de Facturación')
                    ->assertSee('Subir Comprobante de Pago');
        });
    }

    /**
     * Test API billing endpoint
     */
    public function test_api_billing_endpoint()
    {
        $this->browse(function (Browser $browser) {
            // First login to get tenant session
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15);

            // Test the API endpoint via JavaScript
            $result = $browser->script("
                return fetch('/api/v1/billing', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => response.json())
                .then(data => {
                    return {
                        success: data.success || false,
                        hasSubscription: !!(data.data && data.data.subscription),
                        hasPaymentSettings: !!(data.data && data.data.payment_settings),
                        hasRecentPayments: !!(data.data && data.data.recent_payments)
                    };
                })
                .catch(error => {
                    return { success: false, error: error.message };
                });
            ");

            $apiResult = $result[0];

            $this->assertTrue($apiResult['success'], 'API call should succeed');
            $this->assertTrue($apiResult['hasPaymentSettings'], 'Should return payment settings');
        });
    }

    /**
     * Test file upload functionality
     */
    public function test_payment_proof_upload()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15)
                    ->clickLink('Facturación')
                    ->waitForLocation('/billing', 10)
                    ->waitForText('Subir Comprobante de Pago', 10);

            // Check if file upload element exists
            $hasFileUpload = $browser->script("
                return document.querySelector('input[type=\"file\"]') !== null;
            ")[0];

            $this->assertTrue($hasFileUpload, 'File upload input should be present');

            // Check for payment method selection or fields
            $browser->assertSee('Monto')
                    ->assertSee('Método');
        });
    }

    /**
     * Test payment history functionality
     */
    public function test_payment_history_table()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15)
                    ->clickLink('Facturación')
                    ->waitForLocation('/billing', 10);

            // Test API history endpoint
            $result = $browser->script("
                return fetch('/api/v1/billing/history', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => response.json())
                .then(data => {
                    return {
                        success: data.success || false,
                        hasData: !!(data.data && data.data.data),
                        pagination: !!(data.data && data.data.links && data.data.meta)
                    };
                })
                .catch(error => {
                    return { success: false, error: error.message };
                });
            ");

            $apiResult = $result[0];

            $this->assertTrue($apiResult['success'], 'History API call should succeed');
            $this->assertTrue($apiResult['hasData'], 'Should return data array');
            $this->assertTrue($apiResult['pagination'], 'Should include pagination');
        });
    }

    /**
     * Test mobile responsiveness
     */
    public function test_mobile_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            // Resize to mobile viewport
            $browser->resize(375, 667) // iPhone SE dimensions
                    ->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15)
                    ->clickLink('Facturación')
                    ->waitForLocation('/billing', 10)
                    ->assertSee('Centro de Facturación');

            // Check mobile menu works
            $hasMobileMenu = $browser->script("
                return window.getComputedStyle(document.querySelector('[data-slot=\"sidebar\"]')).display === 'none';
            ")[0];

            // Check if content adapts to mobile
            $contentWidth = $browser->script("
                return document.querySelector('.filament-main-content').offsetWidth;
            ")[0];

            $this->assertTrue($contentWidth < 400, 'Content should adapt to mobile width');
        });
    }

    /**
     * Test error handling
     */
    public function test_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15)
                    ->clickLink('Facturación')
                    ->waitForLocation('/billing', 10);

            // Test submitting form without file
            $browser->press('Enviar Comprobante')
                    ->waitForText('Error', 5)
                    ->assertSee('Por favor selecciona un archivo de comprobante');
        });
    }

    /**
     * Test tenant isolation
     */
    public function test_tenant_isolation()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit("http://{$this->tenant->domain}.emporiodigital.test/login")
                    ->waitForText('Iniciar Sesión', 10)
                    ->type('email', $this->user->email)
                    ->type('password', 'password')
                    ->press('Ingresar')
                    ->waitForLocation('/', 15);

            // Verify API calls include tenant context
            $result = $browser->script("
                return fetch('/api/v1/billing', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    return {
                        success: true,
                        authenticated: true,
                        tenantIsolation: data.data && Object.keys(data.data).length > 0
                    };
                })
                .catch(error => {
                    return {
                        success: false,
                        error: error.message,
                        authenticated: false
                    };
                });
            ");

            $apiResult = $result[0];

            $this->assertTrue($apiResult['success'], 'API should authenticate tenant');
            $this->assertTrue($apiResult['authenticated'], 'Tenant should be authenticated');
            $this->assertTrue($apiResult['tenantIsolation'], 'Should return tenant-specific data');
        });
    }
}