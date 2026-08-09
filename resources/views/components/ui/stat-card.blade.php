@props([
    'icon',
    'label',
    'value',
    'trend' => null,           // e.g. '+12%'
    'trendDirection' => 'up',  // up | down | neutral
    'variant' => 'primary',    // primary | secondary | tertiary | neutral
])

@php
    $iconWraps = [
        'primary' => 'bg-primary-fixed text-primary',
        'secondary' => 'bg-secondary-fixed text-on-secondary-fixed-variant',
        'tertiary' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'neutral' => 'bg-surface-container-highest text-on-surface-variant',
    ];

    $trendColors = [
        'up' => 'text-secondary',
        'down' => 'text-error',
        'neutral' => 'text-outline',
    ];
@endphp

<div {{ $attributes->class(['bg-surface-container-lowest border border-outline-variant p-lg rounded-2xl shadow-sm hover:shadow-md transition-shadow']) }}>
    <div class="flex justify-between items-start mb-md">
        <div class="p-sm rounded-lg {{ $iconWraps[$variant] ?? $iconWraps['primary'] }}">
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </div>

        @if ($trend)
            <span class="font-bold text-label-sm {{ $trendColors[$trendDirection] ?? $trendColors['neutral'] }}">{{ $trend }}</span>
        @endif
    </div>

    <p class="text-outline font-label-md text-label-md">{{ $label }}</p>
    <p class="text-headline-lg font-headline-lg mt-xs text-on-surface">{{ $value }}</p>
</div>
