@props(['tenant', 'userCount', 'hasActiveSubscription', 'subscriptionEndsAt'])

<div class="space-y-4">
    <!-- CRITICAL WARNING BANNER -->
    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-red-800">⚠️ ACCIÓN CRÍTICA DE ALTO RIESGO</h3>
                <p class="mt-1 text-sm text-red-700">
                    Estás a punto de desactivar un tenant activo. Esta acción tiene consecuencias inmediatas y reversibles.
                </p>
            </div>
        </div>
    </div>

    <!-- TENANT INFORMATION -->
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <h4 class="font-semibold text-gray-800 mb-3">📋 Información del Tenant</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">Nombre:</span>
                <span class="ml-2 text-gray-900">{{ $tenant->name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Dominio:</span>
                <span class="ml-2 text-gray-900">{{ $tenant->domain }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Usuarios:</span>
                <span class="ml-2 text-gray-900">{{ $userCount }} usuario(s)</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Suscripción:</span>
                <span class="ml-2">
                    @if($hasActiveSubscription)
                        <span class="text-green-600 font-medium">Activa</span>
                        @if($subscriptionEndsAt)
                            <span class="text-gray-500"> (vence: {{ $subscriptionEndsAt->format('d/m/Y') }})</span>
                        @endif
                    @else
                        <span class="text-red-600 font-medium">Sin suscripción</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- CONSEQUENCES SECTION -->
    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
        <h4 class="font-semibold text-yellow-800 mb-3">🚨 Consecuencias Inmediatas</h4>
        <ul class="space-y-2 text-sm text-yellow-700">
            <li class="flex items-start space-x-2">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><strong>Todos los {{ $userCount }} usuarios perderán acceso inmediato</strong> al sistema</li>
            </li>
            <li class="flex items-start space-x-2">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><strong>Las operaciones comerciales se detendrán</strong> (ventas, inventario, etc.)</li>
            </li>
            <li class="flex items-start space-x-2">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><strong>Se creará un backup automático</strong> antes de la desactivación</li>
            </li>
            <li class="flex items-start space-x-2">
                <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><strong>La reactivación requerirá aprobación manual</strong> de un superadmin</li>
            </li>
            @if($hasActiveSubscription)
                <li class="flex items-start space-x-2">
                    <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span><strong>La suscripción activa será pausada</strong> pero no cancelada</li>
                </li>
            @endif
        </ul>
    </div>

    <!-- SECURITY VERIFICATION REQUIREMENTS -->
    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
        <h4 class="font-semibold text-blue-800 mb-3">🔐 Verificaciones de Seguridad Requeridas</h4>
        <ol class="space-y-2 text-sm text-blue-700 list-decimal list-inside">
            <li><strong>Contraseña de Administrador:</strong> Confirma tu identidad</li>
            <li><strong>Confirmación del Tenant:</strong> Escribe el nombre exacto y dominio</li>
            <li><strong>Código OTP:</strong> Código de un solo uso específico para esta operación</li>
            <li><strong>Aceptación de Consecuencias:</strong> Reconocimiento explícito del impacto</li>
            <li><strong>Registro de Auditoría:</strong> Toda acción quedará permanentemente registrada</li>
        </ol>
    </div>

    <!-- REVERSIBILITY INFORMATION -->
    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
        <h4 class="font-semibold text-green-800 mb-2">♻️ Información de Reversibilidad</h4>
        <p class="text-sm text-green-700">
            Esta acción <strong>PUEDE SER REVERTIDA</strong>. Para reactivar el tenant:
        </p>
        <ul class="mt-2 space-y-1 text-sm text-green-600 list-disc list-inside">
            <li>Contacta a un administrador del sistema</li>
            <li>Solicita la reactivación con motivo justificado</li>
            <li>El proceso de reactivación puede tomar hasta 24 horas</li>
            <li>Todos los datos serán conservados intactos</li>
        </ul>
    </div>

    <!-- FINAL WARNING -->
    <div class="mt-6 p-3 bg-red-100 border border-red-300 rounded-lg">
        <p class="text-xs text-red-800 text-center font-medium">
            ⚠️ Esta acción está siendo monitoreada y registrada con fines de seguridad y auditoría.
        </p>
    </div>
</div>