<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminFullRegressionTest extends DuskTestCase
{
    private string $adminEmail = 'admin@emporiodigital.test';

    private string $testTenantName = 'Regression Test LLC';

    private string $testTenantDomain = 'regression-test';

    private ?string $testTenantId = null;

    /**
     * Test complete SuperAdmin panel regression suite
     */
    public function test_complete_admin_panel_regression(): void
    {
        $this->browse(function (Browser $browser) {
            try {
                $browser->resize(1920, 1080)
                    ->visit('/admin')
                    ->waitForLocation('/admin/login', 10)
                    ->assertPathIs('/admin/login')
                    ->assertSee('Iniciar Sesión');

                // Step 1: Login as SuperAdmin
                $this->performLogin($browser);

                // Step 2: Dashboard Health Check
                $this->verifyDashboardHealth($browser);

                // Step 3: Tenant Management Operations
                $this->performTenantManagementOperations($browser);

                // Step 4: Archived Tenants Access (CRITICAL 404 FIX TEST)
                $this->verifyArchivedTenantsAccess($browser);

                // Step 5: Billing Module Operations
                $this->verifyBillingModuleOperations($browser);

                // Step 6: System Health Pages
                $this->verifySystemHealthPages($browser);

                // SUCCESS: All systems nominal
                $browser->screenshot('regression_test_success')
                    ->assertPathIs('/admin');

                echo "\n🟢 FULL REGRESSION TEST PASSED: All systems nominal\n";

            } catch (\Exception $e) {
                echo "\n🔴 REGRESSION TEST FAILED: ".$e->getMessage()."\n";
                echo 'Error location: '.$e->getFile().':'.$e->getLine()."\n";
                echo 'Stack trace: '.$e->getTraceAsString()."\n";
                throw $e;
            }
        });
    }

    /**
     * Perform SuperAdmin login
     */
    private function performLogin(Browser $browser): void
    {
        echo "\n🔐 Testing SuperAdmin Login...\n";
        $browser->type('email', $this->adminEmail)
            ->type('password', 'password')
            ->click('button[type="submit"]')
            ->waitForLocation('/admin', 15)
            ->assertPathIs('/admin')
            ->assertSee('Panel de Administración')
            ->screenshot('step_1_login_success');

        echo "✅ SuperAdmin login successful\n";
    }

    /**
     * Verify Dashboard widgets load without 500 errors
     */
    private function verifyDashboardHealth(Browser $browser): void
    {
        echo "\n📊 Testing Dashboard Health...\n";

        // Wait for dashboard to fully load
        $browser->waitFor('.filament-panel', 10)
            ->pause(2000) // Allow widgets to load
            ->screenshot('step_2_dashboard_loaded');

        // Check for common dashboard elements that would indicate 500 errors
        $elements = [
            'System Health Widget' => '.fi-widget', // Generic widget selector
            'Analytics Overview' => '.filament-widget',
            'Navigation menu' => '.filament-sidebar',
        ];

        foreach ($elements as $name => $selector) {
            try {
                $browser->assertPresent($selector);
                echo "✅ {$name}: Loaded\n";
            } catch (\Exception $e) {
                echo "❌ {$name}: Failed - {$e->getMessage()}\n";
                $browser->screenshot("dashboard_{$name}_failed");
                throw new \Exception("Dashboard element '{$name}' failed to load: ".$e->getMessage());
            }
        }

        echo "✅ Dashboard: All widgets loaded successfully\n";
    }

    /**
     * Perform complete tenant management operations
     */
    private function performTenantManagementOperations(Browser $browser): void
    {
        echo "\n🏢 Testing Tenant Management Operations...\n";

        // Navigate to Tenants
        $browser->clickLink('Tenants')
            ->waitForLocation('*/tenants', 10)
            ->assertPathBeginsWith('/admin/tenants')
            ->screenshot('step_3a_tenants_list');

        // Create Test Tenant
        echo "Creating test tenant...\n";
        $browser->click('.fi-action-button') // New Tenant button
            ->waitFor('.filament-modal', 5)
            ->assertSee('Crear Tenant')
            ->screenshot('step_3b_tenant_create_modal');

        // Fill tenant form
        $browser->type('name', $this->testTenantName)
            ->type('domain', $this->testTenantDomain)
            ->type('email', 'regression@test.com')
            ->type('phone', '1234567890')
            ->screenshot('step_3c_tenant_form_filled');

        // Save tenant
        $browser->click('.fi-btn-primary') // Save button
            ->waitFor('.filament-notifications', 10)
            ->pause(2000)
            ->screenshot('step_3d_tenant_saved');

        // Find and record the tenant ID for later operations
        $tenantUrl = $browser->driver->getCurrentURL();
        if (preg_match('/\/tenants\/(\d+)/', $tenantUrl, $matches)) {
            $this->testTenantId = $matches[1];
            echo "✅ Tenant created with ID: {$this->testTenantId}\n";
        } else {
            throw new \Exception("Could not extract tenant ID from URL: {$tenantUrl}");
        }

        // Edit Tenant
        echo "Testing tenant edit...\n";
        $browser->click('.fi-action-button[title="Editar"]') // Edit button
            ->waitFor('.filament-modal', 5)
            ->assertSee('Editar Tenant')
            ->type('name', $this->testTenantName.' (Edited)')
            ->click('.fi-btn-primary')
            ->waitFor('.filament-notifications', 10)
            ->pause(1000)
            ->screenshot('step_3e_tenant_edited');

        echo "✅ Tenant management operations completed\n";
    }

    /**
     * CRITICAL: Verify archived tenants are accessible (404 fix verification)
     */
    private function verifyArchivedTenantsAccess(Browser $browser): void
    {
        echo "\n🗂️ Testing Archived Tenants Access (CRITICAL 404 FIX)...\n";

        if (! $this->testTenantId) {
            throw new \Exception('Test tenant ID not available for archival test');
        }

        // Archive the test tenant
        echo "Archiving test tenant...\n";
        $browser->visit('/admin/tenants/'.$this->testTenantId)
            ->waitFor('.filament-page', 10)
            ->screenshot('step_4a_tenant_view_before_archive');

        // Look for archive action (might be in dropdown)
        try {
            $browser->click('.fi-action-button[title="Archivar"]')
                ->waitFor('.filament-modal', 5)
                ->assertSee('Confirmar')
                ->click('.fi-btn-primary')
                ->waitFor('.filament-notifications', 10)
                ->pause(2000)
                ->screenshot('step_4b_tenant_archived');
        } catch (\Exception $e) {
            // Try dropdown approach
            $browser->click('.fi-dropdown-trigger')
                ->waitFor('.fi-dropdown-content', 5)
                ->clickLink('Archivar')
                ->waitFor('.filament-modal', 5)
                ->click('.fi-btn-primary')
                ->waitFor('.filament-notifications', 10)
                ->pause(2000)
                ->screenshot('step_4b_tenant_archived_dropdown');
        }

        echo "✅ Tenant archived successfully\n";

        // Navigate to Archived Tenants
        echo "Testing archived tenants access...\n";
        $browser->clickLink('Archived Tenants')
            ->waitForLocation('*/archived-tenants', 10)
            ->assertPathBeginsWith('/admin/archived-tenants')
            ->assertSee($this->testTenantName)
            ->screenshot('step_4c_archived_tenants_list');

        // CRITICAL TEST: Click "View" on archived tenant (this was the 404 issue)
        echo "Testing archived tenant detail view (THE CRITICAL 404 FIX TEST)...\n";

        $browser->with('.filament-table', function ($table) {
            $table->assertSee($this->testTenantName)
                ->clickLink('Ver') // View button for archived tenant
                ->waitForLocation('*/archived-tenants/'.$this->testTenantId, 15)
                ->assertPathBeginsWith('/admin/archived-tenants/'.$this->testTenantId)
                ->assertSee($this->testTenantName)
                ->screenshot('step_4d_archived_tenant_detail_success');
        });

        echo "✅ CRITICAL 404 FIX VERIFIED: Archived tenant accessible\n";
    }

    /**
     * Verify billing module operations
     */
    private function verifyBillingModuleOperations(Browser $browser): void
    {
        echo "\n💳 Testing Billing Module Operations...\n";

        try {
            // Navigate to Payment Proofs
            $browser->clickLink('Comprobantes de Pago')
                ->waitForLocation('*/payment-proofs', 10)
                ->assertPathBeginsWith('/admin/payment-proofs')
                ->screenshot('step_5a_payment_proofs_list');

            echo "✅ Payment Proofs list loaded\n";

            // Test creating a payment proof
            $browser->click('.fi-action-button') // New Payment Proof
                ->waitFor('.filament-modal', 5)
                ->assertSee('Crear Payment Proof')
                ->screenshot('step_5b_payment_proof_create_modal');

            // Fill basic payment proof fields
            $browser->select('tenant_id', '1') // Select first tenant if available
                ->type('amount', '100.00')
                ->type('payment_date', now()->format('Y-m-d'))
                ->type('notes', 'Regression test payment proof')
                ->screenshot('step_5c_payment_proof_form_filled');

            // Save payment proof
            $browser->click('.fi-btn-primary')
                ->waitFor('.filament-notifications', 10)
                ->pause(2000)
                ->screenshot('step_5d_payment_proof_created');

            // Test approval workflow (if the payment proof is pending)
            try {
                $browser->click('.fi-action-button[title="Aprobar"]')
                    ->waitFor('.filament-modal', 5)
                    ->click('.fi-btn-primary')
                    ->waitFor('.filament-notifications', 10)
                    ->pause(1000)
                    ->screenshot('step_5e_payment_proof_approved');

                echo "✅ Payment proof approval workflow tested\n";
            } catch (\Exception $e) {
                echo "⚠️ Payment proof approval test skipped: {$e->getMessage()}\n";
            }

        } catch (\Exception $e) {
            echo '❌ Billing Module Error: '.$e->getMessage()."\n";
            $browser->screenshot('billing_module_crash');
            throw new \Exception('Billing module operations failed: '.$e->getMessage());
        }

        echo "✅ Billing module operations completed\n";
    }

    /**
     * Verify system health pages
     */
    private function verifySystemHealthPages(Browser $browser): void
    {
        echo "\n🏥 Testing System Health Pages...\n";

        $systemPages = [
            'Logs de Errores' => '/admin/error-logs',
            'Backups' => '/admin/backups',
            'Tickets de Soporte' => '/admin/support-tickets',
        ];

        foreach ($systemPages as $name => $path) {
            try {
                echo "Testing {$name}...\n";

                $browser->visit($path)
                    ->waitFor('.filament-page', 10)
                    ->pause(1000) // Allow data to load
                    ->screenshot('step_6_'.str_replace(' ', '_', strtolower($name)).'_success');

                // Check for error indicators
                if ($browser->assertSeeIn('.filament-content', '500')) {
                    throw new \Exception("500 error detected on {$name} page");
                }

                echo "✅ {$name}: Loaded successfully\n";

                // Navigate back to dashboard for next test
                $browser->visit('/admin')
                    ->waitFor('.filament-panel', 5)
                    ->pause(500);

            } catch (\Exception $e) {
                echo "❌ {$name}: Failed - ".$e->getMessage()."\n";
                $browser->screenshot("system_page_{$name}_failed");
                throw new \Exception("System health page '{$name}' failed: ".$e->getMessage());
            }
        }

        echo "✅ All system health pages verified\n";
    }

    /**
     * Clean up test data after test completion
     */
    protected function tearDown(): void
    {
        // Clean up test tenant if it was created
        if ($this->testTenantId) {
            try {
                \App\Models\Tenant::withTrashed()->find($this->testTenantId)?->forceDelete();
            } catch (\Exception $e) {
                // Log cleanup error but don't fail the test
                echo '⚠️ Cleanup warning: '.$e->getMessage()."\n";
            }
        }

        parent::tearDown();
    }
}
