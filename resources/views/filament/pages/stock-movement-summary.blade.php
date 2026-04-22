<x-filament-panels::page>
    {{-- Hero Section Optimizado --}}
    <div class="mb-4">
        <div class="bg-gradient-to-br {{ $movement->type === 'entrada' ? 'from-success-500 to-success-600' : 'from-danger-500 to-danger-600' }} rounded-lg p-4 sm:p-6 text-white shadow-md">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white/20 rounded-full flex items-center justify-center">
                        @if($movement->type === 'entrada')
                            <x-heroicon-o-arrow-down-tray class="w-7 h-7 sm:w-8 sm:h-8" />
                        @else
                            <x-heroicon-o-arrow-up-tray class="w-7 h-7 sm:w-8 sm:h-8" />
                        @endif
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg sm:text-xl font-bold mb-1">
                        {{ $movement->type === 'entrada' ? 'Entrada Registrada' : 'Salida Registrada' }}
                    </h2>
                    <p class="text-sm sm:text-base text-white/90 truncate">{{ $movement->product->name }}</p>
                    <p class="text-xs sm:text-sm text-white/75 mt-1">Doc: {{ $movement->document_number }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stock Movement Cards - Mejorado --}}
    <div class="mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            {{-- Stock Anterior --}}
            <div class="relative overflow-hidden rounded-xl p-4 sm:p-5 text-center border-2 shadow-lg"
                 style="background: linear-gradient(to bottom right, #9ca3af, #4b5563); border-color: #6b7280;">
                <div class="absolute top-0 right-0 w-20 h-20 rounded-full -mr-10 -mt-10" style="background-color: rgba(0,0,0,0.1);"></div>
                <div class="relative">
                    <div class="flex items-center justify-center gap-1 mb-2">
                        <x-heroicon-o-archive-box class="w-4 h-4 sm:w-5 sm:h-5 text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                        <span class="text-xs font-bold text-white uppercase tracking-wide" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">Anterior</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white mb-1" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));">
                        {{ number_format($movement->previous_stock) }}
                    </div>
                    <div class="text-xs text-white font-bold" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">unidades</div>
                </div>
            </div>

            {{-- Movimiento (Entrada/Salida) --}}
            <div class="relative overflow-hidden rounded-xl p-4 sm:p-5 text-center border-2 shadow-xl transform hover:scale-105 transition-transform duration-200"
                 style="background: linear-gradient(to bottom right, {{ $movement->type === 'entrada' ? '#10b981, #059669' : '#ef4444, #dc2626' }}); border-color: {{ $movement->type === 'entrada' ? '#34d399' : '#f87171' }};">
                <div class="absolute top-0 right-0 w-24 h-24 rounded-full -mr-12 -mt-12" style="background-color: rgba(255,255,255,0.2);"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 rounded-full -ml-8 -mb-8" style="background-color: rgba(255,255,255,0.2);"></div>
                <div class="relative">
                    <div class="flex items-center justify-center gap-1 mb-2">
                        @if($movement->type === 'entrada')
                            <x-heroicon-o-arrow-down-circle class="w-5 h-5 sm:w-6 sm:h-6 text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                        @else
                            <x-heroicon-o-arrow-up-circle class="w-5 h-5 sm:w-6 sm:h-6 text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                        @endif
                        <span class="text-sm font-extrabold text-white uppercase tracking-wide" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                            {{ $movement->type === 'entrada' ? 'Entrada' : 'Salida' }}
                        </span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white mb-1" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));">
                        {{ $movement->type === 'entrada' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                    </div>
                    <div class="text-xs text-white font-bold" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">unidades</div>
                </div>
            </div>

            {{-- Stock Actual --}}
            <div class="relative overflow-hidden rounded-xl p-4 sm:p-5 text-center border-2 shadow-lg"
                 style="background: linear-gradient(to bottom right, #3b82f6, #1d4ed8); border-color: #60a5fa;">
                <div class="absolute top-0 left-0 w-20 h-20 rounded-full -ml-10 -mt-10" style="background-color: rgba(255,255,255,0.2);"></div>
                <div class="relative">
                    <div class="flex items-center justify-center gap-1 mb-2">
                        <x-heroicon-o-cube class="w-4 h-4 sm:w-5 sm:h-5 text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                        <span class="text-xs font-bold text-white uppercase tracking-wide" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">Actual</span>
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white mb-1" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));">
                        {{ number_format($movement->new_stock) }}
                    </div>
                    <div class="text-xs text-white font-bold" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">unidades</div>
                </div>
            </div>
        </div>

        @if($movement->type === 'salida' && $movement->new_stock < 10)
        <div class="mt-3 p-3 sm:p-4 rounded-lg shadow-lg" style="background: linear-gradient(to right, #fbbf24, #f97316); border-left: 4px solid #ea580c;">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));" />
                </div>
                <div>
                    <span class="text-sm font-black text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">¡Stock Bajo!</span>
                    <span class="text-sm font-bold text-white ml-2" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                        Quedan solo {{ number_format($movement->new_stock) }} unidades
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Producto e Información Principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 mb-4">
        {{-- Producto --}}
        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-sm sm:text-base">
                    <x-heroicon-o-cube class="w-4 h-4" />
                    <span>Producto</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">SKU</div>
                    <div class="font-semibold font-mono">{{ $movement->product->sku }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Nombre</div>
                    <div class="font-semibold">{{ $movement->product->name }}</div>
                </div>
                @if($movement->product->description)
                <div class="sm:col-span-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Descripción</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($movement->product->description, 120) }}</div>
                </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Registro --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-sm sm:text-base">
                    <x-heroicon-o-user class="w-4 h-4" />
                    <span>Registro</span>
                </div>
            </x-slot>

            <div class="space-y-2">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Registrado por</div>
                    <div class="font-semibold text-sm">{{ $movement->user_name }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Fecha y Hora</div>
                    <div class="font-semibold text-sm">{{ $movement->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Detalles del Movimiento --}}
    <x-filament::section class="mb-4">
        <x-slot name="heading">
            <div class="flex items-center gap-2 text-sm sm:text-base">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                <span>Detalles</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Motivo</div>
                <div class="font-semibold text-sm">{{ $movement->reason }}</div>
            </div>

            @if($movement->supplier_id && $movement->supplier)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Proveedor</div>
                <div class="font-semibold text-sm">
                    {{ $movement->supplier->name }}
                    @if($movement->supplier->contact_name)
                        <span class="text-xs text-gray-500 block">{{ $movement->supplier->contact_name }}</span>
                    @endif
                </div>
            </div>
            @endif

            @if($movement->invoice_reference)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Factura/Guía</div>
                <div class="font-semibold text-sm">{{ $movement->invoice_reference }}</div>
            </div>
            @endif

            @if($movement->batch_number)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Lote</div>
                <div class="font-semibold text-sm">{{ $movement->batch_number }}</div>
            </div>
            @endif

            @if($movement->expiry_date)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Vencimiento</div>
                <div class="font-semibold text-sm">{{ $movement->expiry_date->format('d/m/Y') }}</div>
            </div>
            @endif

            @if($movement->reference)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Referencia</div>
                <div class="font-semibold text-sm">{{ $movement->reference }}</div>
            </div>
            @endif
        </div>

        @if($movement->additional_notes)
        <div class="mt-3 p-3 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg">
            <div class="flex items-start gap-2">
                <x-heroicon-o-pencil-square class="w-4 h-4 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-warning-900 dark:text-warning-300 mb-1 text-sm">Notas:</div>
                    <div class="text-sm text-warning-800 dark:text-warning-400">{{ $movement->additional_notes }}</div>
                </div>
            </div>
        </div>
        @endif
    </x-filament::section>

    {{-- Autorización (solo para salidas) --}}
    @if($movement->type === 'salida' && $movement->authorizedBy)
    <x-filament::section class="mb-4">
        <x-slot name="heading">
            <div class="flex items-center gap-2 text-sm sm:text-base">
                <x-heroicon-o-shield-check class="w-4 h-4" />
                <span>Autorización</span>
            </div>
        </x-slot>

        <div class="bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg p-3">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="w-5 h-5 text-success-600 dark:text-success-400 flex-shrink-0" />
                <div class="flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <div class="text-xs text-success-700 dark:text-success-400 mb-1">Autorizado por</div>
                            <div class="font-semibold text-success-900 dark:text-success-300 text-sm">{{ $movement->authorizedBy->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-success-700 dark:text-success-400 mb-1">Fecha</div>
                            <div class="font-semibold text-success-900 dark:text-success-300 text-sm">{{ $movement->authorized_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
    @endif

    {{-- Verificación Compacta --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2 text-sm sm:text-base">
                <x-heroicon-o-lock-closed class="w-4 h-4" />
                <span>Verificación</span>
            </div>
        </x-slot>

        <div class="bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-800 rounded-lg p-3">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-badge class="w-5 h-5 text-info-600 dark:text-info-400 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-info-900 dark:text-info-300 mb-1 text-sm">Comprobante Verificable</div>
                    <p class="text-xs text-info-700 dark:text-info-400 mb-2">
                        SHA-256 · {{ $movement->verification_generated_at->format('d/m/Y H:i') }}
                    </p>
                    <div class="font-mono text-xs text-info-900 dark:text-info-300 break-all bg-info-100 dark:bg-info-900/30 p-2 rounded">
                        {{ Str::limit($movement->verification_hash, 64) }}
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
