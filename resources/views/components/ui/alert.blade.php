@props([
    'variant' => 'info', // success | error | warning | info
    'dismissible' => false,
    'icon' => null,
])

@php
    $variants = [
        'success' => ['classes' => 'bg-secondary/10 text-on-secondary-container border-secondary/20', 'icon' => 'check_circle'],
        'error' => ['classes' => 'bg-error-container text-on-error-container border-error/20', 'icon' => 'error'],
        'warning' => ['classes' => 'bg-tertiary-container/20 text-on-tertiary-fixed-variant border-tertiary/20', 'icon' => 'warning'],
        'info' => ['classes' => 'bg-primary-fixed/40 text-on-primary-fixed-variant border-primary/20', 'icon' => 'info'],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-cloak x-transition @endif
    {{ $attributes->class(['flex items-start gap-md px-md py-sm rounded-lg border', $config['classes']]) }}
    role="alert"
>
    <span class="material-symbols-outlined text-[20px] shrink-0 mt-[1px]">{{ $icon ?? $config['icon'] }}</span>

    <div class="flex-1 text-label-md text-label-md">
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="shrink-0 hover:opacity-70 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    @endif
</div>
