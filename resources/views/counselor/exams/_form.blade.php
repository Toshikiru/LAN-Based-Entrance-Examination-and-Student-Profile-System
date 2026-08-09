@php
    $isEdit = isset($exam);
    $startsAt = old('starts_at', $isEdit && $exam->starts_at ? $exam->starts_at->format('Y-m-d\TH:i') : '');
    $endsAt = old('ends_at', $isEdit && $exam->ends_at ? $exam->ends_at->format('Y-m-d\TH:i') : '');
    $descError = $errors->has('description');
@endphp

<div class="space-y-xl">
    {{-- General Information --}}
    <section>
        <div class="flex items-center gap-md mb-lg">
            <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined">info</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">General Information</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <div class="md:col-span-2">
                <x-ui.input
                    label="Exam Title"
                    name="title"
                    placeholder="e.g. Entrance Examination 2026"
                    value="{{ old('title', $isEdit ? $exam->title : '') }}"
                    required
                />
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block font-label-md text-label-md text-on-surface-variant mb-xs">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    autocomplete="off"
                    placeholder="Short description of this examination..."
                    @class([
                        'w-full bg-surface-container-lowest border rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none',
                        'border-error focus:border-error focus:ring-4 focus:ring-error/10' => $descError,
                        'border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10' => ! $descError,
                    ])
                >{{ old('description', $isEdit ? $exam->description : '') }}</textarea>
                @error('description')
                    <p class="text-label-sm text-error mt-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Target & Timing --}}
    <section class="pt-lg border-t border-outline-variant">
        <div class="flex items-center gap-md mb-lg">
            <div class="w-10 h-10 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center">
                <span class="material-symbols-outlined">groups</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">Target &amp; Timing</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <x-ui.select label="Course" name="department_id" placeholder="All courses / not assigned">
                @php $selectedDept = old('department_id', $isEdit ? $exam->department_id : null); @endphp
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($selectedDept == $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Year Level" name="year_level" placeholder="Not assigned">
                @php $selectedYear = old('year_level', $isEdit ? $exam->year_level : null); @endphp
                @foreach ($yearLevels as $value => $label)
                    <option value="{{ $value }}" @selected($selectedYear === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input
                label="Duration (minutes)"
                name="duration_minutes"
                type="number"
                min="1"
                max="600"
                placeholder="e.g. 120"
                value="{{ old('duration_minutes', $isEdit ? $exam->duration_minutes : '') }}"
                required
            />

            <div class="hidden md:block"></div>

            <x-ui.input
                label="Start Date & Time"
                name="starts_at"
                type="datetime-local"
                value="{{ $startsAt }}"
                hint="Optional. Leave blank if unscheduled."
            />

            <x-ui.input
                label="End Date & Time"
                name="ends_at"
                type="datetime-local"
                value="{{ $endsAt }}"
                hint="Optional. Must be after the start time."
            />
        </div>
    </section>
</div>
