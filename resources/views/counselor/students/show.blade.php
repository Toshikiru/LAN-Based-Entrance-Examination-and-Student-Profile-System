@php
    $isArchived = $student->trashed();
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Student Profile" active="students">

    <x-ui.breadcrumb class="mb-lg" :items="[
        ['label' => 'Student Directory', 'href' => route('counselor.students.index')],
        ['label' => $student->name],
    ]" />

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($isArchived)
        <x-ui.alert variant="warning" class="mb-lg" icon="archive">
            This profile is archived. Restore it to make edits or re-enable exam access.
        </x-ui.alert>
    @endif

    {{-- Profile Header --}}
    <section class="mb-xl bg-surface-container-lowest rounded-2xl p-lg border border-outline-variant shadow-sm flex flex-col md:flex-row md:items-end justify-between gap-lg">
        <div class="flex items-center gap-xl">
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-2xl bg-primary-container text-on-primary flex items-center justify-center text-4xl font-bold border-4 border-surface shadow-md shrink-0">
                {{ strtoupper(substr($student->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-md mb-xs flex-wrap">
                    <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $student->name }}</h2>
                    @if ($isArchived)
                        <x-ui.badge variant="neutral" dot>Archived</x-ui.badge>
                    @elseif ($student->status === \App\Enums\UserStatus::Active)
                        <x-ui.badge variant="success" dot>Active</x-ui.badge>
                    @elseif ($student->status === \App\Enums\UserStatus::Suspended)
                        <x-ui.badge variant="error" dot>Suspended</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning" dot>Inactive</x-ui.badge>
                    @endif
                </div>
                <div class="flex flex-wrap gap-md text-on-surface-variant font-body-md text-body-md">
                    <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[18px]">fingerprint</span> ID: {{ $student->school_id }}</span>
                    @if ($student->studentProfile?->year_level)
                        <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[18px]">school</span> {{ $student->studentProfile->year_level }}@if($student->studentProfile->section) &middot; {{ $student->studentProfile->section }}@endif</span>
                    @endif
                    @if ($student->department)
                        <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[18px]">location_on</span> {{ $student->department->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-md">
            <x-ui.button :href="route('counselor.students.record.print', $student)" variant="outline" icon="print" target="_blank">
                Print Cumulative Record
            </x-ui.button>
            @if (! $isArchived)
                <x-ui.button variant="outline" icon="lock_reset" @click="$dispatch('open-modal-reset-{{ $student->id }}')">
                    Reset Password
                </x-ui.button>
            @endif
            @if (! $isArchived && ! $isSuperAdmin)
                <x-ui.button :href="route('counselor.students.edit', $student)" variant="primary" icon="edit">
                    Edit Profile
                </x-ui.button>
            @endif
        </div>
    </section>

    {{-- Reset Password modal — available to both Counselor and Super Admin;
         see the comment on the shared route in routes/counselor.php. --}}
    @if (! $isArchived)
        <x-ui.modal name="reset-{{ $student->id }}" title="Reset Password" max-width="sm">
            <form method="POST" action="{{ route('counselor.students.reset-password', $student) }}" class="space-y-lg">
                @csrf
                @method('PATCH')

                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    Set a temporary password for <span class="font-bold text-on-surface">{{ $student->name }}</span>.
                    They'll be required to change it the next time they log in.
                </p>

                <x-ui.input label="Temporary Password" name="password" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                <x-ui.input label="Confirm Password" name="password_confirmation" type="password" placeholder="Re-enter password" autocomplete="new-password" required />

                <div class="flex gap-md pt-sm">
                    <x-ui.button type="button" variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="lock_reset" class="flex-1">Reset Password</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif

    {{-- Tabs --}}
    <div x-data="{ tab: '{{ in_array($activeTab, ['personal', 'exam-history', 'assessments', 'counseling'], true) ? $activeTab : 'personal' }}' }">
        <nav class="flex gap-xl border-b border-outline-variant mb-lg px-md overflow-x-auto">
            <button type="button" @click="tab = 'personal'" :class="tab === 'personal' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md whitespace-nowrap transition-colors">Personal Information</button>
            <button type="button" @click="tab = 'exam-history'" :class="tab === 'exam-history' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md whitespace-nowrap transition-colors">Examination History</button>
            <button type="button" @click="tab = 'assessments'" :class="tab === 'assessments' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md whitespace-nowrap transition-colors">Assessment Results</button>
            <button type="button" @click="tab = 'counseling'" :class="tab === 'counseling' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md whitespace-nowrap transition-colors">Counseling Notes</button>
        </nav>

        {{-- Personal Information --}}
        <div x-show="tab === 'personal'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
            <div class="lg:col-span-8 space-y-lg">
                <x-ui.card title="Basic Information">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg">
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">Email Address</p>
                            <p class="font-body-md text-body-md font-medium">{{ $student->email ?? 'Not provided' }}</p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">School ID</p>
                            <p class="font-body-md text-body-md font-medium">{{ $student->school_id }}</p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">Department</p>
                            <p class="font-body-md text-body-md font-medium">{{ $student->department?->name ?? 'Not assigned' }}</p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">Enrollment Status</p>
                            <p class="font-body-md text-body-md font-medium">{{ ucfirst(str_replace('_', ' ', $student->studentProfile?->enrollment_status ?? 'enrolled')) }}</p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">Year Level</p>
                            <p class="font-body-md text-body-md font-medium">{{ $student->studentProfile?->year_level ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wide">Section</p>
                            <p class="font-body-md text-body-md font-medium">{{ $student->studentProfile?->section ?? '—' }}</p>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Performance Overview: real counts, sourced from actual exam sessions/results --}}
                <div class="bg-primary text-on-primary rounded-2xl p-lg shadow-xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="font-headline-md text-headline-md mb-lg">Performance Overview</h3>
                        <div class="grid grid-cols-2 gap-xl">
                            <div class="text-center">
                                <p class="text-on-primary-container font-label-sm text-label-sm uppercase tracking-widest mb-xs">Exams Taken</p>
                                <p class="text-[36px] font-extrabold leading-none">{{ $assessment['total_exams'] }}</p>
                            </div>
                            <div class="text-center border-l border-on-primary/20">
                                <p class="text-on-primary-container font-label-sm text-label-sm uppercase tracking-widest mb-xs">Average Score</p>
                                <p class="text-[36px] font-extrabold leading-none">{{ $assessment['total_exams'] > 0 ? $assessment['average_percentage'] . '%' : '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Activity: real data from the audit log --}}
            <div class="lg:col-span-4">
                <x-ui.card title="Recent Activity" :padded="false">
                    <div class="p-lg">
                        @forelse ($recentActivity as $entry)
                            <div class="relative flex gap-md pb-lg last:pb-0">
                                <div class="z-10 w-10 h-10 rounded-full bg-surface-container flex items-center justify-center border-4 border-surface-container-lowest shadow-sm shrink-0">
                                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">history</span>
                                </div>
                                <div>
                                    <p class="font-body-md text-body-md font-bold text-on-surface">{{ $entry->description }}</p>
                                    <p class="text-on-surface-variant font-label-sm text-label-sm">{{ $entry->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state
                                icon="history"
                                title="No activity yet"
                                description="Changes to this profile will show up here."
                            />
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Examination History --}}
        <div x-show="tab === 'exam-history'" x-cloak>
            <x-ui.table :headers="['Examination', 'Date Taken', 'Score', 'Status', 'Interpretation']">
                @forelse ($examHistory as $row)
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-4 py-4">
                            <p class="font-body-md text-body-md font-bold text-on-surface">{{ $row['title'] }}</p>
                            <p class="text-label-sm text-outline">{{ $row['exam']?->category ? ucfirst($row['exam']->category->value) : '—' }}</p>
                        </td>
                        <td class="px-4 py-4 text-on-surface-variant">{{ $row['date']?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-4">
                            @if ($row['percentage'] !== null)
                                <div class="flex items-center gap-sm">
                                    <span class="font-bold text-on-surface">{{ rtrim(rtrim(number_format($row['score'], 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($row['max_score'], 2), '0'), '.') }}</span>
                                    <div class="w-16 h-1.5 bg-surface-container-highest rounded-full overflow-hidden">
                                        <div class="h-full {{ $row['passed'] ? 'bg-secondary' : 'bg-error' }}" style="width: {{ $row['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-outline">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @if ($row['passed'] === true)
                                <x-ui.badge variant="success">Passed</x-ui.badge>
                            @elseif ($row['passed'] === false)
                                <x-ui.badge variant="error">Failed</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">{{ ucfirst(str_replace('_', ' ', $row['status']->value)) }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-on-surface-variant">{{ $row['interpretation'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4">
                            <x-ui.empty-state
                                icon="history_edu"
                                title="No examination history"
                                description="Exam attempts for this student will appear here once they take an exam."
                            />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </div>

        {{-- Assessment Results --}}
        <div x-show="tab === 'assessments'" x-cloak class="space-y-lg">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-lg">
                <x-ui.stat-card icon="quiz" label="Total Exams" :value="$assessment['total_exams']" variant="primary" />
                <x-ui.stat-card icon="analytics" label="Average Score" :value="$assessment['total_exams'] > 0 ? $assessment['average_percentage'] . '%' : '—'" variant="secondary" />
                <x-ui.stat-card icon="stars" label="Highest Score" :value="$assessment['highest'] ? round($assessment['highest']['percentage'], 1) . '%' : '—'" variant="tertiary" />
            </div>

            @forelse ($assessment['breakdowns'] as $breakdown)
                <x-ui.card :title="$breakdown['exam']?->title" :subtitle="($breakdown['session']->submitted_at?->format('M d, Y') ?? '—') . ' · ' . round($breakdown['percentage'], 1) . '% overall'">
                    <div class="space-y-lg">
                        @forelse ($breakdown['sections'] as $section)
                            <div>
                                <div class="flex justify-between mb-sm">
                                    <span class="font-label-md font-bold text-on-surface">{{ $section['label'] }}</span>
                                    <span class="font-label-md text-primary">{{ rtrim(rtrim(number_format($section['score'], 2), '0'), '.') }}/{{ rtrim(rtrim(number_format($section['max'], 2), '0'), '.') }}</span>
                                </div>
                                <div class="w-full h-3 bg-surface-container rounded-full overflow-hidden">
                                    <div class="h-full bg-primary rounded-full" style="width: {{ $section['percentage'] }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-on-surface-variant text-body-md">No sectioned questions in this exam.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card>
                    <x-ui.empty-state
                        icon="psychology"
                        title="No assessment results yet"
                        description="Section-by-section score breakdowns will appear here once the student completes a scored exam."
                    />
                </x-ui.card>
            @endforelse
        </div>

        {{-- Counseling Notes --}}
        <div x-show="tab === 'counseling'" x-cloak>
            <div class="flex items-center justify-between mb-lg">
                <p class="text-on-surface-variant font-body-md text-body-md">Visible only to Guidance Counselors and the Super Administrator.</p>
                @unless ($isSuperAdmin)
                    <x-ui.button type="button" icon="add" @click="$dispatch('open-modal-add-counseling-note')">
                        Add Note
                    </x-ui.button>
                @endunless
            </div>

            <div class="space-y-md">
                @forelse ($counselingNotes as $note)
                    @php
                        $categoryColors = [
                            'academic' => 'primary',
                            'behavioral' => 'error',
                            'career' => 'tertiary',
                            'personal' => 'secondary',
                        ];
                    @endphp
                    <x-ui.card :padded="true">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-md mb-md">
                            <div class="flex items-center gap-sm flex-wrap">
                                <x-ui.badge :variant="$categoryColors[$note->category->value] ?? 'neutral'">{{ $note->category->label() }}</x-ui.badge>
                                <x-ui.badge :variant="$note->status->value === 'open' ? 'warning' : 'neutral'" dot>{{ $note->status->label() }}</x-ui.badge>
                                <span class="text-label-sm text-outline">{{ $note->note_date->format('M d, Y') }}</span>
                            </div>
                            @unless ($isSuperAdmin)
                                <button type="button" class="text-primary font-label-md text-label-md flex items-center gap-xs hover:underline shrink-0" @click="$dispatch('open-modal-edit-counseling-note-{{ $note->id }}')">
                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                    Edit
                                </button>
                            @endunless
                        </div>

                        <p class="font-body-md text-body-md text-on-surface whitespace-pre-line mb-md">{{ $note->content }}</p>

                        @if ($note->follow_up_action)
                            <div class="mb-md p-md bg-surface-container-low rounded-lg">
                                <p class="text-label-sm text-outline uppercase tracking-wider mb-xs">Follow-up Action</p>
                                <p class="text-body-md text-on-surface whitespace-pre-line">{{ $note->follow_up_action }}</p>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-lg text-label-sm text-outline border-t border-outline-variant pt-md">
                            <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">badge</span> {{ $note->counselor?->name ?? 'Unknown counselor' }}</span>
                            @if ($note->follow_up_date)
                                <span class="flex items-center gap-xs"><span class="material-symbols-outlined text-[16px]">event</span> Follow-up: {{ $note->follow_up_date->format('M d, Y') }}</span>
                            @endif
                        </div>

                        @if ($note->revisions->isNotEmpty())
                            <details class="mt-md">
                                <summary class="text-label-sm text-primary cursor-pointer hover:underline">Edit history ({{ $note->revisions->count() }})</summary>
                                <div class="mt-sm space-y-sm">
                                    @foreach ($note->revisions as $revision)
                                        <div class="p-md bg-surface-container-low rounded-lg text-label-sm">
                                            <p class="text-outline mb-xs">Prior version, saved by {{ $revision->editor?->name ?? 'Unknown' }} on {{ $revision->created_at->format('M d, Y h:i A') }}</p>
                                            <p class="text-on-surface-variant"><strong>{{ $revision->category->label() }}</strong> · {{ $revision->status->label() }} · {{ $revision->note_date->format('M d, Y') }}</p>
                                            <p class="text-on-surface mt-xs whitespace-pre-line">{{ $revision->content }}</p>
                                            @if ($revision->follow_up_action)
                                                <p class="text-outline mt-xs"><strong>Follow-up:</strong> <span class="whitespace-pre-line">{{ $revision->follow_up_action }}</span></p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </x-ui.card>

                    @unless ($isSuperAdmin)
                        @include('counselor.students.partials._counseling-note-form', [
                            'modalName' => 'edit-counseling-note-' . $note->id,
                            'student' => $student,
                            'note' => $note,
                        ])
                    @endunless
                @empty
                    <x-ui.card>
                        <x-ui.empty-state
                            icon="chat"
                            title="No counseling notes yet"
                            :description="$isSuperAdmin ? 'No guidance activity has been recorded for this student yet.' : 'Add a note to start this student\'s confidential guidance and counseling record.'"
                        >
                            @unless ($isSuperAdmin)
                                <x-slot:action>
                                    <x-ui.button type="button" icon="add" @click="$dispatch('open-modal-add-counseling-note')">Add Note</x-ui.button>
                                </x-slot:action>
                            @endunless
                        </x-ui.empty-state>
                    </x-ui.card>
                @endforelse
            </div>
        </div>
    </div>

    @unless ($isSuperAdmin)
    @include('counselor.students.partials._counseling-note-form', [
        'modalName' => 'add-counseling-note',
        'student' => $student,
        'note' => null,
    ])
    @endunless

</x-dynamic-component>
