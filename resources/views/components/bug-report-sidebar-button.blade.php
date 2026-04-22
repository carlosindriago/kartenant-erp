{{-- Enlace de Reportar Problema al final del sidebar --}}
@php
    $sidebarCollapsible = filament()->isSidebarCollapsibleOnDesktop();
    $reportProblemUrl = \App\Filament\App\Pages\ReportProblem::getUrl();
@endphp

<div class="-mx-2">
    <ul class="flex flex-col gap-y-1">
        <li class="fi-sidebar-item">
            <a
                href="{{ $reportProblemUrl }}"
                x-data="{ tooltip: false }"
                @if ($sidebarCollapsible)
                    x-effect="
                        tooltip = $store.sidebar.isOpen
                            ? false
                            : {
                                  content: 'Reportar Problema',
                                  placement: document.dir === 'rtl' ? 'left' : 'right',
                                  theme: $store.theme,
                              }
                    "
                    x-tooltip.html="tooltip"
                @endif
                @class([
                    'fi-sidebar-item-button relative flex items-center justify-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75',
                    'hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
                ])
            >
                <svg 
                    @class([
                        'fi-sidebar-item-icon h-6 w-6',
                        'text-gray-400 dark:text-gray-500',
                    ])
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke-width="1.5" 
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0112 12.75zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 01-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44.125 2.104.52 4.136 1.153 6.06M12 12.75a2.25 2.25 0 002.248-2.354M12 12.75a2.25 2.25 0 01-2.248-2.354M12 8.25c.995 0 1.971-.08 2.922-.236.403-.066.74-.358.795-.762a3.778 3.778 0 00-.399-2.25M12 8.25c-.995 0-1.97-.08-2.922-.236-.402-.066-.74-.358-.795-.762a3.734 3.734 0 01.4-2.253M12 8.25a2.25 2.25 0 00-2.248 2.146M12 8.25a2.25 2.25 0 012.248 2.146" />
                </svg>
                
                <span
                    @if ($sidebarCollapsible)
                        x-show="$store.sidebar.isOpen"
                        x-transition:enter="lg:transition lg:delay-100"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                    @endif
                    @class([
                        'fi-sidebar-item-label flex-1 truncate text-sm font-medium',
                        'text-gray-700 dark:text-gray-200',
                    ])
                >
                    Reportar Problema
                </span>
            </a>
        </li>
    </ul>
</div>
