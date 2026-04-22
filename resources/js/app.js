import './bootstrap';

// Alpine.js se carga automáticamente desde Livewire
// NO cargar Alpine aquí para evitar conflictos con Livewire
// Livewire incluye Alpine con soporte para $wire

// Import billing integration
import './billing-integration';

// ============================================================================
// FIX: Forzar notificaciones de Filament encima de modales
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Función para aplicar z-index a notificaciones
    function fixNotificationsZIndex() {
        // Seleccionar todos los posibles contenedores de notificaciones
        const selectors = [
            '.fi-notifications',
            '.fi-no',
            '[data-filament-notifications-container]',
            '[x-data*="notifications"]',
            'div[class*="notifications"]'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.zIndex = '99999';
                el.style.position = 'fixed';
            });
        });
    }
    
    // Aplicar al cargar
    fixNotificationsZIndex();
    
    // Observer para notificaciones dinámicas
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                fixNotificationsZIndex();
            }
        });
    });
    
    // Observar cambios en el body
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // También aplicar cada vez que Livewire dispara un evento
    document.addEventListener('livewire:navigated', fixNotificationsZIndex);
    document.addEventListener('livewire:load', fixNotificationsZIndex);
});

// Navigation Accordion se maneja ahora con el componente Blade navigation-accordion.blade.php
// que se inyecta via renderHook en AppPanelProvider
