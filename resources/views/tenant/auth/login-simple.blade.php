<x-tenant.auth.layouts.auth title="Iniciar Sesión">
    <form method="POST" action="{{ route('tenant.login') }}" class="space-y-6" x-data="loginForm()">
        @csrf

        <!-- Email Field -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                📧 Correo Electrónico
            </label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required
                value="{{ old('email') }}"
                class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                placeholder="correo@ejemplo.com"
                aria-describedby="email-help"
            >
            @if ($errors->has('email'))
                <p id="email-help" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ $errors->first('email') }}
                </p>
            @endif
        </div>

        <!-- Password Field -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🔒 Contraseña
            </label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
                placeholder="••••••••"
                aria-describedby="password-help"
            >
            @if ($errors->has('password'))
                <p id="password-help" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ $errors->first('password') }}
                </p>
            @endif
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input
                id="remember_me"
                name="remember"
                type="checkbox"
                class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
            >
            <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Recordar mi sesión
            </label>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
            Iniciar Sesión
        </button>

        <!-- Forgot Password -->
        @if (Route::has('tenant.password.request'))
            <div class="text-center">
                <a
                    href="{{ route('tenant.password.request') }}"
                    class="text-sm text-sky-600 hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300 font-medium transition-colors"
                >
                    ¿Olvidaste tu contraseña?
                </a>
            </div>
        @endif
    </form>

    @push('scripts')
        <script>
            function loginForm() {
                return {
                    processing: false,

                    init() {
                        this.$el.addEventListener('submit', () => {
                            this.processing = true;
                        });
                    }
                }
            }
        </script>
    @endpush
</x-tenant.auth.layouts.auth>