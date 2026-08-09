@props([
    'icon' => 'inbox',
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center text-center py-xl px-lg']) }}>
    <div class="w-16 h-16 rounded-full bg-surface-container-high flex items-center justify-center text-outline mb-md">
        <span class="material-symbols-outlined text-[32px]">{{ $icon }}</span>
    </div>

    <h3 class="font-headline-md text-headline-md text-on-surface mb-xs">{{ $title }}</h3>

    @if ($description)
        <p class="text-body-md text-on-surface-variant max-w-sm mb-lg">{{ $description }}</p>
    @endif

    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
