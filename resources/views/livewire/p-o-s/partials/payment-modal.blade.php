{{-- Modal de Pago --}}
<div x-show="$wire.showPaymentModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.showPaymentModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full mx-4 overflow-hidden transform transition-all"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Procesar Pago
                </h3>
                <button @click="$wire.showPaymentModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">
            {{-- Total a Cobrar --}}
            <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 text-center border-2 border-blue-200 dark:border-blue-700">
                <div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Total a Cobrar</div>
                <div class="text-4xl font-bold text-blue-700 dark:text-blue-300">${{ number_format($total, 2) }}</div>
            </div>

            {{-- Método de Pago --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Método de Pago</label>
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        wire:click="$set('paymentMethod', 'cash')"
                        class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all"
                        :class="$wire.paymentMethod === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-900 dark:bg-opacity-30' : 'border-gray-300 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-700'"
                    >
                        <svg class="w-8 h-8" :class="$wire.paymentMethod === 'cash' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="font-semibold text-sm" :class="$wire.paymentMethod === 'cash' ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300'">Efectivo</span>
                    </button>
                    
                    <button 
                        wire:click="$set('paymentMethod', 'card')"
                        class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all"
                        :class="$wire.paymentMethod === 'card' ? 'border-green-500 bg-green-50 dark:bg-green-900 dark:bg-opacity-30' : 'border-gray-300 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-700'"
                    >
                        <svg class="w-8 h-8" :class="$wire.paymentMethod === 'card' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span class="font-semibold text-sm" :class="$wire.paymentMethod === 'card' ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300'">Tarjeta</span>
                    </button>
                    
                    <button 
                        wire:click="$set('paymentMethod', 'transfer')"
                        class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all"
                        :class="$wire.paymentMethod === 'transfer' ? 'border-green-500 bg-green-50 dark:bg-green-900 dark:bg-opacity-30' : 'border-gray-300 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-700'"
                    >
                        <svg class="w-8 h-8" :class="$wire.paymentMethod === 'transfer' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span class="font-semibold text-sm" :class="$wire.paymentMethod === 'transfer' ? 'text-green-700 dark:text-green-300' : 'text-gray-700 dark:text-gray-300'">Transferencia</span>
                    </button>
                </div>
            </div>

            {{-- Solo para Efectivo: Monto Recibido --}}
            <div x-show="$wire.paymentMethod === 'cash'">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Monto Recibido</label>
                <input 
                    type="number" 
                    wire:model.live="amountReceived"
                    wire:change="calculateChange"
                    step="0.01"
                    class="w-full text-3xl font-bold text-center px-4 py-4 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900 transition-all"
                    placeholder="0.00"
                >
                
                {{-- Botones de Monto Rápido --}}
                <div class="grid grid-cols-4 gap-2 mt-2">
                    @foreach([100, 200, 500, 1000] as $quickAmount)
                        <button 
                            wire:click="setQuickAmount({{ $quickAmount }})"
                            class="py-2 px-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-colors"
                        >
                            ${{ $quickAmount }}
                        </button>
                    @endforeach
                </div>

                {{-- Cambio --}}
                <div class="mt-3 bg-amber-50 dark:bg-amber-900 dark:bg-opacity-30 rounded-xl p-3 border-2 border-amber-200 dark:border-amber-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">Cambio a Devolver:</span>
                        <span class="text-2xl font-bold text-amber-700 dark:text-amber-300">${{ number_format($changeAmount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Código de Referencia (Para Tarjeta y Transferencia) --}}
            <div x-show="$wire.paymentMethod === 'card' || $wire.paymentMethod === 'transfer'">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Código de Referencia <span class="text-xs text-gray-500 dark:text-gray-400">(opcional)</span>
                </label>
                <input 
                    type="text" 
                    wire:model="transactionReference"
                    class="w-full px-4 py-3 border-2 border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 text-gray-900 dark:text-white rounded-xl focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 transition-all font-mono"
                    placeholder="Ej: TRX-20231015-1234 o últimos 4 dígitos"
                    maxlength="50"
                >
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    📋 Útil para verificación con el banco si es necesario
                </p>
            </div>

            {{-- Selector de Cliente --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Cliente (opcional)</label>
                    <button 
                        wire:click="toggleQuickCustomerForm"
                        type="button"
                        class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                    >
                        @if($showQuickCustomerForm)
                            ✕ Cancelar
                        @else
                            + Crear Nuevo
                        @endif
                    </button>
                </div>
                
                @if($showQuickCustomerForm)
                    {{-- Formulario Creación Rápida --}}
                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-3 space-y-2">
                        <input 
                            type="text" 
                            wire:model="quickCustomerName"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm"
                            placeholder="Nombre completo *"
                        >
                        
                        {{-- Documento (DNI/CUIL/CUIT) --}}
                        <div class="grid grid-cols-3 gap-2">
                            <select 
                                wire:model="quickCustomerDocumentType"
                                class="col-span-1 px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm"
                            >
                                <option value="DNI">DNI</option>
                                <option value="CUIL">CUIL</option>
                                <option value="CUIT">CUIT</option>
                            </select>
                            <input 
                                type="text" 
                                wire:model="quickCustomerDocumentNumber"
                                class="col-span-2 px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm"
                                placeholder="Número de documento"
                                maxlength="20"
                            >
                        </div>
                        
                        <input 
                            type="tel" 
                            wire:model="quickCustomerPhone"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm"
                            placeholder="Teléfono"
                        >
                        <input 
                            type="email" 
                            wire:model="quickCustomerEmail"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm"
                            placeholder="Email"
                        >
                        <button 
                            wire:click="createQuickCustomer"
                            type="button"
                            class="w-full px-3 py-2 bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 text-white font-semibold rounded-lg text-sm transition-colors"
                        >
                            💾 Guardar Cliente
                        </button>
                    </div>
                @else
                    {{-- Búsqueda de Cliente --}}
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="customerSearch"
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900 transition-all"
                            placeholder="Buscar cliente por nombre, documento o teléfono..."
                        >
                        
                        @if($customerId)
                            <button 
                                wire:click="clearCustomer"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                        
                        {{-- Resultados de Búsqueda --}}
                        @if(count($customerSearchResults) > 0 && !$customerId)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($customerSearchResults as $customer)
                                    <button 
                                        wire:click="selectCustomer({{ $customer['id'] }})"
                                        type="button"
                                        class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0"
                                    >
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ $customer['name'] }}</div>
                                        @if(!empty($customer['document_number']) || !empty($customer['phone']))
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                @if(!empty($customer['document_number']))
                                                    {{ $customer['document_type'] ?? 'Doc' }}: {{ $customer['document_number'] }}
                                                @endif
                                                @if(!empty($customer['phone']))
                                                    @if(!empty($customer['document_number'])) | @endif Tel: {{ $customer['phone'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-900 px-5 py-3 flex gap-3">
            <button 
                @click="$wire.showPaymentModal = false"
                class="flex-1 px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-all"
            >
                Cancelar <span class="kbd ml-2">ESC</span>
            </button>
            <button 
                wire:click="completeSale"
                class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 hover:from-green-600 hover:to-green-700 dark:hover:from-green-700 dark:hover:to-green-800 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
            >
                Confirmar Venta <span class="kbd ml-2 bg-green-700 dark:bg-green-900 border-green-600 dark:border-green-800">ENTER</span>
            </button>
        </div>
    </div>
</div>
