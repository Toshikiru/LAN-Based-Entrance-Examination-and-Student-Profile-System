@props([
    'name',
    'title' => null,
    'maxWidth' => 'md', // sm | md | lg | xl
])

@php
    $maxWidths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
    ];
@endphp

{{--
    Trigger from anywhere on the page with:
    <button type="button" @click="$dispatch('open-modal-{{ $name }}')">Open</button>
--}}
<div
    x-data="{ open: false }"
    x-on:open-modal-{{ $name }}.window="open = true"
    x-on:close-modal-{{ $name }}.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-md"
>
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="absolute inset-0 bg-inverse-surface/60 backdrop-blur-sm"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
        class="relative w-full {{ $maxWidths[$maxWidth] ?? $maxWidths['md'] }} bg-surface-container-lowest rounded-xl shadow-2xl border border-outline-variant max-h-[90vh] flex flex-col"
    >
        @if ($title)
            <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant shrink-0">
                <h3 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h3>
                <button type="button" @click="open = false" class="text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        @endif

        <div class="p-lg overflow-y-auto custom-scrollbar">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="px-lg py-md border-t border-outline-variant flex items-center justify-end gap-sm shrink-0">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
