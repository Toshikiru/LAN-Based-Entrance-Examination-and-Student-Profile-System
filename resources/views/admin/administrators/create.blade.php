<x-layouts.admin title="Add Guidance Administrator" active="administrators">

    <x-navigation.header
        title="Add Guidance Administrator"
        subtitle="Create a new guidance office account with administrative access to the system."
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Guidance Administrators', 'href' => route('admin.administrators.index')],
                ['label' => 'Add Administrator'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <x-ui.card>
        <form method="POST" action="{{ route('admin.administrators.store') }}" class="space-y-xl">
            @csrf

            @include('admin.administrators._form', ['administrator' => null])

            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg pt-lg border-t border-outline-variant">
                <x-ui.input
                    label="Password"
                    name="password"
                    type="password"
                    placeholder="Minimum 8 characters"
                    autocomplete="new-password"
                    required
                />

                <x-ui.input
                    label="Confirm Password"
                    name="password_confirmation"
                    type="password"
                    placeholder="Re-enter password"
                    autocomplete="new-password"
                    required
                />
            </div>

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('admin.administrators.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save Administrator
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.admin>
