<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SuperAdminDashboardTest extends DuskTestCase
{
    /**
     * Test SuperAdmin login and complete dashboard analysis.
     */
    public function test_superadmin_login_and_dashboard_analysis()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->assertSee('Administración')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10)
                    ->assertPathIs('/admin')
                    ->screenshot('dashboard-full-view')
                    ->pause(2000); // Allow all widgets to load
        });
    }

    /**
     * Test for H1 "Escritorio" visibility issue (client requirement).
     */
    public function test_check_escritorio_h1_issue()
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10);

            // Check if H1 "Escritorio" exists
            $hasEscritorioH1 = $browser->script("
                const h1Elements = document.querySelectorAll('h1');
                return Array.from(h1Elements).some(h1 =>
                    h1.textContent.trim().toLowerCase().includes('escritorio')
                );
            ")[0];

            $browser->screenshot('escritorio-h1-check');

            // Log the result for analysis
            if ($hasEscritorioH1) {
                $browser->script('console.warn("H1 Escritorio found - this should be hidden according to client requirements")');
            }
        });
    }

    /**
     * Test decimal days formatting in expiration dates.
     */
    public function test_decimal_days_formatting()
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10)
                    ->pause(3000); // Wait for all content to load

            // Look for decimal patterns in dates/expirations
            $decimalPatterns = $browser->script("
                const textContent = document.body.innerText;
                const decimalDaysPattern = /\d+\.\d+\s*(d�as|days|day|d�a)/gi;
                const matches = textContent.match(decimalDaysPattern) || [];
                return matches;
            ")[0];

            $browser->screenshot('decimal-days-analysis');

            // Log findings
            if (!empty($decimalPatterns)) {
                $browser->script('console.warn("Decimal days patterns found: " + ' . json_encode($decimalPatterns) . ')');
            }
        });
    }

    /**
     * Test dashboard responsiveness across different breakpoints.
     */
    public function test_dashboard_responsiveness()
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10);

            // Test desktop view
            $browser->resize(1920, 1080)
                    ->pause(1000)
                    ->screenshot('dashboard-desktop')
                    ->assertPresent('body');

            // Test tablet view
            $browser->resize(768, 1024)
                    ->pause(1000)
                    ->screenshot('dashboard-tablet');

            // Test mobile view
            $browser->resize(375, 667)
                    ->pause(1000)
                    ->screenshot('dashboard-mobile');

            // Reset to desktop
            $browser->resize(1920, 1080);
        });
    }

    /**
     * Test all dashboard widgets and metrics.
     */
    public function test_dashboard_widgets_analysis()
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10)
                    ->pause(3000); // Wait for all data to load

            // Take screenshot for visual analysis
            $browser->screenshot('dashboard-widgets-detailed');

            // Get page structure analysis
            $pageStructure = $browser->script("
                return {
                    title: document.title,
                    hasMainContent: !!document.querySelector('main, .filament-content, .content'),
                    widgetCount: document.querySelectorAll('.filament-stats-card, .widget, [class*=\"card\"]').length,
                    tableCount: document.querySelectorAll('table').length,
                    chartElements: document.querySelectorAll('[class*=\"chart\"], canvas, svg').length,
                    navigationItems: document.querySelectorAll('nav a, .nav a').length,
                    hasSidebar: !!document.querySelector('.sidebar, [class*=\"sidebar\"], [class*=\"nav\"]'),
                    hasHeader: !!document.querySelector('header, [class*=\"header\"]')
                };
            ")[0];

            // Log structure analysis
            $browser->script('console.log("Dashboard structure analysis: " + ' . json_encode($pageStructure) . ')');
        });
    }

    /**
     * Test data loading and refresh behavior.
     */
    public function test_data_loading_behavior()
    {
        $this->browse(function (Browser $browser) {
            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10);

            // Check initial state
            $browser->screenshot('dashboard-initial-state');

            // Test page refresh
            $browser->refresh()
                    ->waitFor('body', 10)
                    ->pause(3000) // Wait for reload
                    ->screenshot('dashboard-after-refresh');

            // Test for loading states
            $loadingElements = $browser->script("
                const loadingElements = document.querySelectorAll('[class*=\"loading\"], [class*=\"spinner\"], [aria-busy=\"true\"], [class*=\"skeleton\"]');
                return {
                    count: loadingElements.length,
                    classes: Array.from(loadingElements).map(el => el.className)
                };
            ")[0];

            // Log loading analysis
            $browser->script('console.log("Loading analysis: " + ' . json_encode($loadingElements) . ')');
        });
    }

    /**
     * Test performance metrics.
     */
    public function test_dashboard_performance()
    {
        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            // Login first
            $browser->visit('https://emporiodigital.test/admin/login')
                    ->type('#data\\.email', 'admin@emporiodigital.test')
                    ->type('#data\\.password', 'emporiomental123')
                    ->press('button[type="submit"]')
                    ->waitForLocation('https://emporiodigital.test/admin', 10)
                    ->pause(5000); // Allow full load

            $loadTime = microtime(true) - $startTime;

            // Get performance metrics
            $performanceMetrics = $browser->script("
                if (window.performance && window.performance.timing) {
                    const timing = window.performance.timing;
                    return {
                        domLoadTime: timing.loadEventEnd - timing.navigationStart,
                        resourceCount: window.performance.getEntriesByType('resource').length,
                        memoryUsage: window.performance.memory ? {
                            used: Math.round(window.performance.memory.usedJSHeapSize / 1024 / 1024),
                            total: Math.round(window.performance.memory.totalJSHeapSize / 1024 / 1024)
                        } : null
                    };
                }
                return null;
            ")[0];

            $browser->script('console.log("Performance metrics: " + ' . json_encode([
                'totalLoadTime' => round($loadTime, 2),
                'browserMetrics' => $performanceMetrics
            ]) . ')');

            $browser->screenshot('dashboard-performance-test');
        });
    }
}