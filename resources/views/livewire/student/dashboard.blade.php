@php
    use App\Enums\ClaimStatus;
    use App\Enums\VerifyStatus;

    $profile  = $this->profile;
    $user     = $profile?->user;
    $xp       = (int) ($profile?->xp ?? 0);
    $level    = intdiv($xp, 500) + 1;
    $livesMax = $this->livesMax;
    $xpCost   = $this->xpLifeBuyCost;
    $avatarUrl = $profile?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? 'S') . '&background=2a2a4a&color=f5c518&size=128&bold=true';

    $missions = $this->weeklyMissions;
    $quota = $this->weeklyQuota;
    $doneCount = $missions->filter(fn ($c) => $c->status === ClaimStatus::Approved)->count();
    $progressPct = $quota > 0 ? min(100, (int) round($doneCount / $quota * 100)) : 0;

    $convict = $this->convictProgress;
    $replacement = $this->replacementDuty;
    $leaderboard = $this->leaderboard;
    $badges = $this->recentBadges;
@endphp

<div wire:poll.30s class="space-y-4">

    {{-- SECTION 1 — Status Card (Clickable to Profile) --}}
    <a href="{{ route('student.profile') }}" wire:navigate class="block group transition-transform active:scale-[0.99]">
        <x-ui.card class="mb-0 p-3 sm:p-4 hover:border-[#f5c518] transition-colors cursor-pointer">
            <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                    <div class="shrink-0">
                        <img src="{{ $avatarUrl }}"
                             alt="Avatar"
                             class="w-10 h-10 sm:w-12 sm:h-12 rounded-none border-2 border-[#f5c518] group-hover:border-white transition-colors object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-cinzel text-base sm:text-lg text-[#e8e8f0] group-hover:text-[#f5c518] transition-colors truncate">{{ $user?->name ?? 'Petualang' }}</p>
                        <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa] group-hover:text-[#f5c518]/80 transition-colors truncate">PETUALANG PIKET · KLIK KE PROFIL →</p>
                    </div>
                </div>
                @if ($profile)
                    <x-ui.status-badge :status="$profile->status" />
                @endif
            </div>

            <div class="grid grid-cols-3 gap-1.5 sm:gap-2 mt-3 sm:mt-4">
                <div class="bg-[#0c0918] border-2 border-[#2d2050] group-hover:border-[#f5c518]/40 transition-colors rounded-none p-1.5 sm:p-2 text-center min-w-0 flex flex-col justify-center">
                    <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa] truncate">LEVEL</p>
                    <p class="font-pixel text-xs sm:text-sm text-[#f5c518] mt-1 truncate">{{ $level }}</p>
                </div>
                <div class="bg-[#0c0918] border-2 border-[#2d2050] group-hover:border-[#f5c518]/40 transition-colors rounded-none p-1.5 sm:p-2 text-center min-w-0 flex flex-col justify-center">
                    <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa] truncate">TOTAL XP</p>
                    <p class="font-pixel text-xs sm:text-sm text-[#f5c518] mt-1 truncate">{{ number_format($xp) }}</p>
                </div>
                <div class="bg-[#0c0918] border-2 border-[#2d2050] group-hover:border-[#f5c518]/40 transition-colors rounded-none p-1.5 sm:p-2 flex flex-col items-center justify-center min-w-0 overflow-hidden">
                    <p class="font-pixel text-[7px] sm:text-[8px] text-[#8888aa] mb-1 truncate">NYAWA</p>
                    <x-ui.lives-display :lives="$profile?->lives ?? 0" :max="$livesMax" />
                </div>
            </div>
        </x-ui.card>
    </a>

    {{-- Convict banner --}}
    @if ($convict)
        @php
            $deadline = $convict['deadline'];
            $daysLeft = $deadline ? max(0, (int) ceil(now()->floatDiffInDays($deadline, false))) : null;
        @endphp
        <div class="bg-[#e63946]/10 border-2 border-[#e63946] rounded-none p-3 animate-pulse-red">
            <p class="font-pixel text-[10px] text-[#e63946] leading-relaxed">
                MODE HUKUMAN — Kamu wajib menyelesaikan {{ $convict['total'] }} misi hukuman
                @if ($deadline) sebelum {{ $deadline->locale('id')->translatedFormat('d M Y') }}. @endif
                Progres: {{ $convict['completed'] }}/{{ $convict['total'] }} disetujui.
                @if ($daysLeft !== null) Sisa: {{ $daysLeft }} hari @endif
            </p>
        </div>
    @endif

    {{-- Responsive grid: left (2 cols) = missions/replacement, right (1 col) = leaderboard/badges --}}
    <div class="lg:grid lg:grid-cols-3 lg:gap-4 space-y-4 lg:space-y-0">

        <div class="lg:col-span-2 space-y-4">

            {{-- SECTION 2 — Weekly Missions --}}
            <x-ui.card title="MISI MINGGU INI">
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-pixel text-[10px] text-[#8888aa]">Sudah menyelesaikan {{ $doneCount }} dari {{ $quota }} misi wajib</span>
                        <span class="font-pixel text-[10px] text-[#f5c518]">{{ $progressPct }}%</span>
                    </div>
                    <div class="w-full h-3 bg-[#0c0918] border-2 border-[#2d2050] rounded-none overflow-hidden">
                        <div class="h-full bg-gradient-gold" style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>

                @forelse ($missions as $claim)
                    @php
                        $slot = $claim->dutySlot;
                        $sub = $claim->submission;
                        $date = $slot?->duty_date;
                    @endphp
                    <div class="flex items-center justify-between gap-3 py-2 border-t border-[#2d2050] first:border-t-0">
                        <div class="min-w-0">
                            <p class="font-cinzel text-[#e8e8f0]">
                                {{ $date ? $date->locale('id')->translatedFormat('l') : '—' }}
                            </p>
                            <p class="text-xs text-[#8888aa]">
                                {{ $date ? $date->locale('id')->translatedFormat('d M Y') : '' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if (! $sub)
                                @php
                                    $activeReplacement = \App\Models\ReplacementDuty::where('original_claim_id', $claim->id)
                                        ->where('status', \App\Enums\ReplacementStatus::OFFERED)
                                        ->first();
                                    $isFailed = $claim->status === ClaimStatus::Failed;
                                    $canUpload = !$isFailed && ($date?->isToday() || ($activeReplacement && !$activeReplacement->isExpired()));
                                @endphp
                                @if ($canUpload)
                                    <a href="{{ route('student.missions') }}" wire:navigate
                                       class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[8px] px-2 py-1 rounded-none">
                                        {{ $activeReplacement ? 'PENGGANTI' : 'UPLOAD' }}
                                    </a>
                                @elseif ($isFailed)
                                    <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 text-[#e63946] border-[#e63946]">GAGAL</span>
                                @else
                                    <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 text-[#8888aa] border-[#8888aa]">LEWAT</span>
                                @endif
                            @else
                                @php
                                    [$label, $cls] = match ($sub->verify_status) {
                                        VerifyStatus::Approved => ['APPROVED', 'text-[#2dc653] border-[#2dc653]'],
                                        VerifyStatus::Rejected => ['DITOLAK', 'text-[#e63946] border-[#e63946]'],
                                        VerifyStatus::RejectedFinal => ['GAGAL', 'text-[#e63946] border-[#e63946]'],
                                        default => ['MENUNGGU', 'text-[#f5c518] border-[#f5c518]'],
                                    };
                                @endphp
                                <span class="font-pixel text-[8px] px-2 py-1 rounded-none border-2 {{ $cls }}">{{ $label }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-[#8888aa] text-sm mb-3">Belum ada misi diklaim minggu ini.</p>
                        <a href="{{ route('student.missions') }}" wire:navigate
                           class="inline-block pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">
                            CARI MISI →
                        </a>
                    </div>
                @endforelse
            </x-ui.card>

            {{-- SECTION 3 — Replacement Duty (conditional) --}}
            @if ($replacement)
                @php
                    $rDeadline = $replacement->deadline;
                    $rDays = $rDeadline ? max(0, (int) ceil(now()->floatDiffInDays($rDeadline, false))) : null;
                @endphp
                <div class="bg-[#f5c518]/10 border-2 border-[#f5c518] rounded-none p-3">
                    <p class="font-pixel text-[10px] text-[#f5c518] leading-relaxed mb-3">
                        Kamu punya piket pengganti!
                        @if ($rDeadline) Selesaikan sebelum {{ $rDeadline->locale('id')->translatedFormat('d M Y') }}. @endif
                        @if ($rDays !== null) Sisa: {{ $rDays }} hari @endif
                    </p>
                    <a href="{{ route('student.missions') }}" wire:navigate
                       class="inline-block pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-3 py-2 rounded-none">
                        UPLOAD BUKTI PENGGANTI
                    </a>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1 space-y-4">

            {{-- SECTION: Tukar XP Tambah Nyawa --}}
            @if ($profile && ! $profile->isConvict() && $profile->lives < $livesMax)
                @php $hasEnoughXp = $profile->xp >= $xpCost; @endphp
                <div class="bg-[#1b1537] pixel-box border-2 border-[#f5c518] rounded-none p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-pixel text-xs text-[#f5c518] uppercase tracking-wider">TUKAR XP TAMBAH NYAWA</h3>
                            <p class="text-xs text-[#8888aa] mt-1">Sisa nyawa kamu: <strong class="text-[#e63946] font-pixel text-xs">{{ $profile->lives }} / {{ $livesMax }}</strong></p>
                        </div>
                        <span class="font-pixel text-[10px] text-[#2dc653] bg-[#2dc653]/10 px-2.5 py-1 border border-[#2dc653]/30 rounded-none">+1 NYAWA</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between bg-[#0c0918] p-3 border border-[#2d2050] gap-3">
                        <div class="text-xs text-[#8888aa]">
                            Biaya penukaran: <strong class="text-[#f5c518] font-pixel">{{ $xpCost }} XP</strong>
                            <span class="block text-[10px] text-[#8888aa] mt-0.5">(XP kamu saat ini: {{ number_format($profile->xp) }} XP)</span>
                        </div>

                        <button type="button"
                                wire:click="buyLifeWithXp"
                                wire:confirm="YAKIN TUKAR {{ $xpCost }} XP UNTUK +1 NYAWA?"
                                @disabled(! $hasEnoughXp)
                                class="pixel-btn font-pixel text-[9px] px-3.5 py-2.5 rounded-none whitespace-nowrap cursor-pointer transition-colors
                                       {{ $hasEnoughXp
                                          ? 'bg-[#2dc653] text-[#0c0918] hover:bg-[#25a244]'
                                          : 'bg-[#2d2050] text-[#8888aa] opacity-60 cursor-not-allowed' }}">
                            {{ $hasEnoughXp ? "TUKAR {$xpCost} XP" : 'XP TIDAK CUKUP' }}
                        </button>
                    </div>
                </div>
            @endif

            {{-- SECTION 4 — Mini Leaderboard --}}
            <x-ui.card title="PERINGKAT">
                <div class="space-y-2">
                    @forelse ($leaderboard as $i => $lp)
                        @php $isMe = $profile && $lp->id === $profile->id; @endphp
                        <div class="flex items-center gap-2 p-2 rounded-none border-2
                                    {{ $isMe ? 'border-[#f5c518] bg-[#f5c518]/10' : 'border-transparent' }}">
                            <span class="font-pixel text-[10px] w-5 text-[#8888aa]">{{ $i + 1 }}</span>
                            <img src="{{ $lp->avatar_url }}"
                                 alt="Avatar"
                                 class="w-7 h-7 rounded-none border border-[#2d2050] object-cover shrink-0">
                            <span class="flex-1 text-sm truncate {{ $isMe ? 'text-[#f5c518]' : 'text-[#e8e8f0]' }}">
                                {{ $lp->user?->name ?? '—' }}
                            </span>
                            <span class="font-pixel text-[10px] text-[#f5c518]">{{ number_format($lp->xp) }}</span>
                        </div>
                    @empty
                        <p class="text-[#8888aa] text-sm italic">Belum ada data peringkat.</p>
                    @endforelse
                </div>
                <a href="{{ route('leaderboard') }}" wire:navigate
                   class="block mt-3 text-right font-pixel text-[10px] text-[#8888aa] hover:text-[#f5c518]">
                    Lihat Semua →
                </a>
            </x-ui.card>

            {{-- SECTION 5 — Recent Badges --}}
            <x-ui.card title="BADGE TERBARU">
                @if ($badges->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($badges as $sb)
                            <div class="flex items-center gap-3">
                                @if ($sb->badge?->icon_url)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($sb->badge->icon_url) }}"
                                         alt="" class="w-8 h-8 pixelated rounded-none border-2 border-[#2d2050]">
                                @else
                                    <div class="w-8 h-8 flex items-center justify-center bg-[#0c0918] border-2 border-[#2d2050] rounded-none text-[8px] font-pixel text-[#8888aa]">BADGE</div>
                                @endif
                                <span class="text-sm text-[#e8e8f0]">{{ $sb->badge?->name ?? 'Badge' }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[#8888aa] text-sm italic">Belum ada badge. Selesaikan misi!</p>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
