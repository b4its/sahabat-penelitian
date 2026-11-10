<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>VelScribe Wordcraft Studio — Word & Document Editor Cerdas</title>

    @fonts

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
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

        /* Dark Sheet Mode */
        #document-page-canvas.canvas-dark-sheet,
        .document-page-sheet.canvas-dark-sheet {
            background: #18181b !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.6) !important;
        }

        #document-page-canvas.canvas-dark-sheet #document-editor,
        #document-page-canvas.canvas-dark-sheet #document-editor *,
        .document-page-sheet.canvas-dark-sheet .page-sheet-body,
        .document-page-sheet.canvas-dark-sheet .page-sheet-body * {
            color: #f3f4f6 !important;
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
            font-family: 'Times New Roman', 'Instrument Sans', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #111827;
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
        <div class="flex items-center px-4 border-b border-gray-100 dark:border-gray-800/80 gap-1 text-xs">
            <button data-ribbon-tab="home" class="ribbon-tab-btn active px-3.5 py-1.5 font-medium transition cursor-pointer">
                Beranda (Home)
            </button>
            <button data-ribbon-tab="insert" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Sisipkan (Insert)
            </button>
            <button data-ribbon-tab="layout" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Tata Letak (Layout)
            </button>
            <button data-ribbon-tab="view" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-gray-600 dark:text-gray-400 transition cursor-pointer">
                Tampilan (View)
            </button>
            <button data-ribbon-tab="ai" class="ribbon-tab-btn px-3.5 py-1.5 font-medium text-purple-600 dark:text-purple-400 flex items-center gap-1 transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path></svg>
                Asisten AI Wordcraft
            </button>
        </div>

        <!-- Ribbon Panel 1: BERANDA (HOME) -->
        <div data-ribbon-panel="home" class="flex flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <!-- Riwayat (History) -->
            <div class="flex items-center gap-1 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button data-action="undo" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Urungkan (Undo Ctrl+Z)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"></path></svg>
                </button>
                <button data-action="redo" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Ulangi (Redo Ctrl+Y)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m15-7l-4-4m4 4l-4 4"></path></svg>
                </button>
            </div>

            <!-- Font Family, Size & Line Spacing -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <select id="font-family-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-36">
                    <option value="'Times New Roman', serif" selected>Times New Roman</option>
                    <option value="'Instrument Sans', sans-serif">Instrument Sans</option>
                    <option value="Arial, sans-serif">Arial</option>
                    <option value="Calibri, sans-serif">Calibri</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="'Courier New', monospace">Courier New</option>
                </select>

                <select id="font-size-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-16">
                    <option value="10pt">10 pt</option>
                    <option value="11pt">11 pt</option>
                    <option value="12pt" selected>12 pt (Skripsi)</option>
                    <option value="14pt">14 pt (Judul)</option>
                    <option value="16pt">16 pt</option>
                    <option value="18pt">18 pt</option>
                    <option value="24pt">24 pt</option>
                </select>

                <select id="line-spacing-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-22" title="Spasi Baris Paragraf">
                    <option value="1.0">Spasi 1.0</option>
                    <option value="1.15">Spasi 1.15</option>
                    <option value="1.5" selected>Spasi 1.5 (Skripsi)</option>
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
                <button data-cmd="outdent" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Kurangi Indentasi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                </button>
                <button data-cmd="indent" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Tambah Indentasi Paragraf">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Heading Presets -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <select id="heading-preset-select" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs outline-none focus:border-indigo-500 w-32 font-medium">
                    <option value="" disabled selected>Gaya Teks...</option>
                    <option value="p">Paragraf Normal</option>
                    <option value="h1">Judul Utama / Bab (H1)</option>
                    <option value="h2">Subjudul / Subbab (H2)</option>
                    <option value="h3">Bagian (H3)</option>
                    <option value="blockquote">Kutipan Blok</option>
                </select>
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

            <!-- Insert Table of Contents -->
            <button id="btn-insert-toc" class="px-3 py-1.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 rounded font-semibold flex items-center gap-1.5 hover:bg-indigo-200 transition">
                <span>📑 Daftar Isi Otomatis</span>
            </button>

            <button data-action="insert-table-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <span>Sisipkan Tabel</span>
            </button>

            <button data-action="insert-image-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Sisipkan Gambar</span>
            </button>

            <button data-action="insert-link-modal" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                <span>Tautan (Link)</span>
            </button>

            <button onclick="window.documentEngine.insertHorizontalRule()" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                <span>Garis Pemisah</span>
            </button>

            <button onclick="window.documentEngine.insertPageBreak()" class="px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 rounded font-medium flex items-center gap-1.5 hover:bg-indigo-100 transition">
                <span>📄 Pemisah Halaman (Page Break)</span>
            </button>

            <button onclick="window.documentEngine.insertCallout('info', 'Catatan Penelitian', 'Informasi terverifikasi untuk analisis bab selanjutnya.')" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition">
                <span>💬 Callout Box</span>
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
        </div>

        <!-- Ribbon Panel 4: TAMPILAN (VIEW) -->
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
                <button id="btn-toggle-dark-sheet" class="px-2 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1 cursor-pointer" title="Ganti Lembar Terang/Gelap">
                    <span>🌙 Lembar Gelap</span>
                </button>
            </div>

            <button data-action="open-shortcuts-modal" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 rounded text-xs flex items-center gap-1">
                <span>⌨️ Pintasan Keyboard (Ctrl+/)</span>
            </button>
        </div>

        <!-- Ribbon Panel 5: ASISTEN AI WORDCRAFT -->
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
        <section id="document-canvas-container" class="flex-1 bg-gray-200/70 dark:bg-gray-950 overflow-y-auto overflow-x-hidden flex flex-col items-center p-4 sm:p-8 relative">

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

            <!-- A4 Document Multi-Page Sheets Container -->
            <div id="document-pages-wrapper" class="w-full flex flex-col items-center">
                <!-- Initial / SSR Document Page Sheet -->
                <article id="document-page-canvas" class="document-page-sheet page-a4 relative flex flex-col justify-between" data-page-index="1">
                    <!-- Page Sheet Header -->
                    <header class="page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none no-print">
                        <span class="header-doc-title truncate max-w-[60%] font-medium">{{ $initialTitle }}</span>
                        <span class="header-chapter-name text-gray-400 font-serif italic text-[11px]"></span>
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

                    <!-- Page Footer Simulation (Print Layout) -->
                    <footer class="page-sheet-footer flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none no-print">
                        <span class="text-[11px] text-gray-400 font-mono">Dokumen VelScribe Wordcraft</span>
                        <span class="footer-page-num text-xs font-semibold text-gray-500 font-mono">Halaman 1 dari 1</span>
                    </footer>
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

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-12 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

</body>
</html>
