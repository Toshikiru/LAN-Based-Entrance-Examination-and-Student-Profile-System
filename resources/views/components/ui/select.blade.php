@props([
    'label' => null,
    'name' => null,
    'options' => [], // ['value' => 'label'] or list of ['value' => .., 'label' => ..]
    'placeholder' => null,
    'autocomplete' => 'off',
])

@php
    $id = $attributes->get('id') ?? $name;
    $hasError = $name && $errors->has($name);

    $normalized = collect($options)->map(function ($option, $key) {
        if (is_array($option)) {
            return ['value' => $option['value'] ?? $key, 'label' => $option['label'] ?? $option['value'] ?? $key];
        }
        return ['value' => $key, 'label' => $option];
    })->values();
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $id }}" class="block font-label-md text-label-md text-on-surface-variant mb-xs">{{ $label }}</label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            autocomplete="{{ $autocomplete }}"
            {{ $attributes->except(['class', 'id'])->class([
                'w-full appearance-none bg-surface-container border rounded-lg pl-md pr-10 py-2.5 text-body-md font-medium cursor-pointer transition-all outline-none',
                'border-error focus:border-error focus:ring-2 focus:ring-error/20' => $hasError,
                'border-outline-variant focus:border-primary focus:ring-2 focus:ring-primary/20' => ! $hasError,
            ]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach ($normalized as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach

            {{ $slot }}
        </select>

        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-lg">expand_more</span>
    </div>

    @if ($hasError)
        <p class="text-label-sm text-error mt-xs">{{ $errors->first($name) }}</p>
    @endif
</div>
