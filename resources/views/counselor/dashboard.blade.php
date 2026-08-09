@use('App\Enums\ExamSessionStatus')

<x-layouts.counselor title="Dashboard" active="dashboard">

    <x-navigation.header
        variant="hero"
        title="Good morning, {{ auth()->user()->name }}"
        subtitle="There {{ $stats['live_session_count'] === 1 ? 'is' : 'are' }} {{ $stats['live_session_count'] }} active exam session{{ $stats['live_session_count'] === 1 ? '' : 's' }} currently running. All systems are operational."
    >
        <x-slot:actions>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-md border border-white/10 min-w-[160px]">
                <p class="text-label-sm uppercase tracking-widest text-primary-fixed mb-xs">Next Session</p>
                @if ($upcomingExams->isNotEmpty())
                    <p class="font-headline-md text-headline-md">{{ $upcomingExams->first()->starts_at->format('h:i A') }}</p>
                    <p class="text-sm opacity-80">{{ $upcomingExams->first()->title }}</p>
                @else
                    <p class="font-headline-md text-headline-md">—</p>
                    <p class="text-sm opacity-80">No sessions scheduled</p>
                @endif
            </div>
        </x-slot:actions>
    </x-navigation.header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <x-ui.stat-card icon="group" label="Students" value="{{ number_format($stats['total_students']) }}" variant="primary" />
        <x-ui.stat-card icon="edit_note" label="Active Exams" value="{{ $stats['active_exams'] }}" variant="secondary" />
        <x-ui.stat-card icon="rate_review" label="Pending Grading" value="{{ $stats['pending_grading'] }}" variant="tertiary" />
        <x-ui.stat-card icon="task_alt" label="Completed Exams" value="{{ number_format($stats['completed_exams']) }}" variant="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Main Column --}}
        <div class="lg:col-span-2 space-y-xl">
            {{-- Live Examination Sessions --}}
            <div class="space-y-md">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md">Live Examination Sessions</h3>
                    <a href="{{ route('counselor.monitoring.index') }}" class="text-primary font-label-md text-label-md flex items-center gap-xs hover:underline">
                        View All Active Sessions
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>

                <x-ui.table :headers="['Student', 'Progress', 'Status', '']">
                    @forelse ($liveSessions as $row)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-4">
                                <p class="font-label-md text-label-md text-on-surface">{{ $row['name'] }}</p>
                                <p class="text-label-sm text-outline">ID: {{ $row['school_id'] }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-full max-w-[140px]">
                                    <div class="flex justify-between text-[11px] mb-1 font-bold">
                                        <span class="text-on-surface">{{ $row['progress'] }}%</span>
                                        <span class="text-outline">Q {{ $row['answered'] }}/{{ $row['total'] }}</span>
                                    </div>
                                    <div class="h-2 w-full bg-surface-container-highest rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full" style="width: {{ $row['progress'] }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if ($row['status'] === ExamSessionStatus::Flagged)
                                    <x-ui.badge variant="warning" dot>Flagged</x-ui.badge>
                                @else
                                    <x-ui.badge variant="secondary" dot>Active</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('counselor.exams.monitor', $row['exam']) }}" class="text-primary font-label-md text-label-md hover:underline">View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4">
                                <x-ui.empty-state
                                    icon="visibility"
                                    title="No active exam sessions"
                                    description="Once students begin an exam, their live progress will appear in this table."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </div>

            {{-- Upcoming Examinations --}}
            <div class="space-y-md">
                <h3 class="font-headline-md text-headline-md">Upcoming Examinations</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    @forelse ($upcomingExams as $exam)
                        <a href="{{ route('counselor.exams.monitor', $exam) }}" class="flex items-center gap-md bg-surface-container-lowest border border-outline-variant rounded-xl p-md hover:shadow-md transition-shadow">
                            <div class="flex flex-col items-center justify-center min-w-[56px] h-14 bg-primary/5 rounded-lg text-primary shrink-0">
                                <span class="text-lg font-bold leading-none">{{ $exam->starts_at->format('d') }}</span>
                                <span class="text-[10px] font-bold uppercase">{{ $exam->starts_at->format('M') }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-label-md text-label-md text-on-surface truncate">{{ $exam->title }}</p>
                                <p class="text-label-sm text-outline flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    {{ $exam->starts_at->format('h:i A') }} &middot; {{ $exam->department?->name ?? 'All departments' }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="sm:col-span-2">
                            <x-ui.card>
                                <x-ui.empty-state icon="event_upcoming" title="No upcoming examinations" description="Published exams with a future start date will appear here." />
                            </x-ui.card>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Pending Manual Grading --}}
            <div class="space-y-md">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md">Pending Manual Grading</h3>
                </div>

                <x-ui.table :headers="['Student', 'Examination', 'Submitted', 'Ungraded', '']">
                    @forelse ($pendingGrading as $session)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-4 text-on-surface">{{ $session->student?->name ?? '—' }}</td>
                            <td class="px-4 py-4 text-on-surface-variant">{{ $session->exam?->title ?? '—' }}</td>
                            <td class="px-4 py-4 text-on-surface-variant">{{ $session->submitted_at?->format('M d, h:i A') ?? '—' }}</td>
                            <td class="px-4 py-4"><x-ui.badge variant="warning">{{ $session->ungraded_count }} item{{ $session->ungraded_count === 1 ? '' : 's' }}</x-ui.badge></td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('counselor.exams.grading.session', [$session->exam, $session]) }}" class="text-primary font-label-md text-label-md hover:underline">Grade Now</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4">
                                <x-ui.empty-state
                                    icon="fact_check"
                                    title="Nothing awaiting grading"
                                    description="Short-answer responses that need manual grading will appear here."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </div>
        </div>

        {{-- Right Sidebar Column --}}
        <div class="space-y-xl">
            {{-- Recent Student Activity --}}
            <x-ui.card title="Recent Student Activity">
                <div class="space-y-lg relative">
                    @if ($recentStudentActivity->isNotEmpty())
                        <div class="absolute left-[19px] top-2 bottom-2 w-px bg-outline-variant"></div>
                    @endif

                    @forelse ($recentStudentActivity as $entry)
                        @php
                            $isSubmit = $entry['type'] === 'submitted';
                        @endphp
                        <div class="relative flex gap-lg z-10">
                            <div class="w-10 h-10 rounded-full {{ $isSubmit ? 'bg-secondary-fixed text-on-secondary-fixed-variant' : 'bg-primary-fixed text-primary' }} flex items-center justify-center ring-4 ring-surface-container-lowest shrink-0">
                                <span class="material-symbols-outlined text-[18px]">{{ $isSubmit ? 'task_alt' : 'play_circle' }}</span>
                            </div>
                            <div>
                                <p class="font-label-md text-label-md text-on-surface">{{ $entry['student']?->name ?? 'A student' }}</p>
                                <p class="text-sm text-outline-variant mb-1 font-medium">{{ $isSubmit ? 'Submitted' : 'Started' }} {{ $entry['exam']?->title ?? 'an examination' }}</p>
                                <p class="text-[11px] text-outline">{{ $entry['at']->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state
                            icon="school"
                            title="No student activity yet"
                            description="Exam starts and submissions will appear here as they happen."
                        />
                    @endforelse
                </div>
            </x-ui.card>

            {{-- Quick Actions --}}
            <x-ui.card title="Quick Actions">
                <div class="space-y-md">
                    <a href="{{ route('counselor.exams.create') }}" class="w-full flex items-center gap-md p-md bg-primary-fixed hover:bg-primary-fixed-dim transition-colors rounded-xl group">
                        <div class="w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined">add_task</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-primary-fixed font-bold leading-tight">Create Exam</p>
                            <p class="text-xs text-on-primary-fixed-variant opacity-80">Set up a new exam</p>
                        </div>
                    </a>
                    <a href="{{ route('counselor.questions.import.create') }}" class="w-full flex items-center gap-md p-md bg-secondary-fixed hover:bg-secondary-fixed-dim transition-colors rounded-xl group">
                        <div class="w-10 h-10 rounded-lg bg-secondary text-on-secondary flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined">upload_file</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-secondary-fixed font-bold leading-tight">Import Questions</p>
                            <p class="text-xs text-on-secondary-fixed-variant opacity-80">TXT, DOCX, or PDF</p>
                        </div>
                    </a>
                    <a href="{{ route('counselor.results.index') }}" class="w-full flex items-center gap-md p-md bg-surface-container hover:bg-surface-container-high transition-colors rounded-xl group">
                        <div class="w-10 h-10 rounded-lg bg-on-surface-variant text-surface-container-lowest flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined">bar_chart_4_bars</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-bold leading-tight">View Results</p>
                            <p class="text-xs text-outline">Analytics &amp; trends</p>
                        </div>
                    </a>
                </div>
            </x-ui.card>

            {{-- Recent Audit Activity --}}
            <x-ui.card title="Recent Audit Activity">
                @if ($recentActivity->isEmpty())
                    <x-ui.empty-state
                        icon="history_edu"
                        title="No recent activity"
                        description="Actions you take will appear here."
                    />
                @else
                    <div class="space-y-lg relative">
                        <div class="absolute left-[19px] top-2 bottom-2 w-px bg-outline-variant"></div>
                        @foreach ($recentActivity as $log)
                            @php
                                $isDestructive = str_contains($log->action, 'deleted')
                                    || str_contains($log->action, 'archived')
                                    || str_contains($log->action, 'terminated')
                                    || str_contains($log->action, 'deactivated');
                                $isMilestone = str_contains($log->action, 'activated')
                                    || str_contains($log->action, 'published')
                                    || str_contains($log->action, 'graded')
                                    || str_contains($log->action, 'restored');
                                $isCreated = str_contains($log->action, 'created') || str_contains($log->action, 'imported');

                                [$iconBg, $iconColor, $icon] = match (true) {
                                    $isDestructive => ['bg-error-container', 'text-on-error-container', 'remove_circle'],
                                    $isMilestone => ['bg-secondary-fixed', 'text-on-secondary-fixed-variant', 'verified'],
                                    $isCreated => ['bg-primary-fixed', 'text-primary', 'person_add'],
                                    default => ['bg-tertiary-fixed', 'text-on-tertiary-fixed-variant', 'edit'],
                                };
                            @endphp
                            <div class="relative flex gap-lg z-10">
                                <div class="w-10 h-10 rounded-full {{ $iconBg }} flex items-center justify-center {{ $iconColor }} ring-4 ring-surface-container-lowest shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md text-on-surface">{{ $log->user?->name ?? 'System' }}</p>
                                    <p class="text-sm text-outline-variant mb-1 font-medium">{{ $log->description }}</p>
                                    <p class="text-[11px] text-outline">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

</x-layouts.counselor>
