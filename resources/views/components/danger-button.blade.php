<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#e63946] text-[#e8e8f0] font-pixel text-[10px] uppercase tracking-wider pixel-btn rounded-none focus:outline-none transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
