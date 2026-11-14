@extends('layouts.panel')

@section('title', 'Personalisasi & Transparansi Model AI — Sahabat Penelitian')

@section('content')
<div class="space-y-6">

    <!-- Header Breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('panel.dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Panel</a>
                <span>/</span>
                <a href="{{ route('panel.ai-studio.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">AI Studio</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Personalisasi Model</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-sliders text-blue-600 dark:text-blue-400"></i>
                Personalisasi & Transparansi Model AI
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-3xl">
                Transparansi alokasi cerdas dalam penggunaan model. Sistem membagi alur kerja ke dalam dua peran utama: <strong class="text-purple-600 dark:text-purple-400">Frontier Model</strong> untuk penalaran mendalam dan <strong class="text-emerald-600 dark:text-emerald-400">Worker Model</strong> untuk kecepatan serta efisiensi eksekusi.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a
                href="{{ route('panel.ai-studio.index', ['tab' => 'connections']) }}"
                class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition flex items-center gap-1.5"
            >
                <i class="fa-solid fa-server text-slate-400"></i>
                <span>Koneksi Provider</span>
            </a>
            <a
                href="{{ route('panel.ai-studio.index', ['tab' => 'chat']) }}"
                class="px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
            >
                <i class="fa-solid fa-comments"></i>
                <span>Chat Studio</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-3 animate-in fade-in">
            <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-lg shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-3 animate-in fade-in">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 dark:text-rose-400 text-lg shrink-0"></i>
            <div>
                <strong class="block font-semibold">Terdapat kesalahan pengisian:</strong>
                <ul class="list-disc list-inside text-xs mt-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- SECTION 1: Pilih AI Provider dengan Searchable Select -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <label for="personalize-provider-search-trigger" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Langkah 1: Pilih AI Provider
                </label>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Pilih AI provider yang terhubung untuk mengonfigurasi dan melihat transparansi pembagian modelnya.
                </p>
            </div>

            <!-- Searchable Select Provider Dropdown -->
            <div class="w-full md:w-80 relative" id="personalize-provider-searchable-wrapper">
                <!-- Hidden Native Select -->
                <select id="personalize-provider-select" name="provider_select" class="hidden">
                    @forelse($providers as $p)
                        <option
                            value="{{ $p->uuid }}"
                            {{ ($selectedProvider && $selectedProvider->uuid === $p->uuid) ? 'selected' : '' }}
                            data-name="{{ $p->name }}"
                            data-label="{{ $p->provider_label }}"
                            data-badge="{{ $p->badge_color }}"
                            data-driver="{{ $p->provider }}"
                        >
                            {{ $p->name }} ({{ $p->provider_label }})
                        </option>
                    @empty
                        <option value="" disabled selected>Belum ada provider aktif</option>
                    @endforelse
                </select>

                <!-- Trigger Button -->
                <button
                    type="button"
                    id="personalize-provider-search-trigger"
                    onclick="togglePersonalizeProviderDropdown()"
                    class="w-full pl-3.5 pr-8 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 bg-slate-50 dark:bg-slate-800/90 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer flex items-center justify-between text-left shadow-xs transition hover:border-blue-300 dark:hover:border-blue-700"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                >
                    <span id="personalize-provider-selected-text" class="truncate flex items-center gap-2 font-semibold">
                        @if($selectedProvider)
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="truncate font-bold text-slate-900 dark:text-white">{{ $selectedProvider->name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-normal shrink-0 {{ $selectedProvider->badge_color }}">{{ $selectedProvider->provider_label }}</span>
                        @else
                            <span class="text-slate-400">Pilih AI Provider</span>
                        @endif
                    </span>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-500">
                        <i id="personalize-provider-chevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-150"></i>
                    </div>
                </button>

                <!-- Dropdown Search Panel -->
                <div
                    id="personalize-provider-dropdown"
                    class="hidden absolute z-50 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-2xl border border-blue-200 dark:border-blue-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95"
                >
                    <!-- Search Input Inside Select -->
                    <div class="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60">
                        <div class="relative">
                            <input
                                type="text"
                                id="personalize-provider-search-input"
                                placeholder="Cari AI provider..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                oninput="filterPersonalizeProviders()"
                                autocomplete="off"
                            />
                            <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-2.5 top-2.5 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- List of Providers -->
                    <ul
                        id="personalize-provider-options-list"
                        class="max-h-56 overflow-y-auto p-1.5 divide-y divide-slate-100 dark:divide-slate-800 text-xs"
                        role="listbox"
                    >
                        @forelse($providers as $p)
                            <li
                                class="personalize-provider-option-item p-2.5 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer flex items-center justify-between transition-colors {{ ($selectedProvider && $selectedProvider->uuid === $p->uuid) ? 'bg-blue-50/80 dark:bg-blue-950/60 font-bold' : '' }}"
                                data-uuid="{{ $p->uuid }}"
                                data-name="{{ $p->name }}"
                                data-label="{{ $p->provider_label }}"
                                data-badge="{{ $p->badge_color }}"
                                onclick="selectPersonalizeProvider('{{ $p->uuid }}')"
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

                    <!-- Empty state -->
                    <div id="personalize-provider-empty-result" class="hidden p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                        Provider tidak ditemukan
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($selectedProvider)
        <!-- SECTION 2: Daftar Model yang Terdaftar pada Provider Terpilih -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-blue-600 dark:text-blue-400"></i>
                        Model yang Terdaftar pada {{ $selectedProvider->name }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Berikut seluruh model yang terbaca dan tersedia untuk dialokasikan ke peran Frontier atau Worker.
                    </p>
                </div>
                <div class="text-xs text-slate-500 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 font-semibold">
                        <span id="registered-models-count">{{ is_array($selectedProvider->models) ? count($selectedProvider->models) : 0 }}</span> Model Aktif
                    </span>
                </div>
            </div>

            <!-- Model Tags/Badges Grid -->
            <div id="registered-models-container" class="flex flex-wrap gap-2.5">
                @if(is_array($selectedProvider->models) && count($selectedProvider->models) > 0)
                    @foreach($selectedProvider->models as $m)
                        @php
                            $isFrontier = ($selectedProvider->active_frontier_model === $m);
                            $isWorker = ($selectedProvider->active_worker_model === $m);
                        @endphp
                        <div
                            class="model-tag-card px-3 py-2 rounded-xl border flex items-center gap-2 transition-all {{ $isFrontier ? 'bg-purple-50 dark:bg-purple-950/40 border-purple-300 dark:border-purple-800 text-purple-900 dark:text-purple-200 shadow-xs ring-1 ring-purple-300 dark:ring-purple-700' : ($isWorker ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 shadow-xs ring-1 ring-emerald-300 dark:ring-emerald-700' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300') }}"
                            data-model-name="{{ $m }}"
                        >
                            <span class="font-mono text-xs font-bold">{{ $m }}</span>
                            @if($isFrontier)
                                <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-purple-200 dark:bg-purple-900 text-purple-800 dark:text-purple-200 flex items-center gap-1">
                                    <i class="fa-solid fa-brain text-[9px]"></i> Frontier
                                </span>
                            @elseif($isWorker)
                                <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-emerald-200 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 flex items-center gap-1">
                                    <i class="fa-solid fa-bolt text-[9px]"></i> Worker
                                </span>
                            @else
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                                    Tersedia
                                </span>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="w-full p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 dark:text-slate-500">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1.5"></i>
                        Belum ada model yang didaftarkan pada provider ini. Anda dapat mengedit provider di menu Koneksi Provider.
                    </div>
                @endif
            </div>
        </div>

        <!-- SECTION 3: Form Alokasi Frontier Model & Worker Model -->
        <form id="form-personalize" action="{{ route('panel.ai-studio.personalize.save') }}" method="POST">
            @csrf
            <input type="hidden" name="provider_uuid" id="input-provider-uuid" value="{{ $selectedProvider->uuid }}">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- CARD 1: FRONTIER MODEL -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border-2 border-purple-200 dark:border-purple-900/60 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -right-6 -bottom-6 opacity-5 pointer-events-none text-purple-600">
                        <i class="fa-solid fa-brain text-9xl"></i>
                    </div>

                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg shadow-xs">
                                    <i class="fa-solid fa-brain"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Frontier Model</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Penalaran & Analisis Mendalam (High Reasoning)</p>
                                </div>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                Job: Penalaran
                            </span>
                        </div>

                        <!-- Role Description -->
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                            Model dengan kapasitas parameter terbesar dan penalaran logis tertinggi. Bertanggung jawab atas sintesis konseptual, elaborasi teori, dan kritik metodologis.
                        </p>

                        <!-- Searchable Frontier Model Dropdown -->
                        <div class="mb-5 relative" id="frontier-model-searchable-wrapper">
                            <label for="frontier-model-search-trigger" class="block text-xs font-bold uppercase tracking-wider text-purple-900 dark:text-purple-300 mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-crosshairs text-purple-500"></i>
                                Pilih Model untuk Frontier Job:
                            </label>

                            <!-- Native Hidden Select for Form Submission and Test Compatibility -->
                            <select
                                id="frontier-model-select"
                                name="frontier_model"
                                class="hidden"
                                required
                            >
                                @if(is_array($selectedProvider->models))
                                    @foreach($selectedProvider->models as $m)
                                        <option value="{{ $m }}" {{ ($selectedProvider->active_frontier_model === $m) ? 'selected' : '' }}>
                                            {{ $m }} {{ ($selectedProvider->active_frontier_model === $m) ? '(Saat Ini)' : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="{{ $selectedProvider->default_model }}" selected>{{ $selectedProvider->default_model }}</option>
                                @endif
                            </select>

                            <!-- Searchable Trigger Button -->
                            <button
                                type="button"
                                id="frontier-model-search-trigger"
                                onclick="toggleFrontierModelDropdown()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-purple-300 dark:border-purple-700 bg-purple-50/50 dark:bg-purple-950/40 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none cursor-pointer flex items-center justify-between text-left shadow-xs transition hover:border-purple-400 dark:hover:border-purple-600"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                            >
                                <span id="frontier-model-selected-text" class="truncate flex items-center gap-2 font-mono font-bold">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                                    <span class="truncate">{{ $selectedProvider->active_frontier_model ?? 'Pilih Frontier Model' }}</span>
                                </span>
                                <div class="pointer-events-none flex items-center pl-2 text-purple-600 dark:text-purple-400">
                                    <i id="frontier-model-chevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-150"></i>
                                </div>
                            </button>

                            <!-- Searchable Dropdown Panel -->
                            <div
                                id="frontier-model-dropdown"
                                class="hidden absolute z-30 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-xl border border-purple-200 dark:border-purple-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95"
                            >
                                <!-- Search Input Inside Select -->
                                <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-purple-50/30 dark:bg-purple-950/20">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            id="frontier-model-search-input"
                                            placeholder="Cari model frontier..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-purple-200 dark:border-purple-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            oninput="filterFrontierModels()"
                                            autocomplete="off"
                                        />
                                        <i class="fa-solid fa-magnifying-glass text-purple-400 text-xs absolute left-2.5 top-2.5 pointer-events-none"></i>
                                    </div>
                                </div>

                                <!-- List of Models -->
                                <ul
                                    id="frontier-model-options-list"
                                    class="max-h-48 overflow-y-auto p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs font-mono"
                                    role="listbox"
                                >
                                    @if(is_array($selectedProvider->models))
                                        @foreach($selectedProvider->models as $m)
                                            <li
                                                class="frontier-model-option-item p-2.5 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-950/50 cursor-pointer flex items-center justify-between transition-colors {{ ($selectedProvider->active_frontier_model === $m) ? 'bg-purple-50 dark:bg-purple-950/60 font-bold text-purple-900 dark:text-purple-200' : 'text-slate-800 dark:text-slate-200' }}"
                                                data-model="{{ $m }}"
                                                onclick="selectFrontierModel('{{ $m }}')"
                                                role="option"
                                            >
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                                                    <span class="frontier-option-model-name truncate">{{ $m }}</span>
                                                </div>
                                                @if($selectedProvider->active_frontier_model === $m)
                                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-extrabold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                        Aktif
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>

                                <!-- Empty search result -->
                                <div id="frontier-model-empty-result" class="hidden p-3 text-center text-xs text-slate-400 dark:text-slate-500 font-sans">
                                    Model tidak ditemukan
                                </div>
                            </div>
                        </div>

                        <!-- Division of Labor Tasks list -->
                        <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Tugas yang Diarahkan ke Frontier:
                            </div>
                            @foreach($divisionOfLabor['frontier']['tasks'] as $t)
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-start gap-2.5 text-xs">
                                    <i class="fa-solid fa-circle-check text-purple-500 mt-0.5 shrink-0"></i>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $t['name'] }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">{{ $t['desc'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-purple-100 dark:border-purple-900/50 flex items-center justify-between text-[11px] text-purple-700 dark:text-purple-300 font-semibold">
                        <span><i class="fa-solid fa-microchip mr-1"></i> Mode: Deep Thinking</span>
                        <span>Prioritas: Akurasi & Bobot</span>
                    </div>
                </div>

                <!-- CARD 2: WORKER MODEL -->
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border-2 border-emerald-200 dark:border-emerald-900/60 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -right-6 -bottom-6 opacity-5 pointer-events-none text-emerald-600">
                        <i class="fa-solid fa-bolt-lightning text-9xl"></i>
                    </div>

                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg shadow-xs">
                                    <i class="fa-solid fa-bolt-lightning"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Worker Model</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Kecepatan & Presisi Eksekusi (High Speed)</p>
                                </div>
                            </div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                Job: Kecepatan
                            </span>
                        </div>

                        <!-- Role Description -->
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                            Model ramping dengan latensi instan (< 1s) dan konsumsi token minimal. Bertanggung jawab atas koreksi PUEBI, format bibliografi, parafrase cepat, dan ringkasan kilat.
                        </p>

                        <!-- Searchable Worker Model Dropdown -->
                        <div class="mb-5 relative" id="worker-model-searchable-wrapper">
                            <label for="worker-model-search-trigger" class="block text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300 mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-crosshairs text-emerald-500"></i>
                                Pilih Model untuk Worker Job:
                            </label>

                            <!-- Native Hidden Select for Form Submission and Test Compatibility -->
                            <select
                                id="worker-model-select"
                                name="worker_model"
                                class="hidden"
                                required
                            >
                                @if(is_array($selectedProvider->models))
                                    @foreach($selectedProvider->models as $m)
                                        <option value="{{ $m }}" {{ ($selectedProvider->active_worker_model === $m) ? 'selected' : '' }}>
                                            {{ $m }} {{ ($selectedProvider->active_worker_model === $m) ? '(Saat Ini)' : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="{{ $selectedProvider->default_model }}" selected>{{ $selectedProvider->default_model }}</option>
                                @endif
                            </select>

                            <!-- Searchable Trigger Button -->
                            <button
                                type="button"
                                id="worker-model-search-trigger"
                                onclick="toggleWorkerModelDropdown()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/40 text-xs sm:text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer flex items-center justify-between text-left shadow-xs transition hover:border-emerald-400 dark:hover:border-emerald-600"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                            >
                                <span id="worker-model-selected-text" class="truncate flex items-center gap-2 font-mono font-bold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="truncate">{{ $selectedProvider->active_worker_model ?? 'Pilih Worker Model' }}</span>
                                </span>
                                <div class="pointer-events-none flex items-center pl-2 text-emerald-600 dark:text-emerald-400">
                                    <i id="worker-model-chevron" class="fa-solid fa-chevron-down text-xs transition-transform duration-150"></i>
                                </div>
                            </button>

                            <!-- Searchable Dropdown Panel -->
                            <div
                                id="worker-model-dropdown"
                                class="hidden absolute z-30 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95"
                            >
                                <!-- Search Input Inside Select -->
                                <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-emerald-50/30 dark:bg-emerald-950/20">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            id="worker-model-search-input"
                                            placeholder="Cari model worker..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                            oninput="filterWorkerModels()"
                                            autocomplete="off"
                                        />
                                        <i class="fa-solid fa-magnifying-glass text-emerald-400 text-xs absolute left-2.5 top-2.5 pointer-events-none"></i>
                                    </div>
                                </div>

                                <!-- List of Models -->
                                <ul
                                    id="worker-model-options-list"
                                    class="max-h-48 overflow-y-auto p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs font-mono"
                                    role="listbox"
                                >
                                    @if(is_array($selectedProvider->models))
                                        @foreach($selectedProvider->models as $m)
                                            <li
                                                class="worker-model-option-item p-2.5 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/50 cursor-pointer flex items-center justify-between transition-colors {{ ($selectedProvider->active_worker_model === $m) ? 'bg-emerald-50 dark:bg-emerald-950/60 font-bold text-emerald-900 dark:text-emerald-200' : 'text-slate-800 dark:text-slate-200' }}"
                                                data-model="{{ $m }}"
                                                onclick="selectWorkerModel('{{ $m }}')"
                                                role="option"
                                            >
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                    <span class="worker-option-model-name truncate">{{ $m }}</span>
                                                </div>
                                                @if($selectedProvider->active_worker_model === $m)
                                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                                        Aktif
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>

                                <!-- Empty search result -->
                                <div id="worker-model-empty-result" class="hidden p-3 text-center text-xs text-slate-400 dark:text-slate-500 font-sans">
                                    Model tidak ditemukan
                                </div>
                            </div>
                        </div>

                        <!-- Division of Labor Tasks list -->
                        <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Tugas yang Diarahkan ke Worker:
                            </div>
                            @foreach($divisionOfLabor['worker']['tasks'] as $t)
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-start gap-2.5 text-xs">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 shrink-0"></i>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $t['name'] }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">{{ $t['desc'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">
                        <span><i class="fa-solid fa-gauge-high mr-1"></i> Mode: Turbo Stream</span>
                        <span>Prioritas: Responsif & Hemat Token</span>
                    </div>
                </div>

            </div>

            <!-- Save Action Bar -->
            <div class="mt-6 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-blue-600 dark:text-blue-400 text-sm"></i>
                    <span>Pengaturan personalisasi tersimpan secara persisten pada basis data AI Provider.</span>
                </div>
                <button
                    type="submit"
                    id="btn-save-personalize"
                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-98 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2 cursor-pointer"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Konfigurasi Personalisasi</span>
                </button>
            </div>
        </form>

        <!-- SECTION 4: Matriks Transparansi Pembagian Beban Model (Transparency Matrix) -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="mb-4">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-diagram-project text-blue-600 dark:text-blue-400"></i>
                    Matriks Transparansi Alokasi Komputasi
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Transparansi penuh bagaimana sistem mengarahkan setiap instruksi ke model yang paling optimal berdasarkan beban kerja.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/60 dark:bg-slate-800/40">
                            <th class="py-3 px-4">Alur / Fitur Sistem</th>
                            <th class="py-3 px-4">Peran Job</th>
                            <th class="py-3 px-4">Model Eksekutor</th>
                            <th class="py-3 px-4">Karakteristik Alokasi</th>
                            <th class="py-3 px-4">Alasan Transparansi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                <i class="fa-solid fa-lightbulb text-purple-500 mr-1.5"></i>
                                Brainstorming Ide & Kerangka Bab
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                                    Frontier Model
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-purple-700 dark:text-purple-400" id="matrix-frontier-model-1">
                                {{ $selectedProvider->active_frontier_model }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">Penalaran Tinggi, Konteks Luas</td>
                            <td class="py-3 px-4 text-slate-500">Menghindari halusinasi pada perumusan metodologi</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                <i class="fa-solid fa-pen-nib text-purple-500 mr-1.5"></i>
                                Elaborasi Argumen & Pembahasan
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                                    Frontier Model
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-purple-700 dark:text-purple-400" id="matrix-frontier-model-2">
                                {{ $selectedProvider->active_frontier_model }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">Sintesis Kritis, Koherensi Bab</td>
                            <td class="py-3 px-4 text-slate-500">Memerlukan penalaran logika akademik antar bab</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                <i class="fa-solid fa-screwdriver-wrench text-emerald-500 mr-1.5"></i>
                                Koreksi Ejaan & PUEBI / Gramatika
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    Worker Model
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-emerald-700 dark:text-emerald-400" id="matrix-worker-model-1">
                                {{ $selectedProvider->active_worker_model }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">Latensi Instan (&lt; 1s), Presisi Aturan</td>
                            <td class="py-3 px-4 text-slate-500">Tugas leksikal deterministik hemat komputasi</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                <i class="fa-solid fa-quote-right text-emerald-500 mr-1.5"></i>
                                Format Sitasi & Daftar Pustaka
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    Worker Model
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-emerald-700 dark:text-emerald-400" id="matrix-worker-model-2">
                                {{ $selectedProvider->active_worker_model }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">Format String & Regex Templating</td>
                            <td class="py-3 px-4 text-slate-500">Penataan APA/IEEE tidak membutuhkan parameter besar</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                                <i class="fa-solid fa-file-pen text-emerald-500 mr-1.5"></i>
                                Ringkasan Eksekutif & Parafrase Cepat
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    Worker Model
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-emerald-700 dark:text-emerald-400" id="matrix-worker-model-3">
                                {{ $selectedProvider->active_worker_model }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">Throughput Tinggi, Biaya Rendah</td>
                            <td class="py-3 px-4 text-slate-500">Memberikan respons cepat saat pengguna mengedit teks</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION 5: Simulasi Interaktif Alokasi Tugas (Interactive Dispatch Tester) -->
        <div class="p-6 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50/40 dark:from-slate-900 dark:to-slate-800/80 border border-blue-100 dark:border-blue-900/40 shadow-xs">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-1">
                <i class="fa-solid fa-vial-circle-check"></i>
                Simulator Transparansi Alokasi Tugas
            </div>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-2">
                Uji Coba Alokasi Permintaan Otomatis
            </h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mb-4 max-w-2xl">
                Ketik atau pilih contoh instruksi di bawah ini untuk melihat bagaimana sistem mengklasifikasikan dan merutekan tugas ke model yang tepat secara transparan.
            </p>

            <!-- Sample Action Buttons -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs text-slate-500 font-semibold">Coba Skenario:</span>
                <button
                    type="button"
                    onclick="simulateRouting('Kembangkan hipotesis alternatif untuk variabel terikat pada bab 4')"
                    class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                >
                    <i class="fa-solid fa-brain text-purple-500 mr-1"></i> Evaluasi Hipotesis
                </button>
                <button
                    type="button"
                    onclick="simulateRouting('Perbaiki ejaan kata analisa, sistim, dan jadual')"
                    class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                >
                    <i class="fa-solid fa-bolt text-emerald-500 mr-1"></i> Periksa PUEBI
                </button>
                <button
                    type="button"
                    onclick="simulateRouting('Format daftar pustaka jurnal IEEE dari data kutipan')"
                    class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                >
                    <i class="fa-solid fa-bolt text-emerald-500 mr-1"></i> Format Sitasi IEEE
                </button>
                <button
                    type="button"
                    onclick="simulateRouting('Sintesis hubungan antara teori disrupsi inovasi dengan temuan lapangan')"
                    class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                >
                    <i class="fa-solid fa-brain text-purple-500 mr-1"></i> Sintesis Teori
                </button>
            </div>

            <!-- Simulator Input & Result -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <input
                    type="text"
                    id="simulator-input"
                    placeholder="Ketik instruksi naskah (misal: 'Ringkas bagian kesimpulan')..."
                    class="flex-1 w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onkeydown="if(event.key==='Enter') simulateRouting(this.value)"
                />
                <button
                    type="button"
                    onclick="simulateRouting(document.getElementById('simulator-input').value)"
                    class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer"
                >
                    <i class="fa-solid fa-play text-[10px]"></i>
                    <span>Analisis Alokasi</span>
                </button>
            </div>

            <!-- Result Box -->
            <div id="simulator-result" class="hidden mt-3 p-3.5 rounded-xl border text-xs animate-in fade-in">
                <!-- Dynamically populated -->
            </div>
        </div>

    @else
        <!-- Empty State if no active provider -->
        <div class="p-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-server"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum Ada Provider Aktif</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">
                Silakan tambahkan atau aktifkan setidaknya satu AI Provider untuk mulai mengonfigurasi personalisasi Frontier dan Worker model.
            </p>
            <a
                href="{{ route('panel.ai-studio.index', ['tab' => 'connections']) }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Buka Koneksi Provider</span>
            </a>
        </div>
    @endif

</div>

<!-- Client-side Interactive Search & Selection Logic -->
<script>
    // Toggle Searchable Provider Dropdown
    function togglePersonalizeProviderDropdown() {
        const dropdown = document.getElementById('personalize-provider-dropdown');
        const chevron = document.getElementById('personalize-provider-chevron');
        const trigger = document.getElementById('personalize-provider-search-trigger');
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
            const searchInput = document.getElementById('personalize-provider-search-input');
            if (searchInput) {
                searchInput.value = '';
                filterPersonalizeProviders();
                setTimeout(() => searchInput.focus(), 50);
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }
    }

    // Filter providers based on search query
    function filterPersonalizeProviders() {
        const query = (document.getElementById('personalize-provider-search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('#personalize-provider-options-list .personalize-provider-option-item');
        const emptyResult = document.getElementById('personalize-provider-empty-result');
        let visibleCount = 0;

        items.forEach(item => {
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            const label = (item.getAttribute('data-label') || '').toLowerCase();
            if (!query || name.includes(query) || label.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (emptyResult) {
            if (visibleCount === 0) {
                emptyResult.classList.remove('hidden');
            } else {
                emptyResult.classList.add('hidden');
            }
        }
    }

    // Select provider from dropdown
    function selectPersonalizeProvider(uuid) {
        const select = document.getElementById('personalize-provider-select');
        if (select) {
            select.value = uuid;
        }

        // Navigate to the selected provider page
        const url = new URL(window.location.href);
        url.searchParams.set('provider', uuid);
        window.location.href = url.toString();
    }

    // Frontier Model Searchable Dropdown
    function toggleFrontierModelDropdown() {
        const dropdown = document.getElementById('frontier-model-dropdown');
        const chevron = document.getElementById('frontier-model-chevron');
        const trigger = document.getElementById('frontier-model-search-trigger');
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
            const searchInput = document.getElementById('frontier-model-search-input');
            if (searchInput) {
                searchInput.value = '';
                filterFrontierModels();
                setTimeout(() => searchInput.focus(), 50);
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }
    }

    function filterFrontierModels() {
        const query = (document.getElementById('frontier-model-search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('#frontier-model-options-list .frontier-model-option-item');
        const emptyResult = document.getElementById('frontier-model-empty-result');
        let visibleCount = 0;

        items.forEach(item => {
            const model = (item.getAttribute('data-model') || '').toLowerCase();
            if (!query || model.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (emptyResult) {
            if (visibleCount === 0) {
                emptyResult.classList.remove('hidden');
            } else {
                emptyResult.classList.add('hidden');
            }
        }
    }

    function selectFrontierModel(modelName) {
        const select = document.getElementById('frontier-model-select');
        if (select) {
            select.value = modelName;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const selectedText = document.getElementById('frontier-model-selected-text');
        if (selectedText) {
            selectedText.innerHTML = `
                <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                <span class="truncate">${escapeHtml(modelName)}</span>
            `;
        }

        // Update Matrix displays
        const m1 = document.getElementById('matrix-frontier-model-1');
        const m2 = document.getElementById('matrix-frontier-model-2');
        if (m1) m1.textContent = modelName;
        if (m2) m2.textContent = modelName;

        // Highlight active item
        document.querySelectorAll('#frontier-model-options-list .frontier-model-option-item').forEach(item => {
            const isActive = item.getAttribute('data-model') === modelName;
            item.classList.toggle('bg-purple-50', isActive);
            item.classList.toggle('dark:bg-purple-950/60', isActive);
            item.classList.toggle('font-bold', isActive);
            item.classList.toggle('text-purple-900', isActive);
            item.classList.toggle('dark:text-purple-200', isActive);
        });

        toggleFrontierModelDropdown();
    }

    // Worker Model Searchable Dropdown
    function toggleWorkerModelDropdown() {
        const dropdown = document.getElementById('worker-model-dropdown');
        const chevron = document.getElementById('worker-model-chevron');
        const trigger = document.getElementById('worker-model-search-trigger');
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            dropdown.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
            const searchInput = document.getElementById('worker-model-search-input');
            if (searchInput) {
                searchInput.value = '';
                filterWorkerModels();
                setTimeout(() => searchInput.focus(), 50);
            }
        } else {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }
    }

    function filterWorkerModels() {
        const query = (document.getElementById('worker-model-search-input')?.value || '').toLowerCase().trim();
        const items = document.querySelectorAll('#worker-model-options-list .worker-model-option-item');
        const emptyResult = document.getElementById('worker-model-empty-result');
        let visibleCount = 0;

        items.forEach(item => {
            const model = (item.getAttribute('data-model') || '').toLowerCase();
            if (!query || model.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (emptyResult) {
            if (visibleCount === 0) {
                emptyResult.classList.remove('hidden');
            } else {
                emptyResult.classList.add('hidden');
            }
        }
    }

    function selectWorkerModel(modelName) {
        const select = document.getElementById('worker-model-select');
        if (select) {
            select.value = modelName;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        const selectedText = document.getElementById('worker-model-selected-text');
        if (selectedText) {
            selectedText.innerHTML = `
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate">${escapeHtml(modelName)}</span>
            `;
        }

        // Update Matrix displays
        const m1 = document.getElementById('matrix-worker-model-1');
        const m2 = document.getElementById('matrix-worker-model-2');
        const m3 = document.getElementById('matrix-worker-model-3');
        if (m1) m1.textContent = modelName;
        if (m2) m2.textContent = modelName;
        if (m3) m3.textContent = modelName;

        // Highlight active item
        document.querySelectorAll('#worker-model-options-list .worker-model-option-item').forEach(item => {
            const isActive = item.getAttribute('data-model') === modelName;
            item.classList.toggle('bg-emerald-50', isActive);
            item.classList.toggle('dark:bg-emerald-950/60', isActive);
            item.classList.toggle('font-bold', isActive);
            item.classList.toggle('text-emerald-900', isActive);
            item.classList.toggle('dark:text-emerald-200', isActive);
        });

        toggleWorkerModelDropdown();
    }

    // Dynamic asynchronous model loader
    async function loadProviderModelsAsync(uuid) {
        try {
            const res = await fetch(`/panel/ai-studio/providers/${uuid}/models`);
            if (!res.ok) return;
            const data = await res.json();

            const inputUuid = document.getElementById('input-provider-uuid');
            if (inputUuid) inputUuid.value = data.uuid;

            const frontierSelect = document.getElementById('frontier-model-select');
            const workerSelect = document.getElementById('worker-model-select');
            const frontierList = document.getElementById('frontier-model-options-list');
            const workerList = document.getElementById('worker-model-options-list');

            if (frontierSelect && workerSelect && Array.isArray(data.models)) {
                frontierSelect.innerHTML = '';
                workerSelect.innerHTML = '';
                if (frontierList) frontierList.innerHTML = '';
                if (workerList) workerList.innerHTML = '';

                data.models.forEach(m => {
                    const optF = document.createElement('option');
                    optF.value = m;
                    optF.textContent = m + (m === data.frontier_model ? ' (Saat Ini)' : '');
                    if (m === data.frontier_model) optF.selected = true;
                    frontierSelect.appendChild(optF);

                    const optW = document.createElement('option');
                    optW.value = m;
                    optW.textContent = m + (m === data.worker_model ? ' (Saat Ini)' : '');
                    if (m === data.worker_model) optW.selected = true;
                    workerSelect.appendChild(optW);

                    if (frontierList) {
                        const liF = document.createElement('li');
                        liF.className = `frontier-model-option-item p-2.5 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-950/50 cursor-pointer flex items-center justify-between transition-colors ${m === data.frontier_model ? 'bg-purple-50 dark:bg-purple-950/60 font-bold text-purple-900 dark:text-purple-200' : 'text-slate-800 dark:text-slate-200'}`;
                        liF.setAttribute('data-model', m);
                        liF.setAttribute('role', 'option');
                        liF.onclick = () => selectFrontierModel(m);
                        liF.innerHTML = `
                            <div class="flex items-center gap-2 truncate">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                                <span class="frontier-option-model-name truncate">${escapeHtml(m)}</span>
                            </div>
                            ${m === data.frontier_model ? '<span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-extrabold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Aktif</span>' : ''}
                        `;
                        frontierList.appendChild(liF);
                    }

                    if (workerList) {
                        const liW = document.createElement('li');
                        liW.className = `worker-model-option-item p-2.5 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/50 cursor-pointer flex items-center justify-between transition-colors ${m === data.worker_model ? 'bg-emerald-50 dark:bg-emerald-950/60 font-bold text-emerald-900 dark:text-emerald-200' : 'text-slate-800 dark:text-slate-200'}`;
                        liW.setAttribute('data-model', m);
                        liW.setAttribute('role', 'option');
                        liW.onclick = () => selectWorkerModel(m);
                        liW.innerHTML = `
                            <div class="flex items-center gap-2 truncate">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="worker-option-model-name truncate">${escapeHtml(m)}</span>
                            </div>
                            ${m === data.worker_model ? '<span class="text-[10px] px-1.5 py-0.2 rounded font-sans font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Aktif</span>' : ''}
                        `;
                        workerList.appendChild(liW);
                    }
                });

                const frontierText = document.getElementById('frontier-model-selected-text');
                if (frontierText && data.frontier_model) {
                    frontierText.innerHTML = `
                        <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                        <span class="truncate">${escapeHtml(data.frontier_model)}</span>
                    `;
                }

                const workerText = document.getElementById('worker-model-selected-text');
                if (workerText && data.worker_model) {
                    workerText.innerHTML = `
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="truncate">${escapeHtml(data.worker_model)}</span>
                    `;
                }
            }
        } catch (e) {
            console.error('Failed to load provider models async', e);
        }
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        // Provider Dropdown
        const wrapper = document.getElementById('personalize-provider-searchable-wrapper');
        const dropdown = document.getElementById('personalize-provider-dropdown');
        const chevron = document.getElementById('personalize-provider-chevron');
        const trigger = document.getElementById('personalize-provider-search-trigger');
        if (wrapper && !wrapper.contains(e.target) && dropdown && !dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }

        // Frontier Model Dropdown
        const frontierWrapper = document.getElementById('frontier-model-searchable-wrapper');
        const frontierDropdown = document.getElementById('frontier-model-dropdown');
        const frontierChevron = document.getElementById('frontier-model-chevron');
        const frontierTrigger = document.getElementById('frontier-model-search-trigger');
        if (frontierWrapper && !frontierWrapper.contains(e.target) && frontierDropdown && !frontierDropdown.classList.contains('hidden')) {
            frontierDropdown.classList.add('hidden');
            if (frontierChevron) frontierChevron.classList.remove('rotate-180');
            if (frontierTrigger) frontierTrigger.setAttribute('aria-expanded', 'false');
        }

        // Worker Model Dropdown
        const workerWrapper = document.getElementById('worker-model-searchable-wrapper');
        const workerDropdown = document.getElementById('worker-model-dropdown');
        const workerChevron = document.getElementById('worker-model-chevron');
        const workerTrigger = document.getElementById('worker-model-search-trigger');
        if (workerWrapper && !workerWrapper.contains(e.target) && workerDropdown && !workerDropdown.classList.contains('hidden')) {
            workerDropdown.classList.add('hidden');
            if (workerChevron) workerChevron.classList.remove('rotate-180');
            if (workerTrigger) workerTrigger.setAttribute('aria-expanded', 'false');
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['personalize-provider', 'frontier-model', 'worker-model'].forEach(prefix => {
                const dropdown = document.getElementById(prefix + '-dropdown');
                const chevron = document.getElementById(prefix + '-chevron');
                const trigger = document.getElementById(prefix + '-search-trigger');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                    if (trigger) trigger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });

    // Interactive Dispatch Simulator
    function simulateRouting(query) {
        if (!query || !query.trim()) return;
        document.getElementById('simulator-input').value = query;

        const q = query.toLowerCase();
        const frontierKeywords = ['hipotesis', 'teori', 'sintesis', 'brainstorm', 'kritis', 'analisis', 'bab', 'metode', 'argumen', 'kerangka', 'evaluasi', 'rekomendasi'];
        const isFrontier = frontierKeywords.some(kw => q.includes(kw));

        const frontierSelect = document.getElementById('frontier-model-select');
        const workerSelect = document.getElementById('worker-model-select');
        const frontierModel = frontierSelect ? frontierSelect.value : 'Frontier Model';
        const workerModel = workerSelect ? workerSelect.value : 'Worker Model';

        const resultBox = document.getElementById('simulator-result');
        resultBox.classList.remove('hidden');

        if (isFrontier) {
            resultBox.className = 'mt-3 p-4 rounded-xl border bg-purple-50 dark:bg-purple-950/40 border-purple-200 dark:border-purple-800 text-purple-900 dark:text-purple-200 animate-in fade-in';
            resultBox.innerHTML = `
                <div class="flex items-start gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-200 dark:bg-purple-900 text-purple-700 dark:text-purple-300 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-brain"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-xs uppercase px-2 py-0.5 rounded bg-purple-200 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                Dialihkan ke: Frontier Model
                            </span>
                            <span class="font-mono text-xs font-bold text-purple-700 dark:text-purple-300">
                                (${escapeHtml(frontierModel)})
                            </span>
                        </div>
                        <p class="text-xs text-purple-800 dark:text-purple-300 mt-1 leading-relaxed">
                            Permintaan ini membutuhkan penalaran analitis tinggi dan evaluasi konseptual mendalam. Sistem menggunakan <strong>${escapeHtml(frontierModel)}</strong> untuk menjamin kualitas argumen ilmiah tanpa halusinasi.
                        </p>
                    </div>
                </div>
            `;
        } else {
            resultBox.className = 'mt-3 p-4 rounded-xl border bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 animate-in fade-in';
            resultBox.innerHTML = `
                <div class="flex items-start gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-200 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-bolt-lightning"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-xs uppercase px-2 py-0.5 rounded bg-emerald-200 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200">
                                Dialihkan ke: Worker Model
                            </span>
                            <span class="font-mono text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                (${escapeHtml(workerModel)})
                            </span>
                        </div>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-1 leading-relaxed">
                            Permintaan ini bersifat formatif / deterministik berkecepatan tinggi. Sistem menggunakan <strong>${escapeHtml(workerModel)}</strong> untuk memberikan eksekusi instan (&lt; 1 detik) dengan penghematan token komputasi.
                        </p>
                    </div>
                </div>
            `;
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
@endsection
