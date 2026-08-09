@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Question Bank" active="questions">

    <x-navigation.header
        title="Question Bank"
        :subtitle="$isSuperAdmin ? 'Browse reusable questions across categories.' : 'Create and manage reusable questions across categories.'"
    >
        @unless ($isSuperAdmin)
            <x-slot:actions>
                <x-ui.button :href="route('counselor.questions.import.create')" variant="outline" icon="upload_file">Import</x-ui.button>
                <x-ui.button :href="route('counselor.questions.create')" icon="add">Add Question</x-ui.button>
            </x-slot:actions>
        @endunless
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <x-ui.stat-card icon="quiz" label="Total Questions" :value="number_format($stats['total'])" variant="primary" />
        <x-ui.stat-card icon="check_circle" label="Active" :value="number_format($stats['active'])" variant="secondary" />
        <x-ui.stat-card icon="edit_note" label="Drafts" :value="number_format($stats['draft'])" variant="tertiary" />
        <x-ui.stat-card icon="category" label="Categories" :value="number_format($stats['categories'])" variant="neutral" />
    </div>

    <x-ui.card :padded="false">
        {{-- Filters --}}
        <form method="GET" action="{{ route('counselor.questions.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search questions..."
                rounded="lg"
                class="min-w-[240px] flex-1 max-w-sm"
            />

            <x-ui.select name="category" placeholder="All Categories" onchange="this.form.submit()">
                @foreach ($categories as $c)
                    <option value="{{ $c }}" @selected(($filters['category'] ?? null) === $c)>{{ $c }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="type" placeholder="All Types" onchange="this.form.submit()">
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected(($filters['type'] ?? null) === $t->value)>{{ $t->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="difficulty" placeholder="All Difficulties" onchange="this.form.submit()">
                @foreach ($difficulties as $d)
                    <option value="{{ $d->value }}" @selected(($filters['difficulty'] ?? null) === $d->value)>{{ ucfirst($d->value) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status" placeholder="All Statuses" onchange="this.form.submit()">
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? null) === $s->value)>{{ ucfirst($s->value) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('counselor.questions.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <x-ui.table :headers="['Question', 'Category', 'Type', 'Difficulty', 'Points', 'Status', '']">
            @forelse ($questions as $question)
                @php
                    $diffVariant = match ($question->difficulty->value) {
                        'easy' => 'success', 'medium' => 'warning', 'hard' => 'error', default => 'neutral',
                    };
                    $statusVariant = $question->status->value === 'active' ? 'success' : 'neutral';
                @endphp
                <tr class="hover:bg-primary/5 transition-colors group">
                    <td class="px-lg py-md max-w-md">
                        <a href="{{ route('counselor.questions.show', $question) }}" class="block">
                            <p class="font-body-md text-body-md text-on-surface line-clamp-2">{{ $question->question_text }}</p>
                        </a>
                    </td>
                    <td class="px-lg py-md">
                        <span class="inline-flex items-center px-2 py-1 rounded bg-primary/10 text-primary font-label-sm text-label-sm">{{ $question->section_category ?? '—' }}</span>
                    </td>
                    <td class="px-lg py-md whitespace-nowrap">
                        <span class="inline-flex items-center gap-xs font-label-md text-label-md text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">{{ $question->type->icon() }}</span>
                            {{ $question->type->label() }}
                        </span>
                    </td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$diffVariant">{{ ucfirst($question->difficulty->value) }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md font-label-md text-label-md text-on-surface">{{ rtrim(rtrim(number_format($question->marks, 2), '0'), '.') }}</td>
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$statusVariant" dot>{{ ucfirst($question->status->value) }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md text-right whitespace-nowrap">
                        <a href="{{ route('counselor.questions.show', $question) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Preview">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </a>
                        @unless ($isSuperAdmin)
                            <a href="{{ route('counselor.questions.edit', $question) }}" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </a>
                            <button type="button" class="p-2 text-outline-variant hover:text-error transition-colors" title="Delete" @click="$dispatch('open-modal-delete-{{ $question->id }}')">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state
                            icon="quiz"
                            title="No questions found"
                            :description="'Try adjusting your search or filters' . ($isSuperAdmin ? '.' : ', or add a new question to get started.')"
                        >
                            @unless ($isSuperAdmin)
                                <x-slot:action>
                                    <x-ui.button :href="route('counselor.questions.create')" icon="add">Add Question</x-ui.button>
                                </x-slot:action>
                            @endunless
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$questions->currentPage()"
                    :total-pages="$questions->lastPage()"
                    :total-label="'Showing ' . $questions->firstItem() . '-' . $questions->lastItem() . ' of ' . $questions->total() . ' questions'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

    {{-- Delete modals --}}
    @unless ($isSuperAdmin)
    @foreach ($questions as $question)
        <x-ui.modal name="delete-{{ $question->id }}" max-width="sm">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-error text-[36px]">delete</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Delete Question?</h3>
                <p class="text-body-md text-on-surface-variant leading-relaxed">This question will be removed from the bank. Exams that already use it keep their copy.</p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('counselor.questions.destroy', $question) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger-solid" icon="delete" class="w-full">Delete</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
    @endunless

</x-dynamic-component>
