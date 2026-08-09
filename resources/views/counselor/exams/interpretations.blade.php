<x-layouts.counselor title="Interpretation Ranges" active="exams">

    <x-navigation.header
        title="Interpretation Ranges"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.builder', $exam)],
                ['label' => 'Interpretation'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.results', $exam)" variant="outline" icon="grade">Results</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ $errors->first() }}</x-ui.alert>
    @endif

    <p class="text-body-md text-on-surface-variant mb-lg">
        Define score bands that label each student's result (e.g. 90–100 = "Highly Qualified"). These labels appear on results and the student's summary. They are reference points, not clinical diagnoses.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Existing ranges --}}
        <div class="lg:col-span-2">
            <x-ui.card :padded="false">
                <x-ui.table :headers="['Label', 'Range', 'Description', '']">
                    @forelse ($exam->interpretationRanges as $range)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-lg py-md font-label-md text-on-surface">{{ $range->label }}</td>
                            <td class="px-lg py-md text-label-md text-on-surface-variant">
                                {{ rtrim(rtrim(number_format($range->min_percentage, 2), '0'), '.') }}% – {{ rtrim(rtrim(number_format($range->max_percentage, 2), '0'), '.') }}%
                            </td>
                            <td class="px-lg py-md text-label-md text-outline">{{ $range->description ?? '—' }}</td>
                            <td class="px-lg py-md text-right">
                                <form method="POST" action="{{ route('counselor.exams.interpretations.destroy', [$exam, $range]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-outline-variant hover:text-error transition-colors" title="Remove">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state icon="straighten" title="No ranges yet" description="Add a band on the right. Without ranges, results just show Passed / Failed." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        {{-- Add form --}}
        <div>
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Add Range</h3>
                <form method="POST" action="{{ route('counselor.exams.interpretations.store', $exam) }}" class="space-y-md">
                    @csrf
                    <x-ui.input label="Label" name="label" placeholder="e.g. Highly Qualified" value="{{ old('label') }}" required />
                    <div class="grid grid-cols-2 gap-md">
                        <x-ui.input label="Min %" name="min_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('min_percentage') }}" required />
                        <x-ui.input label="Max %" name="max_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('max_percentage') }}" required />
                    </div>
                    <x-ui.input label="Description" name="description" placeholder="Optional note" value="{{ old('description') }}" />
                    <x-ui.button type="submit" icon="add" class="w-full">Add Range</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>

</x-layouts.counselor>
