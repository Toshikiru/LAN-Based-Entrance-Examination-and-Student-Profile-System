@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Entrance Examinations" active="exams">

    <x-navigation.header
        title="Entrance Examinations"
        :subtitle="$isSuperAdmin ? 'View all admission tests across courses.' : 'Manage and monitor all admission tests across courses.'"
    >
        @unless ($isSuperAdmin)
            <x-slot:actions>
                <x-ui.button :href="route('counselor.exams.create')" icon="add">
                    Create New Exam
                </x-ui.button>
            </x-slot:actions>
        @endunless
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif
    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <x-ui.stat-card icon="quiz" label="Total Exams" :value="number_format($stats['total'])" variant="primary" />
        <x-ui.stat-card icon="play_circle" label="Active" :value="number_format($stats['active'])" variant="secondary" />
        <x-ui.stat-card icon="edit_note" label="Drafts" :value="number_format($stats['draft'])" variant="tertiary" />
        <x-ui.stat-card icon="check_circle" label="Closed" :value="number_format($stats['closed'])" variant="neutral" />
    </div>

    <x-ui.card :padded="false">
        {{-- Filters Toolbar --}}
        <form method="GET" action="{{ route('counselor.exams.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by title..."
                rounded="lg"
                class="min-w-[260px] flex-1 max-w-sm"
            />

            <x-ui.select name="status" placeholder="All Statuses" onchange="this.form.submit()">
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected(($filters['status'] ?? null) === $statusOption->value)>{{ ucfirst($statusOption->value) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="department_id" placeholder="All Courses" onchange="this.form.submit()">
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="year_level" placeholder="All Year Levels" onchange="this.form.submit()">
                @foreach ($yearLevels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['year_level'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('counselor.exams.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Data Table --}}
        <x-ui.table :headers="['Exam Title', 'Course', 'Year Level', 'Schedule', 'Duration', 'Status', '']">
            @forelse ($exams as $exam)
                @php
                    $isActive = $exam->status === \App\Enums\ExamStatus::Published;
                    $statusVariant = match ($exam->status) {
                        \App\Enums\ExamStatus::Published => 'success',
                        \App\Enums\ExamStatus::Ongoing => 'primary',
                        \App\Enums\ExamStatus::Closed => 'warning',
                        default => 'neutral',
                    };
                @endphp
                <tr class="hover:bg-primary/5 transition-colors group">
                    <td class="px-lg py-md">
                        <a href="{{ $isSuperAdmin ? route('counselor.exams.preview', $exam) : route('counselor.exams.edit', $exam) }}" class="block">
                            <p class="font-body-md text-body-md font-semibold text-on-surface">{{ $exam->title }}</p>
                            <p class="text-label-sm text-outline font-mono">ID: EXM-{{ str_pad($exam->id, 4, '0', STR_PAD_LEFT) }}</p>
                        </a>
                    </td>
                    <td class="px-lg py-md">
                        @if ($exam->department)
                            <span class="inline-flex items-center px-2 py-1 rounded bg-primary/10 text-primary font-label-sm text-label-sm">{{ $exam->department->name }}</span>
                        @else
                            <span class="text-label-md text-outline">—</span>
                        @endif
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $exam->year_level ?? '—' }}</td>
                    <td class="px-lg py-md">
                        @if ($exam->starts_at)
                            <p class="font-label-md text-label-md text-on-surface">{{ $exam->starts_at->format('M d, Y') }}</p>
                            <p class="text-label-sm text-outline">{{ $exam->starts_at->format('h:i A') }}</p>
                        @else
                            <span class="text-label-md text-outline italic">Not set</span>
                        @endif
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface">{{ $exam->duration_minutes ? $exam->duration_minutes . ' min' : 'No limit' }}</td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$statusVariant" dot>{{ ucfirst($exam->status->value) }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md text-right whitespace-nowrap">
                        @if ($isSuperAdmin)
                            <a href="{{ route('counselor.exams.preview', $exam) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Preview">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </a>
                            <a href="{{ route('counselor.exams.results', $exam) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Results">
                                <span class="material-symbols-outlined text-[20px]">grade</span>
                            </a>
                        @else
                            <a href="{{ route('counselor.exams.builder', $exam) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Build">
                                <span class="material-symbols-outlined text-[20px]">build</span>
                            </a>
                            <a href="{{ route('counselor.exams.results', $exam) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Results">
                                <span class="material-symbols-outlined text-[20px]">grade</span>
                            </a>
                            <a href="{{ route('counselor.exams.edit', $exam) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>

                            @if ($isActive)
                                <form method="POST" action="{{ route('counselor.exams.deactivate', $exam) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-tertiary transition-colors" title="Deactivate">
                                        <span class="material-symbols-outlined text-[20px]">pause_circle</span>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('counselor.exams.activate', $exam) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-secondary transition-colors" title="Activate">
                                        <span class="material-symbols-outlined text-[20px]">play_circle</span>
                                    </button>
                                </form>
                            @endif

                            <button type="button" class="p-2 text-outline-variant hover:text-error transition-colors" title="Delete" @click="$dispatch('open-modal-delete-{{ $exam->id }}')">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state
                            icon="quiz"
                            title="No examinations found"
                            :description="'Try adjusting your search or filters' . ($isSuperAdmin ? '.' : ', or create a new examination to get started.')"
                        >
                            @unless ($isSuperAdmin)
                                <x-slot:action>
                                    <x-ui.button :href="route('counselor.exams.create')" icon="add">Create New Exam</x-ui.button>
                                </x-slot:action>
                            @endunless
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$exams->currentPage()"
                    :total-pages="$exams->lastPage()"
                    :total-label="'Showing ' . $exams->firstItem() . '-' . $exams->lastItem() . ' of ' . $exams->total() . ' examinations'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

    {{-- Per-row delete modals --}}
    @unless ($isSuperAdmin)
    @foreach ($exams as $exam)
        <x-ui.modal name="delete-{{ $exam->id }}" max-width="sm">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-error text-[36px]">delete</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Delete Examination?</h3>
                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    <span class="font-bold text-on-surface">{{ $exam->title }}</span> will be removed from the list.
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('counselor.exams.destroy', $exam) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger-solid" icon="delete" class="w-full">Delete</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
    @endunless

</x-dynamic-component>
