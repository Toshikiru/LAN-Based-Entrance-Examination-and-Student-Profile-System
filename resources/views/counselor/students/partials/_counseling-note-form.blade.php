{{--
    Shared add/edit form body for a counseling note modal.
    Included with: @include('counselor.students.partials._counseling-note-form', [
        'modalName' => 'add-counseling-note', 'student' => $student, 'note' => null,
    ])
--}}
@php
    $note = $note ?? null;
    $isEdit = $note !== null;
    $action = $isEdit
        ? route('counselor.students.counseling-notes.update', [$student, $note])
        : route('counselor.students.counseling-notes.store', $student);
    $categories = \App\Enums\CounselingNoteCategory::cases();
    $statuses = \App\Enums\CounselingNoteStatus::cases();
@endphp

<x-ui.modal :name="$modalName" :title="$isEdit ? 'Edit Counseling Note' : 'Add Counseling Note'" max-width="lg">
    <form method="POST" action="{{ $action }}" id="form-{{ $modalName }}" class="space-y-lg">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg">
            <x-ui.select id="category-{{ $modalName }}" name="category" label="Category">
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(old('category', $note?->category?->value) === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select id="status-{{ $modalName }}" name="status" label="Status">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $note?->status?->value ?? 'open') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input id="note_date-{{ $modalName }}" type="date" name="note_date" label="Date" :value="old('note_date', $note?->note_date?->format('Y-m-d') ?? now()->format('Y-m-d'))" />

            <x-ui.input id="follow_up_date-{{ $modalName }}" type="date" name="follow_up_date" label="Follow-up Date (optional)" :value="old('follow_up_date', $note?->follow_up_date?->format('Y-m-d'))" />
        </div>

        <div>
            <label for="content-{{ $modalName }}" class="block font-label-md text-label-md text-on-surface-variant mb-xs">Note Content</label>
            <textarea
                id="content-{{ $modalName }}"
                name="content"
                rows="5"
                autocomplete="off"
                placeholder="Summary of the counseling session, observations, or recommendations..."
                @class([
                    'w-full bg-surface-container-lowest border rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none',
                    'border-error focus:border-error focus:ring-4 focus:ring-error/10' => $errors->has('content'),
                    'border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10' => ! $errors->has('content'),
                ])
            >{{ old('content', $note?->content) }}</textarea>
            @error('content')
                <p class="text-label-sm text-error mt-xs">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="follow_up_action-{{ $modalName }}" class="block font-label-md text-label-md text-on-surface-variant mb-xs">Follow-up Action (optional)</label>
            <textarea
                id="follow_up_action-{{ $modalName }}"
                name="follow_up_action"
                rows="3"
                autocomplete="off"
                placeholder="Action taken or planned in response to this note..."
                @class([
                    'w-full bg-surface-container-lowest border rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none',
                    'border-error focus:border-error focus:ring-4 focus:ring-error/10' => $errors->has('follow_up_action'),
                    'border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10' => ! $errors->has('follow_up_action'),
                ])
            >{{ old('follow_up_action', $note?->follow_up_action) }}</textarea>
            @error('follow_up_action')
                <p class="text-label-sm text-error mt-xs">{{ $message }}</p>
            @enderror
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="open = false">Cancel</x-ui.button>
        <x-ui.button type="submit" form="form-{{ $modalName }}" icon="save">
            {{ $isEdit ? 'Save Changes' : 'Add Note' }}
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
