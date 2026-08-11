@php
    $isEdit = isset($exam);
    $startsAt = old('starts_at', $isEdit && $exam->starts_at ? $exam->starts_at->format('Y-m-d\TH:i') : '');
    $endsAt = old('ends_at', $isEdit && $exam->ends_at ? $exam->ends_at->format('Y-m-d\TH:i') : '');
    $descError = $errors->has('description');
    // Unchecked checkboxes aren't submitted, so old('has_timer') is only a
    // reliable signal when this is a validation-failure redisplay — plain
    // old('has_timer', default) would wrongly fall back to $default (true)
    // after a failed submit where the box was actually left unchecked.
    $hasTimer = $errors->any() ? old('has_timer') !== null : ($isEdit ? $exam->duration_minutes !== null : true);
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg" x-data="{ hasTimer: {{ $hasTimer ? 'true' : 'false' }} }">
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

            <div class="md:col-span-2 max-w-xs">
                <x-ui.input
                    label="Passing Score (%)"
                    name="passing_score"
                    type="number" min="0" max="100" step="0.01"
                    placeholder="e.g. 60"
                    value="{{ old('passing_score', $isEdit ? $exam->passing_score : '') }}"
                    hint="Minimum percentage to be marked as passed. Set here so you don't need a separate trip to Settings."
                    required
                />
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-sm cursor-pointer">
                    <input type="checkbox" name="has_timer" value="1" x-model="hasTimer"
                           class="w-5 h-5 text-primary rounded border-outline-variant focus:ring-primary">
                    <span class="font-label-md text-label-md text-on-surface-variant">Enable a time limit for this exam</span>
                </label>
                <p class="text-label-sm text-outline mt-xs">Off means students can take their time — the exam won't auto-submit.</p>
            </div>

            <div x-show="hasTimer" x-cloak>
                <x-ui.input
                    label="Duration (minutes)"
                    name="duration_minutes"
                    type="number"
                    min="1"
                    max="600"
                    placeholder="e.g. 120"
                    value="{{ old('duration_minutes', $isEdit ? $exam->duration_minutes : '') }}"
                    x-bind:required="hasTimer"
                    x-bind:disabled="!hasTimer"
                />
            </div>

            <div class="hidden md:block" x-show="hasTimer" x-cloak></div>

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
