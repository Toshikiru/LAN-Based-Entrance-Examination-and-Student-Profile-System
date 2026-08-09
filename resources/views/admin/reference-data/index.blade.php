<x-layouts.admin title="Reference Data" active="reference-data">

    <x-ui.breadcrumb class="mb-lg" :items="[
        ['label' => 'Settings', 'href' => route('admin.settings.index')],
        ['label' => 'Reference Data'],
    ]" />

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="flex justify-between items-end gap-md mb-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Reference Data</h2>
            <p class="text-on-surface-variant font-body-md">Manage institutional departments, courses, and year levels.</p>
        </div>
    </div>

    <div x-data="{ tab: '{{ $tab }}' }">
        <nav class="flex gap-xl border-b border-outline-variant mb-lg">
            <button type="button" @click="tab = 'departments'" :class="tab === 'departments' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors">Departments</button>
            <button type="button" @click="tab = 'courses'" :class="tab === 'courses' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors">Courses</button>
            <button type="button" @click="tab = 'year-levels'" :class="tab === 'year-levels' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors">Year Levels</button>
        </nav>

        <form method="GET" action="{{ route('admin.reference-data.index') }}" class="flex flex-wrap items-center gap-md mb-lg">
            <input type="hidden" name="tab" x-bind:value="tab">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by name or code..."
                rounded="lg"
                class="min-w-[280px] flex-1 max-w-sm"
            />
            <x-ui.button type="submit" variant="secondary" size="sm" icon="search">Search</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('admin.reference-data.index', ['tab' => $tab]) }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Departments --}}
        <div x-show="tab === 'departments'" x-cloak>
            <div class="flex justify-end mb-md">
                <x-ui.button type="button" icon="add_circle" @click="$dispatch('open-modal-add-department')">Add Department</x-ui.button>
            </div>

            <x-ui.table :headers="['Name', 'Code', 'Users', 'Status', '']">
                @forelse ($departments as $department)
                    @php $isArchived = $department->trashed(); @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-4 py-4 font-label-md text-on-surface">{{ $department->name }}</td>
                        <td class="px-4 py-4 font-mono text-label-md text-on-surface-variant">{{ $department->code }}</td>
                        <td class="px-4 py-4 text-on-surface-variant">{{ $department->users_count }}</td>
                        <td class="px-4 py-4">
                            <x-ui.badge :variant="$isArchived ? 'neutral' : 'success'" dot>{{ $isArchived ? 'Archived' : 'Active' }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-4 text-right whitespace-nowrap">
                            @unless ($isArchived)
                                <button type="button" class="p-2 text-outline-variant hover:text-primary transition-colors" title="Edit" @click="$dispatch('open-modal-edit-department-{{ $department->id }}')">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <form method="POST" action="{{ route('admin.reference-data.departments.archive', $department) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-error transition-colors" title="Archive">
                                        <span class="material-symbols-outlined text-[20px]">archive</span>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reference-data.departments.restore', $department) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-secondary transition-colors" title="Restore">
                                        <span class="material-symbols-outlined text-[20px]">restore_from_trash</span>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>

                    @unless ($isArchived)
                        @include('admin.reference-data.partials._department-form', [
                            'modalName' => 'edit-department-' . $department->id,
                            'department' => $department,
                        ])
                    @endunless
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4">
                            <x-ui.empty-state icon="corporate_fare" title="No departments yet" description="Add your first department to get started." />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </div>

        {{-- Courses --}}
        <div x-show="tab === 'courses'" x-cloak>
            <div class="flex justify-end mb-md">
                <x-ui.button type="button" icon="add_circle" @click="$dispatch('open-modal-add-course')">Add Course</x-ui.button>
            </div>

            <x-ui.table :headers="['Course Name', 'Code', 'Department', 'Duration', 'Status', '']">
                @forelse ($courses as $course)
                    @php $isArchived = $course->trashed(); @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-4 py-4 font-label-md text-on-surface">{{ $course->name }}</td>
                        <td class="px-4 py-4 font-mono text-label-md text-on-surface-variant">{{ $course->code }}</td>
                        <td class="px-4 py-4 text-on-surface-variant">{{ $course->department?->name ?? '—' }}</td>
                        <td class="px-4 py-4 text-on-surface-variant">
                            @if ($course->duration_years)
                                {{ $course->duration_years }} Year{{ $course->duration_years === 1 ? '' : 's' }}
                                @if ($course->duration_semesters) ({{ $course->duration_semesters }} Sem) @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <x-ui.badge :variant="$isArchived ? 'neutral' : 'success'" dot>{{ $isArchived ? 'Archived' : 'Active' }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-4 text-right whitespace-nowrap">
                            @unless ($isArchived)
                                <button type="button" class="p-2 text-outline-variant hover:text-primary transition-colors" title="Edit" @click="$dispatch('open-modal-edit-course-{{ $course->id }}')">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <form method="POST" action="{{ route('admin.reference-data.courses.archive', $course) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-error transition-colors" title="Archive">
                                        <span class="material-symbols-outlined text-[20px]">archive</span>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reference-data.courses.restore', $course) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-secondary transition-colors" title="Restore">
                                        <span class="material-symbols-outlined text-[20px]">restore_from_trash</span>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>

                    @unless ($isArchived)
                        @include('admin.reference-data.partials._course-form', [
                            'modalName' => 'edit-course-' . $course->id,
                            'course' => $course,
                            'departments' => $departments->reject->trashed(),
                        ])
                    @endunless
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4">
                            <x-ui.empty-state icon="menu_book" title="No courses yet" description="Add your first course to get started." />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </div>

        {{-- Year Levels --}}
        <div x-show="tab === 'year-levels'" x-cloak>
            <div class="flex justify-end mb-md">
                <x-ui.button type="button" icon="add_circle" @click="$dispatch('open-modal-add-year-level')">Add Year Level</x-ui.button>
            </div>

            <x-ui.table :headers="['Name', 'Order', 'Status', '']">
                @forelse ($yearLevels as $yearLevel)
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-4 py-4 font-label-md text-on-surface">{{ $yearLevel->name }}</td>
                        <td class="px-4 py-4 text-on-surface-variant">{{ $yearLevel->order }}</td>
                        <td class="px-4 py-4">
                            <x-ui.badge :variant="$yearLevel->is_active ? 'success' : 'neutral'" dot>{{ $yearLevel->is_active ? 'Active' : 'Disabled' }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-4 text-right whitespace-nowrap">
                            <button type="button" class="p-2 text-outline-variant hover:text-primary transition-colors" title="Edit" @click="$dispatch('open-modal-edit-year-level-{{ $yearLevel->id }}')">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                            <form method="POST" action="{{ route('admin.reference-data.year-levels.toggle', $yearLevel) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-2 text-outline-variant hover:{{ $yearLevel->is_active ? 'text-error' : 'text-secondary' }} transition-colors" title="{{ $yearLevel->is_active ? 'Disable' : 'Enable' }}">
                                    <span class="material-symbols-outlined text-[20px]">{{ $yearLevel->is_active ? 'visibility_off' : 'visibility' }}</span>
                                </button>
                            </form>
                        </td>
                    </tr>

                    @include('admin.reference-data.partials._year-level-form', [
                        'modalName' => 'edit-year-level-' . $yearLevel->id,
                        'yearLevel' => $yearLevel,
                    ])
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4">
                            <x-ui.empty-state icon="stairs" title="No year levels yet" description="Add year levels like &quot;1st Year&quot; to standardize student and exam targeting." />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </div>
    </div>

    @include('admin.reference-data.partials._department-form', ['modalName' => 'add-department', 'department' => null])
    @include('admin.reference-data.partials._course-form', ['modalName' => 'add-course', 'course' => null, 'departments' => $departments->reject->trashed()])
    @include('admin.reference-data.partials._year-level-form', ['modalName' => 'add-year-level', 'yearLevel' => null])

</x-layouts.admin>
