{{-- Fix para problema de navegación de Filament/Livewire --}}
{{-- Deshabilita interceptación de Livewire y fuerza navegación tradicional --}}
<script>
    (function() {
        'use strict';

        // Usar un objeto global para mantener el estado a través de las cargas de Livewire
        if (window.navigationFix) {
            // Si ya está inicializado, solo nos aseguramos de que los links estén procesados
            // Esto puede ser útil si Livewire recarga una parte del DOM
            window.navigationFix.processAllLinks();
            return;
        }

        // Helper para enviar logs al backend
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

        // Estado global del sistema
        const processedLinks = new WeakSet();
        let observer = null;
        let isInitialized = false;
        let isProcessing = false;
        let initTimeout = null;
        let currentPageUrl = window.location.href;
        let isFirstLoad = true;
        let monitoringInterval = null;
        let isNavigating = false; // Flag para prevenir navegación duplicada
        let lastClickTime = 0;
        let lastClickHref = null;
        let pendingInit = false; // Flag para prevenir múltiples inicializaciones simultáneas
        let lastInitUrl = window.location.href; // URL de la última inicialización

        // Debounce helper para evitar múltiples inicializaciones simultáneas
        function debounceInit(callback, delay = 150) {
            if (initTimeout) {
                clearTimeout(initTimeout);
            }
            initTimeout = setTimeout(callback, delay);
        }

        // Inicialización inmediata para primera carga (sin debounce)
        function immediateInit(callback) {
            // Usar requestAnimationFrame para asegurar que el DOM está listo
            if (window.requestAnimationFrame) {
                requestAnimationFrame(callback);
            } else {
                setTimeout(callback, 0);
            }
        }

        // Función para procesar un link individual
        function processLink(link) {
            // Evitar procesar el mismo link múltiples veces
            if (processedLinks.has(link)) {
                return false;
            }

            // Marcar como procesado ANTES de agregar listener
            processedLinks.add(link);

            // Remover wire:navigate si existe (esto es crítico para que funcione)
            if (link.hasAttribute('wire:navigate')) {
                link.removeAttribute('wire:navigate');
            }

            // Agregar listener de click con capture phase (true) para ejecutar ANTES que Livewire
            const clickHandler = function(e) {
                const href = this.getAttribute('href');
                
                // Ignorar links especiales
                if (!href || href === '#' || href.startsWith('javascript:') || 
                    href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return;
                }

                // Si es la misma URL, no hacer nada
                if (href === window.location.href) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return;
                }

                // Prevenir clicks duplicados muy rápidos (dentro de 200ms)
                const now = Date.now();
                if (isNavigating || (now - lastClickTime < 200 && lastClickHref === href)) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    logToBackend('[NAV-FIX] ⚠️ Click duplicado ignorado', {
                        href: href,
                        timeSinceLastClick: now - lastClickTime
                    });
                    return;
                }

                // CRÍTICO: Remover wire:navigate ANTES de cualquier otra cosa
                // Esto debe ser lo PRIMERO que hacemos
                if (this.hasAttribute('wire:navigate')) {
                    this.removeAttribute('wire:navigate');
                    logToBackend('[NAV-FIX] ⚠️ wire:navigate removido justo antes del click', {
                        href: href
                    });
                }

                // Prevenir el comportamiento por defecto y otros listeners INMEDIATAMENTE
                // Esto debe ejecutarse ANTES de cualquier otra lógica de Livewire
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                // Marcar que estamos navegando para prevenir clicks duplicados
                isNavigating = true;
                lastClickTime = now;
                lastClickHref = href;
                currentPageUrl = href;

                const linkText = this.textContent?.trim() || 'Sin texto';
                
                logToBackend('[NAV-FIX] 🖱️ Click interceptado, navegando...', {
                    href: href,
                    linkText: linkText,
                    currentUrl: window.location.href,
                    timestamp: new Date().toISOString()
                });

                // Usar location.replace() en lugar de location.href para evitar que se pueda cancelar
                // y asegurar que se ejecute inmediatamente
                try {
                    window.location.replace(href);
                } catch (err) {
                    // Fallback a location.href si replace falla
                    logToBackend('[NAV-FIX] ⚠️ Error con replace, usando href', { error: err.message });
                    window.location.href = href;
                }
            };

            // Agregar listener en capture phase para ejecutar primero
            link.addEventListener('click', clickHandler, true);
            
            return true;
        }

        // Función para procesar múltiples links (síncrono con for loop)
        function processAllLinks() {
            const links = document.querySelectorAll('.fi-sidebar a[href]');
            let newCount = 0;
            
            // Usar for loop síncrono para mayor velocidad
            for (let i = 0; i < links.length; i++) {
                const link = links[i];
                // Remover wire:navigate si existe antes de procesar
                if (link.hasAttribute('wire:navigate')) {
                    link.removeAttribute('wire:navigate');
                }
                if (processLink(link)) {
                    newCount++;
                }
            }
            
            if (newCount > 0 || links.length > 0) {
                logToBackend('[NAV-FIX] Links procesados', { 
                    total: links.length, 
                    nuevos: newCount 
                });
            }
            return newCount;
        }

        // Inicializar el fix de navegación
        function initNavigationFix(force = false) {
            // Evitar múltiples inicializaciones simultáneas
            if (isProcessing) {
                return;
            }

            // Evitar re-inicializaciones innecesarias (excepto después de navegación)
            if (isInitialized && !force) {
                return;
            }

            // Verificar que la URL realmente cambió antes de re-inicializar
            if (force && window.location.href === lastInitUrl && isInitialized) {
                logToBackend('[NAV-FIX] ⚠️ Re-inicialización cancelada - URL no cambió', {
                    currentUrl: window.location.href,
                    lastInitUrl: lastInitUrl
                });
                return;
            }

            // Marcar como procesando
            isProcessing = true;

            logToBackend('[NAV-FIX] 🚀 Inicializando fix de navegación...', { 
                force: force,
                currentUrl: window.location.href
            });

            // Procesar links existentes del sidebar INMEDIATAMENTE
            processAllLinks();

            // Observer para nuevos links que se agreguen dinámicamente
            // Solo crear observer una vez
            if (!observer) {
                observer = new MutationObserver(function(mutations) {
                    // Evitar procesar mientras se está inicializando
                    if (isProcessing) {
                        return;
                    }

                    let hasNewLinks = false;
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                // Si el nodo agregado es un link
                                if (node.tagName === 'A' && node.hasAttribute('href') && 
                                    node.closest('.fi-sidebar')) {
                                    // Remover wire:navigate si existe
                                    if (node.hasAttribute('wire:navigate')) {
                                        node.removeAttribute('wire:navigate');
                                    }
                                    if (processLink(node)) {
                                        hasNewLinks = true;
                                    }
                                    return;
                                }

                                // Buscar links dentro del nodo agregado
                                const newLinks = node.querySelectorAll && node.querySelectorAll('a[href]');
                                if (newLinks && newLinks.length > 0) {
                                    // Filtrar solo los que están en el sidebar
                                    Array.from(newLinks).forEach(function(link) {
                                        if (link.closest('.fi-sidebar')) {
                                            // Remover wire:navigate si existe
                                            if (link.hasAttribute('wire:navigate')) {
                                                link.removeAttribute('wire:navigate');
                                            }
                                            if (processLink(link)) {
                                                hasNewLinks = true;
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    });

                    if (hasNewLinks) {
                        logToBackend('[NAV-FIX] Nuevos links procesados por observer');
                    }
                });
            }

            // Observar el sidebar por cambios
            const sidebar = document.querySelector('.fi-sidebar');
            if (sidebar) {
                // Solo configurar observer una vez
                if (observer && !observer._isObserving) {
                    observer.observe(sidebar, {
                        childList: true,
                        subtree: true
                    });
                    observer._isObserving = true;
                    logToBackend('[NAV-FIX] Observer activado en sidebar');
                }
            }

            // Iniciar monitoreo continuo para capturar wire:navigate re-agregado dinámicamente
            if (!monitoringInterval) {
                monitoringInterval = setInterval(function() {
                    if (!isProcessing && isInitialized) {
                        const allSidebarLinks = document.querySelectorAll('.fi-sidebar a[href]');
                        let removedCount = 0;
                        let processedCount = 0;
                        
                        for (let i = 0; i < allSidebarLinks.length; i++) {
                            const link = allSidebarLinks[i];
                            
                            // Remover wire:navigate si se agregó después del procesamiento
                            if (link.hasAttribute('wire:navigate')) {
                                link.removeAttribute('wire:navigate');
                                removedCount++;
                            }
                            
                            // Si no estaba procesado, procesarlo ahora
                            if (!processedLinks.has(link)) {
                                processLink(link);
                                processedCount++;
                            }
                        }
                        
                        if (removedCount > 0 || processedCount > 0) {
                            logToBackend('[NAV-FIX] Monitoreo continuo: wire:navigate removido y links procesados', {
                                wireRemovidos: removedCount,
                                linksNuevos: processedCount,
                                total: allSidebarLinks.length
                            });
                        }
                    }
                }, 300); // Verificar cada 300ms
            }

            isInitialized = true;
            isProcessing = false;
            currentPageUrl = window.location.href;
            lastInitUrl = window.location.href;
            
            logToBackend('[NAV-FIX] ✅ Sistema completamente inicializado');
        }

        // Función de inicialización con debounce
        function debouncedInit(force = false) {
            debounceInit(function() {
                initNavigationFix(force);
            }, 100);
        }

        // Crear objeto global para compatibilidad
        const navigationFix = {
            processedLinks: processedLinks,
            observer: observer,
            isInitialized: false,
            logToBackend: logToBackend,
            processLink: processLink,
            processAllLinks: processAllLinks,
            init: function() {
                initNavigationFix(false);
            }
        };

        // Actualizar estado del objeto global
        Object.defineProperty(navigationFix, 'isInitialized', {
            get: function() { return isInitialized; },
            set: function(val) { isInitialized = val; }
        });

        window.navigationFix = navigationFix;

        // Esperar a que el DOM esté listo - Primera carga sin debounce para mayor rapidez
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                if (isFirstLoad) {
                    immediateInit(function() {
                        initNavigationFix(false);
                        isFirstLoad = false;
                    });
                } else {
                    debouncedInit();
                }
            });
        } else {
            if (isFirstLoad) {
                immediateInit(function() {
                    initNavigationFix(false);
                    isFirstLoad = false;
                });
            } else {
                debouncedInit();
            }
        }

        // Manejar livewire:navigating - solo marcar si necesitamos re-inicializar
        document.addEventListener('livewire:navigating', function(e) {
            // Si estamos navegando activamente (por nuestro handler), Livewire no debería interferir
            if (isNavigating) {
                logToBackend('[NAV-FIX] ⚠️ livewire:navigating interceptado - ya estamos navegando');
                return; // No hacer nada, nuestro handler ya está manejando la navegación
            }
            
            const targetUrl = e.detail?.visit?.url || window.location.href;
            if (targetUrl !== currentPageUrl && targetUrl !== lastInitUrl) {
                // Marcar que necesitamos re-inicializar después
                isInitialized = false;
                lastInitUrl = targetUrl;
            }
        });

        // Re-inicializar después de navegación Livewire solo si la URL cambió realmente
        document.addEventListener('livewire:navigated', function() {
            // Resetear flag de navegación inmediatamente
            isNavigating = false;
            
            const newUrl = window.location.href;
            const urlChanged = newUrl !== currentPageUrl;
            
            if (urlChanged && !pendingInit) {
                pendingInit = true;
                currentPageUrl = newUrl;
                lastInitUrl = newUrl;
                isInitialized = false;
                
                // Después de navegación Livewire, usar inicialización rápida con delay mínimo
                setTimeout(function() {
                    if (!isProcessing) {
                        immediateInit(function() {
                            initNavigationFix(true);
                            pendingInit = false;
                        });
                    } else {
                        pendingInit = false;
                    }
                }, 50); // Reducir delay
            } else if (!urlChanged && !pendingInit) {
                // Si la URL no cambió, solo procesar nuevos links (sin re-inicializar)
                debounceInit(function() {
                    if (!isProcessing) {
                        processAllLinks();
                    }
                }, 30);
            }
        });

        // También escuchar cambios de URL directamente (para navegación tradicional con full page reload)
        let lastUrl = window.location.href;
        let urlCheckInterval = setInterval(function() {
            const currentUrl = window.location.href;
            if (currentUrl !== lastUrl && currentUrl !== lastInitUrl && !pendingInit) {
                lastUrl = currentUrl;
                lastInitUrl = currentUrl;
                currentPageUrl = currentUrl;
                isNavigating = false; // Resetear flag cuando la URL cambia
                isInitialized = false;
                isFirstLoad = true; // Tratar como primera carga después de reload
                pendingInit = true;
                
                // Después de full page reload, usar inicialización inmediata
                immediateInit(function() {
                    if (!isProcessing) {
                        initNavigationFix(true);
                        pendingInit = false;
                    } else {
                        pendingInit = false;
                    }
                });
            }
        }, 200); // Aumentar intervalo para reducir conflictos con livewire:navigated

        // Resetear flag de navegación después de un tiempo si la URL no cambió
        // Esto previene que quede bloqueado si algo falla
        setInterval(function() {
            if (isNavigating && Date.now() - lastClickTime > 2000) {
                logToBackend('[NAV-FIX] ⚠️ Timeout de navegación, reseteando flag');
                isNavigating = false;
            }
        }, 1000);

    })();
</script>