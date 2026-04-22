<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;

class SimpleAdminTest extends DuskTestCase
{
    /**
     * Test basic admin access and dashboard analysis.
     */
    public function test_admin_dashboard_analysis()
    {
        $this->browse(function (Browser $browser) {
            // Direct login approach
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->waitFor('#data\\.email', 5)
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->pause(1000)
                    ->click('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 15)
                    ->pause(5000); // Wait for full load

            // Analyze page structure for the report
            $pageAnalysis = $browser->script("
                const analysis = {
                    title: document.title,
                    h1Elements: Array.from(document.querySelectorAll('h1')).map(h => h.textContent.trim()),
                    h2Elements: Array.from(document.querySelectorAll('h2')).map(h => h.textContent.trim()),
                    hasEscritorioH1: Array.from(document.querySelectorAll('h1')).some(h =>
                        h.textContent.trim().toLowerCase().includes('escritorio')
                    ),
                    decimalDaysPatterns: document.body.innerText.match(/\\d+\\.\\d+\\s*(días|days|day|día)/gi) || [],
                    widgets: document.querySelectorAll('.filament-stats-card, .widget, [class*=\"card\"]').length,
                    tables: document.querySelectorAll('table').length,
                    charts: document.querySelectorAll('canvas, svg').length,
                    buttons: document.querySelectorAll('button').length,
                    navigationItems: document.querySelectorAll('nav a, .nav a').length,
                    hasSidebar: !!document.querySelector('.sidebar, [class*=\"sidebar\"], [class*=\"nav\"]'),
                    hasHeader: !!document.querySelector('header, [class*=\"header\"]'),
                    bodyText: document.body.innerText.substring(0, 2000),
                    allVisibleText: document.body.innerText
                };
                return analysis;
            ")[0];

            // Take comprehensive screenshots for analysis
            $browser->screenshot('dashboard-desktop-full');

            // Test responsive design
            $browser->resize(768, 1024)
                    ->pause(2000)
                    ->screenshot('dashboard-tablet-view');

            $browser->resize(375, 667)
                    ->pause(2000)
                    ->screenshot('dashboard-mobile-view');

            // Reset to desktop
            $browser->resize(1920, 1080)
                    ->pause(1000);

            // Save analysis data
            error_log('Dashboard Analysis: ' . json_encode($pageAnalysis, JSON_PRETTY_PRINT));

            // Basic functionality tests
            $browser->assertPresent('body')
                    ->pause(1000);

            // Check specific issues
            if ($pageAnalysis['hasEscritorioH1']) {
                error_log('ISSUE FOUND: H1 "Escritorio" is visible (should be hidden)');
            }

            if (!empty($pageAnalysis['decimalDaysPatterns'])) {
                error_log('ISSUE FOUND: Decimal days patterns detected: ' . json_encode($pageAnalysis['decimalDaysPatterns']));
            }
        });
    }
}