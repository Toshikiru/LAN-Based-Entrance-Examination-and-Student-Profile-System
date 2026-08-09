@props([
    'headers' => [], // simple list of strings, or ['label' => .., 'align' => 'left|right', 'width' => ..]
    'tbodyId' => null, // optional id on <tbody>, e.g. as a target for AJAX-refreshed rows
])

@php
    $normalized = collect($headers)->map(function ($header) {
        return is_array($header)
            ? ['label' => $header['label'] ?? '', 'align' => $header['align'] ?? 'left', 'width' => $header['width'] ?? null]
            : ['label' => $header, 'align' => 'left', 'width' => null];
    });
@endphp

<div {{ $attributes->class(['bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            @if ($normalized->isNotEmpty())
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        @foreach ($normalized as $header)
                            <th
                                @if ($header['width']) style="width: {{ $header['width'] }}" @endif
                                class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-on-surface-variant {{ $header['align'] === 'right' ? 'text-right' : 'text-left' }}"
                            >
                                {{ $header['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody @if ($tbodyId) id="{{ $tbodyId }}" @endif class="divide-y divide-outline-variant">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="p-4 bg-surface-container-lowest border-t border-outline-variant">
            {{ $footer }}
        </div>
    @endisset
</div>
