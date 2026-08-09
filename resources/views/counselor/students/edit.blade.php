<x-layouts.counselor title="Edit Student" active="students">

    <x-navigation.header
        title="Edit Student"
        :subtitle="$student->name . ' · ' . $student->school_id"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Student Directory', 'href' => route('counselor.students.index')],
                ['label' => $student->name],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.students.show', $student)" variant="outline" icon="visibility">
                View Profile
            </x-ui.button>
            <button type="button" @click="$dispatch('open-modal-archive-{{ $student->id }}')" class="inline-flex items-center gap-sm px-lg py-3 rounded-lg font-label-md text-label-md border border-error text-error hover:bg-error-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">archive</span>
                Archive
            </button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.students.update', $student) }}" class="space-y-xl">
            @csrf
            @method('PUT')

            @include('counselor.students._form', ['student' => $student])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.students.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save Changes
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.modal name="archive-{{ $student->id }}" max-width="sm">
        <div class="flex flex-col items-center text-center">
            <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                <span class="material-symbols-outlined text-error text-[36px]">warning</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Archive Student Profile?</h3>
            <p class="text-body-md text-on-surface-variant leading-relaxed">
                You are about to archive the profile for <span class="font-bold text-on-surface">{{ $student->name }}</span>.
                This will move the student to the archived list. All historical data is preserved, and the profile
                can be restored later.
            </p>
        </div>

        <x-slot:footer>
            <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
            <form method="POST" action="{{ route('counselor.students.archive', $student) }}" class="flex-1">
                @csrf
                @method('PATCH')
                <x-ui.button type="submit" variant="danger-solid" icon="archive" class="w-full">
                    Archive Profile
                </x-ui.button>
            </form>
        </x-slot:footer>
    </x-ui.modal>

</x-layouts.counselor>
