<div class="space-y-4">

    {{-- Filters --}}
    <x-ui.card>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Cari nama, username, atau email siswa..."
                   class="flex-1 bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518] focus:outline-none">

            <select wire:model.live="filterStatus"
                    class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518] focus:outline-none">
                <option value="">Semua Status</option>
                <option value="citizen">Citizen</option>
                <option value="convict">Convict</option>
            </select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card>
        <div class="overflow-x-auto">
            {{-- Desktop View (Table) --}}
            <table class="w-full text-sm hidden md:table">
                <thead>
                    <tr class="border-b border-[#2d2050] text-left">
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2 pr-4">#</th>
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2 pr-4">SISWA</th>
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2 pr-4 text-center">PIKET MINGGU INI</th>
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2d2050]/50">
                    @forelse ($students as $index => $student)
                        @php
                            $rank = $students->firstItem() + $index;
                            $avatarUrl = $student->user?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($student->user?->name ?? 'S') . '&background=2a2a4a&color=f5c518&size=128&bold=true';
                        @endphp
                        <tr class="border-b border-[#2d2050]/50 hover:bg-[#14102a]/50">
                            <td class="py-2 pr-4">
                                <span class="font-pixel text-[10px] text-[#8888aa]">{{ $rank }}</span>
                            </td>
                            <td class="py-2 pr-4">
                                <button wire:click="$dispatch('openProfileModal', { userId: {{ $student->user_id }} })"
                                        class="flex items-center gap-3 w-full text-left focus:outline-none hover:bg-[#2d2050]/30 transition-colors p-1 -ml-1 rounded-none">
                                    <img src="{{ $avatarUrl }}"
                                         alt="{{ $student->user?->name }}"
                                         class="w-9 h-9 rounded-none border-2 border-[#2d2050] object-cover shrink-0">
                                    <div class="min-w-0">
                                        <p class="text-[#e8e8f0] truncate font-semibold">
                                            {{ $student->user?->name ?? '—' }}
                                            @if($student->user?->username)
                                                <span class="font-pixel text-[8px] text-[#f5c518] font-normal ms-1">@<span>{{ $student->user->username }}</span></span>
                                            @endif
                                        </p>
                                        <p class="text-[#8888aa] text-xs truncate">{{ $student->user?->email }}</p>
                                    </div>
                                </button>
                            </td>
                            <td class="py-2 pr-4 text-center">
                                @if($student->has_duty_this_week)
                                    <span class="inline-flex items-center gap-1 font-pixel text-[8px] text-[#2dc653] bg-[#2dc653]/10 border border-[#2dc653] px-2 py-1">
                                        ✔ YA
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-pixel text-[8px] text-[#e63946] bg-[#e63946]/10 border border-[#e63946] px-2 py-1">
                                        × BLM
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="editStudent({{ $student->id }}, '{{ addslashes($student->user?->name) }}')"
                                            class="pixel-btn bg-[#2d2050] text-[#3b82f6] font-pixel text-[8px] px-2.5 py-1.5 rounded-none hover:bg-[#3b82f6] hover:text-[#0c0918] transition-colors focus:outline-none">
                                        ✏ EDIT
                                    </button>
                                    <button wire:click="deleteStudent({{ $student->id }})"
                                            wire:confirm="PERINGATAN! Tindakan ini akan MENGHAPUS PERMANEN akun siswa ini beserta seluruh riwayat piket, bukti foto, dan log nyawanya. Lanjutkan?"
                                            class="pixel-btn bg-[#2d2050] text-[#e63946] font-pixel text-[8px] px-2.5 py-1.5 rounded-none hover:bg-[#e63946] hover:text-[#0c0918] transition-colors focus:outline-none">
                                        🗑 HAPUS
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-[#8888aa]">
                                <p class="font-pixel text-[10px]">Tidak ada siswa ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile View (Cards) --}}
        <div class="md:hidden space-y-3">
            @forelse ($students as $index => $student)
                @php
                    $rank = $students->firstItem() + $index;
                    $avatarUrl = $student->user?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($student->user?->name ?? 'S') . '&background=2a2a4a&color=f5c518&size=128&bold=true';
                @endphp
                <div class="p-3 bg-[#0c0918] border border-[#2d2050] flex items-center justify-between gap-3">
                    <button wire:click="$dispatch('openProfileModal', { userId: {{ $student->user_id }} })"
                            class="flex items-center gap-3 flex-1 min-w-0 text-left focus:outline-none">
                        <span class="font-pixel text-[10px] text-[#8888aa] shrink-0">#{{ $rank }}</span>
                        <img src="{{ $avatarUrl }}"
                             alt="{{ $student->user?->name }}"
                             class="w-10 h-10 rounded-none border-2 border-[#2d2050] object-cover shrink-0">
                        <div class="min-w-0">
                            <p class="text-[#e8e8f0] font-semibold text-sm truncate">
                                {{ $student->user?->name ?? '—' }}
                            </p>
                            @if($student->user?->username)
                                <p class="font-pixel text-[8px] text-[#f5c518] truncate">@<span>{{ $student->user->username }}</span></p>
                            @endif
                            <p class="text-[#8888aa] text-xs truncate">{{ $student->user?->email }}</p>
                        </div>
                    </button>

                    <div class="shrink-0 text-right space-y-2">
                        <div>
                            <p class="font-pixel text-[8px] text-[#8888aa] mb-1">PIKET?</p>
                            @if($student->has_duty_this_week)
                                <span class="inline-flex items-center gap-1 font-pixel text-[8px] text-[#2dc653] bg-[#2dc653]/10 border border-[#2dc653] px-2 py-1">
                                    ✔ YA
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 font-pixel text-[8px] text-[#e63946] bg-[#e63946]/10 border border-[#e63946] px-2 py-1">
                                    × BLM
                                </span>
                            @endif
                        </div>
                        <button wire:click="editStudent({{ $student->id }}, '{{ addslashes($student->user?->name) }}')"
                                class="w-full pixel-btn bg-[#2d2050] text-[#3b82f6] font-pixel text-[8px] px-2 py-1 rounded-none hover:bg-[#3b82f6] hover:text-[#0c0918] transition-colors focus:outline-none mt-1 block">
                            ✏ EDIT
                        </button>
                        <button wire:click="deleteStudent({{ $student->id }})"
                                wire:confirm="PERINGATAN! Tindakan ini akan MENGHAPUS PERMANEN akun siswa ini beserta seluruh riwayat piket, bukti foto, dan log nyawanya. Lanjutkan?"
                                class="w-full pixel-btn bg-[#2d2050] text-[#e63946] font-pixel text-[8px] px-2 py-1 rounded-none hover:bg-[#e63946] hover:text-[#0c0918] transition-colors focus:outline-none mt-1 block">
                            🗑 HAPUS
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-[#8888aa]">
                    <p class="font-pixel text-[10px]">Tidak ada siswa ditemukan.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($students->hasPages())
            <div class="mt-4 border-t border-[#2d2050] pt-4">
                {{ $students->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Modal Edit Siswa --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="bg-[#0c0918] pixel-box w-full max-w-lg border-2 border-[#3b82f6] flex flex-col max-h-[90vh]">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b-2 border-[#2d2050] shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="inline-block w-2 h-2 bg-[#3b82f6] rounded-none"></span>
                        <h3 class="font-pixel text-[11px] text-[#3b82f6] uppercase tracking-wider">Edit Profil Siswa</h3>
                    </div>
                    <button type="button" wire:click="closeEditModal"
                            class="text-[#8888aa] hover:text-[#e63946] font-pixel text-xs px-2 py-1 transition-colors">
                        ✕
                    </button>
                </div>

                {{-- Body (scrollable) --}}
                <div class="overflow-y-auto flex-1 p-4 sm:p-5">
                    <form id="edit-student-form" wire:submit.prevent="updateStudent" class="space-y-4">

                        {{-- BLOK 1: INFORMASI SISWA --}}
                        <div class="border-2 border-[#2d2050] bg-[#14102a] rounded-none">
                            <div class="px-3 py-2 border-b border-[#2d2050] flex items-center gap-2">
                                <span class="inline-block w-1.5 h-1.5 bg-[#3b82f6] rounded-none"></span>
                                <span class="font-pixel text-[8px] text-[#3b82f6] uppercase tracking-wider">Informasi Siswa</span>
                            </div>
                            <div class="p-3 space-y-3">
                                <div>
                                    <label class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider block mb-1.5">NAMA LENGKAP (NAMA ASLI)</label>
                                    <input type="text" wire:model="editName" autocomplete="off"
                                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-3 py-2 focus:border-[#3b82f6] focus:outline-none rounded-none transition-colors">
                                    @error('editName')
                                        <p class="font-pixel text-[8px] text-[#e63946] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider block mb-1.5">USERNAME</label>
                                    <input type="text" wire:model="editUsername" autocomplete="off"
                                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-3 py-2 focus:border-[#3b82f6] focus:outline-none rounded-none transition-colors">
                                    @error('editUsername')
                                        <p class="font-pixel text-[8px] text-[#e63946] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- BLOK 2: FOTO PROFIL --}}
                        <div class="border-2 border-[#2d2050] bg-[#14102a] rounded-none">
                            <div class="px-3 py-2 border-b border-[#2d2050] flex items-center gap-2">
                                <span class="inline-block w-1.5 h-1.5 bg-[#8888aa] rounded-none"></span>
                                <span class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider">Foto Profil</span>
                            </div>
                            <div class="p-3 space-y-3">
                                {{-- Current avatar row --}}
                                <div class="flex items-center gap-3">
                                    @if ($currentAvatarUrl)
                                        <img src="{{ $currentAvatarUrl }}" class="w-10 h-10 rounded-none border-2 border-[#3b82f6] object-cover shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-none border-2 border-[#2d2050] bg-[#0c0918] flex items-center justify-center shrink-0">
                                            <span class="font-pixel text-[6px] text-[#8888aa]">KOSONG</span>
                                        </div>
                                    @endif
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="removeAvatar"
                                               class="rounded-none border-[#2d2050] bg-[#0c0918] text-[#e63946] focus:ring-0">
                                        <span class="font-pixel text-[8px] text-[#e8e8f0] leading-relaxed">
                                            Hapus foto saat ini<br>
                                            <span class="text-[#8888aa] text-[7px]">(kembali ke default)</span>
                                        </span>
                                    </label>
                                </div>

                                {{-- Upload baru --}}
                                <div>
                                    <label class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider block mb-1.5">
                                        Unggah Foto Baru <span class="normal-case text-[#5a5a7d] font-sans">(opsional)</span>
                                    </label>
                                    <input type="file" wire:model="editAvatar" accept="image/*"
                                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-xs px-3 py-2 focus:border-[#3b82f6] focus:outline-none rounded-none cursor-pointer">
                                    @error('editAvatar')
                                        <p class="font-pixel text-[8px] text-[#e63946] mt-1">{{ $message }}</p>
                                    @enderror
                                    <div wire:loading wire:target="editAvatar" class="font-pixel text-[8px] text-[#f5c518] mt-1">Mengunggah...</div>

                                    @if ($editAvatar)
                                        <div class="mt-2 flex items-center gap-3 bg-[#0c0918] border border-[#3b82f6]/40 p-2">
                                            <img src="{{ $editAvatar->temporaryUrl() }}" class="w-10 h-10 object-cover border-2 border-[#3b82f6] rounded-none shrink-0">
                                            <p class="font-pixel text-[7px] text-[#2dc653]">Pratinjau foto baru siap diupload</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- BLOK 3: KONTROL RPG --}}
                        <div class="border-2 border-[#f5c518]/40 bg-[#14102a] rounded-none">
                            <div class="px-3 py-2 border-b border-[#f5c518]/20 flex items-center gap-2">
                                <span class="inline-block w-1.5 h-1.5 bg-[#f5c518] rounded-none"></span>
                                <span class="font-pixel text-[8px] text-[#f5c518] uppercase tracking-wider">Kontrol RPG</span>
                            </div>
                            <div class="p-3 grid grid-cols-2 gap-3">
                                {{-- Status --}}
                                <div>
                                    <label class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider block mb-1.5">STATUS</label>
                                    <select wire:model.live="editStatus"
                                            class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] font-pixel text-[8px] px-2 py-2 focus:border-[#f5c518] focus:outline-none rounded-none cursor-pointer transition-colors">
                                        <option value="citizen">CITIZEN</option>
                                        <option value="convict">CONVICT</option>
                                    </select>
                                    @error('editStatus')
                                        <p class="font-pixel text-[8px] text-[#e63946] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- Nyawa --}}
                                <div>
                                    <label class="font-pixel text-[8px] text-[#8888aa] uppercase tracking-wider block mb-1.5">NYAWA (0-10)</label>
                                    <input type="number" wire:model.live="editLives" min="0" max="10"
                                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] font-pixel text-[8px] px-2 py-2 focus:border-[#f5c518] focus:outline-none rounded-none transition-colors">
                                    @error('editLives')
                                        <p class="font-pixel text-[8px] text-[#e63946] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-span-2 bg-[#0c0918] border border-[#2d2050] px-3 py-2">
                                    <p class="font-pixel text-[7px] text-[#8888aa] leading-relaxed">Perubahan status dicatat ke log sistem. Nyawa = 0 akan otomatis mengubah status menjadi CONVICT.</p>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                {{-- Footer Buttons --}}
                <div class="shrink-0 px-5 py-4 border-t-2 border-[#2d2050] flex gap-3">
                    <button type="button" wire:click="closeEditModal"
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2.5 rounded-none transition-colors hover:text-white hover:bg-[#3a3a5a] cursor-pointer">
                        BATAL
                    </button>
                    <button type="submit" form="edit-student-form"
                            wire:loading.attr="disabled" wire:target="updateStudent"
                            class="flex-1 pixel-btn bg-[#3b82f6] text-[#0c0918] font-pixel text-[9px] px-4 py-2.5 rounded-none transition-colors hover:bg-[#2563eb] cursor-pointer">
                        <span wire:loading.remove wire:target="updateStudent">SIMPAN</span>
                        <span wire:loading wire:target="updateStudent">MENYIMPAN...</span>
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
