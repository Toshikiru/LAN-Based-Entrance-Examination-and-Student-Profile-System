@php
    $isActive = $exam->status === \App\Enums\ExamStatus::Published;
@endphp

<x-layouts.counselor title="Edit Examination" active="exams">

    <x-navigation.header
        title="Edit Examination"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.builder', $exam)" variant="outline" icon="build">Build</x-ui.button>
            <x-ui.button :href="route('counselor.exams.monitor', $exam)" variant="outline" icon="monitor_heart">Monitor</x-ui.button>
            <x-ui.button :href="route('counselor.exams.results', $exam)" variant="outline" icon="grade">Results</x-ui.button>
            @if ($isActive)
                <form method="POST" action="{{ route('counselor.exams.deactivate', $exam) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="outline" icon="pause_circle">Deactivate</x-ui.button>
                </form>
            @else
                <form method="POST" action="{{ route('counselor.exams.activate', $exam) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit" variant="secondary" icon="play_circle">Activate</x-ui.button>
                </form>
            @endif

            <button type="button" @click="$dispatch('open-modal-delete-{{ $exam->id }}')" class="inline-flex items-center gap-sm px-lg py-3 rounded-lg font-label-md text-label-md border border-error text-error hover:bg-error-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete
            </button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.exams.update', $exam) }}" class="space-y-xl">
            @csrf
            @method('PUT')

            @include('counselor.exams._form', ['exam' => $exam])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.exams.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save Changes
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Delete confirmation --}}
    <x-ui.modal name="delete-{{ $exam->id }}" max-width="sm">
        <div class="flex flex-col items-center text-center">
            <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                <span class="material-symbols-outlined text-error text-[36px]">delete</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Delete Examination?</h3>
            <p class="text-body-md text-on-surface-variant leading-relaxed">
                <span class="font-bold text-on-surface">{{ $exam->title }}</span> will be removed from the list.
            </p>
        </div>

        <x-slot:footer>
            <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
            <form method="POST" action="{{ route('counselor.exams.destroy', $exam) }}" class="flex-1">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger-solid" icon="delete" class="w-full">Delete</x-ui.button>
            </form>
        </x-slot:footer>
    </x-ui.modal>

</x-layouts.counselor>
