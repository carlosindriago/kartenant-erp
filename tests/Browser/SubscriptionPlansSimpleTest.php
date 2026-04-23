<?php

use Laravel\Dusk\Browser;

test('PRUEBA SIMPLE: Acceso y verificación de planes de suscripción', function () {
    $this->browse(function (Browser $browser) {
        // Login con el admin por defecto
        $browser->visit('/admin/login')
            ->waitFor('#email', 5)
            ->type('email', 'admin@emporiodigital.com')
            ->type('password', 'password')
            ->press('Ingresar')
            ->waitForLocation('/admin', 10)
            ->assertPathIs('/admin')
            ->screenshot('simple_test_01_login_success');

        // Navegar a planes de suscripción
        $browser->visit('/admin/subscription-plans')
            ->waitFor('.fi-ta-table', 15)
            ->assertSee('Planes de Suscripción')
            ->screenshot('simple_test_02_subscription_plans_page');

        // Analizar la estructura de la tabla
        $tableInfo = $browser->script("
            return {
                hasTable: !!document.querySelector('.fi-ta-table'),
                rows: document.querySelectorAll('.fi-ta-row').length,
                deleteButtons: document.querySelectorAll('.fi-ta-delete-action').length,
                checkboxes: document.querySelectorAll('.fi-ta-checkbox-cell input').length,
                bulkActions: document.querySelectorAll('.fi-ta-bulk-action').length
            };
        ")[0];

        info('Estructura de la tabla: '.json_encode($tableInfo));

        // Intentar encontrar y hacer clic en un botón de eliminar
        if ($tableInfo['deleteButtons'] > 0) {
            $browser->click('.fi-ta-delete-action')
                ->pause(2000)
                ->screenshot('simple_test_03_delete_clicked');

            // Verificar si aparece modal
            try {
                $browser->waitFor('.fi-modal-window', 3)
                    ->screenshot('simple_test_04_modal_shown')
                    ->press('Eliminar')
                    ->pause(3000)
                    ->screenshot('simple_test_05_after_delete_attempt');

                // Capturar cualquier mensaje que aparezca
                $browser->script("
                    const notifications = document.querySelectorAll('[role=\"alert\"], .notification, .alert, .fi-notification');
                    notifications.forEach((notification, index) => {
                        console.log('Notification', index, ':', notification.textContent);
                    });
                ");

            } catch (\Exception $e) {
                info('No apareció modal o hubo error: '.$e->getMessage());
                $browser->screenshot('simple_test_04_no_modal_error');
            }
        } else {
            $browser->screenshot('simple_test_03_no_delete_buttons');
            info('No se encontraron botones de eliminar');
        }

        // Probar acciones en lote si hay checkboxes
        if ($tableInfo['checkboxes'] > 0) {
            $browser->check('.fi-ta-checkbox-cell input')
                ->pause(1000)
                ->screenshot('simple_test_06_checked_items');

            if ($tableInfo['bulkActions'] > 0) {
                $browser->screenshot('simple_test_07_bulk_actions_available');
            }
        }

        // Captura final del estado completo
        $browser->pause(2000)
            ->screenshot('simple_test_08_final_state');
    });
});
