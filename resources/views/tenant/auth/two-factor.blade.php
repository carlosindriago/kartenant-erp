<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificación en Dos Pasos • {{ $tenant?->display_name ?? $tenant?->name ?? 'Emporio Digital' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Animaciones CSS -->
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }

        @keyframes success {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }

        .success-animation {
            animation: success 0.3s ease-in-out;
        }

        .input-otp {
            width: 3rem;
            height: 3.5rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            .input-otp {
                width: 2.5rem;
                height: 3rem;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Header con Branding del Tenant -->
            <div class="text-center mb-8">
                @if($tenant?->logo_url)
                    <img src="{{ asset($tenant->logo_url) }}"
                         alt="{{ $tenant->display_name ?? $tenant->name }}"
                         class="mx-auto h-16 w-auto object-contain mb-4">
                @else
                    <!-- Logo fallback de Emporio -->
                    <div class="mx-auto h-16 w-16 bg-sky-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <span class="text-white text-xl font-bold">ED</span>
                    </div>
                @endif

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Verificación en Dos Pasos
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Ingresa el código de 6 dígitos que enviamos a tu email
                </p>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    ⚠️ Tienes 3 intentos. Al fallar 3 veces, tu cuenta se bloqueará por 24 horas.
                </p>
            </div>

            <!-- Card de 2FA -->
            <div class="bg-white dark:bg-gray-800 py-8 px-6 shadow-xl rounded-2xl border border-gray-100 dark:border-gray-700">
                <!-- Status Messages -->
                @if (session('status'))
                    <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</p>
                    </div>
                @endif

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">
                            {{ __('Código incorrecto') }}
                        </p>
                        <p class="text-sm text-red-500 dark:text-red-400">
                            El código que ingresaste no es válido. Por favor, inténtalo de nuevo.
                        </p>
                    </div>
                @endif

                <!-- 2FA Form -->
                <form method="POST" action="{{ route('tenant.2fa') }}" x-data="twoFactorForm()" class="space-y-6">
                    @csrf

                    <!-- Email Hidden Field -->
                    <input type="hidden" name="email" value="{{ $email ?? request()->email }}">

                    <!-- OTP Input Fields -->
                    <div class="space-y-4">
                        <div class="flex justify-center space-x-2 sm:space-x-3" id="otp-container">
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input0"
                                @input="handleInput($event, 0)"
                                @keydown.backspace="handleBackspace($event, 0)"
                                @paste="handlePaste($event)"
                            >
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input1"
                                @input="handleInput($event, 1)"
                                @keydown.backspace="handleBackspace($event, 1)"
                            >
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input2"
                                @input="handleInput($event, 2)"
                                @keydown.backspace="handleBackspace($event, 2)"
                            >
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input3"
                                @input="handleInput($event, 3)"
                                @keydown.backspace="handleBackspace($event, 3)"
                            >
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input4"
                                @input="handleInput($event, 4)"
                                @keydown.backspace="handleBackspace($event, 4)"
                            >
                            <input
                                type="text"
                                                                maxlength="1"
                                pattern="[0-9]"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="input-otp px-3 py-2 text-base border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-center font-mono transition-colors"
                                x-ref="input5"
                                @input="handleInput($event, 5)"
                                @keydown.backspace="handleBackspace($event, 5)"
                                @input.debounce.300ms="checkComplete()"
                            >
                        </div>

                        <!-- Hidden Input for Full Code -->
                        <input type="hidden" name="code" x-model="fullCode">
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="processing || !isComplete"
                        :class="processing || !isComplete ? 'opacity-50 cursor-not-allowed' : 'hover:bg-sky-700'"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-sky-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors"
                    >
                        <span x-show="!processing">Verificar Código</span>
                        <span x-show="processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verificando...
                        </span>
                    </button>

                    <!-- Additional Actions -->
                    <div class="flex flex-col space-y-3 text-center">
                        <!-- Resend Code -->
                        <div>
                            <button
                                type="button"
                                @click="resendCode()"
                                :disabled="resendCountdown > 0"
                                :class="resendCountdown > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300'"
                                class="text-sm font-medium transition-colors"
                            >
                                <span x-show="resendCountdown === 0">Reenviar código</span>
                                <span x-show="resendCountdown > 0" x-text="`Reenviar código (\${resendCountdown}s)`"></span>
                            </button>
                        </div>

                        <!-- Back to Login -->
                        <div>
                            <a
                                href="{{ route('tenant.login') }}"
                                class="text-sm text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                            >
                                ← Volver al inicio de sesión
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer Info -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $email ?? request()->email }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    © {{ date('Y') }} Emporio Digital. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function twoFactorForm() {
            return {
                processing: false,
                fullCode: '',
                resendCountdown: 0,
                isComplete: false,

                init() {
                    // Focus first input
                    this.$refs.input0.focus();

                    // Start countdown if needed (from session or previous attempt)
                    const countdownTime = sessionStorage.getItem('otpResendCountdown');
                    if (countdownTime && parseInt(countdownTime) > Date.now()) {
                        this.startResendCountdown(Math.floor((parseInt(countdownTime) - Date.now()) / 1000));
                    }

                    // Handle form submission
                    this.$el.addEventListener('submit', () => {
                        this.processing = true;
                    });

                    // Handle errors from server
                    @if ($errors->any())
                        this.shakeInputs();
                    @endif
                },

                handleInput(event, index) {
                    const input = event.target;
                    const value = input.value;

                    // Only allow numbers
                    if (!/^\d*$/.test(value)) {
                        input.value = '';
                        return;
                    }

                    // Move to next input if value is entered
                    if (value && index < 5) {
                        this.$refs[`input${index + 1}`].focus();
                    }

                    // Update full code and check completion
                    this.updateFullCode();
                },

                handleBackspace(event, index) {
                    const input = event.target;

                    // If current input is empty and not the first one, move to previous
                    if (!input.value && index > 0) {
                        event.preventDefault();
                        this.$refs[`input${index - 1}`].focus();
                    }
                },

                handlePaste(event) {
                    event.preventDefault();
                    const pastedData = event.clipboardData.getData('text').trim();

                    // Only allow 6 digits
                    const digits = pastedData.replace(/\D/g, '').slice(0, 6);

                    if (digits.length === 6) {
                        // Fill all inputs
                        for (let i = 0; i < 6; i++) {
                            this.$refs[`input${i}`].value = digits[i];
                        }
                        this.updateFullCode();
                        this.$refs.input5.focus();
                    }
                },

                updateFullCode() {
                    const code = [];
                    for (let i = 0; i < 6; i++) {
                        const value = this.$refs[`input${i}`].value;
                        if (value) {
                            code.push(value);
                        }
                    }
                    this.fullCode = code.join('');
                    this.isComplete = code.length === 6;
                },

                checkComplete() {
                    if (this.isComplete) {
                        // Auto-submit when complete (optional)
                        // this.$el.submit();
                    }
                },

                shakeInputs() {
                    const container = document.getElementById('otp-container');
                    container.classList.add('shake-animation');
                    setTimeout(() => {
                        container.classList.remove('shake-animation');
                    }, 500);
                },

                async resendCode() {
                    if (this.resendCountdown > 0) return;

                    try {
                        const response = await fetch('{{ route("tenant.2fa.resend") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                email: '{{ $email ?? request()->email }}'
                            })
                        });

                        if (response.ok) {
                            // Start countdown
                            this.startResendCountdown(60);

                            // Clear inputs
                            for (let i = 0; i < 6; i++) {
                                this.$refs[`input${i}`].value = '';
                            }
                            this.fullCode = '';
                            this.isComplete = false;
                            this.$refs.input0.focus();

                            // Show success message (you might want to add a toast notification)
                            console.log('Código reenviado exitosamente');
                        }
                    } catch (error) {
                        console.error('Error al reenviar código:', error);
                    }
                },

                startResendCountdown(seconds) {
                    this.resendCountdown = seconds;
                    const expiryTime = Date.now() + (seconds * 1000);
                    sessionStorage.setItem('otpResendCountdown', expiryTime);

                    const countdown = setInterval(() => {
                        this.resendCountdown--;
                        if (this.resendCountdown <= 0) {
                            clearInterval(countdown);
                            sessionStorage.removeItem('otpResendCountdown');
                        }
                    }, 1000);
                }
            }
        }
    </script>
</body>
</html>