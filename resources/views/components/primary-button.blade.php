<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] uppercase tracking-wider pixel-btn rounded-none focus:outline-none transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
