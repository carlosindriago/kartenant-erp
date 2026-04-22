@props(['tenant', 'userCount', 'dataAge'])

<div class="space-y-6">
    <!-- MAXIMUM CRITICAL WARNING -->
    <div class="bg-red-100 border-4 border-red-500 rounded-lg p-6 shadow-lg">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div class="bg-red-500 rounded-full p-3">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-red-900">🚨 ACCIÓN CRÍTICA MÁXIMA - IRREVERSIBLE</h3>
                <p class="mt-2 text-lg text-red-800 font-semibold">
                    ESTÁS A PUNTO DE ARCHIVAR PERMANENTEMENTE UN TENANT ACTIVO
                </p>
                <p class="mt-2 text-red-700">
                    Esta acción tiene consecuencias <strong>PERMANENTES</strong> y <strong>CASI IRREVERSIBLES</strong>.
                    Requerirá intervención técnica especializada para deshacer.
                </p>
            </div>
        </div>
    </div>

    <!-- TENANT IMPACT ASSESSMENT -->
    <div class="bg-red-50 rounded-lg p-6 border-2 border-red-300">
        <h4 class="font-bold text-red-900 mb-4 text-lg">📊 Evaluación de Impacto del Tenant</h4>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white rounded-lg p-4 border border-red-200">
                <div class="text-2xl font-bold text-red-600">{{ $tenant->name }}</div>
                <div class="text-sm text-gray-600">Nombre del Tenant</div>
            </div>
            <div class="bg-white rounded-lg p-4 border border-red-200">
                <div class="text-2xl font-bold text-red-600">{{ $userCount }}</div>
                <div class="text-sm text-gray-600">Usuarios Afectados</div>
            </div>
            <div class="bg-white rounded-lg p-4 border border-red-200">
                <div class="text-2xl font-bold text-red-600">{{ $dataAge }} días</div>
                <div class="text-sm text-gray-600">Historial de Datos</div>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
            <h5 class="font-semibold text-yellow-800 mb-2">⚠️ Impacto Inmediato en Usuarios</h5>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• <strong>Todos los {{ $userCount }} usuarios</strong> perderán acceso permanente</li>
                <li>• <strong>Secciones comerciales se detendrán</strong> inmediatamente</li>
                <li>• <strong>Datos históricos de {{ $dataAge }} días</strong> serán archivados</li>
                <li>• <strong>Integraciones externas</strong> serán interrumpidas</li>
            </ul>
        </div>
    </div>

    <!-- LEGAL & COMPLIANCE IMPLICATIONS -->
    <div class="bg-purple-50 rounded-lg p-6 border-2 border-purple-300">
        <h4 class="font-bold text-purple-900 mb-4 text-lg">⚖️ Implicaciones Legales y de Cumplimiento</h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg p-4 border border-purple-200">
                <h5 class="font-semibold text-purple-800 mb-2">🔍 GDPR y Privacidad</h5>
                <ul class="text-sm text-purple-700 space-y-1">
                    <li>• Derechos de acceso del usuario</li>
                    <li>• Períodos de retención obligatorios</li>
                    <li>• Requisitos de notificación</li>
                    <li>• Derechos de portabilidad de datos</li>
                </ul>
            </div>

            <div class="bg-white rounded-lg p-4 border border-purple-200">
                <h5 class="font-semibold text-purple-800 mb-2">📋 Requerimientos Contractuales</h5>
                <ul class="text-sm text-purple-700 space-y-1">
                    <li>• Períodos de preaviso contractuales</li>
                    <li>• Obligaciones de retención de datos</li>
                    <li>• Procedimientos de disputa</li>
                    <li>• Responsabilidades post-terminación</li>
                </ul>
            </div>
        </div>

        <div class="mt-4 bg-red-100 border border-red-300 rounded-lg p-3">
            <p class="text-sm text-red-800 font-medium">
                ⚖️ <strong>Importante:</strong> Asegúrate de cumplir con todos los requisitos legales antes de proceder.
                La no conformidad puede resultar en sanciones regulatorias significativas.
            </p>
        </div>
    </div>

    <!-- SECURITY VERIFICATION PROTOCOL -->
    <div class="bg-blue-50 rounded-lg p-6 border-2 border-blue-300">
        <h4 class="font-bold text-blue-900 mb-4 text-lg">🔐 Protocolo de Verificación de Seguridad</h4>

        <div class="space-y-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">1</div>
                </div>
                <div>
                    <h5 class="font-semibold text-blue-800">Evaluación de Impacto Detallada</h5>
                    <p class="text-sm text-blue-700">Se requiere una evaluación exhaustiva del impacto en usuarios, negocio y sistemas.</p>
                </div>
            </div>

            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">2</div>
                </div>
                <div>
                    <h5 class="font-semibold text-blue-800">Verificación de Cumplimiento Legal</h5>
                    <p class="text-sm text-blue-700">Confirmación explícita del cumplimiento con GDPR, contratos y políticas internas.</p>
                </div>
            </div>

            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">3</div>
                </div>
                <div>
                    <h5 class="font-semibold text-blue-800">Autenticación Multi-Factor</h5>
                    <p class="text-sm text-blue-700">Contraseña + OTP + Verificación por Email + Aprobación de Pares (si aplica).</p>
                </div>
            </div>

            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">4</div>
                </div>
                <div>
                    <h5 class="font-semibold text-blue-800">Confirmación Explícita</h5>
                    <p class="text-sm text-blue-700">Reconocimiento explícito de la irreversibilidad y aceptación de responsabilidad.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FINAL CRITICAL WARNING -->
    <div class="bg-red-100 border-4 border-red-500 rounded-lg p-6">
        <div class="text-center">
            <h4 class="text-xl font-bold text-red-900 mb-3">⚠️ ADVERTENCIA FINAL</h4>
            <p class="text-lg text-red-800 font-semibold mb-4">
                Esta acción está siendo monitoreada en tiempo real y registrada permanentemente.
            </p>
            <div class="bg-red-200 rounded-lg p-4">
                <p class="text-red-900 font-medium">
                    <strong>Si tienes dudas, cierra esta ventana ahora.</strong><br>
                    Esta acción puede tener consecuencias legales y comerciales significativas.
                </p>
            </div>
        </div>
    </div>

    <!-- Emergency Contact Information -->
    <div class="bg-gray-100 rounded-lg p-4 border border-gray-300">
        <h5 class="font-semibold text-gray-800 mb-2">🚨 Contacto de Emergencia</h5>
        <p class="text-sm text-gray-700">
            Si esta acción es una emergencia o hay riesgos de seguridad inminentes,
            contacta inmediatamente al equipo de seguridad: <strong>security@emporiodigital.com</strong>
        </p>
    </div>
</div>