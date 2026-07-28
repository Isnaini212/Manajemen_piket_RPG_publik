@php
    use App\Enums\StudentStatus;

    $weeklyTarget = $this->weeklyTarget;
    $weeklyCompleted = $this->weeklyCompleted;
    $weeklyPct = $weeklyTarget > 0 ? min(100, (int) round($weeklyCompleted / $weeklyTarget * 100)) : 0;
@endphp

<div wire:poll.60s class="space-y-4">

    {{-- Stat cards 2x2 --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#14102a] pixel-box rounded-none p-4">
            <p class="font-pixel text-[8px] text-[#8888aa]">TOTAL SISWA</p>
            <p class="font-pixel text-xl text-[#e8e8f0] mt-2">{{ $this->totalSiswa }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-4">
            <p class="font-pixel text-[8px] text-[#8888aa]">CITIZEN</p>
            <p class="font-pixel text-xl text-[#2dc653] mt-2">{{ $this->totalCitizen }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-4">
            <p class="font-pixel text-[8px] text-[#8888aa]">CONVICT</p>
            <p class="font-pixel text-xl text-[#e63946] mt-2">{{ $this->totalConvict }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-4">
            <p class="font-pixel text-[8px] text-[#8888aa]">PENDING HARI INI</p>
            <p class="font-pixel text-xl text-[#f5c518] mt-2">{{ $this->pendingSubmissions }}</p>
        </div>
    </div>

    {{-- Action Card: Manual Cron Trigger --}}
    <div class="bg-[#14102a] pixel-box rounded-none p-4 border-2 border-[#f5c518]/40 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2 h-2 bg-[#f5c518] rounded-none"></span>
                <p class="font-pixel text-[10px] text-[#f5c518] uppercase tracking-wider">Pengecekan Piket Terlewat & Sanksi Otomatis</p>
            </div>
            <p class="text-xs text-[#8888aa] leading-relaxed">Jalankan pengecekan manual untuk mendeteksi piket terlewat kemarin dan menerapkan sanksi/piket pengganti secara langsung.</p>
        </div>
        <button wire:click="runCheckMissed" wire:loading.attr="disabled"
                class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-4 py-2.5 rounded-none transition-colors hover:bg-[#ffd700] cursor-pointer shrink-0 w-full md:w-auto">
            <span wire:loading.remove wire:target="runCheckMissed">JALANKAN PENGECEKAN</span>
            <span wire:loading wire:target="runCheckMissed">MEMPROSES...</span>
        </button>
    </div>

    {{-- Weekly progress --}}
    <x-ui.card title="PROGRES PIKET MINGGU INI">
        <div class="flex items-center justify-between mb-1">
            <span class="font-pixel text-[10px] text-[#8888aa]">{{ $weeklyCompleted }} / {{ $weeklyTarget }} piket disetujui</span>
            <span class="font-pixel text-[10px] text-[#f5c518]">{{ $weeklyPct }}%</span>
        </div>
        <div class="w-full h-3 bg-[#0c0918] border-2 border-[#2d2050] rounded-none overflow-hidden">
            <div class="h-full bg-gradient-gold" style="width: {{ $weeklyPct }}%"></div>
        </div>
    </x-ui.card>

    {{-- Row 2: leaderboard + recent activity --}}
    <div class="lg:grid lg:grid-cols-2 lg:gap-4 space-y-4 lg:space-y-0">

        {{-- Leaderboard --}}
        <x-ui.card title="PERINGKAT XP">
            <div class="space-y-2">
                @forelse ($this->leaderboard as $i => $lp)
                    <div class="flex items-center gap-2">
                        <span class="font-pixel text-[10px] w-5 text-[#8888aa]">{{ $i + 1 }}</span>
                        <img src="{{ $lp->avatar_url }}"
                             alt="{{ $lp->user?->name }}"
                             class="w-7 h-7 rounded-none border border-[#2d2050] object-cover shrink-0">
                        <span class="flex-1 text-sm truncate text-[#e8e8f0]">{{ $lp->user?->name ?? '—' }}</span>
                        <span class="font-pixel text-[10px] text-[#f5c518]">{{ number_format($lp->xp) }}</span>
                    </div>
                @empty
                    <p class="text-[#8888aa] text-sm italic">Belum ada data.</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Recent activity with tabs --}}
        <x-ui.card title="AKTIVITAS TERBARU">
            <div x-data="{ tab: 'swap' }">
                <div class="flex gap-4 border-b border-[#2d2050] mb-3">
                    <button @click="tab = 'swap'"
                            :class="tab === 'swap' ? 'text-[#f5c518] border-[#f5c518]' : 'text-[#8888aa] border-transparent'"
                            class="font-pixel text-[10px] pb-2 -mb-px border-b-2">Tukar</button>
                    <button @click="tab = 'status'"
                            :class="tab === 'status' ? 'text-[#f5c518] border-[#f5c518]' : 'text-[#8888aa] border-transparent'"
                            class="font-pixel text-[10px] pb-2 -mb-px border-b-2">Status</button>
                </div>

                {{-- Swaps --}}
                <div x-show="tab === 'swap'" class="space-y-2">
                    @forelse ($this->recentSwaps as $swap)
                        <div class="text-xs text-[#8888aa] border-t border-[#2d2050] pt-2 first:border-t-0 first:pt-0">
                            <span class="text-[#e8e8f0]">{{ $swap->fromClaim?->student?->user?->name ?? '—' }}</span>
                            → <span class="text-[#e8e8f0]">{{ $swap->toStudent?->user?->name ?? '—' }}</span>
                            <span class="font-pixel text-[8px] text-[#f5c518]">[{{ strtoupper($swap->status->value) }}]</span>
                        </div>
                    @empty
                        <p class="text-[#8888aa] text-sm italic">Belum ada tukar jadwal.</p>
                    @endforelse
                </div>

                {{-- Status changes --}}
                <div x-show="tab === 'status'" x-cloak class="space-y-2">
                    @forelse ($this->recentStatusChanges as $log)
                        <div class="text-xs text-[#8888aa] border-t border-[#2d2050] pt-2 first:border-t-0 first:pt-0">
                            <span class="text-[#e8e8f0]">{{ $log->studentProfile?->user?->name ?? '—' }}</span>
                            <span class="font-pixel text-[8px] {{ $log->to_status === StudentStatus::CONVICT ? 'text-[#e63946]' : 'text-[#2dc653]' }}">
                                → {{ strtoupper($log->to_status->value) }}
                            </span>
                            <span class="block text-[10px]">{{ $log->created_at?->locale('id')->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-[#8888aa] text-sm italic">Belum ada perubahan status.</p>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
