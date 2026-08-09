@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
])

<div {{ $attributes->class(['bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant">
            <div>
                @if ($title)
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-label-sm text-outline mt-xs">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-sm">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class([$padded ? 'p-lg' : ''])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-lg py-md border-t border-outline-variant">
            {{ $footer }}
        </div>
    @endisset
</div>
