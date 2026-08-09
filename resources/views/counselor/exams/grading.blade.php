<x-layouts.counselor title="Manual Grading" active="exams">

    <x-navigation.header
        title="Manual Grading"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => route('counselor.exams.builder', $exam)],
                ['label' => 'Grading'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.results', $exam)" variant="outline" icon="grade">Results</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card :padded="false">
        <div class="p-lg border-b border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface">Awaiting Short-Answer Grading</h3>
        </div>
        @forelse ($sessions as $session)
            <div class="flex items-center justify-between p-lg border-b border-outline-variant last:border-b-0">
                <div>
                    <p class="font-label-md text-on-surface">{{ $session->student?->name }}</p>
                    <p class="text-label-sm text-outline font-mono">{{ $session->student?->school_id }}</p>
                </div>
                <x-ui.button :href="route('counselor.exams.grading.session', [$exam, $session])" size="sm" icon="edit_note">Grade</x-ui.button>
            </div>
        @empty
            <div class="p-xl">
                <x-ui.empty-state icon="task_alt" title="Nothing to grade" description="All short-answer responses for this exam have been graded." />
            </div>
        @endforelse
    </x-ui.card>

</x-layouts.counselor>
