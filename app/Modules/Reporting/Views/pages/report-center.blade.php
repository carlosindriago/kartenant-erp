<x-filament-panels::page>
    {{-- Report Center View --}}

    <div class="space-y-6">
        {{-- Form with Tabs --}}
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        {{-- Report Content based on Active Tab --}}
        <div class="mt-6">
            @if ($activeTab === 'inventory_value' && $inventoryValueData)
                {{-- Inventory Value Report --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Resumen de Valor de Inventario
                    </x-slot>

                    <x-slot name="description">
                        Valor total del inventario actual y tendencias históricas
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Total Value Card --}}
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">Valor Total</div>
                            <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                                ${{ number_format($inventoryValueData['summary']['total_value'], 2) }}
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                @if ($inventoryValueData['summary']['trend_7_days'] >= 0)
                                    ↑ {{ number_format($inventoryValueData['summary']['trend_7_days'], 1) }}% vs semana anterior
                                @else
                                    ↓ {{ number_format(abs($inventoryValueData['summary']['trend_7_days']), 1) }}% vs semana anterior
                                @endif
                            </div>
                        </div>

                        {{-- Total Products Card --}}
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">Productos</div>
                            <div class="text-3xl font-bold text-green-900 dark:text-green-100">
                                {{ number_format($inventoryValueData['summary']['product_count']) }}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-2">
                                {{ number_format($inventoryValueData['summary']['active_products']) }} activos
                            </div>
                        </div>

                        {{-- Potential Profit Card --}}
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-6 border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-600 dark:text-purple-400 mb-1">Ganancia Potencial</div>
                            <div class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                                ${{ number_format($inventoryValueData['summary']['potential_profit'], 2) }}
                            </div>
                            <div class="text-xs text-purple-600 dark:text-purple-400 mt-2">
                                Margen {{ number_format($inventoryValueData['summary']['profit_margin'], 1) }}%
                            </div>
                        </div>

                        {{-- Total Units Card --}}
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg p-6 border border-orange-200 dark:border-orange-800">
                            <div class="text-sm font-medium text-orange-600 dark:text-orange-400 mb-1">Unidades Totales</div>
                            <div class="text-3xl font-bold text-orange-900 dark:text-orange-100">
                                {{ number_format($inventoryValueData['summary']['total_units']) }}
                            </div>
                            <div class="text-xs text-orange-600 dark:text-orange-400 mt-2">
                                En stock actual
                            </div>
                        </div>
                    </div>

                    {{-- Value by Category Table --}}
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">Valor por Categoría</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Categoría</th>
                                        <th class="px-4 py-2 text-right">Productos</th>
                                        <th class="px-4 py-2 text-right">Unidades</th>
                                        <th class="px-4 py-2 text-right">Valor Total</th>
                                        <th class="px-4 py-2 text-right">Margen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventoryValueData['by_category'] as $category)
                                        <tr class="border-t dark:border-gray-700">
                                            <td class="px-4 py-2">{{ $category->category_name }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($category->product_count) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($category->total_units) }}</td>
                                            <td class="px-4 py-2 text-right font-semibold">${{ number_format($category->total_value, 2) }}</td>
                                            <td class="px-4 py-2 text-right">{{ number_format($category->profit_margin, 1) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-filament::section>

            @elseif ($activeTab === 'abc_analysis' && $abcAnalysisData)
                {{-- ABC Analysis Report --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Análisis ABC (Pareto 80/20)
                    </x-slot>

                    <x-slot name="description">
                        Clasificación de productos por importancia en ingresos
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Class A Card --}}
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">Clase A - Alta Prioridad</div>
                            <div class="text-3xl font-bold text-green-900 dark:text-green-100">
                                {{ $abcAnalysisData['distribution']['class_a']['count'] }} productos
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-2">
                                {{ number_format($abcAnalysisData['distribution']['class_a']['revenue_percentage'], 1) }}% de los ingresos
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400">
                                ({{ number_format($abcAnalysisData['distribution']['class_a']['percentage'], 1) }}% de productos)
                            </div>
                        </div>

                        {{-- Class B Card --}}
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-lg p-6 border border-yellow-200 dark:border-yellow-800">
                            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-1">Clase B - Media Prioridad</div>
                            <div class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">
                                {{ $abcAnalysisData['distribution']['class_b']['count'] }} productos
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-2">
                                {{ number_format($abcAnalysisData['distribution']['class_b']['revenue_percentage'], 1) }}% de los ingresos
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400">
                                ({{ number_format($abcAnalysisData['distribution']['class_b']['percentage'], 1) }}% de productos)
                            </div>
                        </div>

                        {{-- Class C Card --}}
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                            <div class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">Clase C - Baja Prioridad</div>
                            <div class="text-3xl font-bold text-red-900 dark:text-red-100">
                                {{ $abcAnalysisData['distribution']['class_c']['count'] }} productos
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400 mt-2">
                                {{ number_format($abcAnalysisData['distribution']['class_c']['revenue_percentage'], 1) }}% de los ingresos
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400">
                                ({{ number_format($abcAnalysisData['distribution']['class_c']['percentage'], 1) }}% de productos)
                            </div>
                        </div>
                    </div>

                    {{-- Recommendations --}}
                    @if (count($abcAnalysisData['recommendations']) > 0)
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold mb-4">Recomendaciones</h3>
                            <div class="space-y-3">
                                @foreach ($abcAnalysisData['recommendations'] as $recommendation)
                                    <div class="p-4 rounded-lg border
                                        @if ($recommendation['priority'] === 'high')
                                            bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800
                                        @elseif ($recommendation['priority'] === 'medium')
                                            bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
                                        @else
                                            bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800
                                        @endif
                                    ">
                                        <div class="font-semibold text-sm mb-1">{{ $recommendation['category'] }}</div>
                                        <div class="text-sm">{{ $recommendation['message'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-filament::section>

            @elseif ($activeTab === 'profitability' && $profitabilityData)
                {{-- Profitability Report --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Análisis de Rentabilidad
                    </x-slot>

                    <x-slot name="description">
                        Productos más y menos rentables del negocio
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">Ganancia Total</div>
                            <div class="text-3xl font-bold text-green-900 dark:text-green-100">
                                ${{ number_format($profitabilityData['summary']['total_profit'], 2) }}
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">Margen Promedio</div>
                            <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                                {{ number_format($profitabilityData['summary']['overall_margin'], 1) }}%
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-6 border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-600 dark:text-purple-400 mb-1">Productos Rentables</div>
                            <div class="text-3xl font-bold text-purple-900 dark:text-purple-100">
                                {{ $profitabilityData['summary']['profitable_products'] }}
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                            <div class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">Productos No Rentables</div>
                            <div class="text-3xl font-bold text-red-900 dark:text-red-100">
                                {{ $profitabilityData['summary']['unprofitable_products'] }}
                            </div>
                        </div>
                    </div>

                    {{-- Top Profitable Products --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">Top Productos Más Rentables</h3>
                            <div class="space-y-2">
                                @foreach ($profitabilityData['most_profitable']->take(10) as $product)
                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold">{{ $product->name }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">SKU: {{ $product->sku }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-green-600 dark:text-green-400">
                                                    ${{ number_format($product->profit, 2) }}
                                                </div>
                                                <div class="text-xs">{{ number_format($product->profit_margin, 1) }}% margen</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold mb-4 text-red-600 dark:text-red-400">Top Productos Menos Rentables</h3>
                            <div class="space-y-2">
                                @foreach ($profitabilityData['least_profitable']->take(10) as $product)
                                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold">{{ $product->name }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">SKU: {{ $product->sku }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-red-600 dark:text-red-400">
                                                    ${{ number_format($product->profit, 2) }}
                                                </div>
                                                <div class="text-xs">{{ number_format($product->profit_margin, 1) }}% margen</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-filament::section>

            @elseif ($activeTab === 'turnover' && $turnoverData)
                {{-- Turnover Report --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Análisis de Rotación de Inventario
                    </x-slot>

                    <x-slot name="description">
                        Velocidad de rotación y días de inventario
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">Alta Rotación</div>
                            <div class="text-3xl font-bold text-green-900 dark:text-green-100">
                                {{ $turnoverData['summary']['fast_movers'] }}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-2">productos</div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">Rotación Normal</div>
                            <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                                {{ $turnoverData['summary']['normal_movers'] }}
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-2">productos</div>
                        </div>

                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-lg p-6 border border-yellow-200 dark:border-yellow-800">
                            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-1">Baja Rotación</div>
                            <div class="text-3xl font-bold text-yellow-900 dark:text-yellow-100">
                                {{ $turnoverData['summary']['slow_movers'] }}
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-2">productos</div>
                        </div>

                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                            <div class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">Estancados</div>
                            <div class="text-3xl font-bold text-red-900 dark:text-red-100">
                                {{ $turnoverData['summary']['stagnant'] }}
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400 mt-2">sin ventas</div>
                        </div>
                    </div>

                    {{-- Fast and Slow Movers --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">Productos de Alta Rotación</h3>
                            <div class="space-y-2">
                                @foreach ($turnoverData['fast_movers']->take(10) as $item)
                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold">{{ $item['product']->name }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $item['turnover_data']['units_sold'] }} unidades vendidas</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-green-600 dark:text-green-400">
                                                    {{ number_format($item['turnover_data']['turnover_rate'], 2) }}x
                                                </div>
                                                <div class="text-xs">{{ number_format($item['turnover_data']['days_sales_of_inventory'], 0) }} días DSI</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold mb-4 text-yellow-600 dark:text-yellow-400">Productos de Baja Rotación</h3>
                            <div class="space-y-2">
                                @foreach ($turnoverData['slow_movers']->take(10) as $item)
                                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold">{{ $item['product']->name }}</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $item['turnover_data']['units_sold'] }} unidades vendidas</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-yellow-600 dark:text-yellow-400">
                                                    {{ number_format($item['turnover_data']['turnover_rate'], 2) }}x
                                                </div>
                                                <div class="text-xs">{{ $item['turnover_data']['stock_status'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-filament::section>

            @elseif ($activeTab === 'comparatives')
                {{-- Comparatives Report --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Comparativas de Períodos
                    </x-slot>

                    <x-slot name="description">
                        Comparación de métricas entre dos períodos de tiempo
                    </x-slot>

                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-lg">Selecciona las fechas y haz clic en "Actualizar Datos" para ver la comparativa</p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
