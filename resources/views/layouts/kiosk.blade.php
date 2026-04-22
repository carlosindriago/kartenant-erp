<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Terminal POS - {{ optional(\Spatie\Multitenancy\Models\Tenant::current())->name ?? config('app.name') }}</title>
        
        <!-- PWA Support -->
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <style>
            /* Kiosk Mode - Fullscreen optimizations */
            body {
                overflow: hidden;
                user-select: none;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
            }
            
            /* Hide scrollbars but keep functionality */
            *::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            *::-webkit-scrollbar-track {
                background: transparent;
            }
            *::-webkit-scrollbar-thumb {
                background: rgba(156, 163, 175, 0.3);
                border-radius: 4px;
            }
            *::-webkit-scrollbar-thumb:hover {
                background: rgba(156, 163, 175, 0.5);
            }
            
            /* Keyboard shortcut indicator */
            .kbd {
                @apply px-2 py-1 text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded shadow-sm;
            }
            
            /* Pulse animation for notifications */
            @keyframes pulse-scale {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            .pulse-scale {
                animation: pulse-scale 0.5s ease-in-out;
            }
            
            /* Success feedback */
            @keyframes success-flash {
                0% { background-color: transparent; }
                50% { background-color: rgba(16, 185, 129, 0.2); }
                100% { background-color: transparent; }
            }
            .success-flash {
                animation: success-flash 0.6s ease-in-out;
            }
        </style>
    </head>
    <body class="antialiased bg-gray-50 dark:bg-gray-900 overflow-hidden">
        {{ $slot }}

        @livewireScripts
        
        {{-- Componente Alpine POS Terminal --}}
        @include('livewire.p-o-s.partials.alpine-script')
        
        <!-- Beep sound for barcode scanner feedback -->
        <audio id="beep-sound" preload="auto">
            <source src="data:audio/wav;base64,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" type="audio/wav">
        </audio>
    </body>
</html>
