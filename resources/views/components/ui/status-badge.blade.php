@props([
    'status' => null,
])

@php
    use App\Enums\StudentStatus;

    // Accept either a StudentStatus enum or its string value.
    $value = $status instanceof StudentStatus ? $status : StudentStatus::tryFrom((string) $status);
    $isConvict = $value === StudentStatus::CONVICT;
@endphp

@if ($isConvict)
    <span class="inline-flex items-center gap-1 font-pixel text-[10px] px-2 py-1 rounded-none
                 bg-[#e63946] text-[#0c0918] pixel-btn animate-pulse-red shrink-0 whitespace-nowrap">
        CONVICT
    </span>
@else
    <span class="inline-flex items-center gap-1 font-pixel text-[10px] px-2 py-1 rounded-none
                 bg-[#2dc653] text-[#0c0918] pixel-btn shrink-0 whitespace-nowrap">
        CITIZEN
    </span>
@endif
