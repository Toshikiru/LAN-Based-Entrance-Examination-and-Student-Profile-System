@props([
    'items' => [], // [ ['label' => .., 'href' => ?string], ... ] — last item (no href) renders as current page
])

<nav {{ $attributes->class(['flex items-center flex-wrap gap-xs']) }} aria-label="Breadcrumb">
    @foreach ($items as $index => $item)
        @if ($index > 0)
            <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
        @endif

        @if (($item['href'] ?? null) && $index < count($items) - 1)
            <a href="{{ $item['href'] }}" class="text-label-sm text-on-surface-variant hover:text-primary transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="text-label-sm text-on-surface font-semibold">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
