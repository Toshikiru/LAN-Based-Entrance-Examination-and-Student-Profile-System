<x-layouts.counselor title="Create Examination" active="exams">

    <x-navigation.header
        title="Create New Entrance Examination"
        subtitle="Configure the exam details, target audience, and schedule."
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => 'Create Examination'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.exams.store') }}" class="space-y-xl">
            @csrf

            @include('counselor.exams._form', ['exam' => null])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.exams.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save as Draft
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.counselor>
