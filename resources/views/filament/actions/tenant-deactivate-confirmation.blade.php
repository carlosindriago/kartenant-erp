<div class="space-y-4">
    <!-- Warning Header -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    Confirmación de Seguridad Requerida
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Esta acción afectará el acceso de todos los usuarios de la tienda <strong>{{ $tenantName }}</strong>.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Consequence Details -->
    <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <h4 class="text-sm font-medium text-red-800 mb-2">Consecuencias de la Desactivación:</h4>
        <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
            <li>Todos los usuarios perderán acceso inmediato al sistema</li>
            <li>Las operaciones comerciales se detendrán completamente</li>
            <li>Los clientes no podrán realizar compras</li>
            <li>La tienda no aparecerá en el listado activo</li>
            <li>Los datos no se eliminarán, pero quedarán inaccesibles</li>
            <li>Reactivación requerirá aprobación manual del administrador</li>
        </ul>
    </div>

    <!-- Confirmation Instructions -->
    <div class="space-y-3">
        <h4 class="text-sm font-medium text-gray-900">Pasos para confirmar:</h4>
        <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
            <li>
                <strong>Motivo:</strong> Especifica por qué estás desactivando esta tienda. Este motivo quedará registrado en el auditoría.
            </li>
            <li>
                <strong>Confirmación del nombre:</strong> Escribe exactamente el nombre de la tienda para demostrar que sabes qué estás haciendo.
            </li>
            <li>
                <strong>Contraseña de administrador:</strong> Confirma tu identidad para verificar que tienes autorización.
            </li>
        </ol>
    </div>

    <!-- Safety Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
        <p class="text-sm text-blue-700">
            <strong>Recomendación:</strong> Si tienes dudas, considera usar el "Modo Mantenimiento" primero, que es una opción temporal y reversible.
        </p>
    </div>

    <!-- Tenant Name Display -->
    <div class="text-center p-3 bg-gray-100 rounded-md">
        <p class="text-xs text-gray-600 mb-1">Nombre exacto a escribir:</p>
        <p class="font-mono font-bold text-lg text-gray-900">{{ $tenantName }}</p>
    </div>
</div>