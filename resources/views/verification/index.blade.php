@extends('verification.layout')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-qrcode text-3xl text-blue-600"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Verificar Documento
            </h2>
            <p class="text-gray-600">
                Ingrese el código de verificación del documento para comprobar su autenticidad
            </p>
        </div>

        <form action="{{ route('verify.hash', ['hash' => 'PLACEHOLDER']) }}" method="GET" id="verifyForm" class="space-y-6">
            <div>
                <label for="hash" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-fingerprint text-blue-500"></i> Código de Verificación
                </label>
                <input 
                    type="text" 
                    id="hash" 
                    name="hash" 
                    placeholder="Ingrese el código de 64 caracteres..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                    pattern="[a-f0-9]{64}"
                    required
                    maxlength="64"
                >
                <p class="mt-2 text-sm text-gray-500">
                    El código debe tener exactamente 64 caracteres hexadecimales (0-9, a-f)
                </p>
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
            >
                <i class="fas fa-search"></i>
                Verificar Documento
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-blue-500"></i> ¿Cómo verificar?
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-qrcode text-blue-600"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-1">Escanear QR</h4>
                        <p class="text-sm text-gray-600">
                            Use la cámara de su celular para escanear el código QR del documento
                        </p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-keyboard text-blue-600"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-1">Ingresar Código</h4>
                        <p class="text-sm text-gray-600">
                            Copie y pegue el código de verificación que aparece en el documento
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-blue-50 rounded-lg p-6">
        <div class="flex gap-3">
            <div class="flex-shrink-0">
                <i class="fas fa-shield-check text-2xl text-blue-600"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 mb-2">Sistema de Seguridad</h4>
                <p class="text-sm text-gray-700">
                    Todos los documentos generados por Kartenant están protegidos con un hash único e inmutable. 
                    Cualquier modificación del documento hace que la verificación falle, garantizando su autenticidad.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('verifyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const hash = document.getElementById('hash').value.trim().toLowerCase();
    
    // Validar formato
    if (!/^[a-f0-9]{64}$/.test(hash)) {
        alert('El código debe tener exactamente 64 caracteres hexadecimales (0-9, a-f)');
        return;
    }
    
    // Redirigir a la ruta de verificación
    window.location.href = '{{ url("/verify") }}/' + hash;
});
</script>
@endsection
