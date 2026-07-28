@php
    use App\Enums\SwapStatus;

    $incoming = $this->incomingRequests;
    $mine = $this->myRequests;
    $history = $this->allHistory;
    $myClaims = $this->myActiveClaims;
    $eligible = $this->eligibleStudents;

    $tabs = [
        'ajukan' => 'Ajukan Tukar',
        'masuk' => 'Request Masuk',
        'riwayat' => 'Riwayat',
    ];
@endphp

<div class="space-y-4" x-data="{ showDetail: false, detailData: {} }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="font-pixel text-sm text-[#f5c518]">TUKAR JADWAL</h2>
        <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 border-[#2d2050] text-[#8888aa]">
            {{ $this->swapUsed }} / {{ $this->swapMax }} tukar bulan ini
        </span>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-4 border-b border-[#2d2050]">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('activeTab', '{{ $key }}')"
                    class="font-pixel text-[10px] pb-2 -mb-px border-b-2 flex items-center gap-1
                           {{ $activeTab === $key ? 'text-[#f5c518] border-[#f5c518]' : 'text-[#8888aa] border-transparent' }}">
                {{ $label }}
                @if ($key === 'masuk' && $incoming->count() > 0)
                    <span class="bg-[#e63946] text-[#0c0918] font-pixel text-[8px] px-1 rounded-none">{{ $incoming->count() }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- TAB: Ajukan --}}
    @if ($activeTab === 'ajukan')
        <x-ui.card>
            @if ($this->hasPendingSwap)
                <p class="text-[#e63946] text-sm">Hanya bisa melakukan 1x swap di waktu yang sama.</p>
            @elseif ($this->swapUsed >= $this->swapMax)
                <p class="text-[#e63946] text-sm">Limit tukar jadwal bulan ini sudah habis.</p>
            @elseif ($myClaims->isEmpty())
                <p class="text-[#8888aa] text-sm italic">Tidak ada jadwal piket minggu ini yang bisa ditukar.</p>
            @else
                <div class="space-y-4">
                    <div>
                        <label class="font-pixel text-[10px] text-[#8888aa] block mb-1">PILIH JADWAL MU</label>
                        <select wire:model.live="selectedClaimId"
                                class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518]">
                            <option value="">— pilih jadwal —</option>
                            @foreach ($myClaims as $claim)
                                <option value="{{ $claim->id }}">
                                    {{ optional($claim->dutySlot?->duty_date)->locale('id')->translatedFormat('l, d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($selectedClaimId)
                        <div>
                            <label class="font-pixel text-[10px] text-[#8888aa] block mb-2">SISWA & JADWAL YANG BISA DITUKAR</label>
                            @if ($eligible->isEmpty())
                                <p class="text-[#8888aa] text-sm italic">Tidak ada siswa lain dengan jadwal di minggu yang sama.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($eligible as $student)
                                        <div class="p-3 border-2 rounded-none {{ $selectedStudentId === $student->id ? 'border-[#f5c518] bg-[#f5c518]/10' : 'border-[#2d2050]' }}">
                                            <div class="flex items-center gap-3 mb-2">
                                                <input type="radio" wire:model.live="selectedStudentId" value="{{ $student->id }}" class="accent-[#f5c518]">
                                                <img src="{{ $student->avatar_url }}"
                                                     alt="{{ $student->user?->name }}"
                                                     class="w-8 h-8 rounded-none border border-[#2d2050] object-cover shrink-0">
                                                <span class="text-sm text-[#e8e8f0]">{{ $student->user?->name }}</span>
                                            </div>

                                            @if ($selectedStudentId === $student->id && $student->dutyClaims->isNotEmpty())
                                                <div class="ml-8 space-y-1">
                                                    <p class="text-[10px] text-[#8888aa]">Pilih jadwal yang ingin ditukar:</p>
                                                    @foreach ($student->dutyClaims as $targetClaim)
                                                        <label class="flex items-center gap-2 cursor-pointer">
                                                            <input type="radio"
                                                                   wire:model.live="selectedTargetClaimId"
                                                                   value="{{ $targetClaim->id }}"
                                                                   class="accent-[#f5c518]">
                                                            <span class="text-xs text-[#e8e8f0]">
                                                                {{ optional($targetClaim->dutySlot?->duty_date)->locale('id')->translatedFormat('l, d M Y') }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <button wire:click="submitSwap"
                                wire:loading.attr="disabled"
                                @disabled(!($selectedStudentId && $selectedTargetClaimId))
                                class="w-full pixel-btn font-pixel text-[10px] px-3 py-2 rounded-none
                                       {{ ($selectedStudentId && $selectedTargetClaimId) ? 'bg-[#2dc653] text-[#0c0918]' : 'bg-[#2d2050] text-[#8888aa] cursor-not-allowed' }}">
                            MINTA TUKAR JADWAL
                        </button>
                    @endif
                </div>
            @endif
        </x-ui.card>
    @endif

    {{-- TAB: Request Masuk --}}
    @if ($activeTab === 'masuk')
        <div class="space-y-3">
            @forelse ($incoming as $req)
                @php
                    $slot = $req->fromClaim?->dutySlot;
                    $targetSlot = $req->toClaim?->dutySlot;
                @endphp
                <x-ui.card>
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-[#e8e8f0]">{{ $req->fromStudent?->user?->name ?? 'Siswa' }}</p>
                            <p class="font-cinzel text-[#f5c518] text-xs">
                                @if ($targetSlot)
                                    Tukar: {{ optional($slot?->duty_date)->locale('id')->translatedFormat('d M Y') }} ↔ {{ optional($targetSlot?->duty_date)->locale('id')->translatedFormat('d M Y') }}
                                @else
                                    {{ optional($slot?->duty_date)->locale('id')->translatedFormat('l, d M Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button wire:click="respondRequest({{ $req->id }}, 'accepted')"
                                    class="pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[8px] px-3 py-2 rounded-none">TERIMA</button>
                            <button wire:click="respondRequest({{ $req->id }}, 'rejected')"
                                    class="pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[8px] px-3 py-2 rounded-none">TOLAK</button>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card>
                    <p class="text-[#8888aa] text-sm italic text-center py-2">Tidak ada request tukar masuk.</p>
                </x-ui.card>
            @endforelse
        </div>
    @endif

    {{-- TAB: Riwayat --}}
    @if ($activeTab === 'riwayat')
        <div class="space-y-3">
            @forelse ($history as $req)
                @php
                    $slot = $req->fromClaim?->dutySlot;
                    $targetSlot = $req->toClaim?->dutySlot;
                    $isOutgoing = $req->from_student_id === $this->profile?->id;
                    [$label, $cls] = match ($req->status) {
                        SwapStatus::Accepted => ['DITERIMA', 'text-[#2dc653] border-[#2dc653]'],
                        SwapStatus::Rejected => ['DITOLAK', 'text-[#e63946] border-[#e63946]'],
                        SwapStatus::Cancelled => ['DIBATALKAN', 'text-[#8888aa] border-[#8888aa]'],
                        default => ['MENUNGGU', 'text-[#f5c518] border-[#f5c518]'],
                    };
                    $detailData = [
                        'id' => $req->id,
                        'status' => $req->status->value,
                        'statusLabel' => $label,
                        'statusCls' => $cls,
                        'from_student' => $req->fromStudent?->user?->name ?? 'Siswa',
                        'from_email' => $req->fromStudent?->user?->email ?? '-',
                        'from_date' => optional($slot?->duty_date)->locale('id')->translatedFormat('l, d M Y') ?? '-',
                        'to_student' => $req->toStudent?->user?->name ?? 'Siswa',
                        'to_email' => $req->toStudent?->user?->email ?? '-',
                        'to_date' => optional($targetSlot?->duty_date)->locale('id')->translatedFormat('l, d M Y') ?? '-',
                        'created_at' => $req->created_at->locale('id')->translatedFormat('d M Y H:i'),
                        'responded_at' => $req->responded_at ? $req->responded_at->locale('id')->translatedFormat('d M Y H:i') : '-',
                    ];
                @endphp
                <x-ui.card @click="detailData = {{ json_encode($detailData) }}; showDetail = true" class="cursor-pointer hover:border-[#f5c518] hover:bg-[#14102a]/80 transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-[#8888aa]">
                                {{ $isOutgoing ? 'ke ' . ($req->toStudent?->user?->name ?? 'Siswa') : 'dari ' . ($req->fromStudent?->user?->name ?? 'Siswa') }}
                            </p>
                            <p class="font-cinzel text-[#e8e8f0] text-xs">
                                @if ($targetSlot)
                                    {{ optional($slot?->duty_date)->locale('id')->translatedFormat('d M Y') }} ↔ {{ optional($targetSlot?->duty_date)->locale('id')->translatedFormat('d M Y') }}
                                @else
                                    {{ optional($slot?->duty_date)->locale('id')->translatedFormat('l, d M Y') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 {{ $cls }}">{{ $label }}</span>
                            @if ($isOutgoing && $req->status === SwapStatus::Pending)
                                <button wire:click="cancelSwap({{ $req->id }})"
                                        @click.stop
                                        class="pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[8px] px-3 py-2 rounded-none">
                                    BATAL
                                </button>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card>
                    <p class="text-[#8888aa] text-sm italic text-center py-2">Belum ada riwayat tukar.</p>
                </x-ui.card>
            @endforelse
        </div>
    @endif

    {{-- MODAL DETAIL --}}
    <div x-show="showDetail" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         x-transition
         @click.self="showDetail = false"
         @keydown.escape.window="showDetail = false">
        <div class="bg-[#0c0918] border border-[#2d2050] rounded-none pixel-box max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-pixel text-sm text-[#f5c518]">Detail Tukar Piket</h3>
                    <button @click="showDetail = false" class="text-[#8888aa] hover:text-[#e8e8f0]">X</button>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-pixel text-[8px] text-[#5a5a7d]" x-text="'#' + detailData.id"></span>
                        <span class="font-pixel text-[8px] px-2 py-1 rounded-none border" 
                              :class="detailData.statusCls" 
                              x-text="detailData.statusLabel"></span>
                    </div>

                    <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Pengirim</p>
                        <p class="text-sm font-semibold text-[#e8e8f0]" x-text="detailData.from_student"></p>
                        <p class="text-xs text-[#5a5a7d]" x-text="detailData.from_email"></p>
                    </div>

                    <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Jadwal Pengirim</p>
                        <p class="text-sm text-[#e8e8f0]" x-text="detailData.from_date"></p>
                    </div>

                    <div class="text-center text-lg text-[#5a5a7d]">↔</div>

                    <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Penerima</p>
                        <p class="text-sm font-semibold text-[#e8e8f0]" x-text="detailData.to_student"></p>
                        <p class="text-xs text-[#5a5a7d]" x-text="detailData.to_email"></p>
                    </div>

                    <div class="p-3 border border-[#2d2050] bg-[#14102a]">
                        <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-1">Jadwal Penerima</p>
                        <p class="text-sm text-[#e8e8f0]" x-text="detailData.to_date"></p>
                    </div>

                    <div class="pt-3 border-t border-[#2d2050]">
                        <div class="flex items-center justify-between text-xs text-[#5a5a7d]">
                            <span>Dibuat: <span x-text="detailData.created_at"></span></span>
                            <span>Respon: <span x-text="detailData.responded_at"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
