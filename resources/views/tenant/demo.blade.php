@extends('tenant.layouts.app')

@section('title', 'Demo del Sistema Ernesto')

@section('header', 'Demo del Sistema')
@section('subheader', 'Muestra de componentes y funcionalidades')

@section('content')
    {{-- Demo Section --}}
    <div class="mb-8">
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900">🎨 Componentes de Demostración</h2>
                <p class="text-sm text-gray-500 mt-1">Muestra de los componentes principales del sistema</p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Metric Cards Demo -->
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-900">Tarjetas de Métricas</h3>

                        @include('tenant.partials.metric-card', [
                            'icon' => '💰',
                            'value' => '$12,450.00',
                            'label' => 'Ventas del Día',
                            'trend' => 'up',
                            'trendValue' => '+15%'
                        ])

                        @include('tenant.partials.metric-card', [
                            'icon' => '📦',
                            'value' => '24',
                            'label' => 'Productos con Stock Bajo',
                            'color' => 'warning'
                        ])
                    </div>

                    <!-- Buttons Demo -->
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-900">Botones Principales</h3>

                        <button class="btn-primary w-full mb-3">
                            🛒 PUNTO DE VENTA
                        </button>

                        <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 rounded-lg text-lg transition-colors">
                            ✅ Venta Rápida
                        </button>

                        <button class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                            📊 Ver Reportes
                        </button>
                    </div>
                </div>

                <!-- Navigation Demo -->
                <div class="border-t pt-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Navegación Responsive</h3>

                    <div class="bg-gray-50 rounded-lg p-4">
                        @include('tenant.partials.navigation', [
                            'orientation' => 'horizontal',
                            'showMobileDropdown' => true
                        ])
                    </div>
                </div>

                <!-- Content Cards Demo -->
                <div class="border-t pt-6 mt-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Tarjetas de Contenido</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @include('tenant.partials.content-card', [
                            'title' => 'Ventas Recientes',
                            'subtitle' => 'Últimas 24 horas',
                            'collapsible' => true,
                            'collapsed' => false
                        ])

                        @include('tenant.partials.content-card', [
                            'title' => 'Alertas de Inventario',
                            'subtitle' => 'Productos con stock bajo',
                            'collapsible' => true,
                            'collapsed' => true
                        ])
                    </div>
                </div>

                <!-- Flash Messages Demo -->
                <div class="border-t pt-6 mt-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Mensajes Flash</h3>

                    <div class="space-y-4">
                        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 shadow-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        ¡Éxito! La operación se completó correctamente.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 shadow-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-800">
                                        ⚠️ Advertencia: El stock está por debajo del mínimo recomendado.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Responsiveness Test -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900">📱 Prueba de Responsividad</h2>
            <p class="text-sm text-gray-500 mt-1">Esta página se adapta a diferentes tamaños de pantalla</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-primary text-white p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">📱</div>
                    <div class="font-semibold">Mobile</div>
                    <div class="text-sm opacity-90">320px+</div>
                </div>

                <div class="bg-blue-600 text-white p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">📱</div>
                    <div class="font-semibold">Large</div>
                    <div class="text-sm opacity-90">414px+</div>
                </div>

                <div class="bg-indigo-600 text-white p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">💻</div>
                    <div class="font-semibold">Tablet</div>
                    <div class="text-sm opacity-90">768px+</div>
                </div>

                <div class="bg-purple-600 text-white p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">🖥️</div>
                    <div class="font-semibold">Desktop</div>
                    <div class="text-sm opacity-90">1024px+</div>
                </div>
            </div>

            <div class="text-center text-sm text-gray-500">
                <p>💡 <strong>Tip:</strong> Cambia el tamaño de tu navegador o usa herramientas de desarrollo para probar la responsividad</p>
            </div>
        </div>
    </div>
@stop