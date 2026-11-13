<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Panel Riset Sahabat Penelitian - Dashboard analitik dan manajemen naskah publikasi ilmiah.">
    <title>@yield('title', 'Panel Riset') — Sahabat Penelitian</title>

    @fonts

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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
        /* Smooth transitions for sidebar expand/collapse */
        #panel-sidebar {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-text-label {
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        body.sidebar-collapsed #panel-sidebar {
            width: 5rem !important; /* 80px / w-20 */
        }
        body.sidebar-collapsed .sidebar-text-label,
        body.sidebar-collapsed .sidebar-group-title,
        body.sidebar-collapsed .sidebar-badge {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-nav-item {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-[#070d1e] text-slate-800 dark:text-slate-100 font-sans antialiased selection:bg-blue-600 selection:text-white transition-colors duration-200 flex overflow-hidden">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-mobile-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

    <!-- ========================================================================= -->
    <!-- SIDEBAR NAVIGATION (COLLAPSIBLE & RESPONSIVE)                             -->
    <!-- ========================================================================= -->
    <aside
        id="panel-sidebar"
        class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-900 border-r border-blue-100 dark:border-blue-900/50 flex flex-col justify-between shrink-0 transform -translate-x-full md:translate-x-0 shadow-xl md:shadow-none"
    >
        <!-- Sidebar Brand & Toggle -->
        <div>
            <div class="h-16 px-4 border-b border-blue-100 dark:border-blue-900/40 flex items-center justify-between">
                <a href="{{ route('panel.dashboard') }}" class="flex items-center gap-3 overflow-hidden focus:outline-none">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-sky-400 flex items-center justify-center text-white shrink-0 shadow-md shadow-blue-500/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div class="sidebar-text-label flex flex-col min-w-0">
                        <span class="text-base font-bold tracking-tight text-slate-900 dark:text-white truncate">
                            Sahabat <span class="text-blue-600 dark:text-blue-400">Penelitian</span>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 -mt-0.5">
                            Panel Riset &amp; Publikasi
                        </span>
                    </div>
                </a>

                <!-- Mobile Close Drawer Button -->
                <button
                    type="button"
                    id="btn-close-mobile-sidebar"
                    class="md:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                    aria-label="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-6 overflow-y-auto max-h-[calc(100vh-8.5rem)]">
                
                <!-- Group 1: Workspace Utama -->
                <div>
                    <div class="sidebar-group-title px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Menu Utama
                    </div>
                    <div class="space-y-1">
                        <!-- Dashboard -->
                        <a
                            href="{{ route('panel.dashboard') }}"
                            class="sidebar-nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-150 {{ request()->routeIs('panel.dashboard') ? 'bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}"
                            title="Dashboard Riset"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('panel.dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-text-label truncate">Dashboard Riset</span>
                        </a>

                        <!-- Documents -->
                        <a
                            href="{{ route('panel.documents') }}"
                            class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-150 {{ request()->routeIs('panel.documents') ? 'bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}"
                            title="Manajemen Dokumen"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('panel.documents') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="sidebar-text-label truncate">Dokumen &amp; Naskah</span>
                            </div>
                            <span class="sidebar-badge text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                                DOCX
                            </span>
                        </a>

                        <!-- Direct Editor Studio -->
                        <a
                            href="{{ url('/editor') }}"
                            class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors duration-150"
                            title="Buka Xishie Studio Editor"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="sidebar-text-label truncate">Xishie Studio</span>
                            </div>
                            <span class="sidebar-badge text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                Editor A4
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Group 2: AI Studio -->
                <div>
                    <div class="sidebar-group-title px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>AI Studio</span>
                        <span class="sidebar-badge text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">LLM Hub</span>
                    </div>
                    <div class="space-y-1">
                        <!-- AI Studio: Connection -->
                        <a
                            href="{{ route('panel.ai-studio.index', ['tab' => 'connections']) }}"
                            class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-150 {{ (request()->routeIs('panel.ai-studio*') && (request()->query('tab') === 'connections' || !request()->query('tab'))) ? 'bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}"
                            title="Koneksi Provider AI"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 shrink-0 {{ (request()->routeIs('panel.ai-studio*') && (request()->query('tab') === 'connections' || !request()->query('tab'))) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="sidebar-text-label truncate">Koneksi Provider</span>
                            </div>
                            <span class="sidebar-badge text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                                Connect
                            </span>
                        </a>

                        <!-- AI Studio: Chat -->
                        <a
                            href="{{ route('panel.ai-studio.index', ['tab' => 'chat']) }}"
                            class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-150 {{ (request()->routeIs('panel.ai-studio*') && request()->query('tab') === 'chat') ? 'bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}"
                            title="Chat Model AI Studio"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 shrink-0 {{ (request()->routeIs('panel.ai-studio*') && request()->query('tab') === 'chat') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <span class="sidebar-text-label truncate">Chat Studio AI</span>
                            </div>
                            <span class="sidebar-badge text-[10px] font-bold px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300">
                                Chat
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Group 3: Pintasan & Portal -->
                <div>
                    <div class="sidebar-group-title px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Pintasan Sistem
                    </div>
                    <div class="space-y-1">
                        <a
                            href="{{ url('/') }}"
                            class="sidebar-nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors"
                            title="Kembali ke Beranda"
                        >
                            <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="sidebar-text-label truncate">Portal Beranda</span>
                        </a>
                    </div>
                </div>

            </nav>
        </div>

        <!-- Sidebar Footer: Desktop Collapse Toggle Button -->
        <div class="p-3 border-t border-blue-100 dark:border-blue-900/40">
            <button
                type="button"
                id="btn-toggle-desktop-sidebar"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                title="Sembunyikan / Lebarkan Sidebar"
            >
                <svg id="sidebar-toggle-icon" class="w-4 h-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <span class="sidebar-text-label">Kecilkan Menu</span>
            </button>
        </div>
    </aside>

    <!-- ========================================================================= -->
    <!-- MAIN WORKSPACE CONTAINER                                                 -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        
        <!-- Top Application Bar (Header Panel) -->
        <header class="h-16 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-blue-100 dark:border-blue-900/40 px-4 sm:px-6 flex items-center justify-between shrink-0 z-20">
            
            <!-- Left Header: Mobile Hamburger & Breadcrumb -->
            <div class="flex items-center gap-3">
                <!-- Mobile Hamburger Toggle -->
                <button
                    type="button"
                    id="btn-open-mobile-sidebar"
                    class="md:hidden p-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none"
                    aria-label="Buka Menu Sidebar"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Breadcrumbs -->
                <nav class="flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 min-w-0">
                    <a href="{{ route('panel.dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors hidden sm:inline">Panel</a>
                    <span class="mx-2 text-slate-300 dark:text-slate-700 hidden sm:inline">/</span>
                    <span class="font-bold text-slate-900 dark:text-white truncate max-w-[120px] sm:max-w-none">@yield('breadcrumb', 'Dashboard')</span>
                </nav>
            </div>

            <!-- Right Header: Quick Actions, Theme Switch, & Profile -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                
                <!-- Quick Create Document Button -->
                <button
                    type="button"
                    onclick="openCreateDocumentModal()"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-1.5 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 shrink-0"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Naskah Baru</span>
                </button>

                <!-- Theme Switch Toggle Button -->
                <button
                    id="theme-toggle-btn"
                    type="button"
                    aria-label="Ganti Tema (Gelap / Terang)"
                    class="p-2 rounded-xl border border-blue-200 dark:border-blue-800/80 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 shadow-xs transition-colors focus:outline-none"
                    title="Ganti Tema (Gelap / Terang)"
                >
                    <!-- Sun Icon (Shown in Dark Mode) -->
                    <svg id="theme-icon-sun" class="w-4 h-4 hidden dark:block text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (Shown in Light Mode) -->
                    <svg id="theme-icon-moon" class="w-4 h-4 block dark:hidden text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Academic User Profile Dropdown Button -->
                <div class="relative" id="user-profile-menu-container">
                    <button
                        type="button"
                        id="user-profile-menu-button"
                        aria-expanded="false"
                        aria-haspopup="true"
                        class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-slate-200 dark:border-slate-800 hover:opacity-95 transition group focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-xl py-1 px-1.5 cursor-pointer"
                        title="Menu Profil Peneliti"
                    >
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-700 to-sky-400 text-white font-bold flex items-center justify-center text-xs shadow-xs shrink-0">
                            {{ strtoupper(substr(session('panel_user_name', 'Dr. Peneliti'), 0, 2)) }}
                        </div>
                        <div class="hidden sm:flex flex-col text-left">
                            <span class="text-xs font-bold text-slate-900 dark:text-white leading-tight">
                                {{ session('panel_user_name', 'Dr. Peneliti') }}
                            </span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                {{ session('panel_user_role', 'Penulis Utama') }}
                            </span>
                        </div>
                        <svg id="user-menu-chevron" class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        id="user-profile-dropdown"
                        class="hidden absolute right-0 mt-2 w-64 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/60 shadow-xl py-2 z-50 transition-all duration-150 animate-in fade-in"
                        role="menu"
                        aria-orientation="vertical"
                    >
                        <!-- User Identity Summary -->
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                {{ session('panel_user_name', 'Dr. Peneliti') }}
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                {{ session('panel_user_email', 'dr.peneliti@sahabatpenelitian.id') }}
                            </p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                                    {{ session('panel_user_role', 'Penulis Utama') }}
                                </span>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-1">
                            <!-- Edit Profile Item -->
                            <button
                                type="button"
                                id="btn-open-edit-profile"
                                onclick="openEditProfileModal()"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors text-left cursor-pointer"
                                role="menuitem"
                            >
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit Profil</span>
                            </button>
                        </div>

                        <!-- Logout Item -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-1">
                            <form action="{{ route('panel.logout') }}" method="POST">
                                @csrf
                                <button
                                    type="submit"
                                    id="btn-panel-logout"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left cursor-pointer"
                                    role="menuitem"
                                >
                                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        <!-- Main Scrollable Content Viewport -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Flash Notification Banner -->
                @if (session('success'))
                    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-200 text-sm font-semibold flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-emerald-100">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-200 text-sm font-semibold flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:hover:text-rose-100">&times;</button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-200 text-xs sm:text-sm font-semibold space-y-1 shadow-xs">
                        <div class="flex items-center gap-2 font-bold text-rose-900 dark:text-rose-100">
                            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Terjadi kesalahan pada input data:</span>
                        </div>
                        <ul class="list-disc list-inside pl-2 space-y-0.5 text-rose-700 dark:text-rose-300">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: BUAT NASKAH PENELITIAN BARU                                        -->
    <!-- ========================================================================= -->
    <div id="modal-create-document" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity overflow-y-auto">
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-blue-200 dark:border-blue-900/60 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto my-auto">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Buat Naskah Riset Baru</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pilih judul dan templat awal naskah Anda.</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateDocumentModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">&times;</button>
            </div>

            <form action="{{ route('panel.documents.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="doc-title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Judul Naskah / Artikel:
                    </label>
                    <input
                        type="text"
                        name="title"
                        id="doc-title"
                        required
                        placeholder="Contoh: Analisis Kinerja Jaringan Syaraf Tiruan pada Citra Satelit"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="doc-template" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Format Templat Naskah:
                    </label>
                    <select
                        name="template"
                        id="doc-template"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="journal">Artikel Jurnal Ilmiah (IMRAD SINTA / Scopus)</option>
                        <option value="thesis">Skripsi / Tesis / Disertasi Pascasarjana</option>
                        <option value="proposal">Proposal Penelitian Hibah Riset</option>
                        <option value="blank">Dokumen Kosong (Blank Sheet)</option>
                    </select>
                </div>

                <div>
                    <label for="doc-protection" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Mode Hak Akses Awal:
                    </label>
                    <select
                        name="restricted_mode"
                        id="doc-protection"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="none">Bebas Sunting (Standard Full Editor)</option>
                        <option value="read_only">Hanya Baca (Read-Only Mode)</option>
                        <option value="comments_only">Hanya Komentar (Reviewer Mode)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        onclick="closeCreateDocumentModal()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-md shadow-blue-500/25"
                    >
                        Buat &amp; Buka di Editor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: SUNTING PROFIL PENELITI (POPUP DITENGAH)                            -->
    <!-- ========================================================================= -->
    <div id="modal-edit-profile" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity overflow-y-auto">
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-blue-200 dark:border-blue-900/60 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto my-auto animate-in zoom-in-95">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-xs shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Sunting Profil Peneliti</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui identitas akademik dan afiliasi riset Anda.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditProfileModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg cursor-pointer" aria-label="Tutup Modal">&times;</button>
            </div>

            <form action="{{ route('panel.profile.update') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="profile-name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Nama Lengkap &amp; Gelar Akademik:
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="profile-name"
                        required
                        value="{{ session('panel_user_name', 'Dr. Peneliti') }}"
                        placeholder="Contoh: Dr. Peneliti, M.Kom., Ph.D."
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="profile-role" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Peran Peneliti:
                    </label>
                    <input
                        type="text"
                        name="role"
                        id="profile-role"
                        value="{{ session('panel_user_role', 'Penulis Utama') }}"
                        placeholder="Contoh: Penulis Utama / Peneliti Senior"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="profile-email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Alamat Email Riset:
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="profile-email"
                        value="{{ session('panel_user_email', 'dr.peneliti@sahabatpenelitian.id') }}"
                        placeholder="Contoh: dr.peneliti@sahabatpenelitian.id"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="profile-affiliation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Institusi / Lembaga Afiliasi:
                    </label>
                    <input
                        type="text"
                        name="affiliation"
                        id="profile-affiliation"
                        value="{{ session('panel_user_affiliation', 'Pusat Riset Komputasi & Bahasa') }}"
                        placeholder="Contoh: Universitas Riset Nasional"
                        class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        onclick="closeEditProfileModal()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-md shadow-blue-500/25 cursor-pointer"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel Sidebar & Theme Switch Client Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Theme Switcher Logic
            const themeBtn = document.getElementById('theme-toggle-btn');
            function setTheme(isDark) {
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('sahabat_theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('sahabat_theme', 'light');
                }
            }

            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    setTheme(!isDark);
                });
            }

            // Desktop Sidebar Collapse Toggle
            const desktopToggleBtn = document.getElementById('btn-toggle-desktop-sidebar');
            const toggleIcon = document.getElementById('sidebar-toggle-icon');

            // Restore collapsed state
            if (localStorage.getItem('sahabat_panel_sidebar_collapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
                if (toggleIcon) toggleIcon.style.transform = 'rotate(180deg)';
            }

            if (desktopToggleBtn) {
                desktopToggleBtn.addEventListener('click', () => {
                    const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sahabat_panel_sidebar_collapsed', isCollapsed ? 'true' : 'false');
                    if (toggleIcon) {
                        toggleIcon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });
            }

            // Mobile Sidebar Drawer Toggle
            const mobileOpenBtn = document.getElementById('btn-open-mobile-sidebar');
            const mobileCloseBtn = document.getElementById('btn-close-mobile-sidebar');
            const mobileBackdrop = document.getElementById('sidebar-mobile-backdrop');
            const panelSidebar = document.getElementById('panel-sidebar');

            function openMobileSidebar() {
                if (panelSidebar && mobileBackdrop) {
                    panelSidebar.classList.remove('-translate-x-full');
                    panelSidebar.classList.add('translate-x-0');
                    mobileBackdrop.classList.remove('hidden');
                }
            }

            function closeMobileSidebar() {
                if (panelSidebar && mobileBackdrop) {
                    panelSidebar.classList.add('-translate-x-full');
                    panelSidebar.classList.remove('translate-x-0');
                    mobileBackdrop.classList.add('hidden');
                }
            }

            if (mobileOpenBtn) mobileOpenBtn.addEventListener('click', openMobileSidebar);
            if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileSidebar);
            if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeMobileSidebar);

            // User Profile Dropdown Logic
            const profileBtn = document.getElementById('user-profile-menu-button');
            const profileDropdown = document.getElementById('user-profile-dropdown');
            const profileChevron = document.getElementById('user-menu-chevron');

            function toggleProfileDropdown() {
                if (!profileDropdown) return;
                const isHidden = profileDropdown.classList.contains('hidden');
                if (isHidden) {
                    profileDropdown.classList.remove('hidden');
                    if (profileChevron) profileChevron.style.transform = 'rotate(180deg)';
                    if (profileBtn) profileBtn.setAttribute('aria-expanded', 'true');
                } else {
                    closeProfileDropdown();
                }
            }

            function closeProfileDropdown() {
                if (!profileDropdown) return;
                profileDropdown.classList.add('hidden');
                if (profileChevron) profileChevron.style.transform = 'rotate(0deg)';
                if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
            }

            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleProfileDropdown();
                });
            }

            document.addEventListener('click', (e) => {
                const container = document.getElementById('user-profile-menu-container');
                if (container && !container.contains(e.target)) {
                    closeProfileDropdown();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeProfileDropdown();
                    closeEditProfileModal();
                    closeCreateDocumentModal();
                }
            });

            // Backdrop click closing for modals
            const editProfileModal = document.getElementById('modal-edit-profile');
            if (editProfileModal) {
                editProfileModal.addEventListener('click', (e) => {
                    if (e.target === editProfileModal) {
                        closeEditProfileModal();
                    }
                });
            }

            const createDocModal = document.getElementById('modal-create-document');
            if (createDocModal) {
                createDocModal.addEventListener('click', (e) => {
                    if (e.target === createDocModal) {
                        closeCreateDocumentModal();
                    }
                });
            }
        });

        // Global Modal Helpers
        function openCreateDocumentModal() {
            const modal = document.getElementById('modal-create-document');
            if (modal) modal.classList.remove('hidden');
        }

        function closeCreateDocumentModal() {
            const modal = document.getElementById('modal-create-document');
            if (modal) modal.classList.add('hidden');
        }

        function openEditProfileModal() {
            const dropdown = document.getElementById('user-profile-dropdown');
            if (dropdown) dropdown.classList.add('hidden');
            const chevron = document.getElementById('user-menu-chevron');
            if (chevron) chevron.style.transform = 'rotate(0deg)';
            const modal = document.getElementById('modal-edit-profile');
            if (modal) modal.classList.remove('hidden');
        }

        function closeEditProfileModal() {
            const modal = document.getElementById('modal-edit-profile');
            if (modal) modal.classList.add('hidden');
        }
    </script>
</body>
</html>
