<div
    x-data="{ isOpen: @entangle('isOpen') }"
    @open-modal.window="
        console.log('[BugReport Modal] Evento recibido:', $event.detail);
        if ($event.detail && $event.detail.id === 'bug-report-modal') {
            console.log('[BugReport Modal] Abriendo modal...');
            isOpen = true;
        }
    "
    x-on:open-bug-report-modal.window="
        console.log('[BugReport Modal] Evento Livewire recibido');
        isOpen = true;
    "
>
    @auth
    {{-- Modal --}}
    <x-filament::modal
        id="bug-report-modal"
        :visible="$isOpen"
        width="2xl"
    >
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1.5rem; height: 1.5rem; color: rgb(220, 38, 38);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Reportar un Problema</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Describe el problema que estás experimentando y adjunta capturas de pantalla si es posible.
        </x-slot>

        <form wire:submit="submit">
            {{ $this->form }}

            <x-slot name="footerActions">
                <x-filament::button type="submit" color="danger">
                    Enviar Reporte
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    @click="isOpen = false"
                >
                    Cancelar
                </x-filament::button>
            </x-slot>
        </form>
    </x-filament::modal>
    @endauth
</div>
