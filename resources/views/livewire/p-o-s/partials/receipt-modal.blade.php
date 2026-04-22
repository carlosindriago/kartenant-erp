{{-- Modal de Comprobante --}}
<div x-data="{ 
        saleId: @entangle('lastSaleId'),
        show: @entangle('showReceiptModal')
     }"
     x-show="show && saleId" 
     x-cloak
     x-init="$watch('show', value => console.log('showReceiptModal:', value)); $watch('saleId', value => console.log('saleId:', value))"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.closeReceiptModal()"
     @keydown.escape="show && saleId && $wire.closeReceiptModal()"
     @keydown.enter="show && saleId && $wire.closeReceiptModal()">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto transform transition-all"
         @click.stop
         x-init="$el.focus()"
         tabindex="-1">
        @if($this->lastSale)
        {{-- Header --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        ¡Venta Completada!
                    </h3>
                    <p class="text-green-100 mt-1 font-medium">Comprobante #{{ $this->lastSale->invoice_number }}</p>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-5">
            {{-- Resumen de Venta --}}
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl p-6 space-y-4 border-2 border-gray-200 dark:border-gray-700 shadow-inner">
                <div class="text-center mb-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Fecha y Hora</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->lastSale->created_at->format('d/m/Y - H:i') }}</div>
                </div>
                
                @if($this->lastSale->items)
                    <div class="border-y border-gray-300 dark:border-gray-600 py-3 my-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Productos vendidos:</div>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @foreach($this->lastSale->items as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($item->quantity * $item->unit_price, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <div class="space-y-2">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>Subtotal (Neto):</span>
                        <span class="font-semibold">${{ number_format($this->lastSale->subtotal, 2) }}</span>
                    </div>
                    @if($this->lastSale->tax_amount > 0)
                        <div class="flex justify-between text-gray-700 dark:text-gray-300">
                            <span>IVA:</span>
                            <span class="font-semibold">${{ number_format($this->lastSale->tax_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
                
                <div class="flex justify-between items-center text-2xl font-bold text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-lg px-4 py-3 shadow-md border-2 border-green-500 dark:border-green-400">
                    <span>TOTAL:</span>
                    <span class="text-green-600 dark:text-green-400">${{ number_format($this->lastSale->total, 2) }}</span>
                </div>
                
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 pt-2">
                    <span>Método de Pago:</span>
                    <span class="font-semibold capitalize">
                        @if($this->lastSale->payment_method === 'cash')
                            💵 Efectivo
                        @elseif($this->lastSale->payment_method === 'card')
                            💳 Tarjeta
                        @elseif($this->lastSale->payment_method === 'transfer')
                            🏦 Transferencia
                        @else
                            {{ ucfirst($this->lastSale->payment_method) }}
                        @endif
                    </span>
                </div>
                
                @if($this->lastSale->customer)
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3 text-sm">
                        <div class="text-gray-600 dark:text-gray-400">Cliente:</div>
                        <div class="font-semibold text-gray-900 dark:text-white">👤 {{ $this->lastSale->customer->name }}</div>
                        @if($this->lastSale->customer->email)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">📧 {{ $this->lastSale->customer->email }}</div>
                        @endif
                    </div>
                @endif
                
                @if($this->lastSale->user)
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3 text-sm">
                        <div class="text-gray-600 dark:text-gray-400">Atendido por:</div>
                        <div class="font-semibold text-gray-900 dark:text-white">👨‍💼 {{ $this->lastSale->user->name }}</div>
                    </div>
                @endif
            </div>

            {{-- Código de Seguridad del Comprobante --}}
            @if($this->lastSale->verification_hash)
            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-gray-700 dark:to-gray-600 rounded-xl p-5 border-2 border-amber-300 dark:border-amber-600 shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-amber-500 dark:bg-amber-600 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="font-bold text-gray-800 dark:text-gray-100 text-base">🔐 Código de Seguridad</h4>
                            <span class="px-2 py-0.5 bg-amber-500 dark:bg-amber-600 text-white text-xs font-semibold rounded-full">SHA-256</span>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 mb-2 border border-amber-200 dark:border-amber-700">
                            <code class="text-xs font-mono text-gray-800 dark:text-gray-200 block break-all select-all leading-relaxed">
                                {{ $this->lastSale->verification_hash }}
                            </code>
                        </div>
                        <div class="flex items-start gap-2 text-xs text-amber-800 dark:text-amber-200">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="leading-snug">
                                <strong class="font-semibold">Código único e inmutable.</strong> Este hash verifica la autenticidad del comprobante y aparece en el PDF con código QR. Guárdelo para cualquier verificación futura.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

                {{-- Opciones de Comprobante --}}
                <div class="space-y-3">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">¿Qué deseas hacer con el comprobante?</div>
                    
                    {{-- Descargar PDF --}}
                    <a
                        :href="`${window.location.origin}/pos/receipt/${saleId}/pdf`"
                        target="_blank"
                        class="flex items-center justify-center gap-3 w-full px-5 py-4 bg-red-500 dark:bg-red-600 hover:bg-red-600 dark:hover:bg-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Descargar PDF</span>
                    </a>

                    {{-- Imprimir --}}
                    <button
                        @click="window.open(`${window.location.origin}/pos/receipt/${saleId}/print`, '_blank', 'width=800,height=600')"
                        class="flex items-center justify-center gap-3 w-full px-5 py-4 bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span>Imprimir</span>
                    </button>

                    {{-- Enviar por Email --}}
                    @if($this->lastSale && $this->lastSale->customer && $this->lastSale->customer->email)
                        <button
                            wire:click="emailReceipt({{ $this->lastSale->id }})"
                            class="flex items-center justify-center gap-3 w-full px-5 py-4 bg-purple-500 dark:bg-purple-600 hover:bg-purple-600 dark:hover:bg-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>Enviar por Email</span>
                            <span class="text-xs bg-purple-700 dark:bg-purple-900 px-2 py-1 rounded">{{ $this->lastSale->customer->email }}</span>
                        </button>
                    @endif
                </div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button
                wire:click="closeReceiptModal"
                class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 hover:from-green-600 hover:to-green-700 dark:hover:from-green-700 dark:hover:to-green-800 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2"
            >
                <span>✅ Nueva Venta</span>
                <span class="text-sm bg-white bg-opacity-20 px-2 py-1 rounded">[Enter] o [Esc]</span>
            </button>
            <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-3">
                Presiona <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-gray-800 dark:text-gray-200 font-mono text-xs">Enter</kbd> o 
                <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-gray-800 dark:text-gray-200 font-mono text-xs">Esc</kbd> para continuar con la siguiente venta
            </p>
        </div>
        @endif
    </div>
</div>
