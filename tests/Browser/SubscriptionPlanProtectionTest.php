<?php

namespace Tests\Browser;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubscriptionPlanProtectionTest extends DuskTestCase
{
    /**
     * Test que verifica la protección contra eliminación de planes con suscripciones activas
     */
    public function test_cannot_delete_plan_with_active_subscriptions(): void
    {
        $this->browse(function (Browser $browser) {
            // Crear un tenant y plan con suscripción activa para pruebas
            $tenant = Tenant::factory()->create(['domain' => 'test-protection']);
            $plan = SubscriptionPlan::factory()->create([
                'name' => 'Plan Protección Test',
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => false,
            ]);

            TenantSubscription::factory()->create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
            ]);

            $browser->visit('/admin/login')
                ->type('email', 'admin@emporiodigital.com')
                ->type('password', 'password')
                ->press('Ingresar')
                ->waitForText('Dashboard')
                ->assertPathIs('/admin')
                ->visit('/admin/subscription-plans')
                ->waitForText('Planes de Suscripción')
                ->selectRecord($plan->id)
                ->click('button[filament-action="delete"]')
                ->waitForText('¿Estás seguro?')
                ->press('Eliminar')
                ->pause(500) // Esperar posible excepción
                ->assertSee('No se puede eliminar el plan porque tiene suscripciones activas');

            // Limpiar datos de prueba
            TenantSubscription::where('subscription_plan_id', $plan->id)->delete();
            $plan->delete();
            $tenant->delete();
        });
    }

    /**
     * Test que verifica la protección contra eliminación de planes activos
     */
    public function test_cannot_delete_active_visible_featured_plans(): void
    {
        $this->browse(function (Browser $browser) {
            // Crear plan activo, visible y destacado SIN suscripciones
            $plan = SubscriptionPlan::factory()->create([
                'name' => 'Plan Activo Test',
                'is_active' => true,
                'is_visible' => true,
                'is_featured' => true,
            ]);

            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/subscription-plans')
                ->waitForText('Planes de Suscripción')
                ->selectRecord($plan->id)
                ->click('button[filament-action="delete"]')
                ->waitForText('¿Estás seguro?')
                ->press('Eliminar')
                ->pause(500) // Esperar posible excepción
                ->assertSee('No se puede eliminar un plan que está activo');

            $plan->delete();
        });
    }

    /**
     * Test que verifica la separación correcta de páginas activas vs archivadas
     */
    public function test_page_separation_active_vs_archived(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/subscription-plans')
                ->waitForText('Planes de Suscripción')
                    // Verificar que solo muestra planes activos (deberían ser 2 registros como se menciona)
                ->assertSeeIn('[data-component="filament-tables-table"]', 'Basic') // Plan activo
                ->assertSeeIn('[data-component="filament-tables-table"]', 'Premium') // Plan activo
                    // Verificar botón de navegación a archivados
                ->clickLink('Ver Planes Archivados')
                ->waitForLocation('/admin/subscription-plans/archived')
                ->waitForText('Planes Archivados')
                    // Verificar que muestra planes archivados (deberían ser muchos más)
                ->assertSeeIn('[data-component="filament-tables-table"]', 'Inactive');
        });
    }

    /**
     * Test que verifica bulk actions en página de archivados
     */
    public function test_bulk_actions_in_archived_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/subscription-plans/archived')
                ->waitForText('Planes Archivados')
                    // Verificar que existen bulk actions específicas para archivados
                ->assertSee('Activar seleccionados')
                ->assertSee('Restaurar seleccionados')
                ->assertSee('Eliminar seleccionados')
                    // Verificar que no aparece el botón de archivar (porque ya están archivados)
                ->assertDontSee('Archivar seleccionados');
        });
    }

    /**
     * Test que verifica que solo se pueden eliminar planes sin restricciones
     */
    public function test_can_only_delete_unrestricted_plans(): void
    {
        $this->browse(function (Browser $browser) {
            // Crear plan sin restricciones (puede eliminarse)
            $deletablePlan = SubscriptionPlan::factory()->create([
                'name' => 'Plan Eliminable Test',
                'is_active' => false,
                'is_visible' => false,
                'is_featured' => false,
            ]);

            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/subscription-plans')
                ->waitForText('Planes de Suscripción')
                    // Buscar el plan eliminable
                ->click('[data-component="filament-tables-search-input"] input')
                ->type('[data-component="filament-tables-search-input"] input', 'Plan Eliminable Test')
                ->pause(1000)
                ->selectRecord($deletablePlan->id)
                ->click('button[filament-action="delete"]')
                ->waitForText('¿Estás seguro?')
                ->press('Eliminar')
                ->waitForText('Eliminado')
                ->assertDontSee('Plan Eliminable Test');

            // Verificar que el plan fue eliminado (soft-deleted)
            $this->assertSoftDeleted('subscription_plans', ['id' => $deletablePlan->id]);
        });
    }

    /**
     * Test de navegación - verifica que el botón abre en misma pestaña
     */
    public function test_navigation_opens_in_same_tab(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'admin@emporiodigital.com')->first())
                ->visit('/admin/subscription-plans')
                ->waitForText('Planes de Suscripción')
                    // Verificar que el link no tiene target="_blank
                ->assertAttributeMissing('a[href*="archived"]', 'target')
                ->clickLink('Ver Planes Archivados')
                ->waitForLocation('/admin/subscription-plans/archived')
                ->assertPathIs('/admin/subscription-plans/archived');
        });
    }
}
