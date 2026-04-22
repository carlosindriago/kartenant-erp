<div class="overflow-x-auto">
    @if(!empty($getState()) && count($getState()) > 0)
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">📅 Fecha</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">📦 Cantidad</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">💵 Precio Unitario</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">💰 Total</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">🧾 Venta #</th>
                </tr>
            </thead>
            <tbody>
                @foreach($getState() as $sale)
                    @php
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $saleUrl = route('filament.app.resources.sales.view', [
                            'tenant' => $tenant?->domain,
                            'record' => $sale['sale_id']
                        ]);
                    @endphp
                    <tr 
                        class="border-b border-gray-100 dark:border-gray-800 transition-all duration-150 group cursor-pointer outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                        onclick="window.location.href='{{ $saleUrl }}'"
                        title="Click para ver el resumen completo de la venta"
                    >
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($sale['date'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ number_format($sale['quantity']) }} un.
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">
                            ${{ number_format($sale['unit_price'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($sale['total'], 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                {{ $sale['sale_number'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2 text-sm">No hay ventas registradas para este producto</p>
        </div>
    @endif
</div>
