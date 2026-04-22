@extends('filament::pages.dashboard')

@section('content')
    {{-- Dashboard Corporativo con Diseño Profesional --}}
    <div class="space-y-6">
        {{-- Saludo Personalizado y Acciones Rápidas --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl p-6 text-white shadow-xl">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Saludo --}}
                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ $user_greeting }}</h1>
                    <p class="text-blue-100">{{ $subheading }}</p>
                </div>

                {{-- Acciones Rápidas --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($quick_actions as $action)
                        @if(auth('superadmin')->user()->is_super_admin || auth('superadmin')->user()->can($action['permission'], 'superadmin'))
                            <a href="{{ $action['url'] }}"
                               class="bg-white/20 hover:bg-white/30 backdrop-blur rounded-lg p-3 text-center transition-all duration-200 group">
                                <div class="text-3xl mb-1">{{ $action['icon'] }}</div>
                                <div class="text-xs font-medium">{{ $action['title'] }}</div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Alertas Críticas --}}
        @if(!empty($critical_alerts))
            <div class="space-y-3">
                @foreach($critical_alerts as $alert)
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="text-red-600 text-xl">{{ $alert['icon'] }}</div>
                            <div>
                                <div class="font-medium text-red-800">{{ $alert['message'] }}</div>
                                <div class="text-red-600 text-sm">Requiere atención inmediata</div>
                            </div>
                        </div>
                        <a href="{{ $alert['url'] }}"
                           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                            {{ $alert['action'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- KPIs Principales --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($kpi_metrics as $kpi)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-lg {{ $kpi['color'] === 'success' ? 'bg-green-100' : ($kpi['color'] === 'primary' ? 'bg-blue-100' : ($kpi['color'] === 'warning' ? 'bg-yellow-100' : 'bg-gray-100')) }}">
                            <div class="text-xl {{ $kpi['color'] === 'success' ? 'text-green-600' : ($kpi['color'] === 'primary' ? 'text-blue-600' : ($kpi['color'] === 'warning' ? 'text-yellow-600' : 'text-gray-600')) }}">
                                {{ $kpi['icon'] }}
                            </div>
                        </div>
                        <div class="text-right">
                            @if(isset($kpi['trend']))
                                <div class="flex items-center text-sm {{ $kpi['trend']['positive'] ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $kpi['trend']['positive'] ? '↑' : '↓' }} {{ $kpi['trend']['value'] }}%
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 mb-1">
                        {{ $kpi['format'] === 'currency' ? '$' . number_format($kpi['value'], 0) : number_format($kpi['value'], 0) }}
                    </div>
                    <div class="text-gray-600 text-sm font-medium">{{ $kpi['title'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Grid Principal de Información --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Salud del Sistema --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Salud del Sistema</h3>
                        <div class="flex items-center space-x-2">
                            <div class="text-sm text-gray-600">Puntuación General:</div>
                            <div class="text-lg font-bold {{ $business_overview['system_health_score'] >= 90 ? 'text-green-600' : ($business_overview['system_health_score'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $business_overview['system_health_score'] }}/100
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="text-center p-3 rounded-lg {{ $system_health['database_status'] === 'healthy' ? 'bg-green-50' : 'bg-red-50' }}">
                            <div class="text-2xl mb-1">{{ $system_health['database_status'] === 'healthy' ? '✅' : '❌' }}</div>
                            <div class="text-sm font-medium">Base de Datos</div>
                        </div>
                        <div class="text-center p-3 rounded-lg {{ $system_health['cache_status'] === 'healthy' ? 'bg-green-50' : 'bg-red-50' }}">
                            <div class="text-2xl mb-1">{{ $system_health['cache_status'] === 'healthy' ? '✅' : '❌' }}</div>
                            <div class="text-sm font-medium">Caché</div>
                        </div>
                        <div class="text-center p-3 rounded-lg {{ $system_health['queue_status'] === 'healthy' ? 'bg-green-50' : 'bg-red-50' }}">
                            <div class="text-2xl mb-1">{{ $system_health['queue_status'] === 'healthy' ? '✅' : '❌' }}</div>
                            <div class="text-sm font-medium">Colas</div>
                        </div>
                        <div class="text-center p-3 rounded-lg {{ $system_health['backup_status'] === 'healthy' ? 'bg-green-50' : ($system_health['backup_status'] === 'warning' ? 'bg-yellow-50' : 'bg-red-50') }}">
                            <div class="text-2xl mb-1">{{ $system_health['backup_status'] === 'healthy' ? '✅' : ($system_health['backup_status'] === 'warning' ? '⚠️' : '❌') }}</div>
                            <div class="text-sm font-medium">Backups</div>
                        </div>
                    </div>

                    {{-- Métricas de Rendimiento --}}
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Rendimiento del Sistema</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Carga del Sistema</div>
                                <div class="text-lg font-semibold">{{ $system_health['system_load']['1min'] }}</div>
                                <div class="text-xs text-gray-500">1min / 5min / 15min</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Uso de Memoria</div>
                                <div class="text-lg font-semibold">{{ $performance_metrics['memory_usage']['current'] }}MB</div>
                                <div class="text-xs text-gray-500">{{ $performance_metrics['memory_usage']['percentage'] }}%</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600 mb-1">Uso de Disco</div>
                                <div class="text-lg font-semibold">{{ $performance_metrics['disk_usage']['percentage'] }}%</div>
                                <div class="text-xs text-gray-500">{{ $performance_metrics['disk_usage']['used'] }}GB / {{ $performance_metrics['disk_usage']['total'] }}GB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Business Insights --}}
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Insights de Negocio</h3>
                    <div class="space-y-4">
                        @foreach($business_insights as $insight)
                            <div class="border-l-4 {{ $insight['priority'] === 'high' ? 'border-red-500 bg-red-50' : ($insight['priority'] === 'medium' ? 'border-yellow-500 bg-yellow-50' : 'border-blue-500 bg-blue-50') }} p-4 rounded-lg">
                                <div class="flex items-start space-x-3">
                                    <div class="text-xl mt-1">{{ $insight['icon'] }}</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 mb-2">{{ $insight['title'] }}</div>
                                        <div class="text-sm text-gray-700 mb-3">{{ $insight['description'] }}</div>
                                        <button class="bg-white hover:bg-gray-50 border border-gray-300 text-sm font-medium px-3 py-1 rounded-lg transition-colors">
                                            {{ $insight['action'] }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Segunda Fila de Información Detallada --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Top Tenants --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Top Tenants por Rendimiento</h3>
                    <a href="{{ route('filament.admin.resources.tenants.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver Todos</a>
                </div>
                <div class="space-y-3">
                    @foreach($top_performers->take(5) as $tenant)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-bold text-sm">{{ strtoupper(substr($tenant['name'], 0, 2)) }}</span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $tenant['name'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $tenant['domain'] }}.emporiodigital.test</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold {{ $tenant['health_score'] >= 90 ? 'text-green-600' : ($tenant['health_score'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $tenant['health_score'] }}/100
                                </div>
                                <div class="text-xs text-gray-600">{{ $tenant['users_count'] }} usuarios</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Resumen de Soporte --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Tickets de Soporte</h3>
                    <a href="{{ route('filament.admin.resources.bug-reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Ver Todos</a>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ $support_tickets['open_tickets'] }}</div>
                        <div class="text-sm text-gray-600">Abiertos</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $support_tickets['in_progress_tickets'] }}</div>
                        <div class="text-sm text-gray-600">En Progreso</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Resueltos Hoy</span>
                        <span class="font-medium">{{ $support_tickets['resolved_today'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Prioridad Crítica</span>
                        <span class="font-medium text-red-600">{{ $support_tickets['critical_priority'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Tiempo Promedio</span>
                        <span class="font-medium">{{ $support_tickets['average_resolution_time'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actividad Reciente --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Actividad Reciente del Sistema</h3>
                <span class="text-sm text-gray-600">Últimas 24 horas</span>
            </div>

            <div class="space-y-3">
                @foreach($recent_activities as $activity)
                    <div class="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                        <div class="text-xl text-gray-600">{{ $activity['icon'] }}</div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">{{ $activity['description'] }}</div>
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">{{ $activity['user_name'] }}</span> •
                                <span>{{ $activity['tenant_name'] }}</span> •
                                <span>{{ $activity['created_at'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Distribución de Tenants --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Distribución por Estado</h3>

                <div class="space-y-4">
                    @foreach($tenant_distribution['by_status'] as $status => $count)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full {{ $status === 'active' ? 'bg-green-500' : ($status === 'trial' ? 'bg-blue-500' : ($status === 'suspended' ? 'bg-yellow-500' : 'bg-gray-500')) }}"></div>
                                <span class="capitalize">{{ $status }}</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="font-medium">{{ $count }}</span>
                                <span class="text-sm text-gray-600">{{ $tenant_distribution['by_status_percentages'][$status] ?? 0 }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Alertas de Seguridad</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Usuarios sin 2FA</span>
                        <span class="font-medium {{ $security_alerts['users_without_2fa'] > 0 ? 'text-yellow-600' : 'text-green-600' }}">
                            {{ $security_alerts['users_without_2fa'] }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Intentos Fallidos</span>
                        <span class="font-medium {{ $security_alerts['failed_login_attempts'] > 5 ? 'text-red-600' : 'text-gray-600' }}">
                            {{ $security_alerts['failed_login_attempts'] }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Actividad Sospechosa</span>
                        <span class="font-medium {{ $security_alerts['suspicious_activities'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $security_alerts['suspicious_activities'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Animaciones y Transiciones Corporativas */
    .group:hover .group-hover\:scale-110 {
        transform: scale(1.1);
    }

    .animate-pulse-slow {
        animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Gradientes Profesionales */
    .gradient-corporate {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Efectos Hover Corporativos */
    .corporate-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .corporate-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    /* Indicadores de Estado */
    .status-indicator {
        position: relative;
    }

    .status-indicator::before {
        content: '';
        position: absolute;
        top: -2px;
        right: -2px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    .status-healthy::before {
        background-color: #10b981;
    }

    .status-warning::before {
        background-color: #f59e0b;
    }

    .status-critical::before {
        background-color: #ef4444;
    }

    /* Optimización Mobile */
    @media (max-width: 768px) {
        .grid-cols-1.lg\:grid-cols-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-cols-1.lg\:grid-cols-3 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animaciones de carga suaves
    const cards = document.querySelectorAll('.corporate-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.5s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Actualización automática de métricas cada 5 minutos
    setInterval(() => {
        window.location.reload();
    }, 300000);

    // Interactividad mejorada
    const quickActions = document.querySelectorAll('.quick-action');
    quickActions.forEach(action => {
        action.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });

        action.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>
@endpush