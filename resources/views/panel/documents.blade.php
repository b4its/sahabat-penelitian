@extends('layouts.panel')

@section('title', 'Dokumen & Naskah')
@section('breadcrumb', 'Manajemen Dokumen')

@section('content')
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
                class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2"
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
    <!-- 3. DOCUMENTS TABLE VIEW                                                   -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-blue-100 dark:border-blue-900/40 shadow-xs overflow-hidden">
        @if ($documents->count() > 0)
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-4">Judul Naskah Penelitian</th>
                            <th class="px-4 py-4">Versi</th>
                            <th class="px-4 py-4">Mode Akses</th>
                            <th class="px-4 py-4">Terakhir Diperbarui</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @foreach ($documents as $doc)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group">
                                <!-- Title & UUID -->
                                <td class="px-6 py-4">
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
                                            <span class="font-mono text-[10px] text-slate-400 dark:text-slate-500">
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
                                <td class="px-4 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                    {{ $doc->updated_at?->diffForHumans() ?? 'Baru saja' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit in Wordcraft -->
                                        <a
                                            href="{{ url('/editor?doc='.$doc->uuid) }}"
                                            class="p-2 rounded-lg text-blue-600 hover:text-white hover:bg-blue-600 transition-colors"
                                            title="Buka dan sunting di Wordcraft Studio"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>

                                        <!-- Duplicate Document -->
                                        <form action="{{ route('panel.documents.clone', $doc->uuid) }}" method="POST" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors"
                                                title="Duplikasi Naskah Ini"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </form>

                                        <!-- Delete Document -->
                                        <form action="{{ route('panel.documents.destroy', $doc->uuid) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus naskah {{ addslashes($doc->title) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition-colors"
                                                title="Hapus Naskah"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
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
@endsection
