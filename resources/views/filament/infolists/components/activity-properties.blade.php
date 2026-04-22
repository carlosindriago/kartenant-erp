@php
    $properties = $getState();
    
    // Asegurarse de que sea un array
    if (is_string($properties)) {
        $properties = json_decode($properties, true);
    }
@endphp

<div class="space-y-4">
    @if(empty($properties))
        <p class="text-sm text-gray-500 dark:text-gray-400">Sin cambios registrados</p>
    @else
        @if(isset($properties['old']) && !empty($properties['old']))
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Valores Anteriores:</h4>
                <pre class="text-xs bg-gray-50 dark:bg-gray-900 p-2 rounded overflow-x-auto">{{ json_encode($properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
        
        @if(isset($properties['attributes']) && !empty($properties['attributes']))
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Valores Nuevos:</h4>
                <pre class="text-xs bg-gray-50 dark:bg-gray-900 p-2 rounded overflow-x-auto">{{ json_encode($properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    @endif
</div>
