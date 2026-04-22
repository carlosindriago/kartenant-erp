<div class="qr-verification text-center">
    <h3 class="text-lg font-semibold mb-2">{{ $title }}</h3>
    
    <div class="qr-code mb-3">
        <img src="{{ $qr }}" alt="QR de Verificación" class="mx-auto" style="max-width: 100%; height: auto;">
    </div>
    
    <div class="verification-info">
        <p class="text-sm text-gray-600 mb-2">
            Escanea este código QR para verificar la autenticidad del documento
        </p>
        
        <div class="hash-display bg-gray-100 p-2 rounded mb-2">
            <code class="text-xs break-all">{{ $hash }}</code>
        </div>
        
        <a href="{{ $url }}" 
           class="text-blue-600 hover:text-blue-800 text-sm underline" 
           target="_blank">
            {{ $url }}
        </a>
    </div>
</div>
