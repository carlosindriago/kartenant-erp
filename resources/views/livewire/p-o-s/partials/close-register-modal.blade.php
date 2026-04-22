{{-- Modal de Cierre y Arqueo de Caja --}}
<div x-show="$wire.showCloseRegisterModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.cancelCloseCashRegister()">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden transform transition-all max-h-[90vh] overflow-y-auto"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 px-5 py-4 sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Cierre de Caja y Arqueo
                </h3>
                <button @click="$wire.cancelCloseCashRegister()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-5">
            @if($currentCashRegister)
            {{-- Información de la Caja --}}
            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Información del Turno
                </h4>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Número de Caja:</span>
                        <span class="ml-2 font-semibold text-gray-800 dark:text-gray-100">{{ $currentCashRegister->register_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Abierta por:</span>
                        <span class="ml-2 font-semibold text-gray-800 dark:text-gray-100">{{ $currentCashRegister->openedBy->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Hora de Apertura:</span>
                        <span class="ml-2 font-semibold text-gray-800 dark:text-gray-100">{{ $currentCashRegister->opened_at->format('H:i') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Duración:</span>
                        <span class="ml-2 font-semibold text-gray-800 dark:text-gray-100">{{ $currentCashRegister->opened_at->diffForHumans(null, true) }}</span>
                    </div>
                </div>
            </div>

            {{-- Cálculo del Efectivo --}}
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Cálculo de Efectivo
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Fondo Inicial --}}
                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Fondo Inicial</div>
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">${{ number_format($currentCashRegister->initial_amount, 2) }}</div>
                    </div>

                    {{-- Ventas en Efectivo --}}
                    <div class="bg-green-50 dark:bg-green-900 dark:bg-opacity-30 rounded-xl p-4 border border-green-200 dark:border-green-700">
                        <div class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">+ Ventas Efectivo</div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                            ${{ number_format($cashSalesTotal, 2) }}
                        </div>
                    </div>

                    {{-- Devoluciones --}}
                    <div class="bg-orange-50 dark:bg-orange-900 dark:bg-opacity-30 rounded-xl p-4 border border-orange-200 dark:border-orange-700">
                        <div class="text-xs text-orange-600 dark:text-orange-400 font-medium mb-1">- Devoluciones</div>
                        <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                            ${{ number_format($cancelledSalesTotal, 2) }}
                        </div>
                    </div>
                </div>

                {{-- Monto Esperado --}}
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 dark:bg-opacity-30 rounded-xl p-5 border-2 border-purple-300 dark:border-purple-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-1">Monto Esperado en Caja</div>
                            <div class="text-xs text-purple-500 dark:text-purple-400">(Lo que debería haber según el sistema)</div>
                        </div>
                        <div class="text-4xl font-bold text-purple-700 dark:text-purple-300">
                            ${{ number_format($currentCashRegister->calculateExpectedAmount(), 2) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Arqueo: Monto Contado --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Monto Contado Físicamente <span class="text-red-500">*</span>
                    </span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 font-semibold text-lg">$</span>
                    </div>
                    <input 
                        type="number" 
                        wire:model.live="closingActualAmount"
                        step="0.01"
                        min="0"
                        class="block w-full pl-10 pr-4 py-4 text-2xl font-bold border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-colors"
                        placeholder="0.00"
                        autofocus
                    >
                </div>
                @error('closingActualAmount')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 bg-yellow-50 dark:bg-yellow-900 dark:bg-opacity-20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-700">
                    <strong>Importante:</strong> Cuenta físicamente todo el efectivo en tu caja e ingresa el monto total aquí.
                </p>
            </div>

            {{-- Diferencia --}}
            @if($closingActualAmount > 0)
            @php
                $difference = $closingActualAmount - $currentCashRegister->calculateExpectedAmount();
            @endphp
            <div class="rounded-xl p-5 border-2 @if($difference > 0) bg-green-50 dark:bg-green-900 dark:bg-opacity-30 border-green-300 dark:border-green-600 @elseif($difference < 0) bg-red-50 dark:bg-red-900 dark:bg-opacity-30 border-red-300 dark:border-red-600 @else bg-emerald-50 dark:bg-emerald-900 dark:bg-opacity-30 border-emerald-300 dark:border-emerald-600 @endif">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium mb-1 @if($difference > 0) text-green-600 dark:text-green-400 @elseif($difference < 0) text-red-600 dark:text-red-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                            @if($difference > 0)
                                ✅ Sobrante
                            @elseif($difference < 0)
                                ⚠️ Faltante
                            @else
                                🎯 ¡Exacto!
                            @endif
                        </div>
                        <div class="text-xs @if($difference > 0) text-green-500 dark:text-green-400 @elseif($difference < 0) text-red-500 dark:text-red-400 @else text-emerald-500 dark:text-emerald-400 @endif">
                            {{ $difference > 0 ? 'Tienes más dinero de lo esperado' : ($difference < 0 ? 'Falta dinero en la caja' : 'El monto coincide perfectamente') }}
                        </div>
                    </div>
                    <div class="text-4xl font-bold @if($difference > 0) text-green-700 dark:text-green-300 @elseif($difference < 0) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif">
                        {{ $difference >= 0 ? '+' : '' }}${{ number_format(abs($difference), 2) }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Observaciones --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Observaciones del Cierre (Opcional)
                </label>
                <textarea 
                    wire:model="closingNotes"
                    rows="3"
                    class="block w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white resize-none transition-colors"
                    placeholder="Ej: Explicación de diferencias, incidentes, etc."
                ></textarea>
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-4 flex gap-3 sticky bottom-0">
            <button 
                @click="$wire.cancelCloseCashRegister()"
                class="flex-1 px-4 py-3 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors border border-gray-300 dark:border-gray-500"
            >
                Cancelar
            </button>
            <button 
                wire:click="closeCashRegister"
                wire:loading.attr="disabled"
                class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" wire:loading>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>Cerrar Caja</span>
                <span wire:loading>Cerrando...</span>
            </button>
        </div>
    </div>
</div>
