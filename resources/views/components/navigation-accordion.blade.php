{{--
    Navigation Accordion Component

    Implementa comportamiento accordion en los grupos de navegación lateral:
    - Solo un grupo abierto a la vez
    - Al abrir un grupo, los demás se cierran automáticamente
    - Se inyecta en el panel via renderHook
--}}

<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const groups = document.querySelectorAll('.fi-sidebar-group');

            if (!groups.length) {
                console.log('[Accordion] No se encontraron grupos de navegación');
                return;
            }

            groups.forEach((group) => {
                const groupButton = group.querySelector(':scope > button');

                if (!groupButton) {
                    return;
                }

                // Handler para el click en el botón del grupo
                function handleAccordionClick(e) {
                    // Solo procesar si es click en el botón del grupo mismo
                    if (!groupButton.contains(e.target)) {
                        return;
                    }

                    // Esperar un frame para no interferir con otras operaciones
                    requestAnimationFrame(() => {
                        const currentGroup = e.currentTarget.closest('.fi-sidebar-group');

                        // Si este grupo se está ABRIENDO, cerrar los demás
                        const isOpening = currentGroup.querySelector('[x-show]')?.getAttribute('style') === 'display: none;';

                        if (isOpening) {
                            groups.forEach((otherGroup) => {
                                if (otherGroup !== currentGroup) {
                                    const otherButton = otherGroup.querySelector(':scope > button');
                                    if (otherButton) {
                                        const isVisible = otherGroup.querySelector('[x-show]')?.getAttribute('style') !== 'display: none;';

                                        if (isVisible) {
                                            // Simular click para cerrar
                                            const clickEvent = new MouseEvent('click', {
                                                bubbles: false, // NO propagar para evitar loops
                                                cancelable: true,
                                                view: window
                                            });

                                            otherButton.dispatchEvent(clickEvent);
                                        }
                                    }
                                }
                            });
                        }
                    });
                }

                // Attach event listener - NO usar capture, passive para mejor performance
                groupButton.addEventListener('click', handleAccordionClick, {
                    capture: false,
                    passive: true
                });
            });

            console.log('[Accordion] Inicializado con', groups.length, 'grupos');
        });

    })();
</script>
