@php
    use App\Enums\VerifyStatus;
    use Illuminate\Support\Facades\Storage;

    $statusMeta = function ($status) {
        return match ($status) {
            VerifyStatus::Approved     => ['APPROVED', 'text-[#2dc653] border-[#2dc653]', 'bg-[#2dc653]/10'],
            VerifyStatus::Rejected     => ['DITOLAK', 'text-[#e63946] border-[#e63946]', 'bg-[#e63946]/10'],
            VerifyStatus::RejectedFinal => ['DITOLAK FINAL', 'text-[#e63946] border-[#e63946]', 'bg-[#e63946]/10'],
            default                    => ['PENDING', 'text-[#f5c518] border-[#f5c518]', 'bg-[#f5c518]/10'],
        };
    };

    $tabs = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'];
    $submissions = $this->submissions;
@endphp

<div class="space-y-4">

    {{-- Filter tabs & Cleanup action --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2d2050] pb-3">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1" style="scrollbar-width: none; -ms-overflow-style: none;">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')"
                        class="pixel-btn font-pixel text-[9px] px-3.5 py-2 rounded-none whitespace-nowrap flex items-center gap-1.5 transition-all cursor-pointer
                               {{ $filter === $key 
                                  ? 'bg-[#f5c518] text-[#0c0918] font-bold border-[#f5c518]' 
                                  : 'bg-[#14102a] text-[#8888aa] border-[#2d2050] hover:text-[#e8e8f0] hover:bg-[#2d2050]/60' }}">
                    <span>{{ $label }}</span>
                    @if ($key === 'pending' && $this->pendingCount > 0)
                        <span class="font-pixel text-[8px] px-1.5 py-0.5 rounded-none bg-[#e63946] text-white">
                            {{ $this->pendingCount }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        <button wire:click="openCleanupModal"
                class="pixel-btn bg-[#e63946]/10 text-[#f87171] border border-[#e63946]/40 hover:bg-[#e63946] hover:text-[#0c0918] font-pixel text-[8px] px-3 py-2 rounded-none transition-colors flex items-center gap-1.5 cursor-pointer ml-auto">
            BERSIHKAN FOTO LAMA
        </button>
    </div>

    {{-- Cards grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @forelse ($submissions as $sub)
            @php
                [$label, $cls, $bgCls] = $statusMeta($sub->verify_status);
                $slot = $sub->dutyClaim?->dutySlot;
            @endphp

            <div class="bg-[#14102a] pixel-box rounded-none p-3 space-y-3">
                {{-- Header: siswa + status --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        @if ($sub->proof_url)
                            <img src="{{ Storage::url($sub->proof_url) }}" alt="Bukti"
                                 class="w-12 h-12 object-cover pixelated border border-[#2d2050] rounded-none shrink-0">
                        @else
                            <div class="w-12 h-12 bg-[#0c0918] border border-[#e63946]/40 flex flex-col items-center justify-center shrink-0 font-pixel text-center leading-none overflow-hidden p-0.5 rounded-none">
                                <span class="text-[8px] text-[#e63946] mb-0.5">✕</span>
                                <span class="text-[5px] text-[#8888aa] tracking-tighter">DIHAPUS</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-[#e8e8f0] truncate">
                                {{ $sub->dutyClaim?->student?->user?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-[#8888aa] mt-0.5">
                                {{ optional($slot?->duty_date)->locale('id')->translatedFormat('d M Y') ?? '—' }}
                            </p>
                            @if ($sub->replacement_id)
                                <span class="inline-block font-pixel text-[7px] px-1.5 py-0.5 rounded-none bg-[#e63946]/20 text-[#f87171] border border-[#e63946] mt-1">PIKET PENGGANTI</span>
                            @else
                                <span class="inline-block font-pixel text-[7px] px-1.5 py-0.5 rounded-none bg-[#2dc653]/20 text-[#86efac] border border-[#2dc653] mt-1">PIKET TEPAT WAKTU</span>
                            @endif
                        </div>
                    </div>
                    <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 shrink-0 {{ $cls }} {{ $bgCls }}">
                        {{ $label }}
                    </span>
                </div>

                {{-- Meta info --}}
                <div class="flex items-center justify-between text-xs text-[#5a5a7d]">
                    <span>Upload: {{ optional($sub->uploaded_at)->locale('id')->translatedFormat('d M, H:i') ?? '—' }}</span>
                    <span>Resubmit: <strong class="text-[#8888aa]">{{ $sub->resubmit_count }}x</strong></span>
                </div>

                {{-- Action --}}
                <button wire:click="openDetail({{ $sub->id }})"
                        class="w-full pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-3 py-2 rounded-none cursor-pointer">
                    PERIKSA BUKTI
                </button>
            </div>
        @empty
            <div class="md:col-span-2">
                <x-ui.card>
                    <p class="text-[#8888aa] text-sm text-center py-4">Tidak ada bukti untuk filter ini.</p>
                </x-ui.card>
            </div>
        @endforelse
    </div>

    <div>{{ $submissions->links() }}</div>

    {{-- Detail modal --}}
    @if ($showModal && $this->selectedSubmission)
        @php
            $sub = $this->selectedSubmission;
            $slot = $sub->dutyClaim?->dutySlot;
            [$label, $cls] = $statusMeta($sub->verify_status);
            $dutyDate = $slot?->duty_date;
            $finalAllowed = $dutyDate && \Illuminate\Support\Carbon::parse($dutyDate)->addDays(2)->isPast();
            $isPending = $sub->verify_status === VerifyStatus::Pending || $sub->verify_status === VerifyStatus::Rejected;
        @endphp

        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/70"
             x-data="{ showReject: false }" wire:key="modal-{{ $sub->id }}">
            <div class="bg-[#14102a] pixel-box rounded-none w-full max-w-lg max-h-[90vh] overflow-y-auto p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-pixel text-xs text-[#f5c518]">PERIKSA BUKTI</h3>
                    <button wire:click="closeModal" class="pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[8px] px-2 py-1 rounded-none">✕</button>
                </div>

                {{-- Proof photo --}}
                @if ($sub->proof_url)
                    <img src="{{ Storage::url($sub->proof_url) }}" alt="Bukti"
                         class="w-full max-h-96 object-contain pixelated border-2 border-[#2d2050] rounded-none bg-[#0c0918]">
                @else
                    <div class="w-full py-10 px-4 bg-[#0c0918] border-2 border-[#e63946]/40 text-center space-y-2 rounded-none">
                        <div class="inline-flex items-center justify-center w-10 h-10 bg-[#e63946]/10 border border-[#e63946] text-[#e63946] font-pixel text-xs mb-1">
                            ✕
                        </div>
                        <p class="font-pixel text-[10px] text-[#e63946]">FILE FOTO BUKTI TELAH DIBERSIHKAN</p>
                        <p class="text-xs text-[#8888aa] max-w-sm mx-auto leading-relaxed">
                            File foto fisik bukti ini telah dihapus dari penyimpanan server oleh admin untuk menghemat kapasitas disk. Data riwayat verifikasi & poin XP siswa tetap aman & tersimpan di database.
                        </p>
                    </div>
                @endif

                {{-- Detail --}}
                <div class="mt-3 space-y-1 text-sm">
                    <p class="text-[#e8e8f0]">{{ $sub->dutyClaim?->student?->user?->name ?? '—' }}</p>
                    <p class="text-[#8888aa]">{{ optional($dutyDate)->locale('id')->translatedFormat('l, d M Y') }}</p>
                    <p class="text-[#8888aa]">Upload: {{ optional($sub->uploaded_at)->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                    <p class="text-xs text-[#8888aa]">
                        Tipe Piket: 
                        @if ($sub->replacement_id)
                            <span class="text-[#f87171] font-bold">Piket Pengganti</span>
                        @else
                            <span class="text-[#86efac] font-bold">Piket Tepat Waktu</span>
                        @endif
                    </p>
                    <p><span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 {{ $cls }}">{{ $label }}</span></p>
                </div>

                {{-- Rejection History --}}
                @if ($sub->histories->isNotEmpty())
                    <div class="mt-4 border-t border-[#2d2050] pt-4">
                        <p class="font-pixel text-[9px] text-[#f5c518] mb-3">HISTORI PENOLAKAN ({{ $sub->histories->count() }}x)</p>
                        <div class="space-y-3">
                            @foreach ($sub->histories as $history)
                                <div class="bg-[#0c0918] border border-[#e63946]/40 rounded-none p-2">
                                    <div class="flex gap-3 items-start">
                                        <a href="{{ Storage::url($history->proof_url) }}" target="_blank" class="shrink-0">
                                            <img src="{{ Storage::url($history->proof_url) }}" alt="Bukti lama"
                                                 class="w-16 h-16 object-cover pixelated border border-[#2d2050] rounded-none hover:opacity-80 transition-opacity">
                                        </a>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-pixel text-[7px] text-[#e63946] mb-1">DITOLAK {{ optional($history->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                                            <p class="text-xs text-[#8888aa] break-words">{{ $history->reason ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Actions (only when actionable) --}}
                @if ($isPending)
                    <div class="mt-4 space-y-3">
                        {{-- Approve --}}
                        <button wire:click="openConfirmModal('approve')"
                                class="w-full pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[10px] px-3 py-3 rounded-none">
                            TERIMA
                        </button>

                        {{-- Reject toggle --}}
                        <button type="button" @click="showReject = !showReject"
                                class="w-full pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">
                            TOLAK
                        </button>

                        <div x-show="showReject" x-cloak x-transition class="space-y-2">
                            <textarea wire:model="rejectionReason" rows="3" placeholder="Alasan penolakan..."
                                      class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#e63946]"></textarea>
                            @error('rejectionReason') <p class="text-xs text-[#e63946]">{{ $message }}</p> @enderror

                            <button wire:click="openConfirmModal('reject')"
                                    class="w-full pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[8px] px-3 py-2 rounded-none">
                                TOLAK (BISA UPLOAD ULANG)
                            </button>

                            @if ($finalAllowed)
                                <button wire:click="openConfirmModal('reject_final')"
                                        class="w-full pixel-btn bg-[#0c0918] text-[#e63946] border-[#e63946] font-pixel text-[8px] px-3 py-2 rounded-none">
                                    TOLAK FINAL (deadline lewat)
                                </button>
                            @else
                                <p class="font-pixel text-[8px] text-[#8888aa]">Tolak Final tersedia setelah 2 hari dari tanggal piket.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Custom Confirm Modal --}}
        @if ($showConfirmModal)
            @php
                $modalBorder = 'border-[#f5c518]';
                $modalBtnBg = 'bg-[#f5c518] text-[#0c0918] hover:bg-transparent hover:text-[#f5c518]';
                $modalTitle = 'KONFIRMASI';
                $modalMessage = '';
                
                if ($confirmType === 'approve') {
                    $modalBorder = 'border-[#2dc653]';
                    $modalBtnBg = 'bg-[#2dc653] text-[#0c0918] hover:bg-transparent hover:text-[#2dc653]';
                    $modalTitle = 'KONFIRMASI TERIMA';
                    $modalMessage = 'Setujui bukti ini dan beri reward?';
                } elseif ($confirmType === 'reject') {
                    $modalBorder = 'border-[#e63946]';
                    $modalBtnBg = 'bg-[#e63946] text-[#0c0918] hover:bg-transparent hover:text-[#e63946]';
                    $modalTitle = 'KONFIRMASI TOLAK';
                    $modalMessage = 'Tolak bukti ini? Siswa bisa upload ulang.';
                } elseif ($confirmType === 'reject_final') {
                    $modalBorder = 'border-[#e63946]';
                    $modalBtnBg = 'bg-[#e63946] text-[#0c0918] hover:bg-transparent hover:text-[#e63946]';
                    $modalTitle = 'KONFIRMASI TOLAK FINAL';
                    $modalMessage = 'TOLAK FINAL? Ini akan menggagalkan piket & memicu penalti.';
                }
            @endphp
            <div class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/75">
                <div class="bg-[#14102a] pixel-box border-4 {{ $modalBorder }} rounded-none w-full max-w-md p-6 text-center space-y-4 shadow-2xl relative">
                    <div class="text-sm font-bold font-pixel text-[#e8e8f0]">
                        {{ $modalTitle }}
                    </div>
                    <div class="text-xs font-semibold text-[#e8e8f0] leading-relaxed py-2 whitespace-pre-line">
                        {{ $modalMessage }}
                    </div>
                    <div class="pt-2 flex gap-3">
                        <button wire:click="closeConfirmModal"
                                class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                            BATAL
                        </button>
                        @if ($confirmType === 'approve')
                            <button wire:click="approve"
                                    class="flex-1 pixel-btn {{ $modalBtnBg }} font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer">
                                TERIMA
                            </button>
                        @elseif ($confirmType === 'reject')
                            <button wire:click="reject(false)"
                                    class="flex-1 pixel-btn {{ $modalBtnBg }} font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer">
                                TOLAK
                            </button>
                        @elseif ($confirmType === 'reject_final')
                            <button wire:click="reject(true)"
                                    class="flex-1 pixel-btn {{ $modalBtnBg }} font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer">
                                TOLAK FINAL
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- MODAL BERSIHKAN FOTO BUKTI LAMA --}}
    @if ($showCleanupModal)
        @php
            $stats = $this->cleanupStats;
        @endphp
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-[#14102a] pixel-box border-4 border-[#e63946] rounded-none w-full max-w-lg p-6 space-y-5 shadow-2xl relative">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-[#2d2050] pb-3">
                    <h3 class="font-pixel text-xs text-[#e63946] uppercase tracking-wider">Bersihkan Foto Bukti Lama</h3>
                    <button wire:click="closeCleanupModal" class="text-[#8888aa] hover:text-white font-pixel text-xs">✕</button>
                </div>

                {{-- Filter Options --}}
                <div class="space-y-4">
                    <p class="text-xs text-[#8888aa] leading-relaxed">
                        Fitur ini menghapus <strong class="text-[#e8e8f0]">file foto fisik</strong> di penyimpanan server untuk menghemat ruang disk. 
                        Data riwayat verifikasi & XP siswa di database <strong class="text-[#2dc653]">tetap aman & tersimpan</strong>.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-[#0c0918] p-3 border-2 border-[#2d2050]">
                        <div>
                            <label class="text-[10px] font-pixel text-[#8888aa] block mb-1">PILIH FOTO UNTUK DIHAPUS</label>
                            <select wire:model.live="cleanupTimeframe"
                                    class="w-full bg-[#14102a] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-2.5 py-2 focus:border-[#e63946] focus:outline-none rounded-none cursor-pointer">
                                <option value="1_month">Foto lebih dari 1 Bulan lalu</option>
                                <option value="2_months">Foto lebih dari 2 Bulan lalu</option>
                                <option value="3_months">Foto lebih dari 3 Bulan lalu</option>
                                <option value="before_date">Foto sebelum tanggal tertentu...</option>
                                <option value="all_old">Semua foto kemarin & sebelumnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-pixel text-[#8888aa] block mb-1">STATUS FOTO</label>
                            <select wire:model.live="cleanupStatus"
                                    class="w-full bg-[#14102a] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-2.5 py-2 focus:border-[#e63946] focus:outline-none rounded-none cursor-pointer">
                                <option value="approved">Hanya yang Sudah Disetujui (Disarankan)</option>
                                <option value="rejected">Hanya yang Ditolak</option>
                                <option value="all">Semua Foto (Disetujui & Ditolak)</option>
                            </select>
                        </div>

                        @if ($cleanupTimeframe === 'before_date')
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-pixel text-[#8888aa] block mb-1">TANGGAL BATAS PENYIMPANAN</label>
                                <input type="date" wire:model.live="cleanupBeforeDate"
                                       class="w-full bg-[#14102a] border-2 border-[#2d2050] text-[#f5c518] text-xs px-2.5 py-2 focus:border-[#e63946] focus:outline-none rounded-none"
                                       style="color-scheme: dark;">
                            </div>
                        @endif
                    </div>

                    {{-- Live Counter Box --}}
                    <div class="bg-[#1b1537] border-2 border-[#7c3aed]/50 p-4 rounded-none space-y-2">
                        <p class="font-pixel text-[9px] text-[#8888aa] uppercase">ESTIMASI KAPASITAS YANG DIHEMAT:</p>
                        <div class="flex items-baseline justify-between">
                            <span class="font-pixel text-lg text-[#f5c518] animate-pulse">{{ $stats['formatted_size'] }}</span>
                            <span class="text-xs text-[#8888aa] font-semibold">{{ $stats['file_count'] }} File Foto Ditemukan</span>
                        </div>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="closeCleanupModal"
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2.5 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                        BATAL
                    </button>
                    <button type="button" wire:click="purgeOldProofs"
                            wire:confirm="YAKIN HAPUS {{ $stats['file_count'] }} FILE FOTO? Aksi ini tidak dapat dibatalkan!"
                            @disabled($stats['file_count'] === 0)
                            class="flex-1 pixel-btn font-pixel text-[9px] px-4 py-2.5 rounded-none transition-colors cursor-pointer
                                   {{ $stats['file_count'] > 0 ? 'bg-[#e63946] text-[#0c0918] hover:bg-[#dc2626]' : 'bg-[#2d2050] text-[#8888aa] opacity-50 cursor-not-allowed' }}">
                        HAPUS SEKARANG
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>