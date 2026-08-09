@props([
    'variant' => 'primary', // primary | secondary | outline | ghost | danger
    'size' => 'md',         // sm | md | lg
    'href' => null,
    'type' => 'button',
    'icon' => null,          // material symbol name
    'iconPosition' => 'left', // left | right — where $icon renders relative to the label
    'loading' => false,
    'disabled' => false,
])

@php
    $sizes = [
        'sm' => 'h-9 px-md text-label-sm gap-xs',
        'md' => 'h-11 px-lg text-label-md gap-sm',
        'lg' => 'h-14 px-xl text-[18px] font-headline-md gap-sm',
    ];

    $variants = [
        'primary' => 'bg-primary text-on-primary hover:brightness-110 shadow-sm shadow-primary/20',
        'secondary' => 'bg-surface-container-high text-on-surface hover:bg-surface-variant',
        'outline' => 'bg-transparent border border-outline-variant text-on-surface hover:bg-surface-container-low',
        'ghost' => 'bg-transparent text-primary hover:bg-primary-fixed/40',
        'danger' => 'bg-transparent border border-error text-error hover:bg-error-container',
        'danger-solid' => 'bg-error text-on-error hover:brightness-90 shadow-sm',
    ];

    $classes = collect([
        'inline-flex items-center justify-center rounded-lg font-bold transition-all active:scale-[0.98]',
        'disabled:opacity-50 disabled:pointer-events-none',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ])->implode(' ');
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled || $loading)
        {{ $attributes->class([$classes]) }}
    >
        @if ($loading)
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        @else
            @if ($icon && $iconPosition === 'left')
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            @endif
            {{ $slot }}
            @if ($icon && $iconPosition === 'right')
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            @endif
        @endif
    </button>
@endif
