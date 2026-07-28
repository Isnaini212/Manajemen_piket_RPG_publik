@php
    use App\Enums\StudentStatus;

    $entries = $this->entries;
    $convictVisible = $this->convictVisible;
    $myProfileId = auth()->user()?->studentProfile?->id;

    $top3 = $entries->take(3);
    $rest = $entries->slice(3)->values();

    $podiumMeta = [
        0 => ['label' => 'RANK 1', 'border' => 'border-[#f5c518]', 'order' => 'order-2', 'size' => 'scale-100', 'color' => 'text-[#f5c518]'],
        1 => ['label' => 'RANK 2', 'border' => 'border-[#c0c0c0]', 'order' => 'order-1', 'size' => 'scale-90', 'color' => 'text-[#c0c0c0]'],
        2 => ['label' => 'RANK 3', 'border' => 'border-[#cd7f32]', 'order' => 'order-3', 'size' => 'scale-90', 'color' => 'text-[#cd7f32]'],
    ];
@endphp

<div wire:poll.60s class="space-y-4 pb-20">

    {{-- Title --}}
    <div class="text-center">
        <h1 class="font-cinzel text-2xl text-[#f5c518] font-bold">Hall of Fame</h1>
        @if ($this->activeSemester)
            <p class="font-pixel text-[10px] text-[#8888aa] mt-1">{{ $this->activeSemester->name }}</p>
        @endif
    </div>

    {{-- Top 3 podium --}}
    @if ($top3->isNotEmpty())
        <div class="flex items-end justify-center gap-2">
            @foreach ($top3 as $i => $p)
                @php $m = $podiumMeta[$i]; $isMe = $p->id === $myProfileId; @endphp
                <div class="{{ $m['order'] }} {{ $m['size'] }} flex-1 max-w-[33%]">
                    <button wire:click="$dispatch('openProfileModal', { userId: {{ $p->user_id }} })"
                            class="block w-full text-left hover:opacity-90 transition-opacity focus:outline-none cursor-pointer">
                        <div class="bg-[#14102a] pixel-box rounded-none p-3 text-center border-4 {{ $m['border'] }}
                                    {{ $isMe ? 'ring-2 ring-[#f5c518]' : '' }} hover:border-[#f5c518] transition-colors">
                            <div class="font-pixel text-[9px] font-bold uppercase tracking-wider mb-1 {{ $m['color'] }}">{{ $m['label'] }}</div>
                            <div class="mt-1 flex justify-center">
                                <img src="{{ $p->avatar_url }}"
                                     alt="{{ $p->user?->name }}"
                                     class="w-12 h-12 rounded-none border-2 {{ $m['border'] }} object-cover">
                            </div>
                            <p class="text-xs text-[#e8e8f0] truncate mt-1">
                                {{ $p->user?->name ?? '—' }}
                                @if ($convictVisible && $p->status === StudentStatus::CONVICT)
                                    <span class="font-pixel text-[7px] text-[#e63946] ml-1">[CONVICT]</span>
                                @endif
                            </p>
                            <p class="font-pixel text-[10px] text-[#f5c518] mt-1">{{ number_format($p->xp) }} XP</p>
                            <p class="font-pixel text-[8px] text-[#8888aa]">{{ $p->piket_count }} piket</p>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Rank 4+ list --}}
    @if ($rest->isNotEmpty())
        <x-ui.card>
            <div class="space-y-2">
                @foreach ($rest as $idx => $p)
                    @php $rank = $idx + 4; $isMe = $p->id === $myProfileId; @endphp
                    <button wire:click="$dispatch('openProfileModal', { userId: {{ $p->user_id }} })"
                            class="w-full flex items-center gap-3 p-2 rounded-none border-2 hover:border-[#f5c518]/50 transition-colors focus:outline-none cursor-pointer text-left
                               {{ $isMe ? 'border-[#f5c518] bg-[#f5c518]/10' : 'border-transparent' }}">
                        <span class="font-pixel text-[10px] w-6 text-center text-[#8888aa]">{{ $rank }}</span>
                        <img src="{{ $p->avatar_url }}"
                             alt="{{ $p->user?->name }}"
                             class="w-8 h-8 rounded-none border border-[#2d2050] object-cover shrink-0">
                        <span class="flex-1 text-sm truncate {{ $isMe ? 'text-[#f5c518]' : 'text-[#e8e8f0]' }}">
                            {{ $p->user?->name ?? '—' }}
                            @if ($convictVisible && $p->status === StudentStatus::CONVICT)
                                <span class="font-pixel text-[7px] text-[#e63946] ml-1">[CONVICT]</span>
                            @endif
                        </span>
                        <div class="text-right">
                            <p class="font-pixel text-[10px] text-[#f5c518]">{{ number_format($p->xp) }}</p>
                            <p class="font-pixel text-[8px] text-[#8888aa]">{{ $p->piket_count }} piket</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    @if ($entries->isEmpty())
        <x-ui.card><p class="text-[#8888aa] text-sm text-center py-4">Belum ada data peringkat.</p></x-ui.card>
    @endif

    {{-- Sticky footer: my position --}}
    @if ($this->myRank)
        <div class="fixed bottom-16 lg:bottom-0 left-0 right-0 lg:left-60 bg-[#14102a] border-t-2 border-[#f5c518] px-4 py-3 z-30">
            <p class="font-pixel text-[10px] text-[#f5c518] text-center">
                Posisi kamu: #{{ $this->myRank }} — {{ number_format($this->myProfile?->xp ?? 0) }} XP
            </p>
        </div>
    @endif
</div>
