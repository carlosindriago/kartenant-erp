<x-filament-widgets::widget>
    <x-filament::section heading="💵 Estado de Caja" description="Monitoreo en tiempo real">
        @php
            $openRegisters = $this->getCurrentRegisters();
            $lastClosing = $this->getLastClosing();
            $totals = $this->getTotals();
            $userId = auth('tenant')->id();
            $userHasOpenRegister = $userId ? \App\Modules\POS\Models\CashRegister::userHasOpenRegister($userId) : false;
            $userOpenRegister = ($userId && $userHasOpenRegister) ? \App\Modules\POS\Models\CashRegister::getUserOpenRegister($userId) : null;
        @endphp
        
        {{-- Botones de acción rápida --}}
        <div class="flex gap-3 mb-4">
            @if(!$userHasOpenRegister)
                <x-filament::button
                    href="{{ \App\Filament\App\Pages\POS\OpenCashRegisterPage::getUrl() }}"
                    color="success"
                    icon="heroicon-o-lock-open"
                    size="sm"
                >
                    Abrir Mi Caja
                </x-filament::button>
            @else
                <div class="flex items-center gap-3 flex-1">
                    <div class="bg-success-50 dark:bg-success-950/20 rounded-lg px-4 py-2 border border-success-200 dark:border-success-800 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-success-600 dark:text-success-400 font-semibold text-sm">
                                🟢 Tu caja: {{ $userOpenRegister->register_number }}
                            </span>
                            <span class="text-xs text-success-600 dark:text-success-400">
                                · {{ $userOpenRegister->opened_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <x-filament::button
                        href="{{ \App\Filament\App\Pages\POS\CloseCashRegisterPage::getUrl() }}"
                        color="danger"
                        icon="heroicon-o-lock-closed"
                        size="sm"
                    >
                        Cerrar Mi Caja
                    </x-filament::button>
                </div>
            @endif
        </div>
        
        @if(count($openRegisters) > 0)
            {{-- Tabla de cajas abiertas --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">👤 Cajero</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">⏱️ Tiempo</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Inicial</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">💵 Efectivo</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">💳 Tarjeta</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">🏦 Transfer.</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">💰 Total</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">🧾 Trans.</th>
                            <th class="px-3 py-3 text-right font-semibold text-success-700 dark:text-success-400">💵 En Caja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($openRegisters as $register)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">🟢</span>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $register['user_name'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $register['register_number'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $register['time_open'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $register['opened_at']->format('H:i') }}</div>
                                </td>
                                <td class="px-3 py-4 text-right font-medium text-gray-900 dark:text-white">${{ number_format($register['initial_amount'], 0) }}</td>
                                <td class="px-3 py-4 text-right font-medium text-gray-600 dark:text-gray-400">${{ number_format($register['cash_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right font-medium text-gray-600 dark:text-gray-400">${{ number_format($register['card_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right font-medium text-gray-600 dark:text-gray-400">${{ number_format($register['transfer_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right font-bold text-gray-900 dark:text-white">${{ number_format($register['total_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $register['transaction_count'] }}</div>
                                    @if($register['returns_count'] > 0)
                                        <div class="text-xs text-danger-600">-{{ $register['returns_count'] }} dev.</div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-right bg-success-50 dark:bg-success-950/20">
                                    <div class="font-bold text-xl text-success-700 dark:text-success-400">${{ number_format($register['expected_cash'], 0) }}</div>
                                    @if($register['average_ticket'] > 0)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Ticket: ${{ number_format($register['average_ticket'], 0) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Fila de totales --}}
                        @if(count($openRegisters) > 1)
                            <tr class="bg-gray-100 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                                <td class="px-3 py-4 text-gray-900 dark:text-white" colspan="2">
                                    📊 TOTAL GENERAL ({{ count($openRegisters) }} cajas)
                                </td>
                                <td class="px-3 py-4 text-right text-gray-900 dark:text-white">${{ number_format($totals['initial_amount'], 0) }}</td>
                                <td class="px-3 py-4 text-right text-gray-700 dark:text-gray-300">${{ number_format($totals['cash_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right text-gray-700 dark:text-gray-300">${{ number_format($totals['card_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right text-gray-700 dark:text-gray-300">${{ number_format($totals['transfer_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right text-gray-900 dark:text-white text-lg">${{ number_format($totals['total_sales'], 0) }}</td>
                                <td class="px-3 py-4 text-right text-gray-900 dark:text-white">{{ $totals['transaction_count'] }}</td>
                                <td class="px-3 py-4 text-right bg-success-100 dark:bg-success-950/30 text-success-700 dark:text-success-400 text-xl">${{ number_format($totals['expected_cash'], 0) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Métricas adicionales --}}
            @if(count($openRegisters) > 1)
                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Total Transacciones</div>
                        <div class="font-bold text-gray-900 dark:text-white">{{ $totals['transaction_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Ticket Promedio Global</div>
                        <div class="font-bold text-gray-900 dark:text-white">${{ number_format($totals['average_ticket'], 0) }}</div>
                    </div>
                    @if($totals['returns_count'] > 0)
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Total Devoluciones</div>
                            <div class="font-bold text-danger-600">{{ $totals['returns_count'] }} · ${{ number_format($totals['returns'], 0) }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Cajas Operando</div>
                        <div class="font-bold text-success-600">{{ count($openRegisters) }} activas</div>
                    </div>
                </div>
            @endif

        @elseif($lastClosing)
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4 text-center">
                <span class="text-3xl">🔴</span>
                <p class="mt-2 font-semibold">Caja Cerrada</p>
                <p class="text-sm text-gray-600">Último cierre: {{ \Carbon\Carbon::parse($lastClosing['closed_at'])->diffForHumans() }}</p>
                <a href="/pos" class="mt-3 inline-flex items-center gap-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-md">
                    Abrir Caja
                </a>
            </div>
        @else
            <div class="py-8 text-center">
                <p class="text-gray-500">Sin cajas registradas</p>
                <a href="/pos" class="mt-3 inline-flex px-4 py-2 bg-primary-600 text-white rounded-md">Ir al POS</a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
