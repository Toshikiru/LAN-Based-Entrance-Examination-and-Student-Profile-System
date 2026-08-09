@props([
    'title',
    'subtitle' => null,
    'variant' => 'default', // 'default' | 'hero'
])

@if ($variant === 'hero')
    <section class="relative overflow-hidden rounded-3xl bg-primary text-on-primary p-xl shadow-lg mb-xl">
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-secondary-container/20 rounded-full blur-3xl"></div>
        <div class="absolute right-10 bottom-0 w-32 h-32 bg-primary-fixed/10 rounded-full blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-lg">
            <div>
                <h2 class="font-display-lg-mobile text-display-lg-mobile md:font-display-lg md:text-display-lg mb-sm">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-primary-fixed font-body-lg max-w-xl">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex gap-md shrink-0">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </section>
@else
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-lg mb-xl">
        <div>
            @isset($breadcrumb)
                <div class="mb-xs">{{ $breadcrumb }}</div>
            @endisset
            <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-on-surface-variant mt-xs">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-sm shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>
@endif
