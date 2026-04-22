<x-filament-panels::page>
    <div class="max-w-5xl mx-auto">
        {{-- Header con información --}}
        <div class="mb-6 bg-gradient-to-r from-danger-50 to-warning-50 dark:from-danger-950/20 dark:to-warning-950/20 rounded-lg p-6 border border-danger-200 dark:border-danger-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-danger-100 dark:bg-danger-900/50 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-danger-600 dark:text-danger-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        Cierre de Caja - {{ $cashRegister->register_number }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Abierta: {{ $cashRegister->opened_at->format('d/m/Y H:i') }} · 
                        Tiempo: {{ $summary['hours_open'] }}h abierta · 
                        {{ $summary['total_sales'] }} transacciones realizadas
                    </p>
                </div>
            </div>
        </div>

        {{-- Formulario --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <form wire:submit="closeRegister">
                {{ $this->form }}
                
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <p class="font-semibold mb-1">⚠️ Importante:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Verifica que el efectivo contado sea correcto</li>
                                <li>Las diferencias quedarán registradas en el sistema</li>
                                <li>No podrás modificar el arqueo después del cierre</li>
                            </ul>
                        </div>
                        
                        <div class="flex gap-3">
                            @foreach ($this->getFormActions() as $action)
                                {{ $action }}
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Tips para el arqueo --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-success-50 dark:bg-success-950/20 rounded-lg p-4 border border-success-200 dark:border-success-800">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-success-900 dark:text-success-100 text-sm">Arqueo Exacto</h5>
                </div>
                <p class="text-xs text-success-800 dark:text-success-200">
                    Si el efectivo contado coincide exactamente con el esperado, ¡excelente trabajo!
                </p>
            </div>

            <div class="bg-warning-50 dark:bg-warning-950/20 rounded-lg p-4 border border-warning-200 dark:border-warning-800">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-warning-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-warning-900 dark:text-warning-100 text-sm">Sobrante</h5>
                </div>
                <p class="text-xs text-warning-800 dark:text-warning-200">
                    Más efectivo del esperado. Verifica que no haya ventas sin registrar.
                </p>
            </div>

            <div class="bg-danger-50 dark:bg-danger-950/20 rounded-lg p-4 border border-danger-200 dark:border-danger-800">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-danger-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <h5 class="font-semibold text-danger-900 dark:text-danger-100 text-sm">Faltante</h5>
                </div>
                <p class="text-xs text-danger-800 dark:text-danger-200">
                    Menos efectivo del esperado. Revisa cuidadosamente y explica en notas.
                </p>
            </div>
        </div>

        {{-- Información del turno --}}
        @if($summary['cancelled_sales'] > 0 || $summary['cash_returns'] > 0)
            <div class="mt-6 bg-info-50 dark:bg-info-950/20 rounded-lg p-4 border border-info-200 dark:border-info-800">
                <h5 class="font-semibold text-info-900 dark:text-info-100 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Actividades Especiales del Turno
                </h5>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($summary['cancelled_sales'] > 0)
                        <div>
                            <span class="font-semibold text-info-900 dark:text-info-100">Cancelaciones:</span>
                            <span class="text-info-800 dark:text-info-200">{{ $summary['cancelled_sales'] }} ventas</span>
                        </div>
                    @endif
                    @if($summary['cash_returns'] > 0)
                        <div>
                            <span class="font-semibold text-info-900 dark:text-info-100">Devoluciones:</span>
                            <span class="text-info-800 dark:text-info-200">${{ number_format($summary['cash_returns'], 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
