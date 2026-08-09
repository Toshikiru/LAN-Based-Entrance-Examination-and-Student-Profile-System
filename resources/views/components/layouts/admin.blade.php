@props(['title' => \App\Services\SchoolSettingsService::DEFAULT_SYSTEM_NAME, 'active' => null])

@php
    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('admin.dashboard')],
            ],
        ],
        [
            'label' => 'User Management',
            'items' => [
                ['key' => 'administrators', 'label' => 'Guidance Administrators', 'icon' => 'group', 'href' => route('admin.administrators.index')],
                ['key' => 'students', 'label' => 'Students', 'icon' => 'school', 'href' => route('counselor.students.index')],
            ],
        ],
        [
            'label' => 'Examination',
            'items' => [
                ['key' => 'questions', 'label' => 'Question Bank', 'icon' => 'quiz', 'href' => route('counselor.questions.index')],
                ['key' => 'exams', 'label' => 'Examinations', 'icon' => 'edit_note', 'href' => route('counselor.exams.index')],
                ['key' => 'monitoring', 'label' => 'Live Monitoring', 'icon' => 'visibility', 'href' => route('counselor.monitoring.index')],
                ['key' => 'results', 'label' => 'Results', 'icon' => 'grade', 'href' => route('counselor.results.index')],
            ],
        ],
        [
            'label' => 'Student Records',
            'items' => [
                ['key' => 'counseling-notes', 'label' => 'Counseling Notes', 'icon' => 'chat', 'href' => route('counselor.counseling-notes.index')],
            ],
        ],
        [
            'label' => 'Analytics',
            'items' => [
                ['key' => 'reports', 'label' => 'Reports', 'icon' => 'analytics', 'href' => route('counselor.reports.index')],
                ['key' => 'audit-logs', 'label' => 'Audit Logs', 'icon' => 'history_edu', 'href' => route('admin.audit-logs.index')],
            ],
        ],
        [
            'label' => 'System',
            'items' => [
                ['key' => 'settings', 'label' => 'System Settings', 'icon' => 'settings', 'href' => route('admin.settings.index')],
                ['key' => 'reference-data', 'label' => 'Reference Data', 'icon' => 'storage', 'href' => route('admin.reference-data.index')],
                ['key' => 'backup', 'label' => 'Backup & Restore', 'icon' => 'backup', 'href' => route('admin.backup.index')],
            ],
        ],
        [
            'label' => 'Account',
            'items' => [
                ['key' => 'profile', 'label' => 'My Profile', 'icon' => 'person', 'href' => route('profile.edit')],
                ['key' => 'logout', 'label' => 'Logout', 'icon' => 'logout', 'confirm' => 'logout-confirm'],
            ],
        ],
    ];
@endphp

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="nav-active" content="{{ $active }}"/>
<title>{{ $title }} | {{ $branding['system_name'] }} Super Admin</title>
@include('partials.theme-head')
</head>
<body class="bg-background text-on-background font-body-md min-h-screen">

<x-navigation.sidebar :groups="$navGroups" :active="$active" :brand="$branding['system_name']" :subtitle="$branding['school_name']" :logo-url="$branding['logo_url']">
    <x-slot:cta>
        <a href="{{ route('admin.administrators.create') }}" class="w-full flex items-center justify-center gap-sm bg-primary text-on-primary py-sm rounded-lg font-label-md text-label-md shadow-sm hover:brightness-110 active:scale-[0.98] transition-all">
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            Add Administrator
        </a>
    </x-slot:cta>
</x-navigation.sidebar>

<div class="lg:ml-[260px] min-h-screen flex flex-col">
    <x-navigation.topnav search-placeholder="Search system logs..." />

    <main class="flex-1 p-lg max-w-[1600px] mx-auto w-full">
        {{ $slot }}
    </main>

    <x-navigation.footer />
</div>

</body>
</html>
