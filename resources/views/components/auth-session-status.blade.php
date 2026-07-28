@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-pixel text-[10px] text-[#2dc653]']) }}>
        {{ $status }}
    </div>
@endif
