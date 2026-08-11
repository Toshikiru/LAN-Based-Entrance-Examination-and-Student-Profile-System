<!DOCTYPE html>
<html class="light scroll-smooth" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>{{ $branding['system_name'] }} | {{ $branding['school_name'] }} Entrance Examination Platform</title>

@include('partials.theme-head')

<style>
    .glass-panel {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(224, 227, 229, 0.8);
    }
    .modern-shadow {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Page-to-page transition (landing <-> login). Turbo is disabled on
       these specific links (data-turbo="false") so this plain JS/CSS
       animation gets full control of the timing instead of racing Turbo's
       own instant crossfade. */
    @keyframes tpc-page-exit {
        to { opacity: 0; transform: translateY(-16px); }
    }
    @keyframes tpc-page-enter {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .tpc-page-exit { animation: tpc-page-exit 0.25s ease-in forwards; }
    .tpc-page-enter { animation: tpc-page-enter 0.4s ease-out; }
    @media (prefers-reduced-motion: reduce) {
        .tpc-page-exit, .tpc-page-enter { animation: none; }
    }
</style>
</head>
<body class="min-h-screen flex flex-col font-body-md bg-surface text-on-surface tpc-page-enter">

<!-- Top Nav -->
<header class="bg-surface/80 backdrop-blur-md fixed top-0 w-full border-b border-outline-variant/30 shadow-sm z-50">
    <div class="flex justify-between items-center h-16 px-gutter max-w-7xl mx-auto">
        <a href="{{ route('landing') }}" class="flex items-center gap-sm">
            @if (!empty($branding['logo_url']))
                <div class="w-8 h-8 shrink-0 flex items-center justify-center">
                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['system_name'] }}" class="max-w-full max-h-full w-auto h-auto object-contain">
                </div>
            @else
                <span class="material-symbols-outlined text-primary text-3xl">school</span>
            @endif
            <span class="font-headline-md text-headline-md font-bold text-primary">{{ $branding['system_name'] }}</span>
        </a>

        <nav class="hidden md:flex items-center gap-lg">
            <a class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors" href="#features">Features</a>
            <a class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors" href="#roles">Roles</a>
            <a class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors" href="#process">Process</a>
            <a class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors" href="#security">Security</a>
        </nav>

        <div class="flex items-center gap-md">
            <x-ui.theme-toggle />
            <a class="font-label-md text-label-md bg-primary text-on-primary px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors shadow-sm relative overflow-hidden" href="{{ route('login') }}" data-turbo="false" onclick="return tpcPageExit(event, this.href)">
                <div class="absolute inset-x-0 top-0 h-px bg-white/20"></div>
                Sign In
            </a>
        </div>
    </div>
</header>

<main class="flex-grow pt-24 pb-16">

    <!-- Hero -->
    <section class="max-w-7xl mx-auto px-gutter pt-xl pb-[64px] md:pt-[64px] md:pb-[96px] text-center">
        <h1 class="font-display-lg-mobile text-display-lg-mobile md:font-display-lg md:text-display-lg text-on-surface max-w-4xl mx-auto mb-lg tracking-tight">
            The Official Entrance Examination Platform for <span class="text-primary">{{ $branding['school_name'] }}</span>
        </h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto mb-xl">
            Secure, LAN-based assessment and profile management for the modern institution. Built for clarity, speed, and reliability.
        </p>
        <div class="flex flex-col sm:flex-row justify-center items-center gap-md mb-[64px]">
            <a class="font-label-md text-label-md bg-primary text-on-primary px-8 py-3 rounded-lg hover:bg-primary/90 transition-colors shadow-md relative w-full sm:w-auto text-center" href="{{ route('login') }}" data-turbo="false" onclick="return tpcPageExit(event, this.href)">
                <div class="absolute inset-x-0 top-0 h-px bg-white/20"></div>
                Sign In
            </a>
            <a class="font-label-md text-label-md text-primary border border-primary/20 px-8 py-3 rounded-lg hover:bg-primary/5 transition-colors w-full sm:w-auto text-center" href="#features">
                Learn More
            </a>
        </div>

        <!-- Product Mockup -->
        <div class="relative max-w-5xl mx-auto rounded-2xl overflow-hidden border border-outline-variant/30 shadow-2xl glass-panel bg-surface-container-lowest">
            <div class="w-full h-8 bg-surface-container flex items-center px-4 gap-2 border-b border-outline-variant/20">
                <div class="w-3 h-3 rounded-full bg-outline-variant/50"></div>
                <div class="w-3 h-3 rounded-full bg-outline-variant/50"></div>
                <div class="w-3 h-3 rounded-full bg-outline-variant/50"></div>
            </div>
            <img
                src="{{ asset('images/landing-preview.png') }}"
                alt="{{ $branding['system_name'] }} Guidance Counselor dashboard"
                class="w-full h-auto block"
            >
        </div>
    </section>

    <!-- Trust Strip -->
    <section id="features" class="max-w-7xl mx-auto px-gutter mb-[64px] scroll-mt-24">
        <div class="flex flex-wrap justify-center gap-sm md:gap-lg">
            <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-full border border-outline-variant/20">
                <span class="material-symbols-outlined text-primary text-sm">dns</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">LAN-based</span>
            </div>
            <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-full border border-outline-variant/20">
                <span class="material-symbols-outlined text-primary text-sm">shield_person</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">Role-based security</span>
            </div>
            <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-full border border-outline-variant/20">
                <span class="material-symbols-outlined text-primary text-sm">monitoring</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">Real-time monitoring</span>
            </div>
            <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2 rounded-full border border-outline-variant/20">
                <span class="material-symbols-outlined text-primary text-sm">fact_check</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant">Automated scoring</span>
            </div>
        </div>
    </section>

    <!-- Roles -->
    <section class="max-w-7xl mx-auto px-gutter py-xl md:py-[64px] scroll-mt-24" id="roles">
        <div class="text-center mb-xl">
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-sm">Built for every role</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Tailored experiences ensuring seamless operation across the institution.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 hover-lift modern-shadow flex flex-col items-start text-left relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -mr-8 -mt-8"></div>
                <div class="w-12 h-12 bg-primary-container text-on-primary-container rounded-lg flex items-center justify-center mb-6 z-10">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-2 z-10">Super Admin</h3>
                <p class="font-body-md text-body-md text-on-surface-variant z-10">System-wide control &amp; audit trails.</p>
            </div>
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 hover-lift modern-shadow flex flex-col items-start text-left relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-secondary/5 rounded-bl-full -mr-8 -mt-8"></div>
                <div class="w-12 h-12 bg-secondary-container text-on-secondary-container rounded-lg flex items-center justify-center mb-6 z-10">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-2 z-10">Guidance Counselor</h3>
                <p class="font-body-md text-body-md text-on-surface-variant z-10">Build exams, monitor live sessions, manage profiles.</p>
            </div>
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/30 hover-lift modern-shadow flex flex-col items-start text-left relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-tertiary/5 rounded-bl-full -mr-8 -mt-8"></div>
                <div class="w-12 h-12 bg-tertiary-container text-on-tertiary-container rounded-lg flex items-center justify-center mb-6 z-10">
                    <span class="material-symbols-outlined">school</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-2 z-10">Student</h3>
                <p class="font-body-md text-body-md text-on-surface-variant z-10">Join with a code, take timed exams, instant results.</p>
            </div>
        </div>
    </section>

    <!-- Process -->
    <section class="bg-surface-container-low py-xl md:py-[64px] mt-xl border-y border-outline-variant/20 scroll-mt-24" id="process">
        <div class="max-w-7xl mx-auto px-gutter">
            <div class="text-center mb-xl">
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-sm">How it works</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">A streamlined workflow from creation to result.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-xl relative">
                <div class="hidden md:block absolute top-6 left-[16%] right-[16%] h-px bg-outline-variant/40 z-0"></div>

                <div class="flex flex-col items-center text-center relative z-10">
                    <div class="w-12 h-12 rounded-full bg-secondary text-on-secondary flex items-center justify-center font-headline-md mb-4 shadow-sm relative">
                        <div class="absolute inset-x-0 top-0 h-px bg-white/30 rounded-t-full"></div>
                        1
                    </div>
                    <h4 class="font-headline-md text-headline-md text-on-surface mb-2">Author &amp; Publish</h4>
                    <p class="font-body-md text-body-md text-on-surface-variant">Counselors build the question bank and publish an examination with an access code.</p>
                </div>

                <div class="flex flex-col items-center text-center relative z-10">
                    <div class="w-12 h-12 rounded-full bg-secondary text-on-secondary flex items-center justify-center font-headline-md mb-4 shadow-sm relative">
                        <div class="absolute inset-x-0 top-0 h-px bg-white/30 rounded-t-full"></div>
                        2
                    </div>
                    <h4 class="font-headline-md text-headline-md text-on-surface mb-2">Join &amp; Take</h4>
                    <p class="font-body-md text-body-md text-on-surface-variant">Students join with the access code on the campus LAN and complete a timed assessment.</p>
                </div>

                <div class="flex flex-col items-center text-center relative z-10">
                    <div class="w-12 h-12 rounded-full bg-secondary text-on-secondary flex items-center justify-center font-headline-md mb-4 shadow-sm relative">
                        <div class="absolute inset-x-0 top-0 h-px bg-white/30 rounded-t-full"></div>
                        3
                    </div>
                    <h4 class="font-headline-md text-headline-md text-on-surface mb-2">Score &amp; Release</h4>
                    <p class="font-body-md text-body-md text-on-surface-variant">Objective items are auto-scored instantly; results are released once grading is complete.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Security -->
    <section class="max-w-7xl mx-auto px-gutter py-xl scroll-mt-24" id="security">
        <div class="bg-surface-container-highest rounded-2xl p-8 border border-outline-variant/30 flex flex-col md:flex-row items-center justify-between gap-lg">
            <div class="flex-1">
                <h3 class="font-headline-lg text-headline-lg text-on-surface mb-2 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">lock</span>
                    Built-in Access Control
                </h3>
                <p class="font-body-md text-body-md text-on-surface-variant">Designed to maintain academic integrity within a controlled network environment.</p>
            </div>
            <div class="flex flex-col gap-3 w-full md:w-auto">
                <div class="flex items-center gap-3 bg-surface-container-lowest px-4 py-3 rounded-lg border border-outline-variant/20 shadow-sm">
                    <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                    <span class="font-label-md text-label-md text-on-surface">Role-based access</span>
                </div>
                <div class="flex items-center gap-3 bg-surface-container-lowest px-4 py-3 rounded-lg border border-outline-variant/20 shadow-sm">
                    <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                    <span class="font-label-md text-label-md text-on-surface">One active session per student</span>
                </div>
                <div class="flex items-center gap-3 bg-surface-container-lowest px-4 py-3 rounded-lg border border-outline-variant/20 shadow-sm">
                    <span class="material-symbols-outlined text-secondary text-lg">check_circle</span>
                    <span class="font-label-md text-label-md text-on-surface">Full audit logs</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="bg-primary py-[64px] mt-xl">
        <div class="max-w-4xl mx-auto px-gutter text-center">
            <h2 class="font-headline-lg text-headline-lg text-on-primary mb-6">Ready to start?</h2>
            <p class="font-body-lg text-body-lg text-primary-fixed-dim mb-8">Access the portal to manage your profile or begin an assessment.</p>
            <a class="inline-block font-label-md text-label-md bg-surface text-primary px-8 py-4 rounded-lg hover:bg-surface-container-lowest transition-colors shadow-lg" href="{{ route('login') }}" data-turbo="false" onclick="return tpcPageExit(event, this.href)">
                Sign in to the portal
            </a>
        </div>
    </section>
</main>

<footer class="bg-surface-container-low w-full py-12 border-t border-outline-variant/20">
    <div class="flex flex-col md:flex-row justify-between items-center px-gutter max-w-7xl mx-auto gap-lg">
        <div class="font-headline-md text-headline-md font-bold text-on-surface opacity-80">
            {{ $branding['system_name'] }}
        </div>
        <nav class="flex flex-wrap justify-center gap-6">
            <a class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="#">Privacy Policy</a>
            <a class="font-label-sm text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="#">Terms of Service</a>
        </nav>
        <div class="font-label-sm text-label-sm text-on-surface-variant">
            &copy; {{ date('Y') }} {{ $branding['school_name'] }}. All rights reserved.
        </div>
    </div>
</footer>

<script>
    function tpcPageExit(event, href) {
        event.preventDefault();
        document.body.classList.add('tpc-page-exit');
        setTimeout(function () { window.location.href = href; }, 220);
        return false;
    }
</script>

</body>
</html>
