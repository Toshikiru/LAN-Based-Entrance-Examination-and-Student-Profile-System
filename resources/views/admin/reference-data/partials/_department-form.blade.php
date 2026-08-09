{{--
    Shared add/edit form body for a department modal.
    Included with: @include('admin.reference-data.partials._department-form', [
        'modalName' => 'add-department', 'department' => null,
    ])
--}}
@php
    $department = $department ?? null;
    $isEdit = $department !== null;
    $action = $isEdit
        ? route('admin.reference-data.departments.update', $department)
        : route('admin.reference-data.departments.store');
@endphp

<x-ui.modal :name="$modalName" :title="$isEdit ? 'Edit Department' : 'Add Department'" max-width="sm">
    <form method="POST" action="{{ $action }}" id="form-{{ $modalName }}" class="space-y-lg">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-ui.input id="name-{{ $modalName }}" name="name" label="Department Name" :value="old('name', $department?->name)" />
        <x-ui.input id="code-{{ $modalName }}" name="code" label="Department Code" :value="old('code', $department?->code)" />
    </form>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="open = false">Cancel</x-ui.button>
        <x-ui.button type="submit" form="form-{{ $modalName }}" icon="save">{{ $isEdit ? 'Save Changes' : 'Add Department' }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
