<x-layouts.student title="My Results" active="results">

    <x-navigation.header
        title="My Results"
        subtitle="Your completed examinations and scores."
    />

    <x-ui.card :padded="false">
        <x-ui.table :headers="['Examination', 'Submitted', 'Score', 'Result', '']">
            @forelse ($sessions as $session)
                @php
                    $exam = $session->exam;
                    $result = $session->result;
                @endphp
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-4">
                        <p class="font-label-md text-label-md text-on-surface">{{ $exam?->title ?? 'Untitled Examination' }}</p>
                    </td>
                    <td class="px-4 py-4 text-on-surface-variant">{{ $session->submitted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    <td class="px-4 py-4">
                        @if ($exam?->show_score && $result)
                            <span class="font-bold text-on-surface">{{ rtrim(rtrim(number_format($result->total_score, 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($result->max_score, 2), '0'), '.') }}</span>
                            <span class="text-outline"> ({{ rtrim(rtrim(number_format($result->percentage, 2), '0'), '.') }}%)</span>
                        @else
                            <span class="text-outline">Pending release</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        @if ($exam?->show_score && $result)
                            <x-ui.badge :variant="$result->passed ? 'success' : 'error'">{{ $result->passed ? 'Passed' : 'Failed' }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">Submitted</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-right">
                        @if ($exam)
                            <a href="{{ route('student.exams.result', $exam) }}" class="text-primary font-label-md text-label-md hover:underline">View Details</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-4">
                        <x-ui.empty-state
                            icon="grade"
                            title="No results yet"
                            description="Once you submit an examination, your results will appear here."
                        />
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$sessions->currentPage()"
                    :total-pages="$sessions->lastPage()"
                    :total-label="'Showing ' . $sessions->firstItem() . '-' . $sessions->lastItem() . ' of ' . $sessions->total() . ' results'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

</x-layouts.student>
