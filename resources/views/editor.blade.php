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

        /* Visual Theme Option Cards in Modal */
        .theme-option-card.active {
            border-color: #4f46e5 !important;
            background-color: rgba(99, 102, 241, 0.08) !important;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25) !important;
        }

        .theme-option-card.active .theme-check-badge {
            display: flex !important;
        }

        /* Segmented Theme Switcher Active State */
        .theme-segment-btn.active {
            background-color: #ffffff;
            color: #4f46e5 !important;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .dark .theme-segment-btn.active {
            background-color: #4f46e5;
            color: #ffffff !important;
            box-shadow: 0 1px 4px rgba(79, 70, 229, 0.4);
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
            transition: transform 0.15s ease-out, padding 0.2s ease, width 0.2s ease, min-height 0.2s ease;
            box-sizing: border-box;
            border-radius: 2px;
            position: relative;
        }

        /* Paper Sizes: Portrait */
        .document-page-sheet.page-a4 { width: 210mm; min-height: 297mm; }
        .document-page-sheet.page-letter { width: 215.9mm; min-height: 279.4mm; }
        .document-page-sheet.page-legal { width: 215.9mm; min-height: 355.6mm; }
        .document-page-sheet.page-folio { width: 215mm; min-height: 330mm; }
        .document-page-sheet.page-executive { width: 184.1mm; min-height: 266.7mm; }
        .document-page-sheet.page-a3 { width: 297mm; min-height: 420mm; }
        .document-page-sheet.page-a5 { width: 148mm; min-height: 210mm; }
        .document-page-sheet.page-b5 { width: 182mm; min-height: 257mm; }
        .document-page-sheet.page-statement { width: 139.7mm; min-height: 215.9mm; }
        .document-page-sheet.page-tabloid { width: 279.4mm; min-height: 431.8mm; }

        /* Document Orientation: Landscape (Dynamic for all paper sizes) */
        #document-page-canvas.page-landscape,
        .document-page-sheet.page-landscape {
            width: 297mm !important;
            min-height: 210mm !important;
        }
        .document-page-sheet.page-a4.page-landscape { width: 297mm !important; min-height: 210mm !important; }
        .document-page-sheet.page-letter.page-landscape { width: 279.4mm !important; min-height: 215.9mm !important; }
        .document-page-sheet.page-legal.page-landscape { width: 355.6mm !important; min-height: 215.9mm !important; }
        .document-page-sheet.page-folio.page-landscape { width: 330mm !important; min-height: 215mm !important; }
        .document-page-sheet.page-executive.page-landscape { width: 266.7mm !important; min-height: 184.1mm !important; }
        .document-page-sheet.page-a3.page-landscape { width: 420mm !important; min-height: 297mm !important; }
        .document-page-sheet.page-a5.page-landscape { width: 210mm !important; min-height: 148mm !important; }
        .document-page-sheet.page-b5.page-landscape { width: 257mm !important; min-height: 182mm !important; }
        .document-page-sheet.page-statement.page-landscape { width: 215.9mm !important; min-height: 139.7mm !important; }
        .document-page-sheet.page-tabloid.page-landscape { width: 431.8mm !important; min-height: 279.4mm !important; }

        /* Paragraph Indentation and Typography Helpers */
        .page-sheet-body p.indent-first-line { text-indent: 1.25cm; }
        .page-sheet-body p.indent-hanging { text-indent: -1.25cm; padding-left: 1.25cm; }

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
            min-width: 0;
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

        /* Tab stops are inline positions, not paragraph indentation. */
        .page-sheet-body .document-tab {
            display: inline-block;
            vertical-align: baseline;
            height: 1em;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            user-select: none;
        }
        .page-sheet-body .document-tab[data-tab-leader="dots"]::after { content: '................................................................'; letter-spacing: 0.1em; }
        .page-sheet-body .document-tab[data-tab-leader="dashes"]::after { content: '--------------------------------'; letter-spacing: 0.1em; }
        .page-sheet-body .document-tab[data-tab-leader="underline"]::after { content: '________________________________'; letter-spacing: 0.1em; }

        .page-sheet-body .document-comment { background: #fef08a; box-decoration-break: clone; }
        .page-sheet-body .document-comment[data-comment-resolved="true"] { background: #e5e7eb; }

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
            max-width: 100%;
            table-layout: fixed;
            margin: 12pt 0;
            font-size: 10.5pt;
        }

        /* Imported Word grid widths are proportions, not permission to exceed the paper. */
        .page-sheet-body table th,
        .page-sheet-body table td {
            overflow-wrap: anywhere;
            word-break: normal;
            min-width: 0;
        }

        .page-sheet-body table img {
            max-width: 100%;
            height: auto;
        }

        #document-editor table.wordcraft-wide-table,
        .page-sheet-body table.wordcraft-wide-table {
            font-size: 8pt;
        }

        #document-editor table.wordcraft-wide-table th,
        #document-editor table.wordcraft-wide-table td,
        .page-sheet-body table.wordcraft-wide-table th,
        .page-sheet-body table.wordcraft-wide-table td {
            padding: 2pt;
            font-size: inherit;
        }

        #document-editor table.wordcraft-wide-table p,
        .page-sheet-body table.wordcraft-wide-table p {
            margin: 0;
            line-height: 1.2;
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

        /* Drop Cap (Huruf Awal Kapital Besar Word) */
        .drop-cap {
            float: left;
            line-height: 0.82;
            font-weight: 700;
            padding-top: 3px;
            padding-right: 6px;
            padding-bottom: 2px;
            font-size: 3.2em;
            color: inherit;
            user-select: none;
        }

        /* Microsoft Word Formatting Marks (Pilcrow ¶ Mode) */
        .show-formatting-marks p::after,
        .show-formatting-marks h1::after,
        .show-formatting-marks h2::after,
        .show-formatting-marks h3::after,
        .show-formatting-marks h4::after,
        .show-formatting-marks h5::after,
        .show-formatting-marks h6::after,
        .show-formatting-marks blockquote::after {
            content: " ¶";
            color: #94a3b8;
            font-weight: normal;
            font-size: 0.8em;
            user-select: none;
            pointer-events: none;
            opacity: 0.65;
        }

        .show-formatting-marks p:empty::before {
            content: "¶";
            color: #94a3b8;
            font-weight: normal;
            font-size: 0.8em;
            user-select: none;
            pointer-events: none;
            opacity: 0.65;
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

        .page-sheet-body { position: relative; }

        /* The frame moves the picture, never the caption or surrounding paragraphs. */
        .page-sheet-body .wordcraft-image-frame {
            display: block;
            width: fit-content;
            max-width: 100%;
            margin: 8pt 0;
        }
        .page-sheet-body .wordcraft-image-frame img { margin: 0 !important; max-width: 100%; height: auto; display: block; }
        .page-sheet-body .wordcraft-image-frame[data-image-align="center"] { margin-left: auto; margin-right: auto; }
        .page-sheet-body .wordcraft-image-frame[data-image-align="right"] { margin-left: auto; margin-right: 0; }
        .page-sheet-body .wordcraft-image-frame[data-image-mode="wrap-left"] { float: left; margin: 0 12pt 8pt 0; }
        .page-sheet-body .wordcraft-image-frame[data-image-mode="wrap-right"] { float: right; margin: 0 0 8pt 12pt; }
        .page-sheet-body .wordcraft-image-frame[data-image-mode="top-bottom"] { clear: both; }
        .page-sheet-body .wordcraft-image-frame[data-image-mode="floating"] {
            position: absolute;
            z-index: 5;
            margin: 0;
            cursor: grab;
            touch-action: none;
        }
        .page-sheet-body .wordcraft-image-frame[data-image-mode="floating"]:active { cursor: grabbing; }

        .page-sheet-body .wordcraft-image-frame img { cursor: grab; }
        .page-sheet-body .wordcraft-image-frame img:active { cursor: grabbing; }
        .wordcraft-drop-marker {
            position: fixed;
            height: 3px;
            background: #4f46e5;
            border-radius: 2px;
            z-index: 60;
            pointer-events: none;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
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

        /* Microsoft Word Line Numbers Simulation */
        .document-page-sheet.show-line-numbers {
            counter-reset: line-counter;
        }
        .document-page-sheet.show-line-numbers .page-sheet-body > p,
        .document-page-sheet.show-line-numbers .page-sheet-body > h1,
        .document-page-sheet.show-line-numbers .page-sheet-body > h2,
        .document-page-sheet.show-line-numbers .page-sheet-body > h3 {
            position: relative;
            counter-increment: line-counter;
        }
        .document-page-sheet.show-line-numbers .page-sheet-body > p::before,
        .document-page-sheet.show-line-numbers .page-sheet-body > h1::before,
        .document-page-sheet.show-line-numbers .page-sheet-body > h2::before,
        .document-page-sheet.show-line-numbers .page-sheet-body > h3::before {
            content: counter(line-counter);
            position: absolute;
            left: -2.2rem;
            width: 1.8rem;
            text-align: right;
            font-family: monospace;
            font-size: 9px;
            color: #9ca3af;
            user-select: none;
            pointer-events: none;
        }
        .document-page-sheet.show-line-numbers .page-sheet-body > .suppress-line-numbers::before {
            display: none !important;
        }

        /* Microsoft Word Paragraph Spacing: Don't add space between paragraphs of the same style */
        .document-page-sheet .page-sheet-body > [data-dont-add-space-same="true"] + [data-dont-add-space-same="true"] {
            margin-top: 0 !important;
        }

        /* Watermark & Page Border Helpers */
        .page-sheet-watermark {
            pointer-events: none;
            user-select: none;
        }
        .page-sheet-border-frame {
            pointer-events: none;
            box-sizing: border-box;
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
            <div class="flex items-center gap-1">
                <button
                    id="theme-toggle-btn"
                    type="button"
                    role="switch"
                    aria-checked="false"
                    class="group relative inline-flex items-center gap-2 px-2.5 py-1 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 transition-all cursor-pointer shadow-2xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    title="Ganti Tema Tampilan (Terang / Gelap)"
                >
                    <!-- Visual Switch Track -->
                    <div class="relative w-12 h-6 rounded-full bg-amber-100 dark:bg-indigo-950/80 border border-amber-300 dark:border-indigo-800/80 transition-colors px-1 flex items-center justify-between pointer-events-none">
                        <!-- Sun Icon (Gold/Amber) -->
                        <span id="theme-icon-sun" class="text-[11px] transition-opacity duration-200 text-amber-500 leading-none">☀️</span>
                        <!-- Moon Icon (Indigo/Blue) -->
                        <span id="theme-icon-moon" class="text-[11px] transition-opacity duration-200 text-indigo-400 opacity-40 leading-none">🌙</span>
                        <!-- Sliding Knob with Active State Glow -->
                        <span id="theme-toggle-knob" class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow-md transform translate-x-0 transition-transform duration-300 ease-in-out border border-gray-200 dark:border-indigo-400 flex items-center justify-center">
                            <span id="knob-icon" class="text-[10px] leading-none select-none">☀️</span>
                        </span>
                    </div>
                    <!-- Label Text -->
                    <span id="theme-toggle-text" class="text-xs font-semibold text-gray-700 dark:text-gray-200 hidden lg:inline select-none">
                        Mode Terang
                    </span>
                </button>

                <!-- Quick Theme Customization Modal Trigger -->
                <button
                    data-action="open-theme-modal"
                    class="p-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                    title="Buka Pengaturan Tema & Visual Tampilan"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4h4a4 4 0 014 4 4 4 0 01-4 4H7zm0 0v-8m0 0a4 4 0 014-4h4a4 4 0 014 4v8m-4-8a4 4 0 00-4-4H7a4 4 0 00-4 4v4"></path>
                    </svg>
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
                    <option value="2.5">Spasi 2.5</option>
                    <option value="3.0">Spasi 3.0</option>
                </select>

                <!-- Microsoft Word Style Line & Paragraph Spacing Dropdown -->
                <div class="relative inline-block" id="line-spacing-dropdown-wrapper">
                    <button id="btn-line-spacing-dropdown" type="button" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded flex items-center gap-0.5 transition cursor-pointer" title="Spasi Baris dan Paragraf (Microsoft Word)">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16M7 4v16m0-16l-2 2m2-2l2 2m-2 16l-2-2m2 2l2-2" />
                        </svg>
                        <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="line-spacing-dropdown-menu" class="hidden absolute left-0 mt-1 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <button type="button" data-spacing-val="1.0" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between">
                            <span>1.0 (Tunggal)</span>
                            <span class="text-[10px] text-gray-400">Single</span>
                        </button>
                        <button type="button" data-spacing-val="1.15" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between">
                            <span>1.15</span>
                        </button>
                        <button type="button" data-spacing-val="1.5" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between font-semibold text-indigo-600 dark:text-indigo-400">
                            <span>1.5 (Standar Skripsi)</span>
                            <span class="text-[10px]">1.5 Lines</span>
                        </button>
                        <button type="button" data-spacing-val="2.0" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between">
                            <span>2.0 (Ganda)</span>
                            <span class="text-[10px] text-gray-400">Double</span>
                        </button>
                        <button type="button" data-spacing-val="2.5" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between">
                            <span>2.5</span>
                        </button>
                        <button type="button" data-spacing-val="3.0" class="spacing-option-item w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center justify-between">
                            <span>3.0</span>
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <button type="button" data-action="add-space-before" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>➕ Tambah Spasi Sebelum Paragraf</span>
                        </button>
                        <button type="button" data-action="remove-space-before" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 text-gray-500">
                            <span>➖ Hapus Spasi Sebelum Paragraf</span>
                        </button>
                        <button type="button" data-action="add-space-after" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>➕ Tambah Spasi Sesudah Paragraf</span>
                        </button>
                        <button type="button" data-action="remove-space-after" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 text-gray-500">
                            <span>➖ Hapus Spasi Sesudah Paragraf</span>
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <button type="button" data-action="open-paragraph-modal" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 font-medium text-indigo-600 dark:text-indigo-400 flex items-center justify-between">
                            <span>Opsi Spasi Baris...</span>
                            <span>↘</span>
                        </button>
                    </div>
                </div>
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

                <!-- Paragraph Shading (Microsoft Word Paint Bucket) -->
                <div class="relative inline-block" id="paragraph-shading-wrapper">
                    <button id="btn-paragraph-shading-dropdown" type="button" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition flex items-center gap-0.5" title="Warna Latar Paragraf (Paragraph Shading)">
                        <svg class="w-4 h-4 text-amber-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16.56 8.94L7.62 0 6.21 1.41l2.38 2.38-5.15 5.15c-.59.59-.59 1.54 0 2.12l5.5 5.5c.29.29.68.44 1.06.44s.77-.15 1.06-.44l5.5-5.5c.59-.58.59-1.53 0-2.12zM5.21 10L10 5.21 14.79 10H5.21zM19 11.5s-2 2.17-2 3.5c0 1.1.9 2 2 2s2-.9 2-2c0-1.33-2-3.5-2-3.5z"/>
                        </svg>
                        <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="paragraph-shading-dropdown-menu" class="hidden absolute left-0 mt-1 w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 p-2 text-xs space-y-2">
                        <div class="text-[10px] font-bold text-gray-400 uppercase">Warna Latar Paragraf</div>
                        <div class="grid grid-cols-4 gap-1.5">
                            <button type="button" data-para-shading="#fef08a" class="w-full h-6 rounded border border-amber-300 bg-[#fef08a]" title="Kuning Sorotan"></button>
                            <button type="button" data-para-shading="#fef9c3" class="w-full h-6 rounded border border-amber-200 bg-[#fef9c3]" title="Kuning Muda"></button>
                            <button type="button" data-para-shading="#e0f2fe" class="w-full h-6 rounded border border-sky-300 bg-[#e0f2fe]" title="Biru Lembut"></button>
                            <button type="button" data-para-shading="#dcfce7" class="w-full h-6 rounded border border-emerald-300 bg-[#dcfce7]" title="Hijau Muda"></button>
                            <button type="button" data-para-shading="#f3e8ff" class="w-full h-6 rounded border border-purple-300 bg-[#f3e8ff]" title="Lavender"></button>
                            <button type="button" data-para-shading="#ffe4e6" class="w-full h-6 rounded border border-rose-300 bg-[#ffe4e6]" title="Merah Muda"></button>
                            <button type="button" data-para-shading="#f3f4f6" class="w-full h-6 rounded border border-gray-300 bg-[#f3f4f6]" title="Abu-abu Lembut"></button>
                            <button type="button" data-para-shading="#e5e7eb" class="w-full h-6 rounded border border-gray-400 bg-[#e5e7eb]" title="Abu-abu"></button>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-1 flex items-center justify-between">
                            <span class="text-[11px] text-gray-500">Kustom:</span>
                            <input type="color" id="input-custom-para-shading" value="#fef9c3" class="w-6 h-6 p-0 border border-gray-300 dark:border-gray-700 rounded cursor-pointer" />
                        </div>
                        <button type="button" data-para-shading="none" class="w-full py-1 text-center bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                            Tanpa Latar (No Color)
                        </button>
                    </div>
                </div>

                <!-- Paragraph Borders (Microsoft Word Paragraph Borders Menu) -->
                <div class="relative inline-block" id="paragraph-borders-wrapper">
                    <button id="btn-paragraph-borders-dropdown" type="button" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition flex items-center gap-0.5" title="Batas Garis Paragraf (Paragraph Borders)">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="1" stroke-width="2"/><line x1="3" y1="12" x2="21" y2="12" stroke-width="1.5" stroke-dasharray="2 2"/><line x1="12" y1="3" x2="12" y2="21" stroke-width="1.5" stroke-dasharray="2 2"/></svg>
                        <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="paragraph-borders-dropdown-menu" class="hidden absolute left-0 mt-1 w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <button type="button" data-para-border="bottom" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>_</span> Batas Bawah (Bottom Border)
                        </button>
                        <button type="button" data-para-border="top" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>‾</span> Batas Atas (Top Border)
                        </button>
                        <button type="button" data-para-border="left" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 font-medium text-indigo-600">
                            <span>|</span> Batas Kiri (Left / Quote Bar)
                        </button>
                        <button type="button" data-para-border="right" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>|</span> Batas Kanan (Right Border)
                        </button>
                        <button type="button" data-para-border="box" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>□</span> Kotak Paragraf (Outside Borders)
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <button type="button" data-para-border="none" class="w-full px-3 py-1.5 text-left hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 flex items-center gap-2">
                            <span>✕</span> Tanpa Batas (No Border)
                        </button>
                    </div>
                </div>

                <!-- Sort Paragraphs (Microsoft Word A-Z) -->
                <button type="button" id="btn-sort-paragraphs-modal" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition font-bold text-[11px] flex items-center" title="Urutkan Paragraf Alfabetis (A-Z)">
                    <span>A↓Z</span>
                </button>

                <!-- Show/Hide Formatting Marks (Microsoft Word Pilcrow ¶) -->
                <button type="button" id="btn-toggle-formatting-marks" class="p-1.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition font-serif font-black text-xs leading-none" title="Tampilkan/Sembunyikan Tanda Pemformatan (¶ Pilcrow - Format Paragraf Word)">
                    ¶
                </button>

                <!-- Text Direction (LTR / RTL BiDi) -->
                <div class="flex items-center gap-0.5 border border-gray-200 dark:border-gray-700 rounded p-0.5" title="Arah Teks Paragraf (Bi-Directional LTR / RTL)">
                    <button type="button" id="btn-text-dir-ltr" class="px-1 py-0.5 text-[10px] font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded" title="Arah Teks Kiri ke Kanan (LTR)">
                        LTR
                    </button>
                    <button type="button" id="btn-text-dir-rtl" class="px-1 py-0.5 text-[10px] font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded" title="Arah Teks Kanan ke Kiri (RTL)">
                        RTL
                    </button>
                </div>

                <button id="btn-open-paragraph-modal" data-action="open-paragraph-modal" class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition ml-0.5" title="Kotak Dialog Format Paragraf (Microsoft Word Paragraph Options)">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
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
            <button id="btn-insert-toc" class="px-3 py-1.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 rounded font-semibold flex items-center gap-1.5 hover:bg-indigo-200 transition cursor-pointer">
                <span>📑 Daftar Isi Otomatis</span>
            </button>

            <!-- Insert Bibliography (Daftar Pustaka) -->
            <button data-action="insert-bibliography-quick" class="px-3 py-1.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 rounded font-semibold flex items-center gap-1.5 hover:bg-indigo-200 transition cursor-pointer" title="Sisipkan Daftar Pustaka (Bibliography) Otomatis seperti Word">
                <span>📚 Daftar Pustaka</span>
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

            <!-- Drop Cap (Microsoft Word Drop Cap Suite) -->
            <div class="relative inline-block" id="drop-cap-dropdown-wrapper">
                <button id="btn-drop-cap-dropdown" type="button" class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition" title="Huruf Awal Kapital Besar (Drop Cap)">
                    <span class="font-serif font-black text-sm text-indigo-600">A</span>
                    <span>Drop Cap</span>
                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div id="drop-cap-dropdown-menu" class="hidden absolute left-0 mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                    <button type="button" data-drop-cap-preset="none" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                        <span>❌</span> Tidak Ada (None)
                    </button>
                    <button type="button" data-drop-cap-preset="dropped" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                        <span class="font-serif font-black text-indigo-600">A</span> Turun di Naskah (Dropped)
                    </button>
                    <button type="button" data-drop-cap-preset="margin" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                        <span class="font-serif font-black text-indigo-600">|A</span> Di Margin (In Margin)
                    </button>
                    <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                    <button type="button" data-action="open-drop-cap-modal" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-medium">
                        Opsi Drop Cap...
                    </button>
                </div>
            </div>
        </div>

        <!-- Ribbon Panel 3: TATA LETAK (LAYOUT) -->
        <div data-ribbon-panel="layout" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs">
            <!-- Group 1: Page Setup (Margins, Size, Orientation, Dialog Launcher) -->
            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[10px] font-bold uppercase tracking-wider">Halaman:</span>

                <!-- Preset Margin -->
                <div class="flex items-center gap-1.5" title="Margin Halaman (Microsoft Word)">
                    <span class="text-gray-500 font-bold">Margin:</span>
                    <select id="layout-margins" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none font-medium">
                        <option value="skripsi" selected>Skripsi / Tesis (Atas 4, Kiri 4, Kanan 3, Bawah 3 cm)</option>
                        <option value="normal">Normal (2.54 cm / 1 inch)</option>
                        <option value="narrow">Sempit (1.27 cm)</option>
                        <option value="moderate">Sedang (2.54 x 1.91 cm)</option>
                        <option value="wide">Lebar (2.54 x 5.08 cm / 2 in)</option>
                        <option value="mirrored">Margin Cermin (Mirrored)</option>
                        <option value="office2003">Office 2003 Standar (2.54 x 3.18 cm)</option>
                        <option value="custom">Margin Kustom...</option>
                    </select>
                </div>

                <!-- Ukuran Kertas -->
                <div class="flex items-center gap-1.5" title="Ukuran Kertas Dokumen">
                    <span class="text-gray-500 font-bold">Ukuran:</span>
                    <select id="layout-paper-size" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none font-medium">
                        <option value="a4" selected>A4 (210 x 297 mm)</option>
                        <option value="letter">Letter (8.5 x 11 in)</option>
                        <option value="legal">Legal (8.5 x 14 in)</option>
                        <option value="folio">Folio / F4 (215 x 330 mm)</option>
                        <option value="executive">Executive (184.1 x 266.7 mm)</option>
                        <option value="a3">A3 (297 x 420 mm)</option>
                        <option value="a5">A5 (148 x 210 mm)</option>
                        <option value="b5">B5 (182 x 257 mm)</option>
                        <option value="statement">Statement (139.7 x 215.9 mm)</option>
                        <option value="tabloid">Tabloid (11 x 17 in / 279.4 x 431.8 mm)</option>
                        <option value="custom">Ukuran Kustom...</option>
                    </select>
                </div>

                <!-- Orientasi -->
                <div class="flex items-center gap-1.5" title="Orientasi Halaman">
                    <span class="text-gray-500 font-bold">Orientasi:</span>
                    <select id="layout-orientation" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs outline-none font-medium">
                        <option value="portrait" selected>Potret (Portrait)</option>
                        <option value="landscape">Lanskap (Landscape)</option>
                    </select>
                </div>

                <!-- Page Setup Dialog Launcher Button -->
                <button id="btn-open-page-setup-modal" data-action="page-setup-modal" class="p-1 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Penyetelan Halaman Lengkap (Microsoft Word Page Setup Dialog)">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </button>
            </div>

            <!-- Group 1b: Columns, Breaks & Hyphenation Suite (Microsoft Word Parity) -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <!-- Columns Dropdown -->
                <div class="relative inline-block" id="columns-dropdown-wrapper">
                    <button id="btn-columns-dropdown" type="button" class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Format Kolom Dokumen (Columns)">
                        <span>🏛️ Kolom</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="columns-dropdown-menu" class="hidden absolute left-0 mt-1 w-44 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <button type="button" data-column-val="1" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 font-semibold">
                            <span>📄</span> Satu Kolom (Standar)
                        </button>
                        <button type="button" data-column-val="2" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📰</span> Dua Kolom (Jurnal)
                        </button>
                        <button type="button" data-column-val="3" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📑</span> Tiga Kolom (Buletin)
                        </button>
                        <button type="button" data-column-val="left" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>◀️</span> Kiri (1/3 : 2/3)
                        </button>
                        <button type="button" data-column-val="right" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>▶️</span> Kanan (2/3 : 1/3)
                        </button>
                    </div>
                </div>

                <!-- Breaks Dropdown -->
                <div class="relative inline-block" id="breaks-dropdown-wrapper">
                    <button id="btn-breaks-dropdown" type="button" class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Pemisah Halaman dan Bagian (Breaks)">
                        <span>✂️ Pemisah</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="breaks-dropdown-menu" class="hidden absolute left-0 mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase">Pemisah Halaman</div>
                        <button type="button" data-break-type="page" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📄</span> Halaman Baru (Page Break)
                        </button>
                        <button type="button" data-break-type="column" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>🏛️</span> Kolom (Column Break)
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase">Pemisah Bagian (Section)</div>
                        <button type="button" data-break-type="section-next" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📑</span> Halaman Berikutnya
                        </button>
                        <button type="button" data-break-type="section-continuous" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📜</span> Berkelanjutan (Continuous)
                        </button>
                    </div>
                </div>

                <!-- Hyphenation Dropdown -->
                <div class="relative inline-block" id="hyphenation-dropdown-wrapper">
                    <button id="btn-hyphenation-dropdown" type="button" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Pemenggalan Kata / Tanda Hubung (Hyphenation)">
                        <span>🔤 Tanda Hubung</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="hyphenation-dropdown-menu" class="hidden absolute left-0 mt-1 w-40 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <button type="button" data-hyphen-val="none" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 font-semibold text-indigo-600">
                            Tidak Ada (None)
                        </button>
                        <button type="button" data-hyphen-val="auto" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800">
                            Otomatis (Automatic)
                        </button>
                    </div>
                </div>

                <!-- Tabs Dialog Launcher Button -->
                <button type="button" id="btn-layout-open-tabs-modal" data-action="open-tabs-modal" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Atur Perhentian Tabulasi (Tabs Dialog)">
                    <span>⇥ Tab</span>
                </button>

                <!-- Line Numbers Dropdown (Microsoft Word Standard) -->
                <div class="relative inline-block" id="line-numbers-dropdown-wrapper">
                    <button id="btn-line-numbers-dropdown" type="button" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Penomoran Baris Dokumen (Line Numbers)">
                        <span>🔢 Baris</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="line-numbers-dropdown-menu" class="hidden absolute left-0 mt-1 w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <button type="button" data-line-number-val="none" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 font-semibold">
                            <span>❌</span> Tidak Ada (None)
                        </button>
                        <button type="button" data-line-number-val="continuous" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📜</span> Berkelanjutan (Continuous)
                        </button>
                        <button type="button" data-line-number-val="page" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📄</span> Mulai Ulang Tiap Halaman
                        </button>
                        <button type="button" data-line-number-val="suppress" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>🚫</span> Abaikan Paragraf Saat Ini
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <button type="button" data-action="open-line-numbers-modal" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-medium">
                            Opsi Penomoran Baris...
                        </button>
                    </div>
                </div>

                <!-- Watermark Dropdown (Microsoft Word Standard) -->
                <div class="relative inline-block" id="watermark-dropdown-wrapper">
                    <button id="btn-watermark-dropdown" type="button" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Cap Air Dokumen (Watermark)">
                        <span>💧 Cap Air</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="watermark-dropdown-menu" class="hidden absolute left-0 mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 py-1 text-xs text-gray-800 dark:text-gray-200">
                        <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase">Standar Word</div>
                        <button type="button" data-watermark-preset="DRAF" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📝</span> DRAF (Draft)
                        </button>
                        <button type="button" data-watermark-preset="RAHASIA" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2 font-semibold text-red-600">
                            <span>🔒</span> RAHASIA (Confidential)
                        </button>
                        <button type="button" data-watermark-preset="JANGAN DISALIN" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>🚫</span> JANGAN DISALIN
                        </button>
                        <button type="button" data-watermark-preset="CONTOH" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 flex items-center gap-2">
                            <span>📋</span> CONTOH (Sample)
                        </button>
                        <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                        <button type="button" data-action="open-watermark-modal" class="w-full px-3 py-1.5 text-left hover:bg-indigo-50 dark:hover:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-medium">
                            Cap Air Kustom (Custom)...
                        </button>
                        <button type="button" data-action="remove-watermark" class="w-full px-3 py-1.5 text-left hover:bg-red-50 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400">
                            Hapus Cap Air (Remove)
                        </button>
                    </div>
                </div>

                <!-- Page Color Dropdown (Microsoft Word Standard) -->
                <div class="relative inline-block" id="page-color-dropdown-wrapper">
                    <button id="btn-page-color-dropdown" type="button" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Warna Latar Lembar Dokumen (Page Color)">
                        <span>🎨 Warna</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="page-color-dropdown-menu" class="hidden absolute left-0 mt-1 w-52 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 p-2 text-xs text-gray-800 dark:text-gray-200 space-y-2">
                        <div class="text-[10px] font-bold text-gray-400 uppercase">Warna Standar Kertas</div>
                        <div class="grid grid-cols-4 gap-1.5">
                            <button type="button" data-page-color="#ffffff" class="w-full h-6 rounded border border-gray-300 bg-white" title="Putih Standar"></button>
                            <button type="button" data-page-color="#fdfbf7" class="w-full h-6 rounded border border-gray-300 bg-[#fdfbf7]" title="Ivory / Gading"></button>
                            <button type="button" data-page-color="#fefae0" class="w-full h-6 rounded border border-gray-300 bg-[#fefae0]" title="Parchment / Kertas Tua"></button>
                            <button type="button" data-page-color="#fef9c3" class="w-full h-6 rounded border border-amber-200 bg-[#fef9c3]" title="Kuning Lembut"></button>
                            <button type="button" data-page-color="#ecfdf5" class="w-full h-6 rounded border border-emerald-200 bg-[#ecfdf5]" title="Mint Segar"></button>
                            <button type="button" data-page-color="#eff6ff" class="w-full h-6 rounded border border-blue-200 bg-[#eff6ff]" title="Biru Lembut"></button>
                            <button type="button" data-page-color="#f5f3ff" class="w-full h-6 rounded border border-purple-200 bg-[#f5f3ff]" title="Lavender"></button>
                            <button type="button" data-page-color="#1f2937" class="w-full h-6 rounded border border-gray-600 bg-[#1f2937]" title="Abu-abu Gelap"></button>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-1 flex items-center justify-between">
                            <span class="text-[11px] text-gray-500">Kustom:</span>
                            <input type="color" id="input-custom-page-color" value="#ffffff" class="w-6 h-6 p-0 border border-gray-300 dark:border-gray-700 rounded cursor-pointer" />
                        </div>
                        <button type="button" data-page-color="none" class="w-full py-1 text-center bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                            Tanpa Warna (Standar)
                        </button>
                    </div>
                </div>

                <!-- Page Borders Button (Microsoft Word Standard) -->
                <button type="button" id="btn-open-page-borders-modal" data-action="open-page-borders-modal" class="px-2 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 flex items-center gap-1 transition" title="Batas Halaman (Page Borders)">
                    <span>🖼️ Batas</span>
                </button>
            </div>

            <!-- Group 2: Word Layout Paragraph Controls (Indent & Spacing Quick Inputs) -->
            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[10px] font-bold uppercase tracking-wider">Paragraf:</span>

                <!-- Indent Left / Right -->
                <div class="flex flex-col gap-0.5">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-gray-500 w-13">Indent Kiri:</span>
                        <div class="flex items-center">
                            <input type="number" id="layout-indent-left" value="0" min="0" max="10" step="0.25" class="w-13 px-1 py-0.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-center outline-none" />
                            <span class="text-[10px] text-gray-400 ml-0.5">cm</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-gray-500 w-13">Indent Kanan:</span>
                        <div class="flex items-center">
                            <input type="number" id="layout-indent-right" value="0" min="0" max="10" step="0.25" class="w-13 px-1 py-0.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-center outline-none" />
                            <span class="text-[10px] text-gray-400 ml-0.5">cm</span>
                        </div>
                    </div>
                </div>

                <!-- Spacing Before / After -->
                <div class="flex flex-col gap-0.5">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-gray-500 w-12">Sebelum:</span>
                        <div class="flex items-center">
                            <input type="number" id="layout-spacing-before" value="0" min="0" max="72" step="6" class="w-13 px-1 py-0.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-center outline-none" />
                            <span class="text-[10px] text-gray-400 ml-0.5">pt</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-gray-500 w-12">Sesudah:</span>
                        <div class="flex items-center">
                            <input type="number" id="layout-spacing-after" value="6" min="0" max="72" step="6" class="w-13 px-1 py-0.5 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-center outline-none" />
                            <span class="text-[10px] text-gray-400 ml-0.5">pt</span>
                        </div>
                    </div>
                </div>

                <!-- Paragraph Dialog Launcher Button -->
                <button id="btn-layout-open-paragraph-modal" data-action="open-paragraph-modal" class="p-1 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition" title="Pengaturan Paragraf Lengkap (Word Dialog)">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </button>
            </div>

            <!-- Group 3: Quick Templates & Headers -->
            <div class="flex items-center gap-2">
                <button onclick="window.documentEngine.setMarginPreset('skripsi'); window.documentEngine.setFontFamily('\'Times New Roman\', serif'); window.documentEngine.setFontSize('12pt'); window.documentEngine.setLineSpacing('1.5'); window.uiController.showToast('Format Skripsi 4-4-3-3 cm diterapkan!');" class="px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-semibold hover:bg-indigo-100 transition">
                    🎓 Terapkan Standar Skripsi
                </button>
                <button data-action="header-footer-modal" class="px-2.5 py-1 rounded bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 font-medium hover:bg-gray-100 transition" title="Konfigurasi running header, footer, dan nomor halaman">
                    📋 Header & Footer
                </button>
                <button data-action="page-setup-modal" class="px-2.5 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition shadow-xs" title="Buka Kotak Dialog Penyetelan Halaman Word">
                    📐 Penyetelan Halaman
                </button>
            </div>
        </div>

        <!-- Ribbon Panel 4: TINJAU & REFERENSI (REVIEW & REFERENCES) -->
        <div data-ribbon-panel="review" class="hidden flex-wrap items-center px-4 py-2 gap-3 overflow-x-auto text-xs bg-indigo-50/20 dark:bg-indigo-950/10">
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[11px] font-semibold uppercase">Komentar:</span>
                <button type="button" id="btn-review-new-comment" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-semibold">Komentar baru</button>
                <button type="button" id="btn-review-comments" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded">Lihat komentar</button>
            </div>
            <!-- Table of Contents Group (Microsoft Word References Standard) -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[11px] font-semibold uppercase">Daftar Isi:</span>

                <!-- 1. TOC Gallery Dropdown Button -->
                <div class="relative inline-block" id="toc-dropdown-wrapper">
                    <button id="btn-toc-dropdown" type="button" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold flex items-center gap-1.5 shadow-xs transition cursor-pointer" title="Galeri Format Daftar Isi Microsoft Word">
                        <span>📑 Daftar Isi</span>
                        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="toc-dropdown-menu" class="hidden absolute left-0 mt-1.5 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl z-50 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                        <div class="px-3 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tabel Otomatis Word</div>

                        <button type="button" data-toc-action="insert-auto1" class="w-full text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 flex items-start gap-2.5 transition cursor-pointer">
                            <span class="text-base">📋</span>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-gray-100">Tabel Otomatis 1</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Judul "Konten", Level 1-3 dengan Titik-titik</div>
                            </div>
                        </button>

                        <button type="button" data-toc-action="insert-auto2" class="w-full text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 flex items-start gap-2.5 transition cursor-pointer">
                            <span class="text-base">📑</span>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-gray-100">Tabel Otomatis 2 (Standar)</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Judul "Daftar Isi", Format Skripsi & Akademik</div>
                            </div>
                        </button>

                        <button type="button" data-toc-action="insert-manual" class="w-full text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 flex items-start gap-2.5 transition cursor-pointer">
                            <span class="text-base">✍️</span>
                            <div>
                                <div class="font-bold text-gray-900 dark:text-gray-100">Tabel Manual</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Templat isian manual dengan placeholder</div>
                            </div>
                        </button>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>

                        <button type="button" data-action="open-custom-toc-modal" class="w-full text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 flex items-center gap-2 font-medium text-indigo-600 dark:text-indigo-400 transition cursor-pointer">
                            <span>⚙️</span>
                            <span>Kustomisasi Daftar Isi...</span>
                        </button>

                        <button type="button" data-toc-action="remove-toc" class="w-full text-left px-3 py-2 hover:bg-red-50 dark:hover:bg-red-950/40 flex items-center gap-2 font-medium text-red-600 dark:text-red-400 transition cursor-pointer">
                            <span>🗑️</span>
                            <span>Hapus Daftar Isi</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Update Table Button (Word Standard) -->
                <button id="btn-ribbon-update-toc" type="button" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition cursor-pointer border border-gray-200 dark:border-gray-700 shadow-2xs" title="Perbarui Nomor Halaman atau Seluruh Tabel Daftar Isi (F9)">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Perbarui Tabel...</span>
                </button>

                <!-- 3. Add Text / Heading Level Selector -->
                <div class="flex items-center gap-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 shadow-2xs" title="Atur Level Teks Terpilih untuk Tampil di Daftar Isi">
                    <span class="text-gray-400 text-[11px] font-medium">Level Teks:</span>
                    <select id="ribbon-toc-level-select" class="text-xs bg-transparent text-gray-800 dark:text-gray-200 font-semibold outline-none cursor-pointer">
                        <option value="" disabled selected>Atur Level...</option>
                        <option value="none">Jangan Tampilkan (Teks Biasa)</option>
                        <option value="1">Level 1 (Heading 1 / Bab)</option>
                        <option value="2">Level 2 (Heading 2 / Subbab)</option>
                        <option value="3">Level 3 (Heading 3 / Anak Subbab)</option>
                    </select>
                </div>
            </div>

            <!-- PUEBI & Tata Bahasa -->
            <div class="flex items-center gap-1.5 pr-3 border-r border-gray-200 dark:border-gray-800">
                <button id="btn-puebi-check" onclick="window.wordcraftAi.executeAction('grammar')" class="px-3 py-1.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 rounded font-semibold flex items-center gap-1.5 hover:bg-emerald-100 transition cursor-pointer">
                    <span>🛠️ Koreksi PUEBI</span>
                </button>
                <button id="btn-word-stats" data-action="open-stats-modal" class="px-2.5 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition cursor-pointer" title="Hitung Kata & Statistik">
                    <span>📊 Statistik</span>
                </button>
                <button id="btn-read-aloud" class="px-2.5 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1 hover:bg-gray-100 transition cursor-pointer" title="Bacakan Naskah Dokumen dengan Suara">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                    <span>Baca Suara</span>
                </button>
            </div>

            <!-- Citations & Bibliography Group (Microsoft Word References Standard) -->
            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-800">
                <span class="text-gray-400 dark:text-gray-500 text-[11px] font-semibold uppercase">Sitasi & Pustaka:</span>

                <!-- 1. Insert In-text Citation -->
                <button data-action="insert-citation-modal" class="px-2.5 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 rounded font-semibold flex items-center gap-1.5 hover:bg-indigo-100 transition cursor-pointer shadow-2xs border border-indigo-200 dark:border-indigo-800" title="Sisipkan Kutipan Sumber di Posisi Kursor (Insert Citation)">
                    <span>➕ Sisipkan Sitasi</span>
                </button>

                <!-- 2. Manage Sources -->
                <button data-action="open-source-manager" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition cursor-pointer border border-gray-200 dark:border-gray-700 shadow-2xs" title="Buka Pengelola Sumber Pustaka (Manage Sources)">
                    <span>📋 Kelola Sumber</span>
                </button>

                <!-- 3. Citation Style Selector -->
                <div class="flex items-center gap-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 shadow-2xs">
                    <span class="text-gray-400 text-[11px] font-medium">Gaya:</span>
                    <select id="ribbon-citation-style-select" class="text-xs bg-transparent text-gray-800 dark:text-gray-200 font-semibold outline-none cursor-pointer">
                        <option value="apa">APA (Edisi 7)</option>
                        <option value="ieee">IEEE [1]</option>
                        <option value="harvard">Harvard</option>
                        <option value="mla">MLA (Edisi 9)</option>
                        <option value="chicago">Chicago</option>
                        <option value="vancouver">Vancouver (1)</option>
                    </select>
                </div>

                <!-- 4. Insert Bibliography (Daftar Pustaka) -->
                <button data-action="insert-bibliography-quick" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold flex items-center gap-1.5 shadow-xs transition cursor-pointer" title="Sisipkan Daftar Pustaka Lengkap Sesuai Standar Word">
                    <span>📚 Sisipkan Daftar Pustaka</span>
                </button>

                <!-- 5. Update Bibliography -->
                <button id="btn-update-bibliography" class="px-2.5 py-1.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 rounded font-medium flex items-center gap-1 hover:bg-emerald-100 transition cursor-pointer border border-emerald-200 dark:border-emerald-800 shadow-2xs" title="Perbarui dan Sinkronkan Seluruh Daftar Pustaka di Dokumen">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Perbarui Pustaka</span>
                </button>

                <!-- 6. Academic Web Search Engine -->
                <button data-action="open-academic-search" class="px-2.5 py-1.5 bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 rounded font-medium flex items-center gap-1.5 hover:bg-purple-100 transition cursor-pointer border border-purple-200 dark:border-purple-800 shadow-2xs" title="Cari Jurnal Ilmiah, Buku, dan Artikel Online (CrossRef, Google Scholar, DOI)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>🔍 Mesin Pencari Pustaka</span>
                </button>
            </div>

            <!-- Mode Fokus Zen -->
            <button data-layout-mode="zen" class="px-2.5 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded font-medium flex items-center gap-1.5 hover:bg-gray-100 transition cursor-pointer">
                <span>🧘 Mode Fokus</span>
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
                <span class="text-gray-400 dark:text-gray-500 text-[11px] font-semibold uppercase">Tema Studio:</span>
                <!-- Segmented Theme Switch -->
                <div class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-gray-800 p-0.5 border border-gray-200 dark:border-gray-700">
                    <button id="segment-theme-light" data-theme-set="light" class="theme-segment-btn px-2 py-1 text-xs font-medium rounded-md transition flex items-center gap-1 cursor-pointer">
                        <span>☀️</span>
                        <span>Terang</span>
                    </button>
                    <button id="segment-theme-dark" data-theme-set="dark" class="theme-segment-btn px-2 py-1 text-xs font-medium rounded-md transition flex items-center gap-1 cursor-pointer">
                        <span>🌙</span>
                        <span>Gelap</span>
                    </button>
                    <button id="segment-theme-system" data-theme-set="system" class="theme-segment-btn px-2 py-1 text-xs font-medium rounded-md transition flex items-center gap-1 cursor-pointer">
                        <span>💻</span>
                        <span>Sistem</span>
                    </button>
                </div>
                <button id="btn-toggle-dark-sheet" class="px-2.5 py-1 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded text-xs flex items-center gap-1.5 cursor-pointer border border-gray-200 dark:border-gray-700 transition" title="Ganti Warna Lembar Kertas (Terang / Gelap)">
                    <span id="sheet-theme-icon">📄</span>
                    <span id="sheet-theme-label">Lembar Putih</span>
                </button>
                <button data-action="open-theme-modal" class="p-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer" title="Buka Pengaturan Tema & Visual Tampilan Lengkap">
                    <span>🎨</span>
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
    <div id="ruler-bar" class="h-6 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-center shrink-0 no-print overflow-hidden relative select-none">
        <div class="h-full flex relative px-0" id="ruler-container" style="width: 210mm; max-width: 100%;">
            <!-- Left Margin Shaded Area -->
            <div id="ruler-left-shade" class="absolute top-0 bottom-0 left-0 bg-gray-200/70 dark:bg-gray-800/80 pointer-events-none" style="width: 40mm;"></div>
            <!-- Right Margin Shaded Area -->
            <div id="ruler-right-shade" class="absolute top-0 bottom-0 right-0 bg-gray-200/70 dark:bg-gray-800/80 pointer-events-none" style="width: 30mm;"></div>

            <!-- Ticks Container -->
            <div class="w-full h-full flex relative" id="ruler-ticks">
                <!-- Rendered dynamically by UiController -->
            </div>

            <!-- Margin indicators on ruler -->
            <div id="ruler-left-margin" class="absolute top-0 bottom-0 w-0.5 bg-indigo-500 opacity-90 cursor-ew-resize z-20" style="left: 40mm;" title="Margin Kiri (Klik ganda untuk Penyetelan Halaman)"></div>
            <div id="ruler-right-margin" class="absolute top-0 bottom-0 w-0.5 bg-indigo-500 opacity-90 cursor-ew-resize z-20" style="left: calc(100% - 30mm);" title="Margin Kanan (Klik ganda untuk Penyetelan Halaman)"></div>

            <!-- First Line Indent Marker (Top Triangle ▽) -->
            <div id="ruler-marker-first-line" class="absolute top-0 w-3 h-2.5 flex items-center justify-center -translate-x-1/2 cursor-pointer z-30 opacity-85 hover:opacity-100 transition-opacity" style="left: 52.5mm;" title="Indentasi Baris Pertama (1.25 cm)">
                <svg class="w-2.5 h-2 text-indigo-700 dark:text-indigo-400 fill-current" viewBox="0 0 10 8"><polygon points="0,0 10,0 5,8"/></svg>
            </div>
            <!-- Hanging & Left Indent Marker (Bottom Triangle △ & Rectangle ▭) -->
            <div id="ruler-marker-left-indent" class="absolute bottom-0 w-3 h-3 flex flex-col items-center justify-end -translate-x-1/2 cursor-pointer z-30 opacity-85 hover:opacity-100 transition-opacity" style="left: 40mm;" title="Indentasi Kiri Paragraf">
                <svg class="w-2.5 h-1.5 text-indigo-700 dark:text-indigo-400 fill-current" viewBox="0 0 10 6"><polygon points="5,0 10,6 0,6"/></svg>
                <div class="w-2 h-1 bg-indigo-700 dark:bg-indigo-400"></div>
            </div>
            <!-- Right Indent Marker (Bottom Triangle △ on Right) -->
            <div id="ruler-marker-right-indent" class="absolute bottom-0 w-3 h-2.5 flex items-center justify-center -translate-x-1/2 cursor-pointer z-30 opacity-85 hover:opacity-100 transition-opacity" style="left: calc(100% - 30mm);" title="Indentasi Kanan Paragraf">
                <svg class="w-2.5 h-2 text-indigo-700 dark:text-indigo-400 fill-current" viewBox="0 0 10 8"><polygon points="5,0 10,8 0,8"/></svg>
            </div>
        </div>
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
                <button type="button" data-table-action="move" class="px-2 py-0.5 rounded bg-indigo-600 hover:bg-indigo-500 cursor-grab active:cursor-grabbing text-[11px] touch-none" title="Geser tabel ke sela paragraf atau halaman lain">⠿ Pindahkan</button>
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
            <div id="floating-image-toolbar" class="hidden fixed bg-gray-900 text-white px-3 py-1.5 rounded-lg shadow-2xl z-40 items-center gap-2 text-xs border border-gray-700 no-print flex-wrap max-w-[min(92vw,580px)]">
                <span class="text-gray-400 font-semibold mr-1">Gambar:</span>
                <button data-img-align="left" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">Kiri</button>
                <button data-img-align="center" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">Tengah</button>
                <button data-img-align="right" class="px-2 py-0.5 hover:bg-gray-800 rounded bg-gray-800 text-[11px]">Kanan</button>
                <span class="w-px h-3 bg-gray-700"></span>
                <button data-img-size="50%" class="px-2 py-0.5 hover:bg-gray-800 rounded text-[11px]">50%</button>
                <button data-img-size="100%" class="px-2 py-0.5 hover:bg-gray-800 rounded text-[11px]">100%</button>
                <label class="sr-only" for="image-layout-mode">Mode tata letak gambar</label>
                <select id="image-layout-mode" class="bg-gray-800 text-white rounded px-1 py-0.5 text-[11px]" title="Atur alur teks dan posisi gambar">
                    <option value="inline">Tanpa pembungkusan</option>
                    <option value="wrap-left">Teks di kanan gambar</option>
                    <option value="wrap-right">Teks di kiri gambar</option>
                    <option value="top-bottom">Teks atas &amp; bawah</option>
                    <option value="floating">Posisi bebas (geser)</option>
                </select>
                <label class="text-gray-300 text-[11px]" for="image-offset-x">X</label>
                <input id="image-offset-x" type="number" min="0" step="1" class="w-14 bg-gray-800 rounded px-1 text-[11px]" title="Posisi horizontal dalam piksel (mode bebas)">
                <label class="text-gray-300 text-[11px]" for="image-offset-y">Y</label>
                <input id="image-offset-y" type="number" min="0" step="1" class="w-14 bg-gray-800 rounded px-1 text-[11px]" title="Posisi vertikal dalam piksel (mode bebas)">
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

            <!-- Progressive Document Page Chunk Loader Indicator -->
            <div id="document-chunk-progress" class="hidden mx-6 sm:mx-14 mt-2 mb-2 p-2.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white/95 dark:bg-gray-800/95 shadow-md flex items-center justify-between gap-3 text-xs transition-all no-print z-20">
                <div class="flex items-center gap-2.5 flex-1 min-w-0">
                    <span class="inline-block animate-spin text-sm" role="status" aria-label="Loading">⏳</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span id="chunk-progress-label">Memuat lembar dokumen secara bertahap...</span>
                            <span id="chunk-progress-percent" class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">0%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div id="chunk-progress-bar" class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-100 ease-out rounded-full" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
                <span id="chunk-progress-page-count" class="text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900 whitespace-nowrap">Halaman 1 / 1</span>
            </div>

            <!-- A4 Document Multi-Page Sheets Container -->
            <div id="document-pages-wrapper" class="w-full flex flex-col items-center relative py-4 px-6 sm:px-14">
                <!-- Vertical Metric Ruler (Microsoft Word Standard) -->
                <div id="vertical-ruler" class="w-5 shrink-0 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col absolute left-2 sm:left-4 top-4 select-none no-print rounded-xs overflow-hidden shadow-2xs z-10" style="height: 297mm;" title="Penggaris Vertikal (Klik ganda untuk Penyetelan Margin)">
                    <!-- Top Margin Shaded Area -->
                    <div id="v-ruler-top-shade" class="w-full bg-gray-200/70 dark:bg-gray-800/80 pointer-events-none absolute top-0 left-0" style="height: 40mm;"></div>
                    <!-- Bottom Margin Shaded Area -->
                    <div id="v-ruler-bottom-shade" class="w-full bg-gray-200/70 dark:bg-gray-800/80 pointer-events-none absolute bottom-0 left-0" style="height: 30mm;"></div>
                    <!-- Vertical Ticks Container -->
                    <div id="vertical-ruler-ticks" class="w-full h-full flex flex-col relative"></div>
                    <!-- Top Margin Indicator line -->
                    <div id="v-ruler-top-margin" class="absolute left-0 right-0 h-0.5 bg-indigo-500 opacity-90 cursor-ns-resize z-20" style="top: 40mm;" title="Margin Atas"></div>
                    <!-- Bottom Margin Indicator line -->
                    <div id="v-ruler-bottom-margin" class="absolute left-0 right-0 h-0.5 bg-indigo-500 opacity-90 cursor-ns-resize z-20" style="bottom: 30mm;" title="Margin Bawah"></div>
                </div>

                <!-- Initial / SSR Document Page Sheet -->
                <article id="document-page-canvas" class="document-page-sheet page-a4 relative flex flex-col justify-between" data-page-index="1">
                    <!-- Page Sheet Header -->
                    <header class="page-sheet-header flex items-center justify-between text-[11px] text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1" title="Header belum diterapkan (Klik untuk mengatur)" data-action="header-footer-modal">
                        <span class="header-doc-title truncate max-w-[55%] font-medium text-gray-600 dark:text-gray-300"></span>
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
                    <footer class="page-sheet-footer flex items-center justify-between text-[11px] text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-800 select-none cursor-pointer transition hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 rounded px-1 -mx-1" title="Footer belum diterapkan (Klik untuk mengatur)" data-action="header-footer-modal">
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
                    <div class="page-side-actions absolute -right-12 sm:-right-14 top-4 select-none no-print flex flex-col items-center gap-1 pointer-events-auto">
                        <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Header: Belum diterapkan (Klik untuk mengatur)">
                            <span class="text-xs">🏷️</span>
                        </button>
                        <span class="side-header-status text-[8px] font-sans text-center leading-tight select-none tracking-tight block text-gray-400 dark:text-gray-500">Header:<br><span class="text-[7.5px] text-amber-500">Belum diterapkan</span></span>
                    </div>
                    <div class="page-side-footer-action absolute -right-12 sm:-right-14 bottom-4 select-none no-print pointer-events-auto flex flex-col items-center gap-1">
                        <button data-action="header-footer-modal" class="p-1.5 rounded-lg bg-white/95 dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-400 hover:text-indigo-600 hover:border-indigo-400 transition" title="Footer: Belum diterapkan (Klik untuk mengatur)">
                            <span class="text-xs">📋</span>
                        </button>
                        <span class="side-footer-status text-[8px] font-sans text-center leading-tight select-none tracking-tight block text-gray-400 dark:text-gray-500">Footer:<br><span class="text-[7.5px] text-amber-500">Belum diterapkan</span></span>
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

    <aside id="review-comments-panel" aria-label="Komentar dokumen" class="hidden fixed right-4 top-36 bottom-12 w-80 max-w-[calc(100vw-2rem)] z-40 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl p-4 overflow-y-auto no-print">
        <div class="flex items-center justify-between gap-3 mb-3">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">Komentar dokumen</h2>
            <button type="button" id="btn-close-review-comments" aria-label="Tutup komentar" class="text-gray-500 hover:text-gray-900 dark:hover:text-gray-100">✕</button>
        </div>
        <label for="review-comment-input" class="text-xs text-gray-600 dark:text-gray-300">Pilih teks dalam satu paragraf, lalu tambahkan komentar</label>
        <textarea id="review-comment-input" maxlength="2000" rows="3" class="mt-2 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 text-sm" placeholder="Tulis komentar..."></textarea>
        <button type="button" id="btn-save-review-comment" class="mb-4 px-3 py-1.5 rounded bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Simpan komentar</button>
        <div id="review-comments-list" class="space-y-2" aria-live="polite"></div>
    </aside>

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

    <!-- Modal 11: Sitasi & Daftar Pustaka (Microsoft Word References Standard) -->
    <div id="modal-citation" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50/70 to-purple-50/70 dark:from-gray-900 dark:to-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 rounded-lg bg-indigo-600 text-white text-sm">📚</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Sitasi & Pengelola Daftar Pustaka (Word Standard)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Mesin pencari literatur ilmiah, kelola sumber referensi, & format daftar pustaka otomatis</p>
                    </div>
                </div>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">✕</button>
            </div>

            <!-- Tab Navigation (Word References Tabs) -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-900/60 px-4 text-xs font-semibold gap-1 select-none overflow-x-auto">
                <button type="button" data-citation-nav="search" class="citation-tab-btn py-2.5 px-3 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-1.5">
                    <span>🔍</span> Mesin Pencari Online
                </button>
                <button type="button" data-citation-nav="add" class="citation-tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 flex items-center gap-1.5">
                    <span>✍️</span> Tambah Sumber Manual
                </button>
                <button type="button" data-citation-nav="manage" class="citation-tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 flex items-center gap-1.5">
                    <span>📋</span> Kelola Sumber (<span id="tab-source-count-badge">0</span>)
                </button>
                <button type="button" data-citation-nav="bibliography" class="citation-tab-btn py-2.5 px-3 border-b-2 border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 flex items-center gap-1.5">
                    <span>📖</span> Sisipkan Daftar Pustaka
                </button>
            </div>

            <!-- Tab Contents (Scrollable) -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                <!-- Tab 1: Mesin Pencari Online -->
                <div id="citation-tab-search" class="citation-tab-content space-y-3.5">
                    <div class="bg-indigo-50/60 dark:bg-indigo-950/30 p-3 rounded-lg border border-indigo-100 dark:border-indigo-900/50 text-[11px] text-indigo-800 dark:text-indigo-300">
                        Cari jutaan publikasi ilmiah dari <strong>CrossRef</strong>, <strong>Google Books</strong>, indeks <strong>DOI</strong>, dan repositori akademik. Klik <em>"Tambah ke Sumber"</em> atau <em>"Sisipkan Sitasi"</em> untuk memasukkannya ke naskah Anda.
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 relative">
                            <input type="text" id="search-source-query" placeholder="Ketik kata kunci judul, topik (mis: IoT ESP32), nama penulis, atau DOI (10.xxxx)..." class="w-full px-3 py-2 pl-8 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 outline-none focus:border-indigo-500 shadow-2xs" />
                            <span class="absolute left-2.5 top-2.5 text-gray-400">🔍</span>
                        </div>
                        <select id="search-source-type" class="px-2.5 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-xs outline-none focus:border-indigo-500">
                            <option value="all">Semua Tipe</option>
                            <option value="journal">Jurnal Ilmiah</option>
                            <option value="book">Buku</option>
                            <option value="conference">Prosiding</option>
                            <option value="thesis">Skripsi/Tesis</option>
                            <option value="website">Website</option>
                        </select>
                        <button type="button" id="btn-execute-source-search" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer transition shrink-0">
                            <span>Cari</span>
                        </button>
                    </div>

                    <!-- Search Suggestions / Quick Chips -->
                    <div class="flex items-center gap-1.5 flex-wrap text-[11px]">
                        <span class="text-gray-400">Contoh pencarian:</span>
                        <button type="button" class="btn-search-chip px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition">TCS34725 ESP32</button>
                        <button type="button" class="btn-search-chip px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition">Sugiyono Metode Penelitian</button>
                        <button type="button" class="btn-search-chip px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition">Deep Learning Transformers</button>
                        <button type="button" class="btn-search-chip px-2 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition">10.1016/j.procs.2023.01.002</button>
                    </div>

                    <!-- Search Results State -->
                    <div id="search-source-status" class="hidden text-xs text-center py-2 text-indigo-600 font-medium">
                        <span class="inline-block animate-spin mr-1">⏳</span> Mencari literatur di CrossRef dan basis data ilmiah...
                    </div>

                    <!-- Results List -->
                    <div id="search-results-list" class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                        <p class="text-xs text-gray-400 italic text-center py-6">Ketik kata kunci atau klik salah satu contoh di atas untuk mencari referensi akademik secara langsung.</p>
                    </div>
                </div>

                <!-- Tab 2: Tambah Sumber Manual -->
                <div id="citation-tab-add" class="citation-tab-content hidden space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe Sumber (Type):</label>
                            <select id="citation-type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500">
                                <option value="journal">Artikel Jurnal Ilmiah</option>
                                <option value="book">Buku</option>
                                <option value="conference">Prosiding Konferensi</option>
                                <option value="thesis">Skripsi / Tesis / Disertasi</option>
                                <option value="website">Situs Web / Dokumen Online</option>
                                <option value="report">Laporan Teknis / Standar</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Penulis / Institusi:</label>
                            <input type="text" id="citation-author" placeholder="Contoh: Sugiyono / Santoso, A. / Smith, John" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun Publikasi:</label>
                            <input type="text" id="citation-year" placeholder="Contoh: 2024" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Karya / Artikel / Buku:</label>
                            <input type="text" id="citation-title" placeholder="Contoh: Metode Penelitian Kuantitatif, Kualitatif, dan R&D" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Jurnal / Penerbit / Konferensi:</label>
                        <input type="text" id="citation-source" placeholder="Contoh: Jurnal Teknologi Informasi / Penerbit Alfabeta / IEEE Sensors" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Volume:</label>
                            <input type="text" id="citation-volume" placeholder="Contoh: 12" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">No / Isu:</label>
                            <input type="text" id="citation-issue" placeholder="Contoh: 3" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Halaman:</label>
                            <input type="text" id="citation-pages" placeholder="Contoh: 145-156" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">DOI / Tautan URL:</label>
                        <input type="text" id="citation-doi" placeholder="Contoh: 10.1016/j.procs.2023.01.002 atau https://..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 outline-none focus:border-indigo-500" />
                    </div>

                    <div class="p-3 rounded-lg bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 text-[11px] text-indigo-700 dark:text-indigo-300">
                        Klik <strong>"Sisipkan Sitasi di Kursor"</strong> untuk langsung membuat tanda kutipan di naskah dan mendaftarkannya ke Pengelola Sumber Word.
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" id="btn-save-source-only" class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition cursor-pointer">
                            💾 Simpan ke Pengelola Sumber
                        </button>
                        <button type="button" id="btn-confirm-citation" class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white transition cursor-pointer shadow-xs">
                            ✍️ Sisipkan Sitasi di Kursor
                        </button>
                    </div>
                </div>

                <!-- Tab 3: Kelola Sumber (Manage Sources) -->
                <div id="citation-tab-manage" class="citation-tab-content hidden space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="relative flex-1">
                            <input type="text" id="manage-source-filter" placeholder="Cari dalam daftar sumber naskah..." class="w-full px-3 py-1.5 pl-8 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-xs outline-none focus:border-indigo-500" />
                            <span class="absolute left-2.5 top-2 text-gray-400">🔍</span>
                        </div>
                        <button type="button" id="btn-add-sample-sources" class="px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded font-semibold text-[11px] hover:bg-indigo-100 transition cursor-pointer border border-indigo-200 dark:border-indigo-800">
                            + Sampel Sumber Ilmiah
                        </button>
                        <button type="button" id="btn-clear-all-sources" class="px-2.5 py-1.5 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded font-medium text-[11px] hover:bg-red-100 transition cursor-pointer border border-red-200 dark:border-red-900">
                            Kosongkan
                        </button>
                    </div>

                    <div id="source-manager-items" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        <!-- Loaded via JS -->
                    </div>
                </div>

                <!-- Tab 4: Sisipkan Daftar Pustaka (Bibliography) -->
                <div id="citation-tab-bibliography" class="citation-tab-content hidden space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Gaya Referensi / Sitasi:</label>
                            <select id="modal-bibliography-style" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold outline-none focus:border-indigo-500">
                                <option value="apa">APA (American Psychological Assoc. - Edisi 7)</option>
                                <option value="ieee">IEEE (Institute of Electrical and Electronics Engineers)</option>
                                <option value="harvard">Harvard (Author-Date)</option>
                                <option value="mla">MLA (Modern Language Assoc. - Edisi 9)</option>
                                <option value="chicago">Chicago (Author-Date - Edisi 17)</option>
                                <option value="vancouver">Vancouver (Numbered)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Bab / Heading Daftar Pustaka:</label>
                            <input type="text" id="modal-bibliography-heading" value="DAFTAR PUSTAKA" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-semibold outline-none focus:border-indigo-500" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Pratinjau Format Daftar Pustaka:</span>
                            <span id="preview-style-badge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200">Standar APA 7th (Hanging Indent 0.5 in)</span>
                        </div>
                        <div id="bibliography-preview-box" class="bg-gray-50 dark:bg-gray-800/80 p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-xs max-h-56 overflow-y-auto font-serif space-y-2">
                            <!-- Live preview loaded dynamically -->
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" id="btn-sync-bibliography-block" class="px-3.5 py-2 text-xs font-semibold rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 transition cursor-pointer">
                            🔄 Perbarui Daftar Pustaka Dokumen
                        </button>
                        <button type="button" id="btn-insert-bibliography-block" class="px-4 py-2 text-xs font-bold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer flex items-center gap-1.5">
                            <span>📚 Sisipkan ke Dokumen</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-gray-500">
                <span id="citation-modal-footer-stats">0 sumber aktif di dokumen saat ini.</span>
                <button type="button" data-close-modal class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition">Tutup</button>
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

    <!-- Modal 13: Pengaturan Tema & Visual Tampilan -->
    <div id="modal-theme-selector" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-800 overflow-hidden transform transition-all">
            <div class="p-4 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎨</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Pengaturan Tema & Tampilan Visual</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Pilih skema warna antarmuka editor dan kontras lembar dokumen</p>
                    </div>
                </div>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-5 text-xs">
                <!-- 1. Pilihan Skema Warna Antarmuka (Cards) -->
                <div>
                    <label class="block font-bold text-gray-800 dark:text-gray-200 mb-2.5">Skema Warna Editor:</label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Card 1: Terang (Light) -->
                        <div data-theme-card="light" class="theme-option-card relative cursor-pointer rounded-xl border-2 border-gray-200 dark:border-gray-700 p-3 transition-all flex flex-col items-center text-center gap-2 hover:border-indigo-400">
                            <!-- Visual Preview Mockup -->
                            <div class="w-full h-16 rounded-lg bg-gray-100 border border-gray-300 p-1.5 flex flex-col gap-1 overflow-hidden shadow-2xs">
                                <div class="w-full h-2.5 bg-white rounded flex items-center px-1 border border-gray-200">
                                    <div class="w-2 h-1 bg-indigo-500 rounded-xs"></div>
                                </div>
                                <div class="flex-1 bg-white rounded border border-gray-200 p-1 flex flex-col justify-center items-center">
                                    <div class="w-3/4 h-1 bg-gray-300 rounded mb-0.5"></div>
                                    <div class="w-1/2 h-1 bg-gray-200 rounded"></div>
                                </div>
                            </div>
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-white flex items-center justify-center gap-1">
                                    <span>☀️</span> Terang
                                </span>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-0.5">Mode Siang Bersih</span>
                            </div>
                            <span class="theme-check-badge hidden absolute top-2 right-2 w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] items-center justify-center">✓</span>
                        </div>

                        <!-- Card 2: Gelap (Dark Studio) -->
                        <div data-theme-card="dark" class="theme-option-card relative cursor-pointer rounded-xl border-2 border-gray-200 dark:border-gray-700 p-3 transition-all flex flex-col items-center text-center gap-2 hover:border-indigo-400">
                            <!-- Visual Preview Mockup -->
                            <div class="w-full h-16 rounded-lg bg-gray-950 border border-gray-800 p-1.5 flex flex-col gap-1 overflow-hidden shadow-2xs">
                                <div class="w-full h-2.5 bg-gray-900 rounded flex items-center px-1 border border-gray-800">
                                    <div class="w-2 h-1 bg-indigo-400 rounded-xs"></div>
                                </div>
                                <div class="flex-1 bg-gray-900 rounded border border-gray-800 p-1 flex flex-col justify-center items-center">
                                    <div class="w-3/4 h-1 bg-gray-700 rounded mb-0.5"></div>
                                    <div class="w-1/2 h-1 bg-gray-800 rounded"></div>
                                </div>
                            </div>
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-white flex items-center justify-center gap-1">
                                    <span>🌙</span> Gelap
                                </span>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-0.5">Studio Malam</span>
                            </div>
                            <span class="theme-check-badge hidden absolute top-2 right-2 w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] items-center justify-center">✓</span>
                        </div>

                        <!-- Card 3: Otomatis Sistem -->
                        <div data-theme-card="system" class="theme-option-card relative cursor-pointer rounded-xl border-2 border-gray-200 dark:border-gray-700 p-3 transition-all flex flex-col items-center text-center gap-2 hover:border-indigo-400">
                            <!-- Visual Preview Mockup (Split) -->
                            <div class="w-full h-16 rounded-lg border border-gray-300 dark:border-gray-700 p-1 flex gap-1 overflow-hidden shadow-2xs bg-gradient-to-r from-gray-100 to-gray-950">
                                <div class="w-1/2 h-full bg-white rounded-l p-1 flex flex-col justify-center items-center">
                                    <span class="text-[10px]">☀️</span>
                                </div>
                                <div class="w-1/2 h-full bg-gray-900 rounded-r p-1 flex flex-col justify-center items-center">
                                    <span class="text-[10px]">🌙</span>
                                </div>
                            </div>
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-white flex items-center justify-center gap-1">
                                    <span>💻</span> Sistem
                                </span>
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 block mt-0.5">Ikuti Perangkat</span>
                            </div>
                            <span class="theme-check-badge hidden absolute top-2 right-2 w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] items-center justify-center">✓</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Pengaturan Lembar Kanvas (Kertas Dokumen) -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                    <label class="block font-bold text-gray-800 dark:text-gray-200 mb-2">Tampilan Lembar Dokumen (Kanvas Kertas):</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label id="sheet-card-white" class="cursor-pointer rounded-lg border-2 border-gray-200 dark:border-gray-700 p-3 flex items-start gap-2.5 transition-all hover:border-indigo-400">
                            <input type="radio" name="sheet-canvas-theme" value="white" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white block">📄 Lembar Putih (Standar Cetak)</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block leading-relaxed">
                                    Format kertas putih asli (WYSIWYG) untuk akurasi cetak skripsi, laporan, dan dokumen resmi.
                                </span>
                            </div>
                        </label>
                        <label id="sheet-card-dark" class="cursor-pointer rounded-lg border-2 border-gray-200 dark:border-gray-700 p-3 flex items-start gap-2.5 transition-all hover:border-indigo-400">
                            <input type="radio" name="sheet-canvas-theme" value="dark" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white block">🕶️ Lembar Gelap (Mode Redup)</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block leading-relaxed">
                                    Mengubah latar kertas jadi hitam arang untuk kenyamanan mata saat membaca di malam hari.
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <span id="theme-status-text" class="text-xs text-gray-500 dark:text-gray-400">Tema tersimpan otomatis</span>
                <div class="flex items-center gap-2">
                    <button data-close-modal class="px-4 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 14: Kustomisasi Daftar Isi (Custom Table of Contents - Microsoft Word Standard) -->
    <div id="modal-custom-toc" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50/70 to-purple-50/70 dark:from-gray-900 dark:to-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 rounded-lg bg-indigo-600 text-white text-sm">📑</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Kustomisasi Format Daftar Isi (Word Standard)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur hierarki level heading, karakter tab leader, dan gaya tipografi daftar isi</p>
                    </div>
                </div>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">✕</button>
            </div>

            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Left: Configuration Options -->
                    <div class="space-y-3.5">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Heading Daftar Isi:</label>
                            <input type="text" id="toc-heading-input" value="DAFTAR ISI" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 font-bold outline-none focus:border-indigo-500" />
                        </div>

                        <div class="space-y-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" id="toc-show-page-numbers" checked class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                                <span class="font-medium text-gray-800 dark:text-gray-200">Tampilkan nomor halaman</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" id="toc-right-align-pages" checked class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                                <span class="font-medium text-gray-800 dark:text-gray-200">Rata kanan nomor halaman</span>
                            </label>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Karakter Tab Leader (Garis Perangkai):</label>
                            <select id="toc-tab-leader-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-medium outline-none focus:border-indigo-500">
                                <option value="dots">Titik-titik (....................)</option>
                                <option value="dashes">Garis putus-putus (--------------------)</option>
                                <option value="line">Garis lurus bawah (____________________)</option>
                                <option value="none">Tanpa garis perangkai (none)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Format Gaya Desain (Formats):</label>
                            <select id="toc-style-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-semibold outline-none focus:border-indigo-500">
                                <option value="template">Dari Templat (Standar Akademik & Skripsi 4-4-3-3)</option>
                                <option value="classic">Klasik (Serif & Kapitalisasi Elegan)</option>
                                <option value="modern">Modern (Sans-serif & Minimalis)</option>
                                <option value="formal">Formal (Border Ganda & Serif Presisi)</option>
                                <option value="simple">Sederhana (Clean Flow)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Tampilkan Level Heading (Show Levels):</label>
                            <select id="toc-levels-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-bold outline-none focus:border-indigo-500">
                                <option value="1">1 Level (Hanya Bab Utama / H1)</option>
                                <option value="2">2 Level (Bab & Subbab / H1-H2)</option>
                                <option value="3" selected>3 Level (Bab, Subbab, & Anak Subbab / H1-H3 - Standar Word)</option>
                                <option value="4">4 Level (H1-H4)</option>
                                <option value="5">5 Level (H1-H5)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right: Live Print & Web Preview -->
                    <div class="flex flex-col">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-bold text-gray-700 dark:text-gray-300">Pratinjau Hasil Cetak:</span>
                            <span id="toc-preview-badge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">Level 1 - 3</span>
                        </div>
                        <div id="toc-preview-container" class="flex-1 bg-white dark:bg-gray-800/80 p-4 rounded-lg border border-gray-200 dark:border-gray-700 text-xs overflow-y-auto max-h-72 shadow-inner">
                            <!-- Live interactive preview loaded by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3.5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <span class="text-[11px] text-gray-500 dark:text-gray-400">Daftar Isi akan otomatis menghubungkan tautan anchor ke setiap bab.</span>
                <div class="flex items-center gap-2">
                    <button type="button" data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition">Batal</button>
                    <button type="button" id="btn-confirm-insert-toc" class="px-4 py-2 text-xs font-bold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <span>📑 Sisipkan Daftar Isi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 15: Perbarui Daftar Isi (Update Table of Contents Dialog - Microsoft Word Standard) -->
    <div id="modal-update-toc" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-emerald-50/70 to-teal-50/70 dark:from-gray-900 dark:to-gray-800">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-emerald-600 text-white text-xs">🔄</span>
                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">Perbarui Daftar Isi (Update Table of Contents)</h3>
                </div>
                <button data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">✕</button>
            </div>
            <div class="p-5 space-y-3.5 text-xs">
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                    Word sedang memperbarui daftar isi dokumen. Pilih salah satu tindakan yang ingin Anda lakukan:
                </p>

                <div class="space-y-2.5 pt-1">
                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 cursor-pointer transition">
                        <input type="radio" name="toc-update-scope" value="page_numbers" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        <div>
                            <span class="font-bold text-gray-900 dark:text-gray-100 block">Hanya perbarui nomor halaman</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Gunakan opsi ini jika Anda hanya menambah atau mengurangi teks yang menggeser nomor lembar halaman tanpa mengubah teks judul bab.
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 cursor-pointer transition">
                        <input type="radio" name="toc-update-scope" value="entire" class="mt-0.5 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                        <div>
                            <span class="font-bold text-gray-900 dark:text-gray-100 block">Perbarui seluruh tabel</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Memindai ulang seluruh naskah untuk menambahkan judul bab baru, menghapus bab yang telah dihapus, dan menyelaraskan penomoran.
                            </span>
                        </div>
                    </label>
                </div>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                <button type="button" data-close-modal class="px-3.5 py-2 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition">Batal</button>
                <button type="button" id="btn-confirm-update-toc" class="px-4 py-2 text-xs font-bold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 16: PENYETELAN HALAMAN (MICROSOFT WORD PAGE SETUP SUITE)            -->
    <!-- ========================================================================= -->
    <div id="modal-page-setup" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white text-xs">📐</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Penyetelan Halaman (Page Setup)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur margin, ukuran kertas, orientasi, dan tata letak standar Microsoft Word</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 px-4 bg-gray-50 dark:bg-gray-800/60 shrink-0 text-xs font-semibold">
                <button type="button" data-page-setup-tab="margins" class="page-setup-tab-btn px-4 py-2.5 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold transition">
                    Margin
                </button>
                <button type="button" data-page-setup-tab="paper" class="page-setup-tab-btn px-4 py-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                    Kertas (Paper)
                </button>
                <button type="button" data-page-setup-tab="layout" class="page-setup-tab-btn px-4 py-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                    Tata Letak (Layout)
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                <!-- TAB 1: MARGIN -->
                <div id="tab-ps-margins" class="page-setup-tab-pane space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Left: Margins and Gutter Form -->
                        <div class="space-y-3.5">
                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Margin Dokumen</legend>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Atas (Top):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-margin-top" value="4.0" step="0.1" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Bawah (Bottom):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-margin-bottom" value="3.0" step="0.1" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label id="lbl-ps-margin-left" class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Kiri (Left):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-margin-left" value="4.0" step="0.1" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label id="lbl-ps-margin-right" class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Kanan (Right):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-margin-right" value="3.0" step="0.1" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Jilid (Gutter)</legend>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Lebar Jilid:</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-gutter" value="0" step="0.1" min="0" max="10" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Posisi Jilid:</label>
                                        <select id="ps-gutter-pos" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                            <option value="left" selected>Kiri (Left)</option>
                                            <option value="top">Atas (Top)</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Orientation Visual Radio Cards -->
                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Orientasi Halaman</legend>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <label class="ps-orient-card flex flex-col items-center p-2.5 rounded-lg border-2 border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 cursor-pointer transition text-center" data-orient="portrait">
                                        <input type="radio" name="ps-orient-radio" value="portrait" checked class="hidden" />
                                        <div class="w-7 h-10 border-2 border-indigo-600 rounded bg-white dark:bg-gray-800 mb-1.5 flex flex-col justify-between p-1 shadow-xs">
                                            <div class="w-full h-0.5 bg-indigo-300 rounded"></div>
                                            <div class="w-2/3 h-0.5 bg-indigo-200 rounded"></div>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">Potret</span>
                                        <span class="text-[10px] text-gray-500">Tegak (Portrait)</span>
                                    </label>
                                    <label class="ps-orient-card flex flex-col items-center p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 hover:border-gray-300 cursor-pointer transition text-center" data-orient="landscape">
                                        <input type="radio" name="ps-orient-radio" value="landscape" class="hidden" />
                                        <div class="w-10 h-7 border-2 border-gray-400 rounded bg-white dark:bg-gray-800 mb-1.5 flex flex-col justify-between p-1 shadow-xs">
                                            <div class="w-full h-0.5 bg-gray-300 rounded"></div>
                                            <div class="w-2/3 h-0.5 bg-gray-200 rounded"></div>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">Lanskap</span>
                                        <span class="text-[10px] text-gray-500">Mendatar (Landscape)</span>
                                    </label>
                                </div>
                            </fieldset>

                            <!-- Multiple Pages Mode -->
                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Beberapa Halaman (Multiple Pages):</label>
                                <select id="ps-multiple-pages" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                    <option value="normal" selected>Normal</option>
                                    <option value="mirror">Margin Cermin (Mirror Margins)</option>
                                    <option value="2pages">2 Halaman per Lembar (2 Pages per Sheet)</option>
                                    <option value="bookfold">Lipatan Buku (Book Fold)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Presets & Live Visual Sheet Preview -->
                        <div class="space-y-3 flex flex-col">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-700 dark:text-gray-300">Preset Margin Microsoft Word:</span>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5">
                                <button type="button" data-ps-preset="skripsi" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-indigo-300 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-left font-semibold hover:bg-indigo-100 transition">
                                    🎓 Skripsi (4-4-3-3 cm)
                                </button>
                                <button type="button" data-ps-preset="normal" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    📄 Normal (2.54 cm)
                                </button>
                                <button type="button" data-ps-preset="narrow" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    📐 Sempit (1.27 cm)
                                </button>
                                <button type="button" data-ps-preset="moderate" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    📏 Sedang (2.54 x 1.91)
                                </button>
                                <button type="button" data-ps-preset="wide" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    ↔️ Lebar (2.54 x 5.08)
                                </button>
                                <button type="button" data-ps-preset="mirrored" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    📖 Cermin (3.18 / 2.54)
                                </button>
                                <button type="button" data-ps-preset="office2003" class="btn-ps-quick-preset px-2.5 py-1.5 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-left transition">
                                    🏛️ Office 2003 (2.54 x 3.18)
                                </button>
                            </div>

                            <!-- Interactive Miniature Preview -->
                            <div class="flex-1 bg-gray-100 dark:bg-gray-800/80 rounded-lg p-3 border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center min-h-[170px] relative overflow-hidden">
                                <span class="text-[10px] font-bold text-gray-400 absolute top-2 left-2 uppercase">Pratinjau Lembar</span>
                                <div id="ps-preview-canvas" class="bg-white dark:bg-gray-900 border border-gray-400 shadow-md relative transition-all duration-200" style="width: 105px; height: 148px;">
                                    <!-- Margin overlay guides -->
                                    <div id="ps-preview-content-box" class="absolute border border-dashed border-indigo-400/80 bg-indigo-50/20 dark:bg-indigo-950/20 flex flex-col gap-1 p-1" style="top: 20px; left: 20px; right: 15px; bottom: 15px;">
                                        <div class="w-full h-1 bg-gray-300 dark:bg-gray-700 rounded"></div>
                                        <div class="w-5/6 h-1 bg-gray-200 dark:bg-gray-800 rounded"></div>
                                        <div class="w-4/5 h-1 bg-gray-200 dark:bg-gray-800 rounded"></div>
                                        <div class="w-full h-1 bg-gray-200 dark:bg-gray-800 rounded"></div>
                                    </div>
                                </div>
                                <span id="ps-preview-caption" class="text-[10px] text-gray-500 dark:text-gray-400 mt-2">A4 Potret (210 × 297 mm)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: KERTAS (PAPER) -->
                <div id="tab-ps-paper" class="page-setup-tab-pane hidden space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3.5">
                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Ukuran Kertas Standar (Paper Size):</label>
                                <select id="ps-paper-size-select" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none font-semibold">
                                    <option value="a4" selected>A4 (210 × 297 mm / 21 × 29.7 cm)</option>
                                    <option value="letter">Letter (8.5 × 11 in / 21.59 × 27.94 cm)</option>
                                    <option value="legal">Legal (8.5 × 14 in / 21.59 × 35.56 cm)</option>
                                    <option value="folio">Folio / F4 (21.5 × 33 cm / 8.5 × 13 in)</option>
                                    <option value="executive">Executive (18.41 × 26.67 cm / 7.25 × 10.5 in)</option>
                                    <option value="a3">A3 (297 × 420 mm / 29.7 × 42 cm)</option>
                                    <option value="a5">A5 (148 × 210 mm / 14.8 × 21 cm)</option>
                                    <option value="b5">B5 JIS (18.2 × 25.7 cm)</option>
                                    <option value="statement">Statement / Half Letter (13.97 × 21.59 cm)</option>
                                    <option value="tabloid">Tabloid / Ledger (11 × 17 in / 27.94 × 43.18 cm)</option>
                                    <option value="custom">Ukuran Kustom (Custom Dimensions)...</option>
                                </select>
                            </div>

                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Dimensi Kertas Kustom</legend>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Lebar Kertas (Width):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-custom-width" value="21.0" step="0.1" min="5" max="100" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Tinggi Kertas (Height):</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-custom-height" value="29.7" step="0.1" min="5" max="100" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Sumber Kertas (Paper Source):</label>
                                <select id="ps-paper-source" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                    <option value="auto">Baki Otomatis (Default Tray)</option>
                                    <option value="manual">Baki Manual (Manual Feed)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Paper Info Cards -->
                        <div class="bg-indigo-50/40 dark:bg-indigo-950/20 p-4 rounded-lg border border-indigo-100 dark:border-indigo-900/40 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                <span>📄 Informasi Kertas</span>
                            </h4>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400 leading-relaxed">
                                Standar naskah akademik, proposal penelitian, tesis, dan skripsi di universitas Indonesia umumnya menggunakan kertas <strong>A4 80 gram</strong> atau <strong>Folio / F4</strong>.
                            </p>
                            <div class="space-y-1.5 pt-1 text-[11px]">
                                <div class="flex justify-between py-1 border-b border-gray-200/60 dark:border-gray-800">
                                    <span class="text-gray-500">Rasio Lembar:</span>
                                    <span id="ps-paper-ratio-info" class="font-mono font-semibold">1 : 1.414 (ISO 216)</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-gray-200/60 dark:border-gray-800">
                                    <span class="text-gray-500">Luas Area Cetak:</span>
                                    <span id="ps-printable-area-info" class="font-mono font-semibold">140 × 227 mm</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: TATA LETAK (LAYOUT) -->
                <div id="tab-ps-layout" class="page-setup-tab-pane hidden space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3.5">
                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Bagian (Section)</legend>
                                <div class="mt-1">
                                    <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Awal Bagian (Section start):</label>
                                    <select id="ps-section-start" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                        <option value="newpage" selected>Halaman Baru (New page)</option>
                                        <option value="continuous">Berkelanjutan (Continuous)</option>
                                        <option value="evens">Halaman Genap</option>
                                        <option value="odds">Halaman Ganjil</option>
                                    </select>
                                </div>
                            </fieldset>

                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-2">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Header dan Footer</legend>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="ps-diff-odd-even" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span>Beda halaman ganjil dan genap (Different odd and even)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="ps-diff-first-page" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span>Beda halaman pertama (Different first page)</span>
                                </label>
                            </fieldset>

                            <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Jarak dari Tepi (From edge)</legend>
                                <div class="grid grid-cols-2 gap-3 mt-1">
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Header:</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-edge-header" value="1.5" step="0.1" min="0" max="10" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Footer:</label>
                                        <div class="flex items-center">
                                            <input type="number" id="ps-edge-footer" value="1.5" step="0.1" min="0" max="10" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Perataan Vertikal Halaman:</label>
                                <select id="ps-vert-align" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                    <option value="top" selected>Atas (Top - Standar)</option>
                                    <option value="center">Tengah (Center)</option>
                                    <option value="justify">Rata (Justified)</option>
                                    <option value="bottom">Bawah (Bottom)</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                                <button type="button" data-action="open-line-numbers-modal" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    Nomor Baris (Line Numbers)...
                                </button>
                                <button type="button" data-action="open-page-borders-modal" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    Batas Halaman (Borders)...
                                </button>
                            </div>
                        </div>

                        <!-- Layout Info -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-2.5 text-[11px] text-gray-600 dark:text-gray-400">
                            <span class="font-bold text-gray-900 dark:text-white block text-xs">Tata Letak Standar Word</span>
                            <p>Konfigurasi header dan footer terintegrasi dengan running header dan nomor halaman pada dokumen Wordcraft Anda.</p>
                            <p>Menandai <em>Different first page</em> akan menyembunyikan running header dan nomor halaman pada lembar sampul pertama.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3.5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-[11px]">Terapkan ke:</span>
                    <select id="ps-apply-scope" class="px-2 py-1 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 text-xs outline-none">
                        <option value="document" selected>Seluruh dokumen</option>
                        <option value="current">Halaman aktif saat ini</option>
                        <option value="forward">Mulai titik ini ke depan</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-ps-set-default" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 transition">
                        Jadikan Default
                    </button>
                    <button type="button" data-close-modal class="px-3.5 py-1.5 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="button" id="btn-confirm-page-setup" class="px-4 py-1.5 text-xs font-bold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer">
                        OK / Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 17: FORMAT PARAGRAF (MICROSOFT WORD PARAGRAPH DIALOG SUITE)         -->
    <!-- ========================================================================= -->
    <div id="modal-paragraph-format" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-blue-600 text-white text-xs">📝</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Format Paragraf (Paragraph Options)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur perataan, indentasi baris pertama / gantung, jarak baris, dan spasi sebelum / sesudah</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-800 px-4 bg-gray-50 dark:bg-gray-800/60 shrink-0 text-xs font-semibold">
                <button type="button" data-para-tab="indents-spacing" class="para-tab-btn px-4 py-2.5 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold transition">
                    Indentasi dan Spasi (Indents and Spacing)
                </button>
                <button type="button" data-para-tab="line-breaks" class="para-tab-btn px-4 py-2.5 border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                    Pemindahan Baris dan Halaman (Line and Page Breaks)
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                <!-- TAB 1: INDENTASI & SPASI -->
                <div id="tab-para-indents-spacing" class="para-tab-pane space-y-4">
                    <!-- Bagian Umum (General) -->
                    <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Umum (General)</legend>
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Perataan (Alignment):</label>
                                <select id="para-align-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                    <option value="justify" selected>Rata Kiri-Kanan (Justified / Standar)</option>
                                    <option value="left">Rata Kiri (Left)</option>
                                    <option value="center">Rata Tengah (Centered)</option>
                                    <option value="right">Rata Kanan (Right)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Tingkat Kerangka (Outline Level):</label>
                                <select id="para-outline-level" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none">
                                    <option value="body" selected>Teks Isi (Body text)</option>
                                    <option value="level1">Tingkat 1 (Level 1)</option>
                                    <option value="level2">Tingkat 2 (Level 2)</option>
                                    <option value="level3">Tingkat 3 (Level 3)</option>
                                    <option value="level4">Tingkat 4 (Level 4)</option>
                                    <option value="level5">Tingkat 5 (Level 5)</option>
                                    <option value="level6">Tingkat 6 (Level 6)</option>
                                    <option value="level7">Tingkat 7 (Level 7)</option>
                                    <option value="level8">Tingkat 8 (Level 8)</option>
                                    <option value="level9">Tingkat 9 (Level 9)</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Bagian Indentasi (Indentation) -->
                    <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Indentasi (Indentation)</legend>
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Kiri (Left):</label>
                                <div class="flex items-center">
                                    <input type="number" id="para-indent-left" value="0" step="0.25" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Kanan (Right):</label>
                                <div class="flex items-center">
                                    <input type="number" id="para-indent-right" value="0" step="0.25" min="0" max="15" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Khusus (Special Indent):</label>
                                <select id="para-special-indent" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none font-medium">
                                    <option value="none">(Tidak Ada)</option>
                                    <option value="first-line" selected>Baris Pertama (First line)</option>
                                    <option value="hanging">Gantung (Hanging - Daftar Pustaka)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Sebesar (By):</label>
                                <div class="flex items-center">
                                    <input type="number" id="para-special-val" value="1.25" step="0.05" min="0" max="10" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="para-mirror-indents" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">Indentasi cermin (Mirror indents)</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Bagian Spasi (Spacing) -->
                    <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Spasi (Spacing)</legend>
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Sebelum (Before):</label>
                                <div class="flex items-center">
                                    <input type="number" id="para-spacing-before" value="0" step="1" min="0" max="100" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">pt</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Sesudah (After):</label>
                                <div class="flex items-center">
                                    <input type="number" id="para-spacing-after" value="6" step="1" min="0" max="100" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                                    <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">pt</span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Jarak Baris (Line Spacing):</label>
                                <select id="para-line-spacing-type" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none font-medium">
                                    <option value="1.0">Tunggal (Single)</option>
                                    <option value="1.15">1,15 baris</option>
                                    <option value="1.5" selected>1,5 baris (Standar Skripsi)</option>
                                    <option value="2.0">Ganda (Double)</option>
                                    <option value="atleast">Minimal (At least)</option>
                                    <option value="exactly">Tepat (Exactly)</option>
                                    <option value="multiple">Jamak (Multiple)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-600 dark:text-gray-400 mb-1 font-medium">Sebesar (At):</label>
                                <input type="text" id="para-line-spacing-at" value="1.5" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 outline-none text-right font-mono" />
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="para-dont-add-space-same" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-600 dark:text-gray-400">Jangan tambahkan spasi antar-paragraf dengan gaya yang sama</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Interactive Live Preview Box -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-800/40 space-y-1.5">
                        <span class="font-bold text-gray-700 dark:text-gray-300 block text-[11px] uppercase">Pratinjau Paragraf (Live Preview):</span>
                        <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded p-3 text-gray-900 dark:text-gray-100 max-h-36 overflow-hidden shadow-inner font-serif text-[11px]">
                            <p class="text-gray-400 dark:text-gray-500 text-[10px] mb-1 italic">Paragraf Sebelumnya...</p>
                            <div id="para-preview-sample" style="text-align: justify; text-indent: 1.25cm; line-height: 1.5; margin-top: 0pt; margin-bottom: 6pt;">
                                Teks sampel ini menampilkan secara langsung penerapan perataan teks, indentasi baris pertama atau gantung, jarak baris (line spacing), dan spasi sebelum serta sesudah paragraf sesuai pengaturan Microsoft Word yang Anda pilih di atas.
                            </div>
                            <p class="text-gray-400 dark:text-gray-500 text-[10px] mt-1 italic">Paragraf Sesudahnya...</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PEMINDAHAN BARIS & HALAMAN -->
                <div id="tab-para-line-breaks" class="para-tab-pane hidden space-y-4">
                    <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                        <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Paginasi (Pagination)</legend>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" id="para-widow-orphan" checked class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Kontrol janda / yatim baris (Widow/Orphan control)</span>
                                <p class="text-[11px] text-gray-400">Mencegah satu baris terpisah sendiri di bagian bawah atau atas halaman.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" id="para-keep-with-next" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Pertahankan bersama baris berikutnya (Keep with next)</span>
                                <p class="text-[11px] text-gray-400">Mencegah pemisahan bab atau judul dari paragraf pertamanya.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" id="para-keep-lines-together" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Pertahankan baris tetap menyatu (Keep lines together)</span>
                                <p class="text-[11px] text-gray-400">Memastikan seluruh paragraf berada di halaman yang sama.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" id="para-page-break-before" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Halaman baru sebelumnya (Page break before)</span>
                                <p class="text-[11px] text-gray-400">Selalu memulai paragraf atau bab ini pada lembar halaman baru.</p>
                            </div>
                        </label>

                        <div class="border-t border-gray-100 dark:border-gray-800 my-2 pt-2">
                            <span class="font-bold text-gray-500 text-[10px] uppercase block mb-1">Pengecualian Format (Formatting Exceptions)</span>
                            <label class="flex items-center gap-2.5 cursor-pointer mb-2">
                                <input type="checkbox" id="para-suppress-line-numbers" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">Abaikan nomor baris (Suppress line numbers)</span>
                                    <p class="text-[11px] text-gray-400">Tidak menyertakan nomor baris pada paragraf ini saat penomoran baris aktif.</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" id="para-dont-hyphenate" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">Jangan gunakan tanda hubung (Don't hyphenate)</span>
                                    <p class="text-[11px] text-gray-400">Mencegah pemenggalan kata otomatis pada akhir baris untuk paragraf ini.</p>
                                </div>
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3.5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 text-[11px]">Terapkan ke:</span>
                    <select id="para-apply-scope" class="px-2 py-1 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 text-xs outline-none">
                        <option value="selection" selected>Paragraf yang dipilih / aktif</option>
                        <option value="all">Seluruh paragraf dokumen</option>
                    </select>
                    <button type="button" id="btn-open-tabs-modal" data-action="open-tabs-modal" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Buka Kotak Dialog Tabulasi Word">
                        Tabulasi (Tabs)...
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-para-set-default" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 transition">
                        Jadikan Standar
                    </button>
                    <button type="button" data-close-modal class="px-3.5 py-1.5 text-xs font-medium rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="button" id="btn-confirm-paragraph-format" class="px-4 py-1.5 text-xs font-bold rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition cursor-pointer">
                        OK / Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 18: TABULASI (MICROSOFT WORD TABS DIALOG SUITE)                     -->
    <!-- ========================================================================= -->
    <div id="modal-tabs-format" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-blue-600 text-white text-xs">⇥</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Tabulasi (Tabs)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur perhentian tab, perataan, dan karakter pemandu standar Word</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Posisi Perhentian Tab:</label>
                        <div class="flex items-center">
                            <input type="number" id="tab-stop-pos" value="1.25" step="0.25" min="0" max="25" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 text-right font-mono" />
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                        </div>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Perhentian Tab Standar:</label>
                        <div class="flex items-center">
                            <input type="number" id="tab-default-stop" value="1.27" step="0.01" min="0.5" max="5" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 text-right font-mono" />
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                        </div>
                    </div>
                </div>

                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Perataan (Alignment)</legend>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-align-radio" value="left" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Kiri (Left)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-align-radio" value="center" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Tengah (Center)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-align-radio" value="right" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Kanan (Right)</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Karakter Pemandu (Leader)</legend>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-leader-radio" value="none" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span>1 (Tidak ada)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-leader-radio" value="dots" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>2 ....... (Titik-titik)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-leader-radio" value="dashes" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>3 ------- (Putus-putus)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="tab-leader-radio" value="underline" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>4 _______ (Garis bawah)</span>
                        </label>
                    </div>
                </fieldset>

                <div>
                    <span class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Daftar Tab yang Ditetapkan:</span>
                    <select id="tab-stops-list" size="3" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 font-mono text-xs">
                        <option value="1.25">1.25 cm (Kiri)</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-1.5">
                    <button type="button" id="btn-tab-set" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">Tetapkan</button>
                    <button type="button" id="btn-tab-clear" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">Hapus</button>
                    <button type="button" id="btn-tab-clear-all" class="px-2.5 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">Hapus Semua</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                    <button type="button" id="btn-confirm-tabs" class="px-3.5 py-1 text-xs font-bold rounded bg-blue-600 hover:bg-blue-700 text-white shadow-xs">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 19: CAP AIR / WATERMARK (MICROSOFT WORD WATERMARK SUITE)             -->
    <!-- ========================================================================= -->
    <div id="modal-watermark" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-blue-600 text-white text-xs">💧</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Cap Air Kustom (Watermark)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Sisipkan tanda teks transparan di latar belakang naskah</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Teks Cap Air (Text):</label>
                    <input type="text" id="wm-text-input" value="DRAF" placeholder="Misal: DRAF, RAHASIA, JANGAN DISALIN" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 font-semibold uppercase" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Font:</label>
                        <select id="wm-font-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                            <option value="'Times New Roman', serif" selected>Times New Roman</option>
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="Calibri, sans-serif">Calibri</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="monospace">Courier New</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Ukuran (Size):</label>
                        <select id="wm-size-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                            <option value="auto">Otomatis (Auto)</option>
                            <option value="36">36 pt</option>
                            <option value="48">48 pt</option>
                            <option value="54">54 pt</option>
                            <option value="72" selected>72 pt (Standar)</option>
                            <option value="96">96 pt</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 items-center">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Warna (Color):</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="wm-color-input" value="#9ca3af" class="w-8 h-8 p-0 border border-gray-300 dark:border-gray-700 rounded cursor-pointer" />
                            <span class="text-[11px] text-gray-500 font-mono">Abu-abu Lembut</span>
                        </div>
                    </div>
                    <div class="pt-3">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="wm-semitransparent" checked class="rounded text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Semitransparan (Washout)</span>
                        </label>
                    </div>
                </div>

                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Tata Letak (Layout)</legend>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="wm-layout-radio" value="diagonal" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Miring (Diagonal - 45°)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="wm-layout-radio" value="horizontal" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Mendatar (Horizontal)</span>
                        </label>
                    </div>
                </fieldset>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <button type="button" id="btn-remove-watermark-dialog" class="px-2.5 py-1 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition">
                    Hapus Cap Air
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                    <button type="button" id="btn-confirm-watermark" class="px-3.5 py-1 text-xs font-bold rounded bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 20: BATAS HALAMAN / PAGE BORDERS (MICROSOFT WORD SUITE)             -->
    <!-- ========================================================================= -->
    <div id="modal-page-borders" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-blue-600 text-white text-xs">🖼️</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Batas Halaman (Page Borders)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur garis bingkai sekeliling lembar halaman dokumen Word</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Pengaturan (Setting)</legend>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="pb-setting-radio" value="none" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Tidak Ada</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="pb-setting-radio" value="box" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Kotak (Box)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="pb-setting-radio" value="shadow" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Bayangan</span>
                        </label>
                    </div>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Gaya Garis (Style):</label>
                        <select id="pb-style-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                            <option value="solid" selected>Solid (Garis Tunggal)</option>
                            <option value="dashed">Dashed (Garis Putus)</option>
                            <option value="dotted">Dotted (Titik-titik)</option>
                            <option value="double">Double (Garis Ganda)</option>
                            <option value="groove">Groove (Ukiran)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Ketebalan (Width):</label>
                        <select id="pb-width-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                            <option value="0.5pt">0.5 pt</option>
                            <option value="1pt">1.0 pt</option>
                            <option value="1.5pt" selected>1.5 pt (Standar)</option>
                            <option value="2.25pt">2.25 pt</option>
                            <option value="3pt">3.0 pt</option>
                            <option value="4.5pt">4.5 pt</option>
                            <option value="6pt">6.0 pt</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 items-center">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Warna Batas (Color):</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="pb-color-input" value="#000000" class="w-8 h-8 p-0 border border-gray-300 dark:border-gray-700 rounded cursor-pointer" />
                            <span class="text-[11px] text-gray-500">Warna Garis</span>
                        </div>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Jarak dari Tepi (Margin):</label>
                        <div class="flex items-center">
                            <input type="number" id="pb-margin-inset" value="15" min="5" max="35" step="1" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 text-right font-mono" />
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">mm</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Terapkan ke (Apply to):</label>
                    <select id="pb-apply-to" class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                        <option value="document" selected>Seluruh Dokumen (Whole document)</option>
                        <option value="first-page">Halaman Pertama Saja (First page only)</option>
                        <option value="not-first-page">Semua Kecuali Halaman Pertama</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <button type="button" id="btn-remove-border-dialog" class="px-2.5 py-1 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition">
                    Hapus Batas
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                    <button type="button" id="btn-confirm-page-border" class="px-3.5 py-1 text-xs font-bold rounded bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 21: NOMOR BARIS / LINE NUMBERS (MICROSOFT WORD SUITE)               -->
    <!-- ========================================================================= -->
    <div id="modal-line-numbers" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-blue-600 text-white text-xs">🔢</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Penomoran Baris (Line Numbers)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Atur penomoran baris naskah akademik & jurnal</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-2">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Mode Penomoran</legend>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ln-mode-radio" value="none" checked class="text-indigo-600 focus:ring-indigo-500" />
                        <span>Tidak Ada (None)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ln-mode-radio" value="continuous" class="text-indigo-600 focus:ring-indigo-500" />
                        <span>Berkelanjutan di Seluruh Dokumen</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ln-mode-radio" value="page" class="text-indigo-600 focus:ring-indigo-500" />
                        <span>Mulai Ulang di Tiap Halaman Baru</span>
                    </label>
                </fieldset>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Mulai Dari (Start at):</label>
                        <input type="number" id="ln-start-at" value="1" min="1" max="1000" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 text-right font-mono" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Kenaikan (Count by):</label>
                        <input type="number" id="ln-count-by" value="1" min="1" max="50" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 text-right font-mono" />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 shrink-0">
                <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                <button type="button" id="btn-confirm-line-numbers" class="px-3.5 py-1 text-xs font-bold rounded bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">OK</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 22: DROP CAP (MICROSOFT WORD HURUF AWAL KAPITAL BESAR)               -->
    <!-- ========================================================================= -->
    <div id="modal-drop-cap" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white text-xs font-serif font-black">A</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Huruf Awal Kapital (Drop Cap)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Buat huruf kapital besar di awal paragraf naskah</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Posisi (Position)</legend>
                    <div class="grid grid-cols-3 gap-2 mt-1">
                        <label class="flex flex-col items-center gap-1 p-2 border border-gray-200 dark:border-gray-700 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                            <input type="radio" name="dc-position-radio" value="none" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-[11px] font-medium">Tidak Ada</span>
                        </label>
                        <label class="flex flex-col items-center gap-1 p-2 border border-gray-200 dark:border-gray-700 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                            <input type="radio" name="dc-position-radio" value="dropped" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-[11px] font-medium">Turun</span>
                        </label>
                        <label class="flex flex-col items-center gap-1 p-2 border border-gray-200 dark:border-gray-700 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                            <input type="radio" name="dc-position-radio" value="margin" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-[11px] font-medium">Di Margin</span>
                        </label>
                    </div>
                </fieldset>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Font Drop Cap:</label>
                    <select id="dc-font-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                        <option value="inherit" selected>(Sama dengan teks naskah)</option>
                        <option value="'Times New Roman', serif">Times New Roman</option>
                        <option value="Georgia, serif">Georgia</option>
                        <option value="Garamond, serif">Garamond</option>
                        <option value="Arial, sans-serif">Arial</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Jumlah Baris Turun:</label>
                        <input type="number" id="dc-lines-to-drop" value="3" min="2" max="10" step="1" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800 text-right font-mono" />
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Jarak dari Teks:</label>
                        <div class="flex items-center">
                            <input type="number" id="dc-distance-text" value="0.2" min="0" max="2" step="0.1" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded-l bg-white dark:bg-gray-800 text-right font-mono" />
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1.5 border-y border-r border-gray-300 dark:border-gray-700 rounded-r text-gray-500">cm</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                <button type="button" id="btn-remove-drop-cap" class="px-2.5 py-1 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition">
                    Hapus
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                    <button type="button" id="btn-confirm-drop-cap" class="px-3.5 py-1 text-xs font-bold rounded bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 23: URUTKAN TEKS / PARAGRAF (MICROSOFT WORD SORT A-Z SUITE)          -->
    <!-- ========================================================================= -->
    <div id="modal-sort-paragraphs" class="editor-modal hidden fixed inset-0 bg-black/60 backdrop-blur-xs z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl max-w-sm w-full border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-indigo-600 text-white text-xs font-bold">A↓Z</span>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Urutkan Teks (Sort Text)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Urutkan paragraf atau daftar secara alfabetis</p>
                    </div>
                </div>
                <button type="button" data-close-modal class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-md text-base leading-none">✕</button>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3.5 text-xs">
                <fieldset class="border border-gray-200 dark:border-gray-700 rounded-lg p-2.5">
                    <legend class="font-bold text-gray-700 dark:text-gray-300 px-1 text-[11px] uppercase">Urutan (Order)</legend>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="sort-order-radio" value="asc" checked class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Menaik (Ascending A - Z)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="sort-order-radio" value="desc" class="text-indigo-600 focus:ring-indigo-500" />
                            <span>Menurun (Descending Z - A)</span>
                        </label>
                    </div>
                </fieldset>

                <div>
                    <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Cakupan (Scope):</label>
                    <select id="sort-scope-select" class="w-full px-2 py-1.5 border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-800">
                        <option value="selection" selected>Paragraf yang dipilih / disorot</option>
                        <option value="all">Seluruh lembar aktif</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 shrink-0">
                <button type="button" data-close-modal class="px-3 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300">Batal</button>
                <button type="button" id="btn-confirm-sort-paragraphs" class="px-3.5 py-1 text-xs font-bold rounded bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">Urutkan</button>
            </div>
        </div>
    </div>

    <!-- Floating Theme Switcher Widget -->
    <div id="floating-theme-widget" class="fixed bottom-12 right-6 z-40 no-print flex items-center gap-1 p-1 rounded-full bg-white/95 dark:bg-gray-900/95 border border-gray-300 dark:border-gray-700 shadow-xl backdrop-blur-md transition-all">
        <button id="floating-theme-toggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-amber-500 dark:text-indigo-400 transition cursor-pointer" title="Beralih Cepat Tema Terang / Gelap">
            <span id="floating-theme-icon" class="text-base leading-none">☀️</span>
        </button>
        <button data-action="open-theme-modal" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900 transition flex items-center gap-1 cursor-pointer" title="Pengaturan Tema & Tampilan Visual">
            <span>🎨 Tema</span>
        </button>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-24 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

</body>
</html>
