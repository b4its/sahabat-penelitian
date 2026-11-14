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
        overflow-x: auto;
        min-width: 0;
        max-width: 100%;
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
<div class="space-y-6 w-full max-w-full min-w-0">

    <!-- ========================================================================= -->
    <!-- 1. HEADER SECTION & TABS NAVIGATION                                      -->
    <!-- ========================================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-2 border-b border-blue-100 dark:border-blue-900/40">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-500/30 shrink-0">
                    <i class="fa-solid fa-microchip text-sm"></i>
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight truncate">
                    AI Studio
                </h1>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 shrink-0">
                    LLM Hub &amp; Chat
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pusat integrasi koneksi multi-provider LLM (OpenAI, Gemini, Claude, Groq, Ollama) dan workspace interaktif chat model.
            </p>
        </div>

        <!-- Navigation Tabs (Fully Responsive across mobile, tablet, and desktop) -->
        <div class="flex items-center p-1 bg-slate-200/70 dark:bg-slate-800/80 rounded-2xl w-full sm:w-auto shrink-0 self-stretch sm:self-start lg:self-auto overflow-x-auto max-w-full gap-1">
            <button
                type="button"
                onclick="switchTab('connections')"
                id="tab-btn-connections"
                class="flex-1 sm:flex-initial flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 {{ $activeTab === 'connections' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <i class="fa-solid fa-plug text-xs"></i>
                <span class="whitespace-nowrap">Koneksi Provider</span>
                <span class="ml-1 text-[11px] px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 shrink-0 font-bold">
                    {{ $providers->total() }}
                </span>
            </button>

            <button
                type="button"
                onclick="switchTab('chat')"
                id="tab-btn-chat"
                class="flex-1 sm:flex-initial flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 {{ $activeTab === 'chat' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
            >
                <i class="fa-solid fa-comments text-xs"></i>
                <span class="whitespace-nowrap">Chat AI Studio</span>
                <span class="ml-1 text-[11px] px-1.5 py-0.2 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 shrink-0 font-bold">
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

        <!-- Metrics Overview Cards (Responsive Grid) -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
            <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs min-w-0">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block truncate">Total Provider</span>
                <div class="mt-2 flex items-baseline justify-between gap-1">
                    <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white" id="stat-total-providers">{{ $providers->total() }}</span>
                    <span class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 shrink-0">Terkonfigurasi</span>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs min-w-0">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block truncate">Provider Aktif</span>
                <div class="mt-2 flex items-baseline justify-between gap-1">
                    <span class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400" id="stat-active-providers">{{ $activeProviders->count() }}</span>
                    <span class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 shrink-0">Siap Pakai</span>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs min-w-0">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block truncate">Model Terhubung</span>
                <div class="mt-2 flex items-baseline justify-between gap-1">
                    @php
                        $totalModels = $activeProviders->sum(fn($p) => count($p->models ?? []));
                    @endphp
                    <span class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400" id="stat-total-models">{{ $totalModels }}</span>
                    <span class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 shrink-0">LLM Ready</span>
                </div>
            </div>

            <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs min-w-0">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block truncate">Status Gateway</span>
                <div class="mt-2 flex items-baseline justify-between gap-1">
                    <span class="text-base sm:text-lg font-extrabold text-blue-600 dark:text-blue-400 flex items-center gap-1.5 truncate">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                        <span class="truncate">Optimal</span>
                    </span>
                    <span class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 shrink-0">Auto Route</span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MULTIPLE CHOICE BULK ACTION BAR                                           -->
        <!-- ========================================================================= -->
        <div
            id="bulk-actions-bar"
            class="hidden p-3.5 sm:p-4 rounded-2xl bg-blue-50/90 dark:bg-slate-900 border-2 border-blue-200 dark:border-blue-600 shadow-lg flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 transition-all duration-200"
        >
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span id="bulk-selected-badge" class="w-7 h-7 rounded-lg bg-blue-600 dark:bg-blue-500 text-white font-extrabold flex items-center justify-center text-xs shadow-xs shrink-0">
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

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end shrink-0">
                <button
                    type="button"
                    id="btn-bulk-destroy"
                    class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 dark:bg-rose-600 dark:hover:bg-rose-500 border border-rose-500 dark:border-rose-400 shadow-md shadow-rose-600/30 flex items-center justify-center gap-2 transition cursor-pointer hover:shadow-lg"
                    title="Hapus provider terpilih dari sistem"
                >
                    <i class="fa-solid fa-trash-can text-white text-xs"></i>
                    <span>Hapus Terpilih</span>
                </button>
            </div>
        </div>

        <!-- Connection Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl shadow-xs overflow-hidden">
            <!-- Table Header Bar -->
            <div class="p-3.5 sm:p-5 border-b border-blue-100 dark:border-blue-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                        <span>Daftar Provider AI Terhubung</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 shrink-0">
                            {{ $providers->total() }} Provider
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
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 cursor-pointer"
                        id="btn-add-provider"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
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
                                                <i class="fa-solid fa-server text-sm text-slate-700 dark:text-slate-200"></i>
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
                                                        <i class="fa-solid fa-star text-[9px] text-blue-500 ml-0.5"></i>
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
                                <td class="px-3 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Test Connection Button -->
                                        <button
                                            type="button"
                                            onclick="testProviderConnection('{{ $prov->uuid }}')"
                                            class="p-2 rounded-lg text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors cursor-pointer btn-test-conn"
                                            title="Uji Koneksi Provider"
                                            id="btn-test-{{ $prov->uuid }}"
                                        >
                                            <i class="fa-solid fa-bolt text-xs test-icon-idle"></i>
                                            <i class="fa-solid fa-spinner fa-spin text-xs test-icon-spinner hidden text-blue-600"></i>
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            onclick="openEditProviderModal({{ json_encode($prov) }})"
                                            class="p-2 rounded-lg text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Edit Pengaturan Provider"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            onclick="openSingleDeleteModal('{{ $prov->uuid }}', @js($prov->name))"
                                            class="p-2 rounded-lg text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Hapus Provider"
                                            aria-label="Hapus provider {{ $prov->name }}"
                                        >
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 sm:px-6 py-12 text-center text-slate-400">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950 flex items-center justify-center text-blue-600 mb-3">
                                            <i class="fa-solid fa-server text-xl"></i>
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

            <!-- Pagination Bar -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <div class="text-slate-500 dark:text-slate-400 font-medium text-center sm:text-left shrink-0">
                    Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ $providers->firstItem() ?? 0 }}</span> sampai <span class="font-bold text-slate-800 dark:text-slate-200">{{ $providers->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ $providers->total() }}</span> provider terhubung
                </div>
                @if ($providers->hasPages())
                    <div class="pagination-container w-full sm:w-auto flex justify-center sm:justify-end overflow-x-auto max-w-full py-0.5" id="providers-pagination-container">
                        {{ $providers->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: CHAT AI STUDIO                                                    -->
    <!-- ========================================================================= -->
    <div id="tab-content-chat" class="{{ $activeTab === 'chat' ? 'block' : 'hidden' }} space-y-4">

        <!-- Top Configuration Bar: Provider & Model Selector (Responsive Layout) -->
        <div class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex flex-col xl:flex-row xl:items-end justify-between gap-3">
            
            <!-- Selectors Grid (Responsive across mobile, tablet, and desktop) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:flex xl:items-end gap-3 flex-1 min-w-0">
                
                <!-- 1. AI Provider Selector with Integrated Search -->
                <div class="flex flex-col w-full min-w-0 xl:w-64 relative" id="chat-provider-searchable-wrapper">
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
                                <i id="chat-provider-chevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-150"></i>
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
                                    <i class="fa-solid fa-magnifying-glass text-xs text-slate-400 absolute left-2.5 top-2.5 pointer-events-none"></i>
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

                <!-- 2. Model Selector (Searchable Dropdown with conditional search input) -->
                <div class="flex flex-col w-full min-w-0 xl:w-64 relative" id="chat-model-searchable-wrapper">
                    <label for="chat-model-search-trigger" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Pilih Model Terhubung:
                    </label>

                    <!-- Native select kept hidden for full programmatic access and test assertions -->
                    <select
                        id="chat-model-select"
                        class="hidden"
                    >
                        <!-- Populated dynamically via JS -->
                    </select>

                    <!-- Searchable Dropdown Trigger -->
                    <div class="relative">
                        <button
                            type="button"
                            id="chat-model-search-trigger"
                            onclick="toggleChatModelDropdown()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer flex items-center justify-between text-left shadow-xs transition hover:border-blue-300 dark:hover:border-blue-700"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                        >
                            <span id="chat-model-selected-text" class="truncate font-mono font-medium flex items-center gap-1.5">
                                <i class="fa-solid fa-microchip text-xs text-blue-600 dark:text-blue-400 shrink-0"></i>
                                <span>Pilih Model</span>
                            </span>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                                <i id="chat-model-chevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-150"></i>
                            </div>
                        </button>

                        <!-- Searchable Dropdown Panel -->
                        <div
                            id="chat-model-dropdown"
                            class="hidden absolute z-50 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-2xl border border-blue-200 dark:border-blue-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95"
                        >
                            <!-- Search Input Inside Select (Hidden if models <= 2, shown if > 2) -->
                            <div id="chat-model-search-container" class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60">
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="chat-model-search-input"
                                        placeholder="Cari model..."
                                        class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        oninput="filterChatModelOptions()"
                                        autocomplete="off"
                                    />
                                    <i class="fa-solid fa-magnifying-glass text-xs text-slate-400 absolute left-2.5 top-2.5 pointer-events-none"></i>
                                </div>
                            </div>

                            <!-- List of Models -->
                            <ul
                                id="chat-model-options-list"
                                class="max-h-56 overflow-y-auto p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs font-mono"
                                role="listbox"
                            >
                                <!-- Populated dynamically via JS -->
                            </ul>

                            <!-- Empty search result state -->
                            <div id="chat-model-empty-result" class="hidden p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                Model tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. System Prompt / Persona Preset -->
                <div class="flex flex-col w-full min-w-0 sm:col-span-2 lg:col-span-1 xl:w-64 relative">
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
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Quick Reset Button -->
            <div class="flex items-center gap-2 shrink-0 self-stretch sm:self-end xl:self-end">
                <button
                    type="button"
                    onclick="resetChatSession()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
                    title="Mulai Sesi Obrolan Baru"
                >
                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                    <span>Sesi Baru</span>
                </button>
            </div>
        </div>

        <!-- Chat Main Interactive Area (Responsive Heights & Padding) -->
        <div class="flex flex-col h-[520px] sm:h-[600px] lg:h-[680px] bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl shadow-xs overflow-hidden w-full max-w-full">
            
            <!-- Message Stream Scrollable Box -->
            <div id="chat-messages-container" class="flex-1 p-3.5 sm:p-6 overflow-y-auto space-y-4 min-w-0 max-w-full">
                
                @if(!$chatSession || $chatSession->messages->isEmpty())
                    <!-- Empty State Welcome Hero -->
                    <div id="chat-empty-state" class="h-full flex flex-col items-center justify-center text-center p-4 sm:p-6 max-w-xl mx-auto my-auto">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-sky-400 text-white flex items-center justify-center shadow-lg shadow-blue-500/30 mb-3 sm:mb-4">
                            <i class="fa-solid fa-comments text-xl sm:text-2xl"></i>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                            Selamat Datang di AI Studio Chat
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md">
                            Pilih provider dan model AI di atas, lalu mulai ajukan pertanyaan akademik, telaah naskah, atau formulasi kerangka teori.
                        </p>

                        <!-- Suggestion Prompts Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 sm:mt-6 w-full text-left">
                            <button
                                type="button"
                                onclick="sendQuickPrompt('Bantu susun outline metodologi penelitian mixed-methods untuk studi kasus evaluasi implementasi sistem.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-blue-600 dark:text-blue-400 block group-hover:underline"><i class="fa-solid fa-ruler-combined mr-1"></i> Metodologi Riset</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Rancangan mixed-methods studi kasus implementasi.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Bagaimana cara merumuskan abstrak artikel jurnal yang ringkas, tajam, dan memuat IMRaD secara seimbang?')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 block group-hover:underline"><i class="fa-solid fa-file-lines mr-1"></i> Abstrak IMRaD</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Formulasi abstrak jurnal seimbang dan padat.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Beri contoh sitasi APA 7th edition dan IEEE untuk artikel jurnal dengan 3 penulis serta ada DOI.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-amber-600 dark:text-amber-400 block group-hover:underline"><i class="fa-solid fa-book-bookmark mr-1"></i> Standar Sitasi</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Contoh komparasi format sitasi APA 7th vs IEEE.</span>
                            </button>

                            <button
                                type="button"
                                onclick="sendQuickPrompt('Jelaskan apa saja kriteria novelty dan kontribusi empiris dalam penulisan tesis atau disertasi.')"
                                class="p-3 rounded-xl border border-blue-100 dark:border-blue-900/60 hover:border-blue-300 dark:hover:border-blue-700 bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/50 dark:hover:bg-blue-950/40 text-xs text-slate-700 dark:text-slate-300 transition text-left cursor-pointer group"
                            >
                                <span class="font-bold text-purple-600 dark:text-purple-400 block group-hover:underline"><i class="fa-solid fa-lightbulb mr-1"></i> Kebaruan / Novelty</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">Kriteria novelty &amp; kontribusi penelitian ilmiah.</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Render Existing Messages -->
                    @foreach($chatSession->messages as $msg)
                        <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }} message-item">
                            @if($msg->role === 'user')
                                <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl rounded-br-xs bg-blue-600 text-white p-3 sm:p-3.5 shadow-xs text-xs sm:text-sm break-words">
                                    <div class="whitespace-pre-wrap leading-relaxed">${escapeHtml($msg->content)}</div>
                                    <div class="text-[10px] text-blue-200 mt-1 text-right">{{ $msg->created_at->format('H:i') }}</div>
                                </div>
                            @else
                                <div class="max-w-[95%] sm:max-w-[82%] rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-3.5 sm:p-4 shadow-xs text-xs sm:text-sm text-slate-800 dark:text-slate-100 break-words min-w-0">
                                    <div class="flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-200 dark:border-slate-700 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1 truncate">
                                            <i class="fa-solid fa-microchip text-xs"></i>
                                            <span class="truncate">{{ $msg->model_used ?: 'Model AI' }}</span>
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
                                            <i class="fa-solid fa-copy text-xs"></i>
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
                    <div class="rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-3 sm:p-3.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin text-sm text-blue-600"></i>
                        <span id="chat-generating-text">Sedang memproses respon melalui model...</span>
                    </div>
                </div>

            </div>

            <!-- Chat Bottom Input Bar (Fully Responsive) -->
            <div class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-900 border-t border-blue-100 dark:border-blue-900/40">
                <form id="chat-form" onsubmit="handleSendChat(event)" class="relative flex flex-col sm:flex-row items-stretch sm:items-end gap-2">
                    <input type="hidden" id="chat-session-uuid" value="{{ $chatSession?->uuid }}">
                    
                    <div class="flex-1 min-w-0 relative">
                        <textarea
                            id="chat-user-input"
                            rows="2"
                            placeholder="Tulis pesan atau pertanyaan untuk model yang Anda pilih... (Tekan Enter untuk kirim, Shift+Enter untuk baris baru)"
                            class="w-full px-3.5 sm:px-4 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800/80 bg-white dark:bg-slate-800 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none transition"
                            onkeydown="handleInputKeydown(event)"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        id="btn-chat-send"
                        class="w-full sm:w-auto h-11 sm:h-10 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-sm shadow-blue-500/25 transition cursor-pointer shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span>Kirim</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>

                <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-1 text-[11px] text-slate-400 dark:text-slate-500 px-1">
                    <span id="chat-active-indicator" class="flex items-center gap-1.5 truncate max-w-full">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span id="chat-active-model-summary" class="truncate">Pilih model untuk mulai</span>
                    </span>
                    <span class="hidden sm:inline shrink-0">AI Studio v2.4 • Sahabat Penelitian</span>
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

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/60 text-left shadow-2xl transition-all w-full sm:max-w-xl max-h-[92vh] flex flex-col my-auto">
            
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 border-b border-blue-100 dark:border-blue-900/40 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <i class="fa-solid fa-microchip text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate" id="modal-provider-title">
                            Tambah AI Provider Baru
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Hubungkan penyedia LLM Anda untuk dipakai di sistem.</p>
                    </div>
                </div>
                <button type="button" onclick="closeProviderModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg shrink-0">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="form-provider" onsubmit="handleSaveProvider(event)" class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1">
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
                            <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
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
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
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
                            <i class="fa-solid fa-xmark text-xs"></i>
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
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-700 cursor-pointer"
                        checked
                    >
                    <label for="input-provider-active" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                        Aktifkan provider ini untuk digunakan langsung di Chat Studio
                    </label>
                </div>

                <!-- Modal Actions -->
                <div class="pt-3 border-t border-blue-100 dark:border-blue-900/40 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 shrink-0">
                    <button
                        type="button"
                        onclick="closeProviderModal()"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        id="btn-save-provider-submit"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition cursor-pointer text-center"
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
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="custom-delete-title"
>
    <!-- Invisible bridge for backwards compatibility with tests and scripts -->
    <div id="modal-delete-provider" class="hidden"></div>

    <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-7 border-2 border-rose-300 dark:border-rose-900/70 shadow-2xl space-y-4 sm:space-y-5 my-auto animate-in zoom-in-95 max-h-[92vh] overflow-y-auto">
        
        <!-- Red Danger Icon Badge & Heading -->
        <div class="text-center space-y-3">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 border-2 border-rose-200 dark:border-rose-800/60 flex items-center justify-center mx-auto shadow-md shadow-rose-500/10">
                <i class="fa-solid fa-trash-can text-2xl text-rose-600 dark:text-rose-400"></i>
            </div>
            <div>
                <h3 id="custom-delete-title" class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
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
            <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 shrink-0 text-sm mt-0.5"></i>
            <span>Tindakan ini permanen. Seluruh konfigurasi dan riwayat model terkait provider ini akan dihapus dari sistem.</span>
        </div>

        <!-- Action Buttons (Responsive Layout) -->
        <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
                type="button"
                onclick="closeCustomDeleteAlert()"
                class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-center"
            >
                Batal
            </button>
            <button
                type="button"
                id="btn-confirm-delete-action"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 border border-rose-500 shadow-lg shadow-rose-600/30 transition cursor-pointer flex items-center justify-center gap-1.5 text-center"
            >
                <i class="fa-solid fa-trash-can text-xs"></i>
                <span id="btn-confirm-delete-text">Ya, Hapus Sekarang</span>
            </button>
        </div>
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
                defBtn.innerHTML = isDef ? '<i class="fa-solid fa-star text-[9px] mr-1"></i> Utama' : 'Set Utama';
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
                label.innerHTML = m + (m === currentDefaultModel ? ' <i class="fa-solid fa-star text-[9px] text-blue-500 ml-0.5"></i>' : '');
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
                alert(`[Sukses] ${data.message}`);
                if (timestampSpan) {
                    timestampSpan.textContent = `Diuji: baru saja (${data.latency_ms} ms)`;
                }
            } else {
                alert(`[Gagal] ${data.message}`);
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

    const PROVIDER_STORAGE_KEY = 'sp_selected_providers';

    function getStoredProviders() {
        try {
            if (typeof window !== 'undefined' && window.sessionStorage) {
                const raw = sessionStorage.getItem(PROVIDER_STORAGE_KEY);
                return raw ? JSON.parse(raw) : {};
            }
        } catch (e) {}
        return {};
    }

    function saveStoredProviders(map) {
        try {
            if (typeof window !== 'undefined' && window.sessionStorage) {
                sessionStorage.setItem(PROVIDER_STORAGE_KEY, JSON.stringify(map));
            }
        } catch (e) {}
    }

    function clearStoredProviders() {
        try {
            if (typeof window !== 'undefined' && window.sessionStorage) {
                sessionStorage.removeItem(PROVIDER_STORAGE_KEY);
            }
        } catch (e) {}
    }

    function getSelectedProviderCheckboxes() {
        return Array.from(document.querySelectorAll('.provider-checkbox')).filter(cb => cb.checked);
    }

    function updateProviderSelectionUI() {
        const selectAllCheckbox = document.getElementById('select-all-providers');
        const rowCheckboxes = Array.from(document.querySelectorAll('.provider-checkbox'));
        const bulkBar = document.getElementById('bulk-actions-bar');
        const countLabel = document.getElementById('bulk-selected-count');
        const badgeLabel = document.getElementById('bulk-selected-badge');

        const stored = getStoredProviders();
        const totalStoredCount = Object.keys(stored).length;

        // Restore checkbox checked state based on storage
        rowCheckboxes.forEach(cb => {
            if (stored[cb.value]) {
                cb.checked = true;
            }
        });

        const pageSelectedCount = rowCheckboxes.filter(cb => cb.checked).length;
        const pageTotalCount = rowCheckboxes.length;

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
            if (pageTotalCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (pageSelectedCount === pageTotalCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (pageSelectedCount > 0 || totalStoredCount > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Bulk bar display showing cumulative count across pages
        if (bulkBar && countLabel && badgeLabel) {
            if (totalStoredCount > 0) {
                bulkBar.classList.remove('hidden');
                countLabel.textContent = totalStoredCount;
                badgeLabel.textContent = totalStoredCount;
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
            const stored = getStoredProviders();
            const items = Object.values(stored);
            if (items.length === 0) return;

            const container = document.getElementById('bulk-destroy-inputs-container');
            if (!container) return;
            container.innerHTML = '';

            items.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'uuids[]';
                input.value = item.uuid;
                container.appendChild(input);
            });

            clearStoredProviders();
            document.getElementById('form-bulk-destroy').submit();
        } else if (pendingDeleteMode === 'single' && pendingSingleUuid) {
            const stored = getStoredProviders();
            if (stored[pendingSingleUuid]) {
                delete stored[pendingSingleUuid];
                saveStoredProviders(stored);
            }
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
        const modelOptionsList = document.getElementById('chat-model-options-list');
        const searchContainer = document.getElementById('chat-model-search-container');
        const selectedModelText = document.getElementById('chat-model-selected-text');

        if (modelSelect) modelSelect.innerHTML = '';
        if (modelOptionsList) modelOptionsList.innerHTML = '';

        updateSelectedProviderDisplay();

        if (!providerSelect || providerSelect.options.length === 0 || !providerSelect.value) {
            if (modelSelect) {
                const opt = document.createElement('option');
                opt.textContent = 'Tidak ada model tersedia';
                modelSelect.appendChild(opt);
            }
            if (selectedModelText) {
                selectedModelText.innerHTML = '<span class="text-slate-400">Tidak ada model</span>';
            }
            if (searchContainer) {
                searchContainer.classList.add('hidden');
            }
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

        // Rule: "tapi kalau modelnya hanya 1 atau 2 maka hilangkan saja fitur searchnya."
        if (searchContainer) {
            if (models.length <= 2) {
                searchContainer.classList.add('hidden');
            } else {
                searchContainer.classList.remove('hidden');
            }
        }

        // Reset search input and empty state
        const searchInput = document.getElementById('chat-model-search-input');
        if (searchInput) searchInput.value = '';
        const emptyResult = document.getElementById('chat-model-empty-result');
        if (emptyResult) emptyResult.classList.add('hidden');

        let activeModel = '';

        if (models.length === 0) {
            activeModel = defaultModel || 'standard-model';
            if (modelSelect) {
                const opt = document.createElement('option');
                opt.value = activeModel;
                opt.textContent = activeModel;
                opt.selected = true;
                modelSelect.appendChild(opt);
            }
            if (modelOptionsList) {
                const li = document.createElement('li');
                li.className = 'chat-model-option-item p-2 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer flex items-center justify-between transition-colors bg-blue-50/80 dark:bg-blue-950/60 font-bold';
                li.setAttribute('data-model', activeModel);
                li.onclick = () => selectChatModelOption(activeModel);
                li.innerHTML = `
                    <div class="flex items-center gap-2 truncate font-mono">
                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                        <span class="chat-model-option-name truncate text-slate-900 dark:text-white">${escapeHtml(activeModel)}</span>
                    </div>
                `;
                modelOptionsList.appendChild(li);
            }
        } else {
            activeModel = (defaultModel && models.includes(defaultModel)) ? defaultModel : models[0];

            models.forEach(m => {
                const isSelected = (m === activeModel);

                // Populate native hidden select
                if (modelSelect) {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m + (m === defaultModel ? ' (Default)' : '');
                    if (isSelected) {
                        opt.selected = true;
                    }
                    modelSelect.appendChild(opt);
                }

                // Populate custom dropdown options list
                if (modelOptionsList) {
                    const li = document.createElement('li');
                    li.className = `chat-model-option-item p-2 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer flex items-center justify-between transition-colors ${isSelected ? 'bg-blue-50/80 dark:bg-blue-950/60 font-bold' : ''}`;
                    li.setAttribute('data-model', m);
                    li.onclick = () => selectChatModelOption(m);
                    li.innerHTML = `
                        <div class="flex items-center gap-2 truncate font-mono">
                            <span class="w-2 h-2 rounded-full ${isSelected ? 'bg-blue-500' : 'bg-slate-300 dark:bg-slate-600'} shrink-0"></span>
                            <span class="chat-model-option-name truncate text-slate-900 dark:text-white">${escapeHtml(m)}</span>
                        </div>
                        ${m === defaultModel ? '<span class="text-[10px] px-1.5 py-0.5 rounded font-normal bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 shrink-0 font-sans"><i class="fa-solid fa-star text-[9px] mr-1 text-blue-500"></i>Utama</span>' : ''}
                    `;
                    modelOptionsList.appendChild(li);
                }
            });
        }

        if (selectedModelText) {
            selectedModelText.innerHTML = `
                <i class="fa-solid fa-microchip text-xs text-blue-600 dark:text-blue-400 shrink-0"></i>
                <span class="truncate font-mono">${escapeHtml(activeModel)}</span>
            `;
        }

        updateModelSummary();
    }

    // Toggle Searchable Model Dropdown
    function toggleChatModelDropdown() {
        const dropdown = document.getElementById('chat-model-dropdown');
        const chevron = document.getElementById('chat-model-chevron');
        if (!dropdown) return;

        // Close provider dropdown if open
        const provDropdown = document.getElementById('chat-provider-dropdown');
        const provChevron = document.getElementById('chat-provider-chevron');
        if (provDropdown && !provDropdown.classList.contains('hidden')) {
            provDropdown.classList.add('hidden');
            if (provChevron) provChevron.classList.remove('rotate-180');
        }

        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            const searchContainer = document.getElementById('chat-model-search-container');
            const searchInput = document.getElementById('chat-model-search-input');
            if (searchInput && searchContainer && !searchContainer.classList.contains('hidden')) {
                searchInput.value = '';
                filterChatModelOptions();
                setTimeout(() => searchInput.focus(), 50);
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    // Select Model Option
    function selectChatModelOption(modelName) {
        const modelSelect = document.getElementById('chat-model-select');
        if (modelSelect) {
            modelSelect.value = modelName;
            modelSelect.dispatchEvent(new Event('change'));
        }

        const selectedModelText = document.getElementById('chat-model-selected-text');
        if (selectedModelText) {
            selectedModelText.innerHTML = `
                <i class="fa-solid fa-microchip text-xs text-blue-600 dark:text-blue-400 shrink-0"></i>
                <span class="truncate font-mono">${escapeHtml(modelName)}</span>
            `;
        }

        // Highlight active item
        document.querySelectorAll('#chat-model-options-list .chat-model-option-item').forEach(li => {
            const isMatch = (li.getAttribute('data-model') === modelName);
            const dot = li.querySelector('.rounded-full');
            if (isMatch) {
                li.classList.add('bg-blue-50/80', 'dark:bg-blue-950/60', 'font-bold');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-blue-500 shrink-0';
            } else {
                li.classList.remove('bg-blue-50/80', 'dark:bg-blue-950/60', 'font-bold');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600 shrink-0';
            }
        });

        const dropdown = document.getElementById('chat-model-dropdown');
        const chevron = document.getElementById('chat-model-chevron');
        if (dropdown) dropdown.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');

        updateModelSummary();
    }

    // Filter Model Options by search input
    function filterChatModelOptions() {
        const query = (document.getElementById('chat-model-search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('#chat-model-options-list .chat-model-option-item');
        let visibleCount = 0;

        items.forEach(item => {
            const model = (item.getAttribute('data-model') || '').toLowerCase();
            if (model.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const emptyMsg = document.getElementById('chat-model-empty-result');
        if (emptyMsg) {
            if (visibleCount === 0 && items.length > 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
            }
        }
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
                    content: '<i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1"></i> Gagal mendapatkan balasan dari model: ' + (data.message || 'Terjadi kesalahan sistem.'),
                    provider_name: 'Error Handler',
                    model_used: model,
                    created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                });
            }
        } catch (err) {
            typingIndicator.classList.add('hidden');
            appendMessageToUi({
                role: 'assistant',
                content: '<i class="fa-solid fa-triangle-exclamation text-rose-500 mr-1"></i> Kesalahan koneksi jaringan saat menghubungi server AI: ' + err.message,
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
                <div class="max-w-[85%] sm:max-w-[75%] rounded-2xl rounded-br-xs bg-blue-600 text-white p-3 sm:p-3.5 shadow-xs text-xs sm:text-sm break-words">
                    <div class="whitespace-pre-wrap leading-relaxed">${escapeHtml(msg.content)}</div>
                    <div class="text-[10px] text-blue-200 mt-1 text-right">${msg.created_at || 'Baru saja'}</div>
                </div>
            `;
        } else {
            wrapper.innerHTML = `
                <div class="max-w-[95%] sm:max-w-[82%] rounded-2xl rounded-bl-xs bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-3.5 sm:p-4 shadow-xs text-xs sm:text-sm text-slate-800 dark:text-slate-100 break-words min-w-0">
                    <div class="flex items-center gap-2 mb-2 pb-1.5 border-b border-slate-200 dark:border-slate-700 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1 truncate">
                            <i class="fa-solid fa-microchip text-xs"></i>
                            <span class="truncate">${escapeHtml(msg.model_used || 'Model AI')}</span>
                        </span>
                        <span>•</span>
                        <span class="truncate">${escapeHtml(msg.provider_name || 'AI Provider')}</span>
                    </div>
                    <div class="prose prose-xs dark:prose-invert max-w-none leading-relaxed ai-markdown-content" data-raw-content="${escapeHtml(msg.content)}">${msg.formatted_content || formatMarkdown(msg.content)}</div>
                    <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px] text-slate-400">
                        <span>${msg.created_at || 'Baru saja'}</span>
                        <button type="button" onclick="copyMessageContent(this)" class="hover:text-blue-500 flex items-center gap-1 cursor-pointer" title="Salin Jawaban">
                            <i class="fa-solid fa-copy text-xs"></i>
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
                <i class="fa-solid fa-check text-xs text-emerald-500 mr-1"></i>
                <span class="text-emerald-500">Tersalin!</span>
            `;
            setTimeout(() => {
                btn.innerHTML = `
                    <i class="fa-solid fa-copy text-xs mr-1"></i>
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
                const stored = getStoredProviders();
                rowCheckboxes.forEach(cb => {
                    cb.checked = targetState;
                    if (targetState) {
                        stored[cb.value] = {
                            uuid: cb.value,
                            name: cb.getAttribute('data-name') || cb.value
                        };
                    } else {
                        delete stored[cb.value];
                    }
                });
                saveStoredProviders(stored);
                updateProviderSelectionUI();
            });
        }

        // Individual Row Checkbox Handler
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const stored = getStoredProviders();
                if (cb.checked) {
                    stored[cb.value] = {
                        uuid: cb.value,
                        name: cb.getAttribute('data-name') || cb.value
                    };
                } else {
                    delete stored[cb.value];
                }
                saveStoredProviders(stored);
                updateProviderSelectionUI();
            });
        });

        // Clear Selection Handler
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                clearStoredProviders();
                rowCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
                updateProviderSelectionUI();
            });
        }

        // Bulk Destroy Action Handler
        if (bulkDestroyBtn) {
            bulkDestroyBtn.addEventListener('click', () => {
                const stored = getStoredProviders();
                const items = Object.values(stored);
                if (items.length === 0) return;

                pendingDeleteMode = 'bulk';
                pendingSingleUuid = null;

                const titleEl = document.getElementById('custom-delete-title');
                const subtitleEl = document.getElementById('custom-delete-subtitle');
                const labelEl = document.getElementById('custom-delete-target-label');
                const previewEl = document.getElementById('custom-delete-preview-content');
                const confirmBtnText = document.getElementById('btn-confirm-delete-text');

                if (titleEl) titleEl.textContent = `Hapus ${items.length} Provider Terpilih`;
                if (subtitleEl) subtitleEl.textContent = `Anda akan menghapus ${items.length} koneksi provider AI sekaligus.`;
                if (labelEl) labelEl.textContent = `Daftar ${items.length} Provider yang Akan Dihapus:`;

                if (previewEl) {
                    previewEl.innerHTML = '';
                    const ul = document.createElement('ul');
                    ul.className = 'list-disc list-inside space-y-1 text-slate-900 dark:text-slate-100 font-medium';
                    items.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item.name;
                        ul.appendChild(li);
                    });
                    previewEl.appendChild(ul);
                }

                if (confirmBtnText) confirmBtnText.textContent = `Ya, Hapus (${items.length}) Provider`;

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
                const provDropdown = document.getElementById('chat-provider-dropdown');
                const provChevron = document.getElementById('chat-provider-chevron');
                if (provDropdown && !provDropdown.classList.contains('hidden')) {
                    provDropdown.classList.add('hidden');
                    if (provChevron) provChevron.classList.remove('rotate-180');
                }
                const modelDropdown = document.getElementById('chat-model-dropdown');
                const modelChevron = document.getElementById('chat-model-chevron');
                if (modelDropdown && !modelDropdown.classList.contains('hidden')) {
                    modelDropdown.classList.add('hidden');
                    if (modelChevron) modelChevron.classList.remove('rotate-180');
                }
            }
        });

        // Close Searchable Dropdowns on Click Outside
        document.addEventListener('click', (e) => {
            const provWrapper = document.getElementById('chat-provider-searchable-wrapper');
            if (provWrapper && !provWrapper.contains(e.target)) {
                const provDropdown = document.getElementById('chat-provider-dropdown');
                const provChevron = document.getElementById('chat-provider-chevron');
                if (provDropdown && !provDropdown.classList.contains('hidden')) {
                    provDropdown.classList.add('hidden');
                    if (provChevron) provChevron.classList.remove('rotate-180');
                }
            }

            const modelWrapper = document.getElementById('chat-model-searchable-wrapper');
            if (modelWrapper && !modelWrapper.contains(e.target)) {
                const modelDropdown = document.getElementById('chat-model-dropdown');
                const modelChevron = document.getElementById('chat-model-chevron');
                if (modelDropdown && !modelDropdown.classList.contains('hidden')) {
                    modelDropdown.classList.add('hidden');
                    if (modelChevron) modelChevron.classList.remove('rotate-180');
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
