<x-filament-panels::page>
    @php
        $data = $this->getData();
        $limits = $data['limits'];
        $subscription = $limits['subscription'];
        $plan = $limits['plan'];
    @endphp

    <div class="space-y-6">
        {{-- Subscription Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Información de Suscripción
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Plan Info --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Plan Actual</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $plan['name'] ?? 'Sin Plan' }}
                    </p>
                    @if($plan['billing_cycle'])
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 capitalize">
                            Facturación {{ $plan['billing_cycle'] === 'monthly' ? 'Mensual' : 'Anual' }}
                        </p>
                    @endif
                </div>

                {{-- Status --}}
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Estado</p>
                    <div class="flex items-center gap-2">
                        @if($subscription['active'])
                            <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-medium rounded-full">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Activo
                            </span>
                        @elseif($subscription['expired'])
                            <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm font-medium rounded-full">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Expirado
                            </span>
                        @elseif($subscription['suspended'])
                            <span class="inline-flex items-center px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 text-sm font-medium rounded-full">
                                Suspendido
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300 text-sm font-medium rounded-full">
                                Sin Plan
                            </span>
                        @endif

                        @if($subscription['on_trial'])
                            <span class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-medium rounded-full">
                                🎉 Trial
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Expiration --}}
                @if($subscription['active'] && isset($subscription['days_until_expiration']))
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            {{ $subscription['on_trial'] ? 'Trial Termina En' : 'Renovación En' }}
                        </p>
                        <p class="text-2xl font-bold
                            {{ $subscription['days_until_expiration'] <= 3 ? 'text-red-600 dark:text-red-400' : ($subscription['days_until_expiration'] <= 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white') }}">
                            {{ $subscription['days_until_expiration'] }} días
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Limits Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Límites del Plan
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Users --}}
                @if(isset($limits['users']['limit']))
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Usuarios</h4>
                            <span class="text-xs font-semibold px-2 py-1 rounded
                                {{ $limits['users']['percentage'] >= 100 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($limits['users']['percentage'] >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300') }}">
                                {{ $limits['users']['percentage'] }}%
                            </span>
                        </div>
                        <div class="mb-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all
                                    {{ $limits['users']['percentage'] >= 100 ? 'bg-red-500' : ($limits['users']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($limits['users']['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $limits['users']['current'] }}</span> de {{ $limits['users']['limit'] }} en uso
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $limits['users']['remaining'] }} disponibles
                        </p>
                    </div>
                @else
                    <div class="flex items-center justify-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="font-semibold text-green-800 dark:text-green-300">Usuarios Ilimitados</p>
                        </div>
                    </div>
                @endif

                {{-- Products --}}
                @if(isset($limits['products']['limit']))
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Productos</h4>
                            <span class="text-xs font-semibold px-2 py-1 rounded
                                {{ $limits['products']['percentage'] >= 100 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($limits['products']['percentage'] >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300') }}">
                                {{ $limits['products']['percentage'] }}%
                            </span>
                        </div>
                        <div class="mb-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all
                                    {{ $limits['products']['percentage'] >= 100 ? 'bg-red-500' : ($limits['products']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($limits['products']['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $limits['products']['current'] }}</span> de {{ $limits['products']['limit'] }} en uso
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $limits['products']['remaining'] }} disponibles
                        </p>
                    </div>
                @else
                    <div class="flex items-center justify-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="font-semibold text-green-800 dark:text-green-300">Productos Ilimitados</p>
                        </div>
                    </div>
                @endif

                {{-- Sales --}}
                @if(isset($limits['sales']['limit']))
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Ventas (Este Mes)</h4>
                            <span class="text-xs font-semibold px-2 py-1 rounded
                                {{ $limits['sales']['percentage'] >= 100 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : ($limits['sales']['percentage'] >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300') }}">
                                {{ $limits['sales']['percentage'] }}%
                            </span>
                        </div>
                        <div class="mb-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all
                                    {{ $limits['sales']['percentage'] >= 100 ? 'bg-red-500' : ($limits['sales']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min($limits['sales']['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $limits['sales']['current'] }}</span> de {{ $limits['sales']['limit'] }} en uso
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $limits['sales']['remaining'] }} disponibles
                        </p>
                    </div>
                @else
                    <div class="flex items-center justify-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="text-center">
                            <svg class="w-8 h-8 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="font-semibold text-green-800 dark:text-green-300">Ventas Ilimitadas</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Upgrade CTA --}}
        @if($data['needsUpgrade'] || count($data['suggestions']) > 0)
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold mb-2">
                            ¡Es hora de crecer! 🚀
                        </h3>
                        <p class="text-white/90 mb-4">
                            {{ $data['needsUpgrade'] ? 'Has alcanzado límites importantes de tu plan actual.' : 'Tu negocio podría beneficiarse de más capacidad.' }}
                        </p>
                        @if(count($data['suggestions']) > 0)
                            <ul class="space-y-2 mb-4">
                                @foreach($data['suggestions'] as $suggestion)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $suggestion }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ \App\Filament\App\Pages\UpgradePlan::getUrl(tenant: \Filament\Facades\Filament::getTenant()) }}"
                           class="inline-flex items-center px-6 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-gray-100 transition shadow-md">
                            Ver Planes Disponibles
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
