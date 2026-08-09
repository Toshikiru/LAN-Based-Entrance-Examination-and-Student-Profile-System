@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Student Directory" active="students">

    <x-navigation.header
        title="Student Directory"
        subtitle="Manage, filter, and track all registered students across departments."
    >
        <x-slot:actions>
            <x-ui.button :href="route('counselor.students.export', request()->query())" data-turbo="false" variant="outline" icon="download">
                Export CSV
            </x-ui.button>
            @unless ($isSuperAdmin)
                <x-ui.button :href="route('counselor.students.create')" icon="person_add">
                    Add New Student
                </x-ui.button>
            @endunless
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Dashboard Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <x-ui.stat-card icon="group" label="Total Students" :value="number_format($stats['total'])" variant="primary" />
        <x-ui.stat-card icon="calendar_add_on" label="New This Month" :value="number_format($stats['new_this_month'])" variant="secondary" />
        <x-ui.stat-card icon="how_to_reg" label="Active Students" :value="number_format($stats['active'])" variant="tertiary" />
        <x-ui.stat-card icon="archive" label="Archived Profiles" :value="number_format($stats['archived'])" variant="neutral" />
    </div>

    <x-ui.card :padded="false">
        {{-- Filters Toolbar --}}
        <form method="GET" action="{{ route('counselor.students.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by name, ID, or email..."
                rounded="lg"
                class="min-w-[280px] flex-1 max-w-sm"
            />

            <x-ui.select name="department_id" placeholder="All Departments" onchange="this.form.submit()">
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="year_level" placeholder="Year Level" onchange="this.form.submit()">
                @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $level)
                    <option value="{{ $level }}" @selected(($filters['year_level'] ?? null) === $level)>{{ $level }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status" placeholder="Status" onchange="this.form.submit()">
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive</option>
                <option value="suspended" @selected(($filters['status'] ?? null) === 'suspended')>Suspended</option>
                <option value="archived" @selected(($filters['status'] ?? null) === 'archived')>Archived</option>
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('counselor.students.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Data Table --}}
        <x-ui.table :headers="['Name', 'Student ID', 'Department', 'Year Level', 'Exams Taken', 'Status', '']">
            @forelse ($students as $student)
                @php $isArchived = $student->trashed(); @endphp
                <tr class="hover:bg-primary/5 transition-colors group">
                    <td class="px-lg py-md">
                        <a href="{{ route('counselor.students.show', $student) }}" class="flex items-center gap-md">
                            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold shrink-0">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-body-md text-body-md font-semibold text-on-surface">{{ $student->name }}</p>
                                <p class="text-label-sm text-outline">{{ $student->email ?? '—' }}</p>
                            </div>
                        </a>
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $student->school_id }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $student->department?->name ?? '—' }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $student->studentProfile?->year_level ?? '—' }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $student->exam_sessions_count }}</td>
                    <td class="px-lg py-md">
                        @if ($isArchived)
                            <x-ui.badge variant="neutral" dot>Archived</x-ui.badge>
                        @elseif ($student->status === \App\Enums\UserStatus::Active)
                            <x-ui.badge variant="success" dot>Active</x-ui.badge>
                        @elseif ($student->status === \App\Enums\UserStatus::Suspended)
                            <x-ui.badge variant="error" dot>Suspended</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning" dot>Inactive</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-lg py-md text-right whitespace-nowrap">
                        <a href="{{ route('counselor.students.show', $student) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="View">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>

                        @unless ($isSuperAdmin)
                            @if ($isArchived)
                                <form method="POST" action="{{ route('counselor.students.restore', $student) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-secondary transition-colors" title="Restore">
                                        <span class="material-symbols-outlined text-[20px]">restore_from_trash</span>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('counselor.students.edit', $student) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                                <button type="button" class="p-2 text-outline-variant hover:text-error transition-colors" title="Archive" @click="$dispatch('open-modal-archive-{{ $student->id }}')">
                                    <span class="material-symbols-outlined text-[20px]">archive</span>
                                </button>
                            @endif
                        @endunless
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state
                            icon="group_off"
                            title="No students found"
                            description="Try adjusting your search or filters{{ $isSuperAdmin ? '.' : ', or add a new student to get started.' }}"
                        >
                            @unless ($isSuperAdmin)
                                <x-slot:action>
                                    <x-ui.button :href="route('counselor.students.create')" icon="person_add">Add New Student</x-ui.button>
                                </x-slot:action>
                            @endunless
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$students->currentPage()"
                    :total-pages="$students->lastPage()"
                    :total-label="'Showing ' . $students->firstItem() . '-' . $students->lastItem() . ' of ' . $students->total() . ' students'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

    {{-- Archive confirmation modals (one per active row) --}}
    @unless ($isSuperAdmin)
    @foreach ($students as $student)
        @continue($student->trashed())
        <x-ui.modal name="archive-{{ $student->id }}" max-width="sm">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-error text-[36px]">warning</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Archive Student Profile?</h3>
                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    You are about to archive the profile for <span class="font-bold text-on-surface">{{ $student->name }}</span>.
                    This will move the student to the archived list. All historical data is preserved, and the profile
                    can be restored later.
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('counselor.students.archive', $student) }}" class="flex-1">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="danger-solid" icon="archive" class="w-full">
                        Archive Profile
                    </x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
    @endunless

</x-dynamic-component>
