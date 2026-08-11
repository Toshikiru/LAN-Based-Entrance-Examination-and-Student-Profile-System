<x-layouts.guest :title="$branding['system_name'] . ' | ' . $branding['school_name'] . ' Login'">

<style>
    /* Mirrors the landing page's page-to-page transition (see
       resources/views/landing.blade.php) so the two feel like one paired
       animation: landing exits upward into this page entering from below,
       and this "Back to landing" link exits downward in reverse. */
    @keyframes tpc-page-enter {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes tpc-page-exit-down {
        to { opacity: 0; transform: translateY(16px); }
    }
    .tpc-page-enter { animation: tpc-page-enter 0.4s ease-out; }
    .tpc-page-exit-down { animation: tpc-page-exit-down 0.25s ease-in forwards; }
    @media (prefers-reduced-motion: reduce) {
        .tpc-page-enter, .tpc-page-exit-down { animation: none; }
    }
</style>

<div x-data="{ roleHint: @js(old('role', 'super_admin')) }" class="tpc-page-enter">

    <a
        href="{{ route('landing') }}"
        data-turbo="false"
        onclick="return tpcPageExitDown(event, this.href)"
        class="inline-flex items-center gap-xs font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors mb-lg"
    >
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to landing page
    </a>

    <div class="text-center mb-lg">
        <h2 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-xs">Welcome Back</h2>
        <p class="font-label-md text-label-md text-on-surface-variant">Sign in to access the institutional dashboard.</p>
    </div>

    {{-- Role selector. This isn't just a UI hint anymore — the picked role
         is submitted with the form (hidden input below) and enforced
         server-side in LoginRequest::authenticate(), which rejects the
         attempt if the account's real role doesn't match what's selected
         here, regardless of what the client sends. --}}
    <div class="w-full flex items-center bg-surface-container rounded-full p-1 mb-lg border border-outline-variant">
        <button type="button" @click="roleHint = 'super_admin'" :class="roleHint === 'super_admin' ? 'bg-surface-container-lowest shadow-sm text-on-surface' : 'text-on-surface-variant hover:text-on-surface'" class="flex-1 py-2 rounded-full font-label-sm text-label-sm font-semibold transition-all">Super Admin</button>
        <button type="button" @click="roleHint = 'counselor'" :class="roleHint === 'counselor' ? 'bg-surface-container-lowest shadow-sm text-on-surface' : 'text-on-surface-variant hover:text-on-surface'" class="flex-1 py-2 rounded-full font-label-sm text-label-sm font-semibold transition-all">Counselor</button>
        <button type="button" @click="roleHint = 'student'" :class="roleHint === 'student' ? 'bg-surface-container-lowest shadow-sm text-on-surface' : 'text-on-surface-variant hover:text-on-surface'" class="flex-1 py-2 rounded-full font-label-sm text-label-sm font-semibold transition-all">Student</button>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="w-full mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" class="w-full mb-lg">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form action="{{ route('login') }}" class="space-y-lg" method="POST">
        @csrf
        <input type="hidden" name="role" :value="roleHint">

        <div>
            <label class="block font-label-md text-label-md text-on-surface-variant mb-xs" for="school_id" x-text="roleHint === 'student' ? 'Student ID' : 'School ID'">School ID</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">badge</span>
                <input
                    class="block w-full pl-11 pr-md py-3 border border-outline-variant rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary font-label-md text-label-md transition-colors bg-surface-container-low focus:bg-surface-container-lowest outline-none"
                    id="school_id" name="school_id" :placeholder="roleHint === 'student' ? 'e.g. 2021-0452' : 'e.g. 2024-0001'" value="{{ old('school_id') }}" required type="text" autocomplete="username" autofocus
                />
            </div>
            @error('role') <p class="font-label-sm text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-xs">
                <label class="block font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
                <a class="font-label-sm text-label-sm font-medium text-primary hover:text-surface-tint transition-colors" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="relative" x-data="{ show: false }">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">lock</span>
                <input
                    :type="show ? 'text' : 'password'"
                    class="block w-full pl-11 pr-11 py-3 border border-outline-variant rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary font-label-md text-label-md transition-colors bg-surface-container-low focus:bg-surface-container-lowest outline-none"
                    id="password" name="password" placeholder="••••••••" required autocomplete="current-password"
                />
                <button type="button" @click="show = !show" class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>
            @error('password') <p class="font-label-sm text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <input class="h-4 w-4 text-primary focus:ring-primary/20 border-outline-variant rounded cursor-pointer" id="remember-me" name="remember" type="checkbox"/>
            <label class="ml-sm block font-label-md text-label-md text-on-surface-variant cursor-pointer" for="remember-me">
                Remember me on this device
            </label>
        </div>

        <div>
            <button class="w-full flex justify-center items-center py-3 px-md border border-transparent rounded-xl shadow-sm font-label-md text-label-md font-semibold text-on-primary bg-gradient-to-r from-primary to-surface-tint hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform hover:-translate-y-0.5" type="submit">
                <span x-text="roleHint === 'super_admin' ? 'Log in as Administrator' : (roleHint === 'counselor' ? 'Log in as Counselor' : 'Log in as Student')"></span>
            </button>
        </div>
    </form>

    <div class="mt-lg text-center">
        <p class="font-label-md text-label-md text-on-surface-variant">
            Need help signing in? <a class="font-medium text-primary hover:text-surface-tint transition-colors" href="{{ route('password.request') }}">Contact the Guidance Office</a>
        </p>
    </div>

</div>

<script>
    function tpcPageExitDown(event, href) {
        event.preventDefault();
        document.querySelector('.tpc-page-enter').classList.add('tpc-page-exit-down');
        setTimeout(function () { window.location.href = href; }, 220);
        return false;
    }
</script>

</x-layouts.guest>
