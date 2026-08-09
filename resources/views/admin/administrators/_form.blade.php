@php
    $isEdit = isset($administrator);
    $profile = $isEdit ? $administrator->counselorProfile : null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
    <x-ui.input
        label="Full Name"
        name="name"
        placeholder="e.g. Maria Santillan"
        value="{{ old('name', $isEdit ? $administrator->name : '') }}"
        autocomplete="name"
        required
    />

    <x-ui.input
        label="School ID"
        name="school_id"
        placeholder="e.g. COUNSELOR-0003"
        value="{{ old('school_id', $isEdit ? $administrator->school_id : '') }}"
        hint="Used as the login username."
        autocomplete="username"
        required
    />

    <x-ui.input
        label="Email Address"
        name="email"
        type="email"
        placeholder="counselor@school.edu"
        value="{{ old('email', $isEdit ? $administrator->email : '') }}"
        autocomplete="email"
    />

    <x-ui.input
        label="Position Title"
        name="position_title"
        placeholder="e.g. Guidance Counselor"
        value="{{ old('position_title', $profile?->position_title) }}"
    />
</div>
