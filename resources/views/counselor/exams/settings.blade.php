<x-layouts.counselor title="Exam Settings" active="exams">

    <x-navigation.header
        title="Examination Settings"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.builder', $exam)],
                ['label' => 'Settings'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="outline" icon="build">Back to Builder</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.exams.settings.update', $exam) }}" class="space-y-xl">
            @csrf @method('PATCH')

            <div class="max-w-xs">
                <x-ui.input
                    label="Passing Score (%)"
                    name="passing_score"
                    type="number" min="0" max="100" step="0.01"
                    value="{{ old('passing_score', $exam->passing_score) }}"
                    hint="Minimum percentage to be marked as passed."
                    required
                />
            </div>

            <div class="pt-lg border-t border-outline-variant space-y-lg">
                @php
                    $toggles = [
                        ['shuffle_questions', 'Shuffle Questions', 'Randomize the order of questions per student.'],
                        ['shuffle_choices', 'Shuffle Choices', 'Randomize the order of choices within each question.'],
                        ['auto_submit', 'Auto-submit on Time Expiry', 'Automatically submit when the timer reaches zero.'],
                        ['show_score', 'Show Score After Submission', 'Let students see their score once they finish.'],
                        ['show_correct_answers', 'Show Correct Answers', 'Reveal the correct answers after submission.'],
                        ['allow_resume', 'Allow Resume', 'Let students resume if disconnected before the timer ends.'],
                    ];
                @endphp

                @foreach ($toggles as [$field, $label, $desc])
                    <div class="flex items-center justify-between gap-lg">
                        <div class="flex-1">
                            <p class="font-label-md text-on-surface">{{ $label }}</p>
                            <p class="text-label-sm text-outline">{{ $desc }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" class="sr-only peer"
                                   @checked(old($field, $exam->$field)) />
                            <div class="w-11 h-6 bg-surface-variant rounded-full peer peer-checked:bg-primary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end pt-lg border-t border-outline-variant">
                <x-ui.button type="submit" icon="check">Save Settings</x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.counselor>
