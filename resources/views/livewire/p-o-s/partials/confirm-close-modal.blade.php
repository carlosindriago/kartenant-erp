{{-- Modal de Confirmación de Cierre --}}
<div x-show="$wire.showConfirmCloseModal" 
     x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm"
     @click.self="$wire.cancelCloseCashRegister()">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full mx-4 overflow-hidden transform transition-all"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        ⚠️ Confirmar Cierre de Caja
                    </h3>
                    <p class="text-amber-100 text-sm mt-1">Por favor, verifica que toda la información sea correcta antes de confirmar</p>
                </div>
                <button @click="$wire.cancelCloseCashRegister()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-6 max-h-[calc(90vh-180px)] overflow-y-auto">
            @if($currentCashRegister)
            
            {{-- Advertencia Principal --}}
            <div class="bg-amber-50 dark:bg-amber-900 dark:bg-opacity-20 border-2 border-amber-400 dark:border-amber-600 rounded-xl p-5">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-bold text-amber-900 dark:text-amber-200 mb-2">
                            ⚠️ Estás a punto de cerrar la caja
                        </h4>
                        <p class="text-amber-800 dark:text-amber-300 text-sm leading-relaxed">
                            Esta acción <strong>NO SE PUEDE DESHACER</strong>. Por favor, verifica cuidadosamente todos los datos a continuación antes de confirmar el cierre.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Resumen de la Caja --}}
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900 dark:to-indigo-900 dark:bg-opacity-20 rounded-xl p-5 border-2 border-blue-200 dark:border-blue-700">
                <h4 class="font-bold text-blue-900 dark:text-blue-200 mb-4 flex items-center gap-2 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    📋 Información del Turno
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Número de Caja</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ $currentCashRegister->register_number }}</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Cajero</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ $currentCashRegister->openedBy->name }}</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Hora de Apertura</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ $currentCashRegister->opened_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Duración del Turno</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ $currentCashRegister->opened_at->diffForHumans(null, true) }}</span>
                    </div>
                </div>
            </div>

            {{-- Resumen Financiero --}}
            <div class="space-y-4">
                <h4 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    💰 Resumen Financiero
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Fondo Inicial --}}
                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-700">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Fondo Inicial</div>
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">${{ number_format($currentCashRegister->initial_amount, 2) }}</div>
                    </div>

                    {{-- Ventas en Efectivo --}}
                    <div class="bg-green-50 dark:bg-green-900 dark:bg-opacity-30 rounded-xl p-4 border-2 border-green-200 dark:border-green-700">
                        <div class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">+ Ventas Efectivo</div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">${{ number_format($cashSalesTotal, 2) }}</div>
                    </div>

                    {{-- Ventas Canceladas --}}
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-600">
                        <div class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1">Ventas Canceladas</div>
                        <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">${{ number_format($cancelledSalesTotal, 2) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">(Dinero devuelto)</div>
                    </div>
                </div>

                {{-- Monto Esperado vs Contado --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Esperado --}}
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 dark:bg-opacity-30 rounded-xl p-5 border-2 border-purple-300 dark:border-purple-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-1">💎 Monto Esperado</div>
                                <div class="text-xs text-purple-500 dark:text-purple-400">(Según el sistema)</div>
                            </div>
                            <div class="text-4xl font-bold text-purple-700 dark:text-purple-300">
                                ${{ number_format($currentCashRegister->calculateExpectedAmount(), 0) }}
                            </div>
                        </div>
                    </div>

                    {{-- Contado --}}
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900 dark:to-indigo-800 dark:bg-opacity-30 rounded-xl p-5 border-2 border-indigo-300 dark:border-indigo-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-indigo-600 dark:text-indigo-400 font-medium mb-1">💰 Monto Contado</div>
                                <div class="text-xs text-indigo-500 dark:text-indigo-400">(Lo que hay en caja)</div>
                            </div>
                            <div class="text-4xl font-bold text-indigo-700 dark:text-indigo-300">
                                ${{ number_format($closingActualAmount, 0) }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Diferencia --}}
                @php
                    $difference = $closingActualAmount - $currentCashRegister->calculateExpectedAmount();
                @endphp
                <div class="rounded-xl p-6 border-4 @if($difference > 0) bg-green-50 dark:bg-green-900 dark:bg-opacity-30 border-green-400 dark:border-green-600 @elseif($difference < 0) bg-red-50 dark:bg-red-900 dark:bg-opacity-30 border-red-400 dark:border-red-600 @else bg-emerald-50 dark:bg-emerald-900 dark:bg-opacity-30 border-emerald-400 dark:border-emerald-600 @endif">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-lg font-bold mb-2 @if($difference > 0) text-green-700 dark:text-green-300 @elseif($difference < 0) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif">
                                @if($difference > 0)
                                    ✅ SOBRANTE
                                @elseif($difference < 0)
                                    ⚠️ FALTANTE
                                @else
                                    🎯 ¡EXACTO!
                                @endif
                            </div>
                            <div class="text-sm @if($difference > 0) text-green-600 dark:text-green-400 @elseif($difference < 0) text-red-600 dark:text-red-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                                @if($difference > 0)
                                    Hay más dinero del esperado en la caja
                                @elseif($difference < 0)
                                    Falta dinero en la caja según el sistema
                                @else
                                    El monto coincide perfectamente con el esperado
                                @endif
                            </div>
                        </div>
                        <div class="text-5xl font-black @if($difference > 0) text-green-700 dark:text-green-300 @elseif($difference < 0) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif">
                            {{ $difference >= 0 ? '+' : '' }}${{ number_format(abs($difference), 0) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Observaciones --}}
            @if($closingNotes)
            <div class="bg-yellow-50 dark:bg-yellow-900 dark:bg-opacity-20 rounded-xl p-4 border-2 border-yellow-200 dark:border-yellow-700">
                <h4 class="font-bold text-yellow-900 dark:text-yellow-200 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Observaciones
                </h4>
                <p class="text-yellow-800 dark:text-yellow-300 text-sm whitespace-pre-wrap">{{ $closingNotes }}</p>
            </div>
            @endif

            {{-- Checklist de Verificación --}}
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-5 border-2 border-gray-300 dark:border-gray-600">
                <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    ✅ Verifica antes de confirmar:
                </h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>¿El <strong>monto contado</strong> es correcto? (${{ number_format($closingActualAmount, 0) }})</span>
                    </li>
                    <li class="flex items-start gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>¿Contaste <strong>todo el efectivo</strong> físicamente?</span>
                    </li>
                    <li class="flex items-start gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>¿La <strong>diferencia</strong> es la correcta? ({{ $difference >= 0 ? '+' : '' }}${{ number_format(abs($difference), 0) }})</span>
                    </li>
                    <li class="flex items-start gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>¿Agregaste <strong>observaciones</strong> si hay diferencias?</span>
                    </li>
                </ul>
            </div>

            @endif
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-5 flex gap-3 border-t-2 border-gray-200 dark:border-gray-600">
            <button 
                wire:click="backToCloseModal"
                class="flex-1 px-6 py-4 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-500 transition-all border-2 border-gray-300 dark:border-gray-500 flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver a Editar
            </button>
            <button 
                wire:click="confirmAndCloseCashRegister"
                wire:loading.attr="disabled"
                class="flex-1 px-6 py-4 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-lg"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24" wire:loading>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove>✅ Sí, Cerrar Caja Ahora</span>
                <span wire:loading>Cerrando...</span>
            </button>
        </div>
    </div>
</div>
