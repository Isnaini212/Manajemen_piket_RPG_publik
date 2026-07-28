@php $sem = $this->activeSemester; @endphp

<div class="space-y-4 pb-24">

    <h1 class="font-cinzel text-xl text-[#f5c518] font-bold"> Konfigurasi Sistem</h1>

    {{-- Active semester + reset --}}
    <x-ui.card title="SEMESTER AKTIF">
        @if ($sem)
            <p class="font-cinzel text-[#e8e8f0]">{{ $sem->name }}</p>
            <p class="text-xs text-[#8888aa] mt-1">
                {{ optional($sem->start_date)->locale('id')->translatedFormat('d M Y') }}
                – {{ optional($sem->end_date)->locale('id')->translatedFormat('d M Y') }}
            </p>
        @else
            <p class="text-[#8888aa] text-sm">Tidak ada semester aktif.</p>
        @endif

        <button wire:click="confirmReset"
                class="mt-3 pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">
             RESET SEMESTER
        </button>
    </x-ui.card>

    {{-- Reset confirm modal --}}
    @if ($showResetConfirm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/70">
            <div class="bg-[#14102a] pixel-box rounded-none w-full max-w-md p-4">
                <h3 class="font-pixel text-xs text-[#e63946] mb-3"> RESET SEMESTER</h3>
                <p class="text-sm text-[#e8e8f0] mb-2">
                    Semua XP, nyawa, dan status siswa akan direset ke awal, dan semester baru dibuat.
                    Riwayat (badge, log) tetap disimpan. Tindakan ini tidak bisa dibatalkan.
                </p>
                <p class="font-pixel text-[8px] text-[#8888aa] mb-1">KETIK "RESET" UNTUK KONFIRMASI</p>
                <input type="text" wire:model="resetConfirmText" placeholder="RESET"
                       class="w-full bg-[#0c0918] border-2 border-[#e63946] rounded-none text-[#e8e8f0] text-sm px-3 py-2 mb-3">
                <div class="flex gap-2">
                    <button wire:click="executeReset" wire:loading.attr="disabled"
                            class="flex-1 pixel-btn bg-[#e63946] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">EKSEKUSI RESET</button>
                    <button wire:click="$set('showResetConfirm', false)"
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[10px] px-3 py-2 rounded-none">BATAL</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Missions --}}
    <x-ui.card title="PENGATURAN MISI">
        <div class="grid grid-cols-2 gap-3">
            <x-config-field label="Misi wajib Citizen / minggu" model="configs.citizen_weekly_missions" min="1" max="7" />
            <x-config-field label="Misi wajib Convict / minggu" model="configs.convict_weekly_missions" min="1" max="6" />
        </div>
    </x-ui.card>

    {{-- Lives & status --}}
    <x-ui.card title="NYAWA & STATUS">
        <div class="grid grid-cols-2 gap-3">
            <x-config-field label="Nyawa maksimal" model="configs.lives_max" min="1" max="10" />
            <x-config-field label="Nyawa pulih saat recovery" model="configs.lives_on_recovery" min="1" />
            <x-config-field label="Periode penebusan (minggu)" model="configs.redemption_period_weeks" min="1" max="8" />
        </div>
        <label class="flex items-center gap-3 mt-3 cursor-pointer">
            <input type="checkbox" wire:model="configs.convict_status_visible" class="w-5 h-5 accent-[#f5c518] rounded-none">
            <span class="text-sm text-[#e8e8f0]">Tampilkan status RPG siswa di publik & pop-up profil (Citizen / Convict)</span>
        </label>
    </x-ui.card>

    {{-- Swap --}}
    <x-ui.card title="TUKAR JADWAL">
        <x-config-field label="Limit tukar / bulan" model="configs.swap_limit_per_month" min="0" max="10" />
    </x-ui.card>

    {{-- Replacement --}}
    <x-ui.card title="PIKET PENGGANTI">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" wire:model.live="configs.replacement_duty_enabled" class="w-5 h-5 accent-[#f5c518] rounded-none">
            <span class="text-sm text-[#e8e8f0]">Aktifkan piket pengganti sebelum potong nyawa</span>
        </label>
        <div class="mt-3 {{ $configs['replacement_duty_enabled'] ? '' : 'opacity-40' }}">
            <x-config-field label="Deadline piket pengganti (hari)" model="configs.replacement_duty_days"
                            :disabled="! $configs['replacement_duty_enabled']" min="1" max="7" />
        </div>
    </x-ui.card>

    {{-- Reward --}}
    <x-ui.card title="REWARD & PENUKARAN XP">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <x-config-field label="XP Misi Tepat Waktu" model="configs.xp_per_mission" min="1" />
            <x-config-field label="XP Misi Piket Pengganti" model="configs.xp_per_replacement_mission" min="0" />
            <x-config-field label="Biaya XP per Pemulihan 1 Nyawa" model="configs.xp_per_life_buy" min="1" />
        </div>
    </x-ui.card>

    {{-- Sanksi --}}
    <x-ui.card title="SANKSI / PENALTI">
        <div class="grid grid-cols-2 gap-3">
            <x-config-field label="Pengurangan Nyawa per Pelanggaran" model="configs.lives_penalty" min="0" max="5" />
            <x-config-field label="Pengurangan XP per Pelanggaran" model="configs.xp_penalty" min="0" />
        </div>
    </x-ui.card>

    {{-- Keamanan Pendaftaran --}}
    <x-ui.card title="KEAMANAN PENDAFTARAN">
        <div class="space-y-4">
            {{-- Token Registrasi --}}
            <div>
                <label class="font-pixel text-[9px] text-[#8888aa] block mb-1">TOKEN PENDAFTARAN</label>
                <p class="text-xs text-[#8888aa] mb-2">Siswa wajib memasukkan token ini saat registrasi. Kosongkan jika tidak ingin menggunakan token.</p>
                <div class="flex gap-2">
                    <input type="text" wire:model="configs.registration_token"
                           placeholder="Contoh: PIKET2025"
                           class="flex-1 bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 font-mono tracking-widest focus:border-[#f5c518] focus:ring-1 focus:ring-[#f5c518] transition-colors uppercase">
                </div>
                @if($configs['registration_token'])
                    <p class="font-pixel text-[8px] text-[#2dc653] mt-1">Token aktif. Siswa wajib memasukkan token untuk mendaftar.</p>
                @else
                    <p class="font-pixel text-[8px] text-[#8888aa] mt-1">Tidak ada token. Siapa saja bisa mendaftar.</p>
                @endif
                @error('configs.registration_token') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email Verification Toggle --}}
            <div class="pt-3 border-t border-[#2d2050]">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="configs.require_email_verification"
                           class="w-5 h-5 mt-0.5 accent-[#f5c518] rounded-none shrink-0">
                    <div>
                        <span class="text-sm text-[#e8e8f0] font-semibold">Wajibkan Verifikasi Email</span>
                        <p class="text-xs text-[#8888aa] mt-0.5">Jika aktif, siswa yang baru daftar harus mengklik link verifikasi di email sebelum bisa mengakses dashboard.</p>
                    </div>
                </label>
                @if($configs['require_email_verification'])
                    <p class="font-pixel text-[8px] text-[#f5c518] mt-2 ml-8">Verifikasi email AKTIF. Pastikan konfigurasi SMTP di .env sudah benar!</p>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- Sticky save --}}
    <div class="fixed bottom-16 lg:bottom-0 left-0 right-0 lg:left-60 bg-[#14102a] border-t-2 border-[#2d2050] px-4 py-3 z-30">
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="w-full pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-3 py-3 rounded-none">
            <span wire:loading.remove wire:target="save"> SIMPAN KONFIGURASI</span>
            <span wire:loading wire:target="save">MENYIMPAN...</span>
        </button>
    </div>
</div>
