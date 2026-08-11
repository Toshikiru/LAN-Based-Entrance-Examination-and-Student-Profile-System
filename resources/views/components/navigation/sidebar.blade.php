@props([
    'groups' => [],
    'active' => null,
    'brand' => \App\Services\SchoolSettingsService::DEFAULT_SYSTEM_NAME,
    'subtitle' => null,
    'logoUrl' => null,
])

{{--
    Usage:
    <x-navigation.sidebar :groups="$navGroups" :active="'dashboard'" brand="TPC EntryPoint" subtitle="...">
        <x-slot:cta> ... optional call-to-action button ... </x-slot:cta>
    </x-navigation.sidebar>

    $groups shape: [ ['label' => ?string, 'items' => [ ['key','label','icon','href'], ... ]], ... ]
    Active state is determined purely by comparing $active to each item's 'key' — no route()
    resolution happens here, so the sidebar never breaks when a destination route doesn't exist yet.

    The <aside> below is marked `data-turbo-permanent`: Turbo Drive carries this exact DOM node
    across every navigation instead of re-rendering it, so it never flashes/reflows between pages.
    Because of that, its "active" highlighting can't rely on the server re-rendering it per page —
    see the `syncActiveNav()` script in partials/theme-head.blade.php, which re-applies the active
    classes on every `turbo:load` based on a <meta name="nav-active"> tag each layout prints.
    Mobile open/close state lives in the global `Alpine.store('sidebar')` for the same reason —
    a permanent element's own `x-data` would still work, but the toggle button that opens it lives
    in topnav.blade.php, a sibling component, so a shared store is simpler than DOM-scoped state.

    Same caveat applies to the logo: because this node is permanent, a freshly-uploaded logo
    won't appear here on a soft Turbo visit — only on a real page load. The Branding settings
    form (admin/settings/index.blade.php) is marked `data-turbo="false"` for exactly this reason,
    so saving a new logo forces a real reload and you see it immediately.
--}}

<!-- Mobile overlay backdrop -->
<div
    x-show="$store.sidebar.open"
    x-cloak
    x-transition.opacity
    @click="$store.sidebar.open = false"
    class="fixed inset-0 bg-inverse-surface/50 z-40 lg:hidden"
></div>

<aside
    id="app-sidebar"
    data-turbo-permanent
    class="fixed left-0 top-0 h-full w-[260px] flex flex-col pt-xl pb-lg bg-surface-container-lowest border-r border-outline-variant z-50 transition-transform duration-200 -translate-x-full lg:translate-x-0"
    :class="{ '-translate-x-full': !$store.sidebar.open, 'translate-x-0': $store.sidebar.open }"
>
    <div class="px-lg mb-xl flex items-center justify-between">
        <div class="flex items-center gap-md min-w-0">
            {{-- No background box around a real logo — it should sit on its own,
                 transparent, at whatever aspect ratio it was uploaded at. The
                 fallback icon still gets a tinted box since it needs contrast
                 against the sidebar background to read as a mark at all. --}}
            @if ($logoUrl)
                <div class="w-10 h-10 shrink-0 flex items-center justify-center">
                    <img src="{{ $logoUrl }}" alt="{{ $brand }}" class="max-w-full max-h-full w-auto h-auto object-contain">
                </div>
            @else
                <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-on-primary shrink-0">
                    <span class="material-symbols-outlined">school</span>
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="font-label-md text-label-md font-bold text-primary leading-snug truncate" title="{{ $brand }}">{{ $brand }}</h1>
                @if ($subtitle)
                    <p class="text-label-sm text-outline leading-snug line-clamp-2" title="{{ $subtitle }}">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <button type="button" class="lg:hidden text-on-surface-variant shrink-0" @click="$store.sidebar.open = false">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <nav class="flex-1 px-sm space-y-xs overflow-y-auto custom-scrollbar">
        @foreach ($groups as $group)
            @if ($group['label'] ?? null)
                <div class="pt-lg pb-sm px-md">
                    <p class="text-[10px] uppercase tracking-widest text-outline font-bold">{{ $group['label'] }}</p>
                </div>
            @endif

            @foreach ($group['items'] as $item)
                @php $isActive = $active !== null && $active === ($item['key'] ?? null); @endphp
                @if ($item['confirm'] ?? null)
                    {{-- Opens a modal (e.g. logout-confirm) instead of submitting directly. --}}
                    <button
                        type="button"
                        data-nav-key="{{ $item['key'] ?? '' }}"
                        @click="$dispatch('open-modal-{{ $item['confirm'] }}')"
                        @class([
                            'w-full flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 text-left',
                            'border-l-4 border-primary bg-primary-fixed text-on-primary-fixed font-bold' => $isActive,
                            'text-on-surface-variant hover:bg-surface-container-high hover:translate-x-1' => ! $isActive,
                        ])
                    >
                        <span @class(['material-symbols-outlined', 'fill-icon' => $isActive])>{{ $item['icon'] ?? 'circle' }}</span>
                        <span class="font-label-md text-label-md">{{ $item['label'] }}</span>
                    </button>
                @elseif ($item['method'] ?? null)
                    <form method="POST" action="{{ $item['href'] ?? '#' }}">
                        @csrf
                        @if (strtoupper($item['method']) !== 'POST')
                            @method($item['method'])
                        @endif
                        <button
                            type="submit"
                            data-nav-key="{{ $item['key'] ?? '' }}"
                            @class([
                                'w-full flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 text-left',
                                'border-l-4 border-primary bg-primary-fixed text-on-primary-fixed font-bold' => $isActive,
                                'text-on-surface-variant hover:bg-surface-container-high hover:translate-x-1' => ! $isActive,
                            ])
                        >
                            <span @class(['material-symbols-outlined', 'fill-icon' => $isActive])>{{ $item['icon'] ?? 'circle' }}</span>
                            <span class="font-label-md text-label-md">{{ $item['label'] }}</span>
                        </button>
                    </form>
                @else
                    <a
                        href="{{ $item['href'] ?? '#' }}"
                        data-nav-key="{{ $item['key'] ?? '' }}"
                        @class([
                            'flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200',
                            'border-l-4 border-primary bg-primary-fixed text-on-primary-fixed font-bold' => $isActive,
                            'text-on-surface-variant hover:bg-surface-container-high hover:translate-x-1' => ! $isActive,
                        ])
                    >
                        <span @class(['material-symbols-outlined', 'fill-icon' => $isActive])>{{ $item['icon'] ?? 'circle' }}</span>
                        <span class="font-label-md text-label-md">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    @isset($cta)
        <div class="px-lg mt-xl">
            {{ $cta }}
        </div>
    @endisset
</aside>
