@php
    $isActive = $administrator->status === \App\Enums\UserStatus::Active;
@endphp

<x-layouts.admin title="Edit Guidance Administrator" active="administrators">

    <x-navigation.header
        title="Edit Guidance Administrator"
        :subtitle="$administrator->name . ' · ' . $administrator->school_id"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Guidance Administrators', 'href' => route('admin.administrators.index')],
                ['label' => $administrator->name],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button variant="outline" icon="lock_reset" @click="$dispatch('open-modal-reset-{{ $administrator->id }}')">
                Reset Password
            </x-ui.button>

            @if ($isActive)
                <x-ui.button variant="danger" icon="block" @click="$dispatch('open-modal-deactivate-{{ $administrator->id }}')">
                    Deactivate
                </x-ui.button>
            @else
                <form method="POST" action="{{ route('admin.administrators.activate', $administrator) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="secondary" icon="check_circle">Activate</x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @unless ($isActive)
        <x-ui.alert variant="warning" class="mb-lg" icon="block">
            This account is deactivated and cannot log in. Activate it to restore access.
        </x-ui.alert>
    @endunless

    <x-ui.card>
        <form method="POST" action="{{ route('admin.administrators.update', $administrator) }}" class="space-y-xl">
            @csrf
            @method('PUT')

            @include('admin.administrators._form', ['administrator' => $administrator])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('admin.administrators.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save Changes
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Reset Password modal --}}
    <x-ui.modal name="reset-{{ $administrator->id }}" title="Reset Password" max-width="sm">
        <form method="POST" action="{{ route('admin.administrators.reset-password', $administrator) }}" class="space-y-lg">
            @csrf
            @method('PATCH')

            <p class="text-body-md text-on-surface-variant leading-relaxed">
                Set a new password for <span class="font-bold text-on-surface">{{ $administrator->name }}</span>.
                They will use it with their School ID to log in.
            </p>

            <x-ui.input label="New Password" name="password" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
            <x-ui.input label="Confirm Password" name="password_confirmation" type="password" placeholder="Re-enter password" autocomplete="new-password" required />

            <div class="flex gap-md pt-sm">
                <x-ui.button type="button" variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="lock_reset" class="flex-1">Reset Password</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Deactivate confirmation modal --}}
    @if ($isActive)
        <x-ui.modal name="deactivate-{{ $administrator->id }}" max-width="sm">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-error text-[36px]">block</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Deactivate Account?</h3>
                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    <span class="font-bold text-on-surface">{{ $administrator->name }}</span> will no longer be able to
                    log in until the account is reactivated. All records are preserved.
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('admin.administrators.deactivate', $administrator) }}" class="flex-1">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="danger-solid" icon="block" class="w-full">Deactivate</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endif

</x-layouts.admin>
