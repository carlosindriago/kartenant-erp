<div
    x-data="{ open: @entangle('showDropdown') }"
    @click.away="open = false"
    class="relative"
>
    {{-- Botón de notificaciones --}}
    <button
        @click="open = !open"
        type="button"
        class="relative inline-flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200
               hover:bg-gray-100 dark:hover:bg-gray-800
               focus:outline-none focus:ring-2 focus:ring-primary-500"
        aria-label="Notificaciones de alertas críticas"
    >
        {{-- Icono de campana - Amarillo cuando hay notificaciones --}}
        <svg class="w-6 h-6 transition-colors duration-200
                    @if($this->count > 0)
                        text-yellow-500 dark:text-yellow-400
                    @else
                        text-gray-600 dark:text-gray-300
                    @endif"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        {{-- Badge con contador - Rojo oscuro en modo claro, rojo claro en modo oscuro --}}
        @if($this->count > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white
                         @if($this->criticalCount > 0)
                             bg-red-700 dark:bg-red-400 animate-pulse
                         @else
                             bg-red-600 dark:bg-red-500
                         @endif
                         rounded-full border-2 border-white dark:border-gray-900">
                {{ $this->count > 9 ? '9+' : $this->count }}
            </span>
        @endif
    </button>

    {{-- Dropdown de notificaciones --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 max-h-[32rem] overflow-y-auto
               bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    🚨 Alertas Críticas
                </h3>
                <button
                    wire:click="refreshAlerts"
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex items-center gap-1"
                    type="button"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualizar
                </button>
            </div>

            {{-- Estadísticas --}}
            @if($this->count > 0)
                <div class="flex items-center gap-2 mt-2">
                    @if($this->criticalCount > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-white bg-danger-600 rounded-full">
                            🔴 {{ $this->criticalCount }} críticas
                        </span>
                    @endif
                    @if($this->highCount > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-white bg-warning-600 rounded-full">
                            🟡 {{ $this->highCount }} importantes
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Lista de alertas --}}
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->alerts as $alert)
                <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    {{-- Contenido de la alerta --}}
                    <div class="flex items-start gap-3">
                        <span class="text-2xl flex-shrink-0">{{ $alert['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $alert['title'] }}
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                {{ $alert['description'] }}
                            </p>

                            {{-- Impacto --}}
                            @if(isset($alert['impact']))
                                <p class="text-xs font-medium mt-1
                                    @if($alert['priority'] === 'critical')
                                        text-danger-600 dark:text-danger-400
                                    @else
                                        text-warning-600 dark:text-warning-400
                                    @endif">
                                    💰 {{ $alert['impact'] }}
                                </p>
                            @endif

                            {{-- Acciones --}}
                            <div class="flex gap-2 mt-2">
                                @foreach($alert['actions'] as $action)
                                    <a
                                        href="{{ $action['url'] }}"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md transition-colors
                                            @if($action['type'] === 'danger')
                                                bg-danger-100 text-danger-700 hover:bg-danger-200 dark:bg-danger-900 dark:text-danger-300 dark:hover:bg-danger-800
                                            @elseif($action['type'] === 'warning')
                                                bg-warning-100 text-warning-700 hover:bg-warning-200 dark:bg-warning-900 dark:text-warning-300 dark:hover:bg-warning-800
                                            @elseif($action['type'] === 'success')
                                                bg-success-100 text-success-700 hover:bg-success-200 dark:bg-success-900 dark:text-success-300 dark:hover:bg-success-800
                                            @else
                                                bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600
                                            @endif"
                                    >
                                        @if(isset($action['icon']))
                                            <x-filament::icon
                                                :icon="$action['icon']"
                                                class="h-3 w-3"
                                            />
                                        @endif
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Badge de prioridad --}}
                        <div class="flex-shrink-0">
                            @if($alert['priority'] === 'critical')
                                <span class="inline-flex items-center justify-center w-2 h-2 bg-danger-600 rounded-full"></span>
                            @else
                                <span class="inline-flex items-center justify-center w-2 h-2 bg-warning-600 rounded-full"></span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                {{-- Estado vacío --}}
                <div class="px-4 py-8 text-center">
                    <svg class="w-16 h-16 mx-auto text-success-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                        ¡Todo bajo control!
                    </h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        No hay alertas críticas en este momento
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if($this->count > 0)
            <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                    💡 Resolver estas alertas mejorará tu flujo de caja
                </p>
            </div>
        @endif
    </div>
</div>
