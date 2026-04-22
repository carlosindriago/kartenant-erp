@php
    $data = $this->getData();
    $hasCriticalIssues = $data['has_critical_issues'];
    $totalIssues = $data['total_issues'];
@endphp

<x-filament::widget>
    @if($totalIssues > 0)
        {{-- Alerta Principal - Tarjeta Mejorada --}}
        <div class="alert-card mb-6 p-5 rounded-xl border-l-4 shadow-sm
            {{ $hasCriticalIssues
                ? 'bg-gradient-to-r from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-950/20 border-red-500'
                : 'bg-gradient-to-r from-yellow-50 to-yellow-100/50 dark:from-yellow-900/20 dark:to-yellow-950/20 border-yellow-500' }}">
            <div class="flex items-center gap-4">
                <div class="icon-container flex-shrink-0
                    {{ $hasCriticalIssues
                        ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400'
                        : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400' }}
                    rounded-full p-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold mb-1
                        {{ $hasCriticalIssues
                            ? 'text-red-900 dark:text-red-200'
                            : 'text-yellow-900 dark:text-yellow-200' }}">
                        {{ $hasCriticalIssues
                            ? '🚨 Suscripciones Críticas'
                            : '⚠️ Atención Requerida' }}
                    </h3>
                    <p class="text-base font-medium
                        {{ $hasCriticalIssues
                            ? 'text-red-800 dark:text-red-300'
                            : 'text-yellow-800 dark:text-yellow-300' }}">
                        {{ $totalIssues }} cliente(s) necesita{{ $totalIssues > 1 ? 'n' : '' }} tu atención
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Suscripciones Expiradas - Prioridad Máxima --}}
            @if($data['expired']->count() > 0)
                <div class="client-group bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-red-200 dark:border-red-800 overflow-hidden">
                    <div class="section-header p-4 bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 dark:bg-white/10 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-white">
                                    Suscripciones Expiradas
                                    <span class="ml-2 text-sm font-normal opacity-90">({{ $data['expired']->count() }})</span>
                                </h4>
                            </div>
                            <div class="status-badge px-3 py-1.5 bg-white/20 dark:bg-white/10 text-white text-sm font-semibold rounded-full">
                                CRÍTICO
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($data['expired'] as $tenant)
                            <div class="client-item p-4 rounded-lg border-2 border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10
                                   hover:bg-red-100/50 dark:hover:bg-red-900/20 hover:border-red-300 dark:hover:border-red-700
                                   transition-all duration-200 group cursor-pointer">
                                <a href="{{ route('filament.admin.resources.tenants.view', $tenant) }}" class="block">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="text-red-600 dark:text-red-400 font-bold text-lg">
                                                    {{ $tenant->name }}
                                                </div>
                                                <div class="text-xs px-2 py-1 bg-red-500 text-white rounded-full font-semibold">
                                                    EXPIRADO
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                                                    </svg>
                                                    <span>{{ $tenant->domain }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                    </svg>
                                                    <span>{{ $tenant->activeSubscription?->plan->name ?? 'Sin plan' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-sm font-bold text-red-600 dark:text-red-400 mb-1">
                                                {{ $tenant->activeSubscription?->getFormattedRemainingTime() ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $tenant->activeSubscription?->ends_at?->format('d/m/Y') ?? 'N/A' }}
                                            </div>
                                            <div class="mt-2 text-xs font-medium text-red-600 dark:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                                Ver detalles →
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Vencen Pronto - Prioridad Alta --}}
            @if($data['expiring_soon']->count() > 0)
                <div class="client-group bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-800 overflow-hidden">
                    <div class="section-header p-4 bg-gradient-to-r from-yellow-500 to-amber-500 dark:from-yellow-600 dark:to-amber-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 dark:bg-white/10 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-white">
                                    Vencen en 7 días
                                    <span class="ml-2 text-sm font-normal opacity-90">({{ $data['expiring_soon']->count() }})</span>
                                </h4>
                            </div>
                            <div class="status-badge px-3 py-1.5 bg-white/20 dark:bg-white/10 text-white text-sm font-semibold rounded-full">
                                ADVERTENCIA
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($data['expiring_soon'] as $tenant)
                            @php
                                $daysRemaining = $tenant->activeSubscription?->ends_at?->diffInDays(now(), false) ?? 0;
                                $isUrgent = $daysRemaining <= 3;
                            @endphp
                            <div class="client-item p-4 rounded-lg border-2 {{ $isUrgent ? 'border-orange-300 dark:border-orange-700 bg-orange-50/50 dark:bg-orange-900/10' : 'border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10' }}
                                   hover:bg-yellow-100/50 dark:hover:bg-yellow-900/20
                                   transition-all duration-200 group cursor-pointer">
                                <a href="{{ route('filament.admin.resources.tenants.view', $tenant) }}" class="block">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="{{ $isUrgent ? 'text-orange-600 dark:text-orange-400' : 'text-yellow-700 dark:text-yellow-500' }} font-bold text-lg">
                                                    {{ $tenant->name }}
                                                </div>
                                                <div class="text-xs px-2 py-1 {{ $isUrgent ? 'bg-orange-500' : 'bg-yellow-500' }} text-white rounded-full font-semibold">
                                                    {{ $isUrgent ? 'URGENTE' : 'PRONTO' }}
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                                                    </svg>
                                                    <span>{{ $tenant->domain }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                    </svg>
                                                    <span>{{ $tenant->activeSubscription?->plan->name ?? 'Sin plan' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-sm font-bold {{ $isUrgent ? 'text-orange-600 dark:text-orange-400' : 'text-yellow-600 dark:text-yellow-400' }} mb-1">
                                                {{ $tenant->activeSubscription?->getFormattedRemainingTime() }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $tenant->activeSubscription?->ends_at?->format('d/m/Y') ?? 'N/A' }}
                                            </div>
                                            <div class="mt-2 text-xs font-medium {{ $isUrgent ? 'text-orange-600 dark:text-orange-400' : 'text-yellow-600 dark:text-yellow-400' }} opacity-0 group-hover:opacity-100 transition-opacity">
                                                Ver detalles →
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Cuentas Suspendidas - Prioridad Media --}}
            @if($data['suspended']->count() > 0)
                <div class="client-group bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-orange-200 dark:border-orange-800 overflow-hidden">
                    <div class="section-header p-4 bg-gradient-to-r from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 dark:bg-white/10 rounded-lg p-2">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-white">
                                    Cuentas Suspendidas
                                    <span class="ml-2 text-sm font-normal opacity-90">({{ $data['suspended']->count() }})</span>
                                </h4>
                            </div>
                            <div class="status-badge px-3 py-1.5 bg-white/20 dark:bg-white/10 text-white text-sm font-semibold rounded-full">
                                SUSPENDIDO
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($data['suspended'] as $tenant)
                            <div class="client-item p-4 rounded-lg border-2 border-orange-200 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-900/10
                                   hover:bg-orange-100/50 dark:hover:bg-orange-900/20 hover:border-orange-300 dark:hover:border-orange-700
                                   transition-all duration-200 group cursor-pointer">
                                <a href="{{ route('filament.admin.resources.tenants.view', $tenant) }}" class="block">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="text-orange-600 dark:text-orange-400 font-bold text-lg">
                                                    {{ $tenant->name }}
                                                </div>
                                                <div class="text-xs px-2 py-1 bg-orange-500 text-white rounded-full font-semibold">
                                                    SUSPENDIDO
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"/>
                                                    </svg>
                                                    <span>{{ $tenant->domain }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <div class="text-sm font-bold text-orange-600 dark:text-orange-400 mb-1">
                                                Cuenta Suspendida
                                            </div>
                                            <div class="mt-2 text-xs font-medium text-orange-600 dark:text-orange-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                                Ver detalles →
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Estado Informativo - Prioridad Baja --}}
            @if($data['cancelled']->count() > 0 || $data['no_subscription']->count() > 0)
                <div class="client-group bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="section-header p-4 bg-gradient-to-r from-gray-500 to-gray-600 dark:from-gray-700 dark:to-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 dark:bg-white/10 rounded-lg p-2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-white">Información Adicional</h4>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        @if($data['cancelled']->count() > 0)
                            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                    <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                        Suscripciones Canceladas ({{ $data['cancelled']->count() }})
                                    </h5>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 pl-5">
                                    {{ $data['cancelled']->count() }} cliente(s) cancelaron pero mantienen acceso hasta que expire su plan actual.
                                </p>
                            </div>
                        @endif

                        @if($data['no_subscription']->count() > 0)
                            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                    <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                        Sin Suscripción ({{ $data['no_subscription']->count() }})
                                    </h5>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 pl-5">
                                    {{ $data['no_subscription']->count() }} cliente(s) fueron creados sin plan de suscripción asignado.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Estado Perfecto - Diseño Corporativo Optimista --}}
        <div class="success-state p-8 text-center bg-gradient-to-br from-green-50 via-emerald-50 to-green-100/50 dark:from-green-900/20 dark:via-emerald-900/20 dark:to-green-950/20 rounded-xl border border-green-200 dark:border-green-800 shadow-sm">
            <div class="icon-container mx-auto w-20 h-20 bg-green-500 dark:bg-green-600 rounded-full flex items-center justify-center mb-6 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-green-900 dark:text-green-100 mb-3">
                ¡Todo Perfecto!
            </h3>
            <p class="text-green-700 dark:text-green-300 text-lg max-w-md mx-auto">
                Todas las suscripciones están al día. No requieren acciones en este momento.
            </p>
        </div>
    @endif
</x-filament::widget>

{{-- Estilos CSS para mejorar UX - Principios "Filtro de Ernesto" --}}
<style>
/* Touch-friendly areas - Zona de Pulgar ≥44px */
.client-item {
    min-height: 44px;
}

.client-item a {
    min-height: 44px;
    display: block;
}

/* Hover states suaves con indicadores */
.client-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Estados para móviles */
@media (hover: none) {
    .client-item:active {
        transform: scale(0.98);
        transition: transform 0.1s;
    }

    .client-item .group-hover\:opacity-100 {
        opacity: 1;
    }
}

/* Jerarquía visual clara */
.status-badge {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

/* Separación consistente entre elementos interactivos */
.client-group .client-item:not(:last-child) {
    margin-bottom: 0.75rem;
}

/* Factor de miedo reducido - colores corporativos pero amigables */
.alert-card {
    transition: all 0.2s ease;
}

.alert-card:hover {
    transform: translateY(-1px);
}

/* Mejora de legibilidad */
.client-item .font-bold {
    line-height: 1.3;
}

/* Loading states para acciones futuras */
.client-item.loading {
    opacity: 0.6;
    pointer-events: none;
}

.client-item.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Mejoras de accesibilidad */
.client-item a:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
    border-radius: 6px;
}

/* Consistencia de modo oscuro */
.dark .alert-card {
    background: linear-gradient(to right, theme('colors.red.900/20'), theme('colors.red.950/20'));
}

.dark .client-group {
    background: theme('colors.gray.900');
    border-color: theme('colors.gray.700');
}
</style>
