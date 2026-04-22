<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors" 
     x-data="posTerminal()" 
     x-init="init()"
     @keydown.window="handleKeyPress($event)"
     :class="darkMode ? 'dark' : ''"
     @notify.window="showNotification($event.detail)"
     @play-beep.window="playBeep()"
     @play-error-beep.window="playErrorBeep()"
     @flash-success.window="flashSuccess()"
     @sale-completed.window="onSaleCompleted($event.detail)">
     
    {{-- Top Bar --}}
    <div class="bg-white dark:bg-gray-800 shadow-lg border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                {{-- Logo y Búsqueda --}}
                <div class="flex items-center gap-4 flex-1">
                    <div class="flex items-center gap-3">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 p-3 rounded-xl shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Punto de Venta</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sistema Profesional</p>
                        </div>
                    </div>
                    
                    <div class="flex-1 max-w-2xl">
                        <div class="relative">
                            <input 
                                wire:model.live.debounce.300ms="search"
                                type="text"
                                placeholder="Buscar producto por nombre, código o código de barras..."
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 transition-all placeholder-gray-400 dark:placeholder-gray-500"
                            >
                            <svg class="absolute left-4 top-3.5 h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Acciones y Usuario --}}
                <div class="flex items-center gap-3">
                    {{-- GESTIÓN DE CAJA --}}
                    @if(!$currentCashRegister)
                        {{-- NO hay caja abierta: Mostrar botón de apertura --}}
                        <button 
                            wire:click="$set('showOpenRegisterModal', true)"
                            class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-lg transition-all shadow-lg hover:shadow-xl"
                            title="Debe abrir caja antes de vender"
                        >
                            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-bold">Abrir Caja</span>
                        </button>
                    @else
                        {{-- HAY caja abierta: Mostrar info y controles --}}
                        <div class="flex items-center gap-2 px-3 py-2 bg-green-50 dark:bg-green-900 dark:bg-opacity-30 border border-green-200 dark:border-green-700 rounded-lg">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-xs font-semibold text-green-700 dark:text-green-300">Caja: {{ $currentCashRegister->register_number }}</span>
                            </div>
                        </div>
                        
                        <button 
                            wire:click="prepareCloseCashRegister"
                            class="flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors"
                            title="Cerrar y arquear caja"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="hidden lg:inline font-medium">Cerrar Caja</span>
                        </button>
                        
                        <button 
                            wire:click="showDailyReport"
                            class="flex items-center gap-2 px-4 py-2 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-colors"
                            title="Ver reporte del día"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden lg:inline font-medium">Reporte</span>
                        </button>
                    @endif
                    
                    {{-- Divisor --}}
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>
                    
                    {{-- Regresar al Panel --}}
                    <a 
                        href="/app"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        title="Volver al panel"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="hidden lg:inline font-medium">Salir</span>
                    </a>
                    
                    {{-- Historial del Día --}}
                    <button 
                        wire:click="loadTodaySales"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-800 transition-colors"
                        title="Ver ventas del día (F2)"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden lg:inline font-medium">Historial</span>
                        <span class="kbd hidden xl:inline">F2</span>
                    </button>
                    
                    {{-- 🚨 BOTÓN DE PÁNICO: Anular Última Venta --}}
                    @if($this->canCancelLastSale)
                        <button 
                            wire:click="openCancelConfirmation"
                            class="flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors animate-pulse"
                            title="Anular última venta (últimos 5 minutos)"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                            <span class="hidden lg:inline font-medium">Anular Venta</span>
                        </button>
                    @endif
                    
                    {{-- Ayuda de Teclado --}}
                    <button 
                        @click="$wire.showKeyboardHelp = !$wire.showKeyboardHelp"
                        class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800 transition-colors"
                        title="Atajos de teclado (F1)"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    
                    {{-- Dark Mode Toggle --}}
                    <button 
                        @click="darkMode = !darkMode"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        title="Cambiar tema"
                    >
                        <svg x-show="!darkMode" class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="darkMode" class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    
                    @php
                        $user = auth()->user() ?? auth('tenant')->user();
                    @endphp
                    @if($user)
                        <div class="text-right hidden lg:block border-l border-gray-200 dark:border-gray-700 pl-4 ml-2">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Usuario</div>
                            <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ $user->name }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex">
        {{-- Products Grid --}}
        <div class="flex-1 p-6 overflow-y-auto bg-gray-50 dark:bg-gray-900 transition-colors" style="height: calc(100vh - 88px)">
            @if(strlen($search) > 0 && $products->isEmpty())
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No se encontraron productos</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Intenta con otro término de búsqueda</p>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach($products as $product)
                        <button 
                            wire:click="addToCart({{ $product->id }})"
                            @if($product->stock <= 0) disabled @endif
                            class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-2xl transition-all duration-200 overflow-hidden border-2 border-transparent hover:border-blue-500 dark:hover:border-blue-400 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            {{-- Product Image --}}
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-20 h-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                @endif
                                
                                {{-- Stock badge --}}
                                <div class="absolute top-2 right-2">
                                    @if($product->stock > 0)
                                        <span class="bg-green-500 dark:bg-green-600 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                                            {{ $product->stock }} en stock
                                        </span>
                                    @else
                                        <span class="bg-red-500 dark:bg-red-600 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                                            Sin stock
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Product Info --}}
                            <div class="p-4 text-center">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-1 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    {{ $product->name }}
                                </h3>
                                @if($product->sku)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Código: {{ $product->sku }}</p>
                                @endif
                                
                                {{-- Precio Base + IVA --}}
                                <div class="space-y-1 mt-3">
                                    <div class="relative">
                                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($product->final_price, 2) }}</span>
                                        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 text-blue-500 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    @if($product->tax)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Base: ${{ number_format($product->price, 2) }} + {{ number_format($product->tax->rate, 2) }}% IVA
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Cart Sidebar --}}
        <div class="w-full lg:w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col" style="height: calc(100vh - 88px)">
            {{-- Cart Header --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700">
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Carrito
                    <span class="ml-auto text-sm bg-white text-blue-600 dark:text-blue-700 px-3 py-1 rounded-full font-bold">{{ count($cart) }} items</span>
                </h2>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900">
                @if(empty($cart))
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-600">
                        <svg class="w-24 h-24 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <p class="text-lg font-medium">Carrito vacío</p>
                        <p class="text-sm">Agrega productos para comenzar</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($cart as $index => $item)
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="flex gap-2.5">
                                    @if($item['product']->image)
                                        <img src="{{ Storage::url($item['product']->image) }}" alt="{{ $item['product']->name }}" class="w-12 h-12 object-cover rounded-md flex-shrink-0">
                                    @else
                                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-md flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $item['product']->name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            ${{ number_format($item['product']->final_price, 2) }} c/u
                                        </p>
                                        <div class="mt-1.5 flex items-center gap-1.5">
                                            <button wire:click="decrementQty({{ $index }})" class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800 flex items-center justify-center transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                </svg>
                                            </button>
                                            <span class="w-9 text-center text-sm font-bold text-gray-900 dark:text-white">{{ $item['qty'] }}</span>
                                            <button wire:click="incrementQty({{ $index }})" class="w-7 h-7 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800 flex items-center justify-center transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right flex-shrink-0 flex flex-col justify-between">
                                        <div class="text-base font-bold text-gray-900 dark:text-white">${{ number_format($item['product']->final_price * $item['qty'], 2) }}</div>
                                        <button wire:click="removeFromCart({{ $index }})" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors p-1 self-end">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cart Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 space-y-4">
                {{-- Discount Section (Collapsible) --}}
                @if(!empty($cart))
                    <div x-data="{ discountExpanded: {{ $discountAmount > 0 ? 'true' : 'false' }} }" class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg overflow-hidden">
                        {{-- Header (Always Visible) --}}
                        <button @click="discountExpanded = !discountExpanded" class="w-full p-3 flex items-center justify-between hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Descuento</span>
                                @if($discountAmount > 0)
                                    <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-0.5 rounded-full">
                                        -${{ number_format($discountAmount, 2) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if($discountAmount > 0)
                                    <button wire:click.stop="removeDiscount" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-xs font-medium">
                                        Quitar
                                    </button>
                                @endif
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" :class="discountExpanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>
                        
                        {{-- Expanded Content --}}
                        <div x-show="discountExpanded" x-collapse class="px-3 pb-3 space-y-2">
                            <div class="grid grid-cols-3 gap-2">
                                <select wire:model.live="discountType" class="col-span-1 px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 dark:focus:ring-yellow-400">
                                    <option value="fixed">$ Fijo</option>
                                    <option value="percentage">% %</option>
                                </select>
                                <input 
                                    type="number" 
                                    wire:model.live="discountValue"
                                    min="0"
                                    max="{{ $discountType === 'percentage' ? '100' : $subtotal }}"
                                    step="{{ $discountType === 'percentage' ? '1' : '0.01' }}"
                                    placeholder="0"
                                    class="col-span-2 px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-2 focus:ring-yellow-500 dark:focus:ring-yellow-400"
                                >
                            </div>
                            
                            @if($discountValue > 0)
                                <button 
                                    wire:click="applyDiscount"
                                    class="w-full bg-yellow-500 dark:bg-yellow-600 hover:bg-yellow-600 dark:hover:bg-yellow-700 text-white text-sm font-semibold py-1.5 px-3 rounded-md transition-colors"
                                >
                                    Aplicar Descuento
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
                
                <div class="space-y-2">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Subtotal (Neto):</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>IVA:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($taxAmount, 2) }}</span>
                    </div>
                    @if($discountAmount > 0)
                        <div class="flex justify-between text-yellow-600 dark:text-yellow-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                                Descuento:
                            </span>
                            <span class="font-semibold">-${{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-2xl font-bold text-gray-900 dark:text-white pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span>Total a Cobrar:</span>
                        <span class="text-green-600 dark:text-green-400">${{ number_format($total, 2) }}</span>
                    </div>
                </div>

                <button 
                    wire:click="openPaymentModal"
                    @if(empty($cart)) disabled @endif
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 hover:from-green-600 hover:to-green-700 dark:hover:from-green-700 dark:hover:to-green-800 disabled:from-gray-300 disabled:to-gray-400 dark:disabled:from-gray-700 dark:disabled:to-gray-800 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 disabled:transform-none disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Procesar Pago</span>
                    <span class="kbd ml-2">F12</span>
                </button>
                
                @if(!empty($cart))
                    <button 
                        wire:click="clearCart"
                        class="w-full bg-white dark:bg-gray-700 border-2 border-red-500 dark:border-red-600 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900 font-semibold py-3 px-6 rounded-xl transition-all duration-200"
                    >
                        Vaciar Carrito
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- MODALS Y COMPONENTES ADICIONALES --}}
    @include('livewire.p-o-s.partials.payment-modal')
    @include('livewire.p-o-s.partials.receipt-modal')
    @include('livewire.p-o-s.partials.history-modal')
    @include('livewire.p-o-s.partials.cancel-confirmation-modal')
    @include('livewire.p-o-s.partials.keyboard-help-modal')
    @include('livewire.p-o-s.partials.notification-toast')
    
    {{-- GESTIÓN DE CAJA --}}
    @include('livewire.p-o-s.partials.open-register-modal')
    @include('livewire.p-o-s.partials.close-register-modal')
    @include('livewire.p-o-s.partials.confirm-close-modal')
    @include('livewire.p-o-s.partials.daily-report-modal')
    
    {{-- El script Alpine se carga en el layout kiosk DESPUÉS de @livewireScripts --}}
</div>
