<?php

namespace Tests\Browser;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubscriptionAlertsWidgetUITest extends DuskTestCase
{
    protected User $superadmin;

    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user for testing
        $this->superadmin = User::factory()->create([
            'name' => 'Super Admin UI Test',
            'email' => 'superadmin-ui@test.com',
            'is_super_admin' => true,
            'password' => bcrypt('password'),
        ]);

        // Create subscription plan for testing
        $this->plan = SubscriptionPlan::factory()->create([
            'name' => 'UI Test Plan',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function subscription_widget_displays_correctly_on_dashboard()
    {
        $this->browse(function (Browser $browser) {
            // Login as superadmin
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10);

            // Wait for widget to load
            $browser->pause(2000);

            // Check if subscription alerts widget is present
            $browser->assertPresent('.subscription-alerts-widget')
                ->assertVisible('.subscription-alerts-widget');
        });
    }

    /** @test */
    public function expired_subscriptions_display_with_critical_styling()
    {
        // Create tenant with expired subscription
        $expiredTenant = Tenant::factory()->create([
            'name' => 'Expired UI Test',
            'domain' => 'expired-ui',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiredTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(5),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000); // Wait for widget data

            // Check for critical alert styling
            $browser->assertSee('🚨 Suscripciones Críticas')
                ->assertSee('cliente(s) necesitan tu atención')
                ->assertSee('Suscripciones Expiradas')
                ->assertSee('EXPIRADO')
                ->assertSee('CRÍTICO');

            // Verify red color scheme is applied
            $browser->assertPresent('.bg-red-50')
                ->assertPresent('.border-red-500')
                ->assertPresent('.text-red-600');
        });
    }

    /** @test */
    public function expiring_soon_subscriptions_display_warning_styling()
    {
        // Create tenant with subscription expiring in 3 days
        $expiringTenant = Tenant::factory()->create([
            'name' => 'Expiring UI Test',
            'domain' => 'expiring-ui',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $expiringTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(3),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Check for warning styling
            $browser->assertSee('⚠️ Atención Requerida')
                ->assertSee('Vencen en 7 días')
                ->assertSee('URGENTE');

            // Verify yellow/orange color scheme
            $browser->assertPresent('.bg-yellow-50')
                ->assertPresent('.border-yellow-500')
                ->assertPresent('.text-yellow-600');
        });
    }

    /** @test */
    public function widget_links_are_clickable_and_navigate_to_view_pages()
    {
        // Create tenant for testing navigation
        $testTenant = Tenant::factory()->create([
            'name' => 'Navigation UI Test',
            'domain' => 'nav-ui-test',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $testTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($testTenant) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Find and click the tenant link in the widget
            $browser->click('.client-item a')
                ->waitForLocation("/admin/tenants/{$testTenant->id}", 10);

            // Verify we are on the correct view page
            $browser->assertPathIs("/admin/tenants/{$testTenant->id}")
                ->assertSee($testTenant->name);
        });
    }

    /** @test */
    public function hover_effects_show_ver_detalles_arrow()
    {
        // Create tenant for hover testing
        $hoverTenant = Tenant::factory()->create([
            'name' => 'Hover Test Tenant',
            'domain' => 'hover-test',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $hoverTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(1),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Move mouse over client item
            $browser->mouseover('.client-item')
                ->pause(500);

            // Check for "Ver detalles →" text appears on hover
            $browser->assertSee('Ver detalles →');

            // Move mouse away
            $browser->mouseout('.client-item')
                ->pause(500);

            // The "Ver detalles →" should still be visible due to CSS opacity transition
            // but we can verify the hover styling is applied
            $browser->assertPresent('.group-hover\\:opacity-100');
        });
    }

    /** @test */
    public function widget_is_touch_friendly_with_minimum_click_areas()
    {
        // Create tenant for touch testing
        $touchTenant = Tenant::factory()->create([
            'name' => 'Touch Test Tenant',
            'domain' => 'touch-test',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $touchTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Check for touch-friendly styling
            $browser->assertPresent('.client-item');

            // Verify minimum height requirement (44px for touch targets)
            $clientItemHeight = $browser->script('return window.getComputedStyle(document.querySelector(.client-item)).minHeight;')[0];
            $this->assertGreaterThanOrEqual(44, intval($clientItemHeight));

            // Test touch interaction
            $browser->tap('.client-item a')
                ->waitForLocation("/admin/tenants/{$touchTenant->id}", 10);
        });
    }

    /** @test */
    public function widget_responsive_on_mobile_devices()
    {
        // Create tenant for mobile testing
        $mobileTenant = Tenant::factory()->create([
            'name' => 'Mobile Test Tenant',
            'domain' => 'mobile-test',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $mobileTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'suspended',
        ]);

        $this->browse(function (Browser $browser) {
            // Set mobile viewport
            $browser->resize(375, 812); // iPhone X dimensions

            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Check widget displays correctly on mobile
            $browser->assertPresent('.subscription-alerts-widget')
                ->assertSee($mobileTenant->name);

            // Test mobile tap interaction
            $browser->tap('.client-item a')
                ->waitForLocation("/admin/tenants/{$mobileTenant->id}", 10);

            // Restore desktop size
            $browser->resize(1920, 1080);
        });
    }

    /** @test */
    public function widget_display_success_state_when_no_issues()
    {
        // No tenants with issues - should show success state

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Check for success state
            $browser->assertSee('¡Todo Perfecto!')
                ->assertSee('Todas las suscripciones están al día')
                ->assertPresent('.bg-green-50')
                ->assertPresent('.text-green-900');

            // Should not show any issue-related content
            $browser->assertDontSee('Suscripciones Expiradas')
                ->assertDontSee('Vencen en 7 días')
                ->assertDontSee('Cuentas Suspendidas');
        });
    }

    /** @test */
    public function language_displays_in_spanish_for_business_terms()
    {
        // Create tenant for language testing
        $langTenant = Tenant::factory()->create([
            'name' => 'Tenant Prueba',
            'domain' => 'tenant-prueba',
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $langTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
            'ends_at' => now()->subDays(3),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Verify Spanish business terms
            $browser->assertSee('cliente(s) necesitan tu atención')
                ->assertSee('Ver detalles →')
                ->assertSee('Suscripciones Expiradas')
                ->assertSee('Vencen en 7 días')
                ->assertSee('Cuentas Suspendidas')
                ->assertSee('Información Adicional');
        });
    }

    /** @test */
    public function widget_badges_display_correct_status_and_priority()
    {
        // Create tenants with different statuses
        $urgentTenant = Tenant::factory()->create(['name' => 'Urgent Tenant']);
        $normalTenant = Tenant::factory()->create(['name' => 'Normal Tenant']);

        TenantSubscription::factory()->create([
            'tenant_id' => $urgentTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(2), // 2 days = urgent
        ]);

        TenantSubscription::factory()->create([
            'tenant_id' => $normalTenant->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(6), // 6 days = normal
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                ->type('email', $this->superadmin->email)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/admin', 10)
                ->pause(3000);

            // Check for priority badges
            $browser->assertSee('URGENTE')
                ->assertSee('PRONTO')
                ->assertSee('ADVERTENCIA');

            // Verify badge styling
            $browser->assertPresent('.bg-orange-500') // Urgent
                ->assertPresent('.bg-yellow-500') // Normal/Prompt
                ->assertPresent('.text-white');   // Badge text
        });
    }
}
