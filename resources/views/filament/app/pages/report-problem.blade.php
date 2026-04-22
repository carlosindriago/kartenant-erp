<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-danger-50 dark:bg-danger-900/20 border-l-4 border-danger-500 p-4 rounded-r">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-danger-800 dark:text-danger-200">
                        Reportar un Problema
                    </h3>
                    <div class="mt-2 text-sm text-danger-700 dark:text-danger-300">
                        <p>
                            Si encuentras un error o un problema mientras usas el sistema, por favor completa este formulario con todos los detalles posibles. 
                            Nuestro equipo de soporte revisará tu reporte y te contactará si es necesario.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit="submit">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getFormActions()"
                :full-width="false"
            />
        </form>
    </div>
</x-filament-panels::page>

