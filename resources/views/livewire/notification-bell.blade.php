<div class="relative" wire:poll.15s="loadNotifications" x-data="{ open: @entangle('isOpen') }" @click.away="open = false">

    {{-- Bell button --}}
    <button type="button" wire:click="toggle" class="relative text-[#8888aa] hover:text-[#f5c518] transition-colors">
        <x-ui.icon name="bell" class="w-6 h-6" />
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-none bg-[#e63946]
                         text-[#0c0918] font-pixel text-[8px] flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak x-transition
         class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-[#14102a] pixel-box rounded-none z-50">

        <div class="flex items-center justify-between p-3 border-b border-[#2d2050]">
            <span class="font-pixel text-[10px] text-[#f5c518]">NOTIFIKASI</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" class="font-pixel text-[8px] text-[#8888aa] hover:text-[#f5c518]">
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notif)
                @php
                    $url = $this->getUrl($notif['type']);
                @endphp
                <button type="button"
                        wire:click="markAsRead({{ $notif['id'] }})"
                        @if($url)
                            @click="window.location.href = '{{ $url }}'"
                        @else
                            @click="open = false"
                        @endif
                        class="w-full text-left flex gap-2 p-3 border-b border-[#2d2050]/50 hover:bg-[#2d2050]/30
                               {{ $notif['is_read'] ? 'bg-[#14102a]' : 'bg-[#1e1e35]' }}
                               {{ $url ? 'cursor-pointer' : '' }}">
                    <span class="text-lg shrink-0">{{ $this->getIcon($notif['type']) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-[#e8e8f0] leading-snug">{{ $notif['message'] }}</p>
                        <p class="text-[10px] text-[#8888aa] mt-1">{{ $notif['time'] }}</p>
                    </div>
                    @if (! $notif['is_read'])
                        <span class="w-2 h-2 rounded-full bg-[#4a90d9] shrink-0 mt-1"></span>
                    @endif
                </button>
            @empty
                <p class="p-6 text-center text-sm text-[#8888aa]">Tidak ada notifikasi 🎉</p>
            @endforelse
        </div>
    </div>
</div>
