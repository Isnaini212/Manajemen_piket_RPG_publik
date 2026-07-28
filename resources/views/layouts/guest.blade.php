<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' · ' . config('app.name', 'Piket RPG') : (trim($__env->yieldContent('title')) ? $__env->yieldContent('title') . ' · ' . config('app.name', 'Piket RPG') : config('app.name', 'Piket RPG')) }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
        <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* ── Base: dark night sky ─────────────────────────────── */
            body {
                background-color: #0c0918;
                color: #e8e8f0;
                font-family: 'Inter', sans-serif;
            }

            /* ── Star field (pure CSS, multi-layer overlapping grids) */
            .starfield {
                background-image:
                    radial-gradient(circle, rgba(255,255,255,0.85) 1px, transparent 1px),
                    radial-gradient(circle, rgba(220,200,255,0.6) 1px, transparent 1px),
                    radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1px),
                    radial-gradient(circle, rgba(245,197,24,0.4) 1px, transparent 1px);
                background-size:
                    127px 113px,
                    97px  89px,
                    61px  67px,
                    173px 151px;
                background-position:
                    0px   0px,
                    47px  61px,
                    22px  38px,
                    83px  29px;
            }

            /* ── Pixel shadow ───────────────────────────────────────── */
            .pixel-shadow { text-shadow: 3px 3px 0 rgba(0,0,0,0.8); }
        </style>
    </head>
    <body class="bg-[#0c0918] text-[#e8e8f0] font-['Inter'] min-h-screen starfield">
        <div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0">
            <div class="mb-4">
                <a href="/" wire:navigate class="font-pixel text-lg text-[#f5c518] hover:text-[#ffd700] pixel-shadow transition-colors">
                    PIKET RPG
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-4 p-8 bg-[#14102a] border-2 border-[#2d2050] shadow-2xl relative">
                {{-- Decorative corner pixels --}}
                <div class="absolute top-0 left-0 w-2 h-2 bg-[#f5c518]"></div>
                <div class="absolute top-0 right-0 w-2 h-2 bg-[#f5c518]"></div>
                <div class="absolute bottom-0 left-0 w-2 h-2 bg-[#f5c518]"></div>
                <div class="absolute bottom-0 right-0 w-2 h-2 bg-[#f5c518]"></div>

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
