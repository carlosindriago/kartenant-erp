<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demasiadas solicitudes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <div class="flex justify-center mb-6">
            <svg class="w-16 h-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-4">
            Demasiadas Solicitudes
        </h1>
        
        <p class="text-gray-600 text-center mb-6">
            {{ $message ?? 'Has realizado demasiadas solicitudes en poco tiempo.' }}
        </p>
        
        @if(isset($retryAfter))
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-yellow-800">
                <strong>Por favor, espera {{ $retryAfter }} segundos</strong> antes de intentar nuevamente.
            </p>
        </div>
        @endif
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                <strong>¿Por qué veo esto?</strong><br>
                Tenemos límites de seguridad para proteger el sistema contra ataques automatizados.
            </p>
        </div>
        
        <div class="flex justify-center">
            <a href="{{ url('/') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>
