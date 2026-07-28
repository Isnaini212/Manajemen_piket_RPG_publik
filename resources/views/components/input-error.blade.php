@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'font-pixel text-[9px] text-[#e63946] space-y-1 mt-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
