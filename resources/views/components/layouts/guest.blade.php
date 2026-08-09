@props([
    'title' => \App\Services\SchoolSettingsService::DEFAULT_SYSTEM_NAME,
    'branding' => [
        'system_name' => config('app.name', 'GuidancePulse'),
        'system_full_name' => 'Guidance & Psychological Services Management System',
        'school_name' => 'Tagbilaran City College',
        'logo_url' => null,
    ]
])

@php
    $academicYearStart = now()->month >= 6 ? now()->year : now()->year - 1;
    $academicYear = $academicYearStart . '–' . ($academicYearStart + 1);
@endphp
<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="turbo-cache-control" content="no-cache"/>
<title>{{ $title }}</title>

@include('partials.theme-head')

<style>
    /* Split-screen guest shell */
    .glass-overlay {
        background: color-mix(in srgb, var(--color-on-tertiary-fixed, #0f172a) 78%, transparent);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    @media (max-height: 560px) {
        .hero-feature-grid {
            display: none;
        }
    }
</style>
</head>
<body class="font-body-md text-on-surface h-screen w-full overflow-hidden flex selection:bg-primary-fixed selection:text-on-primary-fixed bg-surface">

<main class="flex w-full h-full">
    {{-- Left panel: hero / brand imagery (60%) --}}
    <section class="hidden lg:flex lg:w-3/5 relative flex-col justify-center p-lg xl:p-xl overflow-y-auto overflow-x-hidden bg-on-tertiary-fixed text-white">
        <img
            src="{{ asset('images/tpc_school_bg.jpg') }}"
            alt="{{ $branding['school_name'] ?? 'School' }} campus"
            class="absolute inset-0 w-full h-full object-cover opacity-50 z-0"
        >
        <div class="absolute inset-0 glass-overlay z-10"></div>

        <div class="relative z-20 flex flex-col max-w-2xl">
            <header>
                <div class="flex items-center gap-sm mb-md">
                    @if (!empty($branding['logo_url']))
                        <div class="w-10 h-10 shrink-0 flex items-center justify-center">
                            <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['system_name'] ?? 'Logo' }}" class="max-w-full max-h-full w-auto h-auto object-contain">
                        </div>
                    @else
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-on-primary">school</span>
                        </div>
                    @endif
                    <h1 class="font-headline-lg text-headline-lg font-bold tracking-tight">{{ $branding['system_name'] ?? 'GuidancePulse' }}</h1>
                </div>

                <h2 class="font-display-lg-mobile text-display-lg-mobile font-bold leading-tight mb-sm">
                    Next-Generation <br> Academic Gateway
                </h2>
                <p class="font-body-lg text-body-lg text-white/80 font-medium max-w-lg">
                    {{ $branding['system_full_name'] ?? '' }}
                </p>
            </header>

            {{-- Feature grid --}}
            <div class="hero-feature-grid grid grid-cols-2 gap-sm mt-lg">
                @foreach ([
                    ['icon' => 'lan', 'label' => 'Secure LAN', 'desc' => 'Isolated network, zero external access.', 'tint' => 'text-secondary-fixed'],
                    ['icon' => 'edit_note', 'label' => 'Examinations', 'desc' => 'Real-time digital testing and proctoring.', 'tint' => 'text-primary-fixed-dim'],
                    ['icon' => 'person_search', 'label' => 'Student Profiles', 'desc' => 'Complete academic records and tracking.', 'tint' => 'text-tertiary-fixed'],
                    ['icon' => 'analytics', 'label' => 'Analytics', 'desc' => 'Deep insights into institutional data.', 'tint' => 'text-secondary-fixed-dim'],
                ] as $feature)
                    <div class="glass-card rounded-xl p-sm flex flex-col gap-xs transition-transform hover:-translate-y-1 duration-300">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center {{ $feature['tint'] }}">
                            <span class="material-symbols-outlined text-[18px]">{{ $feature['icon'] }}</span>
                        </div>
                        <h3 class="font-body-md text-body-md font-semibold">{{ $feature['label'] }}</h3>
                        <p class="font-label-md text-label-md text-white/75 truncate">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Status strip --}}
            <div class="flex items-center gap-lg mt-lg pt-md border-t border-white/20">
                <div class="flex items-center gap-sm">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary-fixed opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-secondary-fixed"></span>
                    </span>
                    <span class="font-label-sm text-label-sm font-medium text-white/90">100% Campus LAN</span>
                </div>
                <div class="flex items-center gap-sm">
                    <span class="material-symbols-outlined text-secondary-fixed text-[18px]">bolt</span>
                    <span class="font-label-sm text-label-sm font-medium text-white/90">&lt;1s Latency</span>
                </div>
                <div class="flex items-center gap-sm">
                    <span class="material-symbols-outlined text-secondary-fixed text-[18px]">check_circle</span>
                    <span class="font-label-sm text-label-sm font-medium text-white/90">24/7 Monitoring</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Right panel: auth form (40%) --}}
    <section class="w-full lg:w-2/5 flex flex-col bg-surface p-md md:p-lg h-full overflow-y-auto custom-scrollbar">
        {{-- Inner wrapper keeps card vertically centered while reserving room for footer --}}
        <div class="flex-1 flex flex-col justify-center items-center my-auto w-full py-md">
            {{-- Mobile-only logo --}}
            <div class="lg:hidden flex items-center gap-sm mb-lg">
                @if (!empty($branding['logo_url']))
                    <div class="w-8 h-8 shrink-0 flex items-center justify-center">
                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['system_name'] ?? 'Logo' }}" class="max-w-full max-h-full w-auto h-auto object-contain">
                    </div>
                @else
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[18px] text-on-primary">school</span>
                    </div>
                @endif
                <h1 class="font-headline-md text-headline-md font-bold text-on-surface">{{ $branding['system_name'] ?? 'GuidancePulse' }}</h1>
            </div>

            <div class="w-full max-w-md bg-surface-container-lowest rounded-[24px] shadow-2xl p-lg border border-outline-variant relative z-10">
                {{ $slot }}
            </div>
        </div>

        {{-- Footer info - Flows naturally below the card --}}
        <footer class="mt-auto pt-md text-center w-full shrink-0">
            <p class="font-label-sm text-label-sm text-outline font-medium">
                {{ $branding['system_name'] ?? 'GuidancePulse' }} Version 2.0 &bull; A.Y. {{ $academicYear }}
            </p>
            <p class="font-label-sm text-label-sm text-outline mt-xs">
                &copy; {{ date('Y') }} {{ $branding['school_name'] ?? 'School' }}. All rights reserved.
            </p>
        </footer>
    </section>
</main>

</body>
</html>