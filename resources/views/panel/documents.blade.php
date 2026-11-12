@extends('layouts.panel')

@section('title', 'Dokumen & Naskah')
@section('breadcrumb', 'Manajemen Dokumen')

@section('content')
<style>
    /* Selected Document Row in Table - Light Mode */
    tr.doc-row-selected {
        background-color: #eff6ff !important;
        border-left: 4px solid #2563eb !important;
    }
    tr.doc-row-selected td {
        color: #0f172a !important;
    }
    tr.doc-row-selected td a {
        color: #1d4ed8 !important;
    }
    tr.doc-row-selected td .doc-uuid,
    tr.doc-row-selected td .doc-date {
        color: #475569 !important;
    }

    /* Selected Document Row in Table - Dark Mode (Rich Deep Navy Blue with High Contrast Pure White Text) */
    html.dark tr.doc-row-selected,
    .dark tr.doc-row-selected {
        background-color: rgba(30, 58, 138, 0.45) !important;
        border-left: 4px solid #38bdf8 !important;
    }
    html.dark tr.doc-row-selected:hover,
    .dark tr.doc-row-selected:hover {
        background-color: rgba(30, 58, 138, 0.6) !important;
    }
    html.dark tr.doc-row-selected td,
    .dark tr.doc-row-selected td {
        color: #f1f5f9 !important;
    }
    html.dark tr.doc-row-selected td a,
    .dark tr.doc-row-selected td a {
        color: #ffffff !important;
    }
    html.dark tr.doc-row-selected td a:hover,
    .dark tr.doc-row-selected td a:hover {
        color: #38bdf8 !important;
    }
    html.dark tr.doc-row-selected td .doc-uuid,
    .dark tr.doc-row-selected td .doc-uuid,
    html.dark tr.doc-row-selected td .doc-date,
    .dark tr.doc-row-selected td .doc-date {
        color: #cbd5e1 !important;
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

    /* Bulk Action Buttons */
    html.dark #btn-bulk-clone,
    .dark #btn-bulk-clone {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border: 1px solid #3b82f6 !important;
    }
    html.dark #btn-bulk-clone:hover,
    .dark #btn-bulk-clone:hover {
        background-color: #1d4ed8 !important;
    }
    #btn-bulk-destroy,
    html.dark #btn-bulk-destroy,
    .dark #btn-bulk-destroy {
        background-color: #e11d48 !important;
        color: #ffffff !important;
        border: 1px solid #f43f5e !important;
    }
    #btn-bulk-destroy:hover,
    html.dark #btn-bulk-destroy:hover,
    .dark #btn-bulk-destroy:hover {
        background-color: #be123c !important;
    }
</style>
<div class="space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. HEADER SECTION & QUICK ACTIONS                                        -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex flex-wrap items-center gap-2">
                <span>Manajemen Dokumen &amp; Naskah</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 shrink-0">
                    {{ $documents->total() }} Naskah
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola seluruh draf penelitian, artikel jurnal, skripsi, dan berkas kolaborasi ilmiah Anda.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button
                type="button"
                onclick="openCreateDocumentModal()"
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Buat Naskah Baru</span>
            </button>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 2. FILTER & SEARCH TOOLBAR                                                -->
    <!-- ========================================================================= -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search Input Form -->
        <form action="{{ route('panel.documents') }}" method="GET" class="flex-1 max-w-md relative">
            <input
                type="text"
                name="q"
                value="{{ $searchQuery }}"
                placeholder="Cari berdasarkan judul naskah..."
                class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            @if ($currentFilter !== 'all')
                <input type="hidden" name="status" value="{{ $currentFilter }}">
            @endif
        </form>

        <!-- Segmented Status Filter Buttons -->
        <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
            <a
                href="{{ route('panel.documents', ['q' => $searchQuery, 'status' => 'all']) }}"
                class="px-3 py-1.5 rounded-lg font-semibold transition-colors {{ $currentFilter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
            >
                Semua ({{ $totalCount }})
            </a>
            <a
                href="{{ route('panel.documents', ['q' => $searchQuery, 'status' => 'open']) }}"
                class="px-3 py-1.5 rounded-lg font-semibold transition-colors {{ $currentFilter === 'open' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
            >
                Terbuka
            </a>
            <a
                href="{{ route('panel.documents', ['q' => $searchQuery, 'status' => 'protected']) }}"
                class="px-3 py-1.5 rounded-lg font-semibold transition-colors {{ $currentFilter === 'protected' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
            >
                Terproteksi
            </a>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 3. MULTIPLE CHOICE BULK ACTION BAR                                        -->
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
                    <span id="bulk-selected-count" class="text-blue-600 dark:text-sky-400 font-extrabold">0</span> naskah dipilih
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

        <!-- Two Bulk Options: Duplikat & Hapus -->
        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
            <!-- Option 1: Duplikat (Bulk Clone) -->
            <button
                type="button"
                id="btn-bulk-clone"
                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 border border-blue-500 dark:border-blue-400 shadow-sm flex items-center justify-center gap-2 transition cursor-pointer hover:shadow"
                title="Duplikat seluruh naskah yang dipilih"
            >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Duplikat Terpilih</span>
            </button>

            <!-- Option 2: Hapus (Bulk Delete with Red/Rose Elements) -->
            <button
                type="button"
                id="btn-bulk-destroy"
                class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 dark:bg-rose-600 dark:hover:bg-rose-500 border border-rose-500 dark:border-rose-400 shadow-md shadow-rose-600/30 flex items-center justify-center gap-2 transition cursor-pointer hover:shadow-lg"
                title="Hapus naskah terpilih dari sistem"
            >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus Terpilih</span>
            </button>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 4. DOCUMENTS TABLE VIEW                                                   -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-blue-100 dark:border-blue-900/40 shadow-xs overflow-hidden">
        @if ($documents->count() > 0)
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <!-- Multiple Choice Header Checkbox -->
                            <th class="w-12 px-4 py-4 text-center">
                                <label for="select-all-documents" class="sr-only">Pilih Semua Dokumen</label>
                                <input
                                    type="checkbox"
                                    id="select-all-documents"
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-800 cursor-pointer"
                                    title="Pilih semua naskah pada halaman ini"
                                />
                            </th>
                            <th class="px-5 py-4">Judul Naskah Penelitian</th>
                            <th class="px-4 py-4">Versi</th>
                            <th class="px-4 py-4">Mode Akses</th>
                            <th class="px-4 py-4">Terakhir Diperbarui</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach ($documents as $doc)
                            <tr
                                id="doc-row-{{ $doc->uuid }}"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group"
                            >
                                <!-- Multiple Choice Row Checkbox -->
                                <td class="w-12 px-4 py-4 text-center">
                                    <label for="checkbox-doc-{{ $doc->uuid }}" class="sr-only">Pilih naskah {{ $doc->title }}</label>
                                    <input
                                        type="checkbox"
                                        id="checkbox-doc-{{ $doc->uuid }}"
                                        name="selected_uuids[]"
                                        value="{{ $doc->uuid }}"
                                        data-title="{{ $doc->title }}"
                                        class="doc-checkbox w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500 dark:bg-slate-800 cursor-pointer"
                                        aria-label="Pilih naskah {{ $doc->title }}"
                                    />
                                </td>

                                <!-- Title & UUID -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <a
                                                href="{{ url('/editor?doc='.$doc->uuid) }}"
                                                class="font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors truncate block max-w-md"
                                                title="{{ $doc->title }}"
                                            >
                                                {{ $doc->title }}
                                            </a>
                                            <span class="doc-uuid font-mono text-[10px] text-slate-400 dark:text-slate-400">
                                                UUID: {{ $doc->uuid }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Version -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-mono text-[11px] font-bold bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300">
                                        v{{ $doc->current_version }}
                                    </span>
                                </td>

                                <!-- Mode Akses -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if ($doc->restricted_mode === 'none' && empty($doc->password_hash))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Terbuka
                                        </span>
                                    @elseif ($doc->restricted_mode === 'read_only')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300">
                                            🔒 Read-Only
                                        </span>
                                    @elseif ($doc->restricted_mode === 'comments_only')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300">
                                            💬 Komentar
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300">
                                            🔑 Password
                                        </span>
                                    @endif
                                </td>

                                <!-- Updated At -->
                                <td class="doc-date px-4 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                    {{ $doc->updated_at?->diffForHumans() ?? 'Baru saja' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit in Wordcraft -->
                                        <a
                                            href="{{ url('/editor?doc='.$doc->uuid) }}"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-white hover:bg-blue-600 dark:hover:bg-blue-600 transition-colors"
                                            title="Buka dan sunting di Wordcraft Studio"
                                            aria-label="Sunting naskah {{ $doc->title }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>

                                        <!-- Duplicate Document (Single) -->
                                        <form action="{{ route('panel.documents.clone', $doc->uuid) }}" method="POST" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                                title="Duplikasi Naskah Ini"
                                                aria-label="Duplikasi naskah {{ $doc->title }}"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </form>

                                        <!-- Delete Document (Custom Themed Modal, No Default JS Confirm!) -->
                                        <button
                                            type="button"
                                            onclick="openSingleDeleteModal('{{ $doc->uuid }}', @js($doc->title))"
                                            class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                                            title="Hapus Naskah"
                                            aria-label="Hapus naskah {{ $doc->title }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <div class="text-slate-500 dark:text-slate-400 font-medium text-center sm:text-left shrink-0">
                    Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ $documents->firstItem() ?? 0 }}</span> sampai <span class="font-bold text-slate-800 dark:text-slate-200">{{ $documents->lastItem() ?? 0 }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ $documents->total() }}</span> naskah
                </div>
                @if ($documents->hasPages())
                    <div class="pagination-container w-full sm:w-auto flex justify-center sm:justify-end overflow-x-auto max-w-full py-0.5">
                        {{ $documents->links() }}
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-16 px-4 space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak Ada Dokumen yang Ditemukan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    @if ($searchQuery)
                        Tidak ada naskah yang cocok dengan kata kunci "{{ $searchQuery }}". Coba gunakan kata kunci lain atau reset filter.
                    @else
                        Belum ada naskah penelitian yang tersimpan. Mulai tulis artikel jurnal atau skripsi Anda sekarang.
                    @endif
                </p>
                <div class="pt-2">
                    @if ($searchQuery)
                        <a href="{{ route('panel.documents') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                            Reset Pencarian
                        </a>
                    @else
                        <button type="button" onclick="openCreateDocumentModal()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700">
                            Buat Naskah Baru
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- 5. HIDDEN FORM HANDLERS FOR BULK & SINGLE OPERATIONS                     -->
<!-- ========================================================================= -->

<!-- Bulk Clone Form -->
<form id="form-bulk-clone" action="{{ route('panel.documents.bulk-clone') }}" method="POST" class="hidden">
    @csrf
    <div id="bulk-clone-inputs-container"></div>
</form>

<!-- Bulk Destroy Form -->
<form id="form-bulk-destroy" action="{{ route('panel.documents.bulk-destroy') }}" method="POST" class="hidden">
    @csrf
    <div id="bulk-destroy-inputs-container"></div>
</form>

<!-- Single Destroy Form -->
<form id="form-single-destroy" action="" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>


<!-- ========================================================================= -->
<!-- 6. CUSTOM MODAL: KONFIRMASI HAPUS NASKAH (CUSTOM ALERT BERTEMA & UNSUR MERAH) -->
<!-- ========================================================================= -->
<div
    id="modal-custom-delete-alert"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="custom-delete-title"
>
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
                    Konfirmasi Hapus Naskah
                </h3>
                <p id="custom-delete-subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Pastikan Anda benar-benar ingin menghapus data naskah penelitian ini.
                </p>
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
            <span>Tindakan ini permanen. Seluruh snapshot riwayat versi dan perubahan pada berkas ini akan dihapus dari sistem.</span>
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
<!-- 7. CLIENT LOGIC: MULTIPLE CHOICE SELECTION & CUSTOM ALERT CONTROLLER      -->
<!-- ========================================================================= -->
<script>
    (function () {
        // State variables
        let pendingDeleteMode = null; // 'single' or 'bulk'
        let pendingSingleUuid = null;

        document.addEventListener('DOMContentLoaded', () => {
            const selectAllCheckbox = document.getElementById('select-all-documents');
            const rowCheckboxes = Array.from(document.querySelectorAll('.doc-checkbox'));
            const bulkBar = document.getElementById('bulk-actions-bar');
            const countLabel = document.getElementById('bulk-selected-count');
            const badgeLabel = document.getElementById('bulk-selected-badge');
            const clearBtn = document.getElementById('btn-clear-selection');
            const bulkCloneBtn = document.getElementById('btn-bulk-clone');
            const bulkDestroyBtn = document.getElementById('btn-bulk-destroy');
            const confirmDeleteBtn = document.getElementById('btn-confirm-delete-action');

            function getSelectedCheckboxes() {
                return rowCheckboxes.filter(cb => cb.checked);
            }

            function updateSelectionUI() {
                const selected = getSelectedCheckboxes();
                const total = rowCheckboxes.length;
                const count = selected.length;

                // Update row visual styling
                rowCheckboxes.forEach(cb => {
                    const row = document.getElementById(`doc-row-${cb.value}`);
                    if (row) {
                        if (cb.checked) {
                            row.classList.add('doc-row-selected');
                        } else {
                            row.classList.remove('doc-row-selected');
                        }
                    }
                });

                // Update select-all checkbox state
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

                // Update bulk actions bar
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

            // Header Select All Handler
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    const targetState = selectAllCheckbox.checked;
                    rowCheckboxes.forEach(cb => {
                        cb.checked = targetState;
                    });
                    updateSelectionUI();
                });
            }

            // Individual Row Checkbox Handler
            rowCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    updateSelectionUI();
                });
            });

            // Clear Selection Handler
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    rowCheckboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    updateSelectionUI();
                });
            }

            // Bulk Clone Action Handler
            if (bulkCloneBtn) {
                bulkCloneBtn.addEventListener('click', () => {
                    const selected = getSelectedCheckboxes();
                    if (selected.length === 0) return;

                    const container = document.getElementById('bulk-clone-inputs-container');
                    if (!container) return;
                    container.innerHTML = '';

                    selected.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'uuids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });

                    document.getElementById('form-bulk-clone').submit();
                });
            }

            // Bulk Destroy Action Handler (Opens Custom Alert Modal)
            if (bulkDestroyBtn) {
                bulkDestroyBtn.addEventListener('click', () => {
                    const selected = getSelectedCheckboxes();
                    if (selected.length === 0) return;

                    pendingDeleteMode = 'bulk';
                    pendingSingleUuid = null;

                    const titleEl = document.getElementById('custom-delete-title');
                    const subtitleEl = document.getElementById('custom-delete-subtitle');
                    const labelEl = document.getElementById('custom-delete-target-label');
                    const previewEl = document.getElementById('custom-delete-preview-content');
                    const confirmBtnText = document.getElementById('btn-confirm-delete-text');

                    if (titleEl) titleEl.textContent = `Hapus ${selected.length} Naskah Terpilih`;
                    if (subtitleEl) subtitleEl.textContent = `Anda akan menghapus ${selected.length} naskah penelitian sekaligus.`;
                    if (labelEl) labelEl.textContent = `Daftar ${selected.length} Naskah yang Akan Dihapus:`;

                    if (previewEl) {
                        previewEl.innerHTML = '';
                        const ul = document.createElement('ul');
                        ul.className = 'list-disc list-inside space-y-1 text-slate-900 dark:text-slate-100 font-medium';
                        selected.forEach(cb => {
                            const li = document.createElement('li');
                            li.textContent = cb.getAttribute('data-title') || cb.value;
                            ul.appendChild(li);
                        });
                        previewEl.appendChild(ul);
                    }

                    if (confirmBtnText) confirmBtnText.textContent = `Ya, Hapus (${selected.length}) Naskah`;

                    const modal = document.getElementById('modal-custom-delete-alert');
                    if (modal) modal.classList.remove('hidden');
                });
            }

            // Confirm Delete Action in Custom Alert Modal
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', () => {
                    if (pendingDeleteMode === 'bulk') {
                        const selected = getSelectedCheckboxes();
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
                            singleForm.action = `/panel/documents/${pendingSingleUuid}`;
                            singleForm.submit();
                        }
                    }
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

            // Close Custom Delete Alert on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeCustomDeleteAlert();
                }
            });
        });

        // Global functions for single deletion and modal close
        window.openSingleDeleteModal = function (uuid, title) {
            pendingDeleteMode = 'single';
            pendingSingleUuid = uuid;

            const titleEl = document.getElementById('custom-delete-title');
            const subtitleEl = document.getElementById('custom-delete-subtitle');
            const labelEl = document.getElementById('custom-delete-target-label');
            const previewEl = document.getElementById('custom-delete-preview-content');
            const confirmBtnText = document.getElementById('btn-confirm-delete-text');

            if (titleEl) titleEl.textContent = 'Konfirmasi Hapus Naskah';
            if (subtitleEl) subtitleEl.textContent = 'Naskah berikut akan dihapus secara permanen dari basis data.';
            if (labelEl) labelEl.textContent = 'Judul Naskah:';
            if (previewEl) {
                previewEl.innerHTML = `<span class="font-bold text-rose-900 dark:text-rose-100 text-sm">${title}</span><br><span class="font-mono text-[11px] text-slate-600 dark:text-slate-300 mt-1 block">UUID: ${uuid}</span>`;
            }
            if (confirmBtnText) confirmBtnText.textContent = 'Ya, Hapus Naskah Ini';

            const modal = document.getElementById('modal-custom-delete-alert');
            if (modal) modal.classList.remove('hidden');
        };

        window.closeCustomDeleteAlert = function () {
            pendingDeleteMode = null;
            pendingSingleUuid = null;
            const modal = document.getElementById('modal-custom-delete-alert');
            if (modal) modal.classList.add('hidden');
        };
    })();
</script>
@endsection
