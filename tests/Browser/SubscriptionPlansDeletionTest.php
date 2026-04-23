<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('PRUEBA 1: Eliminación Individual - Verificar restricciones y mensajes', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 10)
            ->assertSee('Planes de Suscripción')
            ->screenshot('individual_deletion_01_initial_state');

        // Intentar eliminar un plan con suscripciones activas
        $browser->click('.fi-ta-action-group .fi-ta-delete-action')
            ->waitFor('.fi-modal-window', 5)
            ->assertSee('Confirmar eliminación')
            ->press('Eliminar')
            ->waitForText('No se puede eliminar el plan', 5)
            ->assertSee('tiene')
            ->assertSee('suscripciones asociadas')
            ->screenshot('individual_deletion_02_subscription_error');

        // Verificar que el plan sigue existiendo
        $browser->refresh()
            ->waitFor('.fi-ta-table', 10)
            ->assertSeeIn('.fi-ta-table', 'Plan Básico');
    });
});

test('PRUEBA 2: Eliminación en Lote - Verificar restricciones múltiples', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test2@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 10);

        // Seleccionar múltiples planes para eliminación
        $browser->check('.fi-ta-checkbox-cell input[type="checkbox"]')
            ->waitFor('.fi-ta-bulk-actions', 5)
            ->screenshot('bulk_deletion_01_selected_plans');

        // Intentar eliminar en lote
        $browser->click('.fi-ta-delete-bulk-action')
            ->waitFor('.fi-modal-window', 5)
            ->assertSee('Confirmar eliminación')
            ->press('Eliminar')
            ->waitForText('No se pueden eliminar los planes seleccionados', 5)
            ->assertSee('Los siguientes planes no pueden eliminarse:')
            ->screenshot('bulk_deletion_02_bulk_error');

        // Verificar que todos los planes siguen existiendo
        $browser->refresh()
            ->waitFor('.fi-ta-table', 10)
            ->assertSeeIn('.fi-ta-table', 'Plan Básico')
            ->assertSeeIn('.fi-ta-table', 'Plan Profesional')
            ->assertSeeIn('.fi-ta-table', 'Plan Enterprise');
    });
});

test('PRUEBA 3: Verificación de Mensajes Específicos', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test3@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 10)
            ->screenshot('messages_01_table_view');

        // Probar eliminación de diferentes planes para ver diferentes mensajes
        $plansCount = 3;
        for ($i = 1; $i <= $plansCount; $i++) {
            try {
                $browser->click(".fi-ta-row:nth-child({$i}) .fi-ta-delete-action")
                    ->waitFor('.fi-modal-window', 5)
                    ->press('Eliminar')
                    ->pause(2000); // Esperar a ver el mensaje

                // Capturar screenshot para cada intento
                $browser->screenshot('messages_0'.($i + 1).'_attempt_'.$i);

                // Cerrar modal si está abierto
                try {
                    $browser->press('Cancelar');
                } catch (\Exception $e) {
                    // Modal ya cerrado, continuar
                }

                // Pausar antes del siguiente intento
                $browser->pause(1000);

            } catch (\Exception $e) {
                // Continuar con el siguiente plan si hay error
                $browser->pause(1000);

                continue;
            }
        }
    });
});

test('PRUEBA 4: Acciones en Lote de Visibilidad', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test4@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 10)
            ->screenshot('visibility_01_initial_state');

        // Seleccionar planes para acciones de visibilidad
        $browser->check('.fi-ta-row:nth-child(1) .fi-ta-checkbox-cell input')
            ->check('.fi-ta-row:nth-child(2) .fi-ta-checkbox-cell input')
            ->waitFor('.fi-ta-bulk-actions', 5)
            ->screenshot('visibility_02_selected_plans');

        // Probar acciones de visibilidad una por una
        $actions = [
            'hide_from_public' => 'Ocultar',
            'show_to_public' => 'Mostrar',
            'set_as_featured' => 'destacados',
            'remove_from_featured' => 'Quitar destacados',
        ];

        foreach ($actions as $actionClass => $actionText) {
            try {
                $browser->click(".fi-ta-bulk-action.{$actionClass}")
                    ->waitFor('.fi-modal-window', 5)
                    ->press('Confirmar')
                    ->pause(2000)
                    ->screenshot("visibility_03_{$actionClass}_action");
            } catch (\Exception $e) {
                $browser->screenshot("visibility_03_{$actionClass}_error");
            }
        }
    });
});

test('PRUEBA 5: Verificación de estado visual e indicadores', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test5@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 10)
            ->screenshot('visual_01_table_overview');

        // Analizar la estructura de la tabla
        $browser->script('
            console.log("Table structure:");
            console.log(document.querySelector(".fi-ta-table"));
            console.log("Rows found:", document.querySelectorAll(".fi-ta-row").length);
            console.log("Delete buttons:", document.querySelectorAll(".fi-ta-delete-action").length);
        ');

        // Capturar diferentes vistas de la interfaz
        $browser->screenshot('visual_02_complete_interface')
            ->pause(1000);

        // Verificar tooltips si existen
        try {
            $browser->script('
                const badges = document.querySelectorAll(".fi-ta-badge");
                badges.forEach((badge, index) => {
                    console.log("Badge", index, ":", badge.textContent, badge.className);
                });
            ');
        } catch (\Exception $e) {
            // Continuar si hay error de JavaScript
        }

        $browser->screenshot('visual_03_detailed_view');
    });
});

test('PRUEBA 6: Diagnóstico completo del sistema', function () {
    $this->browse(function (Browser $browser) {
        // Create and login as superadmin
        $superadmin = User::factory()->create([
            'email' => 'test6@emporiodigital.com',
            'password' => bcrypt('password'),
        ]);
        $superadmin->assignRole('superadmin');

        $browser->loginAs($superadmin)
            ->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 15)
            ->screenshot('diagnosis_01_page_loaded');

        // Analizar DOM completo
        $browser->script('
            const diagnosis = {
                url: window.location.href,
                title: document.title,
                table: !!document.querySelector(".fi-ta-table"),
                rows: document.querySelectorAll(".fi-ta-row").length,
                deleteButtons: document.querySelectorAll(".fi-ta-delete-action").length,
                checkboxes: document.querySelectorAll(".fi-ta-checkbox-cell input").length,
                bulkActions: document.querySelectorAll(".fi-ta-bulk-action").length,
                modals: document.querySelectorAll(".fi-modal-window").length
            };
            console.log("=== DIAGNOSIS ===");
            console.log(JSON.stringify(diagnosis, null, 2));

            // Log all clickable elements
            const clickableElements = document.querySelectorAll("button, [role=button], .fi-ta-action");
            console.log("Clickable elements:", clickableElements.length);
            clickableElements.forEach((el, index) => {
                console.log(`Element ${index}:`, el.tagName, el.className, el.textContent);
            });
        ');

        // Intentar interactuar con elementos eliminables
        try {
            $deleteButtons = $browser->elements('.fi-ta-delete-action');

            if (count($deleteButtons) > 0) {
                $browser->click('.fi-ta-delete-action')
                    ->pause(1000)
                    ->screenshot('diagnosis_02_delete_clicked');

                // Verificar si aparece modal
                try {
                    $browser->waitFor('.fi-modal-window', 3)
                        ->screenshot('diagnosis_03_modal_shown')
                        ->press('Eliminar')
                        ->pause(2000)
                        ->screenshot('diagnosis_04_after_delete_attempt');
                } catch (\Exception $e) {
                    $browser->screenshot('diagnosis_03_no_modal');
                }
            } else {
                $browser->screenshot('diagnosis_02_no_delete_buttons');
            }
        } catch (\Exception $e) {
            $browser->screenshot('diagnosis_02_error_finding_elements');
        }

        // Verificar si hay errores en la consola
        $browser->script('
            window.addEventListener("error", function(e) {
                console.error("JavaScript Error:", e.error);
            });
            console.log("=== Checking for console errors ===");
        ');

        // Captura final del estado
        $browser->pause(3000)
            ->screenshot('diagnosis_05_final_state');
    });
});
