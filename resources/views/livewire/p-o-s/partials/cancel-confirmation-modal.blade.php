{{-- Modal de Confirmación de Anulación - Cámara de Seguridad Digital --}}
<div 
    x-data="{ show: @entangle('showCancelConfirmationModal').live }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm"
    ></div>

    {{-- Modal Content --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="$wire.closeCancelModal()"
                class="relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl"
            >
                {{-- Header - ADVERTENCIA --}}
                <div class="bg-gradient-to-r from-red-600 to-red-700 dark:from-red-700 dark:to-red-800 px-6 py-5 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 w-14 h-14 bg-white/20 rounded-full flex items-center justify-center animate-pulse">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white">⚠️ ANULAR VENTA</h3>
                                <p class="text-red-100 text-sm mt-1">Esta acción requiere autorización con contraseña</p>
                            </div>
                        </div>
                        <button 
                            wire:click="closeCancelModal"
                            class="text-white/80 hover:text-white hover:bg-white/10 rounded-lg p-2 transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                    @if($this->saleToBeCancel)
                        {{-- Alert Box - Lo que va a ocurrir --}}
                        <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-r-lg">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-yellow-800 dark:text-yellow-300 mb-2">📹 Lo que va a ocurrir:</h4>
                                    <ul class="space-y-2 text-yellow-700 dark:text-yellow-400">
                                        <li class="flex items-start">
                                            <span class="mr-2">•</span>
                                            <span>Se creará una <strong>Nota de Crédito</strong> automáticamente</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-2">•</span>
                                            <span>Todos los productos volverán al <strong>inventario</strong></span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-2">•</span>
                                            <span>Se registrará en el <strong>log de auditoría</strong> con tu nombre y contraseña</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-2">•</span>
                                            <span>La venta original <strong>NO se borrará</strong> (libro inmutable)</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="mr-2">•</span>
                                            <span>Esta acción quedará <strong>permanentemente registrada</strong> en el sistema</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Resumen de la Venta --}}
                        <div class="mb-6 bg-gray-50 dark:bg-gray-900 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Resumen de la Venta a Anular
                            </h4>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Factura</div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $this->saleToBeCancel->invoice_number }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Fecha</div>
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $this->saleToBeCancel->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Cliente</div>
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $this->saleToBeCancel->customer?->name ?? 'Cliente General' }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Cajero Original</div>
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $this->saleToBeCancel->user?->name ?? 'Desconocido' }}</div>
                                </div>
                            </div>

                            {{-- Productos --}}
                            <div class="mt-4">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Productos que se devolverán:</div>
                                <div class="space-y-2 max-h-48 overflow-y-auto bg-white dark:bg-gray-800 rounded-lg p-3">
                                    @foreach($this->saleToBeCancel->items as $item)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-gray-900 dark:text-white">${{ number_format($item->unit_price * $item->quantity, 2) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">TOTAL A REEMBOLSAR:</span>
                                    <span class="text-2xl font-bold text-red-600 dark:text-red-500">${{ number_format($this->saleToBeCancel->total, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Campo de Observaciones --}}
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Motivo de la anulación <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                wire:model="cancelObservations"
                                rows="3"
                                placeholder="Ej: Cliente devolvió producto, error en precio, venta duplicada, etc."
                                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 transition-all placeholder-gray-400 dark:placeholder-gray-500 resize-none"
                            ></textarea>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-start">
                                <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Explica brevemente por qué se está anulando esta venta. Esta información quedará registrada permanentemente.</span>
                            </p>
                        </div>

                        {{-- Campo de Contraseña --}}
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Ingrese su contraseña para autorizar esta anulación
                            </label>
                            <input 
                                type="password"
                                wire:model="cancelPassword"
                                wire:keydown.enter="confirmCancelSale"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 border-2 @error('cancelPasswordError') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:border-red-500 dark:focus:border-red-400 focus:ring-2 focus:ring-red-200 dark:focus:ring-red-900 transition-all placeholder-gray-400 dark:placeholder-gray-500 text-lg"
                                autofocus
                            >
                            @if($cancelPasswordError)
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500 font-semibold flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $cancelPasswordError }}
                                </p>
                            @endif
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-start">
                                <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Su identidad y esta acción quedarán registradas permanentemente en el sistema de auditoría (Cámara de Seguridad Digital)</span>
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer - Botones --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-2xl border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-4">
                        <button 
                            wire:click="closeCancelModal"
                            type="button"
                            class="flex-1 px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                        <button 
                            wire:click="confirmCancelSale"
                            type="button"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Confirmar Anulación
                        </button>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        📹 Esta acción será registrada con su usuario, IP y timestamp
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
