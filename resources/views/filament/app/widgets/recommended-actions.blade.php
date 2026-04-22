<x-filament-widgets::widget>
    <x-filament::section
        :heading="'🎯 Acciones Recomendadas'"
        :description="'Qué hacer HOY para mejorar tu negocio'"
    >
        @php
            $actions = $this->getActions();
        @endphp
        
        @if(count($actions) > 0)
            <div class="space-y-3">
                @foreach($actions as $index => $action)
                    <div class="group relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-all hover:shadow-md hover:border-primary-500">
                        {{-- Número de acción --}}
                        <div class="absolute -left-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white shadow-lg">
                            {{ $index + 1 }}
                        </div>
                        
                        <div class="flex items-start justify-between gap-4">
                            {{-- Contenido --}}
                            <div class="flex-1 min-w-0 pl-4">
                                <div class="flex items-start gap-3">
                                    {{-- Icono --}}
                                    <span class="text-3xl flex-shrink-0">{{ $action['icon'] }}</span>
                                    
                                    <div class="flex-1 min-w-0">
                                        {{-- Título con badge de prioridad --}}
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ $action['title'] }}
                                            </h4>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                @if($action['priority'] === 'medium')
                                                    bg-info-100 dark:bg-info-900 text-info-700 dark:text-info-300
                                                @else
                                                    bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                                @endif">
                                                {{ $this->getPriorityLabel($action['priority']) }}
                                            </span>
                                        </div>
                                        
                                        {{-- Descripción --}}
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $action['description'] }}
                                        </p>
                                        
                                        {{-- Impacto --}}
                                        @if(isset($action['impact']))
                                            <div class="mt-2 inline-flex items-center gap-1 rounded-md bg-success-50 dark:bg-success-900/30 px-2 py-1 text-xs font-medium text-success-700 dark:text-success-300">
                                                💰 Impacto: {{ $action['impact'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Acciones/CTAs --}}
                            <div class="flex flex-col gap-2 flex-shrink-0">
                                @foreach($action['actions'] as $actionButton)
                                    <a href="{{ $actionButton['url'] }}"
                                       class="inline-flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap
                                           @if($actionButton['type'] === 'danger')
                                               bg-danger-600 hover:bg-danger-700 text-white
                                           @elseif($actionButton['type'] === 'warning')
                                               bg-warning-600 hover:bg-warning-700 text-white
                                           @elseif($actionButton['type'] === 'success')
                                               bg-success-600 hover:bg-success-700 text-white
                                           @elseif($actionButton['type'] === 'info')
                                               bg-info-600 hover:bg-info-700 text-white
                                           @elseif($actionButton['type'] === 'primary')
                                               bg-primary-600 hover:bg-primary-700 text-white
                                           @else
                                               bg-gray-600 hover:bg-gray-700 text-white dark:bg-gray-700 dark:hover:bg-gray-600
                                           @endif">
                                        @if(isset($actionButton['icon']))
                                            <x-filament::icon
                                                :icon="$actionButton['icon']"
                                                class="h-4 w-4"
                                            />
                                        @endif
                                        {{ $actionButton['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Footer informativo --}}
            <div class="mt-4 rounded-lg bg-primary-50 dark:bg-primary-900/20 p-3">
                <p class="text-xs text-primary-700 dark:text-primary-300 text-center">
                    🤖 <strong>IA Asistente:</strong> Estas recomendaciones son generadas automáticamente basadas en el análisis de tu negocio
                </p>
            </div>
        @else
            {{-- Estado vacío --}}
            <div class="py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-100 dark:bg-success-900">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-6 w-6 text-success-600 dark:text-success-400"
                    />
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                    🎉 ¡Todo está bien!
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    No hay acciones pendientes en este momento. Sigue así.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
