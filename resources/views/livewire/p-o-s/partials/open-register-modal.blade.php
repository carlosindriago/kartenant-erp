{{-- Modal de Apertura de Caja --}}
<div x-show="$wire.showOpenRegisterModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.cancelOpenCashRegister()">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden transform transition-all"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Apertura de Caja
                </h3>
                <button @click="$wire.cancelOpenCashRegister()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-5">
            {{-- Información --}}
            <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-semibold mb-1">Inicio de Turno</p>
                        <p>Registra el fondo inicial con el que comienzas el día. Este es el "punto cero" para el control de efectivo.</p>
                    </div>
                </div>
            </div>

            {{-- Fondo Inicial --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Fondo Inicial <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold">$</span>
                    </div>
                    <input 
                        type="number" 
                        wire:model.live="openingAmount"
                        step="any"
                        min="0"
                        class="block w-full pl-8 pr-4 py-3 text-lg font-bold border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white transition-colors"
                        placeholder="0"
                        autofocus
                    >
                </div>
                @error('openingAmount')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Puedes usar decimales (100.50) o números enteros (100). Si no tienes fondo inicial, ingresa 0
                </p>
            </div>
            
            {{-- Warning para apertura con $0 --}}
            @if($openingAmount == 0 && !$confirmZeroOpening)
                <div class="bg-amber-50 dark:bg-amber-900 dark:bg-opacity-30 rounded-xl p-4 border-2 border-amber-300 dark:border-amber-600 animate-pulse">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-amber-800 dark:text-amber-300 mb-1">Atención: Apertura sin fondo inicial</p>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                Estás por abrir la caja sin dinero para dar cambio. Esto puede dificultar las ventas en efectivo. 
                                <strong>¿Estás seguro?</strong>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Confirmación de apertura con $0 --}}
            @if($confirmZeroOpening)
                <div class="bg-red-50 dark:bg-red-900 dark:bg-opacity-30 rounded-xl p-4 border-2 border-red-300 dark:border-red-600">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-red-800 dark:text-red-300 mb-2">⚠️ Confirmación Requerida</p>
                            <p class="text-sm text-red-700 dark:text-red-400 mb-3">
                                Has confirmado que deseas abrir la caja <strong>sin dinero para cambio</strong>. 
                                Esto significa que no podrás dar vuelto en las primeras ventas en efectivo.
                            </p>
                            <p class="text-sm text-red-700 dark:text-red-400">
                                Presiona <strong>"Abrir Caja"</strong> nuevamente para confirmar esta operación.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Observaciones --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Observaciones (Opcional)
                </label>
                <textarea 
                    wire:model="openingNotes"
                    rows="3"
                    class="block w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white resize-none transition-colors"
                    placeholder="Ej: Cambio recibido del banco, turno mañana, etc."
                ></textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Cualquier información relevante sobre el inicio del turno
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-4 flex gap-3">
            <button 
                @click="$wire.cancelOpenCashRegister()"
                class="flex-1 px-4 py-3 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors border border-gray-300 dark:border-gray-500"
            >
                Cancelar
            </button>
            <button 
                wire:click="openCashRegister"
                wire:loading.attr="disabled"
                class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                </svg>
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" wire:loading>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>Abrir Caja</span>
                <span wire:loading>Abriendo...</span>
            </button>
        </div>
    </div>
</div>
