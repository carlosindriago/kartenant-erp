@php
    $data = $this->getData();
    $subscription = $data['subscription'];
    $warnings = $data['warnings'];
    $hasWarnings = $data['hasWarnings'];
    $needsUpgrade = $data['needsUpgrade'];
@endphp

<x-filament::widget>
    {{-- Critical Warnings (Expired/Suspended) --}}
    @if(!$subscription['active'])
        <div class="p-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg mb-4">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-300">
                        @if($subscription['expired'])
                            ⚠️ Suscripción Expirada
                        @elseif($subscription['suspended'])
                            ⚠️ Cuenta Suspendida
                        @else
                            ⚠️ Sin Suscripción Activa
                        @endif
                    </h3>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-400">
                        @if($subscription['expired'])
                            Tu suscripción ha expirado. Renueva tu plan para continuar disfrutando de todas las funcionalidades del sistema.
                        @elseif($subscription['suspended'])
                            Tu cuenta ha sido suspendida. Por favor contacta a soporte para más información.
                        @else
                            No tienes una suscripción activa. Selecciona un plan para comenzar a usar el sistema.
                        @endif
                    </p>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('filament.app.pages.upgrade-plan', ['tenant' => Filament::getTenant()]) }}"
                           class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Renovar / Actualizar Plan
                        </a>
                        <a href="{{ route('filament.app.pages.subscription-status', ['tenant' => Filament::getTenant()]) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                            Ver Estado Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Warnings (Limits near/reached, trial ending, etc) --}}
    @if($hasWarnings && $subscription['active'])
        <div class="space-y-3 mb-4">
            @foreach($warnings as $warning)
                <div class="p-4 rounded-lg border-l-4
                    {{ $warning['severity'] === 'critical'
                        ? 'bg-red-50 dark:bg-red-900/20 border-red-500'
                        : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            @if($warning['severity'] === 'critical')
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold
                                {{ $warning['severity'] === 'critical'
                                    ? 'text-red-800 dark:text-red-300'
                                    : 'text-yellow-800 dark:text-yellow-300' }}">
                                {{ $warning['message'] }}
                            </p>
                            @if(isset($warning['action']) && isset($warning['action_url']))
                                <a href="{{ $warning['action_url'] }}"
                                   class="mt-2 inline-flex items-center text-sm font-medium
                                       {{ $warning['severity'] === 'critical'
                                           ? 'text-red-700 dark:text-red-400 hover:text-red-900'
                                           : 'text-yellow-700 dark:text-yellow-400 hover:text-yellow-900' }}">
                                    {{ $warning['action'] }}
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Plan Status and Limits (Only if active subscription) --}}
    @if($subscription['active'])
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Plan Info --}}
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Plan Actual</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $data['plan']['name'] ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $data['plan']['billing_cycle'] ?? 'N/A' }}</p>
                    </div>
                </div>
                @if($subscription['on_trial'])
                    <div class="mt-2 inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-medium rounded">
                        🎉 Período de Prueba
                    </div>
                @endif
            </div>

            {{-- Users Limit --}}
            @if(isset($data['users']['limit']))
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Usuarios</p>
                        <span class="text-xs font-semibold
                            {{ $data['users']['percentage'] >= 100 ? 'text-red-600' : ($data['users']['percentage'] >= 80 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $data['users']['percentage'] }}%
                        </span>
                    </div>
                    <div class="mb-2">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all
                                {{ $data['users']['percentage'] >= 100 ? 'bg-red-500' : ($data['users']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($data['users']['percentage'], 100) }}%"></div>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $data['users']['current'] }} / {{ $data['users']['limit'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $data['users']['remaining'] }} disponibles
                    </p>
                </div>
            @endif

            {{-- Products Limit --}}
            @if(isset($data['products']['limit']))
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Productos</p>
                        <span class="text-xs font-semibold
                            {{ $data['products']['percentage'] >= 100 ? 'text-red-600' : ($data['products']['percentage'] >= 80 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $data['products']['percentage'] }}%
                        </span>
                    </div>
                    <div class="mb-2">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all
                                {{ $data['products']['percentage'] >= 100 ? 'bg-red-500' : ($data['products']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($data['products']['percentage'], 100) }}%"></div>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $data['products']['current'] }} / {{ $data['products']['limit'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $data['products']['remaining'] }} disponibles
                    </p>
                </div>
            @endif

            {{-- Sales Limit --}}
            @if(isset($data['sales']['limit']))
                <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ventas (Este Mes)</p>
                        <span class="text-xs font-semibold
                            {{ $data['sales']['percentage'] >= 100 ? 'text-red-600' : ($data['sales']['percentage'] >= 80 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ $data['sales']['percentage'] }}%
                        </span>
                    </div>
                    <div class="mb-2">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all
                                {{ $data['sales']['percentage'] >= 100 ? 'bg-red-500' : ($data['sales']['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ min($data['sales']['percentage'], 100) }}%"></div>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $data['sales']['current'] }} / {{ $data['sales']['limit'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $data['sales']['remaining'] }} disponibles
                    </p>
                </div>
            @endif
        </div>

        {{-- Upgrade Suggestion --}}
        @if($needsUpgrade || count($data['suggestions']) > 0)
            <div class="mt-4 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-purple-900 dark:text-purple-300">
                            💎 Tu negocio está creciendo
                        </h4>
                        <p class="mt-1 text-sm text-purple-700 dark:text-purple-400">
                            {{ $needsUpgrade ? 'Has alcanzado límites importantes de tu plan.' : 'Podrías beneficiarte de un plan superior.' }}
                        </p>
                        @if(count($data['suggestions']) > 0)
                            <ul class="mt-2 space-y-1 text-sm text-purple-600 dark:text-purple-400">
                                @foreach($data['suggestions'] as $suggestion)
                                    <li class="flex items-start gap-2">
                                        <span>•</span>
                                        <span>{{ $suggestion }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('filament.app.pages.upgrade-plan', ['tenant' => Filament::getTenant()]) }}"
                           class="mt-3 inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg transition shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            Ver Planes Disponibles
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament::widget>
