@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $examHref = fn ($exam) => $isSuperAdmin ? route('counselor.exams.preview', $exam) : route('counselor.exams.edit', $exam);
@endphp

<x-layouts.counselor title="Search">

    <x-navigation.header
        title="Search Results"
        :subtitle="$query !== '' ? 'Results for &quot;' . $query . '&quot;' : 'Type something in the header search bar to get started.'"
    />

    @if ($query === '')
        <x-ui.empty-state icon="search" title="Start typing to search" description="Search across students, exams, and reports from the header search bar." />
    @else
        <div class="space-y-lg">
            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Students</h3>
                    <a href="{{ route('counselor.students.index', ['search' => $query]) }}" class="text-label-sm text-primary hover:underline">View all in Students</a>
                </div>

                @if ($students->isEmpty())
                    <div class="p-lg">
                        <x-ui.empty-state icon="person_search" title="No matching students" description="Try a different name, School ID, or email." />
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($students as $student)
                            <a href="{{ route('counselor.students.show', $student) }}" class="flex items-center justify-between gap-md p-lg hover:bg-primary/5 transition-colors">
                                <div class="min-w-0">
                                    <p class="font-label-md text-label-md text-on-surface truncate">{{ $student->name }}</p>
                                    <p class="text-label-sm text-outline truncate">{{ $student->school_id }} &middot; {{ $student->department?->name ?? 'No department' }}</p>
                                </div>
                                <span class="material-symbols-outlined text-outline-variant shrink-0">chevron_right</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Exams &amp; Reports</h3>
                    <a href="{{ route('counselor.exams.index', ['search' => $query]) }}" class="text-label-sm text-primary hover:underline">View all in Exams</a>
                </div>

                @if ($exams->isEmpty())
                    <div class="p-lg">
                        <x-ui.empty-state icon="quiz" title="No matching exams" description="Try a different examination title." />
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($exams as $exam)
                            <div class="flex items-center justify-between gap-md p-lg hover:bg-primary/5 transition-colors">
                                <a href="{{ $examHref($exam) }}" class="min-w-0 flex-1">
                                    <p class="font-label-md text-label-md text-on-surface truncate">{{ $exam->title }}</p>
                                    <p class="text-label-sm text-outline truncate">{{ ucfirst($exam->status->value) }}</p>
                                </a>
                                <a href="{{ route('counselor.exams.results', $exam) }}" class="text-label-sm text-primary hover:underline shrink-0">View Results</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    @endif

</x-layouts.counselor>
