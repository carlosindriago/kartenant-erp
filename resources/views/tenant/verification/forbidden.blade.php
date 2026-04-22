<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white shadow-lg rounded-lg p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-6">
                    <svg class="h-10 w-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Acceso Denegado</h1>
                <p class="text-gray-600 mb-6">No tiene permisos para verificar este documento</p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800 mb-2 font-medium">Permiso requerido:</p>
                    <code class="text-xs bg-white px-3 py-2 rounded border border-yellow-300 block">{{ $required_permission }}</code>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-700 mb-2">Sus roles actuales:</p>
                    @if(!empty($user_roles))
                        <div class="flex flex-wrap gap-2 justify-center">
                            @foreach($user_roles as $role)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $role }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Sin roles asignados</p>
                    @endif
                </div>
                
                <div class="space-y-3 mb-6">
                    <p class="text-sm text-gray-600 font-medium">Para acceder a este documento necesita:</p>
                    <ul class="text-sm text-gray-600 text-left space-y-2">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Tener el permiso específico asignado por el administrador
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Ser gerente, supervisor o administrador del sistema
                        </li>
                    </ul>
                </div>
                
                <div class="border-t pt-6">
                    <p class="text-sm text-gray-600 mb-4">¿Cree que debería tener acceso?</p>
                    <p class="text-xs text-gray-500 mb-4">Contacte a su administrador para solicitar los permisos necesarios</p>
                </div>
                
                <div class="mt-6">
                    <a href="/app" class="inline-flex items-center justify-center w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
