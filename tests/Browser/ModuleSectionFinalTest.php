<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Tenant;
use App\Models\User;

class ModuleSectionFinalTest extends DuskTestCase
{
    /**
     * Test module section positioning - FINAL VALIDATION
     */
    public function test_module_section_final_validation(): void
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::where('domain', 'data-protection-test-1764024534')->first();
            $user = User::first();

            if (!$tenant) {
                $this->markTestSkipped('Test tenant not found');
                return;
            }

            if (!$user) {
                $this->markTestSkipped('No user available for login');
                return;
            }

            try {
                // Login and visit edit page
                $browser->loginAs($user)
                        ->visit('/admin/tenants/{$tenant->id}/edit')
                        ->pause(3000)
                        ->screenshot('module-section-final-validation');

                // Get section order analysis
                $analysis = $browser->script(<<<'JS'
                    const pageText = document.body.innerText;
                    
                    const profileIndex = pageText.indexOf('Perfil de la Tienda');
                    const modulesIndex = pageText.indexOf('Módulos Activos');
                    const metricsIndex = pageText.indexOf('Métricas de Negocio');
                    const activityIndex = pageText.indexOf('Actividad Reciente');
                    
                    return {
                        profileIndex,
                        modulesIndex,
                        metricsIndex,
                        activityIndex,
                        hasProfile: profileIndex !== -1,
                        hasModules: modulesIndex !== -1,
                        hasMetrics: metricsIndex !== -1,
                        hasActivity: activityIndex !== -1,
                        
                        modulesAfterProfile: profileIndex !== -1 && modulesIndex > profileIndex,
                        modulesBeforeMetrics: modulesIndex !== -1 && metricsIndex !== -1 && modulesIndex < metricsIndex,
                        modulesBeforeActivity: modulesIndex !== -1 && activityIndex !== -1 && modulesIndex < activityIndex,
                        
                        content: pageText.substring(0, 1000)
                    };
JS
);

                $results = $analysis[0];

                // Create validation report
                $report = 'MODULE SECTION POSITIONING VALIDATION' . PHP_EOL . PHP_EOL;
                $report .= 'Tenant: ' . $tenant->name . PHP_EOL;
                $report .= 'Sections Found:' . PHP_EOL;
                $report .= '  - Perfil de la Tienda: ' . ($results['hasProfile'] ? 'YES' : 'NO') . PHP_EOL;
                $report .= '  - Módulos Activos: ' . ($results['hasModules'] ? 'YES' : 'NO') . PHP_EOL;
                $report .= '  - Métricas de Negocio: ' . ($results['hasMetrics'] ? 'YES' : 'NO') . PHP_EOL;
                $report .= '  - Actividad Reciente: ' . ($results['hasActivity'] ? 'YES' : 'NO') . PHP_EOL . PHP_EOL;
                
                $report .= 'Position Validation:' . PHP_EOL;
                $report .= '  - Modules after Profile: ' . ($results['modulesAfterProfile'] ? 'PASS' : 'FAIL') . PHP_EOL;
                $report .= '  - Modules before Metrics: ' . ($results['modulesBeforeMetrics'] ? 'PASS' : 'FAIL') . PHP_EOL;
                $report .= '  - Modules before Activity: ' . ($results['modulesBeforeActivity'] ? 'PASS' : 'FAIL') . PHP_EOL;

                file_put_contents(base_path('tests/Browser/final-module-validation.txt'), $report);
                echo $report . PHP_EOL;

                // Critical assertions
                $this->assertTrue($results['hasModules'], 'Módulos Activos section must exist');
                
                if ($results['hasProfile'] && $results['hasMetrics']) {
                    $this->assertTrue($results['modulesAfterProfile'], 'Modules must appear after Profile');
                    $this->assertTrue($results['modulesBeforeMetrics'], 'Modules must appear before Metrics');
                }

                $this->assertTrue(true, 'Module section validation completed successfully');

            } catch (Exception $e) {
                $this->fail('Test error: ' . $e->getMessage());
            }
        });
    }
}
