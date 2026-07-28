@php
    use Illuminate\Support\Facades\Storage;

    $profile = $this->profile;
    $user    = $this->user;
    $level   = $this->level;
    $rank    = $this->leaderboardRank;
    $badges  = $this->ownedBadges;
    $stats   = $this->stats;
    $statusVisible = $this->statusVisible;
    $avatarUrl = $profile?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? 'S') . '&background=2a2a4a&color=f5c518&size=128&bold=true';
@endphp

<div x-data="{ open: @entangle('show') }"
     x-show="open"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
     x-cloak>
    
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-[#0c0918]/80 backdrop-blur-sm"
         @click="open = false"></div>

    {{-- Modal Content --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-2xl bg-[#0c0918] border-2 border-[#2d2050] overflow-hidden flex flex-col max-h-full">
        
        {{-- Header / Close Button --}}
        <div class="absolute top-2 right-2 z-10">
            <button @click="open = false"
                    class="w-8 h-8 flex items-center justify-center bg-[#e63946] text-[#0c0918] font-pixel text-lg leading-none hover:bg-[#ff4d5a] transition-colors">
                ×
            </button>
        </div>

        <div class="overflow-y-auto p-4 sm:p-6 space-y-5">
            @if ($user)
                {{-- HERO CARD --}}
                <div class="bg-[#14102a] pixel-box rounded-none p-5 sm:p-6 relative overflow-hidden">
                    {{-- Top 10 ribbon --}}
                    @if ($rank !== null)
                        <div class="absolute top-0 right-0 bg-[#f5c518] text-[#0c0918] font-pixel text-[8px] px-3 py-1">
                            🏆 TOP {{ $rank }}
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                        {{-- Avatar --}}
                        <div class="shrink-0">
                            <img src="{{ $avatarUrl }}"
                                 alt="{{ $user?->name }}"
                                 class="w-24 h-24 sm:w-28 sm:h-28 rounded-none border-4 {{ $rank !== null ? 'border-[#f5c518]' : 'border-[#2d2050]' }} object-cover">
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0 text-center sm:text-left mt-2 sm:mt-0">
                            <h1 class="font-cinzel text-xl sm:text-2xl text-[#e8e8f0] font-bold truncate">{{ $user?->name ?? 'Petualang' }}</h1>
                            @if ($user?->username)
                                <p class="font-pixel text-[9px] sm:text-[10px] text-[#f5c518] mt-0.5">@<span>{{ $user->username }}</span></p>
                            @endif
                            <p class="font-pixel text-[8px] sm:text-[9px] text-[#8888aa] mt-1">PETUALANG PIKET</p>

                            @if ($profile && $statusVisible)
                                <div class="mt-2 flex justify-center sm:justify-start">
                                    <x-ui.status-badge :status="$profile->status" />
                                </div>
                            @endif

                            @if ($rank !== null)
                                <div class="mt-3 inline-flex items-center gap-2 bg-[#f5c518]/10 border border-[#f5c518] px-3 py-1">
                                    <span class="text-base sm:text-lg">🏆</span>
                                    <span class="font-pixel text-[8px] sm:text-[9px] text-[#f5c518]">
                                        Peringkat #{{ $rank }} Leaderboard
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Stats strip --}}
                    <div class="grid grid-cols-4 gap-2 sm:gap-3 mt-5">
                        <div class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-2 sm:p-3 text-center">
                            <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa]">NYAWA</p>
                            <p class="font-pixel text-lg sm:text-xl {{ $profile?->lives > 0 ? 'text-[#e63946]' : 'text-[#8888aa]' }} mt-1">
                                {{ $profile?->lives ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-2 sm:p-3 text-center">
                            <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa]">LEVEL</p>
                            <p class="font-pixel text-lg sm:text-xl text-[#f5c518] mt-1">{{ $level }}</p>
                        </div>
                        <div class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-2 sm:p-3 text-center">
                            <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa]">TOTAL XP</p>
                            <p class="font-pixel text-lg sm:text-xl text-[#f5c518] mt-1">{{ number_format($stats['total_xp']) }}</p>
                        </div>
                        <div class="bg-[#0c0918] border-2 border-[#2d2050] rounded-none p-2 sm:p-3 text-center">
                            <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa]">PIKET</p>
                            <p class="font-pixel text-lg sm:text-xl text-[#e8e8f0] mt-1">{{ $stats['total_piket'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- BADGES --}}
                @if ($badges->isNotEmpty())
                    <div class="bg-[#14102a] pixel-box rounded-none p-4">
                        <h2 class="font-pixel text-[9px] sm:text-[10px] text-[#f5c518] mb-3">🏅 BADGE DIRAIH ({{ $badges->count() }})</h2>
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 sm:gap-3">
                            @foreach ($badges as $badge)
                                <div class="group relative text-center" title="{{ $badge->description }}">
                                    @if ($badge->icon_url)
                                        <img src="{{ Storage::url($badge->icon_url) }}" alt="{{ $badge->name }}"
                                             class="w-full aspect-square object-cover pixelated border-2 border-[#f5c518]/40 rounded-none">
                                    @else
                                        <div class="w-full aspect-square flex items-center justify-center text-xl sm:text-2xl border-2 border-[#f5c518]/40 rounded-none bg-[#0c0918]">🏅</div>
                                    @endif
                                    <p class="font-pixel text-[6px] sm:text-[7px] text-[#8888aa] mt-1 truncate">{{ $badge->name }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bg-[#14102a] pixel-box rounded-none p-4 text-center">
                        <p class="font-pixel text-[8px] sm:text-[9px] text-[#8888aa]">🔒 Belum ada badge yang diraih.</p>
                    </div>
                @endif
            @else
                {{-- Loading State --}}
                <div class="py-12 flex items-center justify-center">
                    <p class="font-pixel text-[10px] text-[#8888aa] animate-pulse">Memuat data...</p>
                </div>
            @endif
        </div>
    </div>
</div>
