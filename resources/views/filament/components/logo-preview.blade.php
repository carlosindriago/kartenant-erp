<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
    <div class="flex items-center justify-center min-h-[100px]">
        @if($type === 'image')
            <img
                src="{{ $url }}"
                alt="Logo"
                class="max-h-[80px] max-w-full object-contain"
                style="max-height: 80px;"
            />
        @else
            <div
                class="px-6 py-3 rounded-md font-semibold text-lg"
                style="
                    color: {{ $textColor }};
                    background-color: {{ $backgroundColor !== '#ffffff' ? $backgroundColor : 'transparent' }};
                    @if($backgroundColor !== '#ffffff')
                        border: 1px solid {{ $textColor }}15;
                    @endif
                "
            >
                {{ $text }}
            </div>
        @endif
    </div>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        <p class="font-medium">Vista Previa</p>
        <p class="text-xs mt-1">
            @if($type === 'image')
                Modo: Logo con imagen
            @else
                Modo: Logo con texto
            @endif
        </p>
    </div>
</div>
