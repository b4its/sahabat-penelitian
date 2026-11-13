@extends('layouts.panel')

@section('title', 'AI Studio')
@section('breadcrumb', 'AI Studio')

@section('content')
<div class="space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. HEADER SECTION & TABS NAVIGATION                                      -->
    <!-- ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-blue-100 dark:border-blue-900/40">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-500/30">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    AI Studio
                </h1>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                    LLM Hub &amp; Chat
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pusat integrasi koneksi multi-provider LLM (OpenAI, Gemini, Claude, Groq, Ollama) dan workspace interaktif chat model.
            </p>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center p-1 bg-slate-200/70 dark:bg-slate-800/80 rounded-2xl shrink-0 self-start md:self-auto">
            <button
                type="button"
                onclick="switchTab('connections')"
                id="tab-btn-connections"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 {{ $activeTab === 'connections' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Koneksi Provider</span>
                <span class="ml-1 text-[11px] px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                    {{ $providers->count() }}
                </span>
            </button>

            <button
                type="button"
                onclick="switchTab('chat')"
                id="tab-btn-chat"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 {{ $activeTab === 'chat' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <span>Chat AI Studio</span>
                <span class="ml-1 text-[11px] px-1.5 py-0.2 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                    Aktif
                </span>
            </button>
        </div>
    </div>

    <!-- Alert / Flash Message -->
    @if(session('status'))
        <div id="flash-banner" class="flex items-center justify-between p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs sm:text-sm animate-in fade-in">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('flash-banner').remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- TAB 1: KONEKSI PROVIDER (CONNECTION)                                     -->
    <!-- ========================================================================= -->
    <div id="tab-content-connections" class="{{ $activeTab === 'connections' ? 'block' : 'hidden' }} space-y-6">

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Provider</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-slate-900 dark:text-white" id="stat-total-providers">{{ $providers->count() }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400">Terkonfigurasi</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Provider Aktif</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400" id="stat-active-providers">{{ $activeProviders->count() }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400">Siap Pakai</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Model Terhubung</span>
                <div class="mt-2 flex items-baseline justify-between">
                    @php
                        $totalModels = $activeProviders->sum(fn($p) => count($p->models ?? []));
                    @endphp
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400" id="stat-total-models">{{ $totalModels }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">LLM Ready</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Status Gateway</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-extrabold text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Optimal
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400">Auto Route</span>
                </div>
            </div>
        </div>

        <!-- Connection Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl shadow-xs overflow-hidden">
            <!-- Table Header Bar -->
            <div class="p-4 sm:p-5 border-b border-blue-100 dark:border-blue-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Daftar Provider AI Terhubung</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                            {{ $providers->count() }} Provider
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Kelola kunci otentikasi API, endpoint server, dan daftar model yang dapat dipilih di ruang chat.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        onclick="openAddProviderModal()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 cursor-pointer"
                        id="btn-add-provider"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Provider Baru</span>
                    </button>
                </div>
            </div>

            <!-- Table Responsive Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm divide-y divide-blue-50 dark:divide-blue-900/30">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Provider &amp; Nama</th>
                            <th scope="col" class="px-4 py-3.5">Endpoint / Base URL</th>
                            <th scope="col" class="px-4 py-3.5">Kunci API</th>
                            <th scope="col" class="px-4 py-3.5">Model Terdaftar</th>
                            <th scope="col" class="px-4 py-3.5">Status</th>
                            <th scope="col" class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 dark:divide-blue-900/20 text-slate-700 dark:text-slate-300" id="providers-table-body">
                        @forelse($providers as $prov)
                            <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-950/20 transition-colors" id="provider-row-{{ $prov->uuid }}">
                                <!-- Provider & Name -->
                                <td class="px-4 py-4 min-w-[200px]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 {{ $prov->badge_color }}">
                                            @if($prov->provider === 'openai')
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073z"/>
                                                </svg>
                                            @elseif($prov->provider === 'gemini')
                                                <svg class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 24A12 12 0 0 1 0 12 12 12 0 0 1 12 0a12 12 0 0 1 12 12 12 12 0 0 1-12 12zm0-3a9 9 0 0 0 9-9 9 9 0 0 0-9-9 9 9 0 0 0-9 9 9 9 0 0 0 9 9z"/>
                                                </svg>
                                            @elseif($prov->provider === 'anthropic')
                                                <span class="font-extrabold text-amber-600">A\</span>
                                            @elseif($prov->provider === 'groq')
                                                <span class="font-extrabold text-orange-600">GQ</span>
                                            @elseif($prov->provider === 'ollama')
                                                <span class="font-extrabold text-slate-700 dark:text-slate-200">🦙</span>
                                            @else
                                                <svg class="w-5 h-5 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 dark:text-white truncate provider-name-label">
                                                {{ $prov->name }}
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md {{ $prov->badge_color }}">
                                                    {{ $prov->provider_label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Base URL -->
                                <td class="px-4 py-4 min-w-[180px]">
                                    <span class="text-xs font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md block truncate max-w-[220px]" title="{{ $prov->base_url }}">
                                        {{ $prov->base_url ?: '(Default Driver Endpoint)' }}
                                    </span>
                                </td>

                                <!-- Masked API Key -->
                                <td class="px-4 py-4 min-w-[150px]">
                                    <span class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                        {{ $prov->masked_api_key }}
                                    </span>
                                </td>

                                <!-- Models List -->
                                <td class="px-4 py-4 min-w-[220px]">
                                    <div class="flex flex-wrap gap-1 max-w-[280px]">
                                        @foreach($prov->models ?? [] as $m)
                                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-lg border {{ $m === $prov->default_model ? 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800 font-bold' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                                {{ $m }}
                                                @if($m === $prov->default_model)
                                                    <span class="text-[9px] text-blue-500 ml-0.5">★</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Status & Test -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onclick="toggleProviderStatus('{{ $prov->uuid }}')"
                                                class="provider-toggle-btn inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-colors cursor-pointer {{ $prov->is_active ? 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}"
                                                title="Klik untuk mengubah status aktif"
                                            >
                                                <span class="w-2 h-2 rounded-full {{ $prov->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                                <span class="provider-status-text">{{ $prov->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                                            </button>
                                        </div>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 test-timestamp" id="tested-at-{{ $prov->uuid }}">
                                            @if($prov->last_tested_at)
                                                Diuji: {{ $prov->last_tested_at->diffForHumans() }}
                                            @else
                                                Belum pernah diuji
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Test Connection Button -->
                                        <button
                                            type="button"
                                            onclick="testProviderConnection('{{ $prov->uuid }}')"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors cursor-pointer btn-test-conn"
                                            title="Uji Koneksi Provider"
                                            id="btn-test-{{ $prov->uuid }}"
                                        >
                                            <svg class="w-4 h-4 test-icon-idle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <svg class="w-4 h-4 test-icon-spinner animate-spin hidden text-blue-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            onclick="openEditProviderModal({{ json_encode($prov) }})"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Edit Pengaturan Provider"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            onclick="confirmDeleteProvider('{{ $prov->uuid }}', '{{ addslashes($prov->name) }}')"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Hapus Provider"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950 flex items-center justify-center text-blue-600 mb-3">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-slate-700 dark:text-slate-200">Belum ada AI Provider yang terhubung</p>
                                        <p class="text-xs text-slate-500 mt-1">Tambahkan provider OpenAI, Gemini, Claude, Groq, atau Ollama untuk mulai memanfaatkan fitur AI Studio.</p>
                                        <button
                                            type="button"
                                            onclick="openAddProviderModal()"
                                            class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                                        >
                                            Tambah Provider Pertama
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: CHAT AI STUDIO                                                    -->
    <!-- ========================================================================= -->
    <div id="tab-content-chat" class="{{ $activeTab === 'chat' ? 'block' : 'hidden' }} space-y-4">

        <!-- Top Configuration Bar: Provider & Model Selector -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Left: Provider & Model Dropdowns -->
            <div class="flex flex-wrap items-center gap-3">
                
                <!-- 1. AI Provider Selector -->
                <div class="flex flex-col min-w-[200px]">
                    <label for="chat-provider-select" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Pilih AI Provider:
                    </label>
                    <div class="relative">
                        <select
                            id="chat-provider-select"
                            onchange="onChatProviderChanged()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none cursor-pointer"
                        >
                            @forelse($activeProviders as $p)
                                <option value="{{ $p->uuid }}" {{ ($chatSession && $chatSession->ai_provider_id === $p->id) ? 'selected' : '' }} data-models="{{ json_encode($p->models ?? []) }}" data-default="{{ $p->default_model }}">
                                    {{ $p->name }} ({{ $p->provider_label }})
                                </option>
                            @empty
                                <option value="" disabled selected>Belum ada provider aktif</option>
                            @endforelse
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 2. Model Selector (Populates from chosen provider) -->
                <div class="flex flex-col min-w-[200px]">
                    <label for="chat-model-select" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Pilih Model Terhubung:
                    </label>
                    <div class="relative">
                        <select
                            id="chat-model-select"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none cursor-pointer"
                        >
                            <!-- Populated dynamically via JS -->
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 3. System Prompt / Persona Preset -->
                <div class="flex flex-col min-w-[220px]">
                    <label for="chat-system-preset" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Peran Persona AI:
                    </label>
                    <div class="relative">
                        <select
                            id="chat-system-preset"
                            onchange="onSystemPresetChanged()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none cursor-pointer"
                        >
                            <option value="research">Asisten Peneliti &amp; Publikasi Akademik</option>
                            <option value="puebi">Editor Bahasa &amp; Korektor PUEBI Formal</option>
                            <option value="methodology">Konsultan Metodologi &amp; Statistika</option>
                            <option value="literature">Penelaah Literatur &amp; Sintesis Rujukan</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Quick Reset Button -->
            <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                <button
                    type="button"
                    onclick="resetChatSession()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
                    title="Mulai Sesi Obrolan Baru"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Sesi Baru</span>
                </button>
            </div>
        </div>

        <!-- Chat Main Interactive Area -->
        <div class="flex flex-col h-[650px] bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl shadow-xs overflow-hidden">
            
            <!-- Message Stream Scrollable Box -->
            <div id="chat-messages-container" class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4">
                
                @if(!$chatSession || $chatSession->messages->isEmpty())
                    <!-- Empty State Welcome Hero -->
                    <div id="chat-empty-state" class="h-full flex flex-col items-center justify-center text-center p-6 max-w-xl mx-auto my-auto">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-sky-400 text-white flex items-center justify-center shadow-lg shadow-blue-500/30 mb-4">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            Selamat Datang di AI Studio Chat
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md">
                            Pilih provider dan model AI di atas, lalu mulai ajukan pertanyaan akademik, telaah naskah, atau formulasi kerangka teori.
                        </p>

                        <!-- Suggestion Prompts Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-6 w-full text-left">
                            <button
                                type="button"
                                onclick="sendQuickPrompt('Bantu susun outline metodologi penelitian mixed-methods untuk studi kasus evaluasi implementasi sistem.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-blue-600 dark:text-blue-400 block group-hover:underline">📐 Metodologi Riset</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Rancangan mixed-methods studi kasus implementasi.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Bagaimana cara merumuskan abstrak artikel jurnal yang ringkas, tajam, dan memuat IMRaD secara seimbang?')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 block group-hover:underline">📝 Abstrak IMRaD</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Formulasi abstrak jurnal seimbang dan padat.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Beri contoh sitasi APA 7th edition dan IEEE untuk artikel jurnal dengan 3 penulis serta ada DOI.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-amber-600 dark:text-amber-400 block group-hover:underline">📚 Standar Sitasi</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Contoh komparasi format sitasi APA 7th vs IEEE.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Jelaskan apa saja kriteria novelty dan kontribusi empiris dalam penulisan tesis atau disertasi.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-purple-600 dark:text-purple-400 block group-hover:underline">💡 Kebaruan / Novelty</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Kriteria novelty &amp; kontribusi penelitian ilmiah.</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Render Existing Messages -->
                    @foreach($chatSession->messages as $msg)
                        <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }} message-item">
                            @if($msg->role === 'user')
                                <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl rounded-br-xs bg-blue-600 text-white p-3.5 shadow-xs text-xs sm:text-sm">
                                    <div class="whitespace-pre-wrap leading-relaxed">{{ $msg->content }}</div>
                                    <div class="text-[10px] text-blue-200 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</div>
                                </div>
                            @else
                                <div class="max-w-[90%] sm:max-w-[80%] rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-4 shadow-xs text-xs sm:text-sm text-slate-800 dark:text-slate-100">
                                    <div class="flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-200 dark:border-slate-700 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            {{ $msg->model_used ?: 'Model AI' }}
                                        </span>
                                        <span>•</span>
                                        <span class="truncate">{{ $msg->provider_name ?: 'AI Provider' }}</span>
                                    </div>
                                    <div class="prose prose-xs dark:prose-invert max-w-none leading-relaxed whitespace-pre-wrap">{{ $msg->content }}</div>
                                    <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px] text-slate-400">
                                        <span>{{ $msg->created_at->format('H:i') }}</span>
                                        <button type="button" onclick="copyMessageContent(this)" class="hover:text-blue-500 flex items-center gap-1 cursor-pointer" title="Salin Jawaban">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span>Salin</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif

                <!-- Typing shimmer indicator (hidden by default) -->
                <div id="chat-typing-indicator" class="hidden flex justify-start">
                    <div class="rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-3.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="chat-generating-text">Sedang memproses respon melalui model...</span>
                    </div>
                </div>

            </div>

            <!-- Chat Bottom Input Bar -->
            <div class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-900 border-t border-blue-100 dark:border-blue-900/40">
                <form id="chat-form" onsubmit="handleSendChat(event)" class="relative flex items-end gap-2">
                    <input type="hidden" id="chat-session-uuid" value="{{ $chatSession?->uuid }}">
                    
                    <div class="flex-1 relative">
                        <textarea
                            id="chat-user-input"
                            rows="2"
                            placeholder="Tulis pesan atau pertanyaan untuk model yang Anda pilih... (Tekan Enter untuk kirim, Shift+Enter untuk baris baru)"
                            class="w-full px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800/80 bg-white dark:bg-slate-800 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none transition"
                            onkeydown="handleInputKeydown(event)"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        id="btn-chat-send"
                        class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-sm shadow-blue-500/25 transition cursor-pointer shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span>Kirim</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

                <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 px-1">
                    <span id="chat-active-indicator" class="flex items-center gap-1.5 truncate">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span id="chat-active-model-summary">Pilih model untuk mulai</span>
                    </span>
                    <span class="hidden sm:inline">AI Studio v2.4 • Sahabat Penelitian</span>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: TAMBAH / EDIT AI PROVIDER                                         -->
<!-- ========================================================================= -->
<div id="modal-provider" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-provider-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeProviderModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/60 text-left shadow-2xl transition-all sm:w-full sm:max-w-xl">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-blue-100 dark:border-blue-900/40 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modal-provider-title">
                            Tambah AI Provider Baru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Hubungkan penyedia LLM Anda untuk dipakai di sistem.</p>
                    </div>
                </div>
                <button type="button" onclick="closeProviderModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="form-provider" onsubmit="handleSaveProvider(event)" class="p-5 space-y-4">
                <input type="hidden" id="provider-edit-uuid" name="uuid" value="">

                <!-- 1. Provider Driver Type -->
                <div>
                    <label for="input-provider-driver" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Penyedia / Driver AI <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="input-provider-driver"
                        name="provider"
                        onchange="handleDriverPresetChange()"
                        class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
                        required
                    >
                        <option value="openai">OpenAI (GPT-4o, GPT-4o-mini)</option>
                        <option value="gemini">Google Gemini (Gemini 2.0 Flash, Gemini 1.5 Pro)</option>
                        <option value="anthropic">Anthropic Claude (Claude 3.5 Sonnet, Haiku)</option>
                        <option value="groq">Groq Cloud (Llama 3.3, Mixtral)</option>
                        <option value="openrouter">OpenRouter Gateway</option>
                        <option value="ollama">Ollama (Lokal / Mandiri)</option>
                        <option value="custom">Custom OpenAI-Compatible API</option>
                    </select>
                </div>

                <!-- 2. Display Name -->
                <div>
                    <label for="input-provider-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Label Provider <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="input-provider-name"
                        name="name"
                        placeholder="Contoh: OpenAI GPT-4o Riset"
                        class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required
                    >
                </div>

                <!-- 3. Base URL / Endpoint -->
                <div>
                    <label for="input-provider-url" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Base URL / Server Endpoint
                    </label>
                    <input
                        type="text"
                        id="input-provider-url"
                        name="base_url"
                        placeholder="https://api.openai.com/v1"
                        class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                </div>

                <!-- 4. API Key -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="input-provider-key" class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Kunci API (API Key)
                        </label>
                        <span class="text-[10px] text-slate-400" id="key-hint-text">Tersimpan secara terenkripsi</span>
                    </div>
                    <div class="relative">
                        <input
                            type="password"
                            id="input-provider-key"
                            name="api_key"
                            placeholder="sk-proj-..."
                            class="w-full pl-3 pr-10 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >
                        <button
                            type="button"
                            onclick="toggleApiKeyVisibility()"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <svg id="eye-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- 5. Configured Models List (comma separated or selectable) -->
                <div>
                    <label for="input-provider-models" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Daftar Model yang Dihubungkan <span class="text-rose-500">*</span>
                        <span class="text-[11px] font-normal text-slate-400">(Pisahkan dengan koma)</span>
                    </label>
                    <input
                        type="text"
                        id="input-provider-models"
                        name="models"
                        placeholder="gpt-4o, gpt-4o-mini"
                        class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required
                    >
                    <!-- Quick preset pills -->
                    <div class="mt-2 flex flex-wrap gap-1" id="preset-model-pills">
                        <!-- Populated by JS based on driver -->
                    </div>
                </div>

                <!-- 6. Default Model -->
                <div>
                    <label for="input-provider-default-model" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Model Utama (Default)
                    </label>
                    <input
                        type="text"
                        id="input-provider-default-model"
                        name="default_model"
                        placeholder="gpt-4o"
                        class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                </div>

                <!-- 7. Active Status Toggle & Description -->
                <div class="flex items-center gap-2 pt-1">
                    <input
                        type="checkbox"
                        id="input-provider-active"
                        name="is_active"
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700"
                        checked
                    >
                    <label for="input-provider-active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Aktifkan provider ini untuk digunakan langsung di Chat Studio
                    </label>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-blue-100 dark:border-blue-900/40 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        onclick="closeProviderModal()"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        id="btn-save-provider-submit"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition cursor-pointer"
                    >
                        Simpan Provider
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: HAPUS PROVIDER CONFIRMATION                                       -->
<!-- ========================================================================= -->
<div id="modal-delete-provider" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDeleteModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/50 p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-md">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950 flex items-center justify-center text-rose-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Koneksi Provider?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" id="delete-provider-name-label">Provider akan dihapus dari daftar.</p>
                </div>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                Tindakan ini akan menghapus konfigurasi provider dan model terkait. Sesi obrolan yang tersimpan tidak akan lagi dapat mengirim pesan melalui provider ini.
            </p>
            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    id="btn-confirm-delete-action"
                    onclick="executeDeleteProvider()"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 shadow-sm shadow-rose-500/25 transition cursor-pointer"
                >
                    Hapus Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC & REACTIVITY                                            -->
<!-- ========================================================================= -->
<script>
    // Presets data injected from server
    const PRESETS = @json($presets);
    let activeDeleteUuid = null;
    let csrfToken = '{{ csrf_token() }}';

    // Switch between tabs (connections vs chat)
    function switchTab(tab) {
        const connTab = document.getElementById('tab-content-connections');
        const chatTab = document.getElementById('tab-content-chat');
        const btnConn = document.getElementById('tab-btn-connections');
        const btnChat = document.getElementById('tab-btn-chat');

        if (tab === 'chat') {
            connTab.classList.add('hidden');
            chatTab.classList.remove('hidden');

            btnChat.className = "flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs";
            btnConn.className = "flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white";

            // Initialize models dropdown on chat
            onChatProviderChanged();
            scrollChatToBottom();
            const url = new URL(window.location);
            url.searchParams.set('tab', 'chat');
            window.history.replaceState({}, '', url);
        } else {
            chatTab.classList.add('hidden');
            connTab.classList.remove('hidden');

            btnConn.className = "flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs";
            btnChat.className = "flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white";

            const url = new URL(window.location);
            url.searchParams.set('tab', 'connections');
            window.history.replaceState({}, '', url);
        }
    }

    // Modal Add Provider
    function openAddProviderModal() {
        document.getElementById('modal-provider-title').textContent = 'Tambah AI Provider Baru';
        document.getElementById('provider-edit-uuid').value = '';
        document.getElementById('form-provider').reset();
        document.getElementById('input-provider-active').checked = true;
        handleDriverPresetChange();
        document.getElementById('modal-provider').classList.remove('hidden');
    }

    // Modal Edit Provider
    function openEditProviderModal(provider) {
        document.getElementById('modal-provider-title').textContent = 'Edit AI Provider: ' + provider.name;
        document.getElementById('provider-edit-uuid').value = provider.uuid;
        document.getElementById('input-provider-driver').value = provider.provider;
        document.getElementById('input-provider-name').value = provider.name;
        document.getElementById('input-provider-url').value = provider.base_url || '';
        document.getElementById('input-provider-key').value = '';
        document.getElementById('input-provider-key').placeholder = provider.masked_api_key || 'Kunci tersimpan (kosongkan jika tidak diganti)';
        document.getElementById('input-provider-models').value = Array.isArray(provider.models) ? provider.models.join(', ') : (provider.models || '');
        document.getElementById('input-provider-default-model').value = provider.default_model || '';
        document.getElementById('input-provider-active').checked = Boolean(provider.is_active);

        renderPresetPills(provider.provider);
        document.getElementById('modal-provider').classList.remove('hidden');
    }

    function closeProviderModal() {
        document.getElementById('modal-provider').classList.add('hidden');
    }

    // Handle Driver Type Change in Form
    function handleDriverPresetChange() {
        const driver = document.getElementById('input-provider-driver').value;
        const preset = PRESETS[driver];
        const editUuid = document.getElementById('provider-edit-uuid').value;

        if (preset && !editUuid) {
            document.getElementById('input-provider-name').value = preset.label;
            document.getElementById('input-provider-url').value = preset.default_base_url;
            document.getElementById('input-provider-key').placeholder = preset.placeholder_key;
            document.getElementById('input-provider-models').value = preset.default_models.join(', ');
            document.getElementById('input-provider-default-model').value = preset.default_model;
        }

        renderPresetPills(driver);
    }

    function renderPresetPills(driver) {
        const container = document.getElementById('preset-model-pills');
        container.innerHTML = '';
        const preset = PRESETS[driver];
        if (!preset || !preset.default_models) return;

        const label = document.createElement('span');
        label.className = 'text-[10px] text-slate-400 mr-1 self-center';
        label.textContent = 'Model Rekomendasi:';
        container.appendChild(label);

        preset.default_models.forEach(model => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-[10px] px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900 cursor-pointer';
            btn.textContent = '+ ' + model;
            btn.onclick = () => addModelToInput(model);
            container.appendChild(btn);
        });
    }

    function addModelToInput(model) {
        const input = document.getElementById('input-provider-models');
        let current = input.value.split(',').map(s => s.trim()).filter(Boolean);
        if (!current.includes(model)) {
            current.push(model);
            input.value = current.join(', ');
        }
        if (!document.getElementById('input-provider-default-model').value) {
            document.getElementById('input-provider-default-model').value = model;
        }
    }

    function toggleApiKeyVisibility() {
        const input = document.getElementById('input-provider-key');
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    // Save Provider (Create or Update)
    async function handleSaveProvider(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('btn-save-provider-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';

        const form = document.getElementById('form-provider');
        const formData = new FormData(form);
        const editUuid = document.getElementById('provider-edit-uuid').value;

        const url = editUuid 
            ? `/panel/ai-studio/providers/${editUuid}/update`
            : `/panel/ai-studio/providers`;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();
            if (res.ok && data.success) {
                closeProviderModal();
                window.location.reload();
            } else {
                alert(data.message || 'Gagal menyimpan provider.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan: ' + err.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Provider';
        }
    }

    // Toggle Provider Active Status
    async function toggleProviderStatus(uuid) {
        try {
            const res = await fetch(`/panel/ai-studio/providers/${uuid}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (err) {
            alert('Gagal mengubah status: ' + err.message);
        }
    }

    // Test Provider Connection
    async function testProviderConnection(uuid) {
        const btn = document.getElementById(`btn-test-${uuid}`);
        const iconIdle = btn.querySelector('.test-icon-idle');
        const iconSpinner = btn.querySelector('.test-icon-spinner');
        const timestampSpan = document.getElementById(`tested-at-${uuid}`);

        iconIdle.classList.add('hidden');
        iconSpinner.classList.remove('hidden');
        btn.disabled = true;

        try {
            const res = await fetch(`/panel/ai-studio/providers/${uuid}/test`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(`✓ ${data.message}`);
                if (timestampSpan) {
                    timestampSpan.textContent = `Diuji: baru saja (${data.latency_ms} ms)`;
                }
            } else {
                alert(`✕ ${data.message}`);
            }
        } catch (err) {
            alert('Gagal menguji koneksi: ' + err.message);
        } finally {
            iconIdle.classList.remove('hidden');
            iconSpinner.classList.add('hidden');
            btn.disabled = false;
        }
    }

    // Delete Provider Confirmation
    function confirmDeleteProvider(uuid, name) {
        activeDeleteUuid = uuid;
        document.getElementById('delete-provider-name-label').textContent = `Hapus "${name}"`;
        document.getElementById('modal-delete-provider').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('modal-delete-provider').classList.add('hidden');
        activeDeleteUuid = null;
    }

    async function executeDeleteProvider() {
        if (!activeDeleteUuid) return;
        const btn = document.getElementById('btn-confirm-delete-action');
        btn.disabled = true;
        btn.textContent = 'Menghapus...';

        try {
            const res = await fetch(`/panel/ai-studio/providers/${activeDeleteUuid}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                closeDeleteModal();
                window.location.reload();
            } else {
                alert(data.message || 'Gagal menghapus provider.');
            }
        } catch (err) {
            alert('Kesalahan jaringan: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Hapus Sekarang';
        }
    }

    // =========================================================================
    // CHAT CONTROLLER LOGIC
    // =========================================================================

    // On selected provider changed in Chat
    function onChatProviderChanged() {
        const providerSelect = document.getElementById('chat-provider-select');
        const modelSelect = document.getElementById('chat-model-select');
        modelSelect.innerHTML = '';

        if (!providerSelect || providerSelect.options.length === 0 || !providerSelect.value) {
            const opt = document.createElement('option');
            opt.textContent = 'Tidak ada model tersedia';
            modelSelect.appendChild(opt);
            updateModelSummary();
            return;
        }

        const selectedOption = providerSelect.options[providerSelect.selectedIndex];
        let models = [];
        try {
            models = JSON.parse(selectedOption.getAttribute('data-models') || '[]');
        } catch (e) {
            models = [];
        }

        const defaultModel = selectedOption.getAttribute('data-default');

        if (models.length === 0) {
            const opt = document.createElement('option');
            opt.value = defaultModel || 'standard-model';
            opt.textContent = defaultModel || 'standard-model';
            modelSelect.appendChild(opt);
        } else {
            models.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m + (m === defaultModel ? ' (Default)' : '');
                if (m === defaultModel) {
                    opt.selected = true;
                }
                modelSelect.appendChild(opt);
            });
        }

        updateModelSummary();
    }

    function updateModelSummary() {
        const providerSelect = document.getElementById('chat-provider-select');
        const modelSelect = document.getElementById('chat-model-select');
        const summary = document.getElementById('chat-active-model-summary');

        if (providerSelect && providerSelect.value && modelSelect && modelSelect.value) {
            const provName = providerSelect.options[providerSelect.selectedIndex]?.text?.split('(')[0]?.trim();
            summary.textContent = `Terhubung ke: ${provName} • Model: ${modelSelect.value}`;
        } else {
            summary.textContent = 'Pilih provider & model untuk mulai';
        }
    }

    document.getElementById('chat-model-select')?.addEventListener('change', updateModelSummary);

    function onSystemPresetChanged() {
        // Can adjust system persona behavior if needed
    }

    function handleInputKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').requestSubmit();
        }
    }

    function sendQuickPrompt(promptText) {
        document.getElementById('chat-user-input').value = promptText;
        document.getElementById('chat-form').requestSubmit();
    }

    // Send Chat Message
    async function handleSendChat(e) {
        e.preventDefault();
        const input = document.getElementById('chat-user-input');
        const message = input.value.trim();
        if (!message) return;

        const providerSelect = document.getElementById('chat-provider-select');
        const modelSelect = document.getElementById('chat-model-select');
        const sessionUuidInput = document.getElementById('chat-session-uuid');
        const systemPreset = document.getElementById('chat-system-preset');

        if (!providerSelect.value) {
            alert('Silakan pilih AI Provider terlebih dahulu di tab Koneksi.');
            return;
        }

        const providerUuid = providerSelect.value;
        const model = modelSelect.value;
        const sessionUuid = sessionUuidInput.value;

        // Append User Message to UI immediately
        appendMessageToUi({
            role: 'user',
            content: message,
            created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });

        // Clear input and remove empty state if visible
        input.value = '';
        const emptyState = document.getElementById('chat-empty-state');
        if (emptyState) emptyState.remove();

        // Show typing indicator
        const typingIndicator = document.getElementById('chat-typing-indicator');
        const genText = document.getElementById('chat-generating-text');
        genText.textContent = `Sedang memproses respon melalui model ${model}...`;
        typingIndicator.classList.remove('hidden');
        scrollChatToBottom();

        const sendBtn = document.getElementById('btn-chat-send');
        sendBtn.disabled = true;

        try {
            const res = await fetch('/panel/ai-studio/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    provider_uuid: providerUuid,
                    model: model,
                    message: message,
                    session_uuid: sessionUuid,
                    system_prompt: getSystemPromptText(systemPreset.value)
                })
            });

            const data = await res.json();
            typingIndicator.classList.add('hidden');

            if (res.ok && data.success) {
                if (data.session_uuid) {
                    sessionUuidInput.value = data.session_uuid;
                }
                appendMessageToUi(data.assistant_message);
            } else {
                appendMessageToUi({
                    role: 'assistant',
                    content: '⚠️ Gagal mendapatkan balasan dari model: ' + (data.message || 'Terjadi kesalahan sistem.'),
                    provider_name: 'Error Handler',
                    model_used: model,
                    created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
            }
        } catch (err) {
            typingIndicator.classList.add('hidden');
            appendMessageToUi({
                role: 'assistant',
                content: '⚠️ Kesalahan koneksi jaringan saat menghubungi server AI: ' + err.message,
                provider_name: 'Network Error',
                model_used: model,
                created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });
        } finally {
            sendBtn.disabled = false;
            scrollChatToBottom();
        }
    }

    function getSystemPromptText(presetKey) {
        switch (presetKey) {
            case 'puebi':
                return 'Anda adalah penyunting bahasa Indonesia formal dan standar PUEBI. Berikan analisis tata bahasa yang akurat, pembetulan ejaan, dan perbaikan diksi akademik.';
            case 'methodology':
                return 'Anda adalah pakar metodologi penelitian empiris kuantitatif dan kualitatif. Berikan panduan desain riset, operasionalisasi variabel, dan uji validitas.';
            case 'literature':
                return 'Anda adalah penelaah literatur ilmiah internasional. Sintesiskan rujukan, kaji theoretical gaps, dan sarankan framing argumen yang kokoh.';
            case 'research':
            default:
                return 'Anda adalah asisten cerdas AI untuk periset dan penulis publikasi ilmiah bereputasi internasional.';
        }
    }

    function appendMessageToUi(msg) {
        const container = document.getElementById('chat-messages-container');
        const typingIndicator = document.getElementById('chat-typing-indicator');

        const wrapper = document.createElement('div');
        wrapper.className = `flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'} message-item animate-in fade-in`;

        if (msg.role === 'user') {
            wrapper.innerHTML = `
                <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl rounded-br-xs bg-blue-600 text-white p-3.5 shadow-xs text-xs sm:text-sm">
                    <div class="whitespace-pre-wrap leading-relaxed">${escapeHtml(msg.content)}</div>
                    <div class="text-[10px] text-blue-200 mt-1 text-right">${msg.created_at || 'Baru saja'}</div>
                </div>
            `;
        } else {
            wrapper.innerHTML = `
                <div class="max-w-[90%] sm:max-w-[80%] rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-4 shadow-xs text-xs sm:text-sm text-slate-800 dark:text-slate-100">
                    <div class="flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-200 dark:border-slate-700 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            ${escapeHtml(msg.model_used || 'Model AI')}
                        </span>
                        <span>•</span>
                        <span class="truncate">${escapeHtml(msg.provider_name || 'AI Provider')}</span>
                    </div>
                    <div class="prose prose-xs dark:prose-invert max-w-none leading-relaxed whitespace-pre-wrap">${formatMarkdown(msg.content)}</div>
                    <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px] text-slate-400">
                        <span>${msg.created_at || 'Baru saja'}</span>
                        <button type="button" onclick="copyMessageContent(this)" class="hover:text-blue-500 flex items-center gap-1 cursor-pointer" title="Salin Jawaban">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Salin</span>
                        </button>
                    </div>
                </div>
            `;
        }

        container.insertBefore(wrapper, typingIndicator);
        scrollChatToBottom();
    }

    async function resetChatSession() {
        if (!confirm('Mulai sesi obrolan baru? Pesan lama akan diarsipkan.')) return;
        const sessionUuid = document.getElementById('chat-session-uuid').value;

        try {
            const res = await fetch('/panel/ai-studio/chat/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ session_uuid: sessionUuid })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/panel/ai-studio?tab=chat';
            }
        } catch (err) {
            alert('Gagal mereset sesi: ' + err.message);
        }
    }

    function scrollChatToBottom() {
        const container = document.getElementById('chat-messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function copyMessageContent(btn) {
        const contentDiv = btn.closest('.message-item').querySelector('.prose');
        if (contentDiv) {
            navigator.clipboard.writeText(contentDiv.innerText || contentDiv.textContent);
            btn.innerHTML = `
                <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-emerald-500">Tersalin!</span>
            `;
            setTimeout(() => {
                btn.innerHTML = `
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Salin</span>
                `;
            }, 2000);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = escapeHtml(text);
        // Bold
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        return escaped;
    }

    // Auto-init on page load
    document.addEventListener('DOMContentLoaded', () => {
        onChatProviderChanged();
        scrollChatToBottom();
    });
</script>
@endsection
