@php
    // Shared across all three roles, which each have their own layout — resolve
    // the right one for whoever is signed in rather than duplicating this page.
    $layout = match ($user->role) {
        \App\Enums\UserRole::SuperAdmin => 'layouts.admin',
        \App\Enums\UserRole::Counselor => 'layouts.counselor',
        \App\Enums\UserRole::Student => 'layouts.student',
    };

    $isSuperAdmin = $user->role === \App\Enums\UserRole::SuperAdmin;
    $isCounselor = $user->role === \App\Enums\UserRole::Counselor;
    $isStudent = $user->role === \App\Enums\UserRole::Student;

    $enrollmentLabels = \App\Http\Controllers\Counselor\StudentController::ENROLLMENT_STATUSES;
@endphp

<x-dynamic-component :component="$layout" title="My Profile" active="profile">

    <x-navigation.header
        title="My Profile"
        subtitle="View your account details and manage your own security settings."
    />

    @if (session('warning'))
        <x-ui.alert variant="warning" class="mb-lg" icon="lock_reset">{{ session('warning') }}</x-ui.alert>
    @elseif ($user->must_change_password)
        <x-ui.alert variant="warning" class="mb-lg" icon="lock_reset">
            Your password was reset by a Guidance Administrator. Please set a new password below before continuing.
        </x-ui.alert>
    @endif

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Profile Header --}}
    <x-ui.card class="mb-lg">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-lg">
            <div class="flex items-center gap-lg">
                <div class="relative shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-surface-container-highest bg-primary-container text-on-primary flex items-center justify-center font-bold text-headline-lg">
                        @if ($user->avatar_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar_path) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>

                    <button type="button" class="absolute bottom-0 right-0 w-7 h-7 bg-surface-container-lowest border border-outline-variant rounded-full flex items-center justify-center text-on-surface-variant hover:text-primary hover:border-primary transition-colors shadow-sm" @click="$dispatch('open-modal-avatar')">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                </div>
                <div>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-xs">{{ $user->name }}</h2>
                    <div class="flex items-center gap-md flex-wrap">
                        <span class="text-body-md text-on-surface-variant">{{ $user->role->label() }}</span>
                        @if ($user->status === \App\Enums\UserStatus::Active)
                            <x-ui.badge variant="success" dot>Active</x-ui.badge>
                        @elseif ($user->status === \App\Enums\UserStatus::Suspended)
                            <x-ui.badge variant="error" dot>Suspended</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning" dot>Inactive</x-ui.badge>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-lg w-full md:w-auto border-t border-outline-variant md:border-t-0 pt-md md:pt-0">
                <div class="flex flex-col gap-xs">
                    <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Account Created</div>
                    <div class="font-body-md text-body-md text-on-surface">{{ $user->created_at->format('M j, Y') }}</div>
                </div>
                <div class="hidden sm:block w-px h-10 bg-outline-variant"></div>
                <div class="flex flex-col gap-xs">
                    <div class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Last Login</div>
                    <div class="font-body-md text-body-md text-on-surface">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-lg">
            {{-- Identity Information (read-only for everyone — set by whoever manages this account type) --}}
            <x-ui.card title="Personal Information">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-lg gap-x-md">
                    <div>
                        <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Full Name</span>
                        <div class="font-body-md text-body-md text-on-surface font-medium">{{ $user->name }}</div>
                    </div>
                    <div>
                        <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">School ID</span>
                        <div class="font-body-md text-body-md text-on-surface font-mono">{{ $user->school_id }}</div>
                    </div>

                    @if ($isCounselor)
                        <div>
                            <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Position Title</span>
                            <div class="font-body-md text-body-md text-on-surface">{{ $user->counselorProfile?->position_title ?? '—' }}</div>
                        </div>
                    @endif
                </div>

                @if (! $isSuperAdmin)
                    <p class="text-label-sm text-outline mt-lg pt-lg border-t border-outline-variant">
                        @if ($isStudent)
                            Your name and school ID are official records maintained by the Guidance Office. Contact your counselor to request a correction.
                        @else
                            Your name is managed by the Super Admin. Contact them to request a correction.
                        @endif
                    </p>
                @endif
            </x-ui.card>

            {{-- Academic Information (students only, read-only) --}}
            @if ($isStudent)
                <x-ui.card title="Academic Information">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-lg gap-x-md">
                        <div>
                            <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Department</span>
                            <div class="font-body-md text-body-md text-on-surface font-medium">{{ $user->department?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Year Level</span>
                            <div class="font-body-md text-body-md text-on-surface">{{ $user->studentProfile?->year_level ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Section</span>
                            <div class="font-body-md text-body-md text-on-surface">{{ $user->studentProfile?->section ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="block font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-xs">Enrollment Status</span>
                            <div class="font-body-md text-body-md text-on-surface">{{ $enrollmentLabels[$user->studentProfile?->enrollment_status] ?? '—' }}</div>
                        </div>
                    </div>
                </x-ui.card>
            @endif

            {{-- Contact Information (email is editable by everyone; name only for Super Admin) --}}
            <x-ui.card title="Contact Information">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-lg">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-lg gap-x-md">
                        @if ($isSuperAdmin)
                            <x-ui.input
                                label="Full Name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                autocomplete="name"
                                required
                            />
                        @endif

                        <x-ui.input
                            label="Email Address"
                            name="email"
                            type="email"
                            placeholder="you@example.com"
                            value="{{ old('email', $user->email) }}"
                            :hint="$isStudent || $isCounselor ? 'Used for account notifications only — your School ID remains your login.' : null"
                            autocomplete="email"
                        />
                    </div>

                    <div class="flex justify-end pt-md border-t border-outline-variant">
                        <x-ui.button type="submit" icon="check">Save Changes</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>

        {{-- Right Column --}}
        <div class="space-y-lg">
            {{-- Security Settings --}}
            <x-ui.card title="Security Settings">
                <x-slot:actions>
                    <span class="material-symbols-outlined text-on-surface-variant">lock</span>
                </x-slot:actions>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-sm">
                    @csrf
                    @method('PATCH')

                    <x-ui.input label="Current Password" name="current_password" type="password" placeholder="Current password" autocomplete="current-password" required />
                    <x-ui.input label="New Password" name="password" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                    <x-ui.input label="Confirm New Password" name="password_confirmation" type="password" placeholder="Re-enter new password" autocomplete="new-password" required />

                    <x-ui.button type="submit" variant="secondary" class="w-full mt-md">Update Password</x-ui.button>

                    <div class="text-center font-label-sm text-label-sm text-on-surface-variant pt-sm">
                        @if ($user->password_changed_at)
                            Last changed {{ $user->password_changed_at->diffForHumans() }}
                        @else
                            Password never changed since account creation.
                        @endif
                    </div>
                </form>
            </x-ui.card>

            {{-- Recent Activity — real notification history for this account --}}
            <x-ui.card title="Recent Activity" :padded="false">
                @if ($recentActivity->isEmpty())
                    <x-ui.empty-state icon="history" title="No recent activity" description="Account activity will appear here." class="py-lg" />
                @else
                    <div class="relative border-l border-outline-variant ml-lg space-y-lg py-lg pr-lg">
                        @foreach ($recentActivity as $activity)
                            <div class="relative pl-lg">
                                <div class="absolute -left-[5px] top-1 w-2.5 h-2.5 rounded-full {{ $activity->read_at ? 'bg-outline' : 'bg-primary' }} ring-4 ring-surface-container-lowest"></div>
                                <div class="font-label-md text-label-md text-on-surface">{{ $activity->data['title'] ?? 'Notification' }}</div>
                                <div class="font-body-md text-body-md text-on-surface-variant mt-xs">{{ $activity->data['message'] ?? '' }}</div>
                                <div class="font-label-sm text-label-sm text-on-surface-variant mt-xs">{{ $activity->created_at->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <x-slot:footer>
                    <a href="{{ route('notifications.index') }}" class="block text-center text-primary font-label-md text-label-md py-1 hover:underline">View Full History</a>
                </x-slot:footer>
            </x-ui.card>
        </div>
    </div>

    {{-- Avatar upload modal --}}
    <x-ui.modal name="avatar" title="Update Profile Photo" max-width="sm">
        <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="space-y-lg" id="avatar-upload-form">
            @csrf
            <x-ui.input label="Photo" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" required />
            <p class="text-label-sm text-outline">JPG, PNG, or WEBP. Max 2MB.</p>
        </form>

        @if ($user->avatar_path)
            <form method="POST" action="{{ route('profile.avatar.destroy') }}" id="avatar-remove-form">
                @csrf
                @method('DELETE')
            </form>
        @endif

        <div class="flex gap-md pt-lg">
            @if ($user->avatar_path)
                <x-ui.button type="submit" form="avatar-remove-form" variant="danger" class="flex-1">Remove Photo</x-ui.button>
            @endif
            <x-ui.button type="submit" form="avatar-upload-form" icon="upload" class="flex-1">Upload</x-ui.button>
        </div>
    </x-ui.modal>

</x-dynamic-component>
