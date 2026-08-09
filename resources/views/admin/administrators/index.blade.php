<x-layouts.admin title="Guidance Administrators" active="administrators">

    <x-navigation.header
        title="Guidance Administrators"
        subtitle="Manage guidance office accounts that operate the entrance examination and student profile system."
    >
        <x-slot:actions>
            <x-ui.button :href="route('admin.administrators.create')" icon="person_add">
                Add Administrator
            </x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <x-ui.stat-card icon="shield_person" label="Total Administrators" :value="number_format($stats['total'])" variant="primary" />
        <x-ui.stat-card icon="how_to_reg" label="Active" :value="number_format($stats['active'])" variant="secondary" />
        <x-ui.stat-card icon="block" label="Deactivated" :value="number_format($stats['inactive'])" variant="neutral" />
    </div>

    <x-ui.card :padded="false">
        {{-- Filters Toolbar --}}
        <form method="GET" action="{{ route('admin.administrators.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by name, ID, or email..."
                rounded="lg"
                class="min-w-[280px] flex-1 max-w-sm"
            />

            <x-ui.select name="status" placeholder="All Statuses" onchange="this.form.submit()">
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Deactivated</option>
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('admin.administrators.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Data Table --}}
        <x-ui.table :headers="['Name', 'School ID', 'Position', 'Status', '']">
            @forelse ($administrators as $administrator)
                @php $isActive = $administrator->status === \App\Enums\UserStatus::Active; @endphp
                <tr class="hover:bg-primary/5 transition-colors group">
                    <td class="px-lg py-md">
                        <a href="{{ route('admin.administrators.edit', $administrator) }}" class="flex items-center gap-md">
                            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold shrink-0">
                                {{ strtoupper(substr($administrator->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-body-md text-body-md font-semibold text-on-surface">{{ $administrator->name }}</p>
                                <p class="text-label-sm text-outline">{{ $administrator->email ?? '—' }}</p>
                            </div>
                        </a>
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $administrator->school_id }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $administrator->counselorProfile?->position_title ?? '—' }}</td>
                    <td class="px-lg py-md">
                        @if ($isActive)
                            <x-ui.badge variant="success" dot>Active</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral" dot>Deactivated</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-lg py-md text-right whitespace-nowrap">
                        <a href="{{ route('admin.administrators.edit', $administrator) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Edit">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </a>

                        <button type="button" class="p-2 text-outline-variant hover:text-primary transition-colors" title="Reset Password" @click="$dispatch('open-modal-reset-{{ $administrator->id }}')">
                            <span class="material-symbols-outlined text-[20px]">lock_reset</span>
                        </button>

                        @if ($isActive)
                            <button type="button" class="p-2 text-outline-variant hover:text-error transition-colors" title="Deactivate" @click="$dispatch('open-modal-deactivate-{{ $administrator->id }}')">
                                <span class="material-symbols-outlined text-[20px]">block</span>
                            </button>
                        @else
                            <form method="POST" action="{{ route('admin.administrators.activate', $administrator) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-2 text-outline-variant hover:text-secondary transition-colors" title="Activate">
                                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-ui.empty-state
                            icon="person_off"
                            title="No administrators found"
                            description="Try adjusting your search or filters, or add a new guidance administrator to get started."
                        >
                            <x-slot:action>
                                <x-ui.button :href="route('admin.administrators.create')" icon="person_add">Add Administrator</x-ui.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$administrators->currentPage()"
                    :total-pages="$administrators->lastPage()"
                    :total-label="'Showing ' . $administrators->firstItem() . '-' . $administrators->lastItem() . ' of ' . $administrators->total() . ' administrators'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

    {{-- Per-row modals --}}
    @foreach ($administrators as $administrator)
        @php $isActive = $administrator->status === \App\Enums\UserStatus::Active; @endphp

        {{-- Reset Password --}}
        <x-ui.modal name="reset-{{ $administrator->id }}" title="Reset Password" max-width="sm">
            <form method="POST" action="{{ route('admin.administrators.reset-password', $administrator) }}" class="space-y-lg">
                @csrf
                @method('PATCH')

                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    Set a new password for <span class="font-bold text-on-surface">{{ $administrator->name }}</span>.
                </p>

                <x-ui.input id="reset-password-{{ $administrator->id }}" label="New Password" name="password" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                <x-ui.input id="reset-password_confirmation-{{ $administrator->id }}" label="Confirm Password" name="password_confirmation" type="password" placeholder="Re-enter password" autocomplete="new-password" required />

                <div class="flex gap-md pt-sm">
                    <x-ui.button type="button" variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="lock_reset" class="flex-1">Reset</x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        {{-- Deactivate --}}
        @if ($isActive)
            <x-ui.modal name="deactivate-{{ $administrator->id }}" max-width="sm">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                        <span class="material-symbols-outlined text-error text-[36px]">block</span>
                    </div>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Deactivate Account?</h3>
                    <p class="text-body-md text-on-surface-variant leading-relaxed">
                        <span class="font-bold text-on-surface">{{ $administrator->name }}</span> will no longer be able to
                        log in until reactivated. All records are preserved.
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
    @endforeach

</x-layouts.admin>
