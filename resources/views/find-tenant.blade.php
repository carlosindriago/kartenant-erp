<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceder a tu Panel - Kartenant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}">
                 <svg class="mx-auto h-16 w-auto text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 11.25h6m-6 4.5h6M5.25 6h.008v.008H5.25V6Zm.75 4.5h.008v.008H6v-.008Zm-.75 4.5h.008v.008H5.25v-.008Zm12.75-9h.008v.008h-.008V6Zm-.75 4.5h.008v.008h-.008v-.008Zm.75 4.5h.008v.008h-.008v-.008Z" />
                </svg>
            </a>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-800 dark:text-gray-200">
                Accede a tu Espacio de Trabajo
            </h1>
            <p class="mt-2 text-md text-gray-600 dark:text-gray-400">
                Introduce el dominio único de tu empresa para continuar.
            </p>
        </div>

        <div class="w-full max-w-sm bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
            <form action="{{ route('tenant.login.redirect') }}" method="POST">
                @csrf
                <div>
                    <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dominio de tu empresa</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="text" name="domain" id="domain" class="block w-full rounded-none rounded-l-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm" placeholder="mi-tienda" required>
                        <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 text-gray-500 dark:text-gray-400 sm:text-sm">.kartenant.test</span>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>