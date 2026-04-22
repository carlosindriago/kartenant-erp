<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Page header --}}
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                Detalles del Tenant
            </h1>
            <p class="text-gray-600 mt-2">
                Panel administrativo completo para la gestion del tenant
            </p>
        </div>

        {{-- Main infolist content --}}
        {{ $this->infolist }}
    </div>
</x-filament-panels::page>