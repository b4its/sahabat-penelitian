@extends('layouts.panel')

@section('title', 'AI Studio')
@section('breadcrumb', 'AI Studio')

@section('content')
<style>
    /* Selected Provider Row in Table - Light Mode */
    tr.provider-row-selected {
        background-color: #eff6ff !important;
        border-left: 4px solid #2563eb !important;
    }
    tr.provider-row-selected td {
        color: #0f172a !important;
    }

    /* Selected Provider Row in Table - Dark Mode */
    html.dark tr.provider-row-selected,
    .dark tr.provider-row-selected {
        background-color: rgba(30, 58, 138, 0.45) !important;
        border-left: 4px solid #38bdf8 !important;
    }
    html.dark tr.provider-row-selected:hover,
    .dark tr.provider-row-selected:hover {
        background-color: rgba(30, 58, 138, 0.6) !important;
    }
    html.dark tr.provider-row-selected td,
    .dark tr.provider-row-selected td {
        color: #f1f5f9 !important;
    }

    /* Bulk Actions Bar in Dark Mode */
    html.dark #bulk-actions-bar,
    .dark #bulk-actions-bar {
        background: linear-gradient(135deg, #0b152d 0%, #0f1c3f 50%, #111e48 100%) !important;
        border: 2px solid #2563eb !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 20px rgba(37, 99, 235, 0.25) !important;
        color: #ffffff !important;
    }
    html.dark #bulk-actions-bar span,
    .dark #bulk-actions-bar span {
        color: #ffffff !important;
    }
    html.dark #bulk-actions-bar #bulk-selected-count,
    .dark #bulk-actions-bar #bulk-selected-count {
        color: #38bdf8 !important;
    }
    html.dark #bulk-actions-bar #btn-clear-selection,
    .dark #bulk-actions-bar #btn-clear-selection {
        color: #93c5fd !important;
    }
    html.dark #bulk-actions-bar #btn-clear-selection:hover,
    .dark #bulk-actions-bar #btn-clear-selection:hover {
        color: #ffffff !important;
    }

    /* Markdown Styling for AI Assistant Responses */
    .ai-markdown-content {
        word-break: break-word;
    }
    .ai-markdown-content p {
        margin-bottom: 0.65rem;
    }
    .ai-markdown-content p:last-child {
        margin-bottom: 0;
    }
    .ai-markdown-content h1,
    .ai-markdown-content h2,
    .ai-markdown-content h3,
    .ai-markdown-content h4 {
        font-weight: 700;
        color: inherit;
        margin-top: 0.85rem;
        margin-bottom: 0.4rem;
    }
    .ai-markdown-content h1 { font-size: 1.15rem; }
    .ai-markdown-content h2 { font-size: 1.05rem; }
    .ai-markdown-content h3 { font-size: 0.95rem; }
    .ai-markdown-content ul {
        list-style-type: disc;
        margin-left: 1.25rem;
        margin-bottom: 0.65rem;
    }
    .ai-markdown-content ol {
        list-style-type: decimal;
        margin-left: 1.25rem;
        margin-bottom: 0.65rem;
    }
    .ai-markdown-content li {
        margin-bottom: 0.25rem;
    }
    .ai-markdown-content blockquote {
        border-left: 3px solid #3b82f6;
        padding-left: 0.75rem;
        margin: 0.65rem 0;
        font-style: italic;
        background: rgba(59, 130, 246, 0.08);
        border-radius: 0 0.5rem 0.5rem 0;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }
    .ai-markdown-content code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.825em;
        background: rgba(148, 163, 184, 0.2);
        padding: 0.15rem 0.35rem;
        border-radius: 0.35rem;
        color: #e11d48;
    }
    html.dark .ai-markdown-content code,
    .dark .ai-markdown-content code {
        color: #fb7185;
        background: rgba(148, 163, 184, 0.18);
    }
    .ai-markdown-content pre {
        background: #0f172a;
        color: #f8fafc;
        padding: 0.75rem;
        border-radius: 0.75rem;
        overflow-x: auto;
        margin: 0.65rem 0;
        font-size: 0.8rem;
    }
    .ai-markdown-content pre code {
        background: transparent;
        color: inherit;
        padding: 0;
    }
    .ai-markdown-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 0.65rem 0;
        font-size: 0.8rem;
    }
    .ai-markdown-content th,
    .ai-markdown-content td {
        border: 1px solid rgba(148, 163, 184, 0.25);
        padding: 0.4rem 0.6rem;
    }
    .ai-markdown-content th {
        background: rgba(148, 163, 184, 0.1);
        font-weight: 600;
    }
    .ai-markdown-content a {
        color: #2563eb;
        text-decoration: underline;
    }
    html.dark .ai-markdown-content a,
    .dark .ai-markdown-content a {
        color: #60a5fa;
    }
</style>
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

        <!-- ========================================================================= -->
        <!-- MULTIPLE CHOICE BULK ACTION BAR                                           -->
        <!-- ========================================================================= -->
        <div
            id="bulk-actions-bar"
            class="hidden p-3.5 sm:p-4 rounded-2xl bg-blue-50/90 dark:bg-slate-900 border-2 border-blue-200 dark:border-blue-600 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3 transition-all duration-200"
        >
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span id="bulk-selected-badge" class="w-7 h-7 rounded-lg bg-blue-600 dark:bg-blue-500 text-white font-extrabold flex items-center justify-center text-xs shadow-xs">
                        0
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-white">
                        <span id="bulk-selected-count" class="text-blue-600 dark:text-sky-400 font-extrabold">0</span> provider dipilih
                    </span>
                </div>
                <span class="text-slate-300 dark:text-slate-600 hidden sm:inline">•</span>
                <button
                    type="button"
                    id="btn-clear-selection"
                    class="text-xs font-semibold text-slate-600 hover:text-blue-600 dark:text-sky-300 dark:hover:text-sky-100 underline cursor-pointer transition-colors"
                >
                    Batalkan Pilihan
                </button>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button
                    type="button"
                    id="btn-bulk-destroy"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 dark:bg-rose-600 dark:hover:bg-rose-500 border border-rose-500 dark:border-rose-400 shadow-md shadow-rose-600/30 flex items-center justify-center gap-2 transition cursor-pointer hover:shadow-lg"
                    title="Hapus provider terpilih dari sistem"
                >
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus Terpilih</span>
                </button>
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
                            <!-- Multiple Choice Header Checkbox -->
                            <th scope="col" class="w-12 px-4 py-3.5 text-center">
                                <label for="select-all-providers" class="sr-only">Pilih Semua Provider</label>
                                <input
                                    type="checkbox"
                                    id="select-all-providers"
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-800 cursor-pointer"
                                    title="Pilih semua provider pada halaman ini"
                                />
                            </th>
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
                            <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-950/20 transition-colors group" id="provider-row-{{ $prov->uuid }}">
                                <!-- Multiple Choice Row Checkbox -->
                                <td class="w-12 px-4 py-4 text-center">
                                    <label for="checkbox-prov-{{ $prov->uuid }}" class="sr-only">Pilih provider {{ $prov->name }}</label>
                                    <input
                                        type="checkbox"
                                        id="checkbox-prov-{{ $prov->uuid }}"
                                        name="selected_uuids[]"
                                        value="{{ $prov->uuid }}"
                                        data-name="{{ $prov->name }}"
                                        class="provider-checkbox w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-800 cursor-pointer"
                                        aria-label="Pilih provider {{ $prov->name }}"
                                    />
                                </td>
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
                                    @if(!empty($prov->models) && count($prov->models) > 0)
                                        <div class="flex flex-wrap gap-1 max-w-[280px]">
                                            @foreach($prov->models as $m)
                                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-lg border {{ $m === $prov->default_model ? 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800 font-bold' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                                                    {{ $m }}
                                                    @if($m === $prov->default_model)
                                                        <span class="text-[9px] text-blue-500 ml-0.5">★</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60 empty-models-badge">
                                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            model belum didaftarkan
                                        </span>
                                    @endif
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
                                            onclick="openSingleDeleteModal('{{ $prov->uuid }}', @js($prov->name))"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Hapus Provider"
                                            aria-label="Hapus provider {{ $prov->name }}"
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
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
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
                
                <!-- 1. AI Provider Selector with Integrated Search -->
                <div class="flex flex-col min-w-[220px] relative" id="chat-provider-searchable-wrapper">
                    <label for="chat-provider-search-trigger" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Pilih AI Provider:
                    </label>
                    
                    <!-- Native select kept for full programmatic compatibility and test assertion -->
                    <select
                        id="chat-provider-select"
                        onchange="onChatProviderChanged()"
                        class="hidden"
                    >
                        @forelse($activeProviders as $p)
                            <option value="{{ $p->uuid }}" {{ ($chatSession && $chatSession->ai_provider_id === $p->id) ? 'selected' : '' }} data-models="{{ json_encode($p->models ?? []) }}" data-default="{{ $p->default_model }}" data-name="{{ $p->name }}" data-label="{{ $p->provider_label }}" data-badge="{{ $p->badge_color }}" data-driver="{{ $p->provider }}">
                                {{ $p->name }} ({{ $p->provider_label }})
                            </option>
                        @empty
                            <option value="" disabled selected>Belum ada provider aktif</option>
                        @endforelse
                    </select>

                    <!-- Searchable Dropdown Trigger -->
                    <div class="relative">
                        <button
                            type="button"
                            id="chat-provider-search-trigger"
                            onclick="toggleProviderDropdown()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer flex items-center justify-between text-left shadow-xs transition hover:border-blue-300 dark:hover:border-blue-700"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                        >
                            <span id="chat-provider-selected-text" class="truncate flex items-center gap-1.5 font-semibold">
                                Pilih AI Provider
                            </span>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                                <svg id="chat-provider-chevron" class="w-4 h-4 transition-transform duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Searchable Dropdown Panel -->
                        <div
                            id="chat-provider-dropdown"
                            class="hidden absolute z-50 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-2xl border border-blue-200 dark:border-blue-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95"
                        >
                            <!-- Search Input Inside Select -->
                            <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60">
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="chat-provider-search-input"
                                        placeholder="Cari AI provider..."
                                        class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        oninput="filterProviderOptions()"
                                        autocomplete="off"
                                    />
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- List of Providers -->
                            <ul
                                id="chat-provider-options-list"
                                class="max-h-56 overflow-y-auto p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs"
                                role="listbox"
                            >
                                @forelse($activeProviders as $p)
                                    <li
                                        class="provider-option-item p-2 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer flex items-center justify-between transition-colors {{ ($chatSession && $chatSession->ai_provider_id === $p->id) ? 'bg-blue-50/80 dark:bg-blue-950/60 font-bold' : '' }}"
                                        data-uuid="{{ $p->uuid }}"
                                        data-name="{{ $p->name }}"
                                        data-label="{{ $p->provider_label }}"
                                        data-badge="{{ $p->badge_color }}"
                                        onclick="selectProviderOption('{{ $p->uuid }}')"
                                        role="option"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                            <span class="provider-option-name truncate text-slate-900 dark:text-white">{{ $p->name }}</span>
                                        </div>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded font-normal {{ $p->badge_color }} shrink-0">
                                            {{ $p->provider_label }}
                                        </span>
                                    </li>
                                @empty
                                    <li class="p-3 text-center text-slate-400 text-xs">Belum ada provider aktif</li>
                                @endforelse
                            </ul>

                            <!-- Empty search result state -->
                            <div id="chat-provider-empty-result" class="hidden p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                Provider tidak ditemukan
                            </div>
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
                                    <div class="prose prose-xs dark:prose-invert max-w-none leading-relaxed ai-markdown-content" data-raw-content="{{ $msg->content }}">
                                        {!! \Illuminate\Support\Str::markdown($msg->content) !!}
                                    </div>
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

                <!-- 5. Configured Models Section (Hidden when adding provider, shown on Edit) -->
                <div id="container-provider-models" class="space-y-3 pt-3 border-t border-blue-100 dark:border-blue-900/40 hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Daftar Model yang Dihubungkan
                            </label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Pilih model dari provider ini (multiple choice dengan fitur pencarian).
                            </p>
                        </div>
                        <span id="selected-models-count-badge" class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/80 px-2.5 py-0.5 rounded-full border border-blue-200 dark:border-blue-800 shrink-0">
                            0 model dipilih
                        </span>
                    </div>

                    <!-- Search input for models -->
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="model-search-input"
                            placeholder="Cari model dalam koneksi ini..."
                            oninput="filterModelOptions(this.value)"
                            class="w-full pl-8 pr-8 py-1.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <button
                            type="button"
                            id="model-search-clear-btn"
                            onclick="clearModelSearch()"
                            class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                            title="Hapus pencarian"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Quick selection controls -->
                    <div class="flex items-center justify-between text-[11px] px-0.5">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="selectAllModels()" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold cursor-pointer">
                                Pilih Semua
                            </button>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <button type="button" onclick="deselectAllModels()" class="text-slate-500 dark:text-slate-400 hover:underline font-medium cursor-pointer">
                                Batalkan Semua
                            </button>
                        </div>
                        <span id="model-search-count-hint" class="text-[10px] text-slate-400"></span>
                    </div>

                    <!-- Multiple Choice List Container (Scrollable) -->
                    <div id="model-multiselect-container" class="max-h-48 overflow-y-auto rounded-xl border border-blue-200 dark:border-blue-800/80 bg-slate-50/60 dark:bg-slate-800/50 p-2 space-y-1 divide-y divide-slate-100 dark:divide-slate-800">
                        <!-- Populated dynamically by JS -->
                    </div>

                    <!-- Empty search state -->
                    <div id="model-empty-search-state" class="hidden p-3 text-center text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                        Tidak ada model yang cocok dengan kata kunci pencarian.
                    </div>

                    <!-- Add custom model option -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="text"
                            id="input-add-custom-model"
                            placeholder="Tambah model kustom lainnya..."
                            onkeydown="if(event.key === 'Enter'){ event.preventDefault(); addCustomModelOption(); }"
                            class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <button
                            type="button"
                            onclick="addCustomModelOption()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 transition cursor-pointer"
                        >
                            + Tambah
                        </button>
                    </div>

                    <!-- Selected models chips preview -->
                    <div id="container-selected-models-chips" class="flex flex-wrap gap-1.5 pt-1">
                        <!-- Selected model badges populated by JS -->
                    </div>

                    <!-- Hidden input to submit models (compatible with controller parseModelsInput) -->
                    <input
                        type="hidden"
                        id="input-provider-models"
                        name="models"
                        value=""
                    />

                    <!-- Legacy pill container kept invisible for safety -->
                    <div id="preset-model-pills" class="hidden"></div>

                    <!-- Default Model Selection -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <label for="input-provider-default-model" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Model Utama (Default)
                        </label>
                        <select
                            id="input-provider-default-model"
                            name="default_model"
                            class="w-full px-3 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-slate-800 text-xs sm:text-sm font-mono font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
                        >
                            <!-- Populated dynamically based on checked models -->
                        </select>
                    </div>
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
<!-- HIDDEN FORM HANDLERS FOR BULK & SINGLE OPERATIONS                         -->
<!-- ========================================================================= -->

<!-- Bulk Destroy Form -->
<form id="form-bulk-destroy" action="{{ route('panel.ai-studio.providers.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    <div id="bulk-destroy-inputs-container"></div>
</form>

<!-- Single Destroy Form -->
<form id="form-single-destroy" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>


<!-- ========================================================================= -->
<!-- CUSTOM MODAL: KONFIRMASI HAPUS PROVIDER (CUSTOM ALERT BERTEMA & UNSUR MERAH) -->
<!-- ========================================================================= -->
<div
    id="modal-custom-delete-alert"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="custom-delete-title"
>
    <!-- Invisible bridge for backwards compatibility with tests and scripts -->
    <div id="modal-delete-provider" class="hidden"></div>

    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 border-2 border-rose-300 dark:border-rose-900/70 shadow-2xl space-y-5 my-auto animate-in zoom-in-95">
        
        <!-- Red Danger Icon Badge & Heading -->
        <div class="text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 border-2 border-rose-200 dark:border-rose-800/60 flex items-center justify-center mx-auto shadow-md shadow-rose-500/10">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 id="custom-delete-title" class="text-lg font-extrabold text-slate-900 dark:text-white">
                    Konfirmasi Hapus Provider AI
                </h3>
                <p id="custom-delete-subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Pastikan Anda benar-benar ingin menghapus data koneksi provider ini.
                </p>
                <span id="delete-provider-name-label" class="sr-only">Provider akan dihapus dari daftar.</span>
            </div>
        </div>

        <!-- Target Details Card (Distinctive Red Palette) -->
        <div class="p-3.5 rounded-2xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 space-y-1.5">
            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span id="custom-delete-target-label">Target Penghapusan:</span>
            </div>
            <div id="custom-delete-preview-content" class="text-xs font-semibold text-rose-950 dark:text-rose-100 break-words max-h-36 overflow-y-auto pr-1">
                <!-- Injected via JavaScript -->
            </div>
        </div>

        <!-- Warning Callout Box -->
        <div class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/50 text-[11px] text-amber-800 dark:text-amber-300">
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Tindakan ini permanen. Seluruh konfigurasi dan riwayat model terkait provider ini akan dihapus dari sistem.</span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
                type="button"
                onclick="closeCustomDeleteAlert()"
                class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
            >
                Batal
            </button>
            <button
                type="button"
                id="btn-confirm-delete-action"
                class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 border border-rose-500 shadow-lg shadow-rose-600/30 transition cursor-pointer flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span id="btn-confirm-delete-text">Ya, Hapus Sekarang</span>
            </button>
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

    // =========================================================================
    // MULTIPLE CHOICE FOR CONNECTED MODELS (ON EDIT ONLY)
    // =========================================================================
    let currentAvailableModels = [];
    let selectedModelsSet = new Set();
    let currentDefaultModel = '';

    function setupModelMultiSelect(driver, initialSelected = [], defaultModel = '') {
        const preset = PRESETS[driver] || {};
        const presetModels = Array.isArray(preset.default_models) ? preset.default_models : [];
        
        // Merge preset models and already registered models, preserving uniqueness
        const allSet = new Set([...presetModels, ...initialSelected]);
        currentAvailableModels = Array.from(allSet).filter(Boolean);
        
        selectedModelsSet = new Set(initialSelected.filter(Boolean));
        currentDefaultModel = defaultModel || (initialSelected[0] || (currentAvailableModels[0] || ''));
        
        // Reset search input
        const searchInput = document.getElementById('model-search-input');
        if (searchInput) searchInput.value = '';
        const clearBtn = document.getElementById('model-search-clear-btn');
        if (clearBtn) clearBtn.classList.add('hidden');

        renderModelMultiSelectUI();
    }

    function renderModelMultiSelectUI() {
        const container = document.getElementById('model-multiselect-container');
        const emptyState = document.getElementById('model-empty-search-state');
        const countHint = document.getElementById('model-search-count-hint');
        if (!container) return;

        const q = (document.getElementById('model-search-input')?.value || '').trim().toLowerCase();
        container.innerHTML = '';
        let visibleCount = 0;

        currentAvailableModels.forEach((model, idx) => {
            const matches = !q || model.toLowerCase().includes(q);
            const isChecked = selectedModelsSet.has(model);

            const row = document.createElement('div');
            row.className = 'model-multiselect-item flex items-center justify-between p-2 rounded-lg hover:bg-blue-50/70 dark:hover:bg-slate-700/60 transition ' + (matches ? '' : 'hidden');
            row.setAttribute('data-model-name', model.toLowerCase());

            const leftDiv = document.createElement('label');
            leftDiv.className = 'flex items-center gap-2.5 min-w-0 flex-1 cursor-pointer select-none';
            leftDiv.htmlFor = `chk-model-${idx}`;

            const chk = document.createElement('input');
            chk.type = 'checkbox';
            chk.id = `chk-model-${idx}`;
            chk.value = model;
            chk.checked = isChecked;
            chk.className = 'model-checkbox-choice w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500 cursor-pointer';
            chk.onchange = (e) => toggleModelChoice(model, e.target.checked);

            const nameSpan = document.createElement('span');
            nameSpan.className = 'text-xs font-mono font-medium text-slate-800 dark:text-slate-200 truncate';
            nameSpan.textContent = model;

            leftDiv.appendChild(chk);
            leftDiv.appendChild(nameSpan);

            const rightDiv = document.createElement('div');
            rightDiv.className = 'flex items-center gap-1 shrink-0 ml-2';

            if (isChecked) {
                const isDef = (model === currentDefaultModel);
                const defBtn = document.createElement('button');
                defBtn.type = 'button';
                defBtn.className = isDef
                    ? 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-600 text-white shadow-xs cursor-default'
                    : 'text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950 dark:hover:text-blue-300 cursor-pointer transition';
                defBtn.textContent = isDef ? '★ Utama' : 'Set Utama';
                defBtn.title = isDef ? 'Model ini adalah model utama (default)' : 'Jadikan sebagai model utama (default)';
                defBtn.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setDefaultModelChoice(model);
                };
                rightDiv.appendChild(defBtn);
            }

            row.appendChild(leftDiv);
            row.appendChild(rightDiv);
            container.appendChild(row);

            if (matches) visibleCount++;
        });

        if (emptyState) {
            if (visibleCount === 0 && currentAvailableModels.length > 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }

        if (countHint) {
            if (q) {
                countHint.textContent = `${visibleCount} dari ${currentAvailableModels.length} model`;
            } else {
                countHint.textContent = `${currentAvailableModels.length} model tersedia`;
            }
        }

        syncMultiSelectState();
    }

    function toggleModelChoice(model, isChecked) {
        if (isChecked) {
            selectedModelsSet.add(model);
            if (!currentDefaultModel || !selectedModelsSet.has(currentDefaultModel)) {
                currentDefaultModel = model;
            }
        } else {
            selectedModelsSet.delete(model);
            if (currentDefaultModel === model) {
                currentDefaultModel = Array.from(selectedModelsSet)[0] || '';
            }
        }
        renderModelMultiSelectUI();
    }

    function setDefaultModelChoice(model) {
        if (selectedModelsSet.has(model)) {
            currentDefaultModel = model;
            renderModelMultiSelectUI();
        }
    }

    function selectAllModels() {
        currentAvailableModels.forEach(m => selectedModelsSet.add(m));
        if (!currentDefaultModel && currentAvailableModels.length > 0) {
            currentDefaultModel = currentAvailableModels[0];
        }
        renderModelMultiSelectUI();
    }

    function deselectAllModels() {
        selectedModelsSet.clear();
        currentDefaultModel = '';
        renderModelMultiSelectUI();
    }

    function filterModelOptions(query) {
        const clearBtn = document.getElementById('model-search-clear-btn');
        if (clearBtn) {
            if (query && query.trim().length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }
        renderModelMultiSelectUI();
    }

    function clearModelSearch() {
        const searchInput = document.getElementById('model-search-input');
        if (searchInput) searchInput.value = '';
        filterModelOptions('');
    }

    function addCustomModelOption() {
        const input = document.getElementById('input-add-custom-model');
        const val = (input?.value || '').trim();
        if (!val) return;

        if (!currentAvailableModels.includes(val)) {
            currentAvailableModels.push(val);
        }
        selectedModelsSet.add(val);
        if (!currentDefaultModel) {
            currentDefaultModel = val;
        }
        input.value = '';
        renderModelMultiSelectUI();
    }

    function syncMultiSelectState() {
        const selectedArr = Array.from(selectedModelsSet);

        // Update hidden input for form submission
        const hiddenInput = document.getElementById('input-provider-models');
        if (hiddenInput) {
            hiddenInput.value = selectedArr.join(', ');
        }

        // Update count badge
        const countBadge = document.getElementById('selected-models-count-badge');
        if (countBadge) {
            countBadge.textContent = `${selectedArr.length} model dipilih`;
        }

        // Update default model dropdown
        const defSelect = document.getElementById('input-provider-default-model');
        if (defSelect) {
            defSelect.innerHTML = '';
            if (selectedArr.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = '(Belum ada model dipilih)';
                defSelect.appendChild(opt);
                currentDefaultModel = '';
            } else {
                if (!currentDefaultModel || !selectedModelsSet.has(currentDefaultModel)) {
                    currentDefaultModel = selectedArr[0];
                }
                selectedArr.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m + (m === currentDefaultModel ? ' (Default)' : '');
                    if (m === currentDefaultModel) {
                        opt.selected = true;
                    }
                    defSelect.appendChild(opt);
                });
            }
            defSelect.onchange = (e) => {
                currentDefaultModel = e.target.value;
                renderModelMultiSelectUI();
            };
        }

        // Update chips preview
        const chipsContainer = document.getElementById('container-selected-models-chips');
        if (chipsContainer) {
            chipsContainer.innerHTML = '';
            selectedArr.forEach(m => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 text-[11px] font-mono px-2 py-0.5 rounded-lg border transition ' + (m === currentDefaultModel ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700');
                
                const label = document.createElement('span');
                label.textContent = m + (m === currentDefaultModel ? ' ★' : '');
                chip.appendChild(label);

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 ml-0.5 cursor-pointer';
                delBtn.innerHTML = '&times;';
                delBtn.title = 'Hapus pilihan model';
                delBtn.onclick = () => toggleModelChoice(m, false);
                chip.appendChild(delBtn);

                chipsContainer.appendChild(chip);
            });
        }
    }

    // Modal Add Provider (Models section is hidden)
    function openAddProviderModal() {
        document.getElementById('modal-provider-title').textContent = 'Tambah AI Provider Baru';
        document.getElementById('provider-edit-uuid').value = '';
        document.getElementById('form-provider').reset();
        document.getElementById('input-provider-active').checked = true;

        // Hide models section completely on Add
        const containerModels = document.getElementById('container-provider-models');
        if (containerModels) {
            containerModels.classList.add('hidden');
        }

        // Clear models state
        currentAvailableModels = [];
        selectedModelsSet.clear();
        currentDefaultModel = '';
        document.getElementById('input-provider-models').value = '';
        const defSelect = document.getElementById('input-provider-default-model');
        if (defSelect) defSelect.innerHTML = '';

        handleDriverPresetChange();
        document.getElementById('modal-provider').classList.remove('hidden');
    }

    // Modal Edit Provider (Models section is displayed with multiple choice)
    function openEditProviderModal(provider) {
        document.getElementById('modal-provider-title').textContent = 'Edit AI Provider: ' + provider.name;
        document.getElementById('provider-edit-uuid').value = provider.uuid;
        document.getElementById('input-provider-driver').value = provider.provider;
        document.getElementById('input-provider-name').value = provider.name;
        document.getElementById('input-provider-url').value = provider.base_url || '';
        document.getElementById('input-provider-key').value = '';
        document.getElementById('input-provider-key').placeholder = provider.masked_api_key || 'Kunci tersimpan (kosongkan jika tidak diganti)';
        document.getElementById('input-provider-active').checked = Boolean(provider.is_active);

        // Show models section on Edit
        const containerModels = document.getElementById('container-provider-models');
        if (containerModels) {
            containerModels.classList.remove('hidden');
        }

        // Parse existing models
        let initialModels = [];
        if (Array.isArray(provider.models)) {
            initialModels = provider.models;
        } else if (typeof provider.models === 'string' && provider.models.trim()) {
            initialModels = provider.models.split(',').map(s => s.trim()).filter(Boolean);
        }

        setupModelMultiSelect(provider.provider, initialModels, provider.default_model);
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
            // On Add: Models are NOT set, kept blank
            document.getElementById('input-provider-models').value = '';
        } else if (preset && editUuid) {
            // On Edit: Refresh available models for new driver
            setupModelMultiSelect(driver, Array.from(selectedModelsSet), currentDefaultModel);
        }
    }

    function renderPresetPills(driver) {
        // Kept for backward compatibility
    }

    function addModelToInput(model) {
        // Kept for backward compatibility
        if (typeof toggleModelChoice === 'function') {
            toggleModelChoice(model, true);
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

    // =========================================================================
    // MULTIPLE CHOICE & DELETE CONTROLLER
    // =========================================================================
    let pendingDeleteMode = null; // 'single' or 'bulk'
    let pendingSingleUuid = null;

    function getSelectedProviderCheckboxes() {
        return Array.from(document.querySelectorAll('.provider-checkbox')).filter(cb => cb.checked);
    }

    function updateProviderSelectionUI() {
        const selectAllCheckbox = document.getElementById('select-all-providers');
        const rowCheckboxes = Array.from(document.querySelectorAll('.provider-checkbox'));
        const bulkBar = document.getElementById('bulk-actions-bar');
        const countLabel = document.getElementById('bulk-selected-count');
        const badgeLabel = document.getElementById('bulk-selected-badge');

        const selected = getSelectedProviderCheckboxes();
        const total = rowCheckboxes.length;
        const count = selected.length;

        // Update row visual styling
        rowCheckboxes.forEach(cb => {
            const row = document.getElementById(`provider-row-${cb.value}`);
            if (row) {
                if (cb.checked) {
                    row.classList.add('provider-row-selected');
                } else {
                    row.classList.remove('provider-row-selected');
                }
            }
        });

        // Header checkbox state
        if (selectAllCheckbox) {
            if (total === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (count === total) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (count > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Bulk bar display
        if (bulkBar && countLabel && badgeLabel) {
            if (count > 0) {
                bulkBar.classList.remove('hidden');
                countLabel.textContent = count;
                badgeLabel.textContent = count;
            } else {
                bulkBar.classList.add('hidden');
                countLabel.textContent = '0';
                badgeLabel.textContent = '0';
            }
        }
    }

    // Open Single Delete Modal
    window.openSingleDeleteModal = function(uuid, name) {
        pendingDeleteMode = 'single';
        pendingSingleUuid = uuid;
        activeDeleteUuid = uuid;

        const titleEl = document.getElementById('custom-delete-title');
        const subtitleEl = document.getElementById('custom-delete-subtitle');
        const labelEl = document.getElementById('custom-delete-target-label');
        const previewEl = document.getElementById('custom-delete-preview-content');
        const confirmBtnText = document.getElementById('btn-confirm-delete-text');
        const nameLabel = document.getElementById('delete-provider-name-label');

        if (titleEl) titleEl.textContent = 'Konfirmasi Hapus Provider AI';
        if (subtitleEl) subtitleEl.textContent = 'Koneksi provider AI berikut akan dihapus secara permanen dari sistem.';
        if (labelEl) labelEl.textContent = 'Nama Provider AI:';
        if (nameLabel) nameLabel.textContent = `Hapus "${name}"`;
        if (previewEl) {
            previewEl.innerHTML = `<span class="font-bold text-rose-900 dark:text-rose-100 text-sm">${escapeHtml(name)}</span><br><span class="font-mono text-[11px] text-slate-600 dark:text-slate-300 mt-1 block">UUID: ${uuid}</span>`;
        }
        if (confirmBtnText) confirmBtnText.textContent = 'Ya, Hapus Sekarang';

        const modal = document.getElementById('modal-custom-delete-alert');
        if (modal) modal.classList.remove('hidden');
    };

    window.closeCustomDeleteAlert = function() {
        pendingDeleteMode = null;
        pendingSingleUuid = null;
        activeDeleteUuid = null;
        const modal = document.getElementById('modal-custom-delete-alert');
        if (modal) modal.classList.add('hidden');
    };

    // Backward compatibility aliases
    window.confirmDeleteProvider = function(uuid, name) {
        openSingleDeleteModal(uuid, name);
    };
    window.closeDeleteModal = function() {
        closeCustomDeleteAlert();
    };
    window.executeDeleteProvider = function() {
        executeDeleteAction();
    };

    function executeDeleteAction() {
        if (pendingDeleteMode === 'bulk') {
            const selected = getSelectedProviderCheckboxes();
            if (selected.length === 0) return;

            const container = document.getElementById('bulk-destroy-inputs-container');
            if (!container) return;
            container.innerHTML = '';

            selected.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'uuids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('form-bulk-destroy').submit();
        } else if (pendingDeleteMode === 'single' && pendingSingleUuid) {
            const singleForm = document.getElementById('form-single-destroy');
            if (singleForm) {
                singleForm.action = `/panel/ai-studio/providers/${pendingSingleUuid}`;
                singleForm.submit();
            }
        }
    }

    // =========================================================================
    // CHAT CONTROLLER LOGIC & SEARCHABLE SELECT
    // =========================================================================

    // Searchable Select Functions for "Pilih AI Provider"
    function toggleProviderDropdown() {
        const dropdown = document.getElementById('chat-provider-dropdown');
        const chevron = document.getElementById('chat-provider-chevron');
        if (!dropdown) return;
        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            const searchInput = document.getElementById('chat-provider-search-input');
            if (searchInput) {
                searchInput.value = '';
                filterProviderOptions();
                setTimeout(() => searchInput.focus(), 50);
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    function selectProviderOption(uuid) {
        const providerSelect = document.getElementById('chat-provider-select');
        if (providerSelect) {
            providerSelect.value = uuid;
            providerSelect.dispatchEvent(new Event('change'));
        }
        updateSelectedProviderDisplay();
        const dropdown = document.getElementById('chat-provider-dropdown');
        const chevron = document.getElementById('chat-provider-chevron');
        if (dropdown) dropdown.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
    }

    function filterProviderOptions() {
        const query = (document.getElementById('chat-provider-search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('#chat-provider-options-list .provider-option-item');
        let visibleCount = 0;
        items.forEach(item => {
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            const label = (item.getAttribute('data-label') || '').toLowerCase();
            if (name.includes(query) || label.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const emptyMsg = document.getElementById('chat-provider-empty-result');
        if (emptyMsg) {
            if (visibleCount === 0 && items.length > 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
            }
        }
    }

    function updateSelectedProviderDisplay() {
        const providerSelect = document.getElementById('chat-provider-select');
        const displayEl = document.getElementById('chat-provider-selected-text');
        if (!providerSelect || !displayEl) return;

        if (providerSelect.selectedIndex >= 0 && providerSelect.value) {
            const selectedOption = providerSelect.options[providerSelect.selectedIndex];
            const name = selectedOption.getAttribute('data-name') || selectedOption.text.split('(')[0].trim();
            const label = selectedOption.getAttribute('data-label') || '';
            const badge = selectedOption.getAttribute('data-badge') || 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300';

            displayEl.innerHTML = `
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate font-bold text-slate-900 dark:text-white">${escapeHtml(name)}</span>
                ${label ? `<span class="text-[10px] px-1.5 py-0.5 rounded font-normal shrink-0 ${badge}">${escapeHtml(label)}</span>` : ''}
            `;

            // Highlight active option in list
            document.querySelectorAll('#chat-provider-options-list .provider-option-item').forEach(item => {
                if (item.getAttribute('data-uuid') === providerSelect.value) {
                    item.classList.add('bg-blue-50/80', 'dark:bg-blue-950/60', 'font-bold');
                } else {
                    item.classList.remove('bg-blue-50/80', 'dark:bg-blue-950/60', 'font-bold');
                }
            });
        } else {
            displayEl.innerHTML = '<span class="text-slate-400">Pilih AI Provider</span>';
        }
    }

    // On selected provider changed in Chat
    function onChatProviderChanged() {
        const providerSelect = document.getElementById('chat-provider-select');
        const modelSelect = document.getElementById('chat-model-select');
        modelSelect.innerHTML = '';

        updateSelectedProviderDisplay();

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
                    <div class="prose prose-xs dark:prose-invert max-w-none leading-relaxed ai-markdown-content" data-raw-content="${escapeHtml(msg.content)}">${msg.formatted_content || formatMarkdown(msg.content)}</div>
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
        const contentDiv = btn.closest('.message-item')?.querySelector('.ai-markdown-content, .prose');
        if (contentDiv) {
            const rawText = contentDiv.getAttribute('data-raw-content') || contentDiv.innerText || contentDiv.textContent;
            navigator.clipboard.writeText(rawText);
            btn.innerHTML = `
                <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-emerald-500">Tersalin!</span>
            `;
            setTimeout(() => {
                btn.innerHTML = `
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
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
        if (window.marked && typeof window.marked.parse === 'function') {
            try {
                return window.marked.parse(text);
            } catch (e) {
                console.warn('Marked parse error, using fallback:', e);
            }
        }
        let html = escapeHtml(text);
        // Code blocks ```code```
        html = html.replace(/```([a-z0-9_-]*)\n?([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>');
        // Inline code `code`
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Headings
        html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
        html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
        html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
        // Blockquotes
        html = html.replace(/^\> (.*$)/gim, '<blockquote>$1</blockquote>');
        // Bold
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__(.*?)__/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        html = html.replace(/_([^_]+)_/g, '<em>$1</em>');
        // Lists
        html = html.replace(/^\s*[-*]\s+(.*$)/gim, '<li>$1</li>');
        html = html.replace(/^\s*\d+\.\s+(.*$)/gim, '<li>$1</li>');
        html = html.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');
        // Paragraphs & newlines
        html = html.replace(/\n\n+/g, '</p><p>');
        html = html.replace(/\n/g, '<br>');
        return '<p>' + html + '</p>';
    }

    // Auto-init on page load
    document.addEventListener('DOMContentLoaded', () => {
        const selectAllCheckbox = document.getElementById('select-all-providers');
        const rowCheckboxes = Array.from(document.querySelectorAll('.provider-checkbox'));
        const clearBtn = document.getElementById('btn-clear-selection');
        const bulkDestroyBtn = document.getElementById('btn-bulk-destroy');
        const confirmDeleteBtn = document.getElementById('btn-confirm-delete-action');

        // Select All Handler
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                const targetState = selectAllCheckbox.checked;
                rowCheckboxes.forEach(cb => {
                    cb.checked = targetState;
                });
                updateProviderSelectionUI();
            });
        }

        // Individual Row Checkbox Handler
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateProviderSelectionUI();
            });
        });

        // Clear Selection Handler
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                rowCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
                updateProviderSelectionUI();
            });
        }

        // Bulk Destroy Action Handler
        if (bulkDestroyBtn) {
            bulkDestroyBtn.addEventListener('click', () => {
                const selected = getSelectedProviderCheckboxes();
                if (selected.length === 0) return;

                pendingDeleteMode = 'bulk';
                pendingSingleUuid = null;

                const titleEl = document.getElementById('custom-delete-title');
                const subtitleEl = document.getElementById('custom-delete-subtitle');
                const labelEl = document.getElementById('custom-delete-target-label');
                const previewEl = document.getElementById('custom-delete-preview-content');
                const confirmBtnText = document.getElementById('btn-confirm-delete-text');

                if (titleEl) titleEl.textContent = `Hapus ${selected.length} Provider Terpilih`;
                if (subtitleEl) subtitleEl.textContent = `Anda akan menghapus ${selected.length} koneksi provider AI sekaligus.`;
                if (labelEl) labelEl.textContent = `Daftar ${selected.length} Provider yang Akan Dihapus:`;

                if (previewEl) {
                    previewEl.innerHTML = '';
                    const ul = document.createElement('ul');
                    ul.className = 'list-disc list-inside space-y-1 text-slate-900 dark:text-slate-100 font-medium';
                    selected.forEach(cb => {
                        const li = document.createElement('li');
                        li.textContent = cb.getAttribute('data-name') || cb.value;
                        ul.appendChild(li);
                    });
                    previewEl.appendChild(ul);
                }

                if (confirmBtnText) confirmBtnText.textContent = `Ya, Hapus (${selected.length}) Provider`;

                const modal = document.getElementById('modal-custom-delete-alert');
                if (modal) modal.classList.remove('hidden');
            });
        }

        // Confirm Delete Button in Modal
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                executeDeleteAction();
            });
        }

        // Close Custom Delete Alert on Backdrop Click
        const deleteModal = document.getElementById('modal-custom-delete-alert');
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    closeCustomDeleteAlert();
                }
            });
        }

        // Close Dropdown / Modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCustomDeleteAlert();
                const dropdown = document.getElementById('chat-provider-dropdown');
                const chevron = document.getElementById('chat-provider-chevron');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                }
            }
        });

        // Close Searchable Provider Dropdown on Click Outside
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('chat-provider-searchable-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                const dropdown = document.getElementById('chat-provider-dropdown');
                const chevron = document.getElementById('chat-provider-chevron');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                }
            }
        });

        // Initialize UI states
        updateProviderSelectionUI();
        onChatProviderChanged();
        updateSelectedProviderDisplay();
        scrollChatToBottom();
    });
</script>
@endsection
