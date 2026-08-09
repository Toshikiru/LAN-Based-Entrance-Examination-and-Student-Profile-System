<x-layouts.counselor title="Register New Student" active="students">

    <x-navigation.header
        title="Register New Student"
        :subtitle="'Fill out the administrative profile to enroll a new student into ' . $branding['system_name'] . '.'"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Student Directory', 'href' => route('counselor.students.index')],
                ['label' => 'Register New Student'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <x-ui.card>
        <form method="POST" action="{{ route('counselor.students.store') }}" class="space-y-xl">
            @csrf

            @include('counselor.students._form', ['student' => null])

            <div class="flex items-center justify-between pt-lg border-t border-outline-variant">
                <x-ui.button :href="route('counselor.students.index')" variant="ghost">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check">
                    Save Student
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

</x-layouts.counselor>
