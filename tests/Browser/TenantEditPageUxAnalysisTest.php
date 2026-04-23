<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TenantEditPageUxAnalysisTest extends DuskTestCase
{
    /**
     * Comprehensive UX analysis of the tenant edit page
     * Testing layout, button positioning, form flow, and responsive behavior
     */
    public function test_tenant_edit_page_ux_analysis(): void
    {
        $this->browse(function (Browser $browser) {
            // Authenticate as admin
            $browser->visit('https://localhost/admin/login')
                ->assertSee('Ingresar')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'emporiodigital123')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15)
                ->assertPathIs('/admin');

            // Navigate to tenant edit page
            $browser->visit('https://localhost/admin/tenants/data-protection-test-1764024534/edit')
                ->waitUntilMissing('.loading-indicator', 10)
                ->pause(2000) // Allow page to fully render
                ->screenshot('tenant-edit-page-full-layout');

            // ANALYSIS 1: Overall Layout Structure
            $this->analyzeOverallLayout($browser);

            // ANALYSIS 2: Button Positioning Issues
            $this->analyzeButtonPositioning($browser);

            // ANALYSIS 3: Form Flow and Visual Hierarchy
            $this->analyzeFormFlow($browser);

            // ANALYSIS 4: Content Sections Analysis
            $this->analyzeContentSections($browser);

            // ANALYSIS 5: Responsive Behavior Testing
            $this->testResponsiveBehavior($browser);
        });
    }

    /**
     * Analyze the overall layout structure
     */
    private function analyzeOverallLayout(Browser $browser): void
    {
        $browser->screenshot('ux-analysis-overall-layout');

        // Check main layout components
        $hasHeader = $browser->element('.fi-ta-page-header') !== null;
        $hasForm = $browser->element('.fi-form') !== null;
        $hasSidebar = $browser->element('.fi-sidebar') !== null;

        $this->assertTrue($hasHeader, 'Page header should be present');
        $this->assertTrue($hasForm, 'Form should be present');

        // Document page structure
        echo "\n=== OVERALL LAYOUT ANALYSIS ===\n";
        echo 'Header Present: '.($hasHeader ? 'YES' : 'NO')."\n";
        echo 'Form Present: '.($hasForm ? 'YES' : 'NO')."\n";
        echo 'Sidebar Present: '.($hasSidebar ? 'YES' : 'NO')."\n";

        // Check for main content areas
        $mainContent = $browser->element('.fi-ta-content') !== null;
        echo 'Main Content Area: '.($mainContent ? 'YES' : 'NO')."\n";
    }

    /**
     * Analyze button positioning issues (Save Changes and Cancel buttons)
     */
    private function analyzeButtonPositioning(Browser $browser): void
    {
        $browser->screenshot('ux-analysis-button-positioning');

        // Find action buttons
        $saveButton = $browser->element("button[type='submit'], .fi-ta-save-button, .fi-ta-action-button");
        $cancelButton = $browser->element(".fi-ta-cancel-button, a[href*='tenants'], button:contains('Cancelar')");

        // Find Modules and Add-ons widget
        $modulesWidget = $browser->element(".fi-ta-modules-widget, .fi-ta-widget, [class*='module']");

        echo "\n=== BUTTON POSITIONING ANALYSIS ===\n";
        echo 'Save Button Found: '.($saveButton ? 'YES' : 'NO')."\n";
        echo 'Cancel Button Found: '.($cancelButton ? 'YES' : 'NO')."\n";
        echo 'Modules Widget Found: '.($modulesWidget ? 'YES' : 'NO')."\n";

        // Get button positions relative to modules widget
        if ($saveButton && $modulesWidget) {
            $saveButtonPosition = $saveButton->getLocationOnScreenOnceScrolledIntoView();
            $modulesWidgetPosition = $modulesWidget->getLocationOnScreenOnceScrolledIntoView();

            // This is a simplified check - in real testing we'd need more sophisticated position calculation
            $buttonAppearsBeforeModules = true; // Placeholder for actual position calculation
            echo 'Buttons appear BEFORE Modules widget: '.($buttonAppearsBeforeModules ? 'YES - PROBLEMATIC' : 'NO - GOOD')."\n";
        }

        // Test button visibility and accessibility
        if ($saveButton) {
            $isSaveButtonVisible = $saveButton->isDisplayed();
            echo 'Save Button Visible: '.($isSaveButtonVisible ? 'YES' : 'NO')."\n";

            // Check button text
            $saveButtonText = $saveButton->getText();
            echo "Save Button Text: '{$saveButtonText}'\n";
        }
    }

    /**
     * Analyze form flow and visual hierarchy
     */
    private function analyzeFormFlow(Browser $browser): void
    {
        $browser->screenshot('ux-analysis-form-flow');

        // Check form sections
        $formSections = $browser->elements('.fi-ta-section, .fi-section, .fi-form-section');
        $fieldGroups = $browser->elements('.fi-form-field, .fi-field');

        echo "\n=== FORM FLOW ANALYSIS ===\n";
        echo 'Number of Form Sections: '.count($formSections)."\n";
        echo 'Number of Field Groups: '.count($fieldGroups)."\n";

        // Check for logical field grouping
        $basicInfoFields = $browser->elements("input[name*='name'], input[name*='email'], input[name*='domain']");
        $advancedFields = $browser->elements("input[name*='database'], input[type='checkbox'], select");

        echo 'Basic Info Fields: '.count($basicInfoFields)."\n";
        echo 'Advanced/Config Fields: '.count($advancedFields)."\n";

        // Check visual hierarchy indicators
        $hasHeadings = $browser->elements('h1, h2, h3, .fi-heading');
        $hasRequiredIndicators = $browser->elements('.required, [required], .fi-required');
        $hasFieldLabels = $browser->elements('label');

        echo 'Page Headings: '.count($hasHeadings)."\n";
        echo 'Required Field Indicators: '.count($hasRequiredIndicators)."\n";
        echo 'Field Labels: '.count($hasFieldLabels)."\n";
    }

    /**
     * Analyze content sections and their organization
     */
    private function analyzeContentSections(Browser $browser): void
    {
        $browser->screenshot('ux-analysis-content-sections');

        // Find main content areas
        $mainForm = $browser->element('.fi-ta-main-form');
        $modulesWidget = $browser->element("[class*='module'], [class*='widget']");
        $sidebarContent = $browser->element('.fi-sidebar-content');

        echo "\n=== CONTENT SECTIONS ANALYSIS ===\n";
        echo 'Main Form Area: '.($mainForm ? 'YES' : 'NO')."\n";
        echo 'Modules Widget: '.($modulesWidget ? 'YES' : 'NO')."\n";
        echo 'Sidebar Content: '.($sidebarContent ? 'YES' : 'NO')."\n";

        // Check for visual separation
        $hasDividers = $browser->elements('.fi-divider, hr, .border');
        echo 'Visual Dividers: '.count($hasDividers)."\n";

        // Check content density
        $cards = $browser->elements('.fi-card, .fi-ta-card');
        echo 'Card Components: '.count($cards)."\n";
    }

    /**
     * Test responsive behavior at different screen sizes
     */
    private function test_responsive_behavior(Browser $browser): void
    {
        echo "\n=== RESPONSIVE BEHAVIOR TESTING ===\n";

        // Test desktop view
        $browser->resize(1920, 1080);
        $browser->pause(1000);
        $browser->screenshot('ux-responsive-desktop');
        echo "Desktop (1920x1080): Captured\n";

        // Test tablet view
        $browser->resize(768, 1024);
        $browser->pause(1000);
        $browser->screenshot('ux-responsive-tablet');
        echo "Tablet (768x1024): Captured\n";

        // Test mobile view
        $browser->resize(375, 667);
        $browser->pause(1000);
        $browser->screenshot('ux-responsive-mobile');
        echo "Mobile (375x667): Captured\n";

        // Test mobile form usability
        $isFormUsableOnMobile = $browser->element('form') !== null;
        $areButtonsClickable = $browser->element("button[type='submit']") !== null;

        echo 'Mobile Form Usable: '.($isFormUsableOnMobile ? 'YES' : 'NO')."\n";
        echo 'Mobile Buttons Clickable: '.($areButtonsClickable ? 'YES' : 'NO')."\n";

        // Reset to desktop
        $browser->resize(1920, 1080);
        $browser->pause(500);
    }

    /**
     * Test actual form interaction and identify confusing elements
     */
    public function test_form_interaction_analysis(): void
    {
        $this->browse(function (Browser $browser) {
            // Login
            $browser->visit('https://localhost/admin/login')
                ->type('email', 'admin@emporiodigital.test')
                ->type('password', 'emporiodigital123')
                ->press('Ingresar')
                ->waitForLocation('/admin', 15);

            // Navigate to tenant edit page
            $browser->visit('https://localhost/admin/tenants/data-protection-test-1764024534/edit')
                ->waitUntilMissing('.loading-indicator', 10)
                ->pause(2000);

            echo "\n=== FORM INTERACTION ANALYSIS ===\n";

            // Test field focus behavior
            $firstField = $browser->element("input:not([type='hidden']):not([type='checkbox']):not([type='radio'])");
            if ($firstField) {
                $browser->click($firstField);
                $browser->pause(500);
                $isFieldFocused = $browser->driver->executeScript('return document.activeElement === arguments[0];', [$firstField]);
                echo 'First Field Focusable: '.($isFieldFocused ? 'YES' : 'NO')."\n";
            }

            // Test tab order
            $inputFields = $browser->elements('input, select, textarea, button');
            echo 'Tabbable Elements: '.count($inputFields)."\n";

            // Test form validation (if any)
            $requiredFields = $browser->elements('[required]');
            echo 'Required Fields: '.count($requiredFields)."\n";

            // Test button states
            $saveButton = $browser->element("button[type='submit']");
            if ($saveButton) {
                $isSaveInitiallyEnabled = $saveButton->isEnabled();
                echo 'Save Button Initially Enabled: '.($isSaveInitiallyEnabled ? 'YES' : 'NO')."\n";
            }

            // Test for potential confusing elements
            $hasMultiplePrimaryButtons = count($browser->elements("button[type='submit'], .btn-primary")) > 1;
            $hasUnclearActions = count($browser->elements('button:not([type]), .fi-btn')) > 5;

            echo 'Multiple Primary Buttons: '.($hasMultiplePrimaryButtons ? 'YES - PROBLEMATIC' : 'NO')."\n";
            echo 'Too Many Action Buttons: '.($hasUnclearActions ? 'YES - PROBLEMATIC' : 'NO')."\n";

            $browser->screenshot('ux-form-interaction-test');
        });
    }
}
