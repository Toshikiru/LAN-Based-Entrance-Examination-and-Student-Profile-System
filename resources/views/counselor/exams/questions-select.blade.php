<x-layouts.counselor title="Add Questions" active="exams">

    <x-navigation.header
        title="Add Questions from Bank"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.edit', $exam)],
                ['label' => 'Builder', 'href' => route('counselor.exams.builder', $exam)],
                ['label' => 'Add Questions'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    @if ($errors->any())
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ $errors->first() }}</x-ui.alert>
    @endif

    {{-- Filters (GET) --}}
    <form method="GET" action="{{ route('counselor.exams.builder.select', $exam) }}" class="flex flex-wrap items-center gap-md mb-lg">
        <x-ui.search-bar name="search" :value="$filters['search'] ?? ''" placeholder="Search questions..." rounded="lg" class="min-w-[240px] flex-1 max-w-sm" />
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
        <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>
    </form>

    {{-- Selection (POST) --}}
    <form method="POST" action="{{ route('counselor.exams.builder.attach', $exam) }}"
          x-data="{ count: 0, tally() { this.count = this.$el.querySelectorAll('.q-pick:checked').length; } }"
          x-init="tally()" x-on:change="tally()">
        @csrf

        <x-ui.card :padded="false">
            <div class="p-lg border-b border-outline-variant flex flex-wrap items-center justify-between gap-md">
                <label class="inline-flex items-center gap-sm text-label-md text-on-surface-variant cursor-pointer">
                    <input type="checkbox" class="w-5 h-5 rounded text-primary focus:ring-primary"
                           x-on:change="$root.querySelectorAll('.q-pick').forEach(c => c.checked = $el.checked); tally()">
                    Select all on this page
                </label>

                @if ($sections->isNotEmpty())
                    <div class="flex items-center gap-sm">
                        <span class="text-label-sm text-on-surface-variant">Add to section:</span>
                        <x-ui.select name="exam_section_id" placeholder="No section">
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->title }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                @endif
            </div>

            <x-ui.table :headers="['', 'Question', 'Category', 'Type', 'Difficulty', 'Points']">
                @forelse ($questions as $q)
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="px-lg py-md">
                            <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" class="q-pick w-5 h-5 rounded text-primary focus:ring-primary">
                        </td>
                        <td class="px-lg py-md max-w-md"><p class="text-body-md text-on-surface line-clamp-2">{{ $q->question_text }}</p></td>
                        <td class="px-lg py-md"><span class="inline-flex items-center px-2 py-1 rounded bg-primary/10 text-primary font-label-sm text-label-sm">{{ $q->section_category ?? '—' }}</span></td>
                        <td class="px-lg py-md whitespace-nowrap text-label-md text-on-surface-variant">{{ $q->type->label() }}</td>
                        <td class="px-lg py-md text-label-md text-on-surface-variant">{{ ucfirst($q->difficulty->value) }}</td>
                        <td class="px-lg py-md text-label-md text-on-surface">{{ rtrim(rtrim(number_format($q->marks, 2), '0'), '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-ui.empty-state icon="quiz" title="No questions available" description="Every active question is already in this exam, or none match your filters." />
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

        <div class="flex items-center justify-end gap-md mt-lg">
            <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="ghost">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="add" x-bind:disabled="count === 0" x-bind:class="count === 0 ? 'opacity-50 pointer-events-none' : ''">
                Add <span x-text="count"></span> Selected
            </x-ui.button>
        </div>
    </form>

</x-layouts.counselor>
