@props([
    'title' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => "bg-[#14102a] pixel-box rounded-none p-4 $class"]) }}>
    @if ($title)
        <h3 class="font-pixel text-[#f5c518] mb-3 text-sm">{{ $title }}</h3>
    @endif

    {{ $slot }}
</div>
