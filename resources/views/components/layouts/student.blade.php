@props(['title' => \App\Services\SchoolSettingsService::DEFAULT_SYSTEM_NAME, 'active' => null])

@php
    $navGroups = [
        [
            'label' => null,
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('student.dashboard')],
                ['key' => 'exams', 'label' => 'My Exams', 'icon' => 'edit_note', 'href' => route('student.exams.join')],
                ['key' => 'results', 'label' => 'My Results', 'icon' => 'grade', 'href' => route('student.results.index')],
                ['key' => 'profile', 'label' => 'Profile', 'icon' => 'person', 'href' => route('profile.edit')],
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
<title>{{ $title }} | {{ $branding['system_name'] }} Student</title>
@include('partials.theme-head')
</head>
<body class="bg-background text-on-background font-body-md min-h-screen">

<x-navigation.sidebar :groups="$navGroups" :active="$active" :brand="$branding['system_name']" :subtitle="$branding['school_name']" :logo-url="$branding['logo_url']" />

<div class="lg:ml-[260px] min-h-screen flex flex-col">
    <x-navigation.topnav search-placeholder="Search your exams or results..." />

    <main class="flex-1 p-lg max-w-[1200px] mx-auto w-full">
        {{ $slot }}
    </main>

    <x-navigation.footer />
</div>

</body>
</html>
