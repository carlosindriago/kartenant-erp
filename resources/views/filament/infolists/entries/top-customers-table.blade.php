<div class="overflow-x-auto">
    @if(!empty($getState()) && count($getState()) > 0)
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">🏆 #</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">👤 Cliente</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">📦 Cantidad</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">💰 Total Gastado</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">📅 Última Compra</th>
                </tr>
            </thead>
            <tbody>
                @foreach($getState() as $index => $customer)
                    @php
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $saleUrl = $customer['last_sale_id'] 
                            ? route('filament.app.resources.sales.view', [
                                'tenant' => $tenant?->domain,
                                'record' => $customer['last_sale_id']
                            ])
                            : '#';
                    @endphp
                    <tr 
                        class="border-b border-gray-100 dark:border-gray-800 transition-all duration-150 group cursor-pointer outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                        onclick="window.location.href='{{ $saleUrl }}'"
                        title="Click para ver la última venta de este cliente"
                    >
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : ($index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300')) }} font-bold text-sm">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">
                            {{ $customer['customer_name'] }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                {{ number_format($customer['total_quantity']) }} un.
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($customer['total_spent'], 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($customer['last_purchase'])->format('d/m/Y') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="mt-2 text-sm">No hay clientes que hayan comprado este producto</p>
        </div>
    @endif
</div>
