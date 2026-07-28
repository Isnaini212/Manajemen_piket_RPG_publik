@props([
    'lives' => 3,
    'max' => 3,
])

@php
    $lives = (int) $lives;
    $max = max((int) $max, $lives);
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center flex-wrap gap-0.5 sm:gap-1 max-w-full']) }}>
    @for ($i = 1; $i <= $max; $i++)
        {{-- Blocky pixel-art heart (crisp edges, no anti-aliasing) --}}
        <svg viewBox="0 0 8 6" class="w-3.5 h-3.5 sm:w-4 sm:h-4 md:w-5 md:h-5 shrink-0 pixelated {{ $i <= $lives ? 'text-[#e63946]' : 'text-[#2d2050]' }}"
             fill="currentColor" shape-rendering="crispEdges" aria-hidden="true">
            <rect x="1" y="0" width="2" height="1" />
            <rect x="5" y="0" width="2" height="1" />
            <rect x="0" y="1" width="8" height="1" />
            <rect x="1" y="2" width="6" height="1" />
            <rect x="2" y="3" width="4" height="1" />
            <rect x="3" y="4" width="2" height="1" />
        </svg>
    @endfor

    <span class="font-pixel text-[8px] sm:text-[9px] md:text-[10px] text-[#8888aa] ml-0.5 shrink-0">{{ $lives }}/{{ $max }}</span>
</div>
