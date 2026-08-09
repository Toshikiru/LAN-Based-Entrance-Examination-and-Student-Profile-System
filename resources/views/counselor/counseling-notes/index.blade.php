@php
    use App\Enums\CounselingNoteCategory;
    use App\Enums\CounselingNoteStatus;

    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';

    $categoryColors = [
        'academic' => 'primary',
        'behavioral' => 'error',
        'career' => 'tertiary',
        'personal' => 'secondary',
    ];
@endphp

<x-dynamic-component :component="$layout" title="Counseling Notes" active="counseling-notes">

    <x-navigation.header
        title="Counseling Notes"
        subtitle="Oversight view of all guidance and counseling notes across every student."
    />

    <x-ui.card :padded="false">
        {{-- Filters --}}
        <form method="GET" action="{{ route('counselor.counseling-notes.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by student name or ID..."
                rounded="lg"
                class="min-w-[260px] flex-1 max-w-sm"
            />

            <x-ui.select name="category" placeholder="All Categories" onchange="this.form.submit()">
                @foreach (CounselingNoteCategory::cases() as $category)
                    <option value="{{ $category->value }}" @selected(($filters['category'] ?? null) === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status" placeholder="All Statuses" onchange="this.form.submit()">
                @foreach (CounselingNoteStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('counselor.counseling-notes.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        <x-ui.table :headers="['Student', 'Category', 'Status', 'Note Date', 'Counselor', 'Follow-up']">
            @forelse ($notes as $note)
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-lg py-md">
                        @if ($note->student)
                            <a href="{{ route('counselor.students.show', ['student' => $note->student, 'tab' => 'counseling']) }}" class="block">
                                <p class="font-body-md text-body-md font-semibold text-on-surface">{{ $note->student->name }}</p>
                                <p class="text-label-sm text-outline font-mono">{{ $note->student->school_id }}</p>
                            </a>
                        @else
                            <span class="text-outline">—</span>
                        @endif
                    </td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$categoryColors[$note->category->value] ?? 'neutral'">{{ $note->category->label() }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$note->status->value === 'open' ? 'warning' : 'neutral'" dot>{{ $note->status->label() }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $note->note_date->format('M d, Y') }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $note->counselor?->name ?? 'Unknown' }}</td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $note->follow_up_date?->format('M d, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state
                            icon="chat"
                            title="No counseling notes found"
                            description="Try adjusting your search or filters. Notes are added from a student's profile page."
                        />
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$notes->currentPage()"
                    :total-pages="$notes->lastPage()"
                    :total-label="'Showing ' . $notes->firstItem() . '-' . $notes->lastItem() . ' of ' . $notes->total() . ' notes'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

</x-dynamic-component>
