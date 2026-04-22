{{-- Script de debugging para diagnosticar problemas de navegación --}}
{{-- NO interfiere con la navegación, solo registra eventos --}}
<script>
    (function() {
        'use strict';

        function logToBackend(message, context = {}) {
            fetch('/app/debug-log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ message, context })
            }).catch(err => console.error('❌ Error enviando log:', err));

            console.log(message, context);
        }

        // Contador global de clicks
        let clickCount = 0;

        // Listener global para detectar todos los clicks en links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');

            if (link && link.href) {
                clickCount++;
                const targetUrl = link.href;
                const currentUrl = window.location.href;
                const now = Date.now();

                const logData = {
                    clickNumber: clickCount,
                    targetUrl: targetUrl,
                    currentUrl: currentUrl,
                    linkText: link.textContent?.trim() || 'Sin texto',
                    linkClasses: link.className,
                    hasWireNavigate: link.hasAttribute('wire:navigate'),
                    timestamp: new Date().toISOString()
                };

                logToBackend('[DEBUG] 🖱️ Click en link detectado', logData);

                // Verificar después de 1 segundo si la navegación ocurrió
                setTimeout(() => {
                    const navigationResult = {
                        clickNumber: clickCount,
                        targetUrl: targetUrl,
                        currentUrl: window.location.href,
                        navigationSuccessful: window.location.href === targetUrl,
                        timeElapsed: Date.now() - now,
                        urlChanged: window.location.href !== currentUrl
                    };

                    if (!navigationResult.navigationSuccessful && navigationResult.urlChanged) {
                        logToBackend('[DEBUG] ⚠️ URL cambió pero no a destino esperado', navigationResult);
                    } else if (!navigationResult.urlChanged) {
                        logToBackend('[DEBUG] 🔴 NAVEGACIÓN NO COMPLETADA - URL sin cambios', navigationResult);
                    } else {
                        logToBackend('[DEBUG] ✅ NAVEGACIÓN EXITOSA', navigationResult);
                    }
                }, 1000);
            }
        }, { capture: true, passive: true }); // Capture para detectar antes que otros handlers

        // Eventos de Livewire
        document.addEventListener('livewire:navigating', function(e) {
            logToBackend('[LIVEWIRE] 🚀 Comenzando navegación (navigating)', {
                url: e.detail?.visit?.url || 'URL no disponible',
                timestamp: new Date().toISOString()
            });
        });

        document.addEventListener('livewire:navigated', function(e) {
            logToBackend('[LIVEWIRE] ✅ Navegación completada (navigated)', {
                url: window.location.href,
                timestamp: new Date().toISOString()
            });
        });

        document.addEventListener('livewire:navigate', function(e) {
            logToBackend('[LIVEWIRE] 🔄 Evento navigate disparado', {
                detail: e.detail || {},
                timestamp: new Date().toISOString()
            });
        });

        // Eventos de página
        window.addEventListener('beforeunload', function(e) {
            logToBackend('[PAGE] 🔄 beforeunload - Página a punto de cambiar', {
                currentUrl: window.location.href,
                timestamp: new Date().toISOString()
            });
        });

        window.addEventListener('load', function() {
            logToBackend('[PAGE] ✅ Página cargada completamente', {
                url: window.location.href,
                timestamp: new Date().toISOString()
            });
        });

        window.addEventListener('error', function(e) {
            logToBackend('[ERROR] ❌ Error detectado', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno
            });
        });

        logToBackend('[DEBUG] 🟢 Sistema de debugging de navegación inicializado', {
            url: window.location.href,
            timestamp: new Date().toISOString()
        });
    })();
</script>
