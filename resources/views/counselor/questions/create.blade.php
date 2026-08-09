<x-layouts.counselor title="Add Question" active="questions">

    <x-navigation.header
        title="Add Question"
        subtitle="Create a new question for the bank."
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Question Bank', 'href' => route('counselor.questions.index')],
                ['label' => 'Add Question'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.questions.store') }}" class="space-y-xl">
            @csrf

            @include('counselor.questions._form', ['question' => null])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.questions.index')" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save Question</x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.counselor>
