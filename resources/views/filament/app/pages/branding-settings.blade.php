<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Información introductoria --}}
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
                        Personaliza la Identidad de Tu Empresa
                    </h3>
                    <p class="text-sm text-purple-800 dark:text-purple-200">
                        Configura cómo se muestra tu marca en el sistema. Puedes elegir entre mostrar el nombre de tu empresa o usar un logo personalizado. Los cambios se aplicarán inmediatamente en toda la aplicación.
                    </p>
                </div>
            </div>
        </div>

        {{-- Formulario de Filament --}}
        <form wire:submit="save">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button
                    color="gray"
                    wire:click="resetBranding"
                    wire:confirm="¿Estás seguro que deseas restablecer la configuración de branding? Esto eliminará el logo actual."
                >
                    Restablecer
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    color="primary"
                >
                    Guardar Cambios
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
