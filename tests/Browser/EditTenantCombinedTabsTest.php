<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Tenant;

class EditTenantCombinedTabsTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user for testing
        $this->user = User::firstOrCreate(
            ['email' => 'test@admin.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    /**
     * Test that the combined tabs mode works correctly
     */
    public function test_combined_tabs_structure_and_button_positioning()
    {
        $this->browse(function (Browser $browser) {
            // Get test tenant
            $tenant = Tenant::where('id', '10')->first();

            if (!$tenant) {
                $this->markTestSkipped('Test tenant not found');
            }

            $browser->loginAs($this->user)
                    ->visit('/admin/tenants/' . $tenant->id . '/edit')
                    ->waitFor('.fi-ta-content', 10)
                    ->pause(1000); // Allow tabs to render

            // 1. Test tab structure and ordering
            echo "🧪 Testing tab structure and ordering...\n";

            // Check if tabs are present
            $browser->assertVisible('.fi-ta-tabs');

            // Get all tab elements
            $tabs = $browser->elements('.fi-ta-tabs .fi-ta-tab');
            $this->assertGreaterThan(0, count($tabs), 'No tabs found');

            // Expected tab order based on ContentTabPosition::After
            // Tab 1: "Módulos y Add-ons" (RelationManager)
            // Tab 2: "Información" (Form with buttons)

            // Verify first tab is "Módulos y Add-ons"
            $firstTabText = $browser->text('.fi-ta-tabs .fi-ta-tab:nth-child(1)');
            echo "First tab text: $firstTabText\n";
            $this->assertStringContainsString('Módulos y Add-ons', $firstTabText, 'First tab should be "Módulos y Add-ons"');

            // Verify second tab is "Información"
            $secondTabText = $browser->text('.fi-ta-tabs .fi-ta-tab:nth-child(2)');
            echo "Second tab text: $secondTabText\n";
            $this->assertStringContainsString('Información', $secondTabText, 'Second tab should be "Información"');

            // Check that "Información" tab has the document-text icon
            $infoTabHasIcon = $browser->element('.fi-ta-tabs .fi-ta-tab:nth-child(2) svg.heroicon-o-document-text');
            $this->assertNotNull($infoTabHasIcon, 'Información tab should have document-text icon');

            echo "✅ Tab structure and ordering verified correctly\n";

            // 2. Test button positioning inside tabs
            echo "\n🧪 Testing Save/Cancel buttons positioning...\n";

            // Initially, buttons should not be visible since we're on the first tab (Modules)
            $browser->assertDontSee('Guardar Cambios')
                    ->assertDontSee('Cancelar');

            // Click on "Información" tab
            $browser->click('.fi-ta-tabs .fi-ta-tab:nth-child(2)')
                    ->pause(500) // Allow tab content to load
                    ->waitFor('.fi-form', 5);

            // Now Save/Cancel buttons should be visible inside the tab
            $browser->assertSee('Guardar Cambios')
                    ->assertSee('Cancelar');

            // Check that buttons are inside the tab content area, not outside
            $tabContent = $browser->element('.fi-ta-content .fi-ta-tab-content.active');
            $this->assertNotNull($tabContent, 'Active tab content should be visible');

            // Verify buttons are in the tab content
            $saveButtonInTab = $browser->element('.fi-ta-content .fi-ta-tab-content.active button[type="submit"]');
            $this->assertNotNull($saveButtonInTab, 'Save button should be inside tab content');

            // Check button labels
            $saveButtonText = $browser->text('.fi-ta-content .fi-ta-tab-content.active button[type="submit"]');
            $this->assertStringContainsString('Guardar Cambios', $saveButtonText, 'Save button should have correct label');

            $cancelButtonText = $browser->text('.fi-ta-content .fi-ta-tab-content.active .fi-action-button[href*="cancel"]');
            $this->assertStringContainsString('Cancelar', $cancelButtonText, 'Cancel button should have correct label');

            echo "✅ Save/Cancel buttons correctly positioned inside tabs\n";

            // 3. Test form functionality
            echo "\n🧪 Testing form functionality...\n";

            // Switch to "Información" tab if not already active
            if (!$browser->element('.fi-ta-tabs .fi-ta-tab:nth-child(2).active')) {
                $browser->click('.fi-ta-tabs .fi-ta-tab:nth-child(2)')
                        ->pause(500);
            }

            // Verify form fields are present and accessible
            $browser->assertVisible('input[name="tenant.name"]')
                    ->assertVisible('input[name="tenant.domain"]')
                    ->assertVisible('input[name="tenant.email"]');

            // Test that form fields can be edited
            $browser->type('tenant.name', 'Updated Test Tenant Name')
                    ->pause(200);

            // Verify the value was entered
            $nameValue = $browser->value('input[name="tenant.name"]');
            $this->assertStringContainsString('Updated Test Tenant Name', $nameValue, 'Name field should accept input');

            echo "✅ Form functionality working correctly\n";

            // 4. Test cancel button functionality
            echo "\n🧪 Testing cancel button...\n";

            $browser->click('.fi-ta-content .fi-ta-tab-content.active .fi-action-button[href*="cancel"]')
                    ->pause(1000);

            // Should redirect to view page
            $browser->assertPathMatches('/admin\/tenants\/' . $tenant->id);

            echo "✅ Cancel button working correctly\n";

            // 5. Go back to edit page for final verification
            echo "\n🧪 Final verification of tab order...\n";

            $browser->visit('/admin/tenants/' . $tenant->id . '/edit')
                    ->waitFor('.fi-ta-content', 10)
                    ->pause(1000);

            // Verify tab order is consistent
            $tabsAfterRefresh = $browser->elements('.fi-ta-tabs .fi-ta-tab');
            $this->assertEquals(count($tabs), count($tabsAfterRefresh), 'Tab count should be consistent');

            // Verify the order: Modules first, Information second
            $firstTabAfterRefresh = $browser->text('.fi-ta-tabs .fi-ta-tab:nth-child(1)');
            $secondTabAfterRefresh = $browser->text('.fi-ta-tabs .fi-ta-tab:nth-child(2)');

            $this->assertStringContainsString('Módulos y Add-ons', $firstTabAfterRefresh, 'First tab should remain "Módulos y Add-ons"');
            $this->assertStringContainsString('Información', $secondTabAfterRefresh, 'Second tab should remain "Información"');

            echo "✅ Tab order consistency verified\n";

        });
    }

    /**
     * Test that buttons don't appear outside tabs (original bug)
     */
    public function test_buttons_dont_appear_outside_tabs()
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::where('id', '10')->first();

            if (!$tenant) {
                $this->markTestSkipped('Test tenant not found');
            }

            $browser->loginAs($this->user)
                    ->visit('/admin/tenants/' . $tenant->id . '/edit')
                    ->waitFor('.fi-ta-content', 10)
                    ->pause(1000);

            // Check that Save/Cancel buttons are NOT outside the tab system
            $browser->assertDontSeeIn('.fi-ta-content', 'Guardar Cambios')
                    ->assertDontSeeIn('.fi-ta-content', 'Cancelar');

            // Click on Information tab to show buttons
            $browser->click('.fi-ta-tabs .fi-ta-tab:nth-child(2)')
                    ->pause(500);

            // Now buttons should be visible, but only inside the active tab content
            $browser->assertSeeIn('.fi-ta-content .fi-ta-tab-content.active', 'Guardar Cambios')
                    ->assertSeeIn('.fi-ta-content .fi-ta-tab-content.active', 'Cancelar');

            // Verify buttons are not in other tabs
            $browser->assertDontSeeIn('.fi-ta-tabs .fi-ta-tab:nth-child(1)', 'Guardar Cambios');
            $browser->assertDontSeeIn('.fi-ta-tabs .fi-ta-tab:nth-child(1)', 'Cancelar');
        });
    }
}
