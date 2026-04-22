<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Detalles del Comprobante
        </h3>
        <div class="flex items-center gap-2">
            @if($record->status === 'approved')
                <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-medium rounded-full">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Aprobado
                </span>
            @elseif($record->status === 'pending')
                <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-sm font-medium rounded-full">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Pendiente
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm font-medium rounded-full">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    Rechazado
                </span>
            @endif
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Left Column --}}
        <div class="space-y-4">
            <!-- Payment Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Información del Pago
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Monto:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($record->amount, 2) }} USD
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Método:</span>
                        <span class="font-medium text-gray-900 dark:text-white capitalize">
                            {{ $record->payment_method === 'transfer' ? 'Transferencia' : 'Depósito' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Fecha de Subida:</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $record->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    @if($record->approved_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Fecha de Aprobación:</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $record->approved_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($record->notes)
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Notas del Cliente
                    </h4>
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        {{ $record->notes }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="space-y-4">
            <!-- Admin Response -->
            @if($record->admin_notes)
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                    <h4 class="font-medium text-amber-900 dark:text-amber-300 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Respuesta del Administrador
                    </h4>
                    <p class="text-sm text-amber-800 dark:text-amber-300">
                        {{ $record->admin_notes }}
                    </p>
                </div>
            @endif

            <!-- Status Timeline -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Línea de Tiempo
                </h4>
                <div class="space-y-3">
                    {{-- Uploaded --}}
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Comprobante Subido</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $record->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- Approved/Rejected --}}
                    @if($record->approved_at)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $record->status === 'approved' ? 'Pago Aprobado' : 'Pago Rechazado' }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $record->approved_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">En Revisión</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    Esperando aprobación administrativa
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Preview / Download -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Archivo del Comprobante
            </h4>
            <a href="{{ $record->file_url }}"
               target="_blank"
               class="inline-flex items-center px-3 py-1.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar
            </a>
        </div>

        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-6 text-center">
            @if(str_contains(strtolower($record->file_path), '.pdf'))
                <div class="inline-flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg mb-3">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Documento PDF</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ basename($record->file_path) }}</p>
            @elseif(str_contains(strtolower($record->file_path), '.jpg') || str_contains(strtolower($record->file_path), '.jpeg'))
                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg mb-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Imagen JPG</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ basename($record->file_path) }}</p>
            @elseif(str_contains(strtolower($record->file_path), '.png'))
                <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Imagen PNG</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ basename($record->file_path) }}</p>
            @else
                <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-600 rounded-lg mb-3">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Archivo</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ basename($record->file_path) }}</p>
            @endif
        </div>
    </div>

    <!-- Actions -->
    @if($record->status === 'pending')
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-end gap-3">
            <button type="button"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                Editar
            </button>
            <button type="button"
                    class="px-4 py-2 border border-red-300 text-red-700 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                Eliminar
            </button>
        </div>
    @endif
</div>