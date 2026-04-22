{{-- Reusable Metric Card Component --}}
@php
    // Default values
    $icon = $icon ?? '📊';
    $value = $value ?? '0';
    $label = $label ?? 'Métrica';
    $color = $color ?? 'primary';
    $trend = $trend ?? null; // 'up', 'down', or null
    $trendValue = $trendValue ?? null;
    $loading = $loading ?? false;
@endphp

<div class="metric-card {{ $loading ? 'loading' : '' }}" style="{{ $color !== 'primary' ? "border-left-color: var(--color-{$color});" : "" }}">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            {{-- Icon --}}
            <div class="metric-icon {{ $color !== 'primary' ? "text-{$color}" : "" }}">
                {{ $icon }}
            </div>

            {{-- Content --}}
            <div>
                {{-- Value --}}
                <div class="metric-value {{ $color !== 'primary' ? "color-{$color}" : "" }}">
                    @if($loading)
                        <div class="h-8 w-24 bg-gray-200 rounded animate-pulse"></div>
                    @else
                        {{ $value }}
                    @endif
                </div>

                {{-- Label --}}
                <div class="metric-label">
                    @if($loading)
                        <div class="h-4 w-20 bg-gray-200 rounded animate-pulse mt-2"></div>
                    @else
                        {{ $label }}
                    @endif
                </div>

                {{-- Trend --}}
                @if($trend && $trendValue && !$loading)
                    <div class="flex items-center mt-2 text-sm">
                        @if($trend === 'up')
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-green-600 font-medium">{{ $trendValue }}</span>
                            <span class="text-gray-500 ml-1">vs ayer</span>
                        @elseif($trend === 'down')
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-red-600 font-medium">{{ $trendValue }}</span>
                            <span class="text-gray-500 ml-1">vs ayer</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Loading Indicator --}}
        @if($loading)
            <div class="spinner"></div>
        @endif
    </div>
</div>