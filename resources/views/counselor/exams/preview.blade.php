@php
    use App\Enums\QuestionType;

    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Exam Preview" active="exams">

    <x-navigation.header
        title="Examination Preview"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => $isSuperAdmin ? route('counselor.exams.index') : route('counselor.exams.builder', $exam)],
                ['label' => 'Preview'],
            ]" />
        </x-slot:breadcrumb>

        @unless ($isSuperAdmin)
            <x-slot:actions>
                <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="outline" icon="build">Back to Builder</x-ui.button>
            </x-slot:actions>
        @endunless
    </x-navigation.header>

    <x-ui.alert variant="info" icon="visibility" class="mb-lg">
        This is a read-only preview of how students will see the exam. Inputs are disabled.
    </x-ui.alert>

    {{-- Mock exam header --}}
    <x-ui.card class="mb-lg">
        <div class="flex flex-wrap items-center justify-between gap-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ $exam->title }}</h2>
                <p class="text-label-md text-on-surface-variant">{{ $summary['total_questions'] }} questions · {{ rtrim(rtrim(number_format($summary['total_points'], 2), '0'), '.') }} points</p>
            </div>
            <div class="flex items-center gap-sm px-lg py-sm rounded-lg bg-primary/10 text-primary">
                <span class="material-symbols-outlined">timer</span>
                <span class="font-headline-md tracking-wider">
                    {{ $exam->duration_minutes !== null ? sprintf('%02d:%02d:00', intdiv($exam->duration_minutes, 60), $exam->duration_minutes % 60) : 'No limit' }}
                </span>
            </div>
        </div>
        <div class="mt-md w-full h-2 bg-surface-container rounded-full overflow-hidden">
            <div class="h-full bg-primary" style="width: 0%"></div>
        </div>
    </x-ui.card>

    @forelse ($exam->examQuestions as $i => $eq)
        @php $q = $eq->question; @endphp
        <x-ui.card class="mb-lg">
            <div class="flex items-start gap-md mb-md">
                <span class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold shrink-0">{{ $i + 1 }}</span>
                <p class="text-body-lg text-on-surface pt-1">{{ $q->question_text }}</p>
            </div>

            <div class="pl-11 space-y-sm">
                @if ($q->type === QuestionType::ShortAnswer)
                    <textarea rows="3" disabled placeholder="Student writes their answer here..." class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-3 text-body-md resize-none"></textarea>
                @elseif ($q->type === QuestionType::Likert)
                    @foreach ($q->options as $option)
                        <label class="flex items-center gap-md px-lg py-md border border-outline-variant rounded-lg opacity-90">
                            <input type="radio" disabled class="w-5 h-5">
                            <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                @else
                    @foreach ($q->options as $option)
                        <label class="flex items-center gap-md px-lg py-md border border-outline-variant rounded-lg opacity-90">
                            <input type="radio" disabled class="w-5 h-5">
                            <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                @endif
            </div>
        </x-ui.card>
    @empty
        <x-ui.card>
            <x-ui.empty-state icon="quiz" title="No questions to preview" :description="$isSuperAdmin ? 'This examination has no questions yet.' : 'Add questions in the builder first.'">
                @unless ($isSuperAdmin)
                    <x-slot:action>
                        <x-ui.button :href="route('counselor.exams.builder', $exam)" icon="build">Go to Builder</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-ui.empty-state>
        </x-ui.card>
    @endforelse

    @if ($exam->examQuestions->isNotEmpty())
        <div class="flex items-center justify-between mt-xl">
            <x-ui.button variant="outline" icon="chevron_left" disabled>Previous</x-ui.button>
            <x-ui.button variant="danger-solid" icon="send" disabled>Submit (preview)</x-ui.button>
            <x-ui.button icon="chevron_right" disabled>Next</x-ui.button>
        </div>
    @endif

</x-dynamic-component>
