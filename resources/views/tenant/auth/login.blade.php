<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // DEBUG: Log cuando se carga la vista de login
        \Log::info('LOGIN VIEW LOADED', [
            'tenant' => $tenant?->domain ?? 'none',
            'settings' => $settings ? 'loaded' : 'missing',
            'url' => request()->fullUrl()
        ]);

        // Get store settings with fallback robusto
        try {
            $storeName = $settings->effective_store_name ?? ($tenant?->display_name ?? $tenant?->name ?? 'Emporio Digital');
            $storeSlogan = $settings->effective_store_slogan ?? 'Inicia sesión para gestionar tu negocio';
            $brandColor = $settings->effective_brand_color ?? '#2563eb';
            $logoUrl = $settings->logo_url ?? $tenant?->logo_url ?? null;
        } catch (Exception $e) {
            $storeName = $tenant?->display_name ?? $tenant?->name ?? 'Emporio Digital';
            $storeSlogan = 'Inicia sesión para gestionar tu negocio';
            $brandColor = '#2563eb';
            $logoUrl = $tenant?->logo_url ?? null;
        }

        $title = 'Iniciar Sesión • ' . $storeName;
    @endphp

    <title>{{ $title }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $brandColor }}',
                    },
                }
            }
        }
    </script>

    <!-- Custom CSS -->
    <style>
        /* CSS Custom Properties for Dynamic Theming */
        :root {
            --primary-color: {{ $brandColor }};
            --primary-light: {{ $brandColor }}15;
            --primary-hover: {{ $brandColor }}dd;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Business Icon Styles */
        .business-icon {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .business-icon:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.15);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .input-focus:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px {{ $brandColor }}20;
        }

        .link-primary {
            color: var(--primary-color);
        }

        .link-primary:hover {
            color: var(--primary-hover);
        }
    </style>
</head>

<body class="hero-pattern font-sans antialiased min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md fade-in">
            <!-- Header con Branding del Tenant -->
            <div class="text-center mb-8">
                <div class="mb-6 floating">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}"
                             alt="{{ $storeName }}"
                             class="w-20 h-20 mx-auto rounded-2xl shadow-2xl object-contain p-3 business-icon">
                    @else
                        <div class="business-icon w-20 h-20 mx-auto rounded-2xl shadow-2xl flex items-center justify-center">
                            <span class="text-3xl font-bold text-white">{{ strtoupper(substr($storeName, 0, 1)) }}</span>
                        </div>
                    @endif
                </div>

                <h2 class="text-3xl font-bold mb-2" style="color: var(--primary-color);">
                    {{ $storeName }}
                </h2>
                <p class="text-white text-opacity-90">
                    {{ $storeSlogan }}
                </p>
            </div>

            <!-- Card de Login -->
            <div class="login-card py-8 px-6 rounded-2xl">
                <!-- Status Messages -->
                @if (session('status'))
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-600">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600 font-medium mb-1">
                            {{ __('Hay un problema con tu información:') }}
                        </p>
                        <ul class="text-sm text-red-500 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('tenant.login') }}" class="space-y-6" x-data="loginForm()">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            📧 Correo Electrónico
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            value="{{ old('email') }}"
                            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg input-focus transition-colors bg-white placeholder-gray-400"
                            placeholder="correo@ejemplo.com"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            🔒 Contraseña
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full px-4 py-3 text-base border border-gray-300 rounded-lg input-focus transition-colors bg-white placeholder-gray-400"
                            placeholder="••••••••"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember_me"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 text-white focus:ring-white border-gray-300 rounded"
                            style="accent-color: var(--primary-color);"
                        >
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                            Recordar mi sesión
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="processing"
                    >
                        <span x-show="!processing">Iniciar Sesión</span>
                        <span x-show="processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </button>

                    <!-- Forgot Password -->
                    @if (Route::has('tenant.password.request'))
                        {{-- TODO: Implement tenant password reset functionality
                        <div class="text-center">
                            <a
                                href="{{ route('tenant.password.request') }}"
                                class="text-sm link-primary font-medium transition-colors"
                            >
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        --}}
                    @endif
                </form>
            </div>

            <!-- Footer Info -->
            <div class="mt-6 text-center">
                <p class="text-xs text-white text-opacity-70">
                    Dominio: {{ request()->getHost() }}
                </p>
                <p class="text-xs text-white text-opacity-60 mt-1">
                    © {{ date('Y') }} {{ $storeName }} • Powered by Emporio Digital
                </p>
            </div>
        </div>
    </div>

    <!-- Alpine.js para estados interactivos -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginForm', () => ({
                processing: false,

                init() {
                    this.$el.addEventListener('submit', () => {
                        this.processing = true;
                        setTimeout(() => {
                            this.processing = false;
                        }, 10000); // Reset after 10 seconds max
                    });
                }
            }));
        });

        // Add entrance animation
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';

            setTimeout(() => {
                card.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>