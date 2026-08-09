@php
    use App\Enums\QuestionType;
    $diffVariant = match ($question->difficulty->value) {
        'easy' => 'success', 'medium' => 'warning', 'hard' => 'error', default => 'neutral',
    };

    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Question Preview" active="questions">

    <x-navigation.header
        title="Question Preview"
        :subtitle="$question->type->label() . ' · ' . $question->section_category"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Question Bank', 'href' => route('counselor.questions.index')],
                ['label' => 'Preview'],
            ]" />
        </x-slot:breadcrumb>

        @unless ($isSuperAdmin)
            <x-slot:actions>
                <x-ui.button :href="route('counselor.questions.edit', $question)" icon="edit">Edit</x-ui.button>
            </x-slot:actions>
        @endunless
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Preview --}}
        <div class="lg:col-span-2">
            <x-ui.card>
                <p class="text-body-lg text-on-surface mb-lg">{{ $question->question_text }}</p>

                @if ($question->type === QuestionType::MultipleChoice)
                    <div class="space-y-sm">
                        @foreach ($question->options as $option)
                            <div class="flex items-center gap-md px-lg py-md border rounded-lg {{ $option->is_correct ? 'border-secondary bg-secondary/5' : 'border-outline-variant' }}">
                                <span class="material-symbols-outlined text-[20px] {{ $option->is_correct ? 'text-secondary' : 'text-outline-variant' }}">
                                    {{ $option->is_correct ? 'check_circle' : 'radio_button_unchecked' }}
                                </span>
                                <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                                @if ($option->is_correct)
                                    <x-ui.badge variant="success" class="ml-auto">Correct</x-ui.badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($question->type === QuestionType::TrueFalse)
                    <div class="flex gap-md">
                        @foreach ($question->options as $option)
                            <div class="flex-1 flex items-center gap-md px-lg py-md border rounded-lg {{ $option->is_correct ? 'border-secondary bg-secondary/5' : 'border-outline-variant' }}">
                                <span class="material-symbols-outlined text-[20px] {{ $option->is_correct ? 'text-secondary' : 'text-outline-variant' }}">
                                    {{ $option->is_correct ? 'check_circle' : 'radio_button_unchecked' }}
                                </span>
                                <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif ($question->type === QuestionType::Likert)
                    <div class="space-y-sm">
                        @foreach ($question->options as $option)
                            <div class="flex items-center gap-md px-lg py-md border border-outline-variant rounded-lg">
                                <span class="material-symbols-outlined text-[20px] text-outline-variant">radio_button_unchecked</span>
                                <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                                <span class="ml-auto text-label-sm text-outline">Value: {{ $option->value }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <textarea rows="4" disabled placeholder="Student's written answer will appear here (graded manually)..." class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-3 text-body-md resize-none"></textarea>
                @endif
            </x-ui.card>
        </div>

        {{-- Meta --}}
        <div>
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Details</h3>
                <dl class="space-y-md">
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Type</dt>
                        <dd class="font-label-md text-on-surface">{{ $question->type->label() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Category</dt>
                        <dd class="font-label-md text-on-surface">{{ $question->section_category ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Difficulty</dt>
                        <dd><x-ui.badge :variant="$diffVariant">{{ ucfirst($question->difficulty->value) }}</x-ui.badge></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Points</dt>
                        <dd class="font-label-md text-on-surface">{{ rtrim(rtrim(number_format($question->marks, 2), '0'), '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Negative Marks</dt>
                        <dd class="font-label-md text-on-surface">{{ rtrim(rtrim(number_format($question->negative_marks, 2), '0'), '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Status</dt>
                        <dd><x-ui.badge :variant="$question->status->value === 'active' ? 'success' : 'neutral'" dot>{{ ucfirst($question->status->value) }}</x-ui.badge></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Auto-scored</dt>
                        <dd class="font-label-md text-on-surface">{{ $question->type->isAutoScored() ? 'Yes' : 'No (manual)' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>

</x-dynamic-component>
