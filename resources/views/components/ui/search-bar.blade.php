@props([
    'name' => 'search',
    'placeholder' => 'Search...',
    'rounded' => 'full', // 'full' | 'lg'
    'value' => null,
])

<div {{ $attributes->class(['relative flex items-center']) }}>
    <span class="material-symbols-outlined absolute left-3 text-on-surface-variant pointer-events-none">search</span>
    <input
        type="text"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        value="{{ $value }}"
        autocomplete="off"
        class="w-full bg-surface-container border-none {{ $rounded === 'full' ? 'rounded-full' : 'rounded-lg' }} pl-10 pr-md py-2 text-body-md focus:ring-2 focus:ring-primary/20 transition-shadow outline-none"
    />
</div>
