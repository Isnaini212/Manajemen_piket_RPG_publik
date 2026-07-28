@php
    use App\Enums\VerifyStatus;

    $slot = $this->claim?->dutySlot;
    $date = $slot?->duty_date;
    $sub  = $this->existingSubmission;
    $status     = $sub?->verify_status;
    $canUpload  = ! $sub || in_array($status, [VerifyStatus::Rejected, VerifyStatus::Pending]);
    $isPendingEdit = $sub && $status === VerifyStatus::Pending;
@endphp

<div class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-3">

    {{-- Slot info --}}
    <p class="font-pixel text-[10px] text-[#f5c518] mb-2">
        {{ $date ? $date->locale('id')->translatedFormat('l, d M Y') : 'Piket' }}
    </p>

    {{-- Existing submission preview --}}
    @if ($sub)
        @php
            [$label, $cls] = match ($status) {
                VerifyStatus::Approved => ['✅ APPROVED', 'text-[#2dc653] border-[#2dc653]'],
                VerifyStatus::Rejected => ['✖ DITOLAK', 'text-[#e63946] border-[#e63946]'],
                VerifyStatus::RejectedFinal => ['☠ GAGAL PERMANEN', 'text-[#e63946] border-[#e63946]'],
                default => ['⏳ MENUNGGU VERIFIKASI', 'text-[#f5c518] border-[#f5c518]'],
            };
        @endphp

        <div class="mb-3">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($sub->proof_url) }}" alt="Bukti piket"
                 class="w-full max-h-48 object-cover pixelated border-2 border-[#2d2050] rounded-none">
            <span class="inline-block mt-2 font-pixel text-[8px] px-2 py-1 rounded-none border-2 {{ $cls }}">{{ $label }}</span>
        </div>

        @if ($status === VerifyStatus::Rejected)
            @php $latestRejection = $sub->histories->first(); @endphp
            <div class="bg-[#e63946]/10 border-2 border-[#e63946] rounded-none p-2 mb-3 space-y-1">
                <p class="font-pixel text-[8px] text-[#e63946]">BUKTI DITOLAK — Percobaan ke-{{ $sub->resubmit_count + 1 }}</p>
                @if ($latestRejection?->reason)
                    <p class="text-xs text-[#e8e8f0] leading-relaxed">
                        <span class="text-[#8888aa]">Alasan: </span>{{ $latestRejection->reason }}
                    </p>
                @else
                    <p class="text-xs text-[#8888aa]">Silakan upload ulang bukti yang lebih jelas.</p>
                @endif
            </div>
        @elseif ($isPendingEdit)
            <div class="bg-[#f5c518]/10 border-2 border-[#f5c518]/50 rounded-none p-2 mb-3">
                <p class="font-pixel text-[8px] text-[#f5c518]">GANTI FOTO — Sedang menunggu verifikasi admin. Kamu boleh mengganti foto jika salah kirim.</p>
            </div>
        @elseif ($status === VerifyStatus::RejectedFinal)
            <div class="bg-[#e63946]/10 border-2 border-[#e63946] rounded-none p-2">
                <p class="text-xs text-[#e63946]">Piket ini ditolak secara final. Tidak bisa upload ulang.</p>
            </div>
        @endif
    @endif

    {{-- Upload form (new or resubmit) --}}
    @if ($canUpload)
        <form wire:submit="submit" class="space-y-3">
            {{-- Preview of newly picked photo --}}
            @if ($photo)
                <div class="relative">
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview"
                         class="w-full max-h-48 object-cover pixelated border-2 border-[#f5c518] rounded-none">
                    <button type="button" wire:click="$set('photo', null)"
                            class="absolute top-1 right-1 pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[8px] px-2 py-1 rounded-none">
                        HAPUS
                    </button>
                </div>
            @else
                {{-- Dashed drop area triggered by hidden input --}}
                <label class="flex flex-col items-center justify-center gap-2 p-6 border-2 border-dashed border-[#2d2050]
                              rounded-none cursor-pointer text-[#8888aa] hover:border-[#f5c518] hover:text-[#f5c518] transition-colors">
                    <x-ui.icon name="clipboard" class="w-8 h-8" />
                    <span class="font-pixel text-[10px]">TAP UNTUK PILIH FOTO</span>
                    <span class="font-sans text-[10px] text-[#5a5a7d]">JPG, PNG, WEBP (Maksimal 5MB)</span>
                    <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
            @endif

            <div wire:loading wire:target="photo" class="font-pixel text-[8px] text-[#8888aa]">Memuat foto...</div>

            @error('photo')
                <p class="text-xs text-[#e63946]">{{ $message }}</p>
            @enderror

            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit,photo"
                    @disabled(! $photo)
                    class="w-full pixel-btn font-pixel text-[10px] px-3 py-2 rounded-none
                           {{ $photo ? 'bg-[#2dc653] text-[#0c0918]' : 'bg-[#2d2050] text-[#8888aa] cursor-not-allowed' }}">
                <span wire:loading.remove wire:target="submit">
                    @if ($isPendingEdit) GANTI FOTO @elseif ($sub) UPLOAD ULANG @else UPLOAD BUKTI @endif
                </span>
                <span wire:loading wire:target="submit">MENGUNGGAH...</span>
            </button>
        </form>
    @endif
</div>
