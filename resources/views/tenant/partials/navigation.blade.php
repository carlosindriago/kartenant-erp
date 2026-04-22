{{-- Dynamic Navigation Component --}}
@php
    $currentRoute = request()->route()->getName();
    $showLabel = $showLabel ?? false;
    $orientation = $orientation ?? 'horizontal'; // 'horizontal' or 'vertical'
    $size = $size ?? 'normal'; // 'small', 'normal', 'large'
@endphp

@php
    $navigationItems = [
        [
            'name' => 'Inicio',
            'route' => 'tenant.dashboard',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
        [
            'name' => 'Punto de Venta',
            'route' => 'tenant.pos.index',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
        [
            'name' => 'Inventario',
            'route' => 'tenant.inventory.index',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
        [
            'name' => 'Ventas',
            'route' => 'tenant.sales.index',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
        [
            'name' => 'Clientes',
            'route' => 'tenant.customers.index',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
        [
            'name' => 'Configuración',
            'route' => 'tenant.settings.index',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
            'permission' => null,
            'badge' => null,
        ],
    ];
@endphp

<nav class="{{ $orientation === 'vertical' ? 'flex flex-col space-y-1' : 'flex items-center space-x-1' }}" role="navigation" aria-label="Navegación principal">
    @foreach($navigationItems as $item)
        @php
            $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
            $linkClasses = $orientation === 'vertical'
                ? 'nav-link block w-full text-left'
                : 'nav-link';

            if ($size === 'small') {
                $linkClasses .= ' py-2 px-3 text-sm';
            } elseif ($size === 'large') {
                $linkClasses .= ' py-3 px-5 text-base';
            } else {
                $linkClasses .= ' py-2 px-4';
            }
        @endphp

        <a href="{{ route($item['route']) }}"
           class="{{ $linkClasses }} {{ $isActive ? 'active' : '' }}"
           aria-current="{{ $isActive ? 'page' : 'false' }}">
            <span class="flex items-center {{ $orientation === 'vertical' && !$showLabel ? 'justify-center' : '' }}">
                <span>{!! $item['icon'] !!}</span>

                @if($showLabel || $orientation === 'horizontal')
                    <span class="{{ $orientation === 'vertical' ? 'ml-3' : 'ml-2' }}">
                        {{ $item['name'] }}
                    </span>
                @endif

                @if($item['badge'])
                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                        {{ $item['badge'] }}
                    </span>
                @endif
            </span>
        </a>
    @endforeach
</nav>

{{-- Optional Mobile Dropdown Menu --}}
@if($orientation === 'horizontal' && isset($showMobileDropdown) && $showMobileDropdown)
    <div class="md:hidden">
        <button onclick="toggleMobileNavDropdown()"
                class="w-full nav-link justify-between"
                aria-expanded="false"
                aria-haspopup="true">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                Menú
            </span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div id="mobile-nav-dropdown" class="hidden mt-2 space-y-1">
            @foreach($navigationItems as $item)
                @php
                    $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                @endphp

                <a href="{{ route($item['route']) }}"
                   class="nav-link block w-full {{ $isActive ? 'active' : '' }}"
                   aria-current="{{ $isActive ? 'page' : 'false' }}">
                    <span class="flex items-center">
                        <span>{!! $item['icon'] !!}</span>
                        <span class="ml-3">{{ $item['name'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <script>
        function toggleMobileNavDropdown() {
            const dropdown = document.getElementById('mobile-nav-dropdown');
            const button = dropdown.previousElementSibling;
            const isHidden = dropdown.classList.contains('hidden');

            if (isHidden) {
                dropdown.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
            } else {
                dropdown.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick*="toggleMobileNavDropdown"]') && !event.target.closest('#mobile-nav-dropdown')) {
                const dropdown = document.getElementById('mobile-nav-dropdown');
                const button = dropdown.previousElementSibling;
                dropdown.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
@endif