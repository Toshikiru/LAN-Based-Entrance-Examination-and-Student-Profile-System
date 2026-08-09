@props([
    'variant' => 'neutral', // primary | secondary | tertiary | error | warning | success | neutral
    'dot' => false,          // show a small status dot instead of soft-fill background
    'pulse' => false,        // animate the dot (e.g. for a live/in-progress status)
])

@php
    $variants = [
        'primary' => 'bg-primary/10 text-primary',
        'secondary' => 'bg-secondary/10 text-secondary',
        'success' => 'bg-secondary/10 text-secondary',
        'tertiary' => 'bg-tertiary/10 text-tertiary',
        'error' => 'bg-error/10 text-error',
        'warning' => 'bg-tertiary-container/20 text-on-tertiary-fixed-variant',
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
    ];

    $dotColors = [
        'primary' => 'bg-primary',
        'secondary' => 'bg-secondary',
        'success' => 'bg-secondary',
        'tertiary' => 'bg-tertiary',
        'error' => 'bg-error',
        'warning' => 'bg-tertiary',
        'neutral' => 'bg-outline',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-bold whitespace-nowrap',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$variant] ?? $dotColors['neutral'] }} {{ $pulse ? 'animate-pulse' : '' }}"></span>
    @endif
    {{ $slot }}
</span>
