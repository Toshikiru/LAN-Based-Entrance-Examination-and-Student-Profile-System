@php
    // Notifications are shared across all three roles, which each have their own layout —
    // resolve the right one for whoever is signed in rather than duplicating this page per role.
    $layout = match (auth()->user()->role) {
        \App\Enums\UserRole::SuperAdmin => 'layouts.admin',
        \App\Enums\UserRole::Counselor => 'layouts.counselor',
        \App\Enums\UserRole::Student => 'layouts.student',
    };
@endphp
<x-dynamic-component :component="$layout" title="Notifications">

    <x-navigation.header
        title="Notifications"
        subtitle="Recent activity and alerts for your account."
    >
        @if ($notifications->contains(fn ($n) => $n->read_at === null))
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="outline" icon="done_all">Mark all as read</x-ui.button>
                </form>
            </x-slot:actions>
        @endif
    </x-navigation.header>

    <x-ui.card :padded="false">
        <div class="divide-y divide-outline-variant">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="redirect" value="{{ $notification->data['url'] ?? route('notifications.index') }}">
                    <button type="submit" class="w-full text-left px-lg py-md flex items-start gap-md hover:bg-surface-container-low transition-colors {{ $notification->read_at ? '' : 'bg-primary/5' }}">
                        <div class="w-10 h-10 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">{{ $notification->data['icon'] ?? 'notifications' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-label-md text-label-md text-on-surface font-bold">{{ $notification->data['title'] ?? 'Notification' }}</p>
                            <p class="text-body-md text-on-surface-variant">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="text-label-sm text-outline mt-xs">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @unless ($notification->read_at)
                            <span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-2" title="Unread"></span>
                        @endunless
                    </button>
                </form>
            @empty
                <x-ui.empty-state
                    icon="notifications"
                    title="No notifications yet"
                    description="Activity relevant to your account will appear here."
                />
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$notifications->currentPage()"
                    :total-pages="$notifications->lastPage()"
                    :total-label="'Showing ' . $notifications->firstItem() . '-' . $notifications->lastItem() . ' of ' . $notifications->total() . ' notifications'"
                />
            </x-slot:footer>
        @endif
    </x-ui.card>

</x-dynamic-component>
