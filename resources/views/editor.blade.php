<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>VelScribe Wordcraft Studio — Word & Document Editor Cerdas</title>

    <script>
        (function() {
            try {
                var stored = localStorage.getItem('velscribe_theme');
                var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && systemDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    @fonts

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Smooth Theme Transition Class */
        html.theme-transitioning,
        html.theme-transitioning *,
        html.theme-transitioning *:before,
        html.theme-transitioning *:after {
            transition: background-color 0.25s ease-out, border-color 0.25s ease-out, color 0.15s ease-out !important;
            transition-delay: 0s !important;
        }

        /* A4 Page View Simulation (Single & Multi-Page Sheets) */
        #document-page-canvas,
        .document-page-sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 25.4mm;
            margin: 18px auto;
            background: #ffffff;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.12), 0 1px 4px rgba(0, 0, 0, 0.06);
            transition: transform 0.15s ease-out, padding 0.2s ease, width 0.2s ease;
            box-sizing: border-box;
            border-radius: 2px;
            position: relative;
        }

        #document-page-canvas.page-landscape,
        .document-page-sheet.page-landscape {
            width: 297mm !important;
            min-height: 210mm !important;
        }

        /* Dark Sheet Mode (When explicitly toggled by user) */
        #document-page-canvas.canvas-dark-sheet,
        .document-page-sheet.canvas-dark-sheet {
            background: #18181b !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.6) !important;
        }

        .document-page-sheet.canvas-dark-sheet .page-sheet-body {
            color: #f3f4f6 !important;
        }

        .document-page-sheet.canvas-dark-sheet .page-sheet-body *:not([style*="color:"]) {
            color: #f3f4f6 !important;
        }

        /* High-contrast Print Paper Typography on Standard White Document Sheets */
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body {
            color: #111827 !important;
        }

        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body *:not([style*="color:"]) {
            color: inherit !important;
        }

        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body p,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h1,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h2,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h3,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h4,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h5,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body h6,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body div:not([style*="color:"]),
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body span:not([style*="color:"]),
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body td,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body th,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body li,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body strong,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body b,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body u,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body s {
            color: #111827 !important;
        }

        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .text-gray-700,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .text-gray-600,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-author-label,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-subtitle {
            color: #374151 !important;
        }

        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-title,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-badge,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-author-nim,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .cover-institution,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .heading-chapter,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .heading-section,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .heading-subsection,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .heading-preliminary {
            color: #111827 !important;
        }

        /* Prevent washed out / invisible white text on white paper sheets */
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color: #ffffff"],
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color: #fff"],
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color: white"],
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color: rgb(255, 255, 255)"],
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color:#ffffff"],
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body [style*="color:#fff"] {
            color: #111827 !important;
        }

        /* Page Sheet Gap / Margin */
        .page-sheet-gap {
            width: 210mm;
            max-width: 100%;
            margin: 14px auto;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .page-sheet-gap::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(156, 163, 175, 0.35);
            border-bottom: 1px dashed rgba(156, 163, 175, 0.35);
        }

        .page-gap-indicator {
            position: relative;
            z-index: 1;
            background: #e5e7eb;
            color: #4b5563;
            padding: 3px 14px;
            border-radius: 9999px;
            font-size: 10px;
            font-family: ui-monospace, monospace;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(209, 213, 219, 0.6);
        }

        .dark .page-gap-indicator {
            background: #1f2937;
            color: #9ca3af;
            border-color: #374151;
        }

        /* Page Sheet Header & Footer */
        .page-sheet-header {
            font-size: 10pt;
            color: #9ca3af;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .page-sheet-footer {
            font-size: 10pt;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 8px;
            margin-top: 24px;
        }

        /* Editor Typography & Styling */
        #document-editor,
        .page-sheet-body {
            outline: none;
            min-height: 200mm;
            font-family: 'Times New Roman', 'Liberation Serif', 'Nimbus Roman', 'DejaVu Serif', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #111827;
        }

        .document-page-sheet .page-sheet-body[style*="Times New Roman"],
        .document-page-sheet .page-sheet-body [style*="Times New Roman"] {
            font-family: 'Times New Roman', 'Liberation Serif', 'Nimbus Roman', 'DejaVu Serif', serif !important;
        }

        .document-page-sheet .page-sheet-body[style*="Calibri"],
        .document-page-sheet .page-sheet-body [style*="Calibri"] {
            font-family: 'Calibri', 'Liberation Sans', 'DejaVu Sans', sans-serif !important;
        }

        .document-page-sheet .page-sheet-body[style*="Arial"],
        .document-page-sheet .page-sheet-body [style*="Arial"] {
            font-family: 'Arial', 'Liberation Sans', 'DejaVu Sans', sans-serif !important;
        }

        /* LaTeX / Mathematical Formula Typography */
        .latex-equation {
            user-select: none;
            transition: all 0.2s ease;
            border-radius: 6px;
            border: 1px dashed transparent;
            padding: 2px 4px;
            vertical-align: middle;
            cursor: pointer;
        }

        .latex-equation:hover {
            border-color: #6366f1;
            background-color: rgba(99, 102, 241, 0.06);
        }

        .latex-equation.display-equation {
            display: block;
            text-align: center;
            margin: 14pt 0;
            padding: 8pt 12pt;
            overflow-x: auto;
            overflow-y: hidden;
            max-width: 100%;
        }

        .latex-equation.inline-equation {
            display: inline-block;
            margin: 0 2px;
        }

        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .katex,
        .document-page-sheet:not(.canvas-dark-sheet) .page-sheet-body .latex-equation {
            color: #111827 !important;
        }

        .document-page-sheet.canvas-dark-sheet .page-sheet-body .katex,
        .document-page-sheet.canvas-dark-sheet .page-sheet-body .latex-equation {
            color: #f3f4f6 !important;
        }

        #document-editor h1.heading-chapter,
        #document-editor h1.heading-preliminary,
        .page-sheet-body h1.heading-chapter,
        .page-sheet-body h1.heading-preliminary {
            font-size: 14pt;
            font-weight: 700;
            text-align: center;
            margin-top: 16pt;
            margin-bottom: 12pt;
            line-height: 1.3;
            text-transform: uppercase;
        }

        #document-editor h2.heading-section,
        .page-sheet-body h2.heading-section {
            font-size: 12pt;
            font-weight: 700;
            margin-top: 14pt;
            margin-bottom: 6pt;
            line-height: 1.35;
        }

        #document-editor h3.heading-subsection,
        .page-sheet-body h3.heading-subsection {
            font-size: 12pt;
            font-weight: 600;
            margin-top: 10pt;
            margin-bottom: 4pt;
        }

        #document-editor p,
        .page-sheet-body p {
            margin-bottom: 8pt;
            line-height: 1.5;
        }

        /* Perataan Paragraf & Sel Tabel */
        #document-editor .text-center,
        .page-sheet-body .text-center,
        #document-editor p.text-center,
        .page-sheet-body p.text-center,
        #document-editor td.text-center,
        .page-sheet-body td.text-center,
        #document-editor th.text-center,
        .page-sheet-body th.text-center {
            text-align: center !important;
            text-indent: 0 !important;
        }

        #document-editor .text-right,
        .page-sheet-body .text-right,
        #document-editor p.text-right,
        .page-sheet-body p.text-right,
        #document-editor td.text-right,
        .page-sheet-body td.text-right,
        #document-editor th.text-right,
        .page-sheet-body th.text-right {
            text-align: right !important;
            text-indent: 0 !important;
        }

        #document-editor .text-left,
        .page-sheet-body .text-left,
        #document-editor p.text-left,
        .page-sheet-body p.text-left,
        #document-editor td.text-left,
        .page-sheet-body td.text-left {
            text-align: left !important;
            text-indent: 0 !important;
        }

        #document-editor .text-justify,
        .page-sheet-body .text-justify,
        #document-editor p.text-justify,
        .page-sheet-body p.text-justify {
            text-align: justify !important;
        }

        #document-editor p.indent-8,
        .page-sheet-body p.indent-8 {
            text-indent: 1.25cm;
        }

        #document-editor ul,
        .page-sheet-body ul {
            list-style-type: disc;
            margin-left: 24pt;
            margin-bottom: 10pt;
        }

        #document-editor ol,
        .page-sheet-body ol {
            list-style-type: decimal;
            margin-left: 24pt;
            margin-bottom: 10pt;
        }

        #document-editor li,
        .page-sheet-body li {
            margin-bottom: 4pt;
            line-height: 1.5;
        }

        #document-editor blockquote,
        .page-sheet-body blockquote {
            border-left: 4px solid #6366f1;
            padding-left: 14pt;
            margin: 12pt 0;
            color: #4b5563;
            font-style: italic;
        }

        #document-editor table,
        .page-sheet-body table {
            border-collapse: collapse;
            width: 100%;
            margin: 12pt 0;
            font-size: 10.5pt;
        }

        #document-editor th, #document-editor td,
        .page-sheet-body th, .page-sheet-body td {
            border: 1px solid #9ca3af;
            padding: 6pt 10pt;
        }

        #document-editor th,
        .page-sheet-body th {
            background-color: #f3f4f6;
            font-weight: 600;
            text-align: center;
        }

        /* Garis Bawah & Garis Coret */
        #document-editor u, .page-sheet-body u {
            text-decoration: underline;
        }

        #document-editor s, .page-sheet-body s,
        #document-editor del, .page-sheet-body del {
            text-decoration: line-through;
        }

        #document-editor figure,
        .page-sheet-body figure {
            margin: 14pt 0;
            text-align: center;
        }

        #document-editor figure img,
        .page-sheet-body figure img {
            max-width: 100%;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        #document-editor figure figcaption,
        .page-sheet-body figure figcaption {
            font-size: 10pt;
            color: #4b5563;
            margin-top: 6pt;
            font-style: italic;
        }

        /* Pulse animation for outline jump */
        .outline-pulse-active {
            animation: outlinePulse 1.6s ease-out;
        }

        @keyframes outlinePulse {
            0% { background-color: rgba(99, 102, 241, 0.35); }
            100% { background-color: transparent; }
        }

        /* Ribbon Tab States */
        .ribbon-tab-btn.active {
            border-bottom: 2px solid #4f46e5;
            color: #4f46e5;
            font-weight: 600;
        }

        /* Page Break Indicator */
        .page-break {
            page-break-after: always;
            break-after: page;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-100 h-full flex flex-col overflow-hidden select-none">

    <!-- ========================================================================= -->
    <!-- 1. TOP APPLICATION BAR (Google Docs & Microsoft Word inspired)            -->
    <!-- ========================================================================= -->
    <header id="top-nav" class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-2 flex items-center justify-between shrink-0 no-print z-30 shadow-xs">
        <div class="flex items-center gap-3">
            <!-- App Logo -->
            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="hidden sm:block">
                    <div class="font-bold text-sm tracking-tight flex items-center gap-1.5 text-gray-900 dark:text-white">
                        <span>VelScribe</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded font-mono bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Wordcraft</span>
                    </div>
                </div>
            </div>

            <!-- Inline Editable Document Title -->
            <div class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <input
                        type="text"
                        id="document-title-input"
                        value="{{ $initialTitle }}"
                        class="text-sm font-semibold text-gray-800 dark:text-gray-100 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 focus:bg-white dark:focus:bg-gray-900 border border-transparent focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none w-52 sm:w-80 transition"
                        title="Klik untuk mengubah nama naskah/dokumen"
                    />
                    <svg class="w-3.5 h-3.5 text-gray-400 pointer-events-none absolute right-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>

                <!-- Format & Auto-Save Badges -->
                <span id="document-format-badge" class="hidden sm:inline-flex text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                    DOCX
                </span>
                <span id="document-save-status" class="hidden md:inline-flex text-xs text-gray-500 dark:text-gray-400 items-center gap-1">
                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    Tersimpan di lokal
                </span>

                <button id="btn-restore-draft" class="hidden text-xs px-2.5 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200 transition font-medium">
                    Pulihkan Draf Tersimpan
                </button>
            </div>
        </div>

        <!-- Top Right Actions -->
        <div class="flex items-center gap-2">
            <!-- Open Local File (Primary CTA) -->
            <button
                data-action="open-file-modal"
                class="px-3.5 py-1.5 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs hover:shadow transition flex items-center gap-1.5 cursor-pointer"
                title="Buka Dokumen dari File Komputer (Ctrl+O)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                <span>Buka Dokumen Lokal</span>
            </button>

            <!-- Export / Download Dropdown -->
            <button
                data-action="export-modal"
                class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition flex items-center gap-1.5 cursor-pointer"
                title="Ekspor Dokumen (Word, PDF, Markdown, HTML, dll)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="hidden sm:inline">Unduh / Ekspor</span>
            </button>

            <!-- Dark / Light Theme Visual Switcher -->
            <div class="flex items-center">
                <button
                    id="theme-toggle-btn"
                    type="button"
                    role="switch"
                    aria-checked="false"
                    class="group relative inline-flex items-center gap-2 px-2.5 py-1 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    title="Ganti Tema Tampilan (Terang / Gelap)"
                >
                    <!-- Visual Switch Track -->
                    <div class="relative w-11 h-6 rounded-full bg-gray-200 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 transition-colors px-1 flex items-center justify-between pointer-events-none">
                        <!-- Sun Icon (Gold/Amber) -->
                        <span id="theme-icon-sun" class="text-[11px] transition-opacity duration-200 text-amber-500 leading-none">☀️</span>
                        <!-- Moon Icon (Indigo/Blue) -->
                        <span id="theme-icon-moon" class="text-[11px] transition-opacity duration-200 text-indigo-400 opacity-40 leading-none">🌙</span>
                        <!-- Sliding Knob -->
                        <span id="theme-toggle-knob" class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow-sm transform translate-x-0 transition-transform duration-300 ease-in-out border border-gray-200 dark:border-indigo-400 flex items-center justify-center"></span>
                    </div>
                    <!-- Label Text -->
                    <span id="theme-toggle-text" class="text-xs font-semibold text-gray-700 dark:text-gray-200 hidden lg:inline select-none">
                        Mode Terang
                    </span>
                </button>
            </div>

            <!-- Print Shortcut -->
            <button
                onclick="window.print()"
                class="p-1.5 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                title="Cetak Dokumen / Ekspor ke PDF (Ctrl+P)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
            </button>

            <!-- Toggle Wordcraft AI Sidebar -->
            <button
                id="btn-toggle-ai"
                class="px-3 py-1.5 text-xs font-semibold rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                title="Buka Asisten AI Wordcraft"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                </svg>
                <span class="hidden md:inline">Wordcraft AI</span>
            </button>
        </div>
    </header>

    <!-- ========================================================================= -->
    <!-- 2. MICROSOFT WORD STYLE RIBBON TOOLBAR                                    -->
    <!-- ========================================================================= -->
    <div id="ribbon-bar" class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shrink-0 no-print z-20 shadow-xs">
        <!-- Ribbon Tab Bar -->
        <div class="flex items-center px-4 border-b border-gray-100 dark:border-gray-800/80 gap-1 text-xs overflow-x-auto">
            <button data-ribbon-tab="file" class="ribbon-tab-btn px-3 py-1.5 font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 rounded-t transition cursor-pointer flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                <span>Berkas (File)</span>
            </button>
            <button data-ribbon-tab="home" class="ribbon-tab-btn active px-3.5 py-1.5 font-medium transition cursor-pointer">
                Beranda (Home)
            </button>
            <button data-ribbon-tab="insert" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Sisipkan (Insert)
            </button>
            <button data-ribbon-tab="layout" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Tata Letak (Layout)
            </button>
            <button data-ribbon-tab="review" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Tinjau & Referensi (Review)
            </button>
            <button data-ribbon-tab="view" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Tampilan (View)
            </button>
            <button data-ribbon-tab="ai" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-purple-600 dark:text-purple-400 flex items-center gap-1 transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
                Asisten AI Wordcraft
            </button>
        </div>

        <!-- Ribbon Panel 0: BERKAS (FILE) -->
        <div data-ribbon-panel="file" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs bg-indigo-50/40 dark:bg-indigo-950/20">
            <!-- Dokumen Lokal CTA -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-indigo-200 dark:border-indigo-900">
                <button data-action="open-file-modal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold shadow-xs flex items-center gap-1.5 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Buka Dokumen Lokal</span>
                </button>
                <button data-action="new-document" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 rounded font-medium flex items-center gap-1 transition cursor-pointer" title="Buat Lembar Dokumen Kosong Baru">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Dokumen Baru</span>
                </button>
            </div>

            <!-- Template Cepat -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-indigo-200 dark:border-indigo-900">
                <span class="text-gray-500 font-semibold">Pilih Template:</span>
                <button data-load-sample="skripsi" class="px-2.5 py-1 rounded bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 font-medium transition">
                    🎓 Skripsi (4-4-3-3 cm)
                </button>
                <button data-load-sample="report" class="px-2.5 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 font-medium transition">
                    📑 Laporan Riset
                </button>
                <button data-load-sample="wordcraft" class="px-2.5 py-1 rounded bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 hover:bg-purple-50 font-medium transition">
                    ✍️ Naskah Kreatif
                </button>
            </div>

            <!-- Simpan & Ekspor -->
            <div class="flex items-center gap-1.5">
                <button onclick="window.documentEngine.saveToLocalStorage(); window.uiController.showToast('Tersimpan di lokal!');" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 rounded flex items-center gap-1 transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l6-6a1 1 0 00-1.414-1.414l-5.293 5.293-2.293-2.293z"></path></svg>
                    <span>Simpan (Ctrl+S)</span>
                </button>
                <button data-action="export-modal" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 rounded flex items-center gap-1 transition">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Unduh / Ekspor</span>
                </button>
                <button onclick="window.print()" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 rounded flex items-center gap-1 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak PDF</span>
                </button>
            </div>
        </div>

        <!-- Ribbon Panel 1: BERANDA (HOME) -->
        <div data-ribbon-panel="home" class="flex flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <!-- Riwayat (History) & Format Painter -->
            <div class="flex items-center gap-1 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-action="undo" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Urungkan (Undo Ctrl+Z)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"></path></svg>
                </button>
                <button data-action="redo" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Ulangi (Redo Ctrl+Y)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m15-7l-4-4m4 4l-4 4"></path></svg>
                </button>
                <button id="btn-format-painter" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Salin Format (Format Painter)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                </button>
            </div>

            <!-- Font Family, Size, Stepper & Spacing -->
            <div class="flex items-center gap-1 pr-3 border-r border-gray-200 dark:border-gray-800">
                <select id="font-family-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-36">
                    <option value="'Times New Roman', serif" selected>Times New Roman</option>
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Calibri, sans-serif">Calibri</option>
                    <option value="Aptos, 'Segoe UI', sans-serif">Aptos</option>
                    <option value="'Segoe UI', sans-serif">Segoe UI</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="Garamond, serif">Garamond</option>
                    <option value="Cambria, Georgia, serif">Cambria</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                    <option value="Tahoma, sans-serif">Tahoma</option>
                    <option value="'Instrument Sans', sans-serif">Instrument Sans</option>
                    <option value="'Courier New', monospace">Courier New</option>
                    <option value="Consolas, 'Courier New', monospace">Consolas</option>
                </select>

                <select id="font-size-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-16">
                    <option value="8pt">8 pt</option>
                    <option value="9pt">9 pt</option>
                    <option value="10pt">10 pt</option>
                    <option value="11pt">11 pt</option>
                    <option value="12pt" selected>12 pt</option>
                    <option value="14pt">14 pt</option>
                    <option value="16pt">16 pt</option>
                    <option value="18pt">18 pt</option>
                    <option value="20pt">20 pt</option>
                    <option value="24pt">24 pt</option>
                    <option value="28pt">28 pt</option>
                    <option value="36pt">36 pt</option>
                </select>

                <button id="btn-font-grow" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded font-bold text-gray-700 dark:text-gray-300 text-xs" title="Perbesar Huruf (A+)">A⁺</button>
                <button id="btn-font-shrink" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded font-bold text-gray-700 dark:text-gray-300 text-xs" title="Perkecil Huruf (A-)">A⁻</button>

                <select id="line-spacing-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-22" title="Spasi Baris Paragraf">
                    <option value="1.0">Spasi 1.0</option>
                    <option value="1.15">Spasi 1.15</option>
                    <option value="1.5" selected>Spasi 1.5</option>
                    <option value="2.0">Spasi 2.0</option>
                </select>
            </div>

            <!-- Inline Styles (Bold, Italic, Underline, Strike, Sub, Super) -->
            <div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-cmd="bold" class="p-1.5 font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Tebal (Ctrl+B)">
                    <strong>B</strong>
                </button>
                <button data-cmd="italic" class="p-1.5 italic font-serif text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Miring (Ctrl+I)">
                    <em>I</em>
                </button>
                <button data-cmd="underline" class="p-1.5 underline text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Garis Bawah (Ctrl+U)">
                    <u>U</u>
                </button>
                <button data-cmd="strikeThrough" class="p-1.5 line-through text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Coret (Strikethrough)">
                    <s>S</s>
                </button>
                <button data-cmd="subscript" class="p-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Subskrip">
                    X<sub>2</sub>
                </button>
                <button data-cmd="superscript" class="p-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Superskrip">
                    X<sup>2</sup>
                </button>
            </div>

            <!-- Colors & Clear -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <label class="flex items-center gap-1 cursor-pointer p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800" title="Warna Teks">
                    <span class="font-bold text-xs text-red-600">A</span>
                    <input type="color" id="text-color-picker" value="#111827" class="w-4 h-4 rounded cursor-pointer border-0 p-0" />
                </label>
                <label class="flex items-center gap-1 cursor-pointer p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800" title="Warna Sorotan (Highlight)">
                    <span class="bg-yellow-200 text-gray-900 px-1 rounded text-[10px] font-bold">ab</span>
                    <input type="color" id="highlight-color-picker" value="#fef08a" class="w-4 h-4 rounded cursor-pointer border-0 p-0" />
                </label>
                <button id="btn-clear-format" class="p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Hapus Pemformatan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>

            <!-- Paragraph Alignments (Left, Center, Right, Justify) -->
            <div class="flex items-center gap-0.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-align="left" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Rata Kiri">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"></path></svg>
                </button>
                <button data-align="center" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Rata Tengah (Judul/Bab)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14"></path></svg>
                </button>
                <button data-align="right" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Rata Kanan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"></path></svg>
                </button>
                <button data-align="justify" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition bg-gray-100 dark:bg-gray-800" title="Rata Kiri-Kanan / Justify (Standar Skripsi)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <button data-cmd="insertUnorderedList" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Daftar Poin (Bullets)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h.01M4 12h.01M4 18h.01M8 6h12M8 12h12M8 18h12"></path></svg>
                </button>
                <button data-cmd="insertOrderedList" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Daftar Bernomor (Numbered List)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                </button>
                <button id="btn-checklist" data-cmd="insertChecklist" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Daftar Centang / Tugas (Checklist)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
                <button data-cmd="outdent" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Kurangi Indentasi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
                <button data-cmd="indent" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Tambah Indentasi Paragraf">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Quick Styles Gallery (Word Style Bar) -->
            <div class="flex items-center gap-1 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-quick-style="p" class="quick-style-btn px-2 py-1 rounded bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 text-xs">
                    Normal
                </button>
                <button data-quick-style="h1" class="quick-style-btn px-2 py-1 rounded bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 font-bold text-xs">
                    Judul 1 (Bab)
                </button>
                <button data-quick-style="h2" class="quick-style-btn px-2 py-1 rounded bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 font-semibold text-xs">
                    Judul 2
                </button>
                <button data-quick-style="blockquote" class="quick-style-btn px-2 py-1 rounded bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 italic text-xs">
                    Kutipan
                </button>
            </div>

            <!-- Search / Find & Replace -->
            <div class="flex items-center gap-1">
                <button data-action="find-replace" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1.5 transition" title="Cari & Ganti Kata (Ctrl+F)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>Cari & Ganti</span>
                </button>
            </div>
        </div>

        <!-- Ribbon Panel 2: SISIPKAN (INSERT) -->
        <div data-ribbon-panel="insert" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <button data-action="open-file-modal" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 rounded font-medium flex items-center gap-1.5 hover:bg-indigo-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Buka Dokumen Lokal</span>
            </button>

            <!-- Halaman Sampul & Pemisah -->
            <button data-action="insert-cover-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>📘 Halaman Sampul</span>
            </button>

            <button onclick="window.documentEngine.insertPageBreak()" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 rounded font-medium flex items-center gap-1.5 hover:bg-indigo-100 transition">
                <span>📄 Pemisah Halaman</span>
            </button>

            <!-- Insert Table of Contents -->
            <button id="btn-insert-toc" class="px-3 py-1.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 rounded font-semibold flex items-center gap-1.5 hover:bg-indigo-200 transition">
                <span>📑 Daftar Isi Otomatis</span>
            </button>

            <button data-action="insert-table-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span>Tabel</span>
            </button>

            <button data-action="insert-image-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Gambar</span>
            </button>

            <button data-action="insert-link-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                <span>Tautan (Link)</span>
            </button>

            <button data-action="insert-symbols-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>Ω Simbol Khusus</span>
            </button>

            <button data-action="insert-equation-modal" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 rounded font-medium flex items-center gap-1.5 hover:bg-indigo-100 transition" title="Sisipkan rumus atau persamaan matematika (LaTeX)">
                <span class="font-bold font-serif text-sm text-indigo-600 dark:text-indigo-400">∑</span>
                <span>Rumus Matematika (LaTeX)</span>
            </button>

            <button data-action="header-footer-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition" title="Konfigurasi running header, footer, dan nomor halaman">
                <span>📋 Header & Footer</span>
            </button>

            <button data-action="page-number-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition" title="Format dan penomoran halaman">
                <span>#️⃣ Nomor Halaman</span>
            </button>

            <button onclick="window.documentEngine.insertCallout('info', 'Catatan Penelitian', 'Informasi terverifikasi untuk analisis bab selanjutnya.')" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>💬 Callout Box</span>
            </button>

            <button onclick="window.documentEngine.insertHorizontalRule()" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>— Garis Pemisah</span>
            </button>
        </div>

        <!-- Ribbon Panel 3: TATA LETAK (LAYOUT) -->
        <div data-ribbon-panel="layout" class="hidden flex-wrap items-center px-4 py-2 gap-4 overflow-x-auto text-xs">
            <div class="flex items-center gap-2">
                <label class="text-gray-500 font-bold">Preset Margin:</label>
                <select id="layout-margins" class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none font-semibold">
                    <option value="skripsi" selected>Skripsi / Tesis (Atas 4, Kiri 4, Kanan 3, Bawah 3 cm)</option>
                    <option value="normal">Normal (2.54 cm / 1 inch)</option>
                    <option value="narrow">Sempit (1.27 cm)</option>
                    <option value="wide">Lebar (3.81 cm)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-gray-500 font-bold">Ukuran Kertas:</label>
                <select id="layout-paper-size" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none">
                    <option value="a4" selected>A4 (210 x 297 mm)</option>
                    <option value="letter">Letter (8.5 x 11 in)</option>
                    <option value="legal">Legal (8.5 x 14 in)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-gray-500 font-bold">Orientasi:</label>
                <select id="layout-orientation" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none">
                    <option value="portrait" selected>Potret (Portrait)</option>
                    <option value="landscape">Lanskap (Landscape)</option>
                </select>
            </div>

            <div class="flex items-center gap-2 pl-3 border-l border-gray-200 dark:border-gray-800">
                <button onclick="window.documentEngine.setMarginPreset('skripsi'); window.documentEngine.setFontFamily('\'Times New Roman\', serif'); window.documentEngine.setFontSize('12pt'); window.documentEngine.setLineSpacing('1.5'); window.uiController.showToast('Format Skripsi 4-4-3-3 cm diterapkan!');" class="px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-semibold hover:bg-indigo-100 transition">
                    🎓 Terapkan Standar Skripsi
                </button>
                <button data-action="header-footer-modal" class="px-2.5 py-1 rounded bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 font-medium hover:bg-gray-100 transition" title="Konfigurasi running header, footer, dan nomor halaman">
                    📋 Header & Footer
                </button>
            </div>
        </div>

        <!-- Ribbon Panel 4: TINJAU & REFERENSI (REVIEW) -->
        <div data-ribbon-panel="review" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <button id="btn-puebi-check" onclick="window.wordcraftAi.executeAction('grammar')" class="px-3 py-1.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 rounded font-semibold flex items-center gap-1.5 hover:bg-emerald-100 transition">
                <span>🛠️ Koreksi Ejaan PUEBI & Tata Bahasa</span>
            </button>

            <button id="btn-word-stats" data-action="open-stats-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>📊 Hitung Kata & Statistik</span>
            </button>

            <button id="btn-read-aloud" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition" title="Bacakan Naskah Dokumen dengan Suara">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                <span>Baca Suara (Read Aloud)</span>
            </button>

            <button data-action="insert-citation-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>📚 Generator Sitasi / Daftar Pustaka</span>
            </button>

            <button data-layout-mode="zen" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>🧘 Mode Fokus (Zen)</span>
            </button>
        </div>

        <!-- Ribbon Panel 5: TAMPILAN (VIEW) -->
        <div data-ribbon-panel="view" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-layout-mode="print" class="px-2.5 py-1 text-indigo-600 font-bold bg-indigo-50 dark:bg-indigo-950/40 rounded transition">
                    Tata Letak Cetak A4
                </button>
                <button data-layout-mode="web" class="px-2.5 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-100 rounded transition">
                    Aliran Web
                </button>
                <button data-layout-mode="zen" class="px-2.5 py-1 text-gray-600 dark:text-gray-400 hover:bg-gray-100 rounded transition">
                    Mode Fokus (Zen)
                </button>
            </div>

            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button id="btn-toggle-ruler" class="px-2 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" checked class="pointer-events-none" />
                    <span>Penggaris Metrik</span>
                </button>
                <button id="btn-toggle-outline" class="px-2 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" checked class="pointer-events-none" />
                    <span>Struktur Dokumen (Outline)</span>
                </button>
            </div>

            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[11px] font-semibold uppercase">Tema & Kanvas:</span>
                <button id="btn-ribbon-theme-toggle" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1.5 cursor-pointer border border-gray-200 dark:border-gray-700 transition" title="Ganti Tema Studio (Terang / Gelap)">
                    <span id="ribbon-theme-icon">☀️</span>
                    <span id="ribbon-theme-label">Tema Terang</span>
                </button>
                <button id="btn-toggle-dark-sheet" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1.5 cursor-pointer border border-gray-200 dark:border-gray-700 transition" title="Ganti Warna Lembar Kertas (Terang / Gelap)">
                    <span id="sheet-theme-icon">📄</span>
                    <span id="sheet-theme-label">Lembar Putih</span>
                </button>
            </div>

            <button data-action="open-shortcuts-modal" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded text-xs flex items-center gap-1">
                <span>⌨️ Pintasan Keyboard (Ctrl+/)</span>
            </button>
        </div>

        <!-- Ribbon Panel 6: ASISTEN AI WORDCRAFT -->
        <div data-ribbon-panel="ai" class="hidden flex-wrap items-center px-4 py-2 gap-2 overflow-x-auto text-xs bg-purple-50/50 dark:bg-purple-950/20">
            <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">Aksi Cepat Wordcraft:</span>
            <button onclick="window.wordcraftAi.executeAction('continue')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                ⚡ Lanjutkan Tulisan
            </button>
            <button onclick="window.wordcraftAi.executeAction('elaborate')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                🔍 Elaborasi Gagasan
            </button>
            <button onclick="window.wordcraftAi.executeAction('rewrite')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                ✍️ Parafrase Nada
            </button>
            <button onclick="window.wordcraftAi.executeAction('grammar')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                🛠️ Koreksi PUEBI
            </button>
            <button onclick="window.wordcraftAi.executeAction('summarize')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                📝 Ringkas Naskah
            </button>
            <button onclick="window.wordcraftAi.executeAction('brainstorm')" class="px-2.5 py-1 bg-white dark:bg-gray-800 border border-purple-200 rounded font-medium text-purple-800 dark:text-purple-200 hover:bg-purple-100 transition shadow-2xs">
                💡 Brainstorm Ide
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. METRIC HORIZONTAL RULER BAR                                            -->
    <!-- ========================================================================= -->
    <div id="ruler-bar" class="h-5 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-center shrink-0 no-print overflow-hidden relative">
        <div class="w-[210mm] max-w-full h-full flex relative px-6" id="ruler-ticks">
            <!-- Rendered dynamically by UiController -->
        </div>
        <!-- Margin indicators on ruler -->
        <div id="ruler-left-margin" class="absolute top-0 bottom-0 w-1 bg-indigo-500 pointer-events-none opacity-80" style="left: 19%;" title="Margin Kiri (4 cm)"></div>
        <div id="ruler-right-margin" class="absolute top-0 bottom-0 w-1 bg-indigo-500 pointer-events-none opacity-80" style="left: 85.7%;" title="Margin Kanan (3 cm)"></div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. MAIN WORKSPACE (OUTLINE + CANVAS + WORDCRAFT AI DRAWER)                -->
    <!-- ========================================================================= -->
    <main class="flex-1 flex overflow-hidden relative">

        <!-- Left Document Outline Sidebar (Google Docs inspired) -->
        <aside id="outline-sidebar" class="w-64 md:w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 shrink-0 flex flex-col no-print z-10 transition-all duration-200">
            <div class="p-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Struktur Dokumen (Outline)</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Bab & Subbab</span>
            </div>
            <!-- Search Outline Input -->
            <div class="p-2 border-b border-gray-100 dark:border-gray-800">
                <div class="relative flex items-center">
                    <input
                        type="text"
                        id="outline-search-input"
                        placeholder="Cari bab atau subbab..."
                        class="w-full text-xs px-2.5 py-1.5 pl-7 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-800 focus:bg-white dark:focus:bg-gray-900 outline-none focus:border-indigo-500 transition"
                    />
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-3">
                <ul id="outline-headings-list" class="space-y-0.5 text-xs">
                    <!-- Populated dynamically by DocumentEngine -->
                </ul>
            </div>
            <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 text-[11px] text-gray-500">
                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Penyelarasan Format Cerdas:</div>
                <p>Heading dan Bab (BAB I, BAB II, 1.1, dst.) otomatis dideteksi dan dinavigasikan.</p>
            </div>
        </aside>

        <!-- Center Document Canvas Area -->
        <section id="document-canvas-container" class="flex-1 bg-gray-200/70 dark:bg-gray-950 overflow-y-auto overflow-x-auto flex flex-col items-center p-4 sm:p-8 relative">

            <!-- Drag & Drop Visual Overlay -->
            <div id="drag-drop-overlay" class="hidden absolute inset-0 bg-indigo-600/90 text-white z-50 flex flex-col items-center justify-center backdrop-blur-xs transition">
                <div class="border-2 border-dashed border-white/80 rounded-2xl p-12 text-center max-w-md">
                    <svg class="w-16 h-16 mx-auto mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <h3 class="text-xl font-bold mb-2">Lepaskan Berkas di Sini</h3>
                    <p class="text-sm text-indigo-100">VelScribe akan otomatis mendeteksi format .docx, .md, .html, .txt, atau .json dan menyelaraskan margin serta hierarki bab.</p>
                </div>
            </div>

            <!-- Floating Selection Bubble Menu (Google Docs style) -->
            <div id="bubble-menu" class="hidden fixed bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-1 rounded-lg shadow-xl z-40 items-center gap-1 text-xs border border-gray-700 no-print">
                <button data-cmd="bold" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded font-bold" title="Tebal (Ctrl+B)">B</button>
                <button data-cmd="italic" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded italic" title="Miring (Ctrl+I)">I</button>
                <button data-cmd="underline" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded underline" title="Garis Bawah (Ctrl+U)">U</button>
                <span class="w-px h-3.5 bg-gray-700 dark:bg-gray-300 mx-0.5"></span>
                <button data-align="center" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded" title="Rata Tengah">Tengah</button>
                <button data-align="justify" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded" title="Justify">Justify</button>
                <span class="w-px h-3.5 bg-gray-700 dark:bg-gray-300 mx-0.5"></span>
                <button data-action="insert-link-modal" class="p-1 hover:bg-gray-800 dark:hover:bg-gray-200 rounded" title="Tautan">🔗</button>
                <button id="bubble-ask-ai" class="px-2 py-0.5 bg-indigo-600 text-white rounded text-[11px] font-semibold flex items-center gap-1 hover:bg-indigo-500 transition" title="Tanya AI Wordcraft">
                    <span>✨ Wordcraft AI</span>
                </button>
            </div>

            <!-- Floating Table Context Toolbar -->
            <div id="floating-table-toolbar" class="hidden fixed bg-gray-900 text-white px-3 py-1.5 rounded-lg shadow-2xl z-40 items-center gap-2 text-xs border border-gray-700 no-print">
                <span class="text-gray-400 font-semibold mr-1">Tabel:</span>
                <button data-table-action="add-row-above" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">+ Baris Atas</button>
                <button data-table-action="add-row-below" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">+ Baris Bawah</button>
                <button data-table-action="del-row" class="px-2 py-0.5 hover:bg-red-800 rounded text-red-300 text-[11px]">Hapus Baris</button>
                <span class="w-px h-3 bg-gray-700"></span>
                <button data-table-action="add-col" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">+ Kolom</button>
                <button data-table-action="del-col" class="px-2 py-0.5 hover:bg-red-800 rounded text-red-300 text-[11px]">Hapus Kolom</button>
                <span class="w-px h-3 bg-gray-700"></span>
                <button data-table-action="del-table" class="px-2 py-0.5 bg-red-600 hover:bg-red-700 rounded text-white text-[11px] font-bold">Hapus Tabel</button>
            </div>

            <!-- Floating Image Context Toolbar -->
            <div id="floating-image-toolbar" class="hidden fixed bg-gray-900 text-white px-3 py-1.5 rounded-lg shadow-2xl z-40 items-center gap-2 text-xs border border-gray-700 no-print">
                <span class="text-gray-400 font-semibold mr-1">Gambar:</span>
                <button data-img-align="left" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">Kiri</button>
                <button data-img-align="center" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-indigo-600 text-[11px]">Tengah</button>
                <button data-img-align="right" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">Kanan</button>
                <span class="w-px h-3 bg-gray-700"></span>
                <button data-img-size="50%" class="px-2 py-0.5 hover:bg-gray-800 rounded text-[11px]">50%</button>
                <button data-img-size="100%" class="px-2 py-0.5 hover:bg-gray-800 rounded text-[11px]">100%</button>
            </div>

            <!-- Local File Auto-Adaptation Banner -->
            <div id="document-adaptation-banner" class="hidden w-full max-w-[210mm] mb-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/60 dark:to-purple-950/60 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs no-print transition">
                <div class="flex items-start gap-3">
                    <span class="text-xl">🪄</span>
                    <div>
                        <h4 id="adaptation-banner-title" class="font-bold text-indigo-900 dark:text-indigo-200 text-xs">Penyelarasan Format Otomatis:</h4>
                        <p id="adaptation-banner-desc" class="text-indigo-700 dark:text-indigo-300 text-[11px] mt-0.5">Dokumen lokal telah diimpor dan diselaraskan.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                    <button id="banner-btn-skripsi" class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-[11px] shadow-xs transition" title="Terapkan standar format skripsi Indonesia">
                        Format Skripsi 4-4-3-3
                    </button>
                    <button id="banner-btn-normal" class="px-2.5 py-1 rounded bg-white dark:bg-gray-800 hover:bg-gray-100 text-gray-700 dark:text-gray-300 font-medium text-[11px] border border-gray-300 dark:border-gray-600 shadow-xs transition" title="Terapkan margin normal">
                        Format Standar
                    </button>
                    <button id="banner-btn-close" class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Tutup pemberitahuan">
                        ✕
                    </button>
                </div>
            </div>

            <!-- A4 Document Multi-Page Sheets Container -->
            <div id="document-pages-wrapper" class="w-full flex flex-col items-center relative py-4 px-6 sm:px-14">
                <!-- Initial / SSR Document Page Sheet -->
                <article id="document-page-canvas" class="document-page-sheet page-a4 relative flex flex-col justify-between" data-page-index="1">
                    <!-- Page Sheet Header -->
                    <header class="page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1" title="Klik untuk menyesuaikan Header & Footer" data-action="header-footer-modal">
                        <span class="header-doc-title truncate max-w-[55%] font-medium text-gray-600 dark:text-gray-300" title="{{ $initialTitle }}">{{ $initialTitle }}</span>
                        <span class="header-chapter-name text-gray-400 font-serif italic text-[11px] truncate max-w-[42%] text-right"></span>
                    </header>

                    <!-- Page Content Area (Editable) -->
                    <div
                        id="document-editor"
                        class="page-sheet-body flex-1 outline-none"
                        contenteditable="true"
                        spellcheck="true"
                        role="textbox"
                        aria-multiline="true"
                    >
                        {!! $initialContent !!}
                    </div>

                    <!-- Page Footer Simulation (Print Layout - Clean without synthetic page marks) -->
                    <footer class="page-sheet-footer flex items-center justify-between text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1" title="Klik untuk menyesuaikan Header & Footer" data-action="header-footer-modal">
                        <span class="footer-doc-title text-[11px] text-gray-400 font-mono truncate max-w-[85%]"></span>
                    </footer>

                    <!-- Side Page Indicator (Outside Document Canvas) -->
                    <div class="page-side-indicator absolute -left-12 sm:-left-16 top-6 select-none no-print pointer-events-auto flex flex-col items-center" title="Lembar Halaman 1">
                        <div class="flex flex-col items-center justify-center min-w-[38px] px-2 py-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-600 dark:text-gray-300 font-mono text-[11px] hover:border-indigo-400 hover:text-indigo-600 transition">
                            <span class="text-[9px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold leading-none mb-0.5">Hal</span>
                            <span class="font-bold text-xs leading-none text-indigo-600 dark:text-indigo-400">1</span>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 leading-none mt-0.5">/ 1</span>
                        </div>
                    </div>

                    <!-- Side Header & Footer Quick Edit Actions (Outside Document Canvas) -->
                    <div class="page-side-actions absolute -right-12 sm:-right-14 top-4 select-none no-print flex flex-col gap-2 pointer-events-auto">
                        <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Header Dokumen">
                            <span class="text-xs">🏷️</span>
                        </button>
                    </div>
                    <div class="page-side-footer-action absolute -right-12 sm:-right-14 bottom-4 select-none no-print pointer-events-auto">
                        <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Sesuaikan Footer Dokumen">
                            <span class="text-xs">📋</span>
                        </button>
                    </div>
                </article>
            </div>

        </section>

        <!-- Right Wordcraft AI Studio Drawer (https://github.com/storytold/wordcraft inspired) -->
        <aside id="wordcraft-sidebar" class="w-80 md:w-96 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shrink-0 flex flex-col no-print z-10 shadow-lg transition-all duration-200">
            <!-- Studio Header -->
            <div class="p-3.5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-gray-800">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                    </span>
                    <h2 class="font-bold text-xs uppercase tracking-wider text-gray-800 dark:text-gray-100">Wordcraft AI Studio</h2>
                </div>
                <button onclick="window.uiController.toggleWordcraftSidebar(false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Action Chips Bar -->
            <div class="p-3 border-b border-gray-100 dark:border-gray-800">
                <div class="text-[11px] font-semibold text-gray-500 mb-2">PILIH AKSI KREATIF:</div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    <button data-wordcraft-action="continue" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-indigo-600 text-white font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>⚡</span> Lanjutkan
                    </button>
                    <button data-wordcraft-action="elaborate" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>🔍</span> Elaborasi
                    </button>
                    <button data-wordcraft-action="rewrite" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>✍️</span> Parafrase
                    </button>
                    <button data-wordcraft-action="grammar" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>🛠️</span> Ejaan PUEBI
                    </button>
                    <button data-wordcraft-action="summarize" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>📝</span> Ringkas
                    </button>
                    <button data-wordcraft-action="brainstorm" class="px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium text-left truncate flex items-center gap-1.5 transition">
                        <span>💡</span> Ide Lanjutan
                    </button>
                </div>

                <!-- Tone / Style selector -->
                <div class="mt-2.5 flex items-center justify-between text-xs">
                    <label class="text-gray-500 font-medium">Gaya Nada:</label>
                    <select id="wordcraft-style-select" class="px-2 py-1 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs outline-none">
                        <option value="formal" selected>Akademik / Formal</option>
                        <option value="creative">Kreatif / Sastra</option>
                        <option value="concise">Ringkas & Lugas</option>
                        <option value="persuasive">Persuasif & Kuat</option>
                    </select>
                </div>
            </div>

            <!-- Custom Prompt / Q&A Box -->
            <div class="p-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <input
                    type="text"
                    id="wordcraft-custom-prompt"
                    placeholder="Tanya dokumen atau instruksi khusus..."
                    class="flex-1 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs outline-none focus:border-indigo-500 transition"
                />
                <button
                    id="wordcraft-submit-prompt"
                    class="p-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-xs transition"
                    title="Kirim Instruksi"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>

            <!-- Loading Spinner Indicator -->
            <div id="wordcraft-loading-spinner" class="hidden p-6 text-center text-xs text-indigo-600">
                <div class="inline-flex items-center gap-2 font-medium">
                    <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Wordcraft sedang memproses ide...</span>
                </div>
            </div>

            <!-- Wordcraft Candidate Cards Results Container -->
            <div id="wordcraft-results-container" class="flex-1 overflow-y-auto p-3 space-y-3">
                <div class="text-center py-8 text-xs text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Pilih salah satu tombol aksi di atas untuk memunculkan saran kreatif Wordcraft.
                </div>
            </div>
        </aside>

    </main>

    <!-- ========================================================================= -->
    <!-- 5. STATUS BAR (BOTTOM)                                                    -->
    <!-- ========================================================================= -->
    <footer id="status-bar" class="h-8 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 px-4 flex items-center justify-between text-xs text-gray-500 shrink-0 no-print z-20">
        <div class="flex items-center gap-4">
            <span id="stat-pages">Halaman 1 dari 1</span>
            <button data-action="open-stats-modal" class="hover:text-indigo-600 font-semibold" title="Buka Detail Statistik Dokumen">
                <span id="stat-words">{{ $initialStats['word_count'] }} kata</span>
            </button>
            <span id="stat-chars" class="hidden sm:inline">{{ $initialStats['char_count'] }} karakter</span>
            <span id="stat-reading-time" class="hidden md:inline">{{ $initialStats['reading_time'] }}</span>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-layout-mode="print" class="p-1 hover:text-indigo-600" title="Tata Letak Halaman">📄</button>
                <button data-layout-mode="web" class="p-1 hover:text-indigo-600" title="Tampilan Aliran Web">🌐</button>
                <button data-layout-mode="zen" class="p-1 hover:text-indigo-600" title="Mode Fokus (Zen)">🧘</button>
                <button id="btn-status-theme-toggle" class="p-1 hover:text-indigo-600 ml-1 border-l border-gray-200 dark:border-gray-800 pl-2 cursor-pointer" title="Ganti Tema (Terang / Gelap)">
                    <span id="status-theme-icon">☀️</span>
                </button>
            </div>

            <div class="flex items-center gap-1.5">
                <button id="btn-zoom-out" class="p-0.5 hover:text-gray-800 dark:hover:text-gray-200 font-bold">-</button>
                <input type="range" id="zoom-slider" min="50" max="200" value="100" step="5" class="w-20 h-1 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer" />
                <button id="btn-zoom-in" class="p-0.5 hover:text-gray-800 dark:hover:text-gray-200 font-bold">+</button>
                <span id="zoom-label" class="w-10 text-right font-mono text-[11px]">100%</span>
            </div>
        </div>
    </footer>

    <!-- ========================================================================= -->
    <!-- 6. MODALS                                                                 -->
    <!-- ========================================================================= -->

    <!-- Modal 1: Buka Dokumen Lokal -->
    <div id="modal-open-file" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Buka Dokumen dari File Lokal
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6">
                <label for="local-file-input" class="border-2 border-dashed border-indigo-200 dark:border-indigo-900/60 hover:border-indigo-500 rounded-xl p-8 flex flex-col items-center justify-center cursor-pointer bg-indigo-50/40 dark:bg-indigo-950/20 transition group">
                    <svg class="w-12 h-12 text-indigo-500 group-hover:scale-110 transition duration-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Pilih Berkas dari Komputer</span>
                    <span class="text-xs text-gray-500">Mendukung .docx, .doc, .md, .html, .txt, .json</span>
                    <input type="file" id="local-file-input" class="hidden" accept=".docx,.doc,.md,.markdown,.html,.htm,.txt,.json" />
                </label>

                <!-- Sample template loader -->
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Atau Coba Contoh Naskah Terstruktur:</div>
                    <button data-load-sample="skripsi" class="w-full px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 hover:bg-indigo-100 transition flex items-center justify-between border border-indigo-200 dark:border-indigo-800 cursor-pointer">
                        <span>🎓 Naskah Skripsi / Penelitian (Format 4-4-3-3 cm, Sampul & Bab I)</span>
                        <span>Muat →</span>
                    </button>
                    <button data-load-sample="report" class="w-full px-3 py-2 text-xs font-semibold rounded-lg bg-gray-50 text-gray-800 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-100 transition flex items-center justify-between border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <span>📑 Laporan Riset & Tabel Matriks (Format Normal 2.54 cm)</span>
                        <span>Muat →</span>
                    </button>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Cari & Ganti (Find & Replace) -->
    <div id="modal-find-replace" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    🔍 Cari & Ganti Kata
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Cari Kata / Frasa:</label>
                    <input type="text" id="find-search-input" placeholder="Masukkan kata yang dicari..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Ganti Dengan:</label>
                    <input type="text" id="find-replace-input" placeholder="Kata pengganti..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button id="btn-replace-one" class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Ganti Satu</button>
                <button id="btn-replace-all" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">Ganti Semua</button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Statistik Lengkap Dokumen -->
    <div id="modal-stats" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">📊 Statistik Dokumen</h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-3 text-xs">
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Jumlah Kata:</span>
                    <span id="modal-stat-words" class="font-bold font-mono">0</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Jumlah Karakter (dengan spasi):</span>
                    <span id="modal-stat-chars" class="font-bold font-mono">0</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Jumlah Karakter (tanpa spasi):</span>
                    <span id="modal-stat-chars-nospace" class="font-bold font-mono">0</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Jumlah Kalimat:</span>
                    <span id="modal-stat-sentences" class="font-bold font-mono">0</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Jumlah Paragraf:</span>
                    <span id="modal-stat-paras" class="font-bold font-mono">0</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-500">Estimasi Waktu Baca (200 kpm):</span>
                    <span id="modal-stat-reading" class="font-bold font-mono">0 menit</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500">Estimasi Waktu Bicara (130 kpm):</span>
                    <span id="modal-stat-speaking" class="font-bold font-mono">0 menit</span>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 text-white">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal 4: Pintasan Keyboard (Shortcuts) -->
    <div id="modal-shortcuts" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">⌨️ Pintasan Keyboard</h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Cetak / Ekspor PDF</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + P</kbd>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Simpan Dokumen</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + S</kbd>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Buka Berkas Lokal</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + O</kbd>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Cari & Ganti</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + F</kbd>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Pemisah Halaman (Page Break)</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + Enter</kbd>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                    <span>Statistik Dokumen</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + Shift + C</kbd>
                </div>
                <div class="flex justify-between py-1">
                    <span>Format Tebal / Miring / Garis Bawah</span>
                    <kbd class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">Ctrl + B / I / U</kbd>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 text-white">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal 5: Sisipkan Tautan -->
    <div id="modal-insert-link" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">🔗 Sisipkan Tautan</h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">URL Tautan:</label>
                    <input type="url" id="link-url-input" placeholder="https://contoh.com/artikel" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Teks Tampilan (Opsional):</label>
                    <input type="text" id="link-text-input" placeholder="Kunjungi Halaman Riset" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Batal</button>
                <button id="btn-confirm-link" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Sisipkan</button>
            </div>
        </div>
    </div>

    <!-- Modal 6: Sisipkan Tabel -->
    <div id="modal-insert-table" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">📊 Sisipkan Tabel</h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Baris:</label>
                    <input type="number" id="table-rows-input" value="4" min="1" max="50" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Kolom:</label>
                    <input type="number" id="table-cols-input" value="3" min="1" max="20" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Batal</button>
                <button id="btn-confirm-table" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Buat Tabel</button>
            </div>
        </div>
    </div>

    <!-- Modal 7: Sisipkan Gambar -->
    <div id="modal-insert-image" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white">🖼️ Sisipkan Gambar</h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Unggah dari Komputer:</label>
                    <input type="file" id="modal-image-file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                </div>
                <div class="text-center text-gray-400 font-medium">— atau masukkan URL gambar —</div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">URL Gambar:</label>
                    <input type="url" id="modal-image-url" placeholder="https://contoh.com/gambar.png" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan Gambar (Caption):</label>
                    <input type="text" id="modal-image-alt" placeholder="Gambar 1. Ilustrasi diagram sistem" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Batal</button>
                <button id="btn-confirm-image" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Sisipkan Gambar</button>
            </div>
        </div>
    </div>

    <!-- Modal 8: Ekspor / Unduh Dokumen -->
    <div id="modal-export" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh & Ekspor Dokumen
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <button data-export-format="docx" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0">W</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Microsoft Word (.doc/.docx)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Kompatibel penuh dengan MS Word, LibreOffice, dan Google Docs.</div>
                    </div>
                </button>

                <button data-export-format="pdf" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold text-xs shrink-0">PDF</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Dokumen PDF Cetak (.pdf)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Resolusi tinggi tata letak A4 siap cetak via dialog cetak browser.</div>
                    </div>
                </button>

                <button data-export-format="markdown" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-gray-800 text-white flex items-center justify-center font-bold text-xs shrink-0">M↓</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Markdown (.md)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Format standar untuk dokumentasi teknis dan GitHub.</div>
                    </div>
                </button>

                <button data-export-format="html" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center font-bold text-xs shrink-0">&lt;&gt;</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Halaman Web Mandiri (.html)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Berkas HTML tunggal dengan gaya CSS elegan tersemat.</div>
                    </div>
                </button>

                <button data-export-format="txt" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-gray-600 text-white flex items-center justify-center font-bold text-xs shrink-0">TXT</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Teks Murni (.txt)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Teks polos tanpa tag HTML untuk pembacaan sederhana.</div>
                    </div>
                </button>

                <button data-export-format="json" class="p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-xl flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/40 hover:bg-indigo-50/30 transition text-left">
                    <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shrink-0">{ }</div>
                    <div>
                        <div class="font-bold text-gray-800 dark:text-gray-200">Cadangan Struktur JSON (.json)</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Menyimpan metadata lengkap untuk pemulihan instan.</div>
                    </div>
                </button>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal 9: Sisipkan Halaman Sampul (Cover Page) -->
    <div id="modal-cover-page" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span>📄</span> Sisipkan Halaman Sampul (Cover Page)
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <!-- Pilihan 1: Cover Skripsi / Akademik -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-indigo-500 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl mb-3">🎓</div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Sampul Akademik / Skripsi</h4>
                        <p class="text-gray-500 text-[11px] mt-1">Format baku skripsi, tesis, dan disertasi perguruan tinggi di Indonesia dengan placeholder instansi & NIM.</p>
                    </div>
                    <button data-insert-cover="skripsi" class="mt-4 w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-xs transition">
                        Gunakan Sampul Skripsi
                    </button>
                </div>

                <!-- Pilihan 2: Cover Laporan Bisnis / Perusahaan -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-indigo-500 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl mb-3">🏢</div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Sampul Laporan Bisnis</h4>
                        <p class="text-gray-500 text-[11px] mt-1">Gaya modern korporat untuk proposal proyek, laporan keuangan tahunan, dan riset bisnis profesional.</p>
                    </div>
                    <button data-insert-cover="corporate" class="mt-4 w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-xs transition">
                        Gunakan Sampul Bisnis
                    </button>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Batal</button>
            </div>
        </div>
    </div>

    <!-- Modal 10: Simbol Matematika, Sains, & Tipografi Khusus -->
    <div id="modal-symbols" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🔣</span> Palet Simbol & Karakter Khusus
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Simbol Matematika & Logika</h5>
                    <div class="grid grid-cols-8 gap-2">
                        @foreach(['±', '×', '÷', '≠', '≈', '≤', '≥', '∑', '∏', '√', '∞', '∫', '∂', '∆', 'µ', 'π'] as $sym)
                            <button data-insert-symbol="{{ $sym }}" class="p-2 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950 font-serif text-sm font-semibold transition text-center">{{ $sym }}</button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Simbol Sains & Yunani</h5>
                    <div class="grid grid-cols-8 gap-2">
                        @foreach(['α', 'β', 'γ', 'δ', 'ε', 'θ', 'λ', 'σ', 'ω', 'Ω', '°', '℃', '℉', '‰', 'Å', '§'] as $sym)
                            <button data-insert-symbol="{{ $sym }}" class="p-2 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950 font-serif text-sm font-semibold transition text-center">{{ $sym }}</button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Mata Uang & Tipografi</h5>
                    <div class="grid grid-cols-8 gap-2">
                        @foreach(['©', '®', '™', '€', '£', '¥', '₹', '•', '—', '–', '“', '”', '«', '»', '¶', '†'] as $sym)
                            <button data-insert-symbol="{{ $sym }}" class="p-2 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950 font-serif text-sm font-semibold transition text-center">{{ $sym }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal 11: Sitasi & Daftar Pustaka (APA 7th Edition) -->
    <div id="modal-citation" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span>📖</span> Sitasi & Entri Daftar Pustaka
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-3.5 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Penulis / Institusi:</label>
                    <input type="text" id="citation-author" placeholder="Contoh: Sugiyono / Santoso, A." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun Publikasi:</label>
                    <input type="text" id="citation-year" placeholder="Contoh: 2024" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Karya / Artikel / Buku:</label>
                    <input type="text" id="citation-title" placeholder="Contoh: Metode Penelitian Kuantitatif, Kualitatif, dan R&D" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Sumber / Penerbit / Jurnal:</label>
                    <input type="text" id="citation-source" placeholder="Contoh: Alfabeta, Bandung / Jurnal Sistem Informasi, 12(3)" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                </div>
                <div class="p-3 rounded-lg bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 text-[11px] text-indigo-700 dark:text-indigo-300">
                    Sistem akan menyisipkan <strong>sitasi dalam teks</strong> seperti <em>(Santoso, 2024)</em> serta menambahkan entri terformat ke bagian <strong>Daftar Pustaka</strong> dokumen secara otomatis.
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Batal</button>
                <button id="btn-confirm-citation" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">Sisipkan Sitasi</button>
            </div>
        </div>
    </div>

    <!-- Modal 11: Pengaturan Header & Footer Dokumen (Microsoft Word & Google Docs Style) -->
    <div id="modal-header-footer" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-gray-800">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white">📋</span>
                    <span>Pengaturan Header & Footer Dokumen</span>
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 p-1">✕</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Teks Header (Bagian Atas Dokumen):</label>
                    <input type="text" id="hf-header-input" placeholder="Contoh: UNIVERSITAS NEGERI SEMARANG / Laporan Tugas Akhir" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                    <p class="text-[11px] text-gray-400 mt-1">Muncul di sudut kiri atas setiap lembar halaman dokumen.</p>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Teks Footer (Bagian Bawah Dokumen):</label>
                    <input type="text" id="hf-footer-input" placeholder="Contoh: Dokumen VelScribe Wordcraft / Draft Rahasia" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                    <p class="text-[11px] text-gray-400 mt-1">Muncul di sudut kiri bawah berdampingan dengan nomor halaman.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Format Nomor Halaman:</label>
                        <select id="hf-page-format-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500 font-medium">
                            <option value="full">Halaman X dari Y (Standar)</option>
                            <option value="simple">X dari Y</option>
                            <option value="number">X (Hanya Angka)</option>
                            <option value="hal">Hal. X</option>
                            <option value="dash">- X -</option>
                            <option value="roman">i, ii, iii (Romawi Kecil)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Mulai Dari Nomor:</label>
                        <input type="number" id="hf-start-page-input" min="1" value="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500 font-mono" />
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 space-y-2.5">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" id="hf-show-page-number-toggle" checked class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700" />
                        <div>
                            <span class="font-medium text-gray-800 dark:text-gray-200">Tampilkan Penanda Halaman di Samping Dokumen</span>
                            <p class="text-[11px] text-gray-400">Menampilkan badge nomor halaman di samping lembar dokumen A4 (di luar konten dokumen).</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" id="hf-different-first-toggle" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700" />
                        <div>
                            <span class="font-medium text-gray-800 dark:text-gray-200">Halaman Pertama Berbeda (Different First Page)</span>
                            <p class="text-[11px] text-gray-400">Sembunyikan header dan footer pada halaman pertama (standar halaman sampul atau judul skripsi Microsoft Word).</p>
                        </div>
                    </label>
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <button id="btn-reset-header-footer" class="px-3 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition font-medium">
                    Reset ke Standar
                </button>
                <div class="flex items-center gap-2">
                    <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Batal</button>
                    <button id="btn-apply-header-footer" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">Terapkan Perubahan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 12: Editor Rumus / Persamaan (LaTeX KaTeX) -->
    <div id="modal-latex-equation" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-xl w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900 dark:to-gray-800">
                <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white font-serif font-bold text-xs">∑</span>
                    <span id="latex-modal-title">Rumus & Persamaan Matematika (LaTeX)</span>
                </h3>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">✕</button>
            </div>

            <div class="p-5 space-y-4 text-xs">
                <!-- Quick Template Toolbar -->
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Template Simbol Cepat:</label>
                    <div class="flex flex-wrap gap-1 p-2 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-200 dark:border-gray-700/80">
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\frac{a}{b}" title="Pecahan (Fraction)">\frac{a}{b}</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\sqrt{x}" title="Akar Kuadrat">\sqrt{x}</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="x^{2}" title="Pangkat / Eksponen">x^{2}</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="x_{i}" title="Indeks / Subskrip">x_{i}</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\sum_{i=1}^{n} x_i" title="Sigma / Penjumlahan">\sum</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\int_{a}^{b} f(x) \, dx" title="Integral">\int</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\times" title="Kali">×</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\pm" title="Plus Minus">±</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\le" title="Kurang dari atau sama dengan">≤</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\ge" title="Lebih dari atau sama dengan">≥</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\alpha" title="Alpha">α</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\beta" title="Beta">β</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\mu" title="Mu">μ</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\sigma" title="Sigma">σ</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\approx" title="Mendekati / Kira-kira">≈</button>
                        <button type="button" class="latex-tpl-btn px-2 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded hover:bg-indigo-50 dark:hover:bg-indigo-950 font-mono text-xs cursor-pointer" data-tpl="\infty" title="Tak Hingga">∞</button>
                    </div>
                </div>

                <!-- Kode LaTeX -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-semibold text-gray-700 dark:text-gray-300">Sintaks Kode LaTeX:</label>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="latex-mode" value="display" checked class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">Blok Terpusat (Display $$)</span>
                            </label>
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="latex-mode" value="inline" class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">Sejajar Teks (Inline $)</span>
                            </label>
                        </div>
                    </div>
                    <textarea id="latex-input" rows="3" placeholder="Contoh: F1\text{-}Score = 2 \times \frac{Precision \times Recall}{Precision + Recall} \quad (4.9)" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 font-mono text-xs outline-none focus:border-indigo-500"></textarea>
                </div>

                <!-- Live Preview -->
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Pratinjau Hasil Rumus:</label>
                    <div id="latex-preview" class="p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 min-h-[60px] flex items-center justify-center overflow-x-auto text-gray-900 dark:text-white">
                        <span class="text-gray-400 italic text-xs">Pratinjau rumus matematika akan muncul di sini...</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <button id="btn-delete-equation" type="button" class="hidden px-3 py-1.5 text-xs font-semibold rounded-md text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer">Hapus Rumus</button>
                <div class="flex items-center gap-2 ml-auto">
                    <button data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer">Batal</button>
                    <button id="btn-save-equation" type="button" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer">Sisipkan Rumus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-12 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

</body>
</html>
