{{--
    Shared add/edit form body for a course modal.
    Included with: @include('admin.reference-data.partials._course-form', [
        'modalName' => 'add-course', 'course' => null, 'departments' => $departments,
    ])
--}}
@php
    $course = $course ?? null;
    $isEdit = $course !== null;
    $action = $isEdit
        ? route('admin.reference-data.courses.update', $course)
        : route('admin.reference-data.courses.store');
@endphp

<x-ui.modal :name="$modalName" :title="$isEdit ? 'Edit Course' : 'Add New Course'" max-width="lg">
    <form method="POST" action="{{ $action }}" id="form-{{ $modalName }}" class="space-y-lg">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-ui.input id="name-{{ $modalName }}" name="name" label="Course Name" :value="old('name', $course?->name)" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg">
            <x-ui.input id="code-{{ $modalName }}" name="code" label="Course Code" :value="old('code', $course?->code)" />

            <x-ui.select id="department_id-{{ $modalName }}" name="department_id" label="Department" placeholder="No department">
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $course?->department_id) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input id="duration_years-{{ $modalName }}" type="number" name="duration_years" label="Duration (Years)" :value="old('duration_years', $course?->duration_years)" min="0" max="10" />
            <x-ui.input id="duration_semesters-{{ $modalName }}" type="number" name="duration_semesters" label="Duration (Semesters)" :value="old('duration_semesters', $course?->duration_semesters)" min="0" max="20" />
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="open = false">Cancel</x-ui.button>
        <x-ui.button type="submit" form="form-{{ $modalName }}" icon="save">{{ $isEdit ? 'Save Changes' : 'Add Course' }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
