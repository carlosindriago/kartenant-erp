<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @php
        use App\Models\StoreSetting;
        $settings = StoreSetting::current();
    @endphp

    <title>{{ $title ?? $settings->effective_store_name }} - Panel de Gestión</title>
    <meta name="description" content="Panel de gestión para {{ $settings->effective_store_name }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ $settings->logo_url ?? asset('images/default-store-logo.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Dynamic Branding CSS -->
    @include('tenant.partials.branding-css')

    <!-- Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $settings->effective_brand_color }}',
                    },
                    fontFamily: {
                        primary: ['{{ $settings->effective_primary_font }}', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Livewire Scripts (if needed) -->
    @livewireStyles
</head>

<body class="h-full bg-gray-50 font-primary">

    {{-- Main Container --}}
    <div class="min-h-full">

        {{-- Top Navigation --}}
        <nav class="bg-white shadow-lg border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    {{-- Left Side - Logo & Store Name --}}
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            @if($settings->logo_url)
                                <img src="{{ $settings->logo_url }}"
                                     alt="{{ $settings->effective_store_name }}"
                                     class="h-10 w-auto object-contain">
                            @else
                                <div class="h-10 w-10 bg-primary rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                    {{ substr($settings->effective_store_name, 0, 1) }}
                                </div>
                            @endif

                            <div class="ml-3">
                                <h1 class="text-xl font-bold text-gray-900">{{ $settings->effective_store_name }}</h1>
                                <p class="text-xs text-gray-500">{{ $settings->effective_store_slogan }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Right Side - User Menu & Mobile Toggle --}}
                    <div class="flex items-center space-x-4">

                        {{-- Desktop Navigation --}}
                        <div class="hidden md:flex items-center space-x-1">
                            <a href="{{ route('tenant.dashboard') }}"
                               class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Inicio
                            </a>

                            <a href="{{ route('tenant.pos.index') }}"
                               class="nav-link {{ request()->routeIs('tenant.pos.*') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                Punto de Venta
                            </a>

                            <a href="{{ route('tenant.inventory.index') }}"
                               class="nav-link {{ request()->routeIs('tenant.inventory.*') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Inventario
                            </a>

                            <a href="{{ route('tenant.sales.index') }}"
                               class="nav-link {{ request()->routeIs('tenant.sales.*') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Ventas
                            </a>

                            <a href="{{ route('tenant.customers.index') }}"
                               class="nav-link {{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Clientes
                            </a>

                            <a href="{{ route('tenant.settings.index') }}"
                               class="nav-link {{ request()->routeIs('tenant.settings.*') ? 'active' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Configuración
                            </a>
                        </div>

                        {{-- User Menu Dropdown --}}
                        <div class="relative">
                            <button onclick="toggleUserMenu()"
                                    class="flex items-center space-x-3 text-sm rounded-lg hover:bg-gray-100 p-2 transition-colors"
                                    aria-expanded="false"
                                    aria-haspopup="true">
                                <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                                    {{ strtoupper(substr(auth('tenant')->user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <span class="hidden md:block font-medium text-gray-700">
                                    {{ auth('tenant')->user()->name ?? 'Usuario' }}
                                </span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            {{-- Dropdown Menu --}}
                            <div id="user-menu"
                                 class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                                 role="menu">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ auth('tenant')->user()->name ?? 'Usuario' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ auth('tenant')->user()->email ?? 'user@tenant.com' }}
                                    </p>
                                </div>

                                <a href="{{ route('tenant.settings.profile') }}"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                   role="menuitem">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Mi Perfil
                                    </div>
                                </a>

                                <a href="{{ route('tenant.settings.security') }}"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                   role="menuitem">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Seguridad
                                    </div>
                                </a>

                                <div class="border-t border-gray-100"></div>

                                <form method="POST" action="{{ route('tenant.logout') }}" class="block">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                            role="menuitem">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Cerrar Sesión
                                        </div>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Mobile Menu Button --}}
                        <button onclick="toggleMobileMenu()"
                                class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                aria-expanded="false"
                                aria-label="Abrir menú">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile Navigation Menu --}}
            <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="{{ route('tenant.dashboard') }}"
                       class="nav-link block {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Inicio
                    </a>

                    <a href="{{ route('tenant.pos.index') }}"
                       class="nav-link block {{ request()->routeIs('tenant.pos.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Punto de Venta
                    </a>

                    <a href="{{ route('tenant.inventory.index') }}"
                       class="nav-link block {{ request()->routeIs('tenant.inventory.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Inventario
                    </a>

                    <a href="{{ route('tenant.sales.index') }}"
                       class="nav-link block {{ request()->routeIs('tenant.sales.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Ventas
                    </a>

                    <a href="{{ route('tenant.customers.index') }}"
                       class="nav-link block {{ request()->routeIs('tenant.customers.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Clientes
                    </a>

                    <a href="{{ route('tenant.settings.index') }}"
                       class="nav-link block {{ request()->routeIs('tenant.settings.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Configuración
                    </a>
                </div>
            </div>
        </nav>

        {{-- Page Header (Optional) --}}
        @if(isset($header))
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $header }}</h1>
                            @if(isset($subheader))
                                <p class="mt-1 text-sm text-gray-600">{{ $subheader }}</p>
                            @endif
                        </div>

                        @if(isset($headerActions))
                            <div class="flex items-center space-x-3">
                                {{ $headerActions }}
                            </div>
                        @endif
                    </div>
                </div>
            </header>
        @endif

        {{-- Main Content --}}
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @include('tenant.partials.flash-messages')

            {{-- Page Content --}}
            {{ $slot ?? $content ?? '' }}
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col md:flex-row items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center mb-4 md:mb-0">
                        <span>© {{ date('Y') }} {{ $settings->effective_store_name }}</span>
                        <span class="mx-2">•</span>
                        <span>Emporio Digital</span>
                    </div>

                    @if($settings->hasSocialMedia())
                        <div class="flex items-center space-x-4">
                            @if($settings->facebook_url)
                                <a href="{{ $settings->facebook_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-400 hover:text-blue-600 transition-colors"
                                   aria-label="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($settings->instagram_url)
                                <a href="{{ $settings->instagram_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-400 hover:text-pink-600 transition-colors"
                                   aria-label="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($settings->whatsapp_url)
                                <a href="{{ $settings->whatsapp_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-400 hover:text-green-600 transition-colors"
                                   aria-label="WhatsApp">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.548 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts (if needed) -->
    @livewireScripts

    <!-- JavaScript -->
    <script>
        // Toggle user menu
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            const button = menu.previousElementSibling;
            const isHidden = menu.classList.contains('hidden');

            // Close all other dropdowns
            document.querySelectorAll('[id$="-menu"]').forEach(el => {
                el.classList.add('hidden');
            });

            if (isHidden) {
                menu.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        }

        // Toggle mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const button = menu.previousElementSibling.querySelector('button[onclick*="toggleMobileMenu"]');
            const isHidden = menu.classList.contains('hidden');

            if (isHidden) {
                menu.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick*="toggleUserMenu"]') && !event.target.closest('#user-menu')) {
                const userMenu = document.getElementById('user-menu');
                const userButton = userMenu.previousElementSibling;
                userMenu.classList.add('hidden');
                userButton.setAttribute('aria-expanded', 'false');
            }

            if (!event.target.closest('[onclick*="toggleMobileMenu"]') && !event.target.closest('#mobile-menu')) {
                const mobileMenu = document.getElementById('mobile-menu');
                const mobileButton = mobileMenu.previousElementSibling.querySelector('button[onclick*="toggleMobileMenu"]');
                mobileMenu.classList.add('hidden');
                mobileButton.setAttribute('aria-expanded', 'false');
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('[id$="-menu"]').forEach(el => {
                    el.classList.add('hidden');
                });
                document.querySelectorAll('[aria-expanded="true"]').forEach(el => {
                    el.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Auto-hide flash messages
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('[data-flash-message]');
            flashMessages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>