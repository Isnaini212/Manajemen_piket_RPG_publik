@php use App\Enums\StudentStatus; @endphp
<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piket RPG · Ubah Piket Jadi Petualangan</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">

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
            background-color: #0c0918;
            background-image:
                /* large sparse stars */
                radial-gradient(circle, rgba(255,255,255,0.85) 1px, transparent 1px),
                /* medium stars */
                radial-gradient(circle, rgba(220,200,255,0.6) 1px, transparent 1px),
                /* tiny dense stars */
                radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1px),
                /* gold-tinted distant stars */
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

        /* ── Hero specific: starfield + purple zenith glow ─────── */
        .hero-starfield {
            position: relative;
            background-color: #0c0918;
            background-image:
                radial-gradient(ellipse 70% 45% at 50% 0%, rgba(80,30,140,0.55) 0%, transparent 65%),
                radial-gradient(circle, rgba(255,255,255,0.85) 1px, transparent 1px),
                radial-gradient(circle, rgba(220,200,255,0.6) 1px, transparent 1px),
                radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1px),
                radial-gradient(circle, rgba(245,197,24,0.4) 1px, transparent 1px);
            background-size:
                100% 100%,
                127px 113px,
                97px  89px,
                61px  67px,
                173px 151px;
            background-position:
                0 0,
                0px   0px,
                47px  61px,
                22px  38px,
                83px  29px;
        }

        /* ── Section backgrounds ────────────────────────────────── */
        .section-dark {
            background-color: #100d1f;
            background-image:
                radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px),
                radial-gradient(circle, rgba(255,255,255,0.15) 1px, transparent 1px);
            background-size: 113px 97px, 67px 71px;
            background-position: 0 0, 34px 51px;
        }

        .cta-starfield {
            background-color: #0c0918;
            background-image:
                radial-gradient(ellipse 60% 50% at 50% 100%, rgba(80,30,140,0.4) 0%, transparent 70%),
                radial-gradient(circle, rgba(255,255,255,0.7) 1px, transparent 1px),
                radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px);
            background-size: 100% 100%, 127px 113px, 79px 83px;
            background-position: 0 0, 0 0, 31px 41px;
        }

        /* ── Gold text ─────────────────────────────────────────── */
        .gold-text {
            background: linear-gradient(135deg, #f5c518 0%, #ffd700 50%, #f0a500 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* ── Pixel text shadow (no blur, hard offset) ──────────── */
        .pixel-shadow { text-shadow: 3px 3px 0 rgba(0,0,0,0.8); }
        .gold-shadow  { text-shadow: 2px 2px 0 rgba(120,80,0,0.7); }

        /* ── Animations ────────────────────────────────────────── */
        @keyframes floaty {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-10px); }
        }
        .floaty { animation: floaty 4s ease-in-out infinite; }

        @keyframes pulse-gold {
            0%, 100% { box-shadow: 0 0 0 0   rgba(245,197,24,0.55); }
            60%       { box-shadow: 0 0 0 6px rgba(245,197,24,0); }
        }
        .btn-pulse { animation: pulse-gold 2.2s infinite; }

        /* ── Pixel cloud (blocky SVG helper) ────────────────────── */
        .pixel-cloud { image-rendering: pixelated; image-rendering: crisp-edges; }

        /* ── City silhouette fade at bottom ─────────────────────── */
        .city-silhouette { image-rendering: pixelated; }

        /* ── Shimmer divider ─────────────────────────────────────  */
        .shimmer-line {
            background: linear-gradient(90deg, transparent, rgba(245,197,24,0.55), transparent);
            height: 1px;
        }

        /* ── Cards ───────────────────────────────────────────────  */
        .rpg-card {
            background-color: #14102a;
            border: 1px solid #2d2050;
            transition: border-color 0.15s ease;
        }
        .rpg-card:hover { border-color: rgba(245,197,24,0.5); }

        .stat-panel {
            background-color: #100d20;
            border-left: 2px solid rgba(245,197,24,0.35);
        }

        /* ── Scrolling ticker ────────────────────────────────────── */
        @keyframes ticker {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .ticker-track { animation: ticker 30s linear infinite; white-space: nowrap; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden">

{{-- ============ NAVBAR ============ --}}
<header class="sticky top-0 z-40 bg-[#0c0918]/90 backdrop-blur-md border-b border-[#2d2050]">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        <span class="font-pixel text-xs text-[#f5c518] tracking-wider pixel-shadow">PIKET RPG</span>
        <nav class="hidden md:flex items-center gap-8">
            <a href="#fitur"    class="text-[11px] font-medium text-[#8888aa] hover:text-[#f5c518] transition-colors tracking-wide uppercase">Fitur</a>
            <a href="#cara"     class="text-[11px] font-medium text-[#8888aa] hover:text-[#f5c518] transition-colors tracking-wide uppercase">Cara Main</a>
            <a href="#peringkat"class="text-[11px] font-medium text-[#8888aa] hover:text-[#f5c518] transition-colors tracking-wide uppercase">Peringkat</a>
        </nav>
        @auth
            @php $dashRoute = auth()->user()->role === \App\Enums\UserRole::Admin ? route('admin.dashboard') : route('student.dashboard'); @endphp
            <a href="{{ $dashRoute }}" class="pixel-btn btn-pulse bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-4 py-2 tracking-wider">DASHBOARD</a>
        @else
            <a href="{{ route('login') }}" class="pixel-btn bg-[#f5c518] text-[#0c0918] font-pixel text-[9px] px-4 py-2 tracking-wider">MASUK</a>
        @endauth
    </div>
</header>

{{-- ============ HERO ============ --}}
<section class="hero-starfield relative overflow-hidden" style="min-height: 90vh;">

    {{-- LEFT pixel cloud --}}
    <svg class="pixel-cloud absolute left-0 top-28 w-40 md:w-56 opacity-60 pointer-events-none" viewBox="0 0 112 56" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0"  y="40" width="56" height="16" fill="#4c1d6e"/>
        <rect x="8"  y="32" width="48" height="16" fill="#5b2382"/>
        <rect x="16" y="24" width="40" height="16" fill="#6b2fa0"/>
        <rect x="24" y="16" width="24" height="16" fill="#7c3aed"/>
        <rect x="56" y="40" width="32" height="16" fill="#3b1457"/>
        <rect x="64" y="32" width="24" height="16" fill="#4c1d6e"/>
        <rect x="72" y="24" width="16" height="16" fill="#5b2382"/>
    </svg>

    {{-- RIGHT pixel cloud --}}
    <svg class="pixel-cloud absolute right-0 top-24 w-40 md:w-60 opacity-55 pointer-events-none" viewBox="0 0 128 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="72" y="48" width="56" height="16" fill="#4c1d6e"/>
        <rect x="64" y="40" width="48" height="16" fill="#5b2382"/>
        <rect x="56" y="32" width="40" height="16" fill="#6b2fa0"/>
        <rect x="72" y="24" width="32" height="16" fill="#7c3aed"/>
        <rect x="0"  y="48" width="40" height="16" fill="#3b1457"/>
        <rect x="8"  y="40" width="32" height="16" fill="#4c1d6e"/>
        <rect x="16" y="32" width="24" height="16" fill="#5b2382"/>
    </svg>

    {{-- SMALL cloud upper right --}}
    <svg class="pixel-cloud absolute right-16 top-14 w-20 md:w-28 opacity-40 pointer-events-none" viewBox="0 0 80 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="16" y="24" width="48" height="16" fill="#4c1d6e"/>
        <rect x="24" y="16" width="32" height="16" fill="#5b2382"/>
        <rect x="32" y="8"  width="16" height="16" fill="#6b2fa0"/>
    </svg>

    {{-- Hero content --}}
    <div class="relative max-w-4xl mx-auto px-4 pt-20 pb-16 md:pt-28 md:pb-20 text-center z-10">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 bg-[#7c3aed]/20 border border-[#7c3aed]/40 px-4 py-1.5 mb-8">
            <span class="w-1.5 h-1.5 bg-[#2dc653] inline-block"></span>
            <span class="font-pixel text-[8px] text-[#2dc653] tracking-widest">SISTEM PIKET GAMIFIKASI</span>
            <span class="w-1.5 h-1.5 bg-[#2dc653] inline-block"></span>
        </div>

        {{-- Pixel castle SVG --}}
        <div class="floaty mb-8">
            <svg class="mx-auto w-24 h-24" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg" style="image-rendering:pixelated">
                {{-- towers --}}
                <rect x="8"  y="32" width="16" height="56" fill="#3b1457"/>
                <rect x="72" y="32" width="16" height="56" fill="#3b1457"/>
                {{-- tower tops --}}
                <rect x="8"  y="16" width="4"  height="16" fill="#f5c518"/>
                <rect x="20" y="16" width="4"  height="16" fill="#f5c518"/>
                <rect x="72" y="16" width="4"  height="16" fill="#f5c518"/>
                <rect x="84" y="16" width="4"  height="16" fill="#f5c518"/>
                {{-- main body --}}
                <rect x="24" y="48" width="48" height="40" fill="#4c1d6e"/>
                {{-- wall --}}
                <rect x="24" y="40" width="48" height="12" fill="#5b2382"/>
                {{-- battlements --}}
                <rect x="24" y="32" width="8"  height="12" fill="#5b2382"/>
                <rect x="40" y="32" width="8"  height="12" fill="#5b2382"/>
                <rect x="56" y="32" width="8"  height="12" fill="#5b2382"/>
                {{-- gate --}}
                <rect x="40" y="64" width="16" height="24" fill="#1a0d30"/>
                {{-- gate arch highlight --}}
                <rect x="40" y="64" width="16" height="4" fill="#7c3aed"/>
                {{-- windows --}}
                <rect x="28" y="52" width="8" height="8" fill="#f5c518"/>
                <rect x="60" y="52" width="8" height="8" fill="#f5c518"/>
                {{-- flag --}}
                <rect x="46" y="12" width="4" height="20" fill="#c0c0c0"/>
                <rect x="50" y="12" width="12" height="8" fill="#e63946"/>
            </svg>
        </div>

        {{-- Title --}}
        <h1 class="font-pixel text-2xl md:text-4xl lg:text-5xl leading-tight mb-2 text-white pixel-shadow" style="line-height: 1.6;">
            Ubah Piket Jadi
        </h1>
        <h1 class="font-pixel text-3xl md:text-5xl lg:text-6xl leading-none mb-6 gold-shadow" style="color: #f5c518;">
            Petualangan!
        </h1>

        <p class="text-[#a898c8] max-w-lg mx-auto mb-10 leading-relaxed text-sm md:text-base">
            Klaim misi piket mingguan, kumpulkan XP, naik level, raih badge, dan panjat papan peringkat.
            Jaga nyawamu — atau berakhir jadi <span class="text-[#e63946] font-semibold">Convict</span>!
        </p>

        {{-- CTAs --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            @auth
                @php $dashRoute = auth()->user()->role === \App\Enums\UserRole::Admin ? route('admin.dashboard') : route('student.dashboard'); @endphp
                <a href="{{ $dashRoute }}"
                   class="pixel-btn btn-pulse bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-8 py-3.5 tracking-wider">KE DASHBOARD</a>
            @else
                <a href="{{ route('login') }}"
                   class="pixel-btn btn-pulse bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-8 py-3.5 tracking-wider">MASUK & MAIN</a>
            @endauth
            <a href="#peringkat"
               class="pixel-btn bg-transparent text-[#a78bfa] font-pixel text-[10px] px-8 py-3.5 border border-[#7c3aed]/60 hover:border-[#a78bfa] tracking-wider transition-colors">LIHAT PERINGKAT</a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px mt-14 max-w-2xl mx-auto border border-[#2d2050] bg-[#2d2050]">
            @foreach ([
                ['label' => 'PETUALANG',    'value' => number_format($stats['petualang']), 'color' => 'text-[#e8e8f0]'],
                ['label' => 'TOTAL XP',     'value' => number_format($stats['xp']),        'color' => 'text-[#f5c518]'],
                ['label' => 'PIKET SELESAI','value' => number_format($stats['piket']),     'color' => 'text-[#2dc653]'],
                ['label' => 'BADGE',        'value' => number_format($stats['badge']),     'color' => 'text-[#a78bfa]'],
            ] as $s)
                <div class="stat-panel p-4">
                    <p class="font-pixel text-lg md:text-2xl {{ $s['color'] }}">{{ $s['value'] }}</p>
                    <p class="font-pixel text-[7px] text-[#6655aa] mt-2 tracking-widest">{{ $s['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- CITY SILHOUETTE at bottom --}}
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none" style="line-height:0">
        <svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="width:100%; height:80px; display:block; image-rendering:pixelated;">
            {{-- Buildings --}}
            <rect x="0"   y="60" width="40"  height="60" fill="#14102a"/>
            <rect x="8"   y="44" width="24"  height="16" fill="#14102a"/>
            <rect x="12"  y="36" width="16"  height="16" fill="#14102a"/>
            <rect x="40"  y="80" width="60"  height="40" fill="#100d1f"/>
            <rect x="50"  y="64" width="40"  height="20" fill="#100d1f"/>
            <rect x="100" y="56" width="30"  height="64" fill="#14102a"/>
            <rect x="106" y="44" width="18"  height="16" fill="#14102a"/>
            <rect x="130" y="72" width="50"  height="48" fill="#100d1f"/>
            <rect x="140" y="56" width="30"  height="20" fill="#100d1f"/>
            <rect x="180" y="44" width="40"  height="76" fill="#14102a"/>
            <rect x="186" y="32" width="28"  height="16" fill="#14102a"/>
            <rect x="192" y="20" width="16"  height="16" fill="#14102a"/>
            <rect x="220" y="68" width="60"  height="52" fill="#100d1f"/>
            <rect x="280" y="52" width="35"  height="68" fill="#14102a"/>
            <rect x="285" y="36" width="25"  height="20" fill="#14102a"/>
            <rect x="315" y="76" width="55"  height="44" fill="#100d1f"/>
            <rect x="370" y="44" width="45"  height="76" fill="#14102a"/>
            <rect x="376" y="28" width="33"  height="20" fill="#14102a"/>
            <rect x="415" y="60" width="60"  height="60" fill="#100d1f"/>
            <rect x="475" y="36" width="50"  height="84" fill="#14102a"/>
            <rect x="481" y="20" width="38"  height="20" fill="#14102a"/>
            <rect x="525" y="68" width="40"  height="52" fill="#100d1f"/>
            <rect x="565" y="44" width="50"  height="76" fill="#14102a"/>
            <rect x="570" y="28" width="40"  height="20" fill="#14102a"/>
            <rect x="615" y="72" width="60"  height="48" fill="#100d1f"/>
            <rect x="675" y="52" width="40"  height="68" fill="#14102a"/>
            <rect x="680" y="36" width="30"  height="20" fill="#14102a"/>
            <rect x="715" y="64" width="55"  height="56" fill="#100d1f"/>
            <rect x="770" y="40" width="40"  height="80" fill="#14102a"/>
            <rect x="776" y="24" width="28"  height="20" fill="#14102a"/>
            <rect x="810" y="60" width="60"  height="60" fill="#100d1f"/>
            <rect x="870" y="48" width="45"  height="72" fill="#14102a"/>
            <rect x="875" y="32" width="35"  height="20" fill="#14102a"/>
            <rect x="915" y="68" width="50"  height="52" fill="#100d1f"/>
            <rect x="965" y="56" width="35"  height="64" fill="#14102a"/>
            <rect x="970" y="40" width="25"  height="20" fill="#14102a"/>
            <rect x="1000" y="72" width="60" height="48" fill="#100d1f"/>
            <rect x="1060" y="44" width="40" height="76" fill="#14102a"/>
            <rect x="1065" y="28" width="30" height="20" fill="#14102a"/>
            <rect x="1100" y="60" width="60" height="60" fill="#100d1f"/>
            <rect x="1150" y="48" width="50" height="72" fill="#14102a"/>
            {{-- Windows (tiny gold pixels) --}}
            <rect x="14"  y="50" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="22"  y="50" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="104" y="60" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="186" y="50" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="186" y="62" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="376" y="50" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="481" y="28" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="481" y="40" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="571" y="36" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="776" y="30" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="876" y="40" width="4" height="4" fill="#f5c518" opacity="0.5"/>
            <rect x="971" y="48" width="4" height="4" fill="#f5c518" opacity="0.5"/>
        </svg>
    </div>
</section>

{{-- ============ FITUR ============ --}}
<section id="fitur" class="section-dark border-t border-[#2d2050]">
    <div class="max-w-6xl mx-auto px-4 py-20">
        <div class="text-center mb-12">
            <p class="font-pixel text-[9px] text-[#7c3aed] tracking-widest mb-3">MENGAPA SERU?</p>
            <h2 class="font-cinzel text-2xl md:text-4xl font-bold text-[#e8e8f0] pixel-shadow">Sistem RPG Lengkap</h2>
            <div class="shimmer-line w-24 mx-auto mt-4"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ([
                ['icon_color' => '#f5c518', 'border_hover' => '#f5c518', 'icon_char' => 'XP', 'title' => 'XP & Level',     'desc' => 'Setiap piket yang disetujui memberi XP. Kumpulkan untuk naik level dan peringkat.'],
                ['icon_color' => '#e63946', 'border_hover' => '#e63946', 'icon_char' => 'HP', 'title' => 'Sistem Nyawa',   'desc' => 'Gagal piket? Nyawa berkurang. Jaga baik-baik sebelum habis!'],
                ['icon_color' => '#a78bfa', 'border_hover' => '#a78bfa', 'icon_char' => 'BD', 'title' => 'Badge',          'desc' => 'Raih achievement dari aksi tertentu dan pamerkan di profilmu.'],
                ['icon_color' => '#6655aa', 'border_hover' => '#6655aa', 'icon_char' => '!!', 'title' => 'Convict Status', 'desc' => 'Nyawa habis = jadi Convict. Selesaikan misi hukuman untuk menebus diri.'],
            ] as $f)
                <div class="rpg-card p-5 text-center flex flex-col items-center gap-3" style="--hover-color: {{ $f['border_hover'] }}">
                    <div class="w-14 h-14 flex items-center justify-center border" style="border-color: {{ $f['icon_color'] }}33; background: {{ $f['icon_color'] }}14;">
                        <span class="font-pixel text-sm" style="color: {{ $f['icon_color'] }}; text-shadow: 2px 2px 0 rgba(0,0,0,0.5);">{{ $f['icon_char'] }}</span>
                    </div>
                    <h3 class="font-pixel text-[10px]" style="color: {{ $f['icon_color'] }}">{{ $f['title'] }}</h3>
                    <p class="text-sm text-[#7766aa] leading-relaxed">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ CARA MAIN ============ --}}
<section id="cara" class="starfield border-t border-[#2d2050]">
    <div class="max-w-6xl mx-auto px-4 py-20">
        <div class="text-center mb-12">
            <p class="font-pixel text-[9px] text-[#f5c518] tracking-widest mb-3">PANDUAN</p>
            <h2 class="font-cinzel text-2xl md:text-4xl font-bold text-[#e8e8f0] pixel-shadow">Cara Main</h2>
            <div class="shimmer-line w-24 mx-auto mt-4"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @foreach ([
                ['n' => '01', 'title' => 'Klaim Misi',   'desc' => 'Pilih slot piket mingguan yang tersedia dan klaim sebagai milikmu.'],
                ['n' => '02', 'title' => 'Upload Bukti', 'desc' => 'Selesaikan piket, ambil foto, dan unggah buktinya ke sistem.'],
                ['n' => '03', 'title' => 'Diverifikasi', 'desc' => 'Admin menyetujui bukti fotomu dan XP pun langsung cair.'],
                ['n' => '04', 'title' => 'Naik Peringkat','desc' => 'Kumpulkan XP sebanyak-banyaknya dan panjat Hall of Fame.'],
            ] as $step)
                <div class="rpg-card relative p-5 overflow-hidden">
                    <span class="absolute -bottom-1 -right-1 font-pixel text-5xl leading-none pointer-events-none select-none" style="color: rgba(245,197,24,0.06)">{{ $step['n'] }}</span>
                    <div class="w-10 h-10 flex items-center justify-center mb-4" style="background:rgba(245,197,24,0.1); border:1px solid rgba(245,197,24,0.25)">
                        <span class="font-pixel text-[10px] text-[#f5c518]">{{ $step['n'] }}</span>
                    </div>
                    <h3 class="font-pixel text-[10px] text-[#e8e8f0] mb-2">{{ $step['title'] }}</h3>
                    <p class="text-sm text-[#7766aa] leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ LEADERBOARD ============ --}}
<section id="peringkat" class="section-dark border-t border-[#2d2050]">
    <div class="max-w-4xl mx-auto px-4 py-20">
        <div class="text-center mb-12">
            <p class="font-pixel text-[9px] text-[#f5c518] tracking-widest mb-3">PAPAN PERINGKAT</p>
            <h2 class="font-pixel text-xl md:text-2xl text-[#f5c518] gold-shadow">HALL OF FAME</h2>
            <p class="text-[#7766aa] mt-3 text-sm">Para petualang piket dengan XP tertinggi.</p>
            <div class="shimmer-line w-24 mx-auto mt-4"></div>
        </div>

        @php $top3 = $leaderboard->take(3); $rest = $leaderboard->slice(3); @endphp

        @if ($top3->isNotEmpty())
            @php
                $podium = [
                    0 => ['rank_color' => '#f5c518', 'rank_label' => '#1', 'border' => '#f5c518', 'order' => 'md:order-2', 'lift' => 'md:-mt-6'],
                    1 => ['rank_color' => '#c0c0c0', 'rank_label' => '#2', 'border' => '#c0c0c0', 'order' => 'md:order-1', 'lift' => ''],
                    2 => ['rank_color' => '#cd7f32', 'rank_label' => '#3', 'border' => '#cd7f32', 'order' => 'md:order-3', 'lift' => ''],
                ];
            @endphp
            <div class="grid grid-cols-3 gap-2 md:gap-4 items-end mb-4">
                @foreach ($top3 as $i => $p)
                    @php $m = $podium[$i]; @endphp
                    <div class="{{ $m['order'] }} {{ $m['lift'] }}">
                        <button wire:click="$dispatch('openProfileModal', { userId: {{ $p->user_id }} })"
                                class="block w-full focus:outline-none cursor-pointer">
                            <div class="bg-[#14102a] border-2 p-3 md:p-4 text-center transition-colors"
                                 style="border-color: {{ $m['border'] }}33;" onmouseover="this.style.borderColor='{{ $m['border'] }}'" onmouseout="this.style.borderColor='{{ $m['border'] }}33'">
                                <span class="font-pixel text-xs" style="color: {{ $m['rank_color'] }}">{{ $m['rank_label'] }}</span>
                                <div class="mt-2 flex justify-center">
                                    <img src="{{ $p->avatar_url }}" alt="{{ $p->user?->name }}"
                                         class="w-12 h-12 md:w-14 md:h-14 object-cover border-2"
                                         style="border-color: {{ $m['border'] }}">
                                </div>
                                <p class="text-xs md:text-sm text-[#e8e8f0] truncate mt-2">
                                    {{ $p->user?->name ?? '—' }}
                                    @if ($convictVisible && $p->status === StudentStatus::CONVICT) <span style="color:#e63946">[CONVICT]</span> @endif
                                </p>
                                <p class="font-pixel text-[9px] text-[#f5c518] mt-1">{{ number_format($p->xp) }} XP</p>
                                <p class="font-pixel text-[7px] text-[#6655aa]">{{ $p->piket_count }} piket</p>
                            </div>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($rest->isNotEmpty())
            <div class="rpg-card p-3 space-y-1">
                @foreach ($rest as $idx => $p)
                    <button wire:click="$dispatch('openProfileModal', { userId: {{ $p->user_id }} })"
                            class="w-full flex items-center gap-3 p-2 border-b border-[#2d2050]/60 hover:bg-[#2d2050]/40 focus:outline-none cursor-pointer text-left last:border-b-0 transition-colors">
                        <span class="font-pixel text-[9px] w-6 text-center text-[#6655aa]">{{ $loop->iteration + 3 }}</span>
                        <img src="{{ $p->avatar_url }}" alt="{{ $p->user?->name }}"
                             class="w-8 h-8 object-cover shrink-0 border border-[#2d2050]">
                        <span class="flex-1 text-sm truncate text-[#c0b8e0]">
                            {{ $p->user?->name ?? '—' }}
                            @if ($convictVisible && $p->status === StudentStatus::CONVICT) <span class="text-xs" style="color:#e63946">[CONVICT]</span> @endif
                        </span>
                        <div class="text-right">
                            <p class="font-pixel text-[9px] text-[#f5c518]">{{ number_format($p->xp) }}</p>
                            <p class="font-pixel text-[7px] text-[#6655aa]">{{ $p->piket_count }} piket</p>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif

        @if ($leaderboard->isEmpty())
            <div class="rpg-card p-10 text-center">
                <p class="font-pixel text-[10px] text-[#6655aa]">Belum ada petualang. Jadilah yang pertama!</p>
            </div>
        @endif
    </div>
</section>

{{-- ============ CTA ============ --}}
<section class="cta-starfield border-t-2 border-[#f5c518]/20 relative overflow-hidden">
    {{-- Bottom city (mirrored/lighter) --}}
    <div class="relative max-w-3xl mx-auto px-4 py-20 text-center z-10">
        <p class="font-pixel text-[9px] text-[#a78bfa] tracking-widest mb-4">BERGABUNGLAH SEKARANG</p>
        <h2 class="font-pixel text-xl md:text-3xl text-white pixel-shadow mb-2">SIAP JADI</h2>
        <h2 class="font-pixel text-2xl md:text-4xl gold-shadow mb-8" style="color:#f5c518;">LEGENDA PIKET?</h2>
        <p class="text-[#7766aa] mb-10 text-sm">Masuk dengan akunmu dan mulai kumpulkan XP hari ini.</p>
        @auth
            @php $dashRoute = auth()->user()->role === \App\Enums\UserRole::Admin ? route('admin.dashboard') : route('student.dashboard'); @endphp
            <a href="{{ $dashRoute }}"
               class="inline-block pixel-btn btn-pulse bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-10 py-4 tracking-widest">
                KE DASHBOARD
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-block pixel-btn btn-pulse bg-[#f5c518] text-[#0c0918] font-pixel text-[10px] px-10 py-4 tracking-widest">
                MASUK SEKARANG
            </a>
        @endauth
    </div>
</section>

{{-- ============ TICKER ============ --}}
<div class="bg-[#f5c518] py-2 overflow-hidden border-y border-[#c49a0a]">
    <div class="ticker-track inline-flex gap-8 font-pixel text-[9px] text-[#0c0918]">
        @for ($i = 0; $i < 2; $i++)
            <span>PIKET RPG</span><span>★</span>
            <span>KLAIM MISI</span><span>★</span>
            <span>KUMPULKAN XP</span><span>★</span>
            <span>RAIH BADGE</span><span>★</span>
            <span>PANJAT PERINGKAT</span><span>★</span>
            <span>JAGA NYAWAMU</span><span>★</span>
            <span>JANGAN JADI CONVICT</span><span>★</span>
        @endfor
    </div>
</div>

{{-- ============ FOOTER ============ --}}
<footer class="bg-[#0a0816] border-t border-[#2d2050]">
    <div class="max-w-6xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-2">
        <span class="font-pixel text-[10px] text-[#f5c518] tracking-wider pixel-shadow">PIKET RPG</span>
        <p class="text-xs text-[#3d3360]">© {{ date('Y') }} Piket Mingguan RPG. Dibuat untuk kelas yang lebih seru.</p>
    </div>
</footer>

@livewireScripts
<livewire:profile-modal />
</body>
</html>
