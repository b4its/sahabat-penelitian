@extends('layouts.panel')

@section('title', 'Dashboard Riset')
@section('breadcrumb', 'Dashboard Riset')

@section('content')
<div class="space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. HERO WELCOME BANNER (ROYAL BLUE GRADIENT)                              -->
    <!-- ========================================================================= -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 p-6 sm:p-8 text-white shadow-xl shadow-blue-500/15">
        <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-sky-400/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                    🎓 Ruang Kerja Akademik Terpadu
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Selamat Datang di Panel Sahabat Penelitian
                </h1>
                <p class="text-xs sm:text-sm text-blue-100 leading-relaxed">
                    Tulis, kelola draf naskah, format bibliografi otomatis (APA 7th, IEEE), dan kolaborasikan riset Anda secara real-time dengan presisi format dokumen Open XML (<code class="text-sky-200 font-mono text-xs">.docx</code>) murni.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <button
                    type="button"
                    onclick="openCreateDocumentModal()"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold text-blue-900 bg-white hover:bg-blue-50 shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Naskah Baru</span>
                </button>

                <a
                    href="{{ url('/editor') }}"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-800/60 hover:bg-blue-800 border border-white/30 backdrop-blur-md transition-colors flex items-center gap-1.5"
                >
                    <span>Buka Editor A4</span>
                    <svg class="w-3.5 h-3.5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </a>
            </div>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 2. METRIC KPI CARDS (BENTO BLUE ACCENT)                                   -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Total Documents -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex items-center gap-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Naskah</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalDocuments }}</div>
                <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400">Tersimpan di Sistem</span>
            </div>
        </div>

        <!-- Total Versions -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex items-center gap-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Snapshot Versi</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ $totalVersions }}</div>
                <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">Riwayat Terlindungi</span>
            </div>
        </div>

        <!-- Protected Documents -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex items-center gap-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Proteksi Dokumen</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">{{ $protectedDocuments }}</div>
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">Enkripsi Sandi / Read-Only</span>
            </div>
        </div>

        <!-- Compliance & Integrity -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-blue-100 dark:border-blue-900/40 shadow-xs flex items-center gap-4 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Integritas Format</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">100%</div>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">OPC DOCX Valid</span>
            </div>
        </div>

    </div>


    <!-- ========================================================================= -->
    <!-- 3. TWO-COLUMN LAYOUT: RECENT DOCUMENTS & QUICK TOOLS / AUDIT              -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Naskah Penelitian Terbaru (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-blue-100 dark:border-blue-900/40 shadow-xs">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Naskah Penelitian Terbaru</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Dokumen yang paling sering dan terakhir Anda modifikasi.</p>
                        </div>
                    </div>

                    <a href="{{ route('panel.documents') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                        <span>Lihat Semua Dokumen</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>

                <!-- Document List -->
                @if ($recentDocuments->count() > 0)
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($recentDocuments as $doc)
                            <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group hover:bg-slate-50/70 dark:hover:bg-slate-800/40 p-2.5 rounded-2xl transition-colors">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ $doc->title }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            <span class="inline-flex items-center gap-1 font-mono text-[11px] text-blue-600 dark:text-blue-400">
                                                v{{ $doc->current_version }}
                                            </span>
                                            <span>•</span>
                                            <span>Diperbarui: {{ $doc->updated_at?->diffForHumans() ?? 'Baru saja' }}</span>
                                            <span>•</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $doc->restricted_mode === 'none' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' }}">
                                                {{ $doc->restricted_mode === 'none' ? 'Terbuka' : 'Terproteksi' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <a
                                        href="{{ url('/editor?doc='.$doc->uuid) }}"
                                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 transition-all flex items-center gap-1.5"
                                        title="Buka dan sunting naskah ini di Wordcraft Editor"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Sunting</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($recentDocuments->hasPages())
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">
                                Halaman <span class="font-bold text-slate-800 dark:text-slate-200">{{ $recentDocuments->currentPage() }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ $recentDocuments->lastPage() }}</span>
                            </span>
                            <div>
                                {{ $recentDocuments->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada naskah penelitian yang dibuat.</p>
                        <button type="button" onclick="openCreateDocumentModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700">
                            Buat Naskah Sekarang
                        </button>
                    </div>
                @endif
            </div>

            <!-- Publication Workflow Progress Strip -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-blue-100 dark:border-blue-900/40 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Status Kelengkapan Publikasi Nasional &amp; Internasional</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Standar pemenuhan naskah ilmiah sesuai panduan dewan editor.</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/70 px-2.5 py-1 rounded-full">
                        85% Siap Submit
                    </span>
                </div>

                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden flex">
                    <div class="bg-blue-600 h-2.5 rounded-l-full" style="width: 35%" title="Struktur Abstrak &amp; IMRAD"></div>
                    <div class="bg-indigo-500 h-2.5" style="width: 25%" title="Sitasi DOI &amp; Bibliografi"></div>
                    <div class="bg-sky-400 h-2.5" style="width: 15%" title="Uji Tata Bahasa PUEBI"></div>
                    <div class="bg-emerald-500 h-2.5 rounded-r-full" style="width: 10%" title="Format DOCX Terverifikasi"></div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-600"></span> IMRAD Terstruktur</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-500"></span> Sitasi APA/IEEE</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-sky-400"></span> Ejaan PUEBI</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Validasi DOCX</div>
                </div>
            </div>
        </div>


        <!-- Right Column: Quick Research Tools & Audit Trail (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Quick Tools Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-blue-100 dark:border-blue-900/40 shadow-xs space-y-4">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white font-bold text-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Pintasan Alat Penelitian</span>
                </div>

                <div class="space-y-2.5">
                    <a
                        href="{{ url('/editor') }}"
                        class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 border border-slate-100 dark:border-slate-800 flex items-center justify-between group transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs font-bold">
                                W
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Wordcraft Studio</h4>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Editor layout kertas A4 presisi</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transform group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <button
                        type="button"
                        onclick="openCreateDocumentModal()"
                        class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-blue-50/70 dark:hover:bg-blue-950/40 border border-slate-100 dark:border-slate-800 flex items-center justify-between group transition-colors text-left"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">
                                +
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Inisiasi Naskah Baru</h4>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Templat Jurnal, Tesis, atau Proposal</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transform group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
                </div>
            </div>

            <!-- Recent Audit Events Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-blue-100 dark:border-blue-900/40 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-slate-900 dark:text-white font-bold text-sm">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Aktivitas Riset Terkini</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Log Audit</span>
                </div>

                @if ($recentAudits->count() > 0)
                    <div class="space-y-3">
                        @foreach ($recentAudits as $audit)
                            <div class="flex items-start gap-3 text-xs">
                                <span class="w-2 h-2 rounded-full bg-blue-600 mt-1.5 shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                        {{ str_replace('_', ' ', ucfirst($audit->event_type)) }}
                                    </p>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                                        <span>Oleh: {{ $audit->actor ?? 'Sistem' }}</span>
                                        <span>•</span>
                                        <span>{{ $audit->created_at?->diffForHumans() ?? 'Baru saja' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($recentDocuments->hasPages())
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">
                                Halaman <span class="font-bold text-slate-800 dark:text-slate-200">{{ $recentDocuments->currentPage() }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ $recentDocuments->lastPage() }}</span>
                            </span>
                            <div>
                                {{ $recentDocuments->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center py-4">Belum ada aktivitas mutasi yang tercatat.</p>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
