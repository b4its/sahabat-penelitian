<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sahabat Penelitian - Platform riset, olah naskah ilmiah, sitasi otomatis berstandar internasional, dan asisten publikasi akademik terpadu.">
    <title>Sahabat Penelitian — Platform Riset & Penulisan Naskah Ilmiah Terpadu</title>

    @fonts

    <!-- Theme Initialization Script to prevent FOUC -->
    <script>
        if (localStorage.getItem('sahabat_theme') === 'dark' || (!('sahabat_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback Tailwind CSS Link if manifest not present -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                50: '#eff6ff',
                                100: '#dbeafe',
                                200: '#bfdbfe',
                                300: '#93c5fd',
                                400: '#60a5fa',
                                500: '#3b82f6',
                                600: '#2563eb',
                                700: '#1d4ed8',
                                800: '#1e40af',
                                900: '#1e3a8a',
                                950: '#172554',
                            }
                        }
                    }
                }
            }
        </script>
    @endif

    <style>
        /* Custom Keyframe Animations for Sahabat Penelitian */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(1deg); }
        }
        @keyframes float-reverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(12px) rotate(-1deg); }
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.75; transform: scale(1.08); }
        }
        @keyframes shimmer-sweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        @keyframes ring-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .animate-float-slow {
            animation: float-slow 7s ease-in-out infinite;
        }
        .animate-float-reverse {
            animation: float-reverse 8s ease-in-out infinite;
        }
        .animate-pulse-glow {
            animation: pulse-glow 6s ease-in-out infinite;
        }
        .animate-shimmer {
            animation: shimmer-sweep 2.5s infinite;
        }
        .animate-spin-slow {
            animation: ring-spin 25s linear infinite;
        }

        /* Glassmorphism Classes */
        .glass-panel {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(219, 234, 254, 0.7);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(30, 58, 138, 0.45);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* Blue Gradient Text */
        .text-gradient-blue {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .dark .text-gradient-blue {
            background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 50%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Ambient Glow Grid */
        .bg-grid-pattern {
            background-size: 36px 36px;
            background-image: 
                linear-gradient(to right, rgba(59, 130, 246, 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(59, 130, 246, 0.06) 1px, transparent 1px);
        }
        .dark .bg-grid-pattern {
            background-image: 
                linear-gradient(to right, rgba(59, 130, 246, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(59, 130, 246, 0.08) 1px, transparent 1px);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#070d1e] text-slate-800 dark:text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white transition-colors duration-300">

    <!-- Ambient Glow Spots (Atmospheric Blue Lighting) -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 -left-28 w-96 md:w-[600px] h-96 md:h-[600px] bg-blue-400/20 dark:bg-blue-600/15 rounded-full blur-3xl animate-pulse-glow"></div>
        <div class="absolute top-1/3 -right-32 w-80 md:w-[500px] h-80 md:h-[500px] bg-sky-400/20 dark:bg-sky-500/15 rounded-full blur-3xl animate-float-slow"></div>
        <div class="absolute bottom-10 left-1/4 w-96 md:w-[650px] h-96 md:h-[650px] bg-indigo-500/15 dark:bg-blue-800/15 rounded-full blur-3xl animate-float-reverse"></div>
        <div class="absolute inset-0 bg-grid-pattern pointer-events-none opacity-70"></div>
    </div>

    <!-- Navigation Header (Sticky Glassmorphism) -->
    <header class="sticky top-0 z-50 glass-panel border-b border-blue-100 dark:border-blue-900/50 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-xl p-1">
                <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-700 via-blue-600 to-sky-400 flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-300">
                    <!-- Atom & Feather Icon -->
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full animate-ping"></span>
                    <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5">
                        Sahabat <span class="text-blue-600 dark:text-blue-400">Penelitian</span>
                    </span>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 -mt-0.5 tracking-wide">
                        Platform Riset & Naskah Ilmiah
                    </span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                <a href="#features" class="px-3.5 py-2 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors">
                    Fitur Unggulan
                </a>
                <a href="#demo-studio" class="px-3.5 py-2 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors">
                    Demo Interaktif
                </a>
                <a href="#workflow" class="px-3.5 py-2 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors">
                    Alur Riset
                </a>
                <a href="#calculator" class="px-3.5 py-2 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors">
                    Kesiapan Naskah
                </a>
                <a href="#faq" class="px-3.5 py-2 rounded-lg hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors">
                    FAQ
                </a>
                <a href="{{ route('panel.dashboard') }}" class="px-3.5 py-2 rounded-lg text-blue-600 dark:text-blue-400 font-bold hover:bg-blue-50/70 dark:hover:bg-blue-950/40 transition-colors flex items-center gap-1.5">
                    <span>Panel Riset</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                </a>
            </nav>

            <!-- Right Controls: Theme Switcher + CTA -->
            <div class="flex items-center gap-3">
                <!-- Theme Switch Toggle Button -->
                <button
                    id="theme-toggle"
                    type="button"
                    aria-label="Ganti Tema (Gelap / Terang)"
                    class="relative p-2.5 rounded-xl border border-blue-200 dark:border-blue-800/80 bg-white/90 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-400 dark:hover:border-blue-600 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 group"
                    title="Ganti Tema (Gelap / Terang)"
                >
                    <!-- Sun Icon (Shown in Dark Mode) -->
                    <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden dark:block text-amber-400 transform group-hover:rotate-45 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (Shown in Light Mode) -->
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5 block dark:hidden text-blue-600 transform group-hover:-rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Enter Editor CTA Button -->
                <a
                    href="{{ url('/editor') }}"
                    class="relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-600 hover:from-blue-700 hover:via-blue-800 hover:to-indigo-700 shadow-md shadow-blue-600/30 hover:shadow-lg hover:shadow-blue-600/40 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                >
                    <svg class="w-4 h-4 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Buka Editor Naskah</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="relative z-10">

        <!-- ========================================================================= -->
        <!-- 1. HERO SECTION                                                          -->
        <!-- ========================================================================= -->
        <section class="pt-12 pb-20 md:pt-20 md:pb-32 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center">
            <!-- Badge Pill -->
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full border border-blue-300/80 dark:border-blue-700/60 bg-blue-50/80 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-xs sm:text-sm font-medium mb-8 backdrop-blur-md shadow-xs animate-float-slow">
                <span class="flex h-2 w-2 rounded-full bg-blue-600 dark:bg-blue-400 animate-ping"></span>
                <span>✨ Standar Naskah Jurnal SINTA & Scopus Terintegrasi</span>
            </div>

            <!-- Headline -->
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-slate-900 dark:text-white max-w-5xl mx-auto leading-tight sm:leading-tight">
                Meneliti Lebih Dalam, Menulis Lebih Presisi, 
                <span class="block text-gradient-blue mt-2">Publikasi Berstandar Global.</span>
            </h1>

            <!-- Subtitle -->
            <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto leading-relaxed">
                <strong>Sahabat Penelitian</strong> menyatukan kemudahan editor naskah berpresisi kertas A4, mesin sitasi otomatis (APA 7th, IEEE, Harvard), analisis tata bahasa PUEBI, dan format Open XML (<code class="text-blue-600 dark:text-blue-400 font-mono text-sm">.docx</code>) murni tanpa risiko kerusakan layout.
            </p>

            <!-- Hero Action Buttons -->
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a
                    href="{{ url('/editor') }}"
                    class="px-8 py-4 rounded-2xl font-bold text-base text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-xl shadow-blue-600/35 hover:shadow-2xl hover:shadow-blue-600/45 transform hover:-translate-y-1 transition-all duration-200 flex items-center gap-3"
                >
                    <svg class="w-5 h-5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Mulai Menulis Naskah Baru</span>
                    <svg class="w-4 h-4 text-blue-200 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>

                <a
                    href="#demo-studio"
                    class="px-7 py-4 rounded-2xl font-semibold text-base text-slate-700 dark:text-slate-200 bg-white/90 dark:bg-slate-800/90 hover:bg-blue-50 dark:hover:bg-slate-700/80 border border-blue-200 dark:border-blue-800 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2.5"
                >
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Coba Demo Interaktif</span>
                </a>
            </div>

            <!-- Trust Chips Strip -->
            <div class="mt-14 pt-8 border-t border-slate-200/80 dark:border-slate-800 max-w-4xl mx-auto grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                <div class="p-3 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-blue-100/80 dark:border-blue-900/40">
                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">100%</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Format DOCX Murni OPC</div>
                </div>
                <div class="p-3 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-blue-100/80 dark:border-blue-900/40">
                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">APA & IEEE</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Sitasi Otomatis CrossRef</div>
                </div>
                <div class="p-3 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-blue-100/80 dark:border-blue-900/40">
                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">PUEBI & AI</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Audit Ejaan & Keterbacaan</div>
                </div>
                <div class="p-3 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-blue-100/80 dark:border-blue-900/40">
                    <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">0% Loss</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Integritas Struktur Naskah</div>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 2. INTERACTIVE LIVE RESEARCH STUDIO SIMULATOR                            -->
        <!-- ========================================================================= -->
        <section id="demo-studio" class="py-16 md:py-24 bg-blue-50/50 dark:bg-slate-900/50 border-y border-blue-100 dark:border-blue-900/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-950/80 mb-3">
                        ⚡ Simulator Naskah Langsung
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Eksplorasi Kehebatan Studio Naskah Secara Langsung
                    </h2>
                    <p class="mt-3 text-slate-600 dark:text-slate-300 text-base sm:text-lg">
                        Coba langsung fitur penyesuaian gaya sitasi real-time, simulasi Lacak Perubahan (Track Changes), dan penghitungan metrik naskah tanpa meninggalkan halaman ini.
                    </p>
                </div>

                <!-- Simulator Mockup Window -->
                <div class="max-w-5xl mx-auto rounded-3xl overflow-hidden shadow-2xl shadow-blue-500/10 border border-blue-200 dark:border-blue-800/80 bg-white dark:bg-slate-800 transition-all duration-300">
                    
                    <!-- Top Window Bar -->
                    <div class="px-5 py-3.5 bg-slate-100 dark:bg-slate-900 border-b border-blue-100 dark:border-blue-800/60 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span>
                            <span class="ml-2 text-xs font-semibold text-slate-600 dark:text-slate-400 font-mono">
                                VelScribe Wordcraft Studio — Naskah_Publikasi_2026.docx
                            </span>
                        </div>

                        <!-- Interactive Simulator Controls -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Preset Template Switch -->
                            <div class="flex items-center rounded-lg bg-slate-200 dark:bg-slate-800 p-0.5 text-xs font-medium">
                                <button type="button" id="btn-preset-journal" class="px-3 py-1 rounded-md bg-white dark:bg-blue-600 text-blue-700 dark:text-white shadow-xs font-semibold">Jurnal SINTA</button>
                                <button type="button" id="btn-preset-thesis" class="px-3 py-1 rounded-md text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-white">Tesis / Skripsi</button>
                            </div>

                            <!-- Track Changes Live Toggle -->
                            <button
                                type="button"
                                id="btn-toggle-demo-track"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900 transition-colors"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Track Changes: <span id="demo-track-status" class="text-emerald-600 dark:text-emerald-400 font-bold">AKTIF</span></span>
                            </button>
                        </div>
                    </div>

                    <!-- Interactive Citation Selector Bar -->
                    <div class="px-6 py-2.5 bg-blue-50/70 dark:bg-slate-900/70 border-b border-blue-100 dark:border-blue-900/40 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-2 font-semibold text-slate-700 dark:text-slate-300">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <span>Uji Gaya Sitasi Instan:</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-style="apa" class="citation-btn px-3 py-1 rounded-md bg-blue-600 text-white font-bold transition-all shadow-xs">APA 7th</button>
                            <button type="button" data-style="ieee" class="citation-btn px-3 py-1 rounded-md bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-500 font-semibold transition-all">IEEE</button>
                            <button type="button" data-style="harvard" class="citation-btn px-3 py-1 rounded-md bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-blue-500 font-semibold transition-all">Harvard</button>
                        </div>
                    </div>

                    <!-- Simulated Paper Canvas -->
                    <div class="p-6 sm:p-10 bg-slate-100/70 dark:bg-slate-900/80 min-h-[360px] flex justify-center">
                        <div class="w-full max-w-3xl bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200/80 dark:border-slate-700 p-8 text-slate-800 dark:text-slate-100 transition-all duration-300">
                            
                            <h3 id="demo-doc-title" class="text-xl sm:text-2xl font-bold text-center text-slate-900 dark:text-white mb-2 leading-snug">
                                Rancang Bangun Otomasi Penjamin Mutu Limbah Tekstil Berbasis Sensor Spektral dan IoT
                            </h3>
                            <p class="text-center text-xs text-slate-500 dark:text-slate-400 mb-6 italic">
                                Alvin Grand Putra¹, Ratna Sari², Hendra Wijaya³
                            </p>

                            <div class="border-t border-b border-slate-100 dark:border-slate-700 py-3 mb-6">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 mb-1">Abstrak</h4>
                                <p id="demo-doc-abstract" class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed text-justify">
                                    Penelitian ini menyajikan solusi terotomasi kendali mutu limbah berbasis mikrokontroler. Efisiensi pengolahan mencapai 94.2% dengan latensi transmisi telemetri di bawah 120 milidetik. Hasil ini selaras dengan temuan terdahulu <span id="demo-inline-citation" class="inline-block px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/70 text-blue-800 dark:text-blue-200 font-mono font-bold transition-all duration-300">(Wijaya, 2026)</span> mengenai stabilitas sensor spektral pada variasi kekeruhan cairan industri.
                                </p>
                            </div>

                            <!-- Track Changes Paragraph Demonstration -->
                            <div class="mb-6">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Kutipan Bagian Metodologi</h4>
                                <p id="demo-track-paragraph" class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed text-justify">
                                    Pengujian dilakukan dengan <span class="track-add bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 underline underline-offset-2 decoration-emerald-500 font-medium px-1 rounded">tiga puluh kali perulangan bertingkat</span> <span class="track-del bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-400 line-through decoration-rose-500 px-1 rounded">secara acak tanpa kalibrasi</span> untuk menjamin akurasi distribusi probabilitas dan presisi deteksi anomali.
                                </p>
                            </div>

                            <!-- Live Bibliography Entry -->
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-2">Daftar Pustaka Terformat</h4>
                                <div id="demo-bib-output" class="text-xs font-mono bg-blue-50/60 dark:bg-slate-900/60 p-3 rounded-lg border border-blue-100 dark:border-blue-900/40 text-blue-950 dark:text-blue-200 leading-relaxed">
                                    Wijaya, Hendra (2026). Pengembangan Naskah Akademik Otomatis. <em>Jurnal Sistem Informasi</em>, <em>12</em>, 45-56. https://doi.org/10.31294/jsi.v12i1.2026
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Status Bar -->
                    <div class="px-6 py-3 bg-slate-100 dark:bg-slate-900 border-t border-blue-100 dark:border-blue-800/60 flex flex-wrap items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                        <div class="flex items-center gap-4">
                            <span>Statistik: <strong id="demo-word-count" class="text-blue-600 dark:text-blue-400">184 Kata</strong></span>
                            <span>Keterbacaan Flesch: <strong class="text-emerald-600 dark:text-emerald-400">68.4 (Baik)</strong></span>
                            <span>Standar Format: <strong>A4 Portrait</strong></span>
                        </div>
                        <div>
                            <a href="{{ url('/editor') }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                <span>Buka Naskah Ini di Editor Penuh</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 3. BENTO GRID FITUR UNGGULAN (MODERN BLUE THEME)                         -->
        <!-- ========================================================================= -->
        <section id="features" class="py-20 md:py-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100/80 dark:bg-blue-950/80 mb-3">
                    🚀 Kapabilitas Komprehensif
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Enam Pilar Keunggulan Sahabat Penelitian
                </h2>
                <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">
                    Dirancang dari nol untuk menjawab seluruh rintangan penulisan skripsi, tesis, disertasi, dan artikel jurnal bereputasi.
                </p>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                
                <!-- Feature 1: Wide Card (DOCX Murni & Presisi A4) -->
                <div class="md:col-span-2 glass-card rounded-3xl p-8 lg:p-10 border border-blue-200/90 dark:border-blue-900/60 shadow-lg shadow-blue-500/5 hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/10 dark:bg-blue-600/10 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/25">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-800">
                            OPC Open XML Asli
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        Format DOCX Asli Berstandar Microsoft Word & LibreOffice
                    </h3>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                        Bukan sekadar berkas HTML yang diubah ekstensinya. Generator dokumen kami merakit arsip ZIP OPC murni dengan berkas XML terstruktur (<code class="text-blue-600 dark:text-blue-400 font-mono text-xs">word/document.xml</code>, <code class="text-blue-600 dark:text-blue-400 font-mono text-xs">styles.xml</code>, <code class="text-blue-600 dark:text-blue-400 font-mono text-xs">numbering.xml</code>). Margin standar skripsi 4-4-3-3 cm, seksi portrait dan landscape mandiri, penomoran Romawi pada halaman awal, dan tabel gabung (<code class="text-blue-600 dark:text-blue-400 font-mono text-xs">gridSpan</code> & <code class="text-blue-600 dark:text-blue-400 font-mono text-xs">vMerge</code>) terjaga sempurna.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-4 border-t border-slate-200/60 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Section Breaks Bebas
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Header & Footer Mandiri
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Table Repeating Headers
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Sitasi Otomatis -->
                <div class="glass-card rounded-3xl p-8 border border-blue-200/90 dark:border-blue-900/60 shadow-lg shadow-blue-500/5 hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-300 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/25 mb-5">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                        Sitasi & Lookup DOI Otomatis
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Cukup ketikkan judul atau tempel nomor DOI, sistem langsung mengambil metadata resmi dari CrossRef dan Google Books. Pembuatan daftar pustaka berformat gantung (<em class="text-blue-600 dark:text-blue-400">hanging indent</em>) sesuai pedoman APA 7th, IEEE, atau Harvard.
                    </p>
                    <span class="inline-block text-xs font-bold text-indigo-600 dark:text-indigo-400">Terkoneksi CrossRef Global API →</span>
                </div>

                <!-- Feature 3: Lacak Perubahan & Review -->
                <div class="glass-card rounded-3xl p-8 border border-blue-200/90 dark:border-blue-900/60 shadow-lg shadow-blue-500/5 hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-300 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center shadow-md shadow-sky-500/25 mb-5">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                        Lacak Perubahan & Review Ilmiah
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Fitur Track Changes bawaan dengan tag <code class="text-blue-600 dark:text-blue-400 font-mono text-xs">w:ins</code> dan <code class="text-blue-600 dark:text-blue-400 font-mono text-xs">w:del</code>. Pembimbing dan reviewer dapat menambahkan komentar berlabuh (<em class="text-blue-600 dark:text-blue-400">anchored comments</em>) dan membandingkan revisi naskah secara <em class="text-blue-600 dark:text-blue-400">legal blackline diff</em>.
                    </p>
                    <span class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400">Kompatibel Track Changes Word →</span>
                </div>

                <!-- Feature 4: Kolaborasi Real-Time & Versi Server -->
                <div class="glass-card rounded-3xl p-8 border border-blue-200/90 dark:border-blue-900/60 shadow-lg shadow-blue-500/5 hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-300 relative overflow-hidden group">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-600 text-white flex items-center justify-center shadow-md shadow-cyan-600/25 mb-5">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                        Kolaborasi Multi-Peneliti & Versi Server
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Tulis artikel ilmiah bersama tim laboratorium tanpa takut konflik revisi. Didukung sinkronisasi posisi kursor real-time, jejak audit riwayat mutasi (<code class="text-blue-600 dark:text-blue-400 font-mono text-xs">audit_events</code>), dan snapshot versi yang tidak pernah menghapus sejarah draf lama.
                    </p>
                    <span class="inline-block text-xs font-bold text-cyan-600 dark:text-cyan-400">Snapshot Non-Destruktif →</span>
                </div>

                <!-- Feature 5: Wide Card (PUEBI, Audit Aksesibilitas & Mail Merge) -->
                <div class="md:col-span-2 glass-card rounded-3xl p-8 lg:p-10 border border-blue-200/90 dark:border-blue-900/60 shadow-lg shadow-blue-500/5 hover:border-blue-400 dark:hover:border-blue-600 transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-blue-700 text-white flex items-center justify-center shadow-md shadow-blue-700/25">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-800">
                            PUEBI & Keamanan Privasi
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        Pemeriksa Tata Bahasa PUEBI, Mail Merge & Perlindungan Sandi
                    </h3>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                        Pengecekan ejaan baku bahasa Indonesia, pemisahan tanda baca, dan audit aksesibilitas dokumen (heading skip, alt image, header tabel). Dilengkapi Mail Merge berbasis CSV untuk mencetak surat undangan seminar dan amplop naskah massal, serta proteksi sandi dokumen berbasis Bcrypt yang aman dari intrusi data.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-4 border-t border-slate-200/60 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 100% Pemrosesan Aman
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Karantina Makro Berbahaya
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Batch Mail Merge CSV
                        </div>
                    </div>
                </div>

            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 4. INTERACTIVE PUBLICATION READINESS CALCULATOR                           -->
        <!-- ========================================================================= -->
        <section id="calculator" class="py-16 md:py-24 bg-blue-50/60 dark:bg-slate-900/60 border-y border-blue-100 dark:border-blue-900/40">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-950/80 mb-3">
                        📊 Kalkulator Akademik Interaktif
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Cek Skor Kesiapan Publikasi Naskah Anda
                    </h2>
                    <p class="mt-2 text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                        Pilih target publikasi dan centang kelengkapan naskah untuk mengukur kesiapan submit ke dewan redaksi jurnal atau sidang akademik.
                    </p>
                </div>

                <!-- Calculator Container Card -->
                <div class="glass-card rounded-3xl p-6 sm:p-10 border border-blue-200 dark:border-blue-800 shadow-xl shadow-blue-500/5">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                        
                        <!-- Left Form Inputs (7 Cols) -->
                        <div class="md:col-span-7 space-y-5">
                            <div>
                                <label for="target-type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                    Target Publikasi / Karya Tulis:
                                </label>
                                <select id="target-type" class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="sinta">Jurnal Nasional Terakreditasi (SINTA 1-3)</option>
                                    <option value="scopus">Jurnal Internasional Bereputasi (Scopus / WoS)</option>
                                    <option value="thesis">Skripsi / Tesis / Disertasi Pascasarjana</option>
                                    <option value="conference">Prosiding Konferensi Ilmiah Internasional (IEEE)</option>
                                </select>
                            </div>

                            <div>
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2.5">
                                    Komponen Naskah yang Telah Diselesaikan:
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" checked data-weight="15">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Abstrak & Kata Kunci Bilingual</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" checked data-weight="20">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Research Gap & Pendahuluan</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" checked data-weight="20">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Metodologi & Desain Eksperimen</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" checked data-weight="20">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Hasil, Pembahasan & Grafik</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" data-weight="15">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Sitasi Otomatis &gt; 25 Referensi</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-blue-50/50 dark:hover:bg-slate-800 cursor-pointer transition-colors">
                                        <input type="checkbox" class="readiness-check rounded text-blue-600 focus:ring-blue-500" data-weight="10">
                                        <span class="text-slate-700 dark:text-slate-300 font-medium">Format Author Guidelines DOCX</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Right Progress Visualizer (5 Cols) -->
                        <div class="md:col-span-5 text-center flex flex-col items-center justify-center p-6 rounded-2xl bg-blue-50/70 dark:bg-slate-800/80 border border-blue-200/60 dark:border-blue-900/40">
                            <!-- Circular Score Ring Visualizer -->
                            <div class="relative w-36 h-36 flex items-center justify-center mb-4">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                    <circle class="text-slate-200 dark:text-slate-700" stroke-width="10" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" />
                                    <circle id="score-circle" class="text-blue-600 dark:text-blue-400 transition-all duration-700 ease-out" stroke-width="10" stroke-dasharray="251.2" stroke-dashoffset="62.8" stroke-linecap="round" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" />
                                </svg>
                                <div class="absolute flex flex-col items-center">
                                    <span id="readiness-percentage" class="text-3xl font-extrabold text-slate-900 dark:text-white">75%</span>
                                    <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Kesiapan</span>
                                </div>
                            </div>

                            <div id="readiness-status-badge" class="px-4 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 mb-3">
                                Siap Review Internal Pembimbing
                            </div>

                            <p id="readiness-advice" class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                Naskah hampir tuntas! Tambahkan sitasi terkini dari jurnal 5 tahun terakhir untuk memperkuat kebaruan hasil penelitian Anda.
                            </p>

                            <a href="{{ url('/editor') }}" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/30 transition-all flex items-center justify-center gap-1.5">
                                <span>Lengkapi di Wordcraft Studio</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 5. ALUR KERJA PENELITIAN MODERN (5 TAHAP)                                 -->
        <!-- ========================================================================= -->
        <section id="workflow" class="py-20 md:py-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100/80 dark:bg-blue-950/80 mb-3">
                    🗺️ Metodologi Efisien
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Alur Riset Terpadu dari Ide Menuju Publikasi
                </h2>
                <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">
                    Sistem dirancang selaras dengan siklus hidup penelitian akademik modern di perguruan tinggi dan lembaga riset.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 lg:gap-6 relative">
                <!-- Step 1 -->
                <div class="glass-card rounded-2xl p-6 border border-blue-200/80 dark:border-blue-900/50 hover:border-blue-500 transition-all hover:-translate-y-1 duration-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-extrabold flex items-center justify-center mb-4 text-sm">
                        01
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Konseptualisasi</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Tentukan topik, rumuskan pertanyaan penelitian, dan buat outline hierarki judul di panel navigasi.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card rounded-2xl p-6 border border-blue-200/80 dark:border-blue-900/50 hover:border-blue-500 transition-all hover:-translate-y-1 duration-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-extrabold flex items-center justify-center mb-4 text-sm">
                        02
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Studi Pustaka</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Cari artikel rujukan via DOI CrossRef langsung dari editor, format bibliografi otomatis.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card rounded-2xl p-6 border border-blue-200/80 dark:border-blue-900/50 hover:border-blue-500 transition-all hover:-translate-y-1 duration-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-extrabold flex items-center justify-center mb-4 text-sm">
                        03
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Penulisan Naskah</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Ketik naskah dengan kenyamanan layout A4, asistensi ejaan PUEBI, tabel gabung, dan rumus KaTeX.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="glass-card rounded-2xl p-6 border border-blue-200/80 dark:border-blue-900/50 hover:border-blue-500 transition-all hover:-translate-y-1 duration-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-extrabold flex items-center justify-center mb-4 text-sm">
                        04
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Peer Review</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Ajak pembimbing atau rekan meninjau dengan Track Changes, anchored comments, dan diff dokumen.
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="glass-card rounded-2xl p-6 border border-blue-200/80 dark:border-blue-900/50 hover:border-blue-500 transition-all hover:-translate-y-1 duration-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-extrabold flex items-center justify-center mb-4 text-sm shadow-md shadow-blue-600/30">
                        05
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">Publikasi Sukses</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Unduh DOCX murni dan PDF siap kirim ke portal OJS atau submit sidang skripsi tanpa format rusak.
                    </p>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 6. TESTIMONIALS SECTION                                                   -->
        <!-- ========================================================================= -->
        <section class="py-16 md:py-24 bg-blue-50/50 dark:bg-slate-900/50 border-t border-blue-100 dark:border-blue-900/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-950/80 mb-3">
                        💬 Pengalaman Akademisi
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Dipercaya Oleh Peneliti dan Dosen di Seluruh Nusantara
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Testimonial 1 -->
                    <div class="glass-card rounded-3xl p-7 border border-blue-200 dark:border-blue-800/80 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-4">
                                ★★★★★
                            </div>
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed italic mb-6">
                                "Sebelumnya saya selalu khawatir format tabel dan margin berubah ketika mahasiswa bimbingan mengirim draf. Dengan Sahabat Penelitian, berkas DOCX yang diekspor 100% identik di Microsoft Word desktop. Fitur Track Changes-nya sangat memudahkan bimbingan jarak jauh."
                            </p>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-sky-400 text-white font-bold flex items-center justify-center text-sm">
                                HW
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Prof. Dr. Hendra Wijaya, M.Sc.</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Guru Besar & Reviewer Jurnal Internasional</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="glass-card rounded-3xl p-7 border border-blue-200 dark:border-blue-800/80 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-4">
                                ★★★★★
                            </div>
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed italic mb-6">
                                "Lookup DOI otomatis dari CrossRef menghemat waktu riset kami hingga 70%. Saya tidak perlu lagi merapikan spasi dan tanda koma satu per satu pada daftar pustaka format APA. Ini standar baru platform penulisan akademik di Indonesia."
                            </p>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-400 text-white font-bold flex items-center justify-center text-sm">
                                RS
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Dr. Ratna Sari, S.T., M.Eng.</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Peneliti Rekayasa Sistem & Dosen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="glass-card rounded-3xl p-7 border border-blue-200 dark:border-blue-800/80 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-4">
                                ★★★★★
                            </div>
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed italic mb-6">
                                "Menyelesaikan tesis 250 halaman tanpa crash sama sekali. Navigasi outline judul cepat dan generator daftar isinya sangat akurat. Yang terpenting, data penelitian saya tidak pernah bocor ke server pihak ketiga."
                            </p>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-cyan-600 to-blue-500 text-white font-bold flex items-center justify-center text-sm">
                                AP
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Amanda Putri, S.Kom.</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kandidat Magister Ilmu Komputer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 7. FREQUENTLY ASKED QUESTIONS (FAQ ACCORDION)                             -->
        <!-- ========================================================================= -->
        <section id="faq" class="py-20 md:py-28 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 bg-blue-100/80 dark:bg-blue-950/80 mb-3">
                    ❓ Pertanyaan Umum
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Hal yang Sering Ditanyakan Peneliti
                </h2>
                <p class="mt-3 text-slate-600 dark:text-slate-300 text-base">
                    Temukan jawaban cepat seputar kompatibilitas dokumen, keamanan, dan fitur Sahabat Penelitian.
                </p>
            </div>

            <!-- FAQ Items List -->
            <div class="space-y-4">
                <!-- Item 1 -->
                <div class="faq-item glass-card rounded-2xl border border-blue-200/80 dark:border-blue-900/50 overflow-hidden transition-all duration-200">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            Apakah berkas .docx yang diekspor benar-benar kompatibel dengan Microsoft Word?
                        </span>
                        <svg class="faq-icon w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5 pt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800">
                        Ya. Sahabat Penelitian menggunakan generator Open Packaging Convention (OPC) berbasis Open XML murni. Berkas yang dihasilkan dapat dibuka secara sempurna di Microsoft Word 365, Word Desktop (2016–2024), Word for the Web, dan LibreOffice Writer tanpa peringatan "corrupted file" atau pergeseran tata letak.
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="faq-item glass-card rounded-2xl border border-blue-200/80 dark:border-blue-900/50 overflow-hidden transition-all duration-200">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            Bagaimana sistem mengelola sitasi dan daftar pustaka secara otomatis?
                        </span>
                        <svg class="faq-icon w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5 pt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800">
                        Sistem terhubung langsung dengan CrossRef Metadata API dan Google Books. Anda cukup memasukkan kata kunci judul atau nomor DOI. Sistem memformat in-text citation dan entri daftar pustaka otomatis sesuai standar APA 7th Edition, IEEE, Harvard, MLA, Chicago, atau Vancouver secara instan.
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="faq-item glass-card rounded-2xl border border-blue-200/80 dark:border-blue-900/50 overflow-hidden transition-all duration-200">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            Apakah data penelitian dan draf naskah saya aman dari kebocoran privasi?
                        </span>
                        <svg class="faq-icon w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5 pt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800">
                        Sangat aman. Seluruh pemrosesan teks, audit PUEBI, dan penyimpanan riwayat versi beroperasi pada lingkungan server lokal yang terisolasi. Sistem tidak menjual, membagi, atau mentransmisikan data mentah naskah Anda ke model AI pihak ketiga tanpa persetujuan eksplisit.
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="faq-item glass-card rounded-2xl border border-blue-200/80 dark:border-blue-900/50 overflow-hidden transition-all duration-200">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            Bisakah saya berkolaborasi dengan pembimbing atau rekan penulis secara bersamaan?
                        </span>
                        <svg class="faq-icon w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5 pt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800">
                        Bisa. Melalui fitur Kolaborasi Server, Anda dapat membagikan tautan dokumen dengan izin peran (Owner, Editor, Reviewer, atau Viewer). Kursor rekan peneliti terlihat secara dinamis disertai jejak audit mutasi naskah yang transparan.
                    </div>
                </div>

                <!-- Item 5 -->
                <div class="faq-item glass-card rounded-2xl border border-blue-200/80 dark:border-blue-900/50 overflow-hidden transition-all duration-200">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 focus:outline-none">
                        <span class="text-base font-bold text-slate-900 dark:text-white">
                            Apakah ada batasan jumlah halaman untuk dokumen tebal seperti disertasi?
                        </span>
                        <svg class="faq-icon w-5 h-5 text-blue-600 dark:text-blue-400 transform transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-5 pt-1 text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-100 dark:border-slate-800">
                        Tidak ada batasan buatan. Mesin dokumen kami telah teruji melintasi lebih dari 300 halaman naskah dengan ratusan heading tanpa kehilangan stabilitas penomoran halaman atau jangkar daftar isi otomatis.
                    </div>
                </div>
            </div>
        </section>


        <!-- ========================================================================= -->
        <!-- 8. FINAL CALL TO ACTION (ROYAL BLUE GRADIENT BANNER)                      -->
        <!-- ========================================================================= -->
        <section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 p-8 sm:p-14 lg:p-16 text-center text-white shadow-2xl shadow-blue-600/30">
                <!-- Background Accent Glow -->
                <div class="absolute -top-24 -left-24 w-80 h-80 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-sky-400/20 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 max-w-3xl mx-auto">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md mb-6">
                        🎓 Siap Memulai Riset Anda Hari Ini?
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight mb-6 leading-tight">
                        Wujudkan Naskah Ilmiah Berkualitas Tinggi dengan Sahabat Penelitian
                    </h2>
                    <p class="text-base sm:text-lg text-blue-100 leading-relaxed mb-10">
                        Bergabunglah dengan ribuan dosen, peneliti, dan mahasiswa pascasarjana yang telah beralih ke cara penulisan naskah yang lebih modern, presisi, dan bebas stres format.
                    </p>

                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <a
                            href="{{ url('/editor') }}"
                            class="px-8 py-4 rounded-2xl font-bold text-base text-blue-900 bg-white hover:bg-blue-50 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200 flex items-center gap-2.5"
                        >
                            <span>Buka Wordcraft Studio Sekarang</span>
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a
                            href="#features"
                            class="px-6 py-4 rounded-2xl font-semibold text-base text-white border border-white/40 hover:bg-white/10 backdrop-blur-md transition-all duration-200"
                        >
                            Pelajari Seluruh Fitur
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>


    <!-- ========================================================================= -->
    <!-- 9. FOOTER                                                                 -->
    <!-- ========================================================================= -->
    <footer class="glass-panel border-t border-blue-100 dark:border-blue-900/50 pt-16 pb-12 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 mb-12">
                
                <!-- Brand Bio (5 Cols) -->
                <div class="md:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 to-sky-400 flex items-center justify-center text-white shadow-md shadow-blue-500/25">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-slate-900 dark:text-white">
                            Sahabat <span class="text-blue-600 dark:text-blue-400">Penelitian</span>
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed pr-6">
                        Platform asistensi riset dan olah naskah akademik terpadu. Memadukan keindahan antarmuka web, presisi format dokumen Open XML (<code class="font-mono text-xs text-blue-600 dark:text-blue-400">.docx</code>), dan automasi sitasi ilmiah berstandar internasional.
                    </p>
                    <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Semua Layanan Studio Beroperasi Normal (100% Uptime)</span>
                    </div>
                </div>

                <!-- Quick Links (2 Cols) -->
                <div class="md:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Navigasi</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li><a href="#features" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Fitur Utama</a></li>
                        <li><a href="#demo-studio" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Demo Naskah</a></li>
                        <li><a href="#workflow" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Alur Penelitian</a></li>
                        <li><a href="#calculator" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Kesiapan Naskah</a></li>
                        <li><a href="{{ url('/editor') }}" class="hover:text-blue-600 dark:hover:text-blue-400 font-semibold text-blue-600 dark:text-blue-400 transition-colors">Studio Editor →</a></li>
                    </ul>
                </div>

                <!-- Standards & Compliance (3 Cols) -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Standar & Kompatibilitas</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>Microsoft Word M365 Desktop & Web</li>
                        <li>LibreOffice Writer 7.x & 24.x</li>
                        <li>Pedoman Sitasi APA 7th & IEEE</li>
                        <li>Standar Bahasa PUEBI Kemendikbud</li>
                        <li>Format Kertas ISO 216 A4 (4-4-3-3 cm)</li>
                    </ul>
                </div>

                <!-- Academic Integrity (2 Cols) -->
                <div class="md:col-span-2 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Keamanan</h4>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>Proteksi Sandi Bcrypt</li>
                        <li>Isolasi Makro Berbahaya</li>
                        <li>Karantina Traversal File</li>
                        <li>Jejak Audit Non-Destruktif</li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Copyright & Credentials -->
            <div class="pt-8 border-t border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
                <p>© {{ date('Y') }} Sahabat Penelitian — Seluruh Hak Cipta Dilindungi.</p>
                <div class="flex items-center gap-6">
                    <a href="{{ url('/editor') }}" class="hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors">Ruang Kerja Naskah</a>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    <a href="#demo-studio" class="hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-colors">Simulator Ilmiah</a>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    <span>Versi Sistem 2.5 (Core Stable)</span>
                </div>
            </div>
        </div>
    </footer>


    <!-- ========================================================================= -->
    <!-- INTERACTIVE JAVASCRIPT LOGIC (DARK/LIGHT THEME, SIMULATOR, CALCULATOR)    -->
    <!-- ========================================================================= -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // -------------------------------------------------------------
            // 1. Dark & Light Theme Switcher with LocalStorage Persistence
            // -------------------------------------------------------------
            const themeToggleBtn = document.getElementById('theme-toggle');
            
            function updateTheme(isDark) {
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('sahabat_theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('sahabat_theme', 'light');
                }
            }

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const isDarkCurrently = document.documentElement.classList.contains('dark');
                    updateTheme(!isDarkCurrently);
                });
            }

            // Listen to OS system color scheme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('sahabat_theme')) {
                    updateTheme(e.matches);
                }
            });


            // -------------------------------------------------------------
            // 2. Interactive Citation Formatter Switcher in Demo Simulator
            // -------------------------------------------------------------
            const citationButtons = document.querySelectorAll('.citation-btn');
            const inlineCitationEl = document.getElementById('demo-inline-citation');
            const bibOutputEl = document.getElementById('demo-bib-output');

            const citationFormats = {
                apa: {
                    inText: '(Wijaya, 2026)',
                    bib: 'Wijaya, Hendra (2026). Pengembangan Naskah Akademik Otomatis. <em>Jurnal Sistem Informasi</em>, <em>12</em>, 45-56. https://doi.org/10.31294/jsi.v12i1.2026'
                },
                ieee: {
                    inText: '[1]',
                    bib: '[1] H. Wijaya, "Pengembangan Naskah Akademik Otomatis," <em>Jurnal Sistem Informasi</em>, vol. 12, no. 1, pp. 45-56, 2026, doi: 10.31294/jsi.v12i1.2026.'
                },
                harvard: {
                    inText: '(Wijaya 2026)',
                    bib: 'Wijaya, H. (2026) \'Pengembangan Naskah Akademik Otomatis\', <em>Jurnal Sistem Informasi</em>, 12(1), pp. 45-56. Available at: https://doi.org/10.31294/jsi.v12i1.2026.'
                }
            };

            citationButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const style = btn.getAttribute('data-style');
                    if (!citationFormats[style]) return;

                    // Update Active Button Style
                    citationButtons.forEach(b => {
                        b.classList.remove('bg-blue-600', 'text-white', 'font-bold');
                        b.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300', 'border');
                    });
                    btn.classList.add('bg-blue-600', 'text-white', 'font-bold');
                    btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300', 'border');

                    // Highlight change animation
                    if (inlineCitationEl) {
                        inlineCitationEl.style.transform = 'scale(1.1)';
                        inlineCitationEl.textContent = citationFormats[style].inText;
                        setTimeout(() => { inlineCitationEl.style.transform = 'scale(1)'; }, 200);
                    }

                    if (bibOutputEl) {
                        bibOutputEl.innerHTML = citationFormats[style].bib;
                        bibOutputEl.classList.add('ring-2', 'ring-blue-500');
                        setTimeout(() => { bibOutputEl.classList.remove('ring-2', 'ring-blue-500'); }, 400);
                    }
                });
            });


            // -------------------------------------------------------------
            // 3. Track Changes Live Toggle Simulation
            // -------------------------------------------------------------
            const btnToggleTrack = document.getElementById('btn-toggle-demo-track');
            const demoTrackStatus = document.getElementById('demo-track-status');
            const trackAddSpan = document.querySelector('.track-add');
            const trackDelSpan = document.querySelector('.track-del');
            let isTrackActive = true;

            if (btnToggleTrack) {
                btnToggleTrack.addEventListener('click', () => {
                    isTrackActive = !isTrackActive;
                    if (demoTrackStatus) {
                        demoTrackStatus.textContent = isTrackActive ? 'AKTIF' : 'NONAKTIF';
                        demoTrackStatus.className = isTrackActive ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 font-bold';
                    }

                    if (trackAddSpan && trackDelSpan) {
                        if (isTrackActive) {
                            trackAddSpan.className = 'track-add bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 underline underline-offset-2 decoration-emerald-500 font-medium px-1 rounded transition-all duration-300';
                            trackDelSpan.style.display = 'inline';
                        } else {
                            // Clean / Accepted text view
                            trackAddSpan.className = 'track-add text-slate-700 dark:text-slate-300 transition-all duration-300';
                            trackDelSpan.style.display = 'none';
                        }
                    }
                });
            }


            // -------------------------------------------------------------
            // 4. Preset Document Switcher in Simulator
            // -------------------------------------------------------------
            const btnPresetJournal = document.getElementById('btn-preset-journal');
            const btnPresetThesis = document.getElementById('btn-preset-thesis');
            const demoDocTitle = document.getElementById('demo-doc-title');
            const demoDocAbstract = document.getElementById('demo-doc-abstract');
            const demoWordCount = document.getElementById('demo-word-count');

            if (btnPresetJournal && btnPresetThesis) {
                btnPresetJournal.addEventListener('click', () => {
                    btnPresetJournal.className = 'px-3 py-1 rounded-md bg-white dark:bg-blue-600 text-blue-700 dark:text-white shadow-xs font-semibold';
                    btnPresetThesis.className = 'px-3 py-1 rounded-md text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-white';
                    if (demoDocTitle) demoDocTitle.textContent = 'Rancang Bangun Otomasi Penjamin Mutu Limbah Tekstil Berbasis Sensor Spektral dan IoT';
                    if (demoWordCount) demoWordCount.textContent = '184 Kata';
                });

                btnPresetThesis.addEventListener('click', () => {
                    btnPresetThesis.className = 'px-3 py-1 rounded-md bg-white dark:bg-blue-600 text-blue-700 dark:text-white shadow-xs font-semibold';
                    btnPresetJournal.className = 'px-3 py-1 rounded-md text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-white';
                    if (demoDocTitle) demoDocTitle.textContent = 'Analisis Komparatif Performa Model Bahasa Terdistribusi pada Naskah Riset Lintas Disiplin';
                    if (demoWordCount) demoWordCount.textContent = '248 Kata';
                });
            }


            // -------------------------------------------------------------
            // 5. Interactive Publication Readiness Calculator
            // -------------------------------------------------------------
            const readinessCheckboxes = document.querySelectorAll('.readiness-check');
            const scoreCircle = document.getElementById('score-circle');
            const percentageText = document.getElementById('readiness-percentage');
            const statusBadge = document.getElementById('readiness-status-badge');
            const adviceText = document.getElementById('readiness-advice');
            const targetSelect = document.getElementById('target-type');

            function calculateReadiness() {
                let currentScore = 0;
                readinessCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        currentScore += parseInt(cb.getAttribute('data-weight') || '0', 10);
                    }
                });

                // Cap between 0 and 100
                currentScore = Math.min(100, Math.max(0, currentScore));

                // Update Progress Ring (Circumference ~ 251.2 for r=40)
                if (scoreCircle) {
                    const circumference = 251.2;
                    const offset = circumference - (currentScore / 100) * circumference;
                    scoreCircle.style.strokeDashoffset = offset;
                }

                if (percentageText) {
                    percentageText.textContent = `${currentScore}%`;
                }

                if (statusBadge && adviceText) {
                    if (currentScore >= 90) {
                        statusBadge.textContent = 'Naskah Sangat Matang — Siap Submit ke Redaksi';
                        statusBadge.className = 'px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 mb-3';
                        adviceText.textContent = 'Luar biasa! Seluruh elemen metodologi, sitasi, dan struktur naskah telah lengkap sesuai standar publikasi.';
                    } else if (currentScore >= 60) {
                        statusBadge.textContent = 'Siap Review Internal Pembimbing';
                        statusBadge.className = 'px-4 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 mb-3';
                        adviceText.textContent = 'Naskah sudah memiliki pondasi kuat. Tambahkan sitasi terkini dari jurnal 5 tahun terakhir untuk memperkuat kebaruan hasil penelitian Anda.';
                    } else {
                        statusBadge.textContent = 'Tahap Draf Awal — Perlu Penyempurnaan';
                        statusBadge.className = 'px-4 py-1.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 mb-3';
                        adviceText.textContent = 'Fokus pada penyusunan latar belakang masalah, perumusan gap penelitian, dan kerangka metodologi sebelum memulai penulisan pembahasan.';
                    }
                }
            }

            readinessCheckboxes.forEach(cb => cb.addEventListener('change', calculateReadiness));
            if (targetSelect) targetSelect.addEventListener('change', calculateReadiness);
            calculateReadiness();


            // -------------------------------------------------------------
            // 6. Interactive FAQ Accordion
            // -------------------------------------------------------------
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const toggle = item.querySelector('.faq-toggle');
                const content = item.querySelector('.faq-content');
                const icon = item.querySelector('.faq-icon');

                if (toggle && content && icon) {
                    toggle.addEventListener('click', () => {
                        const isOpen = !content.classList.contains('hidden');

                        // Close all others
                        faqItems.forEach(other => {
                            const otherContent = other.querySelector('.faq-content');
                            const otherIcon = other.querySelector('.faq-icon');
                            if (otherContent) otherContent.classList.add('hidden');
                            if (otherIcon) otherIcon.classList.remove('rotate-180');
                        });

                        // Toggle current
                        if (!isOpen) {
                            content.classList.remove('hidden');
                            icon.classList.add('rotate-180');
                        }
                    });
                }
            });

        });
    </script>
</body>
</html>
