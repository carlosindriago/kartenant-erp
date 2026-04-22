<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Autenticación' }} • {{ $tenant?->display_name ?? $tenant?->name ?? 'Emporio Digital' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Meta tags for mobile optimization -->
    <meta name="theme-color" content="#0EA5E9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $tenant?->display_name ?? 'Emporio' }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/emporio-logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/emporio-logo.svg') }}">
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">
    <!-- Skip to content link for screen readers -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-sky-600 text-white px-4 py-2 rounded-lg">
        Saltar al contenido principal
    </a>

    <!-- Main content area -->
    <main id="main-content" class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8" role="main">
        <div class="w-full max-w-md">
            <!-- Header con Branding del Tenant -->
            <header class="text-center mb-8" role="banner">
                @if($tenant?->logo_url)
                    <img src="{{ asset($tenant->logo_url) }}"
                         alt="{{ $tenant->display_name ?? $tenant->name }}"
                         class="mx-auto h-24 w-auto object-contain mb-4"
                         width="{{ isset($tenant->logo_dimensions['width']) ? $tenant->logo_dimensions['width'] : 120 }}"
                         height="{{ isset($tenant->logo_dimensions['height']) ? $tenant->logo_dimensions['height'] : 120 }}">
                @else
                    <!-- Logo fallback de Emporio -->
                    <div class="mx-auto h-20 w-20 bg-sky-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg" role="img" aria-label="Logo de Emporio Digital">
                        <span class="text-white text-2xl font-bold">ED</span>
                    </div>
                @endif

                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $tenant?->display_name ?? $tenant?->name ?? 'Emporio Digital' }}
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $subtitle ?? 'Inicia sesión para gestionar tu negocio' }}
                </p>
            </header>

            <!-- Authentication Card -->
            <div class="bg-white dark:bg-gray-800 py-8 px-6 shadow-xl rounded-2xl border border-gray-100 dark:border-gray-700" role="region" aria-label="Formulario de autenticación">
                <!-- Status Messages -->
                @if (session('status'))
                    <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg" role="alert" aria-live="polite">
                        <p class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" role="alert" aria-live="assertive">
                        <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">
                            {{ $errorTitle ?? 'Hay un problema con tu información:' }}
                        </p>
                        <ul class="text-sm text-red-500 dark:text-red-400 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Dynamic Content Slot -->
                {{ $slot }}
            </div>

            <!-- Footer Info -->
            <footer class="mt-6 text-center" role="contentinfo">
                @if(request()->email)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ request()->email }}
                    </p>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Dominio: {{ request()->getHost() }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    © {{ date('Y') }} Emporio Digital. Todos los derechos reservados.
                </p>
            </footer>
        </div>
    </main>

    <!-- Alpine.js for reactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Focus management and accessibility enhancements -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus first input when page loads
            const firstInput = document.querySelector('input:not([type="hidden"]):not([disabled])');
            if (firstInput) {
                firstInput.focus();
            }

            // Add loading states to forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                let isSubmitting = false;

                form.addEventListener('submit', function() {
                    if (isSubmitting) return;
                    isSubmitting = true;

                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        const originalText = submitButton.textContent;
                        submitButton.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        `;

                        // Reset after 10 seconds max (in case of network issues)
                        setTimeout(() => {
                            submitButton.disabled = false;
                            submitButton.textContent = originalText;
                            isSubmitting = false;
                        }, 10000);
                    }
                });
            });

            // Enhanced error handling
            const errorElements = document.querySelectorAll('[role="alert"]');
            errorElements.forEach(element => {
                // Announce to screen readers
                element.setAttribute('aria-live', 'assertive');

                // Auto-hide success messages after 5 seconds
                if (element.classList.contains('bg-green-50')) {
                    setTimeout(() => {
                        element.style.display = 'none';
                    }, 5000);
                }
            });
        });
    </script>

    <!-- Additional scripts passed from child views -->
    @stack('scripts')
</body>
</html>