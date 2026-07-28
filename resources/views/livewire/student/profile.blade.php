@php
    use App\Enums\ClaimStatus;
    use App\Enums\ClaimType;
    use Illuminate\Support\Facades\Storage;

    $profile = $this->profile;
    $stats = $this->stats;
    $badges = $this->badges;
    $ownedCount = $badges->where('owned', true)->count();
    $avatarUrl = $profile?->avatar_url ?? 'https://ui-avatars.com/api/?name=S&background=2a2a4a&color=f5c518&size=128&bold=true';
@endphp

<div class="space-y-4">

    {{-- SECTION PROFIL --}}
    <x-ui.card>
        @if (! $isEditing)
            <div class="flex items-start sm:items-center justify-between gap-2 sm:gap-3">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                    <div class="relative shrink-0">
                        <img src="{{ $avatarUrl }}" alt="Avatar"
                             class="w-14 h-14 sm:w-16 sm:h-16 rounded-none border-2 border-[#f5c518] object-cover"
                             style="image-rendering: auto;">
                        @if ($profile?->profile_picture)
                            <button wire:click="removeAvatar"
                                    wire:confirm="Hapus foto profil?"
                                    title="Hapus foto"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-[#e63946] text-white text-xs flex items-center justify-center leading-none">
                                ×
                            </button>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-cinzel text-lg sm:text-xl text-[#e8e8f0] truncate">{{ $profile?->user?->name }}</p>
                        @if ($profile?->user?->username)
                            <p class="font-pixel text-[9px] sm:text-[10px] text-[#f5c518] mt-0.5 truncate">@<span>{{ $profile->user->username }}</span></p>
                        @endif
                        <p class="text-sm text-[#8888aa] truncate mt-0.5">{{ $profile?->user?->email }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            @if ($profile)<x-ui.status-badge :status="$profile->status" />@endif
                            <span class="font-pixel text-[8px] text-[#8888aa] truncate">SISWA</span>
                        </div>
                    </div>
                </div>
                <button wire:click="$set('isEditing', true)"
                        class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[8px] px-2 py-2 sm:px-3 rounded-none shrink-0 mt-1 sm:mt-0">
                    EDIT
                </button>
            </div>
        @else
            <div class="space-y-4">

                {{-- Avatar preview --}}
                <div class="text-center">
                    <p class="font-pixel text-[10px] text-[#f5c518] mb-2">FOTO / GIF PROFIL</p>
                    <div class="flex justify-center mb-3">
                        <img id="avatar-preview"
                             src="{{ $newAvatar ? $newAvatar->temporaryUrl() : $avatarUrl }}"
                             alt="Preview Avatar"
                             class="w-24 h-24 rounded-none border-2 border-[#f5c518] object-cover">
                    </div>

                    <label class="cursor-pointer inline-block pixel-btn bg-[#2d2050] text-[#e8e8f0] font-pixel text-[8px] px-3 py-2 rounded-none">
                        PILIH GAMBAR / GIF
                        <input type="file"
                               id="avatar-upload"
                               wire:model="newAvatar"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               class="hidden">
                    </label>
                    @error('newAvatar') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror

                    <div wire:loading wire:target="newAvatar">
                        <p class="font-pixel text-[8px] text-[#8888aa] mt-1">Memproses...</p>
                    </div>

                    {{-- Live preview from Livewire file upload --}}
                    @if ($newAvatar)
                        <p class="font-pixel text-[8px] text-[#2dc653] mt-1">File siap diupload</p>
                    @endif
                </div>

                <div>
                    <label class="font-pixel text-[10px] text-[#8888aa] block mb-1">NAMA LENGKAP (NAMA ASLI)</label>
                    <input type="text" wire:model="name"
                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518]">
                    @error('name') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="font-pixel text-[10px] text-[#8888aa] block mb-1">USERNAME</label>
                    <input type="text" wire:model="username"
                           class="w-full bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[#e8e8f0] text-sm px-3 py-2 focus:border-[#f5c518]">
                    @error('username') <p class="text-xs text-[#e63946] mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button wire:click="saveProfile"
                            wire:loading.attr="disabled"
                            class="flex-1 pixel-btn bg-[#2dc653] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">SIMPAN</button>
                    <button wire:click="$set('isEditing', false)"
                            class="flex-1 pixel-btn bg-[#2d2050] text-[#8888aa] font-pixel text-[10px] px-3 py-2 rounded-none">BATAL</button>
                </div>
            </div>
        @endif
    </x-ui.card>


    {{-- SECTION STATISTIK --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-[#14102a] pixel-box rounded-none p-3 text-center">
            <p class="font-pixel text-[8px] text-[#8888aa]">TOTAL PIKET</p>
            <p class="font-pixel text-lg text-[#e8e8f0] mt-2">{{ $stats['total_piket'] }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-3 text-center">
            <p class="font-pixel text-[8px] text-[#8888aa]">TOTAL XP</p>
            <p class="font-pixel text-lg text-[#f5c518] mt-2">{{ number_format($stats['total_xp']) }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-3 text-center">
            <p class="font-pixel text-[8px] text-[#8888aa]">STREAK</p>
            <p class="font-pixel text-lg text-[#2dc653] mt-2">{{ $stats['streak'] }}</p>
        </div>
        <div class="bg-[#14102a] pixel-box rounded-none p-3 text-center">
            <p class="font-pixel text-[8px] text-[#8888aa]">BADGE</p>
            <p class="font-pixel text-lg text-[#f5c518] mt-2">{{ $stats['badge_count'] }}</p>
        </div>
    </div>

    {{-- SECTION BADGE COLLECTION --}}
    @php
        $ownedBadges = $badges->where('owned', true);
    @endphp
    <x-ui.card>
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <div>
                <h3 class="font-pixel text-sm text-[#f5c518]">
                    BADGE DIRAIH
                </h3>
                <p class="text-xs text-[#8888aa] mt-0.5">{{ $ownedBadges->count() }} badge dimiliki</p>
            </div>
            <a href="{{ route('student.badges') }}" wire:navigate class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[8px] px-2.5 py-1.5 hover:bg-[#e8e8f0] transition-colors">
                KATALOG BADGE ➔
            </a>
        </div>

        @if ($ownedBadges->isNotEmpty())
            <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
                @foreach ($ownedBadges as $badge)
                    <div class="relative group text-center" title="{{ $badge->description }}">
                        <div class="relative">
                            @if ($badge->icon_url)
                                <img src="{{ Storage::url($badge->icon_url) }}" alt="{{ $badge->name }}"
                                     class="w-full aspect-square object-cover pixelated border-2 border-[#2dc653] rounded-none">
                            @else
                                <div class="w-full aspect-square flex items-center justify-center text-[10px] font-pixel border-2 border-[#2dc653] rounded-none bg-[#0c0918] text-[#2dc653]">BADGE</div>
                            @endif
                        </div>
                        <p class="font-pixel text-[8px] text-[#e8e8f0] mt-1 truncate">{{ $badge->name }}</p>

                        {{-- Tooltip --}}
                        @if ($badge->description)
                            <div class="hidden group-hover:block absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-1 w-40
                                        bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-2 text-xs text-[#e8e8f0]">
                                {{ $badge->description }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-4 text-center">
                <p class="text-[#8888aa] text-sm italic">Belum ada badge yang diraih. Selesaikan tugas piket untuk membuka badge pertamamu!</p>
            </div>
        @endif
    </x-ui.card>

    {{-- SECTION RIWAYAT PIKET --}}
    <x-ui.card title="RIWAYAT PIKET">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#2d2050] text-left">
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2">TANGGAL</th>
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2">TIPE</th>
                        <th class="font-pixel text-[8px] text-[#8888aa] py-2">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->recentClaims as $claim)
                        @php
                            [$label, $cls] = match ($claim->status) {
                                ClaimStatus::Approved => ['APPROVED', 'text-[#2dc653]'],
                                ClaimStatus::Failed => ['GAGAL', 'text-[#e63946]'],
                                default => ['PENDING', 'text-[#f5c518]'],
                            };
                        @endphp
                        <tr class="border-b border-[#2d2050]/50">
                            <td class="py-2 text-[#8888aa]">{{ optional($claim->dutySlot?->duty_date)->locale('id')->translatedFormat('d M Y') }}</td>
                            <td class="py-2 text-[#e8e8f0]">{{ ucfirst($claim->claim_type?->value ?? '-') }}</td>
                            <td class="py-2"><span class="font-pixel text-[8px] {{ $cls }}">{{ $label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-[#8888aa] text-sm">Belum ada riwayat piket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
