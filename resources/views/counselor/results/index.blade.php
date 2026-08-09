@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $layout = auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Results" active="results">

    <x-navigation.header
        title="Results"
        subtitle="Choose an examination to view and export results."
    />

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('counselor.results.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by examination title..."
                rounded="lg"
                class="min-w-[280px] flex-1 max-w-sm"
            />
            <x-ui.button type="submit" variant="secondary" size="sm" icon="search">Search</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('counselor.results.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        <x-ui.table :headers="['Examination', 'Status', 'Submissions', '']">
            @forelse ($exams as $exam)
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-lg py-md">
                        <p class="font-label-md text-on-surface">{{ $exam->title }}</p>
                        <p class="text-label-sm text-outline">{{ $exam->duration_minutes }} min</p>
                    </td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$exam->status->value === 'published' ? 'success' : 'neutral'" dot>{{ ucfirst($exam->status->value) }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md font-label-md text-on-surface">{{ $exam->submissions_count }}</td>
                    <td class="px-lg py-md text-right">
                        <x-ui.button :href="route('counselor.exams.results', $exam)" size="sm" variant="outline" icon="grade">View Results</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <x-ui.empty-state icon="grade" title="No examinations yet" description="Results appear here once you have exams with submissions." />
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

</x-dynamic-component>
