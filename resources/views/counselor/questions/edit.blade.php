<x-layouts.counselor title="Edit Question" active="questions">

    <x-navigation.header
        title="Edit Question"
        subtitle="Update this question and its answer options."
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Question Bank', 'href' => route('counselor.questions.index')],
                ['label' => 'Edit Question'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.questions.show', $question)" variant="outline" icon="visibility">Preview</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.questions.update', $question) }}" class="space-y-xl">
            @csrf
            @method('PUT')

            @include('counselor.questions._form', ['question' => $question])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.questions.index')" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save Changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.counselor>
