<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @php
        use App\Models\StoreSetting;

        // Get store settings with fallback robusto
        try {
            $settings = StoreSetting::current();
            $storeName = $settings->effective_store_name ?? 'Frutería Test';
            $storeSlogan = $settings->effective_store_slogan ?? 'Bienvenido a nuestra tienda';
            $brandColor = $settings->effective_brand_color ?? '#2563eb';
            $logoUrl = $settings->logo_url ?? null;
        } catch (Exception $e) {
            $storeName = 'Frutería Test';
            $storeSlogan = 'Bienvenido a nuestra tienda';
            $brandColor = '#2563eb';
            $logoUrl = null;
        }

        $title = $storeName;
    @endphp

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $storeName }} - {{ $storeSlogan }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl ?? asset('images/default-store-logo.png') }}">

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

        .store-info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="hero-pattern min-h-screen flex items-center justify-center p-4">

    {{-- Main Container --}}
    <div class="w-full max-w-2xl fade-in">

        {{-- Store Welcome Card --}}
        <div class="store-info-card rounded-3xl p-8 md:p-12 text-center">

            {{-- Store Logo/Icon --}}
            <div class="mb-8 floating">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}"
                         alt="{{ $storeName }}"
                         class="w-24 h-24 md:w-32 md:h-32 mx-auto rounded-2xl shadow-2xl object-contain p-3 business-icon">
                @else
                    <div class="business-icon w-24 h-24 md:w-32 md:h-32 mx-auto rounded-2xl shadow-2xl flex items-center justify-center">
                        <span class="text-4xl md:text-5xl font-bold text-white">{{ strtoupper(substr($storeName, 0, 1)) }}</span>
                    </div>
                @endif
            </div>

            {{-- Store Name --}}
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-900" style="color: var(--primary-color);">
                {{ $storeName }}
            </h1>

            {{-- Store Slogan/Welcome Message --}}
            <p class="text-xl md:text-2xl mb-8 text-gray-600 max-w-md mx-auto leading-relaxed">
                {{ $storeSlogan }}
            </p>

            {{-- Simple Welcome Message --}}
            <div class="mb-8 p-4 bg-gray-50 rounded-xl">
                <p class="text-gray-700 text-base md:text-lg">
                    Gracias por visitarnos. Estamos comprometidos con ofrecerle los mejores productos y servicios.
                </p>
            </div>

            {{-- Login Button --}}
            <div class="mt-8">
                <a href="{{ route('tenant.login') }}"
                   class="inline-flex items-center px-8 py-4 text-lg font-semibold text-white rounded-2xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl focus:outline-none focus:ring-4 focus:ring-white focus:ring-opacity-30"
                   style="background: var(--primary-color);">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Iniciar Sesión
                </a>
            </div>

            {{-- Optional: Contact Info (if available) --}}
            @if(config('app.env') === 'local')
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">
                        Esta es la página de bienvenida de {{ $storeName }}
                    </p>
                </div>
            @endif

        </div>

        {{-- Simple Footer --}}
        <div class="text-center mt-8 text-white text-opacity-80">
            <p class="text-sm">
                © {{ date('Y') }} {{ $storeName }} • Todos los derechos reservados
            </p>
        </div>
    </div>

    <!-- JavaScript for basic interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add subtle entrance animation
            const card = document.querySelector('.store-info-card');
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