@php
    $isEdit = isset($student);
    $profile = $isEdit ? $student->studentProfile : null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
    <x-ui.input
        label="Full Name"
        name="name"
        placeholder="e.g. Michael Thorne"
        value="{{ old('name', $isEdit ? $student->name : '') }}"
        required
    />

    <x-ui.input
        label="School ID"
        name="school_id"
        placeholder="e.g. 2024-0891"
        value="{{ old('school_id', $isEdit ? $student->school_id : '') }}"
        required
    />

    <x-ui.input
        label="Email Address"
        name="email"
        type="email"
        placeholder="student@school.edu"
        value="{{ old('email', $isEdit ? $student->email : '') }}"
    />

    <x-ui.input
        :label="$isEdit ? 'New Password' : 'Password'"
        name="password"
        type="password"
        :placeholder="$isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters'"
        :required="! $isEdit"
        :hint="$isEdit ? 'Only fill this in if you want to reset the student\'s password.' : null"
    />

    <x-ui.select label="Department" name="department_id" placeholder="Select a department...">
        @php $selectedDepartment = old('department_id', $isEdit ? $student->department_id : null); @endphp
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected($selectedDepartment == $department->id)>{{ $department->name }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.select label="Enrollment Status" name="enrollment_status">
        @php $selectedEnrollment = old('enrollment_status', $profile?->enrollment_status ?? 'enrolled'); @endphp
        @foreach ($enrollmentStatuses as $value => $label)
            <option value="{{ $value }}" @selected($selectedEnrollment === $value)>{{ $label }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.select label="Year Level" name="year_level" placeholder="Select a year level...">
        @php $selectedYearLevel = old('year_level', $profile?->year_level); @endphp
        @foreach ($yearLevels as $yearLevel)
            <option value="{{ $yearLevel->name }}" @selected($selectedYearLevel === $yearLevel->name)>{{ $yearLevel->name }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.input
        label="Section"
        name="section"
        placeholder="e.g. IT-3A"
        value="{{ old('section', $profile?->section) }}"
    />

    @if ($isEdit)
        <x-ui.select label="Account Status" name="status" required>
            @php $selectedStatus = old('status', $student->status->value); @endphp
            @foreach (\App\Enums\UserStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($selectedStatus === $statusOption->value)>{{ ucfirst($statusOption->value) }}</option>
            @endforeach
        </x-ui.select>
    @endif
</div>
