<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' · ' . config('app.name', 'Piket RPG') : (trim($__env->yieldContent('title')) ? $__env->yieldContent('title') . ' · ' . config('app.name', 'Piket RPG') : config('app.name', 'Piket RPG')) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#0c0918] text-[#e8e8f0] font-['Inter'] min-h-screen starfield" x-data="{ mobileMenuOpen: false }">

@php
    $user = auth()->user();

    $navItems = [
        ['route' => 'admin.dashboard',        'label' => 'Dashboard',  'icon' => 'home'],
        ['route' => 'admin.duty-slots.index', 'label' => 'Jadwal',     'icon' => 'calendar'],
        ['route' => 'admin.submissions.index','label' => 'Verifikasi', 'icon' => 'check'],
    ];

    // Extra links shown only on the desktop sidebar / mobile drawer.
    $sidebarExtra = [
        ['route' => 'admin.swap-logs.index', 'label' => 'Log Tukar', 'icon' => 'swap'],
        ['route' => 'admin.recap.index',     'label' => 'Rekap',     'icon' => 'clipboard'],
        ['route' => 'admin.badges.index',    'label' => 'Badge',     'icon' => 'medal'],
        ['route' => 'admin.semesters.index', 'label' => 'Semester',  'icon' => 'trophy'],
        ['route' => 'admin.students.index',  'label' => 'Siswa',     'icon' => 'user'],
        ['route' => 'admin.config.index',    'label' => 'Pengaturan', 'icon' => 'cog'],
    ];
@endphp

{{-- SIDEBAR (desktop) --}}
<aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:w-60 lg:flex lg:flex-col bg-[#14102a] border-r border-[#2d2050] z-50">
    <div class="p-5">
        <a href="{{ route('home') }}" wire:navigate
           class="group inline-flex items-center gap-2 px-4 py-2.5 rounded-none
                  bg-[#0c0918] border-2 border-[#f5c518]
                  hover:bg-[#f5c518]/10 transition-colors
                  pixel-box">
            <span class="font-pixel text-sm text-[#f5c518] tracking-wider group-hover:text-white transition-colors">PIKET RPG</span>
        </a>
        <span class="inline-block mt-3 font-pixel text-[8px] px-2 py-1 rounded-none bg-[#7c3aed] text-[#e8e8f0] pixel-btn">
            ADMIN PANEL
        </span>
    </div>

    <nav class="flex-1 px-3 space-y-2 overflow-y-auto">
        @foreach (array_merge($navItems, $sidebarExtra) as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2 rounded-none pixel-btn
                      {{ $active ? 'bg-[#0c0918] text-[#f5c518]' : 'text-[#8888aa] hover:text-[#e8e8f0]' }}">
                <x-ui.icon :name="$item['icon']" class="w-5 h-5" />
                <span class="font-pixel text-[10px]">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="w-full p-4 border-t border-[#2d2050] flex items-center gap-3 hover:bg-[#2d2050]/30 transition-colors focus:outline-none text-left">
            <img src="{{ $user?->avatar_url }}" alt="Avatar" class="w-9 h-9 rounded-none object-cover pixelated border-2 border-[#7c3aed] shrink-0">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold truncate">{{ $user?->name }}</p>
                <span class="font-pixel text-[8px] text-[#7c3aed]">ADMIN</span>
            </div>
        </button>

        <div x-show="open" @click.away="open = false" x-cloak x-transition
             class="absolute bottom-full left-4 right-4 mb-2 bg-[#14102a] pixel-box rounded-none z-50">
            <div class="p-3 border-b border-[#2d2050]">
                <p class="text-sm text-[#e8e8f0] truncate">{{ $user?->name }}</p>
                <p class="text-xs text-[#8888aa] truncate">{{ $user?->email }}</p>
            </div>
            <a href="{{ route('profile') }}" wire:navigate
               class="block px-3 py-2 text-sm text-[#8888aa] hover:text-[#f5c518] hover:bg-[#2d2050]/30">
                Profil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left px-3 py-2 text-sm text-[#e63946] hover:bg-[#2d2050]/30">
                    Logout
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- MAIN WRAPPER --}}
<div class="lg:ml-60 flex flex-col min-h-screen">

    <header class="sticky top-0 z-40 bg-[#14102a] border-b border-[#2d2050] px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="font-pixel text-xs text-[#f5c518]">{{ $title ?? 'Admin' }}</h1>
            <div class="flex items-center gap-3">
                @livewire('notification-bell')

                {{-- Avatar dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="w-8 h-8 rounded-none flex items-center justify-center overflow-hidden hover:opacity-80 transition-opacity focus:outline-none">
                        <img src="{{ $user?->avatar_url }}" alt="Avatar" class="w-full h-full object-cover pixelated border-2 border-[#7c3aed]">
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak x-transition
                         class="absolute right-0 mt-2 w-52 bg-[#14102a] pixel-box rounded-none z-50">
                        <div class="p-3 border-b border-[#2d2050]">
                            <p class="text-sm text-[#e8e8f0] truncate">{{ $user?->name }}</p>
                            <p class="text-xs text-[#8888aa] truncate">{{ $user?->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate
                           class="block px-3 py-2 text-sm text-[#8888aa] hover:text-[#f5c518] hover:bg-[#2d2050]/30">
                            Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-3 py-2 text-sm text-[#e63946] hover:bg-[#2d2050]/30">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 pb-24 lg:pb-4">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</div>

{{-- BOTTOM NAV (mobile) --}}
<nav class="fixed bottom-0 left-0 right-0 lg:hidden bg-[#14102a] border-t border-[#2d2050] z-50 h-16">
    <div class="flex h-full">
        @foreach (array_slice($navItems, 0, 3) as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
               class="flex-1 flex flex-col items-center justify-center gap-1
                      {{ $active ? 'text-[#f5c518]' : 'text-[#8888aa]' }}">
                <x-ui.icon :name="$item['icon']" class="w-6 h-6" />
                <span class="text-[10px] font-medium tracking-wide">{{ $item['label'] }}</span>
            </a>
        @endforeach
        
        {{-- Tombol Lainnya --}}
        <button @click="mobileMenuOpen = true"
                class="flex-1 flex flex-col items-center justify-center gap-1 text-[#8888aa] hover:text-[#f5c518]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            <span class="text-[10px] font-medium tracking-wide">Lainnya</span>
        </button>
    </div>
</nav>

{{-- MOBILE OFF-CANVAS MENU (Drawer) --}}
<div x-show="mobileMenuOpen" class="fixed inset-0 z-[60] lg:hidden" x-cloak>
    {{-- Backdrop --}}
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- Panel --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-64 bg-[#14102a] border-l border-[#2d2050] flex flex-col shadow-2xl">
         
         <div class="p-4 border-b border-[#2d2050] flex justify-between items-center bg-[#0c0918]">
             <span class="font-pixel text-[10px] text-[#f5c518]">MENU ADMIN</span>
             <button @click="mobileMenuOpen = false" class="text-[#8888aa] hover:text-[#e63946] font-pixel text-xs px-2 py-1">✕</button>
         </div>

         <nav class="flex-1 overflow-y-auto p-4 space-y-2">
            @foreach (array_merge($navItems, $sidebarExtra) as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" wire:navigate @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-none pixel-btn
                          {{ $active ? 'bg-[#0c0918] text-[#f5c518] border border-[#f5c518]/30' : 'text-[#8888aa] hover:text-[#e8e8f0] bg-[#2d2050]/20' }}">
                    <x-ui.icon :name="$item['icon']" class="w-5 h-5" />
                    <span class="font-pixel text-[10px]">{{ $item['label'] }}</span>
                </a>
            @endforeach
         </nav>
    </div>
</div>


{{-- Custom themed confirm dialog (replaces browser native wire:confirm popups) --}}
<x-confirm-dialog />


{{-- Toast Notification (global popup untuk event dispatch 'notify') --}}
<x-toast-notification />

@livewireScripts
<livewire:profile-modal />
</body>
</html>
