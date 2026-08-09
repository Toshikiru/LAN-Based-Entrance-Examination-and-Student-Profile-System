{{--
    Shared add/edit form body for a year level modal.
    Included with: @include('admin.reference-data.partials._year-level-form', [
        'modalName' => 'add-year-level', 'yearLevel' => null,
    ])
--}}
@php
    $yearLevel = $yearLevel ?? null;
    $isEdit = $yearLevel !== null;
    $action = $isEdit
        ? route('admin.reference-data.year-levels.update', $yearLevel)
        : route('admin.reference-data.year-levels.store');
@endphp

<x-ui.modal :name="$modalName" :title="$isEdit ? 'Edit Year Level' : 'Add Year Level'" max-width="sm">
    <form method="POST" action="{{ $action }}" id="form-{{ $modalName }}" class="space-y-lg">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-ui.input id="name-{{ $modalName }}" name="name" label="Year Level Name" placeholder="e.g. 1st Year" :value="old('name', $yearLevel?->name)" />
        <x-ui.input id="order-{{ $modalName }}" type="number" name="order" label="Sort Order" :value="old('order', $yearLevel?->order ?? 0)" min="0" max="100" />
    </form>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="open = false">Cancel</x-ui.button>
        <x-ui.button type="submit" form="form-{{ $modalName }}" icon="save">{{ $isEdit ? 'Save Changes' : 'Add Year Level' }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
