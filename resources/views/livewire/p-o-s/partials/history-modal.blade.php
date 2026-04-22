{{-- Modal de Historial --}}
<div x-show="$wire.showHistoryModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.showHistoryModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 px-6 py-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Historial de Hoy
                </h3>
                <button @click="$wire.showHistoryModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            {{-- Tabs --}}
            <div class="flex gap-2">
                <button 
                    wire:click="$set('historyTab', 'sales')"
                    class="px-4 py-2 rounded-lg font-semibold transition-all
                        @if($historyTab === 'sales') 
                            bg-white text-purple-600
                        @else 
                            text-white bg-white bg-opacity-20 hover:bg-opacity-30
                        @endif"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Ventas ({{ count($todaySales) }})
                    </div>
                </button>
                <button 
                    wire:click="$set('historyTab', 'cash_registers')"
                    class="px-4 py-2 rounded-lg font-semibold transition-all
                        @if($historyTab === 'cash_registers') 
                            bg-white text-purple-600
                        @else 
                            text-white bg-white bg-opacity-20 hover:bg-opacity-30
                        @endif"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Cajas ({{ count($todayCashRegisters) }})
                    </div>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
            {{-- Tab: Ventas --}}
            @if($historyTab === 'sales')
                @if(empty($todaySales) || count($todaySales) === 0)
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay ventas aún</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Las ventas del día aparecerán aquí</p>
                    </div>
                @else
                <div class="space-y-3">
                    @foreach($todaySales as $sale)
                        <div 
                            wire:click="viewSaleDetails({{ $sale->id }})"
                            class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer group"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400 
                                            @if($sale->status === 'cancelled') line-through opacity-50 @endif">
                                            #{{ $sale->invoice_number }}
                                        </span>
                                        <span class="text-xs px-2 py-1 rounded-full font-semibold
                                            @if($sale->status === 'completed') bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300
                                            @elseif($sale->status === 'cancelled') bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300
                                            @elseif($sale->status === 'pending') bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300
                                            @else bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300
                                            @endif">
                                            @if($sale->status === 'completed') Completada
                                            @elseif($sale->status === 'cancelled') ❌ Anulada
                                            @elseif($sale->status === 'pending') Pendiente
                                            @else {{ ucfirst($sale->status) }}
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->created_at->format('H:i:s') }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <div><strong>Items:</strong> {{ $sale->items->count() }} productos</div>
                                        <div><strong>Vendedor:</strong> {{ $sale->user->name ?? 'N/A' }}</div>
                                        @if($sale->customer)
                                            <div><strong>Cliente:</strong> {{ $sale->customer->name }}</div>
                                        @endif
                                        <div><strong>Pago:</strong> {{ ucfirst($sale->payment_method) }}</div>
                                        @if(!empty($sale->transaction_reference) && in_array($sale->payment_method, ['card', 'transfer']))
                                            <div class="flex items-center gap-1">
                                                <strong>Ref:</strong> 
                                                <code class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs font-mono">
                                                    {{ $sale->transaction_reference }}
                                                </code>
                                            </div>
                                        @endif
                                        @if($sale->status === 'cancelled')
                                            <div class="mt-2 pt-2 border-t border-red-200 dark:border-red-800">
                                                <div class="text-red-600 dark:text-red-400">
                                                    <strong>🚫 Venta Anulada</strong>
                                                </div>
                                                @if($sale->cancelled_at)
                                                    <div class="text-xs"><strong>Cuando:</strong> {{ $sale->cancelled_at->format('d/m/Y H:i:s') }}</div>
                                                @endif
                                                @if($sale->cancellation_reason)
                                                    <div class="text-xs"><strong>Motivo:</strong> {{ $sale->cancellation_reason }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-2">
                                    <div class="text-2xl font-bold 
                                        @if($sale->status === 'cancelled') 
                                            text-red-600 dark:text-red-400 line-through opacity-50
                                        @else 
                                            text-green-600 dark:text-green-400
                                        @endif">
                                        ${{ number_format($sale->total, 2) }}
                                    </div>
                                    @if($sale->status === 'cancelled')
                                        <div class="text-xs px-2 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded">
                                            Reembolsado
                                        </div>
                                    @elseif($sale->payment_method === 'cash' && $sale->change_amount > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Cambio: ${{ number_format($sale->change_amount, 2) }}</div>
                                    @endif
                                    <div class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver detalles
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            @endif
            
            {{-- Tab: Cajas --}}
            @if($historyTab === 'cash_registers')
                @if(empty($todayCashRegisters) || count($todayCashRegisters) === 0)
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay cajas aún</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Las aperturas y cierres de caja aparecerán aquí</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($todayCashRegisters as $register)
                            <div 
                                wire:click="viewCashRegisterDetails({{ $register->id }})"
                                class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer group"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                                {{ $register->register_number }}
                                            </span>
                                            <span class="text-xs px-2 py-1 rounded-full font-semibold
                                                @if($register->status === 'open') 
                                                    bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300
                                                @else 
                                                    bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300
                                                @endif">
                                                @if($register->status === 'open') 
                                                    🟢 Abierta
                                                @else 
                                                    🔒 Cerrada
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Apertura: {{ $register->opened_at->format('H:i') }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                            <div><strong>Cajero:</strong> {{ $register->openedBy->name ?? 'N/A' }}</div>
                                            <div><strong>Fondo Inicial:</strong> ${{ number_format($register->initial_amount, 2) }}</div>
                                            @if($register->status === 'closed')
                                                <div><strong>Cerrada:</strong> {{ $register->closed_at->format('d/m/Y H:i') }}</div>
                                                <div><strong>Cerrada por:</strong> {{ $register->closedBy->name ?? 'N/A' }}</div>
                                                @if($register->difference != 0)
                                                    <div class="mt-2 pt-2 border-t {{ $register->difference > 0 ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800' }}">
                                                        <div class="{{ $register->difference > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            <strong>{{ $register->difference > 0 ? '✅ Sobrante:' : '⚠️ Faltante:' }}</strong>
                                                            ${{ number_format(abs($register->difference), 2) }}
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="mt-2 pt-2 border-t border-green-200 dark:border-green-800">
                                                        <div class="text-green-600 dark:text-green-400">
                                                            <strong>🎯 ¡Cierre exacto!</strong>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end gap-2">
                                        @if($register->status === 'closed')
                                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                                ${{ number_format($register->actual_amount ?? 0, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Esperado: ${{ number_format($register->expected_amount ?? 0, 2) }}
                                            </div>
                                        @else
                                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                                ${{ number_format($register->initial_amount, 2) }}
                                            </div>
                                            <div class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded">
                                                En operación
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-1 text-xs text-purple-600 dark:text-purple-400 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Ver reporte
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4">
            <button 
                @click="$wire.showHistoryModal = false"
                class="w-full px-6 py-3 bg-purple-600 hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-800 text-white font-semibold rounded-xl transition-all"
            >
                Cerrar
            </button>
        </div>
    </div>
</div>
