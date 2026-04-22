<x-filament-panels::page>
    @php
        $plans = $this->getAvailablePlans();
        $currentPlan = $this->getCurrentPlan();
        $usage = $this->getCurrentUsage();
    @endphp

    <div class="space-y-8">
        {{-- Current Usage Summary --}}
        @if($currentPlan)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-4">
                    Plan Actual: {{ $currentPlan->name }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    @if(isset($usage['users']['limit']))
                        <div>
                            <span class="text-blue-700 dark:text-blue-400">Usuarios:</span>
                            <span class="font-semibold text-blue-900 dark:text-blue-200">
                                {{ $usage['users']['current'] }}/{{ $usage['users']['limit'] }}
                            </span>
                            <span class="text-xs">({{ $usage['users']['percentage'] }}%)</span>
                        </div>
                    @endif
                    @if(isset($usage['products']['limit']))
                        <div>
                            <span class="text-blue-700 dark:text-blue-400">Productos:</span>
                            <span class="font-semibold text-blue-900 dark:text-blue-200">
                                {{ $usage['products']['current'] }}/{{ $usage['products']['limit'] }}
                            </span>
                            <span class="text-xs">({{ $usage['products']['percentage'] }}%)</span>
                        </div>
                    @endif
                    @if(isset($usage['sales']['limit']))
                        <div>
                            <span class="text-blue-700 dark:text-blue-400">Ventas este mes:</span>
                            <span class="font-semibold text-blue-900 dark:text-blue-200">
                                {{ $usage['sales']['current'] }}/{{ $usage['sales']['limit'] }}
                            </span>
                            <span class="text-xs">({{ $usage['sales']['percentage'] }}%)</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Plans Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
            @foreach($plans as $plan)
                @php
                    $isCurrentPlan = $currentPlan && $currentPlan->id === $plan->id;
                    $isFeatured = $plan->is_featured;
                    $monthlyPrice = (float) $plan->price_monthly;
                    $yearlyPrice = (float) $plan->price_yearly;
                    $yearlySavings = $monthlyPrice > 0 ? round((1 - ($yearlyPrice / ($monthlyPrice * 12))) * 100) : 0;
                @endphp

                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 transition-all hover:scale-105
                    {{ $isFeatured ? 'border-purple-500 dark:border-purple-400' : 'border-gray-200 dark:border-gray-700' }}
                    {{ $isCurrentPlan ? 'ring-4 ring-blue-500 ring-opacity-50' : '' }}">

                    {{-- Featured Badge --}}
                    @if($isFeatured)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center px-4 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-bold rounded-full shadow-lg">
                                ⭐ Más Popular
                            </span>
                        </div>
                    @endif

                    {{-- Current Plan Badge --}}
                    @if($isCurrentPlan)
                        <div class="absolute -top-4 right-4">
                            <span class="inline-flex items-center px-3 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full">
                                Plan Actual
                            </span>
                        </div>
                    @endif

                    <div class="p-6">
                        {{-- Plan Name --}}
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $plan->name }}
                        </h3>

                        {{-- Description --}}
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 h-12 line-clamp-2">
                            {{ $plan->description }}
                        </p>

                        {{-- Pricing --}}
                        <div class="mb-6">
                            {{-- Monthly Price --}}
                            <div class="mb-3">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-bold text-gray-900 dark:text-white">
                                        ${{ number_format($monthlyPrice, 2) }}
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400">/mes</span>
                                </div>
                            </div>

                            {{-- Yearly Price --}}
                            @if($yearlyPrice > 0 && $yearlySavings > 0)
                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <div class="flex items-baseline justify-between">
                                        <div>
                                            <span class="text-lg font-bold text-green-900 dark:text-green-300">
                                                ${{ number_format($yearlyPrice, 2) }}
                                            </span>
                                            <span class="text-sm text-green-700 dark:text-green-400">/año</span>
                                        </div>
                                        <span class="text-xs font-semibold px-2 py-1 bg-green-200 dark:bg-green-900/50 text-green-900 dark:text-green-300 rounded">
                                            Ahorra {{ $yearlySavings }}%
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Trial Info --}}
                        @if($plan->has_trial && $plan->trial_days > 0)
                            <div class="mb-4 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-center">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                    🎉 {{ $plan->trial_days }} días de prueba gratis
                                </span>
                            </div>
                        @endif

                        {{-- Limits --}}
                        <div class="mb-6 space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    {{ $plan->max_users ? "{$plan->max_users} usuarios" : 'Usuarios ilimitados' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    {{ $plan->max_products ? number_format($plan->max_products) . ' productos' : 'Productos ilimitados' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    {{ $plan->max_sales_per_month ? number_format($plan->max_sales_per_month) . ' ventas/mes' : 'Ventas ilimitadas' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    @if($plan->max_storage_mb)
                                        {{ $plan->max_storage_mb >= 1024 ? round($plan->max_storage_mb / 1024, 1) . ' GB' : $plan->max_storage_mb . ' MB' }} almacenamiento
                                    @else
                                        Almacenamiento ilimitado
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Features (First 5) --}}
                        @if(count($plan->features) > 0)
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Características:</h4>
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    @foreach(array_slice($plan->features, 0, 5) as $feature)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="line-clamp-2">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                    @if(count($plan->features) > 5)
                                        <li class="text-xs text-gray-500 dark:text-gray-500 italic">
                                            + {{ count($plan->features) - 5 }} más...
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        {{-- CTA Buttons --}}
                        <div class="space-y-2">
                            @if($isCurrentPlan)
                                <button disabled
                                        class="w-full px-4 py-3 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-semibold rounded-lg cursor-not-allowed">
                                    Plan Actual
                                </button>
                            @else
                                {{-- Monthly --}}
                                <button wire:click="selectPlan({{ $plan->id }}, 'monthly')"
                                        class="w-full px-4 py-3 font-semibold rounded-lg transition shadow-md
                                            {{ $isFeatured
                                                ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white'
                                                : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                                    Seleccionar Mensual
                                </button>

                                {{-- Yearly (if available) --}}
                                @if($yearlyPrice > 0)
                                    <button wire:click="selectPlan({{ $plan->id }}, 'yearly')"
                                            class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-md">
                                        Seleccionar Anual
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Enterprise CTA --}}
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-900 dark:to-black rounded-xl shadow-2xl p-8 text-white">
            <div class="text-center max-w-3xl mx-auto">
                <h3 class="text-3xl font-bold mb-4">
                    ¿Necesitas una solución personalizada?
                </h3>
                <p class="text-lg text-gray-300 mb-6">
                    Para empresas grandes con necesidades específicas, ofrecemos planes empresariales personalizados con soporte dedicado, capacitación y funcionalidades a medida.
                </p>
                <button wire:click="contactSales"
                        class="inline-flex items-center px-8 py-4 bg-white hover:bg-gray-100 text-gray-900 font-bold rounded-lg transition shadow-lg">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contactar a Ventas
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
