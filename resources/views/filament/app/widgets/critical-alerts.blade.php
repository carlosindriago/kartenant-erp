<x-filament-widgets::widget>
    <x-filament::section
        :heading="'🚨 Atención Requerida'"
        :description="count($this->getAlerts()) . ' alertas activas'"
        collapsible
        collapsed="false"
    >
        @php
            $counts = $this->getAlertCounts();
            $totalLoss = $this->getTotalEstimatedLoss();
        @endphp
        
        {{-- Header con estadísticas --}}
        <div class="mb-4 flex items-center justify-between rounded-lg bg-danger-50 dark:bg-danger-950 p-3">
            <div class="flex items-center gap-4">
                <div class="text-sm">
                    @if($counts['critical'] > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-danger-600 px-2 py-1 text-xs font-medium text-white">
                            🔴 {{ $counts['critical'] }} críticas
                        </span>
                    @endif
                    @if($counts['high'] > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-warning-600 px-2 py-1 text-xs font-medium text-white">
                            🟡 {{ $counts['high'] }} importantes
                        </span>
                    @endif
                </div>
            </div>
            
            @if($totalLoss > 0)
                <div class="text-right">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Pérdida estimada/día</div>
                    <div class="text-lg font-bold text-danger-600 dark:text-danger-400">
                        💸 ${{ number_format($totalLoss, 0) }}
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Lista de alertas --}}
        <div class="space-y-3">
            @foreach($this->getAlerts() as $alert)
                <div class="rounded-lg border-l-4 p-4 shadow-sm transition-all hover:shadow-md
                    @if($alert['priority'] === 'critical')
                        border-danger-500 bg-danger-50 dark:bg-danger-950/50
                    @else
                        border-warning-500 bg-warning-50 dark:bg-warning-950/50
                    @endif">
                    
                    <div class="flex items-start justify-between gap-4">
                        {{-- Contenido de la alerta --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-2">
                                <span class="text-2xl flex-shrink-0">{{ $alert['icon'] }}</span>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $alert['title'] }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $alert['description'] }}
                                    </p>
                                    
                                    @if(isset($alert['impact']))
                                        <div class="mt-2 inline-flex items-center gap-1 rounded-md 
                                            @if($alert['priority'] === 'critical')
                                                bg-danger-100 dark:bg-danger-900 text-danger-700 dark:text-danger-300
                                            @else
                                                bg-warning-100 dark:bg-warning-900 text-warning-700 dark:text-warning-300
                                            @endif
                                            px-2 py-1 text-xs font-medium">
                                            💰 {{ $alert['impact'] }}
                                        </div>
                                    @endif
                                    
                                    @if(isset($alert['estimated_loss']))
                                        <p class="mt-2 text-sm font-semibold text-danger-700 dark:text-danger-400">
                                            💸 Pérdida estimada: ${{ number_format($alert['estimated_loss'], 0) }}/día
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- Acciones --}}
                        <div class="flex flex-col gap-2 flex-shrink-0">
                            @foreach($alert['actions'] as $action)
                                <a href="{{ $action['url'] }}"
                                   class="inline-flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap
                                       @if($action['type'] === 'danger')
                                           bg-danger-600 hover:bg-danger-700 text-white
                                       @elseif($action['type'] === 'warning')
                                           bg-warning-600 hover:bg-warning-700 text-white
                                       @elseif($action['type'] === 'success')
                                           bg-success-600 hover:bg-success-700 text-white
                                       @elseif($action['type'] === 'info')
                                           bg-info-600 hover:bg-info-700 text-white
                                       @else
                                           bg-gray-600 hover:bg-gray-700 text-white dark:bg-gray-700 dark:hover:bg-gray-600
                                       @endif">
                                    @if(isset($action['icon']))
                                        <x-filament::icon
                                            :icon="$action['icon']"
                                            class="h-4 w-4"
                                        />
                                    @endif
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Footer con mensaje motivacional --}}
        <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-900 p-3">
            <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                💡 <strong>Pro tip:</strong> Resolver estas alertas puede mejorar significativamente tu flujo de caja y rentabilidad
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
