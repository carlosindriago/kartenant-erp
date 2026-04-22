<div class="fi-wi-widget">
    @php
        $lowStockProducts = $this->getLowStockProducts();
        $outOfStockProducts = $this->getOutOfStockProducts();
    @endphp

    @if($lowStockProducts->count() > 0 || $outOfStockProducts->count() > 0)
        <div class="fi-wi-widget-content">
            <!-- Productos sin stock -->
            @if($outOfStockProducts->count() > 0)
                <div class="mb-6 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">¡Productos sin stock!</h3>
                    </div>
                    <p class="text-red-700 dark:text-red-300 mb-3">Estos productos se han agotado y necesitas reponerlos:</p>
                    <div class="space-y-2">
                        @foreach($outOfStockProducts as $product)
                            <div class="flex justify-between items-center bg-white dark:bg-gray-800 rounded p-2 border border-red-200 dark:border-red-800/50">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $product->name }}</span>
                                <span class="text-sm text-red-600 dark:text-red-400 font-semibold">SIN STOCK</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Productos con stock bajo -->
            @if($lowStockProducts->count() > 0)
                <div class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Stock bajo - ¡Hora de reponer!</h3>
                    </div>
                    <p class="text-yellow-700 dark:text-yellow-300 mb-3">Estos productos están llegando al límite mínimo:</p>
                    <div class="space-y-2">
                        @foreach($lowStockProducts as $product)
                            <div class="flex justify-between items-center bg-white dark:bg-gray-800 rounded p-2 border border-yellow-200 dark:border-yellow-800/50">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $product->name }}</span>
                                <div class="text-right">
                                    <span class="text-sm text-yellow-600 dark:text-yellow-400">Quedan: <strong>{{ $product->stock }}</strong></span>
                                    <br>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Mínimo: {{ $product->min_stock }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
