@php use App\Enums\ExamStatus; @endphp

<x-layouts.counselor title="Exam Builder" active="exams">

    <x-navigation.header
        title="Build Examination"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.edit', $exam)],
                ['label' => 'Builder'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.settings', $exam)" variant="outline" icon="tune">Settings</x-ui.button>
            <x-ui.button :href="route('counselor.exams.preview', $exam)" variant="outline" icon="visibility">Preview</x-ui.button>
            <x-ui.button :href="route('counselor.exams.publish', $exam)" icon="publish">Publish</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif
    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    <input type="hidden" id="reorder-token" value="{{ csrf_token() }}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
        {{-- LEFT: Sections --}}
        <div class="lg:col-span-3 space-y-lg">
            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Sections</h3>
                </div>
                <div class="p-lg space-y-sm">
                    @forelse ($exam->sections as $section)
                        @php $count = $exam->examQuestions->where('exam_section_id', $section->id)->count(); @endphp
                        <div class="flex items-center justify-between px-md py-sm rounded-lg bg-surface-container-low">
                            <div>
                                <p class="font-label-md text-on-surface">{{ $section->title }}</p>
                                <p class="text-label-sm text-outline">{{ $count }} question{{ $count === 1 ? '' : 's' }}</p>
                            </div>
                            <form method="POST" action="{{ route('counselor.exams.builder.sections.destroy', [$exam, $section]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1 text-outline-variant hover:text-error transition-colors" title="Remove section">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-label-sm text-outline">No sections yet. Sections are optional groupings.</p>
                    @endforelse
                </div>
                <div class="p-lg border-t border-outline-variant">
                    {{-- Stacked, not side-by-side: this column is only ~3/12 of the
                         grid, and a text input's default min-width doesn't shrink
                         well in a flex row, which was pushing the Add button
                         completely out of view at this width. --}}
                    <form method="POST" action="{{ route('counselor.exams.builder.sections.store', $exam) }}" class="space-y-sm">
                        @csrf
                        <input type="text" name="title" placeholder="New section title" autocomplete="off" required
                               class="w-full h-11 bg-surface-container-lowest border border-outline-variant rounded-lg px-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all">
                        <x-ui.button type="submit" variant="secondary" size="sm" icon="add" class="w-full">Add Section</x-ui.button>
                    </form>
                    @error('title') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
                </div>
            </x-ui.card>
        </div>

        {{-- CENTER: Questions --}}
        <div class="lg:col-span-6 space-y-lg">
            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant flex items-center justify-between">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Questions ({{ $exam->examQuestions->count() }})</h3>
                    <div class="flex items-center gap-sm">
                        @if ($exam->examQuestions->count() > 1)
                            <x-ui.button type="button" variant="secondary" size="sm" icon="save" onclick="saveOrder()">Save Order</x-ui.button>
                        @endif
                        <x-ui.button :href="route('counselor.exams.builder.select', $exam)" size="sm" icon="library_add">Add from Bank</x-ui.button>
                    </div>
                </div>

                @if ($exam->examQuestions->isEmpty())
                    <div class="p-xl">
                        <x-ui.empty-state
                            icon="quiz"
                            title="No questions yet"
                            description="Add questions from the Question Bank to start building this exam."
                        >
                            <x-slot:action>
                                <x-ui.button :href="route('counselor.exams.builder.select', $exam)" icon="library_add">Add from Bank</x-ui.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    </div>
                @else
                    <div id="question-list" class="divide-y divide-outline-variant">
                        @foreach ($exam->examQuestions as $eq)
                            @php
                                $q = $eq->question;
                                $points = rtrim(rtrim(number_format($eq->marks_override ?? $q->marks, 2), '0'), '.');
                            @endphp
                            <div class="eq-row flex items-start gap-md p-lg hover:bg-primary/5 transition-colors" data-eqid="{{ $eq->id }}">
                                <span class="drag-handle cursor-grab text-outline-variant hover:text-on-surface pt-1" title="Drag to reorder">
                                    <span class="material-symbols-outlined">drag_indicator</span>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-sm mb-xs">
                                        <span class="inline-flex items-center gap-xs text-label-sm text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[16px]">{{ $q->type->icon() }}</span>
                                            {{ $q->type->label() }}
                                        </span>
                                        @if ($eq->examSection)
                                            <x-ui.badge variant="primary">{{ $eq->examSection->title }}</x-ui.badge>
                                        @endif
                                        <span class="ml-auto text-label-sm text-outline">{{ $points }} pt{{ $points == 1 ? '' : 's' }}</span>
                                    </div>
                                    <p class="text-body-md text-on-surface line-clamp-2">{{ $q->question_text }}</p>
                                </div>
                                <form method="POST" action="{{ route('counselor.exams.builder.detach', [$exam, $eq]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-error transition-colors" title="Remove">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- RIGHT: Summary + Access Code --}}
        <div class="lg:col-span-3 space-y-lg">
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Summary</h3>
                <dl class="space-y-md">
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Total Questions</dt>
                        <dd class="font-headline-md text-on-surface">{{ $summary['total_questions'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Total Points</dt>
                        <dd class="font-headline-md text-on-surface">{{ rtrim(rtrim(number_format($summary['total_points'], 2), '0'), '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-label-md text-on-surface-variant">Duration</dt>
                        <dd class="font-label-md text-on-surface">{{ $summary['duration'] }} min</dd>
                    </div>
                </dl>

                @if ($summary['breakdown']->isNotEmpty())
                    <div class="mt-lg pt-lg border-t border-outline-variant">
                        <p class="text-label-sm text-outline mb-sm uppercase tracking-wider">By Type</p>
                        @foreach ($summary['breakdown'] as $label => $count)
                            <div class="flex items-center justify-between py-xs">
                                <span class="text-label-md text-on-surface-variant">{{ $label }}</span>
                                <span class="font-label-md text-on-surface">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-md">Access Code</h3>
                @if ($exam->access_code)
                    <div class="flex items-center justify-between bg-surface-container-low rounded-lg px-md py-sm mb-md">
                        <span class="font-mono text-headline-md text-primary tracking-widest">{{ $exam->access_code }}</span>
                    </div>
                @else
                    <p class="text-label-sm text-outline mb-md">No code yet. One is generated automatically on publish, or generate it now.</p>
                @endif
                <form method="POST" action="{{ route('counselor.exams.access-code', $exam) }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline" size="sm" icon="autorenew" class="w-full">
                        {{ $exam->access_code ? 'Regenerate Code' : 'Generate Code' }}
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        // SortableJS is bundled into resources/js/app.js (window.Sortable),
        // loaded once via @vite in the shared layout head. Its module script
        // is deferred, so it may not have executed yet if this is a hard
        // page load landing directly here (vs. navigating in via Turbo) —
        // `turbo:load` fires after every page is fully ready, including the
        // first one, which is a reliable signal either way. The dataset
        // guard just prevents a double-init if this ever fires twice for
        // the same DOM node.
        function initExamBuilderSortable() {
            const list = document.getElementById('question-list');
            if (list && window.Sortable && !list.dataset.sortableInit) {
                list.dataset.sortableInit = '1';
                Sortable.create(list, { handle: '.drag-handle', animation: 150, ghostClass: 'opacity-50' });
            }
        }
        document.addEventListener('turbo:load', initExamBuilderSortable);
    </script>
    <script>
        function saveOrder() {
            const rows = document.querySelectorAll('#question-list .eq-row');
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('counselor.exams.builder.reorder', $exam) }}';

            const token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token';
            token.value = document.getElementById('reorder-token').value;
            form.appendChild(token);

            rows.forEach(row => {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'order[]';
                input.value = row.dataset.eqid;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>

</x-layouts.counselor>
