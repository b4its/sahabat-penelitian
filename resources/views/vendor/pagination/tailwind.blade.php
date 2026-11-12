@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-between gap-2 w-full max-w-full">
        <!-- Mobile Navigation (< sm / 640px) -->
        <div class="flex items-center justify-between flex-1 sm:hidden gap-2 w-full">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-lg cursor-not-allowed shrink-0">
                    &larr; Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-colors shrink-0">
                    &larr; Sebelumnya
                </a>
            @endif

            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium px-1 text-center truncate">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-colors shrink-0">
                    Berikutnya &rarr;
                </a>
            @else
                <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-lg cursor-not-allowed shrink-0">
                    Berikutnya &rarr;
                </span>
            @endif
        </div>

        <!-- Desktop & Responsive Navigation with Dynamic Adaptive Windowing (>= sm) -->
        <div class="hidden sm:flex sm:items-center sm:gap-1 overflow-x-auto max-w-full py-0.5">
            <!-- Previous Button -->
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="relative inline-flex items-center p-1.5 rounded-lg text-slate-300 dark:text-slate-600 cursor-not-allowed shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya" class="relative inline-flex items-center p-1.5 rounded-lg text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            <!-- Pagination Elements (Page Numbers & Dots with responsive visibility) -->
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="relative inline-flex items-center px-2 py-1 text-xs font-semibold text-slate-400 dark:text-slate-500 shrink-0">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @php
                            $isCurrent = ($page == $paginator->currentPage());
                            $distance = abs($page - $paginator->currentPage());
                            $isEdge = ($page == 1 || $page == $paginator->lastPage());
                        @endphp

                        @if ($isCurrent)
                            <span aria-current="page" class="relative z-10 inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-bold text-white bg-blue-600 rounded-lg shadow-xs shrink-0">
                                {{ $page }}
                            </span>
                        @elseif ($isEdge || $distance <= 1)
                            {{-- Immediate neighbors and edge pages: visible on all sm+ widths --}}
                            <a href="{{ $url }}" class="relative inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700 transition-all shrink-0">
                                {{ $page }}
                            </a>
                        @elseif ($distance <= 2)
                            {{-- Distance 2 pages: visible on lg+ --}}
                            <a href="{{ $url }}" class="hidden lg:inline-flex relative items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700 transition-all shrink-0">
                                {{ $page }}
                            </a>
                        @else
                            {{-- Other slider pages: visible on xl+ --}}
                            <a href="{{ $url }}" class="hidden xl:inline-flex relative items-center justify-center min-w-[2rem] h-8 px-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700 transition-all shrink-0">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <!-- Next Button -->
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya" class="relative inline-flex items-center p-1.5 rounded-lg text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Berikutnya" class="relative inline-flex items-center p-1.5 rounded-lg text-slate-300 dark:text-slate-600 cursor-not-allowed shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
