@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-[#0c0918] border border-[#2d2050] text-[#e8e8f0] focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518] rounded-none shadow-sm transition-colors']) }}>
