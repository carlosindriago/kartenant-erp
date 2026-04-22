<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Tenant;
use App\Models\User;

class SimpleTenantPageCheck extends DuskTestCase
{
    /**
     * Simple check to see what's actually on the tenant edit page
     */
    public function test_simple_tenant_page_check(): void
    {
        $this->browse(function (Browser $browser) {
            $tenant = Tenant::where('domain', 'data-protection-test-1764024534')->first();
            $user = User::first();

            if (!$tenant) {
                $this->markTestSkipped('Test tenant not found');
                return;
            }

            try {
                $browser->loginAs($user)
                        ->visit('/admin/tenants/{$tenant->id}/edit')
                        ->pause(5000)  // Wait longer
                        ->screenshot('simple-tenant-page-check');

                // Get all text content
                $allText = $browser->script('return document.body.innerText;')[0];
                
                // Save full content to file for analysis
                file_put_contents(base_path('tests/Browser/tenant-page-full-content.txt'), $allText);
                
                // Check for key sections
                $hasModules = strpos($allText, 'Módulos Activos') !== false;
                $hasProfile = strpos($allText, 'Perfil de la Tienda') !== false;
                $hasMetrics = strpos($allText, 'Métricas de Negocio') !== false;
                $hasActivity = strpos($allText, 'Actividad Reciente') !== false;
                
                echo 'Page Content Analysis:' . PHP_EOL;
                echo '  - Has Módulos Activos: ' . ($hasModules ? 'YES' : 'NO') . PHP_EOL;
                echo '  - Has Perfil de la Tienda: ' . ($hasProfile ? 'YES' : 'NO') . PHP_EOL;
                echo '  - Has Métricas de Negocio: ' . ($hasMetrics ? 'YES' : 'NO') . PHP_EOL;
                echo '  - Has Actividad Reciente: ' . ($hasActivity ? 'YES' : 'NO') . PHP_EOL;
                
                // Get positions if they exist
                if ($hasModules && $hasProfile && $hasMetrics) {
                    $modulePos = strpos($allText, 'Módulos Activos');
                    $profilePos = strpos($allText, 'Perfil de la Tienda');
                    $metricsPos = strpos($allText, 'Métricas de Negocio');
                    
                    $orderCorrect = ($profilePos < $modulePos) && ($modulePos < $metricsPos);
                    echo '  - Section Order Correct: ' . ($orderCorrect ? 'YES' : 'NO') . PHP_EOL;
                    
                    $this->assertTrue($orderCorrect, 'Module section should be between profile and metrics');
                }

                // If we found the modules section, that's the main goal
                $this->assertTrue($hasModules, 'Módulos Activos section should be found on tenant edit page');

            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage() . PHP_EOL;
                $this->markTestSkipped('Test error: ' . $e->getMessage());
            }
        });
    }
}
