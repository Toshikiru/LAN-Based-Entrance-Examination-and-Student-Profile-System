@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'icon' => null,
    'placeholder' => ' ',
    'hint' => null,
    'floating' => false,
    'autocomplete' => 'off',
])

@php
    $id = $attributes->get('id') ?? $name;
    $hasError = $name && $errors->has($name);
    $isPassword = $type === 'password';
@endphp

<div class="w-full">
    @if ($label && ! $floating)
        <label for="{{ $id }}" class="block font-label-md text-label-md text-on-surface-variant mb-xs">{{ $label }}</label>
    @endif

    <div class="relative {{ $floating ? 'floating-label-input' : '' }}" @if ($isPassword) x-data="{ showPassword: false }" @endif>
        <input
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($isPassword) :type="showPassword ? 'text' : 'password'" @else type="{{ $type }}" @endif
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $floating ? ' ' : $placeholder }}"
            {{ $attributes->except(['class', 'id'])->class([
                'w-full h-14 bg-surface-container-lowest border rounded-lg outline-none transition-all',
                'px-md' => ! $icon && ! $isPassword,
                'pl-11 pr-md' => $icon && ! $isPassword,
                'pl-md pr-11' => ! $icon && $isPassword,
                'pl-11 pr-11' => $icon && $isPassword,
                'pt-base' => $floating,
                'border-error focus:border-error focus:ring-4 focus:ring-error/10' => $hasError,
                'border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10' => ! $hasError,
            ]) }}
        />

        @if ($icon)
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant">{{ $icon }}</span>
        @endif

        @if ($isPassword)
            <button
                type="button"
                @click="showPassword = !showPassword"
                tabindex="-1"
                class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
            </button>
        @endif

        @if ($label && $floating)
            <label for="{{ $id }}" class="absolute left-md top-4 text-on-surface-variant pointer-events-none transition-all duration-200 {{ $icon ? 'left-11' : '' }}">{{ $label }}</label>
        @endif
    </div>

    @if ($hint && ! $hasError)
        <p class="text-label-sm text-outline mt-xs">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p class="text-label-sm text-error mt-xs">{{ $errors->first($name) }}</p>
    @endif
</div>
