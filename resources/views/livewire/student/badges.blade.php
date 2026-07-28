@php
    use Illuminate\Support\Facades\Storage;
    $percentage = $totalCount > 0 ? round(($ownedCount / $totalCount) * 100) : 0;
@endphp

<div class="space-y-6">

    {{-- HEADER CARD --}}
    <x-ui.card>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-pixel text-base sm:text-lg text-[#f5c518]">
                    KATALOG BADGE & ACHIEVEMENT
                </h2>
                <p class="mt-1 text-xs text-[#8888aa]">Kumpulkan badge spektakuler dengan menyelesaikan berbagai tugas dan piket!</p>
            </div>
            <div class="shrink-0 bg-[#0c0918] border-2 border-[#2d2050] p-3 text-center min-w-[160px]">
                <p class="font-pixel text-[8px] text-[#8888aa]">KOLEKSI DIRAIH</p>
                <p class="font-pixel text-xl text-[#f5c518] mt-1">{{ $ownedCount }} <span class="text-xs text-[#8888aa]">/ {{ $totalCount }}</span></p>
                <div class="w-full bg-[#14102a] h-1.5 mt-2 overflow-hidden border border-[#2d2050]">
                    <div class="bg-[#f5c518] h-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- SECTION 1: BADGE DIRAIH --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-[#2dc653]"></span>
            <h3 class="font-pixel text-xs text-[#2dc653] uppercase tracking-wider">
                BADGE DIRAIH ({{ $ownedCount }})
            </h3>
        </div>

        @if ($ownedBadges->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach ($ownedBadges as $badge)
                    <div class="bg-[#14102a] pixel-box p-3 text-center border-2 border-[#2dc653]/60 relative group hover:border-[#2dc653] transition-colors">
                        <div class="absolute -top-2 -right-2 bg-[#2dc653] text-[#0c0918] font-pixel text-[7px] px-1.5 py-0.5 rounded-none font-bold">
                            DIRAIH
                        </div>
                        <div class="w-20 h-20 mx-auto my-2 relative flex items-center justify-center">
                            @if ($badge->icon_url)
                                <img src="{{ Storage::url($badge->icon_url) }}" alt="{{ $badge->name }}"
                                     class="w-full h-full object-cover pixelated border-2 border-[#2dc653]">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-[10px] font-pixel border-2 border-[#2dc653] bg-[#0c0918] text-[#2dc653]">BADGE</div>
                            @endif
                        </div>
                        <h4 class="font-pixel text-[9px] text-[#e8e8f0] truncate mt-2">{{ $badge->name }}</h4>
                        <p class="text-[11px] text-[#8888aa] mt-1 line-clamp-2 leading-tight">{{ $badge->description ?: 'Badge istimewa petualang piket.' }}</p>
                        @if ($badge->earned_at)
                            <p class="font-pixel text-[7px] text-[#2dc653] mt-2 pt-2 border-t border-[#2d2050]">
                                {{ \Illuminate\Support\Carbon::parse($badge->earned_at)->translatedFormat('d M Y') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-[#14102a] pixel-box p-6 text-center border-2 border-[#2d2050]">
                <p class="font-pixel text-[10px] text-[#8888aa]">Kamu belum memiliki badge. Kerjakan piket dan misi untuk meraih badge pertamamu!</p>
            </div>
        @endif
    </div>

    {{-- SECTION 2: KATALOG TERKUNCI --}}
    <div class="space-y-3 pt-4 border-t border-[#2d2050]">
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-[#8888aa]"></span>
            <h3 class="font-pixel text-xs text-[#8888aa] uppercase tracking-wider">
                KATALOG TERKUNCI ({{ $lockedBadges->count() }})
            </h3>
        </div>

        @if ($lockedBadges->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach ($lockedBadges as $badge)
                    <div class="bg-[#14102a]/60 pixel-box p-3 text-center border-2 border-[#2d2050] relative group hover:border-[#8888aa] transition-colors">
                        <div class="w-20 h-20 mx-auto my-2 relative grayscale opacity-40 group-hover:opacity-70 transition-opacity">
                            @if ($badge->icon_url)
                                <img src="{{ Storage::url($badge->icon_url) }}" alt="{{ $badge->name }}"
                                     class="w-full h-full object-cover pixelated border-2 border-[#2d2050]">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-[10px] font-pixel border-2 border-[#2d2050] bg-[#0c0918] text-[#8888aa]">BADGE</div>
                            @endif
                        </div>
                        <h4 class="font-pixel text-[9px] text-[#8888aa] truncate mt-2">{{ $badge->name }}</h4>
                        <p class="text-[11px] text-[#666688] mt-1 line-clamp-2 leading-tight">{{ $badge->description ?: 'Selesaikan syarat khusus untuk membuka.' }}</p>
                        <span class="inline-block mt-2 font-pixel text-[7px] text-[#e63946] bg-[#e63946]/10 px-2 py-0.5 border border-[#e63946]/30">
                            TERKUNCI
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-[#14102a] pixel-box p-6 text-center border-2 border-[#2d2050]">
                <p class="font-pixel text-[10px] text-[#2dc653]">Luar biasa! Kamu telah mengumpulkan seluruh badge di dalam sistem!</p>
            </div>
        @endif
    </div>

</div>
