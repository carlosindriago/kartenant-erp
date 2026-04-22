{{-- Reusable Content Card Component --}}
@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $showHeader = $showHeader ?? true;
    $showFooter = $showFooter ?? false;
    $collapsible = $collapsible ?? false;
    $collapsed = $collapsed ?? false;
    $cardId = $cardId ?? 'card-' . uniqid();
@endphp

<div class="card {{ $collapsible ? 'collapsible-card' : '' }}" @if($collapsible) id="{{ $cardId }}" @endif>
    {{-- Card Header --}}
    @if($showHeader)
        <div class="card-header {{ $collapsible ? 'cursor-pointer hover:bg-gray-100 transition-colors' : '' }}"
             @if($collapsible) onclick="toggleCard('{{ $cardId }}')" @endif>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    @if($title)
                        <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                    @endif

                    @if($subtitle)
                        <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
                    @endif
                </div>

                @if($collapsible)
                    <button class="ml-4 p-1 hover:bg-gray-200 rounded transition-colors"
                            aria-expanded="{{ !$collapsed }}"
                            aria-controls="{{ $cardId }}-content">
                        <svg id="{{ $cardId }}-toggle-icon"
                             class="w-5 h-5 text-gray-500 transition-transform duration-200 {{ $collapsed ? '' : 'rotate-180' }}"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                @endif

                @if(isset($headerActions))
                    <div class="ml-4">
                        {{ $headerActions }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Card Body --}}
    <div id="{{ $cardId }}-content"
         class="card-body {{ $collapsible && $collapsed ? 'hidden' : '' }}">
        {{ $slot ?? $content ?? '' }}
    </div>

    {{-- Card Footer --}}
    @if($showFooter && isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>

@php
    if($collapsible):
        // Add JavaScript for collapsible functionality if not already included
        if(!defined('COLLAPSIBLE_CARD_JS_INCLUDED')):
            define('COLLAPSIBLE_CARD_JS_INCLUDED', true');
@endphp

<script>
    function toggleCard(cardId) {
        const content = document.getElementById(cardId + '-content');
        const icon = document.getElementById(cardId + '-toggle-icon');
        const header = content.previousElementSibling;
        const button = header.querySelector('[aria-controls]');

        const isHidden = content.classList.contains('hidden');

        if (isHidden) {
            content.classList.remove('hidden');
            icon.classList.remove('rotate-180');
            button.setAttribute('aria-expanded', 'true');
        } else {
            content.classList.add('hidden');
            icon.classList.add('rotate-180');
            button.setAttribute('aria-expanded', 'false');
        }
    }
</script>
@php
        endif;
    endif;
@endphp