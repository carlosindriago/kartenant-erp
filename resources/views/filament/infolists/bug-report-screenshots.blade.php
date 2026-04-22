<div class="space-y-2">
    @if(empty($getState()))
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay capturas de pantalla adjuntas</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($getState() as $index => $screenshot)
                @php
                    // Handle both old format (/storage/...) and new format (bug-reports/...)
                    if (str_starts_with($screenshot, '/storage/')) {
                        // Old format: already a URL
                        $url = $screenshot;
                        $relativePath = str_replace('/storage/', '', $screenshot);
                    } else {
                        // New format: relative path, build URL
                        $relativePath = $screenshot;
                        $url = \Storage::disk('public')->url($screenshot);
                    }

                    $filename = basename($screenshot);
                    $fullPath = storage_path('app/public/' . $relativePath);
                    $exists = file_exists($fullPath);
                @endphp

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                    @if($exists)
                        <a href="{{ $url }}" target="_blank" class="block group">
                            <div class="aspect-video bg-gray-100 dark:bg-gray-900 flex items-center justify-center overflow-hidden">
                                <img
                                    src="{{ $url }}"
                                    alt="Screenshot {{ $index + 1 }}"
                                    class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-200"
                                    loading="lazy"
                                />
                            </div>
                        </a>
                    @else
                        <div class="aspect-video bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                            <div class="text-center p-4">
                                <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Archivo no encontrado</p>
                            </div>
                        </div>
                    @endif

                    <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" title="{{ $filename }}">
                                    {{ $filename }}
                                </p>
                                @if($exists)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format(filesize($fullPath) / 1024, 1) }} KB
                                    </p>
                                @endif
                            </div>

                            @if($exists)
                                <div class="flex gap-1">
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors"
                                        title="Ver en nueva pestaña"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>

                                    <a
                                        href="{{ $url }}"
                                        download="{{ $filename }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 transition-colors"
                                        title="Descargar"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                        Total: {{ count($getState()) }} captura(s) de pantalla
                    </p>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                        Haz clic en una imagen para verla en tamaño completo o usa el botón de descarga.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
