{{-- Modal de Reporte Diario --}}
<div x-show="$wire.showDailyReportModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm"
     @click.self="$wire.showDailyReportModal = false">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full mx-4 overflow-hidden transform transition-all max-h-[90vh] overflow-y-auto"
         @click.stop>
        {{-- Header --}}
        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 px-5 py-4 sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Reporte de Ingresos del Día
                </h3>
                <button @click="$wire.showDailyReportModal = false" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-5">
            @if(!empty($dailyReport))
            {{-- Información de la Caja --}}
            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">📋 Información de la Caja</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 mb-1">Número de Caja</div>
                        <div class="font-bold text-gray-800 dark:text-gray-100">{{ $dailyReport['register_info']['register_number'] ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 mb-1">Apertura</div>
                        <div class="font-bold text-gray-800 dark:text-gray-100">
                            {{ isset($dailyReport['register_info']['opened_at']) ? $dailyReport['register_info']['opened_at']->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 mb-1">Cierre</div>
                        <div class="font-bold text-gray-800 dark:text-gray-100">
                            {{ isset($dailyReport['register_info']['closed_at']) ? $dailyReport['register_info']['closed_at']->format('d/m/Y H:i') : 'Abierta' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400 mb-1">Estado</div>
                        <div>
                            @if($dailyReport['register_info']['status'] === 'closed')
                                <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs font-semibold rounded-lg">🔒 Cerrada</span>
                            @else
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 text-xs font-semibold rounded-lg">🔓 Abierta</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Flujo de Efectivo --}}
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    💵 Flujo de Efectivo
                </h4>
                
                {{-- Total Esperado en Efectivo - DESTACADO --}}
                @if(isset($dailyReport['cash_flow']['expected_cash_total']))
                <div class="mb-4 bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-emerald-300 dark:border-emerald-800 rounded-2xl p-5 border-4 shadow-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-white dark:bg-emerald-800 rounded-full p-3">
                                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-emerald-100">💰 Total Esperado en Efectivo</div>
                                <div class="text-xs text-emerald-200">Monto que deberías tener en caja al contar</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-4xl font-black text-white drop-shadow-lg">
                                ${{ number_format($dailyReport['cash_flow']['expected_cash_total'], 0) }}
                            </div>
                            <div class="text-xs text-emerald-100 mt-1">
                                (Inicial + Ventas Completadas)
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    {{-- Fondo Inicial --}}
                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Fondo Inicial</div>
                        <div class="text-xl font-bold text-blue-700 dark:text-blue-300">
                            ${{ number_format($dailyReport['cash_flow']['initial_amount'] ?? 0, 2) }}
                        </div>
                    </div>

                    {{-- Ventas Efectivo --}}
                    <div class="bg-green-50 dark:bg-green-900 dark:bg-opacity-30 rounded-xl p-4 border border-green-200 dark:border-green-700">
                        <div class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">+ Ventas Efectivo</div>
                        <div class="text-xl font-bold text-green-700 dark:text-green-300">
                            ${{ number_format($dailyReport['cash_flow']['cash_sales'] ?? 0, 2) }}
                        </div>
                    </div>

                    {{-- Esperado --}}
                    <div class="bg-purple-50 dark:bg-purple-900 dark:bg-opacity-30 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
                        <div class="text-xs text-purple-600 dark:text-purple-400 font-medium mb-1">= Total Esperado</div>
                        <div class="text-xl font-bold text-purple-700 dark:text-purple-300">
                            @if(($dailyReport['register_info']['status'] ?? '') === 'closed')
                                ${{ number_format($dailyReport['cash_flow']['expected_amount'] ?? 0, 2) }}
                            @else
                                <span class="text-sm font-normal text-purple-500 dark:text-purple-400 flex items-center gap-1">
                                    <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Al cerrar
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Real --}}
                    <div class="bg-indigo-50 dark:bg-indigo-900 dark:bg-opacity-30 rounded-xl p-4 border border-indigo-200 dark:border-indigo-700">
                        <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mb-1">💰 Contado Real</div>
                        <div class="text-xl font-bold text-indigo-700 dark:text-indigo-300">
                            @if(($dailyReport['register_info']['status'] ?? '') === 'closed')
                                ${{ number_format($dailyReport['cash_flow']['actual_amount'] ?? 0, 2) }}
                            @else
                                <span class="text-sm font-normal text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                                    <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Al cerrar
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Ventas Canceladas (solo referencia) --}}
                @if(($dailyReport['cash_flow']['cash_returns'] ?? 0) > 0)
                <div class="mt-3 bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Ventas Canceladas (dinero devuelto, no está en caja):</span>
                        </div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            ${{ number_format($dailyReport['cash_flow']['cash_returns'], 2) }}
                        </span>
                    </div>
                </div>
                @endif

                {{-- Diferencia --}}
                @php 
                    $isClosed = ($dailyReport['register_info']['status'] ?? '') === 'closed';
                    $difference = $dailyReport['cash_flow']['difference'] ?? 0;
                @endphp
                
                @if($isClosed && isset($dailyReport['cash_flow']['difference']))
                <div class="mt-3 rounded-xl p-4 border-2 @if($difference > 0) bg-green-50 dark:bg-green-900 dark:bg-opacity-30 border-green-300 dark:border-green-600 @elseif($difference < 0) bg-red-50 dark:bg-red-900 dark:bg-opacity-30 border-red-300 dark:border-red-600 @else bg-emerald-50 dark:bg-emerald-900 dark:bg-opacity-30 border-emerald-300 dark:border-emerald-600 @endif">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold @if($difference > 0) text-green-600 dark:text-green-400 @elseif($difference < 0) text-red-600 dark:text-red-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                            @if($difference > 0)
                                ✅ Sobrante
                            @elseif($difference < 0)
                                ⚠️ Faltante
                            @else
                                🎯 ¡Exacto!
                            @endif
                        </div>
                        <div class="text-2xl font-bold @if($difference > 0) text-green-700 dark:text-green-300 @elseif($difference < 0) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif">
                            {{ $difference >= 0 ? '+' : '' }}${{ number_format(abs($difference), 2) }}
                        </div>
                    </div>
                </div>
                @else
                {{-- Caja abierta: Mostrar estado de espera --}}
                <div class="mt-3 rounded-xl p-4 border-2 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                    <div class="flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">⏳ Diferencia se calculará al cerrar la caja</span>
                    </div>
                </div>
                @endif
            </div>

            {{-- Resumen de Ventas --}}
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    📊 Resumen de Ventas
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="bg-white dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total de Ventas</div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            {{ $dailyReport['sales_summary']['total_sales'] ?? 0 }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Monto Total</div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                            ${{ number_format($dailyReport['sales_summary']['total_amount'] ?? 0, 2) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Ventas Anuladas</div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ $dailyReport['sales_summary']['cancelled_sales'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Métodos de Pago --}}
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    💳 Desglose por Método de Pago
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-green-50 dark:bg-green-900 dark:bg-opacity-30 rounded-xl p-4 border border-green-200 dark:border-green-700">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-green-100 dark:bg-green-800 rounded-lg">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-green-600 dark:text-green-400 font-medium">Efectivo</div>
                                <div class="text-xl font-bold text-green-700 dark:text-green-300">
                                    ${{ number_format($dailyReport['payment_methods']['cash'] ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Tarjeta</div>
                                <div class="text-xl font-bold text-blue-700 dark:text-blue-300">
                                    ${{ number_format($dailyReport['payment_methods']['card'] ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900 dark:bg-opacity-30 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-purple-100 dark:bg-purple-800 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-purple-600 dark:text-purple-400 font-medium">Transferencia</div>
                                <div class="text-xl font-bold text-purple-700 dark:text-purple-300">
                                    ${{ number_format($dailyReport['payment_methods']['transfer'] ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Observaciones --}}
            @if(!empty($dailyReport['notes']['opening']) || !empty($dailyReport['notes']['closing']))
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">📝 Observaciones</h4>
                <div class="space-y-2">
                    @if(!empty($dailyReport['notes']['opening']))
                    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-30 rounded-lg p-3 border border-blue-200 dark:border-blue-700">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold mb-1">Apertura:</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $dailyReport['notes']['opening'] }}</div>
                    </div>
                    @endif
                    @if(!empty($dailyReport['notes']['closing']))
                    <div class="bg-red-50 dark:bg-red-900 dark:bg-opacity-30 rounded-lg p-3 border border-red-200 dark:border-red-700">
                        <div class="text-xs text-red-600 dark:text-red-400 font-semibold mb-1">Cierre:</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $dailyReport['notes']['closing'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Detalle de Transacciones --}}
            @if(!empty($dailyReport['transactions']) && count($dailyReport['transactions']) > 0)
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    📋 Detalle de Transacciones ({{ count($dailyReport['transactions']) }})
                </h4>
                <div class="bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-600 sticky top-0">
                                <tr class="border-b border-gray-200 dark:border-gray-500">
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Factura</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Hora</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Cliente</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Pago</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">Monto</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach($dailyReport['transactions'] as $index => $transaction)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors {{ $transaction['is_cancelled'] ? 'bg-red-50 dark:bg-red-900 dark:bg-opacity-20' : '' }}">
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-semibold text-blue-600 dark:text-blue-400 {{ $transaction['is_cancelled'] ? 'line-through' : '' }}">
                                            #{{ $transaction['invoice_number'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400 text-xs">{{ $transaction['time'] }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">{{ $transaction['customer'] }}</td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                            {{ ucfirst($transaction['payment_method']) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold {{ $transaction['is_cancelled'] ? 'line-through text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">
                                        ${{ number_format($transaction['total'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if($transaction['is_cancelled'])
                                            <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-full text-xs font-semibold">
                                                ❌ Anulada
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-full text-xs font-semibold">
                                                ✓ Completada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <a 
                                            href="{{ route('filament.app.resources.sales.view', ['tenant' => \Spatie\Multitenancy\Models\Tenant::current(), 'record' => $transaction['id']]) }}" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-800 rounded text-xs font-medium transition-colors"
                                            title="Ver detalle de venta"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-600 border-t-2 border-gray-300 dark:border-gray-500 sticky bottom-0">
                                <tr>
                                    <td colspan="5" class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300">
                                        Total Transacciones:
                                    </td>
                                    <td colspan="3" class="px-3 py-2 text-center font-bold text-gray-800 dark:text-gray-200">
                                        {{ count($dailyReport['transactions']) }}
                                        <span class="text-xs font-normal text-gray-600 dark:text-gray-400">
                                            ({{ count(array_filter($dailyReport['transactions'], fn($t) => !$t['is_cancelled'])) }} completadas, 
                                            {{ count(array_filter($dailyReport['transactions'], fn($t) => $t['is_cancelled'])) }} anuladas)
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Código de Seguridad del Reporte --}}
            @if(!empty($dailyReport['security']['verification_hash']))
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-gray-700 dark:to-gray-600 rounded-xl p-4 border-2 border-amber-200 dark:border-amber-700">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    🔐 Código de Seguridad del Reporte
                </h4>
                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1 font-medium">Hash de Verificación (SHA-256)</div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-300 dark:border-amber-600">
                            <code class="text-xs font-mono text-gray-800 dark:text-gray-200 break-all select-all">
                                {{ $dailyReport['security']['verification_hash'] }}
                            </code>
                        </div>
                    </div>
                    @if(!empty($dailyReport['security']['verification_generated_at']))
                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Generado: {{ \Carbon\Carbon::parse($dailyReport['security']['verification_generated_at'])->format('d/m/Y H:i:s') }}
                    </div>
                    @endif
                    <div class="flex items-start gap-2 bg-amber-100 dark:bg-amber-900 dark:bg-opacity-30 rounded-lg p-3 text-xs text-amber-800 dark:text-amber-200">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <strong class="font-semibold">Código único e inmutable:</strong> Este hash identifica de forma permanente este reporte de caja. 
                            Se genera automáticamente al cerrar la caja y nunca cambia, garantizando transparencia y trazabilidad completa.
                            El mismo código aparece en los PDFs generados.
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                No hay datos de reporte disponibles
            </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-4 space-y-3 sticky bottom-0">
            <div class="text-center text-xs text-gray-500 dark:text-gray-400 font-medium">
                📄 Selecciona el formato de exportación
            </div>
            <div class="grid grid-cols-3 gap-3">
                <button 
                    @click="$wire.showDailyReportModal = false"
                    class="px-4 py-3 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors border border-gray-300 dark:border-gray-500"
                >
                    Cerrar
                </button>
                <button 
                    wire:click="exportDailyReportPdf('thermal')"
                    class="px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex flex-col items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportDailyReportPdf('thermal')">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading wire:target="exportDailyReportPdf('thermal')">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="text-xs" wire:loading.remove wire:target="exportDailyReportPdf('thermal')">📃 Ticket 80mm</span>
                    <span class="text-xs" wire:loading wire:target="exportDailyReportPdf('thermal')">Generando...</span>
                </button>
                <button 
                    wire:click="exportDailyReportPdf('a4')"
                    class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl flex flex-col items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportDailyReportPdf('a4')">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading wire:target="exportDailyReportPdf('a4')">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="text-xs" wire:loading.remove wire:target="exportDailyReportPdf('a4')">📄 Hoja A4</span>
                    <span class="text-xs" wire:loading wire:target="exportDailyReportPdf('a4')">Generando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
