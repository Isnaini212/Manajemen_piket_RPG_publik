@php
    use App\Enums\ClaimStatus;
    use App\Enums\VerifyStatus;

    $slots = $this->slots;
    $quota = $this->weeklyQuota;
    $claimed = $this->claimedThisWeek;
    $progressPct = $quota > 0 ? min(100, (int) round($claimed / $quota * 100)) : 0;
    $isConvict = $this->profile?->isConvict() ?? false;

    $tabs = ['all' => 'Semua', 'available' => 'Tersedia', 'mine' => 'Milik Saya'];
@endphp

<div class="space-y-4">

    {{-- Convict banner --}}
    @if ($isConvict)
        <div class="bg-[#e63946]/10 border-2 border-[#e63946] rounded-none p-3">
            <p class="font-pixel text-[10px] text-[#e63946] leading-relaxed">
                MODE HUKUMAN — Kamu wajib menyelesaikan {{ $quota }} misi hukuman minggu ini untuk menebus statusmu.
            </p>
        </div>
    @endif

    {{-- Header + week navigator --}}
    <x-ui.card>
        <div class="flex items-center justify-between gap-2 mb-4">
            <button wire:click="previousWeek" class="pixel-btn bg-[#0c0918] text-[#f5c518] font-pixel text-xs px-3 py-2 rounded-none">‹</button>
            <div class="text-center">
                <p class="font-pixel text-[8px] text-[#8888aa]">MINGGU</p>
                <p class="font-cinzel text-[#e8e8f0] text-sm">{{ $this->weekLabel }}</p>
            </div>
            <button wire:click="nextWeek" class="pixel-btn bg-[#0c0918] text-[#f5c518] font-pixel text-xs px-3 py-2 rounded-none">›</button>
        </div>

        <div class="mb-4 border-t border-[#2d2050] pt-3">
            <label class="text-[10px] font-semibold text-[#8888aa] block mb-1 tracking-wide">PILIH MINGGU AKTIF</label>
            <select wire:model.live="selectedWeek"
                    class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-3 py-2 focus:border-[#f5c518] focus:outline-none rounded-none cursor-pointer">
                @foreach ($this->weekOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Weekly progress --}}
        <div class="mt-4">
            @if ($claimed >= $quota && $quota > 0)
                {{-- Farming Mode --}}
                <div class="flex items-center justify-between mb-1">
                    <span class="font-pixel text-[10px] text-[#f5c518]">
                        +BONUS — Sudah klaim {{ $claimed }} misi (Wajib: {{ $quota }})
                    </span>
                    <span class="font-pixel text-[10px] text-[#2dc653]">+{{ $claimed - $quota }} bonus</span>
                </div>
                <div class="w-full h-3 bg-[#0c0918] border-2 border-[#f5c518]/60 rounded-none overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-[#f5c518] to-[#2dc653] animate-pulse" style="width: 100%"></div>
                </div>
            @else
                {{-- Normal progress --}}
                <div class="flex items-center justify-between mb-1">
                    <span class="font-pixel text-[10px] text-[#8888aa]">Sudah klaim {{ $claimed }} dari {{ $quota }} misi wajib</span>
                    <span class="font-pixel text-[10px] text-[#f5c518]">{{ $progressPct }}%</span>
                </div>
                <div class="w-full h-3 bg-[#0c0918] border-2 border-[#2d2050] rounded-none overflow-hidden">
                    <div class="h-full bg-gradient-gold" style="width: {{ $progressPct }}%"></div>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Filter tabs --}}
    <div class="flex gap-4 border-b border-[#2d2050]">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')"
                    class="font-pixel text-[10px] pb-2 -mb-px border-b-2
                           {{ $filter === $key ? 'text-[#f5c518] border-[#f5c518]' : 'text-[#8888aa] border-transparent' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Slot grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @forelse ($slots as $slot)
            @php
                $date = $slot->duty_date;
                $mine = $slot->user_claim;
                $remaining = $slot->remaining_quota;
                $isPast = $date && $date->startOfDay()->lt(today());
                $canClaim = ! $mine && $remaining > 0 && ! $isPast; // Bisa klaim hari ini, tidak bisa hari kemarin ke belakang
                $sub = $mine?->submission;
            @endphp

            <div class="bg-[#14102a] pixel-box rounded-none p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-cinzel text-lg text-[#e8e8f0]">
                            {{ $date ? $date->locale('id')->translatedFormat('l') : '—' }}
                        </p>
                        <p class="text-xs text-[#8888aa]">
                            {{ $date ? $date->locale('id')->translatedFormat('d M Y') : '' }}
                        </p>
                    </div>

                    @if ($remaining > 0)
                        <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 text-[#2dc653] border-[#2dc653]">{{ $remaining }} SLOT</span>
                    @else
                        <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 text-[#e63946] border-[#e63946]">PENUH</span>
                    @endif
                </div>

                @if ($mine)
                    @php
                        $activeReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $mine->id)
                            ->where('status', \App\Enums\ReplacementStatus::OFFERED)
                            ->first();
                        $isFailed = $mine->status === ClaimStatus::Failed;
                        $canUpload = !$isFailed && ($date?->isToday() || ($activeReplacement && !$activeReplacement->isExpired()));
                    @endphp
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="font-pixel text-[8px] px-2 py-1 rounded-none bg-[#f5c518] text-[#0c0918]">MILIK KAMU</span>

                        @if ($activeReplacement)
                            <span class="font-pixel text-[8px] px-2 py-1 rounded-none bg-[#e63946] text-white">PIKET PENGGANTI</span>
                        @endif

                        @if ($isFailed)
                            <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 text-[#e63946] border-[#e63946]">GAGAL PIKET</span>
                        @endif

                        @if ($sub)
                            @php
                                [$label, $cls] = match ($sub->verify_status) {
                                    VerifyStatus::Approved => ['APPROVED', 'text-[#2dc653] border-[#2dc653]'],
                                    VerifyStatus::Rejected => ['DITOLAK', 'text-[#e63946] border-[#e63946]'],
                                    VerifyStatus::RejectedFinal => ['GAGAL', 'text-[#e63946] border-[#e63946]'],
                                    default => ['MENUNGGU VERIFIKASI', 'text-[#f5c518] border-[#f5c518]'],
                                };
                            @endphp
                            <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 {{ $cls }}">{{ $label }}</span>
                        @endif
                    </div>

                    {{-- Tombol Lihat / Upload Bukti + Batal --}}
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        @php
                            $hasProof   = $sub !== null;
                            $canEdit    = $hasProof && $canUpload && in_array($sub->verify_status, [VerifyStatus::Pending, VerifyStatus::Rejected]);

                            if (! $hasProof && $canUpload) {
                                $proofBtnLabel = $activeReplacement ? 'UPLOAD BUKTI PENGGANTI' : 'UPLOAD BUKTI';
                                $proofBtnClass = 'bg-[#f5c518] text-[#0c0918]';
                                $showProofBtn  = true;
                            } elseif ($canEdit) {
                                $proofBtnLabel = 'LIHAT / EDIT BUKTI';
                                $proofBtnClass = 'bg-[#3b82f6] text-white';
                                $showProofBtn  = true;
                            } elseif ($hasProof) {
                                $proofBtnLabel = 'LIHAT BUKTI';
                                $proofBtnClass = 'bg-[#2d2050] text-[#e8e8f0]';
                                $showProofBtn  = true;
                            } else {
                                $showProofBtn  = false;
                            }
                        @endphp

                        @if ($showProofBtn)
                            <button wire:click="openProofModal({{ $mine->id }})"
                                    class="pixel-btn {{ $proofBtnClass }} font-pixel text-[10px] px-3 py-2 rounded-none cursor-pointer">
                                {{ $proofBtnLabel }}
                            </button>
                        @endif

                        @if ($date && $date->gt(today()))
                            <button wire:click="cancelClaim({{ $mine->id }})"
                                    wire:confirm="Apakah kamu yakin ingin membatalkan jadwal piket ini?"
                                    class="pixel-btn bg-[#e63946] text-white font-pixel text-[10px] px-3 py-2 rounded-none cursor-pointer">
                                BATAL
                            </button>
                        @endif
                    </div>
                @else
                    {{--
                        Multi-Klaim: Siswa dapat mengklaim slot ini selama:
                        1. Slot belum penuh (remaining > 0)
                        2. Kuota mingguannya belum habis (claimed < quota)
                    --}}
                    <button wire:click="claimSlot({{ $slot->id }})"
                            wire:loading.attr="disabled"
                            wire:target="claimSlot({{ $slot->id }})"
                            @disabled(! $canClaim)
                            class="mt-3 w-full pixel-btn font-pixel text-[10px] px-3 py-2 rounded-none
                                   {{ $canClaim ? 'bg-[#2dc653] text-[#0c0918]' : 'bg-[#2d2050] text-[#8888aa] cursor-not-allowed' }}">
                        <span wire:loading.remove wire:target="claimSlot({{ $slot->id }})">
                            @if ($remaining <= 0) PENUH
                            @elseif ($isPast) LEWAT
                            @else KLAIM
                            @endif
                        </span>
                        <span wire:loading wire:target="claimSlot({{ $slot->id }})">...</span>
                    </button>
                @endif
            </div>
        @empty
            <div class="md:col-span-2">
                <x-ui.card>
                    <p class="text-[#8888aa] text-sm text-center py-4">Tidak ada slot piket untuk filter ini.</p>
                </x-ui.card>
            </div>
        @endforelse
    </div>

    {{-- Modal Pop-up Bukti Piket --}}
    @if ($viewingClaimId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
             wire:click="closeProofModal">
            <div class="w-full max-w-md bg-[#0c0918] pixel-box border-2 border-[#f5c518] flex flex-col max-h-[90vh] overflow-hidden"
                 @click.stop>

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b-2 border-[#2d2050] shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 bg-[#f5c518]"></span>
                        <h3 class="font-pixel text-[11px] text-[#f5c518] uppercase tracking-wider">BUKTI PIKET</h3>
                    </div>
                    <button wire:click="closeProofModal"
                            class="text-[#8888aa] hover:text-[#e63946] font-pixel text-sm px-2 py-1 transition-colors leading-none">
                        ✕
                    </button>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto flex-1 p-4">
                    @livewire('student.upload-proof', ['claimId' => $viewingClaimId], key('proof-modal-'.$viewingClaimId))
                </div>
            </div>
        </div>
    @endif

</div>
