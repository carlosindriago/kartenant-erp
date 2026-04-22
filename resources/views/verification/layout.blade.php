<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Documentos - Kartenant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-shield-check"></i> Kartenant
                    </h1>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i> Verificación Pública
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow py-8">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 py-6 mt-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-600">
                <p class="mb-2">
                    <i class="fas fa-lock text-blue-500"></i> Sistema de Verificación de Autenticidad
                </p>
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} Kartenant - Todos los documentos están protegidos con hash blockchain-style
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
