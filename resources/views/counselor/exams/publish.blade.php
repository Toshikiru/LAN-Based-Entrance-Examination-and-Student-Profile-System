@php use App\Enums\ExamStatus; @endphp

<x-layouts.counselor title="Publish Examination" active="exams">

    <x-navigation.header
        title="Publish Examination"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.builder', $exam)],
                ['label' => 'Publish'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="outline" icon="build">Back to Builder</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif
    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($exam->status === ExamStatus::Published)
        <x-ui.alert variant="success" icon="check_circle" class="mb-lg">This examination is published and available to students.</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Summary --}}
        <div class="lg:col-span-2">
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Examination Summary</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                    <div><dt class="text-label-sm text-outline">Course</dt><dd class="font-label-md text-on-surface">{{ $exam->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Year Level</dt><dd class="font-label-md text-on-surface">{{ $exam->year_level ?? '—' }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Duration</dt><dd class="font-label-md text-on-surface">{{ $exam->duration_minutes ? $exam->duration_minutes . ' min' : 'No limit' }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Passing Score</dt><dd class="font-label-md text-on-surface">{{ rtrim(rtrim(number_format($exam->passing_score, 2), '0'), '.') }}%</dd></div>
                    <div><dt class="text-label-sm text-outline">Total Questions</dt><dd class="font-label-md text-on-surface">{{ $summary['total_questions'] }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Total Points</dt><dd class="font-label-md text-on-surface">{{ rtrim(rtrim(number_format($summary['total_points'], 2), '0'), '.') }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Opens</dt><dd class="font-label-md text-on-surface">{{ $exam->starts_at?->format('M d, Y h:i A') ?? 'Not set' }}</dd></div>
                    <div><dt class="text-label-sm text-outline">Closes</dt><dd class="font-label-md text-on-surface">{{ $exam->ends_at?->format('M d, Y h:i A') ?? 'Not set' }}</dd></div>
                </dl>

                @if ($summary['total_questions'] === 0)
                    <x-ui.alert variant="warning" icon="warning" class="mt-lg">Add at least one question in the builder before publishing.</x-ui.alert>
                @endif
            </x-ui.card>
        </div>

        {{-- Access code + actions --}}
        <div class="space-y-lg">
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-md">Access Code</h3>
                @if ($exam->access_code)
                    <div class="bg-surface-container-low rounded-lg px-md py-lg text-center mb-md">
                        <span class="font-mono text-display-lg-mobile text-primary tracking-widest">{{ $exam->access_code }}</span>
                    </div>
                    <p class="text-label-sm text-outline mb-md">Students enter this code to join the exam.</p>
                @else
                    <p class="text-label-sm text-outline mb-md">A code is generated automatically when you publish.</p>
                @endif
                <form method="POST" action="{{ route('counselor.exams.access-code', $exam) }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline" size="sm" icon="autorenew" class="w-full">
                        {{ $exam->access_code ? 'Regenerate Code' : 'Generate Code' }}
                    </x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card>
                <div class="space-y-sm">
                    <form method="POST" action="{{ route('counselor.exams.publish.store', $exam) }}">
                        @csrf
                        <x-ui.button type="submit" icon="publish" class="w-full" :disabled="$summary['total_questions'] === 0">
                            {{ $exam->status === ExamStatus::Published ? 'Re-publish' : 'Publish Now' }}
                        </x-ui.button>
                    </form>
                    <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="outline" class="w-full" icon="edit">Keep Editing</x-ui.button>
                    <x-ui.button :href="route('counselor.exams.index')" variant="ghost" class="w-full">Cancel</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </div>

</x-layouts.counselor>
