@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-pixel text-[9px] text-[#8888aa] mb-1']) }}>
    {{ $value ?? $slot }}
</label>
