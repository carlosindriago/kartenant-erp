{{-- Modal de Ayuda de Teclado --}}
<div x-show="$wire.showKeyboardHelp" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.showKeyboardHelp = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full mx-4 overflow-hidden"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 px-6 py-5">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Atajos de Teclado
                </h3>
                <button @click="$wire.showKeyboardHelp = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-3">General</h4>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Ayuda</span>
                        <span class="kbd">F1</span>
                    </div>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Historial del día</span>
                        <span class="kbd">F2</span>
                    </div>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Buscar producto</span>
                        <span class="kbd">F3</span>
                    </div>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Procesar pago</span>
                        <span class="kbd">F12</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg mb-3">Carrito</h4>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Vaciar carrito</span>
                        <span class="kbd">F9</span>
                    </div>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Cancelar acción</span>
                        <span class="kbd">ESC</span>
                    </div>
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">Confirmar venta</span>
                        <span class="kbd">ENTER</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl border border-blue-200 dark:border-blue-700">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Escáner de Código de Barras:</strong> Escanee directamente el código de barras de un producto para agregarlo al carrito automáticamente. El sistema detectará la lectura y agregará el producto con un sonido de confirmación.
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4">
            <button 
                @click="$wire.showKeyboardHelp = false"
                class="w-full px-6 py-3 bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-800 text-white font-semibold rounded-xl transition-all"
            >
                Cerrar
            </button>
        </div>
    </div>
</div>
