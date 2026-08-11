<x-layouts.student title="Search">

    <x-navigation.header
        title="Search Results"
        :subtitle="$query !== '' ? 'Results for &quot;' . $query . '&quot;' : 'Type something in the header search bar to get started.'"
    />

    @if ($query === '')
        <x-ui.empty-state icon="search" title="Start typing to search" description="Search your available exams and completed results from the header search bar." />
    @else
        <div class="space-y-lg">
            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Exams</h3>
                    <a href="{{ route('student.exams.join') }}" class="text-label-sm text-primary hover:underline">Join with Access Code</a>
                </div>

                @if ($exams->isEmpty())
                    <div class="p-lg">
                        <x-ui.empty-state icon="quiz" title="No matching exams" description="Nothing available or upcoming matches that title." />
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($exams as $exam)
                            <div class="flex items-center justify-between gap-md p-lg">
                                <div class="min-w-0">
                                    <p class="font-label-md text-label-md text-on-surface truncate">{{ $exam->title }}</p>
                                    <p class="text-label-sm text-outline truncate">
                                        {{ $exam->starts_at && $exam->starts_at->isFuture() ? 'Opens ' . $exam->starts_at->format('M d, Y h:i A') : 'Ready to start' }}
                                    </p>
                                </div>
                                <x-ui.badge variant="primary">{{ $exam->duration_minutes ? $exam->duration_minutes . ' min' : 'No limit' }}</x-ui.badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Results</h3>
                    <a href="{{ route('student.results.index') }}" class="text-label-sm text-primary hover:underline">View all Results</a>
                </div>

                @if ($sessions->isEmpty())
                    <div class="p-lg">
                        <x-ui.empty-state icon="grade" title="No matching results" description="Try a different examination title." />
                    </div>
                @else
                    <div class="divide-y divide-outline-variant">
                        @foreach ($sessions as $session)
                            @php $result = $session->result; @endphp
                            <a href="{{ route('student.exams.result', $session->exam) }}" class="flex items-center justify-between gap-md p-lg hover:bg-primary/5 transition-colors">
                                <div class="min-w-0">
                                    <p class="font-label-md text-label-md text-on-surface truncate">{{ $session->exam->title }}</p>
                                    <p class="text-label-sm text-outline truncate">{{ $session->submitted_at?->format('M d, Y h:i A') ?? '—' }}</p>
                                </div>
                                @if ($session->exam->show_score && $result)
                                    <x-ui.badge :variant="$result->passed ? 'success' : 'error'">{{ $result->passed ? 'Passed' : 'Failed' }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral">Submitted</x-ui.badge>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    @endif

</x-layouts.student>
