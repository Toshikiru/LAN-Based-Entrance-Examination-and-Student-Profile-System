@php use App\Enums\QuestionType; @endphp

<x-layouts.counselor title="Review Import" active="questions">

    <x-navigation.header
        title="Review Imported Questions"
        :subtitle="$fileName"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Question Bank', 'href' => route('counselor.questions.index')],
                ['label' => 'Import', 'href' => route('counselor.questions.import.create')],
                ['label' => 'Review'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    {{-- Summary --}}
    <div class="flex flex-wrap items-center gap-lg mb-lg">
        <span class="inline-flex items-center gap-xs font-label-md text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">quiz</span>
            {{ count($questions) }} detected
        </span>
        <span class="inline-flex items-center gap-xs font-label-md text-secondary">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            {{ $validCount }} ready
        </span>
        @if ($errorCount)
            <span class="inline-flex items-center gap-xs font-label-md text-error">
                <span class="material-symbols-outlined text-[18px]">error</span>
                {{ $errorCount }} with errors
            </span>
        @endif
    </div>

    <form method="POST" action="{{ route('counselor.questions.import.store') }}"
          x-data="{ all: true, toggleAll() { document.querySelectorAll('.q-check').forEach(c => c.checked = this.all); } }">
        @csrf
        <textarea name="source_text" class="hidden">{{ $sourceText }}</textarea>

        <div class="flex items-center justify-between mb-md">
            <label class="inline-flex items-center gap-sm text-label-md text-on-surface-variant cursor-pointer">
                <input type="checkbox" x-model="all" x-on:change="toggleAll()" class="w-5 h-5 rounded text-primary focus:ring-primary">
                Select all valid
            </label>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
            @foreach ($questions as $q)
                @php $type = QuestionType::tryFrom($q['type']); @endphp
                <x-ui.card class="border-l-4 {{ $q['valid'] ? 'border-l-secondary' : 'border-l-error' }}">
                    <div class="flex items-start justify-between gap-md mb-sm">
                        <div class="flex items-center gap-sm">
                            @if ($q['valid'])
                                <input type="checkbox" name="selected[]" value="{{ $q['number'] }}" checked class="q-check w-5 h-5 rounded text-primary focus:ring-primary">
                            @endif
                            <span class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">
                                Q{{ str_pad($q['number'], 2, '0', STR_PAD_LEFT) }} · {{ $type?->label() ?? 'Unknown' }}
                            </span>
                        </div>
                        @if ($q['valid'])
                            <x-ui.badge variant="success">Ready</x-ui.badge>
                        @else
                            <x-ui.badge variant="error">Has errors</x-ui.badge>
                        @endif
                    </div>

                    <p class="font-body-md text-body-md text-on-surface font-semibold mb-md">{{ $q['question'] ?: '(no question text)' }}</p>

                    @if ($type === QuestionType::ShortAnswer)
                        <p class="text-label-sm text-outline italic">Open-ended · graded manually.</p>
                    @elseif (count($q['choices']))
                        <div class="space-y-xs">
                            @foreach ($q['choices'] as $choice)
                                <div class="flex items-center gap-sm text-body-md {{ $choice['correct'] ? 'text-on-surface' : 'text-on-surface-variant' }}">
                                    <span class="material-symbols-outlined text-[18px] {{ $choice['correct'] ? 'text-secondary' : 'text-outline-variant' }}">
                                        {{ $choice['correct'] ? 'check_circle' : 'radio_button_unchecked' }}
                                    </span>
                                    <span>{{ $choice['text'] }}</span>
                                    @if (! is_null($choice['value']))
                                        <span class="ml-auto text-label-sm text-outline">= {{ $choice['value'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! $q['valid'])
                        <div class="mt-md p-sm bg-error-container/40 rounded-lg">
                            <ul class="text-label-sm text-error space-y-xs">
                                @foreach ($q['errors'] as $error)
                                    <li class="flex items-center gap-xs"><span class="material-symbols-outlined text-[14px]">warning</span>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>

        <div class="sticky bottom-md mt-xl py-md px-lg bg-surface-container-lowest border border-outline-variant shadow-xl rounded-2xl flex flex-wrap items-center justify-between gap-md">
            <p class="text-label-md text-on-surface-variant">
                Only questions marked <span class="text-secondary font-semibold">Ready</span> will be imported. Fix the rest in your file and re-upload.
            </p>
            <div class="flex items-center gap-md">
                <x-ui.button :href="route('counselor.questions.index')" variant="ghost">Cancel</x-ui.button>
                <x-ui.button :href="route('counselor.questions.import.create')" variant="outline" icon="refresh">Re-upload</x-ui.button>
                <x-ui.button type="submit" icon="save" :disabled="$validCount === 0">
                    Import {{ $validCount }} Question{{ $validCount === 1 ? '' : 's' }}
                </x-ui.button>
            </div>
        </div>
    </form>

</x-layouts.counselor>
