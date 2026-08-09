@props([
    'searchPlaceholder' => 'Search...',
])

<header id="app-topnav" class="sticky top-0 z-30 flex items-center justify-between gap-md px-lg py-sm w-full bg-surface-container-lowest border-b border-outline-variant shadow-sm h-16">
    <div class="flex items-center gap-lg flex-1 min-w-0">
        <button type="button" class="lg:hidden text-on-surface-variant shrink-0" @click="$store.sidebar.open = true">
            <span class="material-symbols-outlined">menu</span>
        </button>

        {{-- The sidebar (which normally carries the system name) is off-canvas below `lg`,
             so without this the brand is invisible on mobile/tablet until the drawer opens. --}}
        <div class="lg:hidden flex items-center gap-xs min-w-0 shrink">
            <span class="font-label-md text-label-md font-bold text-on-surface truncate" title="{{ $branding['system_name'] }}">{{ $branding['system_name'] }}</span>
        </div>

        <x-ui.search-bar :placeholder="$searchPlaceholder" class="max-w-md w-full hidden sm:flex" />
    </div>

    @php
        $unreadNotificationsCount = auth()->user()?->unreadNotifications()->count() ?? 0;
        $recentNotifications = auth()->user()?->notifications()->latest()->limit(8)->get() ?? collect();
    @endphp

    <div class="flex items-center gap-sm shrink-0">
        <x-ui.theme-toggle />

        <div class="relative" x-data="{ notifOpen: false }">
            <button type="button" class="p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full transition-colors relative" @click="notifOpen = !notifOpen">
                <span class="material-symbols-outlined">notifications</span>
                @if ($unreadNotificationsCount > 0)
                    <span class="absolute top-0 right-0 min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full bg-error text-on-error text-[10px] font-bold border-2 border-surface-container-lowest">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                @endif
            </button>

            <div
                x-show="notifOpen"
                x-cloak
                x-transition
                @click.outside="notifOpen = false"
                class="absolute right-0 mt-sm w-80 max-w-[90vw] bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg z-40 flex flex-col"
            >
                <div class="px-md py-sm border-b border-outline-variant flex items-center justify-between">
                    <p class="font-label-md text-label-md text-on-surface font-bold">Notifications</p>
                    @if ($unreadNotificationsCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-label-sm text-primary hover:underline">Mark all as read</button>
                        </form>
                    @endif
                </div>

                <div class="max-h-96 overflow-y-auto custom-scrollbar divide-y divide-outline-variant">
                    @forelse ($recentNotifications as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="redirect" value="{{ $notification->data['url'] ?? route('notifications.index') }}">
                            <button type="submit" class="w-full text-left px-md py-sm flex items-start gap-sm hover:bg-surface-container-low transition-colors {{ $notification->read_at ? 'opacity-60' : '' }}">
                                <span class="material-symbols-outlined text-primary text-[20px] mt-[2px] shrink-0">{{ $notification->data['icon'] ?? 'notifications' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-label-md text-label-sm text-on-surface font-bold truncate">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-label-sm text-on-surface-variant line-clamp-2">{{ $notification->data['message'] ?? '' }}</p>
                                    <p class="text-[10px] text-outline mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                @unless ($notification->read_at)
                                    <span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-1" title="Unread"></span>
                                @endunless
                            </button>
                        </form>
                    @empty
                        <div class="p-lg">
                            <x-ui.empty-state icon="notifications" title="No notifications" description="You're all caught up." />
                        </div>
                    @endforelse
                </div>

                <div class="p-sm border-t border-outline-variant">
                    <a href="{{ route('notifications.index') }}" class="block text-center text-label-sm text-primary hover:underline py-1">View All</a>
                </div>
            </div>
        </div>

        <button type="button" class="p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full transition-colors">
            <span class="material-symbols-outlined">help_outline</span>
        </button>

        <div class="h-8 w-px bg-outline-variant mx-1 hidden sm:block"></div>

        <div class="relative" x-data="{ userMenuOpen: false }">
            <button type="button" class="flex items-center gap-sm cursor-pointer group" @click="userMenuOpen = !userMenuOpen">
                <div class="text-right hidden sm:block">
                    <p class="font-label-md text-label-md text-on-surface leading-none">{{ auth()->user()->name ?? 'Guest' }}</p>
                    <p class="text-[11px] text-outline">{{ auth()->user()?->role?->label() ?? '' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full border-2 border-primary-fixed group-hover:border-primary transition-colors bg-primary-container text-on-primary flex items-center justify-center font-bold shrink-0 overflow-hidden">
                    @if (auth()->user()?->avatar_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr(auth()->user()->name ?? 'G', 0, 1)) }}
                    @endif
                </div>
            </button>

            <div
                x-show="userMenuOpen"
                x-cloak
                x-transition
                @click.outside="userMenuOpen = false"
                class="absolute right-0 mt-sm w-56 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg py-xs z-40"
            >
                <div class="px-md py-sm border-b border-outline-variant">
                    <p class="font-label-md text-label-md text-on-surface">{{ auth()->user()->name ?? 'Guest' }}</p>
                    <p class="text-[11px] text-outline truncate">{{ auth()->user()->school_id ?? '' }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-sm px-md py-sm text-label-md text-on-surface-variant hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                    Profile
                </a>
                <button type="button" @click="userMenuOpen = false; $dispatch('open-modal-logout-confirm')" class="w-full flex items-center gap-sm px-md py-sm text-label-md text-error hover:bg-error-container transition-colors text-left">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    Sign Out
                </button>
            </div>
        </div>
    </div>
</header>

<x-ui.modal name="logout-confirm" max-width="sm">
    <div class="flex flex-col items-center text-center">
        <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
            <span class="material-symbols-outlined text-error text-[36px]">logout</span>
        </div>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Sign Out?</h3>
        <p class="text-body-md text-on-surface-variant leading-relaxed">
            You'll need to log in again with your School ID and password to access your account.
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <x-ui.button type="submit" variant="danger-solid" icon="logout" class="w-full">Sign Out</x-ui.button>
        </form>
    </x-slot:footer>
</x-ui.modal>
