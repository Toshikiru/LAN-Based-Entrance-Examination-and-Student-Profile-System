@props(['title' => \App\Services\SchoolSettingsService::DEFAULT_SYSTEM_NAME, 'active' => null])

@php
    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('counselor.dashboard')],
                ['key' => 'students', 'label' => 'Student Profiles', 'icon' => 'person_search', 'href' => route('counselor.students.index')],
                ['key' => 'exams', 'label' => 'Exams', 'icon' => 'edit_note', 'href' => route('counselor.exams.index')],
                ['key' => 'questions', 'label' => 'Question Bank', 'icon' => 'quiz', 'href' => route('counselor.questions.index')],
                ['key' => 'monitoring', 'label' => 'Live Monitoring', 'icon' => 'visibility', 'href' => route('counselor.monitoring.index')],
                ['key' => 'results', 'label' => 'Results', 'icon' => 'grade', 'href' => route('counselor.results.index')],
                ['key' => 'reports', 'label' => 'Reports', 'icon' => 'analytics', 'href' => route('counselor.reports.index')],
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
<title>{{ $title }} | {{ $branding['system_name'] }} Counselor</title>
@include('partials.theme-head')
</head>
<body class="bg-background text-on-background font-body-md min-h-screen">

<x-navigation.sidebar :groups="$navGroups" :active="$active" :brand="$branding['system_name']" :subtitle="$branding['school_name']" :logo-url="$branding['logo_url']">
    <x-slot:cta>
        <a href="{{ route('counselor.exams.create') }}" class="w-full flex items-center justify-center gap-sm py-md px-lg bg-primary text-on-primary rounded-xl font-label-md text-label-md shadow-sm hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Start New Session
        </a>
    </x-slot:cta>
</x-navigation.sidebar>

<div class="lg:ml-[260px] min-h-screen flex flex-col">
    <x-navigation.topnav search-placeholder="Search students, exams, or reports..." search-route="counselor.search" />

    <main class="flex-1 p-lg max-w-[1400px] mx-auto w-full">
        {{ $slot }}
    </main>

    <x-navigation.footer />
</div>

</body>
</html>
