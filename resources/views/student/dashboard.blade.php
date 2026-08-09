@php
    $enrollmentStatus = $student->studentProfile?->enrollment_status ?? 'enrolled';
@endphp

<x-layouts.student title="Dashboard" active="dashboard">

    {{-- Welcome Card + Profile Summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mb-xl">
        <div class="lg:col-span-2 relative overflow-hidden rounded-3xl bg-primary p-xl text-on-primary shadow-lg flex flex-col justify-between min-h-[220px]">
            <div class="absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <h2 class="font-headline-lg text-headline-lg mb-sm">Welcome back, {{ $student->name }}.</h2>
                <p class="text-primary-fixed font-body-lg max-w-md">
                    @if ($continuableSession)
                        You have an exam in progress — pick up right where you left off.
                    @elseif ($availableExams->isNotEmpty())
                        You have {{ $availableExams->count() }} examination{{ $availableExams->count() === 1 ? '' : 's' }} available to take right now.
                    @else
                        You're all caught up — no examinations are open right now.
                    @endif
                </p>
            </div>
            <div class="relative z-10 flex flex-wrap gap-md mt-lg">
                @if ($continuableSession)
                    <a href="{{ route('student.exams.take', $continuableSession->exam) }}" class="bg-white text-primary px-lg py-md rounded-xl font-label-md text-label-md hover:bg-primary-fixed transition-colors flex items-center gap-sm shadow-md active:scale-95">
                        <span class="material-symbols-outlined">play_circle</span>
                        Continue Last Exam
                    </a>
                @endif
                <a href="{{ route('student.exams.join') }}" class="border border-white/30 text-white px-lg py-md rounded-xl font-label-md text-label-md hover:bg-white/10 transition-colors">
                    Join an Exam
                </a>
            </div>
        </div>

        <x-ui.card title="Profile Summary">
            <div class="space-y-md">
                <div class="flex items-center gap-md">
                    <div class="w-14 h-14 rounded-full bg-primary-container text-on-primary flex items-center justify-center text-xl font-bold shrink-0">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-label-md text-label-md text-on-surface font-bold">{{ $student->name }}</p>
                        <p class="text-label-sm text-outline">ID: {{ $student->school_id }}</p>
                    </div>
                </div>
                <div class="border-t border-outline-variant pt-md space-y-sm text-label-sm">
                    <div class="flex justify-between">
                        <span class="text-outline">Department</span>
                        <span class="text-on-surface font-medium">{{ $student->department?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-outline">Year Level</span>
                        <span class="text-on-surface font-medium">{{ $student->studentProfile?->year_level ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-outline">Status</span>
                        <x-ui.badge variant="success" dot>{{ ucfirst($enrollmentStatus) }}</x-ui.badge>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Available Examinations + Progress --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-lg mb-xl">
        <div class="lg:col-span-3">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Available Examinations</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                @forelse ($availableExams as $exam)
                    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant border-l-4 border-l-primary p-lg hover:shadow-md transition-all">
                        <div class="flex items-start justify-between mb-md">
                            <div class="bg-surface-container rounded-lg p-sm">
                                <span class="material-symbols-outlined text-primary">quiz</span>
                            </div>
                            <span class="bg-primary/10 text-primary px-sm py-xs rounded text-[10px] font-bold uppercase">Ready to Start</span>
                        </div>
                        <h4 class="font-headline-md text-[18px] text-on-surface mb-xs">{{ $exam->title }}</h4>
                        @if ($exam->description)
                            <p class="text-on-surface-variant text-body-md mb-lg line-clamp-2">{{ $exam->description }}</p>
                        @endif
                        <div class="flex items-center justify-between pt-md border-t border-outline-variant text-outline">
                            <div class="flex items-center gap-md">
                                <span class="flex items-center gap-xs text-label-sm"><span class="material-symbols-outlined text-[18px]">timer</span>{{ $exam->duration_minutes }}m</span>
                                <span class="flex items-center gap-xs text-label-sm"><span class="material-symbols-outlined text-[18px]">list_alt</span>{{ $exam->exam_questions_count }} Questions</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2">
                        <x-ui.card>
                            <x-ui.empty-state icon="quiz" title="No examinations available right now" description="Ask your guidance counselor for an access code once an exam opens." />
                        </x-ui.card>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-1">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Progress Summary</h3>
            <x-ui.card>
                <div class="flex flex-col items-center py-md">
                    <div class="relative w-28 h-28 mb-lg">
                        <svg class="w-full h-full -rotate-90" viewbox="0 0 100 100">
                            <circle class="text-surface-container-high" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor" stroke-width="10"></circle>
                            <circle class="text-primary" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor" stroke-linecap="round" stroke-width="10" stroke-dasharray="251.2" stroke-dashoffset="{{ 251.2 - ($progress['percentage'] / 100 * 251.2) }}"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-on-surface">{{ $progress['percentage'] }}%</span>
                            <span class="text-[10px] text-outline font-label-sm uppercase">Completed</span>
                        </div>
                    </div>
                    <div class="w-full space-y-sm text-label-sm">
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">Completed</span>
                            <span class="text-secondary font-bold">{{ $progress['completed'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">In Progress</span>
                            <span class="text-primary font-bold">{{ $progress['in_progress'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">Total Joined</span>
                            <span class="text-on-surface font-bold">{{ $progress['total_joined'] }}</span>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Upcoming + Notifications + Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mb-xl">
        <div class="lg:col-span-2">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Upcoming Examinations</h3>
            <div class="space-y-sm">
                @forelse ($upcomingExams as $exam)
                    <div class="flex items-center gap-lg bg-surface-container-lowest p-md rounded-xl border border-outline-variant">
                        <div class="flex flex-col items-center justify-center min-w-[60px] h-[60px] bg-primary/5 rounded-xl text-primary shrink-0">
                            <span class="text-lg font-bold leading-none">{{ $exam->starts_at->format('d') }}</span>
                            <span class="text-[10px] font-bold uppercase">{{ $exam->starts_at->format('M') }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-label-md text-on-surface truncate">{{ $exam->title }}</h4>
                            <p class="text-on-surface-variant text-label-sm flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[14px]">schedule</span> {{ $exam->starts_at->format('h:i A') }}
                            </p>
                        </div>
                        <div class="px-md py-sm bg-surface-container rounded-lg text-outline font-label-sm shrink-0">Scheduled</div>
                    </div>
                @empty
                    <x-ui.card>
                        <x-ui.empty-state icon="event_upcoming" title="Nothing scheduled" description="Upcoming examinations for your department and year level will appear here." />
                    </x-ui.card>
                @endforelse
            </div>
        </div>

        <div class="space-y-lg">
            <x-ui.card title="Notifications">
                <div class="space-y-md">
                    @forelse ($notifications as $notification)
                        <div class="flex gap-md">
                            <span class="material-symbols-outlined text-primary">notifications</span>
                            <div>
                                <p class="text-body-md text-on-surface">{{ $notification->data['message'] ?? 'Notification' }}</p>
                                <p class="text-label-sm text-outline">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state icon="notifications" title="No notifications" description="You're all caught up." />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Quick Actions">
                <div class="space-y-sm">
                    <a href="{{ route('student.exams.join') }}" class="w-full flex items-center gap-md p-md bg-surface-container-lowest rounded-xl border border-outline-variant hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-on-primary transition-colors shrink-0">
                            <span class="material-symbols-outlined">login</span>
                        </div>
                        <span class="font-label-md text-on-surface">Join / Resume an Exam</span>
                        <span class="material-symbols-outlined ml-auto text-outline group-hover:text-primary">chevron_right</span>
                    </a>
                    <a href="{{ route('student.results.index') }}" class="w-full flex items-center gap-md p-md bg-surface-container-lowest rounded-xl border border-outline-variant hover:shadow-md transition-all group">
                        <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center group-hover:bg-secondary group-hover:text-on-primary transition-colors shrink-0">
                            <span class="material-symbols-outlined">grade</span>
                        </div>
                        <span class="font-label-md text-on-surface">View My Results</span>
                        <span class="material-symbols-outlined ml-auto text-outline group-hover:text-secondary">chevron_right</span>
                    </a>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Recent Results --}}
    <div>
        <div class="flex items-center justify-between mb-lg">
            <h3 class="font-headline-md text-headline-md text-on-surface">Recent Results</h3>
            <a href="{{ route('student.results.index') }}" class="text-primary font-label-md text-label-md hover:underline">View all</a>
        </div>

        <x-ui.table :headers="['Examination', 'Submitted', 'Score', 'Result']">
            @forelse ($recentResults as $session)
                @php
                    $exam = $session->exam;
                    $result = $session->result;
                @endphp
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-4 font-label-md text-label-md text-on-surface">{{ $exam?->title ?? 'Untitled Examination' }}</td>
                    <td class="px-4 py-4 text-on-surface-variant">{{ $session->submitted_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-4 py-4">
                        @if ($exam?->show_score && $result)
                            <span class="font-bold text-on-surface">{{ rtrim(rtrim(number_format($result->percentage, 2), '0'), '.') }}%</span>
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
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-4">
                        <x-ui.empty-state icon="grade" title="No results yet" description="Completed examinations will show their results here." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

</x-layouts.student>
