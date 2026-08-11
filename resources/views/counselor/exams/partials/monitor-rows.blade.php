@use('App\Enums\ExamSessionStatus')

@forelse ($rows as $row)
    @php
        [$variant, $label] = match ($row['status']) {
            ExamSessionStatus::Completed => ['success', 'Submitted'],
            ExamSessionStatus::InProgress => ['primary', 'In progress'],
            ExamSessionStatus::Flagged => ['tertiary', 'Flagged'],
            ExamSessionStatus::Terminated => ['error', 'Terminated'],
            default => ['neutral', 'Not started'],
        };
        $mmss = $row['remaining'] !== null ? sprintf('%02d:%02d', intdiv($row['remaining'], 60), $row['remaining'] % 60) : null;
        $active = in_array($row['status'], [ExamSessionStatus::InProgress, ExamSessionStatus::Flagged], true);
    @endphp
    <tr class="border-t border-outline-variant">
        <td class="px-lg py-md">
            <p class="font-label-md text-on-surface">{{ $row['name'] }}</p>
            <p class="text-label-sm text-outline font-mono">{{ $row['school_id'] }}</p>
        </td>
        <td class="px-lg py-md">
            <x-ui.badge :variant="$variant" dot :pulse="$row['status'] === ExamSessionStatus::InProgress">{{ $label }}</x-ui.badge>
        </td>
        <td class="px-lg py-md">
            <div class="flex items-center gap-sm">
                <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden min-w-[80px]">
                    <div class="h-full bg-primary" style="width: {{ $row['progress'] }}%"></div>
                </div>
                <span class="text-label-sm text-on-surface-variant w-16 text-right">{{ $row['answered'] }}/{{ $totalQuestions }}</span>
            </div>
        </td>
        <td class="px-lg py-md font-mono text-label-md {{ $mmss ? 'text-on-surface' : 'text-outline' }}">
            {{ $mmss ?? ($active && $exam->duration_minutes === null ? 'No limit' : '—') }}
        </td>
        <td class="px-lg py-md text-label-md text-on-surface-variant">{{ $row['submitted_at']?->format('h:i A') ?? '—' }}</td>
        <td class="px-lg py-md text-right whitespace-nowrap">
            @if ($active && ! auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('counselor.exams.monitor.flag', [$exam, $row['session_id']]) }}" class="inline">
                    @csrf
                    <button type="submit" class="p-2 {{ $row['status'] === ExamSessionStatus::Flagged ? 'text-tertiary' : 'text-outline-variant hover:text-tertiary' }} transition-colors" title="{{ $row['status'] === ExamSessionStatus::Flagged ? 'Unflag' : 'Flag' }}">
                        <span class="material-symbols-outlined text-[20px]">flag</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('counselor.exams.monitor.terminate', [$exam, $row['session_id']]) }}" class="inline"
                      onsubmit="return confirm('Terminate this student\'s session? They will not be able to continue.');">
                    @csrf
                    <button type="submit" class="p-2 text-outline-variant hover:text-error transition-colors" title="Terminate">
                        <span class="material-symbols-outlined text-[20px]">block</span>
                    </button>
                </form>
            @else
                <span class="text-outline">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-lg py-xl text-center text-on-surface-variant">
            No students match — or none have joined this exam yet.
        </td>
    </tr>
@endforelse
