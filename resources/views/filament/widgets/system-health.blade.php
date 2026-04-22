<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Estado del Sistema</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Monitoreo en tiempo real</p>
                </div>
                @php
                    $status = $this->getSystemStatus();
                @endphp
                <div class="flex items-center gap-2">
                    @if($status['overall_status'] === 'healthy')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-success-700 bg-success-50 dark:text-success-500 dark:bg-success-400/10 rounded-md">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Sistema Saludable
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-danger-700 bg-danger-50 dark:text-danger-500 dark:bg-danger-400/10 rounded-md">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Sistema Degradado
                        </span>
                    @endif
                </div>
            </div>

            {{-- System Checks Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($status['checks'] as $name => $check)
                    <div class="flex items-start gap-3 p-4 border rounded-lg dark:border-gray-700
                        {{ $check['status'] === 'ok' ? 'bg-success-50/50 dark:bg-success-400/5 border-success-200 dark:border-success-400/20' : 'bg-danger-50/50 dark:bg-danger-400/5 border-danger-200 dark:border-danger-400/20' }}">
                        <div class="flex-shrink-0">
                            @if($check['status'] === 'ok')
                                <svg class="w-5 h-5 text-success-600 dark:text-success-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-danger-600 dark:text-danger-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $name) }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                {{ $check['message'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Slack Configuration --}}
            @php
                $slackStatus = $this->getSlackStatus();
            @endphp
            <div class="border-t dark:border-gray-700 pt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Configuración de Alertas</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Slack Webhook:</span>
                        <span class="font-medium {{ $slackStatus['configured'] ? 'text-success-600 dark:text-success-500' : 'text-danger-600 dark:text-danger-500' }}">
                            {{ $slackStatus['url'] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Entorno:</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ ucfirst($slackStatus['environment']) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Endpoint Health:</span>
                        <a href="{{ route('health') }}" target="_blank" class="font-medium text-primary-600 dark:text-primary-500 hover:underline">
                            /health →
                        </a>
                    </div>
                </div>
            </div>

            {{-- Warning if Slack not configured --}}
            @if(!$slackStatus['configured'])
                <div class="flex items-start gap-3 p-4 bg-warning-50 dark:bg-warning-400/10 border border-warning-200 dark:border-warning-400/20 rounded-lg">
                    <svg class="w-5 h-5 text-warning-600 dark:text-warning-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-warning-800 dark:text-warning-500">
                            Alertas de Slack No Configuradas
                        </h4>
                        <p class="text-xs text-warning-700 dark:text-warning-600 mt-1">
                            Configure LOG_SLACK_WEBHOOK_URL en el archivo .env para recibir alertas automáticas de errores críticos.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Environment Info --}}
            @php
                $envInfo = $this->getEnvironmentInfo();
            @endphp
            <div class="border-t dark:border-gray-700 pt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Información del Entorno</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    @foreach($envInfo as $key => $value)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Last Check Timestamp --}}
            <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
                Última verificación: {{ $status['last_check'] }}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
