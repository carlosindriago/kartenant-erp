<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartenant - Tu Solución de Gestión</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white dark:bg-gray-900">

    {{-- Contenedor Principal --}}
    <div class="flex flex-col min-h-screen">

        {{-- 1. Barra de Navegación Profesional --}}
        <header class="bg-white dark:bg-gray-900 shadow-sm sticky top-0 z-50">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    {{-- Logo y Nombre --}}
                    <div class="flex items-center space-x-3">
                         <svg class="h-10 w-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 11.25h6m-6 4.5h6M5.25 6h.008v.008H5.25V6Zm.75 4.5h.008v.008H6v-.008Zm-.75 4.5h.008v.008H5.25v-.008Zm12.75-9h.008v.008h-.008V6Zm-.75 4.5h.008v.008h-.008v-.008Zm.75 4.5h.008v.008h-.008v-.008Z" />
                        </svg>
                        <span class="text-2xl font-bold text-gray-800 dark:text-white">Kartenant</span>
                    </div>
                    {{-- Botones de Acción --}}
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('tenant.login.form') }}" class="text-sm font-semibold text-gray-600 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400">
                            Iniciar Sesión
                        </a>
                        <a href="{{ route('register') }}" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                            Registrarse
                        </a>
                    </div>
                </div>
            </div>
        </header>

        {{-- Contenido Principal --}}
        <main class="flex-grow">
            @if(request()->boolean('tenant_not_found'))
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800/50 dark:bg-red-900/30 dark:text-red-200">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8.75-3a.75.75 0 011.5 0v4.5a.75.75 0 01-1.5 0V7zm.75 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-semibold">No encontramos tu empresa</p>
                                <p class="text-sm mt-1">El espacio de trabajo “{{ request('t') }}” no existe. Si crees que es un error, por favor contacta a Soporte Técnico.</p>
                                <div class="mt-3 flex flex-wrap gap-3">
                                    <a href="https://kartenant.test/" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">Ir a la página principal</a>
                                    <a href="https://wa.me/5491122334455?text=Hola%20necesito%20ayuda%20con%20mi%20empresa%20{{ urlencode(request('t')) }}" class="inline-flex items-center rounded-md border border-amber-200 px-3 py-1.5 text-sm font-semibold text-amber-700 hover:bg-amber-50">Contactar soporte por WhatsApp</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            {{-- 2. Sección 'Héroe' con Impacto Visual --}}
            <section class="relative bg-gray-50 dark:bg-gray-800/50 py-20 sm:py-32">
                
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                        <span>La Gestión de tu Negocio,</span>
                        <span class="text-amber-600 dark:text-amber-500">Simplificada.</span>
                    </h1>
                    <p class="mt-6 max-w-2xl mx-auto text-lg leading-8 text-gray-600 dark:text-gray-300">
                        Desde el control de tu inventario hasta el último ticket de venta, Kartenant es la herramienta todo-en-uno que tu PyME necesita para crecer.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ route('register') }}" class="rounded-md bg-amber-600 px-5 py-3 text-base font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                            Comenzar ahora (Es Gratis)
                        </a>
                    </div>
                </div>
            </section>

            {{-- 3. Sección de Características con Iconos --}}
            <section class="bg-white dark:bg-gray-900 py-24 sm:py-32">
                <div class="container mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="text-center">
                        <h2 class="text-base font-semibold leading-7 text-amber-600">Todo lo que necesitas</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">Una plataforma para gobernarlos a todos</p>
                        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">Organiza, vende y crece. Así de simple.</p>
                    </div>
                    <div class="mt-20 grid grid-cols-1 gap-x-8 gap-y-16 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Característica 1: Inventario --}}
                        <div class="flex flex-col items-center text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-600 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold leading-6 text-gray-900 dark:text-white">Control de Inventario</h3>
                            <p class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Gestiona tus productos, categorías y stock en un solo lugar. Recibe alertas de bajo inventario y nunca pierdas una venta.</p>
                        </div>
                        {{-- Característica 2: Punto de Venta --}}
                        <div class="flex flex-col items-center text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-600 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15A2.25 2.25 0 002.25 6.75v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold leading-6 text-gray-900 dark:text-white">Punto de Venta (POS)</h3>
                            <p class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Una interfaz rápida e intuitiva para registrar tus ventas. Busca productos, añade clientes y genera comprobantes en segundos.</p>
                        </div>
                        {{-- Característica 3: Reportes --}}
                        <div class="flex flex-col items-center text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-600 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1.5-1.5m1.5 1.5l1.5-1.5m0 0l-1.5-1.5m1.5 1.5l1.5 1.5M3 13.5h18" /></svg>
                            </div>
                            <h3 class="mt-5 text-lg font-semibold leading-6 text-gray-900 dark:text-white">Reportes Inteligentes</h3>
                            <p class="mt-2 text-base leading-7 text-gray-600 dark:text-gray-400">Toma decisiones basadas en datos. Visualiza tus ventas, identifica tus productos estrella y entiende el pulso de tu negocio.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- 4. Pie de Página Estructurado --}}
        <footer class="bg-gray-100 dark:bg-gray-800">
            <div class="container mx-auto px-6 py-8">
                <div class="flex flex-col items-center text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">© {{ date('Y') }} Kartenant. Todos los derechos reservados.</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Forjado con ❤️ y Laravel.</p>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>