@props([
    'xp' => 0,
    'label' => 'XP',
])

@php
    $xp = (int) $xp;
    $level = intdiv($xp, 500) + 1;
    $intoLevel = $xp % 500;              // XP earned within the current level
    $percent = (int) round($intoLevel / 500 * 100);
@endphp

<div class="w-full">
    <div class="flex items-center justify-between mb-1">
        <span class="font-pixel text-[10px] text-[#f5c518]">{{ $label }} · LV {{ $level }}</span>
        <span class="font-pixel text-[10px] text-[#e8e8f0]">{{ number_format($xp) }}</span>
    </div>

    {{-- Sharp FF/Pokemon-style HP bar: no rounding, hard border --}}
    <div class="w-full h-4 bg-[#0c0918] border-2 border-[#2d2050] rounded-none overflow-hidden">
        <div class="h-full bg-gradient-gold rounded-none transition-all duration-300"
             style="width: {{ $percent }}%"></div>
    </div>

    <div class="mt-1 text-right">
        <span class="font-pixel text-[10px] text-[#8888aa]">{{ $intoLevel }}/500</span>
    </div>
</div>
