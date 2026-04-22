<x-filament-panels::page>
    <div class="max-w-3xl mx-auto">
        {{-- Header con información --}}
        <div class="mb-6 bg-gradient-to-r from-success-50 to-primary-50 dark:from-success-950/20 dark:to-primary-950/20 rounded-lg p-6 border border-success-200 dark:border-success-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-success-100 dark:bg-success-900/50 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        Apertura de Caja Registradora
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Estás a punto de abrir tu caja para el turno. Cuenta cuidadosamente el efectivo inicial y verifica que todo esté en orden.
                    </p>
                </div>
            </div>
        </div>

        {{-- Instrucciones --}}
        <div class="mb-6 bg-primary-50 dark:bg-primary-950/20 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
            <h4 class="font-semibold text-primary-900 dark:text-primary-100 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                Instrucciones
            </h4>
            <ul class="space-y-2 text-sm text-primary-800 dark:text-primary-200">
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 font-bold">1.</span>
                    <span>Cuenta todo el efectivo disponible (billetes y monedas)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 font-bold">2.</span>
                    <span>Ingresa el monto total en el campo "Monto Inicial"</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 font-bold">3.</span>
                    <span>Agrega notas si hay algo especial (ej: billetes grandes, monedas faltantes)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex-shrink-0 font-bold">4.</span>
                    <span>Haz clic en "Abrir Caja" para iniciar tu turno</span>
                </li>
            </ul>
        </div>

        {{-- Formulario --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <form wire:submit="openRegister">
                {{ $this->form }}
                
                <div class="mt-6 flex justify-end gap-3">
                    @foreach ($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </form>
        </div>

        {{-- Tips adicionales --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-info-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-gray-900 dark:text-white text-sm">Turno Único</h5>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Solo puedes tener una caja abierta a la vez. Al cerrar, podrás abrir otra.
                </p>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-warning-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-gray-900 dark:text-white text-sm">Precisión</h5>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Sé preciso con el monto inicial. Las diferencias se calcularán al cierre.
                </p>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-gray-900 dark:text-white text-sm">Auditoría</h5>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Todas las acciones quedan registradas para auditoría y reportes.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
