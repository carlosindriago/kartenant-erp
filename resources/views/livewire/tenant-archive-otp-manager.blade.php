<div>
    <!-- Hidden input for OTP data to be used by JavaScript -->
    <input type="hidden" id="otp-context-code" value="{{ $contextCode }}">
    <input type="hidden" id="otp-expires-at" value="{{ $expiresAt }}">
    <input type="hidden" id="otp-max-attempts" value="{{ $maxAttempts }}">

    <!-- Success Messages -->
    @if($success)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ $success }}
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if($error)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ $error }}
            </div>
        </div>
    @endif

    <!-- OTP Status Display -->
    @if($otpGenerated)
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-blue-900">Código de Verificación Generado</h4>
                    <p class="text-sm text-blue-700 mt-1">
                        Se ha enviado un código de 6 dígitos a tu email administrativo.
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-600 font-mono">{{ $otpCode }}</div>
                    <p class="text-xs text-blue-600">Código de desarrollo</p>
                </div>
            </div>

            <!-- OTP Information -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="bg-white p-3 rounded border border-blue-200">
                    <span class="font-medium text-gray-700">Código de respaldo:</span>
                    <div class="font-mono text-blue-600">{{ $contextCode }}</div>
                </div>
                <div class="bg-white p-3 rounded border border-blue-200">
                    <span class="font-medium text-gray-700">Expira en:</span>
                    <div class="text-blue-600" id="otp-timer">10:00</div>
                </div>
                <div class="bg-white p-3 rounded border border-blue-200">
                    <span class="font-medium text-gray-700">Intentos restantes:</span>
                    <div class="text-blue-600">{{ $maxAttempts }}</div>
                </div>
            </div>

            <!-- Token Email -->
            <div class="mt-4 bg-yellow-50 p-3 rounded border border-yellow-200">
                <span class="font-medium text-gray-700">Token de Email (desarrollo):</span>
                <div class="font-mono text-yellow-700 break-all">{{ $emailToken }}</div>
            </div>
        </div>
    @endif

    <!-- Resend OTP Button -->
    @if($otpGenerated)
        <div class="mb-4">
            <button type="button" wire:click="resendOTP"
                    wire:loading.attr="disabled"
                    class="text-sm bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 disabled:bg-gray-400 transition-colors">
                <span wire:loading>Generando...</span>
                <span wire:loading.remove>Reenviar Código</span>
            </button>
            <p class="text-xs text-gray-500 mt-1">
                Si no recibiste el email, puedes reenviar el código o usar el código de respaldo.
            </p>
        </div>
    @endif

    <!-- Development Info (Remove in production) -->
    @if(config('app.env') !== 'production' && $otpGenerated)
        <div class="mb-4 p-3 bg-gray-100 border border-gray-300 rounded-lg">
            <h5 class="font-medium text-gray-800 mb-2">🔍 Información de Desarrollo</h5>
            <div class="text-xs text-gray-600 space-y-1">
                <div><strong>Email:</strong> {{ $admin->email }}</div>
                <div><strong>Tenant:</strong> {{ $tenant->name }} (ID: {{ $tenant->id }})</div>
                <div><strong>OTP Code:</strong> {{ $otpCode }}</div>
                <div><strong>Context Code:</strong> {{ $contextCode }}</div>
                <div><strong>Email Token:</strong> {{ $emailToken }}</div>
                <div><strong>Expires At:</strong> {{ $expiresAt }}</div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Timer countdown for OTP expiration
    let timeRemaining = 600; // 10 minutes in seconds
    const timerElement = document.getElementById('otp-timer');

    if (timerElement) {
        const interval = setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                // Change color based on time remaining
                if (timeRemaining < 120) {
                    timerElement.className = 'text-red-600 font-bold';
                } else if (timeRemaining < 300) {
                    timerElement.className = 'text-yellow-600 font-medium';
                } else {
                    timerElement.className = 'text-blue-600';
                }
            } else {
                timerElement.textContent = 'Expirado';
                timerElement.className = 'text-red-600 font-bold';
                clearInterval(interval);
            }
        }, 1000);
    }

    // Listen for OTP generation events
    window.addEventListener('otpGenerated', function(event) {
        const data = event.detail;
        console.log('OTP Generated:', data);

        // Update any UI elements that need the OTP data
        const contextCodeInput = document.getElementById('otp-context-code');
        const expiresAtInput = document.getElementById('otp-expires-at');
        const maxAttemptsInput = document.getElementById('otp-max-attempts');

        if (contextCodeInput) contextCodeInput.value = data.context_code;
        if (expiresAtInput) expiresAtInput.value = data.expires_at;
        if (maxAttemptsInput) maxAttemptsInput.value = data.max_attempts;

        // Restart timer if timer element exists
        if (timerElement && data.expires_at) {
            const expiresAt = new Date(data.expires_at);
            const now = new Date();
            timeRemaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
        }
    });
});
</script>