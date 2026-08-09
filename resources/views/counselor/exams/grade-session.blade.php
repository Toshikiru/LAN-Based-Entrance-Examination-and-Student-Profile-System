<x-layouts.counselor title="Grade Submission" active="exams">

    <x-navigation.header
        title="Grade Short Answers"
        :subtitle="$session->student?->name . ' · ' . $exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => 'Grading', 'href' => route('counselor.exams.grading', $exam)],
                ['label' => $session->student?->name ?? 'Submission'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <form method="POST" action="{{ route('counselor.exams.grading.store', [$exam, $session]) }}" class="space-y-lg">
        @csrf

        @forelse ($answers as $answer)
            @php $max = $points->get($answer->question_id, 0); @endphp
            <x-ui.card>
                <div class="flex items-start justify-between gap-md mb-md">
                    <p class="text-body-lg text-on-surface">{{ $answer->question?->question_text }}</p>
                    <span class="text-label-sm text-outline whitespace-nowrap">Max {{ rtrim(rtrim(number_format($max, 2), '0'), '.') }} pt</span>
                </div>

                <div class="bg-surface-container-low rounded-lg p-md mb-md">
                    <p class="text-label-sm text-outline uppercase tracking-wider mb-xs">Student's Answer</p>
                    <p class="text-body-md text-on-surface whitespace-pre-line">{{ $answer->answer_text ?: '(no answer submitted)' }}</p>
                </div>

                <div class="max-w-[200px]">
                    <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Marks awarded</label>
                    <input type="number" name="marks[{{ $answer->id }}]" min="0" max="{{ $max }}" step="0.25" autocomplete="off"
                           value="{{ old('marks.' . $answer->id, $answer->awarded_marks !== null ? rtrim(rtrim(number_format($answer->awarded_marks, 2), '0'), '.') : '') }}"
                           class="w-full h-12 bg-surface-container-lowest border border-outline-variant rounded-lg px-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all">
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty-state icon="task_alt" title="No short-answer items" description="This submission has nothing to grade manually." />
            </x-ui.card>
        @endforelse

        @if ($answers->isNotEmpty())
            <div class="flex items-center justify-between">
                <x-ui.button :href="route('counselor.exams.grading', $exam)" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save Grades &amp; Finalize</x-ui.button>
            </div>
        @endif
    </form>

</x-layouts.counselor>
