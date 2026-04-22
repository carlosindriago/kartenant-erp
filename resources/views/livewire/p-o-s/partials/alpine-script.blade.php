{{-- Alpine.js Component Script --}}
<script>
    document.addEventListener('livewire:init', () => {
        window.posTerminal = function() {
        return {
            darkMode: localStorage.getItem('posTheme') === 'dark',
            showNotif: false,
            notifType: 'info',
            notifMessage: '',
            barcodeBuffer: '',
            lastKeyTime: 0,
            
            init() {
                // Aplicar dark mode inmediatamente al inicializar
                if (this.darkMode) {
                    this.$el.classList.add('dark');
                }
                
                // Watch para cambios futuros
                this.$watch('darkMode', val => {
                    localStorage.setItem('posTheme', val ? 'dark' : 'light');
                    if (val) {
                        this.$el.classList.add('dark');
                    } else {
                        this.$el.classList.remove('dark');
                    }
                });
                
                // Cargar carrito desde localStorage
                this.loadCartFromLocalStorage();
                
                // Focus search input on load
                setTimeout(() => {
                    const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
                    if (searchInput) searchInput.focus();
                }, 100);
                
                // Setup barcode scanner listener
                this.setupBarcodeScanner();
            },
            
            loadCartFromLocalStorage() {
                try {
                    const savedCart = localStorage.getItem('pos_cart');
                    if (savedCart) {
                        const cartData = JSON.parse(savedCart);
                        // Llamar directamente al método Livewire
                        this.$wire.call('restoreCart', cartData);
                    }
                } catch (e) {
                    console.error('Error cargando carrito desde localStorage:', e);
                    localStorage.removeItem('pos_cart');
                }
            },
            
            handleKeyPress(event) {
                const target = event.target;
                const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
                
                // F1 - Ayuda
                if (event.key === 'F1') {
                    event.preventDefault();
                    this.$wire.showKeyboardHelp = !this.$wire.showKeyboardHelp;
                    return;
                }
                
                // F2 - Historial
                if (event.key === 'F2') {
                    event.preventDefault();
                    this.$wire.loadTodaySales();
                    return;
                }
                
                // F3 - Buscar (focus en input)
                if (event.key === 'F3') {
                    event.preventDefault();
                    const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                    return;
                }
                
                // F9 - Vaciar carrito
                if (event.key === 'F9') {
                    event.preventDefault();
                    if (confirm('¿Está seguro de vaciar el carrito?')) {
                        this.$wire.clearCart();
                    }
                    return;
                }
                
                // F12 - Procesar pago
                if (event.key === 'F12') {
                    event.preventDefault();
                    this.$wire.openPaymentModal();
                    return;
                }
                
                // ESC - Cerrar modales
                if (event.key === 'Escape') {
                    if (this.$wire.showPaymentModal) {
                        this.$wire.showPaymentModal = false;
                    } else if (this.$wire.showHistoryModal) {
                        this.$wire.showHistoryModal = false;
                    } else if (this.$wire.showKeyboardHelp) {
                        this.$wire.showKeyboardHelp = false;
                    }
                    return;
                }
                
                // ENTER - Completar venta (solo si modal de pago está abierto)
                if (event.key === 'Enter' && this.$wire.showPaymentModal && !isInput) {
                    event.preventDefault();
                    this.$wire.completeSale();
                    return;
                }
            },
            
            setupBarcodeScanner() {
                let barcodeBuffer = '';
                let lastKeyTime = Date.now();
                
                document.addEventListener('keypress', (e) => {
                    const currentTime = Date.now();
                    const target = e.target;
                    const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
                    
                    // Si estamos en un input, no capturar
                    if (isInput) {
                        return;
                    }
                    
                    // Si el tiempo entre teclas es mayor a 100ms, reiniciar buffer
                    if (currentTime - lastKeyTime > 100) {
                        barcodeBuffer = '';
                    }
                    
                    lastKeyTime = currentTime;
                    
                    // Agregar caracter al buffer
                    if (e.key && e.key.length === 1) {
                        barcodeBuffer += e.key;
                    }
                    
                    // Si presiona Enter, procesar código de barras
                    if (e.key === 'Enter' && barcodeBuffer.length > 3) {
                        e.preventDefault();
                        const barcode = barcodeBuffer.trim();
                        barcodeBuffer = '';
                        
                        // Llamar al método Livewire
                        this.$wire.call('addByBarcode', barcode);
                    }
                });
            },
            
            showNotification(detail) {
                this.notifType = detail.type || 'info';
                this.notifMessage = detail.message || '';
                this.showNotif = true;
                
                setTimeout(() => {
                    this.showNotif = false;
                }, 3000);
            },
            
            playBeep() {
                const audio = document.getElementById('beep-sound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                }
            },
            
            playErrorBeep() {
                // Doble beep para errores
                const audio = document.getElementById('beep-sound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                    setTimeout(() => {
                        audio.currentTime = 0;
                        audio.play().catch(e => console.log('Audio play failed:', e));
                    }, 150);
                }
            },
            
            flashSuccess() {
                // Agregar clase de animación al body
                document.body.classList.add('success-flash');
                setTimeout(() => {
                    document.body.classList.remove('success-flash');
                }, 600);
            },
            
            onSaleCompleted(detail) {
                this.showNotification({
                    type: 'success',
                    message: '¡Venta completada exitosamente!'
                });
                
                // Reproducir sonido de éxito
                this.playBeep();
                
                // Efecto visual
                this.flashSuccess();
            }
        };
        };
    });
</script>
