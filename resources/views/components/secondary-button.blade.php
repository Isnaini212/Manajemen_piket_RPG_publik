<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-[#14102a] text-[#8888aa] font-pixel text-[10px] uppercase tracking-wider pixel-btn rounded-none hover:text-[#e8e8f0] focus:outline-none disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
