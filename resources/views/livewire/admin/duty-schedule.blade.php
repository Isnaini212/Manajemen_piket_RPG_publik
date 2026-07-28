<div class="space-y-4">

    {{-- NAVIGASI MINGGU + TAMBAH SLOT --}}
    <x-ui.card>
        <div class="flex items-center justify-between gap-2 mb-3">
            <button wire:click="previousWeek"
                    wire:loading.attr="disabled"
                    class="pixel-btn bg-[#0c0918] text-[#f5c518] text-sm px-3 py-1.5 rounded-none font-bold cursor-pointer">‹</button>
            <div class="text-center">
                <p class="text-[10px] font-bold text-[#8888aa] uppercase tracking-wider">MINGGU</p>
                <p class="font-cinzel text-[#e8e8f0] text-sm font-semibold">{{ $this->weekLabel }}</p>
            </div>
            <button wire:click="nextWeek"
                    wire:loading.attr="disabled"
                    class="pixel-btn bg-[#0c0918] text-[#f5c518] text-sm px-3 py-1.5 rounded-none font-bold cursor-pointer">›</button>
        </div>

        <div class="mb-3">
            <label class="text-xs font-semibold text-[#8888aa] block mb-1 tracking-wide">PILIH MINGGU AKTIF</label>
            <select wire:model.live="selectedWeek"
                    class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-3 py-2 focus:border-[#f5c518] focus:outline-none rounded-none cursor-pointer">
                @foreach ($this->weekOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button wire:click="$toggle('showAddForm')"
                class="mt-3 w-full pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-3 py-2 rounded-none cursor-pointer">
            {{ $showAddForm ? 'TUTUP PANEL TAMBAH SLOT' : 'TAMBAH SLOT JADWAL BARU' }}
        </button>

        {{-- Add form (collapsible) --}}
        @if ($showAddForm)
            <div class="mt-3 space-y-4 border-t border-[#2d2050] pt-3">
                {{-- Opsi A: Batch Hari (Beberapa Hari Sekaligus) --}}
                <div class="bg-[#0c0918] p-3 border border-[#2d2050] space-y-3">
                    <p class="text-xs font-bold text-[#f5c518]">BUAT BEBERAPA HARI SEKALIGUS</p>
                    
                    {{-- Rekomendasi Kuota Realtime --}}
                    @if (count($batchDays) > 0)
                        <div class="bg-[#0f1a0f] border border-[#2dc653]/40 p-2.5 text-xs text-[#86efac] leading-relaxed">
                            Rekomendasi:
                            Total {{ $this->totalStudentsCount }} siswa ÷ {{ count($batchDays) }} hari = 
                            <span class="text-[#2dc653] font-bold">{{ $this->recommendedBatchQuota }} siswa/hari</span>.
                            <br>
                            Silakan isi kuota sesuai rekomendasi atau sesuai kebutuhan!
                        </div>
                    @endif
                    
                    <div>
                        <label class="text-[10px] text-[#8888aa] block mb-1.5">PILIH HARI & ATUR KUOTA:</label>
                        <div class="grid grid-cols-1 gap-2 text-xs text-[#e8e8f0]">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                                <div class="flex items-center gap-2 bg-[#14102a] p-2 rounded-none border border-[#2d2050]">
                                    <label class="flex items-center gap-1.5 cursor-pointer flex-1">
                                        <input type="checkbox" 
                                               wire:click="toggleBatchDay('{{ $day }}')" 
                                               {{ isset($batchDays[$day]) ? 'checked' : '' }}
                                               class="rounded-none border-[#2d2050] text-[#f5c518] bg-[#0c0918]">
                                        <span class="font-medium">{{ $day }}</span>
                                    </label>
                                    @if (isset($batchDays[$day]))
                                        <div class="flex items-center gap-1 w-32">
                                            <label class="text-[10px] text-[#8888aa]">Kuota:</label>
                                            <input type="number" 
                                                   min="0" 
                                                   max="50" 
                                                   value="{{ $batchDays[$day] }}"
                                                   wire:change="updateBatchDayQuota('{{ $day }}', $event.target.value)"
                                                   class="w-16 bg-[#0c0918] border border-[#2d2050] rounded-none text-[#e8e8f0] text-xs px-2 py-1 text-center">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" wire:click="addBatchSlots"
                            class="w-full pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[9px] px-3 py-2 rounded-none cursor-pointer">
                        BUAT SLOT HARI PILIHAN
                    </button>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- PANEL BAGI JADWAL OTOMATIS --}}
    <x-ui.card>
        <div class="flex items-center justify-between">
            <p class="font-pixel text-[10px] text-[#2dc653]">BAGI JADWAL OTOMATIS</p>
            <button wire:click="$toggle('showAutoAssignForm')" 
                    class="pixel-btn bg-[#14102a] text-[#2dc653] border-[#2d2050] text-xs font-semibold px-3 py-1 rounded-none hover:bg-[#2dc653] hover:text-[#0c0918] cursor-pointer">
                {{ $showAutoAssignForm ? 'Tutup Panel' : 'Buka Panel Pembagian' }}
            </button>
        </div>

        @if ($showAutoAssignForm)
            <div class="mt-3 space-y-3 border-t border-[#2d2050] pt-3">
                @php
                    $totalStudents = $this->totalStudentsCount;
                    $slotsCount = $this->slots->count();
                    $recQuota = $this->recommendedQuota;
                @endphp

                {{-- Statistik ringkas --}}
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="bg-[#0c0918] border border-[#2d2050] rounded-none p-2 text-center">
                        <p class="text-[10px] text-[#8888aa] font-semibold uppercase tracking-wider">Total Siswa</p>
                        <p class="text-xl font-bold text-[#f5c518] font-cinzel mt-0.5">{{ $totalStudents }}</p>
                    </div>
                    <div class="bg-[#0c0918] border border-[#2d2050] rounded-none p-2 text-center">
                        <p class="text-[10px] text-[#8888aa] font-semibold uppercase tracking-wider">Slot Hari Ini</p>
                        <p class="text-xl font-bold text-[#3b82f6] font-cinzel mt-0.5">{{ $slotsCount }}</p>
                    </div>
                    <div class="bg-[#0c0918] border border-[#2d2050] rounded-none p-2 text-center">
                        <p class="text-[10px] text-[#8888aa] font-semibold uppercase tracking-wider">Rekomendasi</p>
                        <p class="text-xl font-bold text-[#2dc653] font-cinzel mt-0.5">
                            {{ $recQuota > 0 ? $recQuota . '/hari' : '-' }}
                        </p>
                    </div>
                </div>

                {{-- Pesan rekomendasi --}}
                @if ($slotsCount > 0 && $totalStudents > 0)
                    <div class="bg-[#0f1a0f] border border-[#2dc653]/40 p-2.5 mb-3 text-xs text-[#86efac] leading-relaxed">
                            Rekomendasi:
                            Untuk {{ $totalStudents }} siswa dengan {{ $slotsCount }} hari piket, kuota ideal adalah
                            <span class="text-[#2dc653] font-bold">{{ $recQuota }} siswa/hari</span>.
                            Silakan sesuaikan kuota slot agar cukup untuk semua siswa.
                        </div>
                @elseif ($slotsCount === 0)
                    <div class="bg-[#1a0f0f] border border-[#e63946]/40 p-2.5 mb-3 text-xs text-[#fca5a5] leading-relaxed">
                            Perhatian: Belum ada slot piket yang dibuat untuk minggu ini. Silakan tambah slot harian atau gunakan looping jadwal terlebih dahulu.
                        </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button wire:click="$set('showAutoAssignConfirm', true)"
                            wire:loading.attr="disabled"
                            class="pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[9px] px-3 py-2.5 rounded-none hover:bg-[#22c55e] transition-colors cursor-pointer">
                         BAGI JADWAL OTOMATIS
                    </button>
                    <button wire:click="runCheckMissed"
                            wire:loading.attr="disabled"
                            class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-3 py-2.5 rounded-none hover:bg-[#ffd700] transition-colors cursor-pointer">
                        <span wire:loading.remove wire:target="runCheckMissed">PENGECEKAN MANUAL SANKSIS</span>
                        <span wire:loading wire:target="runCheckMissed">MEMPROSES...</span>
                    </button>
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Modal Konfirmasi Bagi Jadwal Otomatis --}}
    @if ($showAutoAssignConfirm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/75">
            <div class="bg-[#14102a] pixel-box border-4 border-[#2dc653] rounded-none w-full max-w-md p-6 text-center space-y-4 shadow-2xl relative">
                <div class="text-[#2dc653] text-sm font-bold font-pixel animate-pulse">
                    KONFIRMASI AUTO-ASSIGN
                </div>
                <div class="text-xs font-semibold text-[#e8e8f0] leading-relaxed py-2">
                    Sistem akan otomatis membagikan piket ke siswa yang belum terjadwal di minggu ini secara merata.<br><br>Lanjutkan?
                </div>
                <div class="pt-2 flex gap-3">
                    <button wire:click="$set('showAutoAssignConfirm', false)" 
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                        BATAL
                    </button>
                    <button wire:click="autoAssign" 
                            class="flex-1 pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#22c55e]">
                        BAGIKAN SEKARANG!
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- PANEL LOOP JADWAL (ALAT BANTU) --}}
    <x-ui.card>
        <p class="font-pixel text-[10px] text-[#f5c518] mb-3">DUPLIKASI JADWAL</p>

        <div class="space-y-3 bg-[#0c0918] p-3 border border-[#2d2050]">
            <p class="text-xs font-semibold text-[#8888aa] mb-2 tracking-wide">LOOPING & DUPLIKASI JADWAL</p>
            <div class="mb-3">
                <label class="text-[10px] text-[#5a5a7d] block mb-1">PERIODE LOOPING (MINGGU):</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="1" max="12" wire:model="loopWeeksCount"
                           class="w-16 bg-[#0c0918] border-2 border-[#2d2050] text-center text-[#e8e8f0] text-xs px-2 py-1 focus:border-[#f5c518] focus:outline-none rounded-none">
                    <span class="text-xs text-[#8888aa]">minggu</span>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button wire:click="confirmLoop(false)"
                        wire:loading.attr="disabled"
                        class="flex-1 pixel-btn bg-[#0c0918] border-2 border-[#f5c518] text-[#f5c518]
                               font-pixel text-[9px] px-2 py-2 rounded-none
                               hover:bg-[#f5c518] hover:text-[#0c0918]
                               disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer">
                    <span wire:loading.remove>LOOP SLOT SAJA</span>
                    <span wire:loading class="animate-pulse">PROSES...</span>
                </button>
                <button wire:click="confirmLoop(true)"
                        wire:loading.attr="disabled"
                        class="flex-1 pixel-btn bg-[#f5c518] border-2 border-[#f5c518] text-[#0c0918]
                               font-pixel text-[9px] px-2 py-2 rounded-none
                               hover:bg-[#eab308] hover:border-[#eab308]
                               disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer">
                    <span wire:loading.remove>LOOP SLOT + SISWA</span>
                    <span wire:loading class="animate-pulse">PROSES...</span>
                </button>
            </div>
        </div>
    </x-ui.card>

    {{-- Modal Konfirmasi Looping Jadwal --}}
    @if ($showLoopConfirm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/75">
            <div class="bg-[#14102a] pixel-box border-4 border-[#f5c518] rounded-none w-full max-w-md p-6 text-center space-y-4 shadow-2xl relative">
                <div class="text-[#f5c518] text-sm font-bold font-pixel animate-pulse">
                    KONFIRMASI DUPLIKASI
                </div>
                <div class="text-xs font-semibold text-[#e8e8f0] leading-relaxed py-2">
                    Gandakan semua slot minggu ini untuk <span class="text-[#f5c518] font-bold text-sm">{{ $loopWeeksCount }}</span> minggu ke depan?
                    @if($loopWithStudents)
                        <br><br><span class="text-[#2dc653]">Termasuk daftar siswa (sebagai status Pending).</span>
                    @endif
                </div>
                <div class="pt-2 flex gap-3">
                    <button wire:click="$set('showLoopConfirm', false)" 
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                        BATAL
                    </button>
                    <button wire:click="loopWeekly({{ $loopWithStudents ? 'true' : 'false' }})" 
                            class="flex-1 pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#eab308]">
                        DUPLIKASI SEKARANG!
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DAFTAR SLOT PIKET --}}
    @forelse ($this->slots as $slot)
        @php
            $date      = $slot->duty_date;
            $filled    = $slot->claims->count();
            $remaining = max(0, $slot->quota - $filled);
        @endphp

        <x-ui.card>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-cinzel text-lg text-[#e8e8f0]">{{ $date?->locale('id')->translatedFormat('l') }}</p>
                    <p class="text-xs text-[#8888aa]">{{ $date?->locale('id')->translatedFormat('d M Y') }}</p>
                </div>

                {{-- Quota info --}}
                <div class="text-right">
                    <p class="text-xs font-semibold text-[#8888aa]">
                        KUOTA <span class="text-[#f5c518] font-bold">{{ $slot->quota }}</span>
                    </p>
                    <p class="text-[10px] mt-1 font-medium text-[#8888aa]">
                        <span class="text-[#2dc653] font-bold">{{ $filled }} terisi</span> /
                        <span class="text-[#f5c518] font-bold">{{ $remaining }} sisa</span>
                    </p>
                </div>
            </div>

            {{-- Participants --}}
            <div class="flex items-center justify-between mt-3">
                <div class="flex items-center gap-1">
                    @foreach ($slot->claims->take(3) as $claim)
                        <img src="{{ $claim->student?->avatar_url }}"
                             alt="{{ $claim->student?->user?->name }}"
                             title="{{ $claim->student?->user?->name }}"
                             class="w-7 h-7 rounded-none border border-[#2d2050] object-cover">
                    @endforeach
                    @if ($filled > 3)
                        <span class="text-xs font-semibold text-[#8888aa]">+{{ $filled - 3 }}</span>
                    @endif
                    @if ($filled === 0)
                        <span class="text-xs text-[#8888aa] italic">Belum ada peserta</span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button wire:click="selectSlot({{ $slot->id }})"
                            class="pixel-btn bg-[#0c0918] text-[#3b82f6] px-2 py-1 rounded-none font-semibold text-xs cursor-pointer" title="Detail & Kelola Slot">
                        Detail
                    </button>
                    <button wire:click="confirmDeleteSlot({{ $slot->id }})"
                            class="pixel-btn bg-[#0c0918] text-[#e63946] px-2.5 py-1 rounded-none cursor-pointer" title="Hapus slot">
                        Hapus
                    </button>
                </div>
            </div>
        </x-ui.card>
    @empty
        <x-ui.card>
            <p class="text-[#8888aa] text-sm text-center py-4">Belum ada slot piket di minggu ini.</p>
        </x-ui.card>
    @endforelse

    {{-- Success/Error/Warning retro pop-up modal --}}
    @if ($showModal)
        @php
            $modalBorder = 'border-[#f5c518]';
            $modalTitleColor = 'text-[#f5c518] animate-bounce';
            $modalBtnBg = 'bg-[#f5c518] text-[#0c0918] hover:bg-transparent hover:text-[#f5c518]';
            $modalBtnText = 'OKE, MANTAP!';

            if ($modalType === 'error') {
                $modalBorder = 'border-[#e63946]';
                $modalTitleColor = 'text-[#f87171] animate-pulse';
                $modalBtnBg = 'bg-[#e63946] text-[#e8e8f0] hover:bg-transparent hover:text-[#e63946]';
                $modalBtnText = 'MENGERTI, KEMBALI';
            } elseif ($modalType === 'warning') {
                $modalBorder = 'border-[#d97706]';
                $modalTitleColor = 'text-[#fbbf24] animate-pulse';
                $modalBtnBg = 'bg-[#d97706] text-white hover:bg-transparent hover:text-[#d97706]';
                $modalBtnText = 'BAIKLAH';
            }
        @endphp
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/75">
            <div class="bg-[#14102a] pixel-box border-4 {{ $modalBorder }} rounded-none w-full max-w-md p-6 text-center space-y-4 shadow-2xl relative">
                <div class="{{ $modalTitleColor }} text-sm font-bold font-pixel">
                    {{ $modalTitle }}
                </div>
                <div class="text-xs font-semibold text-[#e8e8f0] leading-relaxed py-2 whitespace-pre-line">
                    {{ $modalMessage }}
                </div>
                <div class="pt-2">
                    <button wire:click="$set('showModal', false)" 
                            class="pixel-btn {{ $modalBtnBg }} font-pixel text-[9px] px-6 py-2 rounded-none transition-colors cursor-pointer">
                        {{ $modalBtnText }}
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Modal Konfirmasi Delete Slot --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/75">
            <div class="bg-[#14102a] pixel-box border-4 border-[#e63946] rounded-none w-full max-w-md p-6 text-center space-y-4 shadow-2xl relative">
                <div class="text-[#f87171] text-sm font-bold font-pixel animate-pulse">
                    KONFIRMASI PENGHAPUSAN
                </div>
                <div class="text-xs font-semibold text-[#e8e8f0] leading-relaxed py-2">
                    Apakah kamu yakin ingin menghapus slot piket ini secara permanen?
                </div>
                <div class="pt-2 flex gap-3">
                    <button wire:click="$set('showDeleteConfirm', false)" 
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                        BATAL
                    </button>
                    <button wire:click="deleteSlot" 
                            class="flex-1 pixel-btn bg-[#e63946] text-[#e8e8f0] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#dc2626]">
                        HAPUS SEKARANG!
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Slot Detail & Edit Modal --}}
    @if ($selectedSlotId)
        <div class="fixed inset-0 z-[50] flex items-center justify-center p-4 bg-black/75 overflow-y-auto">
            <div class="bg-[#14102a] pixel-box border-4 border-[#3b82f6] rounded-none w-full max-w-lg p-6 space-y-4 shadow-2xl relative my-8">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-[#2d2050] pb-3">
                    <span class="font-pixel text-[10px] text-[#3b82f6] uppercase">KELOLA SLOT PIKET</span>
                    <button wire:click="closeSlotDetails" class="text-[#8888aa] hover:text-white text-xs font-semibold">Tutup</button>
                </div>

                {{-- Slot Fields --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-[#8888aa] block mb-1">TANGGAL SLOT</label>
                        <input type="date" wire:model="editSlotDate"
                               class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#f5c518] text-xs px-2 py-1.5 focus:border-[#3b82f6] focus:outline-none rounded-none"
                               style="color-scheme: dark;">
                        @error('editSlotDate') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-[#8888aa] block mb-1">KUOTA PIKET</label>
                        <input type="number" min="1" max="50" wire:model="editSlotQuota"
                               class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-2 py-1.5 focus:border-[#3b82f6] focus:outline-none rounded-none">
                        @error('editSlotQuota') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Claims List --}}
                <div class="border-t border-[#2d2050] pt-3">
                    <p class="text-xs font-bold text-[#f5c518] mb-2 tracking-wide">PESERTA PIKET (KLAIM)</p>
                    
                    @if (empty($slotClaimsData))
                        <p class="text-xs text-[#8888aa] italic mb-3">Belum ada siswa yang mengambil misi ini.</p>
                    @else
                        <div class="space-y-3 max-h-48 overflow-y-auto mb-3 pr-1">
                            @foreach ($slotClaimsData as $index => $claimData)
                                @php
                                    $isApproved = isset($claimData['status']) && $claimData['status'] === 'approved';
                                @endphp
                                <div class="pixel-box bg-[#0c0918] border border-[#2d2050] p-2 flex items-center justify-between gap-2 {{ $isApproved ? 'border-l-4 border-l-[#2dc653]' : '' }}">
                                    <div class="flex-1 space-y-1">
                                        {{-- Student Selector --}}
                                        <label class="text-[10px] font-semibold text-[#8888aa] block">Siswa:</label>
                                        <select wire:model="slotClaimsData.{{ $index }}.student_id"
                                                {{ $isApproved ? 'disabled' : '' }}
                                                class="w-full bg-[#14102a] border border-[#2d2050] text-[#e8e8f0] text-xs p-1 focus:border-[#3b82f6] focus:outline-none rounded-none {{ $isApproved ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}">
                                            @foreach ($this->allStudents as $std)
                                                <option value="{{ $std->id }}">{{ $std->user?->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-24 space-y-1">
                                        {{-- Type Display Only --}}
                                        <label class="text-[10px] font-semibold text-[#8888aa] block">Tipe:</label>
                                        <div class="w-full bg-[#14102a] border border-[#2d2050] text-[#e8e8f0] text-xs p-1 rounded-none opacity-70 cursor-not-allowed">
                                            @php
                                                $typeText = [
                                                    'regular' => 'Regular',
                                                    'replacement' => 'Ganti Nyawa',
                                                    'punishment' => 'Hukuman'
                                                ];
                                            @endphp
                                            {{ $typeText[$claimData['claim_type'] ?? 'regular'] ?? 'Regular' }}
                                        </div>
                                    </div>
                                    <div class="w-20 space-y-1">
                                        {{-- Status Display Only --}}
                                        <label class="text-[10px] font-semibold text-[#8888aa] block">Status:</label>
                                        <div class="w-full bg-[#14102a] border border-[#2d2050] text-[#e8e8f0] text-xs p-1 rounded-none opacity-70 cursor-not-allowed">
                                            @php
                                                $statusText = [
                                                    'pending' => 'Pending',
                                                    'approved' => 'Setuju',
                                                    'rejected' => 'Tolak',
                                                    'failed' => 'Gagal'
                                                ];
                                            @endphp
                                            {{ $statusText[$claimData['status'] ?? 'pending'] ?? 'Pending' }}
                                        </div>
                                    </div>
                                    <div class="pt-3">
                                        @if (!$isApproved)
                                            <button type="button" wire:click="removeClaimFromSlot({{ $index }})"
                                                    class="pixel-btn bg-[#e63946]/20 text-[#f87171] border-[#e63946] px-2 py-1.5 rounded-none text-xs hover:bg-[#e63946] hover:text-white cursor-pointer"
                                                    title="Hapus Klaim">
                                                Hapus
                                            </button>
                                        @else
                                            <span class="text-[#2dc653] text-xs font-bold">Tetap</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Add New Claim Area --}}
                <div class="border-t border-[#2d2050] pt-3 bg-[#111122] p-3 border-2 border-[#2d2050]">
                    <p class="text-xs font-bold text-[#8888aa] mb-2">TAMBAH KLAIM SISWA SECARA MANUAL</p>
                    <div class="mb-2">
                        <select wire:model="newClaimStudentId"
                                class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-2 py-1.5 focus:border-[#f5c518] focus:outline-none rounded-none cursor-pointer">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($this->allStudents as $std)
                                <option value="{{ $std->id }}">{{ $std->user?->name }} ({{ $std->isConvict() ? 'Hukuman' : 'Regular' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" wire:click="addClaimToSlot"
                            class="w-full pixel-btn bg-[#2dc653] text-[#0c0918] font-semibold text-xs py-1.5 px-3 rounded-none cursor-pointer">
                        MASUKKAN SISWA KE SLOT
                    </button>
                </div>

                {{-- Modal Footer --}}
                <div class="flex gap-2 border-t border-[#2d2050] pt-3">
                    <button wire:click="saveSlotDetails"
                            class="flex-1 pixel-btn bg-[#3b82f6] text-white font-semibold text-xs py-2 px-3 rounded-none hover:bg-[#2563eb] cursor-pointer">
                        SIMPAN PERUBAHAN
                    </button>
                    <button wire:click="closeSlotDetails"
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-semibold text-xs py-2 px-3 rounded-none cursor-pointer">
                        BATAL
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>