<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Estado de Backups del Sistema</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- ADVERTENCIAS ACTIVAS --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    ADVERTENCIAS DEL SISTEMA (MVP)
                </h3>
                <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-7">
                    <li>⚠️ Backups almacenados en el mismo servidor (sin recuperación ante desastres)</li>
                    <li>⚠️ Sin notificaciones por email (solo logs del sistema)</li>
                    <li>⚠️ Retención de solo 7 días (considerar ampliar en producción)</li>
                </ul>
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 ml-7">
                    💡 Para producción, se recomienda configurar almacenamiento remoto (S3, DigitalOcean Spaces) y notificaciones por email.
                </p>
            </div>

            {{-- ESTADÍSTICAS GENERALES --}}
            @php
                $stats = $this->getStatistics();
                $problematic = $this->getProblematicTenants();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Tenants</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                        {{ $stats['total_tenants'] + 1 }}
                    </div>
                    <div class="text-xs text-blue-500 dark:text-blue-400 mt-1">
                        Incluyendo landlord
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Backups Exitosos Hoy</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100 mt-1">
                        {{ $stats['backups_today_success'] }}
                    </div>
                    <div class="text-xs text-green-500 dark:text-green-400 mt-1">
                        @if($stats['last_backup'])
                            Último: {{ $stats['last_backup']->created_at->diffForHumans() }}
                        @else
                            Sin backups registrados
                        @endif
                    </div>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <div class="text-sm font-medium text-red-600 dark:text-red-400">Backups Fallidos Hoy</div>
                    <div class="text-2xl font-bold text-red-900 dark:text-red-100 mt-1">
                        {{ $stats['backups_today_failed'] }}
                    </div>
                    <div class="text-xs text-red-500 dark:text-red-400 mt-1">
                        @if($stats['backups_today_failed'] > 0)
                            ⚠️ Requiere atención
                        @else
                            ✅ Sin problemas
                        @endif
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="text-sm font-medium text-purple-600 dark:text-purple-400">Espacio Usado</div>
                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-1">
                        {{ $this->formatBytes($stats['total_storage_used']) }}
                    </div>
                    <div class="text-xs text-purple-500 dark:text-purple-400 mt-1">
                        Almacenamiento local
                    </div>
                </div>
            </div>

            {{-- TENANTS CON PROBLEMAS --}}
            @if(count($problematic) > 0)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        PROBLEMAS DETECTADOS ({{ count($problematic) }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($problematic as $item)
                            <div class="bg-white dark:bg-gray-800 rounded border border-red-300 dark:border-red-700 p-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $item['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            DB: {{ $item['database'] }}
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($item['status']['status'] === 'failed') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200
                                        @elseif($item['status']['status'] === 'never') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200
                                        @endif">
                                        @if($item['status']['status'] === 'failed')
                                            FALLIDO
                                        @elseif($item['status']['status'] === 'never')
                                            SIN BACKUPS
                                        @else
                                            ATRASADO
                                        @endif
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $item['status']['message'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DETALLE POR TENANT --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Estado Detallado por Tenant
                </h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($this->getAllTenantsStatus() as $item)
                        <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3 hover:border-gray-300 dark:hover:border-gray-600 transition">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $item['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item['database'] }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if(isset($item['status']['file_size']))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item['status']['file_size'] }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($item['status']['status'] === 'success') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200
                                        @elseif($item['status']['status'] === 'failed') bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200
                                        @elseif($item['status']['status'] === 'running') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ $item['status']['message'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ACCIONES --}}
            <div class="flex gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                <a href="{{ route('filament.admin.resources.backup-logs.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ver Historial Completo
                </a>

                <button
                    wire:click="executeManualBackup"
                    wire:confirm="¿Ejecutar backup manual de todos los tenants?\n\nEsto puede tomar varios minutos."
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg class="w-4 h-4 animate-spin" wire:loading fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>Ejecutar Backup Manual</span>
                    <span wire:loading>Ejecutando...</span>
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
