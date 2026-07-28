@extends('layouts.admin')

@section('title', 'Manajemen Semester')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-pixel text-sm text-[#f5c518]"> Manajemen Semester</h2>
            <p class="mt-1 text-xs text-[#8888aa]">Kelola semester aktif dan riwayat semester sebelumnya.</p>
        </div>
        <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
                class="font-pixel text-[9px] px-4 py-2 bg-[#f5c518] text-[#0c0918] border-2 border-[#f5c518] hover:bg-transparent hover:text-[#f5c518] transition-colors duration-150 rounded-none pixel-btn">
            + Semester Baru
        </button>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="font-pixel text-[9px] px-4 py-3 bg-[#16a34a]/20 text-[#4ade80] border border-[#16a34a]/40 rounded-none">
             {{ session('success') }}
        </div>
    @endif
    @if ($errors->has('error'))
        <div class="font-pixel text-[9px] px-4 py-3 bg-[#e63946]/20 text-[#f87171] border border-[#e63946]/40 rounded-none">
            ⚠ {{ $errors->first('error') }}
        </div>
    @endif

    {{-- Semester Cards --}}
    @if ($semesters->isEmpty())
        <div class="bg-[#14102a] pixel-box py-16 text-center">
            <p class="font-pixel text-[10px] text-[#8888aa]">⚠ Belum ada semester. Buat semester pertama!</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach ($semesters as $semester)
                <div class="bg-[#14102a] pixel-box rounded-none p-4 space-y-3">
                    {{-- Status badge + nomor --}}
                    <div class="flex items-center justify-between">
                        <span class="font-pixel text-[8px] text-[#5a5a7d]">#{{ $loop->iteration }}</span>
                        @if ($semester->is_active)
                            <span class="font-pixel text-[8px] px-2 py-1 bg-[#16a34a]/20 text-[#4ade80] border border-[#16a34a]/40 rounded-none">
                                AKTIF
                            </span>
                        @else
                            <span class="font-pixel text-[8px] px-2 py-1 bg-[#2d2050] text-[#8888aa] border border-[#2d2050] rounded-none">
                                SELESAI
                            </span>
                        @endif
                    </div>

                    {{-- Nama --}}
                    <p class="text-sm font-semibold text-[#e8e8f0]">{{ $semester->name }}</p>

                    {{-- Tanggal --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-0.5">Mulai</p>
                            <p class="text-[#e8e8f0]">{{ $semester->start_date?->format('d M Y') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-semibold text-[#8888aa] uppercase tracking-wide mb-0.5">Selesai</p>
                            <p class="text-[#e8e8f0]">{{ $semester->end_date?->format('d M Y') ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Aksi --}}
                    <div class="space-y-2 mt-4">
                        @if ($semester->is_active)
                            <button onclick="document.getElementById('modal-reset-{{ $semester->id }}').classList.remove('hidden')"
                                    class="w-full font-pixel text-[8px] px-3 py-2 bg-[#e63946]/10 text-[#f87171] border border-[#e63946]/40 hover:bg-[#e63946]/20 transition-colors rounded-none cursor-pointer">
                                 Reset Semester Ini
                            </button>
                        @endif
                        <button onclick="document.getElementById('modal-delete-{{ $semester->id }}').classList.remove('hidden')"
                                class="w-full font-pixel text-[8px] px-3 py-2 bg-[#e63946]/10 text-[#f87171] border border-[#e63946]/40 hover:bg-[#e63946]/20 transition-colors rounded-none cursor-pointer">
                             Hapus Semester
                        </button>
                    </div>
                </div>

                {{-- Modal Reset (per semester) --}}
                @if ($semester->is_active)
                    <div id="modal-reset-{{ $semester->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70">
                        <div class="bg-[#14102a] pixel-box w-full max-w-md mx-4 p-6 space-y-4 border-4 border-[#e63946]">
                            <h3 class="font-pixel text-xs text-[#e63946] animate-pulse">⚠ Konfirmasi Reset Semester</h3>
                            <p class="text-xs text-[#8888aa] leading-relaxed">
                                Aksi ini akan mereset <strong class="text-[#e8e8f0]">seluruh data siswa</strong> (XP, lives, status) ke nilai awal.
                                Ketik <strong class="text-[#f5c518]">RESET</strong> untuk konfirmasi.
                            </p>
                            <form method="POST" action="{{ route('admin.semesters.reset', $semester) }}">
                                @csrf
                                <input type="text" name="confirmation" placeholder="Ketik RESET"
                                       class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] font-pixel text-[9px] px-3 py-2 focus:border-[#e63946] focus:outline-none rounded-none mb-4" autocomplete="off">
                                <div class="flex gap-3 justify-end">
                                    <button type="button"
                                            onclick="document.getElementById('modal-reset-{{ $semester->id }}').classList.add('hidden')"
                                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                                        Batal
                                    </button>
                                    <button type="submit"
                                            class="flex-1 pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#dc2626]">
                                        Reset Sekarang
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Modal Delete (per semester) --}}
                <div id="modal-delete-{{ $semester->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70">
                    <div class="bg-[#14102a] pixel-box w-full max-w-md mx-4 p-6 space-y-4 border-4 border-[#e63946]">
                        <h3 class="font-pixel text-xs text-[#e63946] animate-pulse">🗑 Konfirmasi Hapus Semester</h3>
                        <p class="text-xs text-[#8888aa] leading-relaxed">
                            Apakah Anda yakin ingin menghapus semester <strong class="text-[#f5c518]">{{ $semester->name }}</strong>?
                            <br><br>
                            Jika ini adalah semester aktif, sistem akan otomatis memilih semester lain yang tersedia sebagai semester aktif.
                        </p>
                        <form method="POST" action="{{ route('admin.semesters.destroy', $semester) }}">
                            @csrf
                            @method('DELETE')
                            <div class="flex gap-3 justify-end mt-4">
                                <button type="button"
                                        onclick="document.getElementById('modal-delete-{{ $semester->id }}').classList.add('hidden')"
                                        class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#3a3a5a] hover:text-white">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="flex-1 pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[9px] px-4 py-2 rounded-none transition-colors cursor-pointer hover:bg-[#dc2626]">
                                    Hapus Permanen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Modal Buat Semester Baru --}}
<div id="modal-create" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70">
    <div class="bg-[#14102a] pixel-box w-full max-w-md mx-4 p-6 space-y-4">
        <h3 class="font-pixel text-xs text-[#f5c518]"> Buat Semester Baru</h3>
        <p class="text-xs text-[#8888aa]">Semester aktif saat ini akan dinonaktifkan secara otomatis.</p>

        <form method="POST" action="{{ route('admin.semesters.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">Nama Semester</label>
                <input type="text" name="name" placeholder="cth: Ganjil 2024/2025" value="{{ old('name') }}"
                       class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518] focus:outline-none rounded-none"
                       required>
                @error('name')
                    <p class="font-pixel text-[8px] text-[#f87171] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">Tanggal Mulai <span class="text-[#5a5a7d]">(klik ikon kalender)</span></label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                       class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#f5c518] text-sm px-3 py-2 focus:border-[#f5c518] focus:outline-none rounded-none"
                       style="color-scheme: dark;"
                       required>
                @error('start_date')
                    <p class="font-pixel text-[8px] text-[#f87171] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">Tanggal Selesai <span class="text-[#5a5a7d]">(klik ikon kalender)</span></label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="w-full bg-[#0c0918] border-2 border-[#2d2050] text-[#f5c518] text-sm px-3 py-2 focus:border-[#f5c518] focus:outline-none rounded-none"
                       style="color-scheme: dark;"
                       required>
                @error('end_date')
                    <p class="font-pixel text-[8px] text-[#f87171] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button"
                        onclick="document.getElementById('modal-create').classList.add('hidden')"
                        class="font-pixel text-[9px] px-4 py-2 border border-[#2d2050] text-[#8888aa] hover:text-[#e8e8f0] transition-colors rounded-none">
                    Batal
                </button>
                <button type="submit"
                        class="font-pixel text-[9px] px-4 py-2 bg-[#f5c518] text-[#0c0918] border border-[#f5c518] hover:bg-transparent hover:text-[#f5c518] transition-colors rounded-none pixel-btn">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
