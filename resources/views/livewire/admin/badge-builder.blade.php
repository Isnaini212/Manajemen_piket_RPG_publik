@php
    use App\Livewire\Admin\BadgeBuilder;
    use Illuminate\Support\Facades\Storage;
@endphp

<div>
    {{-- Header: tombol buat badge baru --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-3">
        <p class="font-pixel text-[9px] text-[#8888aa]">{{ $this->badges->count() }} BADGE TERDAFTAR</p>
        <div class="flex gap-2 w-full sm:w-auto">
            <button wire:click="openManualModal"
                    class="flex-1 sm:flex-none pixel-btn bg-[#2d2050] hover:bg-[#4c1d6e] text-[#f5c518] font-pixel text-[9px] px-4 py-2.5 rounded-none border-2 border-[#f5c518] transition-colors flex items-center justify-center gap-2">
                BERI BADGE MANUAL
            </button>
            <button wire:click="newBadge"
                    class="flex-1 sm:flex-none pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-4 py-2.5 rounded-none shadow-[2px_2px_0px_0px_rgba(0,0,0,0.6)] hover:brightness-110 transition-all flex items-center justify-center gap-2">
                BUAT BADGE BARU
            </button>
        </div>
    </div>

    {{-- Badge Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse ($this->badges as $badge)
            <div class="pixel-box bg-[#14102a] p-4 flex flex-col gap-3 transition-colors hover:bg-[#1e1836] group">
                {{-- Icon + Name --}}
                <div class="flex items-center gap-3">
                    @if ($badge->icon_url)
                        <img src="{{ Storage::url($badge->icon_url) }}" alt="{{ $badge->name }}"
                             class="w-14 h-14 object-cover pixelated border-2 border-[#f5c518] rounded-none shrink-0 bg-[#0c0918]">
                    @else
                        <div class="w-14 h-14 shrink-0 border-2 border-[#f5c518] bg-[#0c0918] flex items-center justify-center">
                            <x-ui.icon name="trophy" class="w-8 h-8 text-[#f5c518]" />
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-cinzel font-bold text-[#e8e8f0] truncate">{{ $badge->name }}</p>
                        <span class="inline-block mt-1 font-pixel text-[7px] text-[#0c0918] bg-[#f5c518] px-2 py-0.5">
                            {{ $badge->student_badges_count }} SISWA
                        </span>
                    </div>
                </div>

                {{-- Description --}}
                @if ($badge->description)
                    <p class="text-xs text-[#8888aa] leading-relaxed line-clamp-2">{{ $badge->description }}</p>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2 pt-1 mt-auto border-t border-[#2d2050]">
                    <button wire:click="editBadge({{ $badge->id }})"
                            class="flex-1 pixel-btn bg-[#2d2050] hover:bg-[#4c1d6e] text-[#f5c518] py-2 rounded-none font-pixel text-[8px] transition-colors">
                        EDIT
                    </button>
                    <button wire:click="deleteBadge({{ $badge->id }})" wire:confirm="Hapus badge ini secara permanen?"
                            class="flex-1 pixel-btn bg-[#2d2050] hover:bg-[#e63946] text-[#e8e8f0] py-2 rounded-none font-pixel text-[8px] transition-colors">
                        HAPUS
                    </button>
                </div>
                
                {{-- List Siswa --}}
                @if($badge->studentBadges->isNotEmpty())
                    <div class="mt-2 pt-3 border-t border-[#2d2050] border-dashed">
                        <p class="font-pixel text-[7px] text-[#8888aa] mb-1.5">DIMILIKI OLEH:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($badge->studentBadges as $sb)
                                <span class="bg-[#2d2050] text-[#e8e8f0] text-[10px] px-2 py-0.5 whitespace-nowrap">
                                    {{ $sb->studentProfile->user->name ?? 'Unknown' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full pixel-box bg-[#14102a] py-16 flex flex-col items-center justify-center gap-4">
                <x-ui.icon name="trophy" class="w-12 h-12 text-[#2d2050]" />
                <p class="font-pixel text-[9px] text-[#8888aa]">BELUM ADA BADGE. BUAT YANG PERTAMA!</p>
            </div>
        @endforelse
    </div>

    {{-- ─── BADGE FORM MODAL ─────────────────────────────────────────── --}}
    <x-modal name="badge-modal" maxWidth="2xl">
        {{-- Modal Title Bar --}}
        <div class="flex items-center justify-between px-5 py-3 border-b-2 border-[#f5c518] bg-[#0c0918]">
            <span class="font-pixel text-[10px] text-[#f5c518]">
                {{ $editingId ? 'EDIT BADGE' : 'BUAT BADGE BARU' }}
            </span>
            <button @click="$dispatch('close-modal', 'badge-modal')" wire:click="cancelForm"
                    class="w-7 h-7 flex items-center justify-center bg-[#e63946] text-[#0c0918] font-pixel text-sm hover:brightness-110 transition">
                &times;
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-5 sm:p-6 overflow-y-auto max-h-[80vh] space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Name --}}
                <div>
                    <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">NAMA BADGE</label>
                    <input type="text" wire:model="formName"
                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2.5 focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518] transition-colors">
                    @error('formName') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Icon --}}
                <div>
                    <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">UNGGAH IKON</label>
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <input type="file" wire:model="formIcon" accept="image/*"
                                   class="w-full text-xs text-[#8888aa] file:mr-3 file:pixel-btn file:bg-[#2d2050] file:text-[#e8e8f0] file:font-pixel file:text-[8px] file:px-3 file:py-2 file:rounded-none file:border-0 hover:file:bg-[#4c1d6e] transition-colors cursor-pointer">
                            <div wire:loading wire:target="formIcon" class="font-pixel text-[8px] text-[#f5c518] mt-2 animate-pulse">Memuat gambar...</div>
                            @error('formIcon') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if ($formIcon)
                            <img src="{{ $formIcon->temporaryUrl() }}" alt="Preview"
                                 class="w-12 h-12 object-cover pixelated border-2 border-[#f5c518] rounded-none bg-[#0c0918] shrink-0">
                        @endif
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">DESKRIPSI BADGE</label>
                <textarea wire:model="formDescription" rows="3"
                          class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2.5 focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518] transition-colors"></textarea>
                @error('formDescription') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex flex-col sm:flex-row gap-3 px-5 py-4 border-t-2 border-[#2d2050] bg-[#0c0918]/50">
            <button wire:click="saveBadge" wire:loading.attr="disabled"
                    class="flex-1 pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[11px] px-4 py-3 rounded-none hover:brightness-110 transition-all flex items-center justify-center gap-2">
                <x-ui.icon name="check" class="w-4 h-4" />
                <span wire:loading.remove wire:target="saveBadge">SIMPAN BADGE</span>
                <span wire:loading wire:target="saveBadge" class="animate-pulse">MENYIMPAN...</span>
            </button>
            <button @click="$dispatch('close-modal', 'badge-modal')" wire:click="cancelForm"
                    class="w-full sm:w-auto pixel-btn bg-[#0c0918] text-[#8888aa] font-pixel text-[11px] px-6 py-3 rounded-none hover:text-[#e8e8f0] border-2 border-[#2d2050] transition-colors">
                BATAL
            </button>
        </div>
    </x-modal>

    {{-- ─── MANUAL BADGE ASSIGNMENT MODAL ────────────────────────────── --}}
    <x-modal name="manual-badge-modal" maxWidth="md">
        <div class="flex items-center justify-between px-5 py-3 border-b-2 border-[#f5c518] bg-[#0c0918]">
            <span class="font-pixel text-[10px] text-[#f5c518]">BERI BADGE MANUAL</span>
            <button wire:click="closeManualModal"
                    class="w-7 h-7 flex items-center justify-center bg-[#e63946] text-[#0c0918] font-pixel text-sm hover:brightness-110 transition">
                &times;
            </button>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
            <p class="font-pixel text-[8px] text-[#8888aa] leading-relaxed">
                Pilih siswa dan badge yang ingin diberikan. Fitur ini akan langsung memberikan badge ke siswa tanpa perlu mengecek aturan perolehan.
            </p>

            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">PILIH SISWA</label>
                <select wire:model="manualStudentId" class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2.5 focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518]">
                    <option value="">-- Pilih Siswa --</option>
                    @foreach($this->allStudents as $student)
                        <option value="{{ $student->id }}">
                            {{ $student->user->name }} {{ $student->nis ? '(' . $student->nis . ')' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('manualStudentId') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">PILIH BADGE</label>
                <select wire:model="manualBadgeId" class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2.5 focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518]">
                    <option value="">-- Pilih Badge --</option>
                    @foreach($this->badges as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('manualBadgeId') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 px-5 py-4 border-t-2 border-[#2d2050] bg-[#0c0918]/50">
            <button wire:click="assignManualBadge" wire:loading.attr="disabled"
                    class="flex-1 pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[11px] px-4 py-3 rounded-none hover:brightness-110 transition-all flex items-center justify-center gap-2">
                <x-ui.icon name="check" class="w-4 h-4" />
                <span wire:loading.remove wire:target="assignManualBadge">BERIKAN BADGE</span>
                <span wire:loading wire:target="assignManualBadge" class="animate-pulse">MEMPROSES...</span>
            </button>
        </div>
    </x-modal>
</div>
