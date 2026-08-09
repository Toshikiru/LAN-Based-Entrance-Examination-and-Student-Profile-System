@props([
    'currentPage' => 1,
    'totalPages' => 1,
    'pageName' => 'page',
    'totalLabel' => null, // e.g. "1-10 of 1,420 questions"
])

@php
    $current = max(1, (int) $currentPage);
    $total = max(1, (int) $totalPages);

    $pageUrl = fn (int $page) => request()->fullUrlWithQuery([$pageName => $page]);

    // Build a windowed page list: first, last, and a small range around current.
    $window = collect(range(max(1, $current - 1), min($total, $current + 1)))
        ->push(1)
        ->push($total)
        ->unique()
        ->sort()
        ->values();
@endphp

@if ($total > 1 || $totalLabel)
    <div {{ $attributes->class(['flex items-center justify-between flex-wrap gap-md']) }}>
        @if ($totalLabel)
            <p class="text-body-md text-on-surface-variant">{{ $totalLabel }}</p>
        @endif

        <div class="flex items-center gap-1 ml-auto">
            <a
                href="{{ $current > 1 ? $pageUrl($current - 1) : '#' }}"
                @class([
                    'size-9 rounded-lg flex items-center justify-center hover:bg-surface-container transition-colors',
                    'opacity-30 pointer-events-none' => $current <= 1,
                ])
            >
                <span class="material-symbols-outlined">chevron_left</span>
            </a>

            @php $previous = null; @endphp
            @foreach ($window as $page)
                @if ($previous !== null && $page - $previous > 1)
                    <span class="px-2 text-on-surface-variant">&hellip;</span>
                @endif

                <a
                    href="{{ $pageUrl($page) }}"
                    @class([
                        'size-9 rounded-lg flex items-center justify-center text-sm font-bold transition-colors',
                        'bg-primary text-on-primary' => $page === $current,
                        'hover:bg-surface-container' => $page !== $current,
                    ])
                >{{ $page }}</a>

                @php $previous = $page; @endphp
            @endforeach

            <a
                href="{{ $current < $total ? $pageUrl($current + 1) : '#' }}"
                @class([
                    'size-9 rounded-lg flex items-center justify-center hover:bg-surface-container transition-colors',
                    'opacity-30 pointer-events-none' => $current >= $total,
                ])
            >
                <span class="material-symbols-outlined">chevron_right</span>
            </a>
        </div>
    </div>
@endif
