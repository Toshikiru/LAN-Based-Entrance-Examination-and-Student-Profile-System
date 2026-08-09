@php
    use App\Enums\QuestionType;

    $isEdit = isset($question);
    $opts = $isEdit ? $question->options : collect();

    // Build the initial Alpine state, preferring old() input on validation failure.
    $initChoices = old('choices', $isEdit && $question->type === QuestionType::MultipleChoice
        ? $opts->pluck('option_text')->all() : ['', '']);
    if (count($initChoices) < 2) {
        $initChoices = array_pad($initChoices, 2, '');
    }

    $initCorrect = old('correct_choice', $isEdit && $question->type === QuestionType::MultipleChoice
        ? max(0, $opts->values()->search(fn ($o) => $o->is_correct)) : 0);

    $initTf = old('tf_answer', $isEdit && $question->type === QuestionType::TrueFalse
        ? ($opts->firstWhere('is_correct', true)?->option_text === 'True' ? 'true' : 'false') : 'true');

    if (old('scale_text')) {
        $initScale = collect(old('scale_text'))->map(fn ($t, $i) => [
            'text' => $t, 'value' => (int) (old('scale_value')[$i] ?? 0),
        ])->values()->all();
    } elseif ($isEdit && $question->type === QuestionType::Likert) {
        $initScale = $opts->map(fn ($o) => ['text' => $o->option_text, 'value' => (int) $o->value])->values()->all();
    } else {
        $initScale = [
            ['text' => 'Strongly Agree', 'value' => 5],
            ['text' => 'Agree', 'value' => 4],
            ['text' => 'Neutral', 'value' => 3],
            ['text' => 'Disagree', 'value' => 2],
            ['text' => 'Strongly Disagree', 'value' => 1],
        ];
    }

    $initial = [
        'type' => old('type', $isEdit ? $question->type->value : QuestionType::MultipleChoice->value),
        'choices' => array_values($initChoices),
        'correctChoice' => (int) $initCorrect,
        'tfAnswer' => $initTf,
        'scale' => $initScale,
    ];
@endphp

<div x-data="questionForm(@js($initial))" class="space-y-xl">
    {{-- Question details --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
        <div class="md:col-span-2">
            <label for="question_text" class="block font-label-md text-label-md text-on-surface-variant mb-xs">Question</label>
            <textarea
                id="question_text"
                name="question_text"
                rows="3"
                autocomplete="off"
                placeholder="Type the question here..."
                @class([
                    'w-full bg-surface-container-lowest border rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none',
                    'border-error focus:border-error focus:ring-4 focus:ring-error/10' => $errors->has('question_text'),
                    'border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10' => ! $errors->has('question_text'),
                ])
            >{{ old('question_text', $isEdit ? $question->question_text : '') }}</textarea>
            @error('question_text') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        <x-ui.select label="Question Type" name="type" x-model="type">
            @foreach (\App\Enums\QuestionType::cases() as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.input
            label="Category"
            name="section_category"
            list="question-categories"
            placeholder="e.g. Quantitative Reasoning"
            value="{{ old('section_category', $isEdit ? $question->section_category : '') }}"
            hint="Pick an existing category or type a new one."
            required
        />
        <datalist id="question-categories">
            @foreach ($categories as $c)
                <option value="{{ $c }}"></option>
            @endforeach
        </datalist>

        <x-ui.select label="Difficulty" name="difficulty">
            @php $selDiff = old('difficulty', $isEdit ? $question->difficulty->value : 'medium'); @endphp
            @foreach (\App\Enums\QuestionDifficulty::cases() as $d)
                <option value="{{ $d->value }}" @selected($selDiff === $d->value)>{{ ucfirst($d->value) }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.select label="Status" name="status">
            @php $selStatus = old('status', $isEdit ? $question->status->value : 'active'); @endphp
            @foreach (\App\Enums\QuestionStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected($selStatus === $s->value)>{{ ucfirst($s->value) }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.input
            label="Points (marks)"
            name="marks"
            type="number" min="0" max="100" step="0.25"
            placeholder="e.g. 1"
            value="{{ old('marks', $isEdit ? $question->marks : 1) }}"
            required
        />

        <x-ui.input
            label="Negative Marks"
            name="negative_marks"
            type="number" min="0" max="100" step="0.25"
            placeholder="0"
            value="{{ old('negative_marks', $isEdit ? $question->negative_marks : 0) }}"
            hint="Deducted for a wrong answer. Leave 0 for none."
        />
    </div>

    {{-- Answer editor (varies by type) --}}
    <div class="pt-lg border-t border-outline-variant">
        {{-- Multiple Choice --}}
        <div x-show="type === 'multiple_choice'" x-cloak>
            <div class="flex items-center justify-between mb-md">
                <h4 class="font-headline-md text-headline-md text-on-surface">Choices</h4>
                <x-ui.button type="button" variant="secondary" size="sm" icon="add" x-on:click="addChoice()">Add Choice</x-ui.button>
            </div>
            <p class="text-label-sm text-outline mb-md">Select the radio button next to the correct answer.</p>

            <template x-for="(choice, index) in choices" :key="index">
                <div class="flex items-center gap-md mb-sm">
                    <input type="radio" name="correct_choice" :value="index" x-model.number="correctChoice" class="w-5 h-5 text-primary focus:ring-primary shrink-0" />
                    <input type="text" name="choices[]" x-model="choices[index]" placeholder="Choice text" autocomplete="off" class="flex-1 h-12 bg-surface-container-lowest border border-outline-variant rounded-lg px-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
                    <button type="button" x-on:click="removeChoice(index)" x-show="choices.length > 2" class="p-2 text-outline-variant hover:text-error transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </div>
            </template>

            @error('choices') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
            @error('correct_choice') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        {{-- True / False --}}
        <div x-show="type === 'true_false'" x-cloak>
            <h4 class="font-headline-md text-headline-md text-on-surface mb-md">Correct Answer</h4>
            <div class="flex gap-md">
                <label class="flex-1 flex items-center gap-md px-lg py-md border rounded-lg cursor-pointer transition-all" :class="tfAnswer === 'true' ? 'border-primary bg-primary/5' : 'border-outline-variant'">
                    <input type="radio" name="tf_answer" value="true" x-model="tfAnswer" class="w-5 h-5 text-primary focus:ring-primary" />
                    <span class="font-label-md text-on-surface">True</span>
                </label>
                <label class="flex-1 flex items-center gap-md px-lg py-md border rounded-lg cursor-pointer transition-all" :class="tfAnswer === 'false' ? 'border-primary bg-primary/5' : 'border-outline-variant'">
                    <input type="radio" name="tf_answer" value="false" x-model="tfAnswer" class="w-5 h-5 text-primary focus:ring-primary" />
                    <span class="font-label-md text-on-surface">False</span>
                </label>
            </div>
            @error('tf_answer') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        {{-- Likert Scale --}}
        <div x-show="type === 'likert'" x-cloak>
            <div class="flex items-center justify-between mb-md">
                <h4 class="font-headline-md text-headline-md text-on-surface">Scale Options</h4>
                <x-ui.button type="button" variant="secondary" size="sm" icon="add" x-on:click="addScale()">Add Option</x-ui.button>
            </div>
            <p class="text-label-sm text-outline mb-md">Each option carries a numeric value used for automatic scoring. No answer is "correct".</p>

            <template x-for="(row, index) in scale" :key="index">
                <div class="flex items-center gap-md mb-sm">
                    <input type="text" name="scale_text[]" x-model="scale[index].text" placeholder="Option label (e.g. Strongly Agree)" autocomplete="off" class="flex-1 h-12 bg-surface-container-lowest border border-outline-variant rounded-lg px-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
                    <input type="number" name="scale_value[]" x-model.number="scale[index].value" min="0" max="100" placeholder="Value" autocomplete="off" class="w-24 h-12 bg-surface-container-lowest border border-outline-variant rounded-lg px-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" />
                    <button type="button" x-on:click="removeScale(index)" x-show="scale.length > 2" class="p-2 text-outline-variant hover:text-error transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </div>
            </template>

            @error('scale_text') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
            @error('scale_value') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        {{-- Short Answer --}}
        <div x-show="type === 'short_answer'" x-cloak>
            <x-ui.alert variant="info" icon="info">
                Short Answer questions have no predefined options and are graded manually by the guidance counselor after submission.
            </x-ui.alert>
        </div>
    </div>
</div>

@once
    <script>
        (function () {
            function registerQuestionForm() {
                Alpine.data('questionForm', (initial) => ({
                    type: initial.type,
                    choices: initial.choices,
                    correctChoice: initial.correctChoice,
                    tfAnswer: initial.tfAnswer,
                    scale: initial.scale,

                    addChoice() { this.choices.push(''); },
                    removeChoice(i) {
                        this.choices.splice(i, 1);
                        if (this.correctChoice === i) { this.correctChoice = 0; }
                        else if (this.correctChoice > i) { this.correctChoice--; }
                    },
                    addScale() { this.scale.push({ text: '', value: 0 }); },
                    removeScale(i) { this.scale.splice(i, 1); },
                }));
            }

            // `alpine:init` only ever fires once per browser tab (whenever
            // Alpine first boots). Under Turbo, this script re-runs on every
            // Turbo visit to this page, but if Alpine already booted earlier
            // in the session (i.e. this isn't the very first page loaded),
            // that event has already fired and would never fire again — so
            // register immediately in that case instead of waiting for it.
            if (window.Alpine) {
                registerQuestionForm();
            } else {
                document.addEventListener('alpine:init', registerQuestionForm, { once: true });
            }
        })();
    </script>
@endonce
