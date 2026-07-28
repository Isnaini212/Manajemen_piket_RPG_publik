@props([
    'label' => '',
    'model' => '',
    'disabled' => false,
    'min' => 0,
    'max' => null,
])

<div>
    <label class="font-pixel text-[8px] text-[#8888aa] block mb-1">{{ $label }}</label>
    <input type="number" wire:model="{{ $model }}" @disabled($disabled)
           min="{{ $min }}" @if($max !== null) max="{{ $max }}" @endif
           class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2
                  focus:border-[#f5c518] disabled:opacity-50">
    @error($model) <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
</div>
