@extends('tenant.layouts.app')

@php
    use App\Models\StoreSetting;
    use App\Modules\POS\Models\Sale;
    use App\Modules\Inventory\Models\Product;
    use App\Modules\POS\Models\Customer;

    // Get current tenant settings
    $settings = StoreSetting::current();

    // Calculate business metrics with proper error handling
    try {
        $salesToday = Sale::whereDate('created_at', today())->sum('total');
        $salesCountToday = Sale::whereDate('created_at', today())->count();
        $stockAlerts = Product::where('stock', '<=', 10)->count();
        $newCustomersToday = Customer::whereDate('created_at', today())->count();

        // Get recent activities
        $recentSales = Sale::with('customer')
                           ->orderBy('created_at', 'desc')
                           ->limit(5)
                           ->get() ?? collect();

        $lowStockProducts = Product::where('stock', '<=', 5)
                                  ->orderBy('stock', 'asc')
                                  ->limit(5)
                                  ->get() ?? collect();
    } catch (\Exception $e) {
        // Fallback values in case of database errors
        $salesToday = 0;
        $salesCountToday = 0;
        $stockAlerts = 0;
        $newCustomersToday = 0;
        $recentSales = collect();
        $lowStockProducts = collect();
    }
@endphp

@section('title', 'Panel Principal')

@section('header', 'Panel Principal')
@section('subheader', 'Gestiona tu ferretería de forma fácil y rápida')

{{-- Quick Actions --}}
@section('headerActions')
    <div class="flex space-x-3">
        <a href="{{ route('tenant.sales.create') }}"
           class="btn-primary bg-green-600 hover:bg-green-700 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Venta Rápida
        </a>

        <a href="{{ route('tenant.inventory.products.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Producto
        </a>
    </div>
@stop

{{-- Main Dashboard Content --}}
@section('content')
    {{-- Key Metrics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Sales Today --}}
        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">💰</div>
                    <div>
                        <div class="metric-value">${{ number_format($salesToday, 2) }}</div>
                        <div class="metric-label">Ventas Hoy</div>
                        @if($salesCountToday > 0)
                            <div class="flex items-center mt-2 text-sm text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $salesCountToday }} ventas
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock Alerts --}}
        <div class="metric-card border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">⚠️</div>
                    <div>
                        <div class="metric-value text-red-600">{{ $stockAlerts }}</div>
                        <div class="metric-label">Alertas de Stock</div>
                        @if($stockAlerts > 0)
                            <div class="flex items-center mt-2 text-sm text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Necesitas reponer
                            </div>
                        @else
                            <div class="flex items-center mt-2 text-sm text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Stock OK
                            </div>
                        @endif
                    </div>
                </div>
                @if($stockAlerts > 0)
                    <a href="{{ route('tenant.inventory.index') }}" class="text-red-500 hover:text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        {{-- New Customers --}}
        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">👥</div>
                    <div>
                        <div class="metric-value">{{ $newCustomersToday }}</div>
                        <div class="metric-label">Clientes Nuevos Hoy</div>
                        <div class="flex items-center mt-2 text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                            </svg>
                            Total: {{ Customer::count() ?? 0 }}
                        </div>
                    </div>
                </div>
                <a href="{{ route('tenant.customers.index') }}" class="text-primary hover:text-primary-hover">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">📦</div>
                    <div>
                        <div class="metric-value">{{ Product::count() ?? 0 }}</div>
                        <div class="metric-label">Productos en Catálogo</div>
                        <div class="flex items-center mt-2 text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            En {{ \App\Models\Category::count() ?? 0 }} categorías
                        </div>
                    </div>
                </div>
                <a href="{{ route('tenant.inventory.products') }}" class="text-primary hover:text-primary-hover">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Main POS CTA -->
    <div class="mb-8">
        <a href="{{ route('tenant.pos.index') }}"
           class="block w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-8 px-8 rounded-xl text-2xl text-center transition-all duration-200 transform hover:scale-105 shadow-2xl hover:shadow-3xl">
            <div class="flex items-center justify-center space-x-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>🛒 PUNTO DE VENTA</span>
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </div>
            <p class="text-lg mt-2 font-normal opacity-90">
                Iniciar nueva venta de forma rápida y sencilla
            </p>
        </a>
    </div>

    <!-- Secondary Information Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Sales -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Ventas Recientes
                </h2>
            </div>
            <div class="card-body">
                @if($recentSales->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentSales as $sale)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            Venta #{{ $sale->id }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $sale->customer?->name ?? 'Cliente General' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900">
                                        ${{ number_format($sale->total, 2) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $sale->created_at->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('tenant.sales.index') }}"
                           class="text-primary hover:text-primary-hover font-medium text-sm flex items-center">
                            Ver todas las ventas
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-500">No hay ventas hoy</p>
                        <p class="text-sm text-gray-400">Comienza usando el Punto de Venta</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Productos con Stock Bajo
                </h2>
            </div>
            <div class="card-body">
                @if($lowStockProducts->count() > 0)
                    <div class="space-y-3">
                        @foreach($lowStockProducts as $product)
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex items-center space-x-3">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ $product->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $product->category?->name ?? 'Sin categoría' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold {{ $product->stock <= 5 ? 'text-red-600' : 'text-yellow-600' }}">
                                        {{ $product->stock }} unidades
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Precio: ${{ number_format($product->price, 2) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('tenant.inventory.index') }}"
                           class="text-primary hover:text-primary-hover font-medium text-sm flex items-center">
                            Gestionar inventario
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-500">Stock optimizado</p>
                        <p class="text-sm text-gray-400">No hay productos con bajo stock</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('tenant.sales.create') }}"
           class="card hover:shadow-lg transition-shadow cursor-pointer group">
            <div class="p-6 text-center">
                <div class="text-3xl mb-3 group-hover:scale-110 transition-transform">💵</div>
                <h3 class="font-semibold text-gray-900">Venta Rápida</h3>
                <p class="text-sm text-gray-500 mt-1">Registrar venta</p>
            </div>
        </a>

        <a href="{{ route('tenant.customers.create') }}"
           class="card hover:shadow-lg transition-shadow cursor-pointer group">
            <div class="p-6 text-center">
                <div class="text-3xl mb-3 group-hover:scale-110 transition-transform">👤</div>
                <h3 class="font-semibold text-gray-900">Nuevo Cliente</h3>
                <p class="text-sm text-gray-500 mt-1">Agregar cliente</p>
            </div>
        </a>

        <a href="{{ route('tenant.inventory.stock-movements') }}"
           class="card hover:shadow-lg transition-shadow cursor-pointer group">
            <div class="p-6 text-center">
                <div class="text-3xl mb-3 group-hover:scale-110 transition-transform">📋</div>
                <h3 class="font-semibold text-gray-900">Movimientos</h3>
                <p class="text-sm text-gray-500 mt-1">Ver movimientos</p>
            </div>
        </a>

        <a href="{{ route('tenant.reports.index') }}"
           class="card hover:shadow-lg transition-shadow cursor-pointer group">
            <div class="p-6 text-center">
                <div class="text-3xl mb-3 group-hover:scale-110 transition-transform">📊</div>
                <h3 class="font-semibold text-gray-900">Reportes</h3>
                <p class="text-sm text-gray-500 mt-1">Ver reportes</p>
            </div>
        </a>
    </div>
@stop