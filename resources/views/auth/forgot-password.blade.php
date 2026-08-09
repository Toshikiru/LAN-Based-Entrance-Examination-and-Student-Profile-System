<x-layouts.guest :title="$branding['system_name'] . ' | Forgot Password'">

<div class="mb-xl">
    <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-xs">Forgot your password?</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">Here's how to get back into your account.</p>
</div>

@if (session('status'))
    <x-ui.alert variant="success" class="w-full mb-lg">{{ session('status') }}</x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert variant="error" class="w-full mb-lg">{{ $errors->first() }}</x-ui.alert>
@endif

{{-- Primary path: this is a LAN-based campus system, so the fastest and most
     reliable way back in is through the people who manage accounts — not
     email, which usually isn't wired up on a school network. --}}
<div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-lg mb-lg">
    <div class="flex items-start gap-md">
        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary">support_agent</span>
        </div>
        <div>
            <h2 class="font-label-lg text-label-lg font-bold text-on-surface mb-xs">Contact the Guidance Office</h2>
            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                Visit or call the Guidance Office and provide your School ID. A Guidance Counselor or the Super Admin
                will reset your password for you from the User Management module. You'll be asked to set a new
                password the next time you log in.
            </p>
        </div>
    </div>
</div>

@if ($mailConfigured)
    <div class="flex items-center gap-md mb-lg">
        <div class="h-px flex-1 bg-outline-variant"></div>
        <span class="font-label-sm text-label-sm text-outline uppercase tracking-wider">Or reset by email</span>
        <div class="h-px flex-1 bg-outline-variant"></div>
    </div>

    <form class="w-full space-y-lg" method="POST" action="{{ route('password.email') }}">
        @csrf

        <x-ui.input
            label="Email address"
            name="email"
            type="email"
            icon="mail"
            placeholder="you@example.com"
            :value="old('email')"
            autocomplete="email"
            required
            autofocus
        />

        <x-ui.button type="submit" size="lg" variant="secondary" icon="send" class="w-full">
            Email me a reset link
        </x-ui.button>
    </form>
@endif

<div class="mt-xl pt-lg border-t border-outline-variant text-center">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-xs font-label-md text-label-md text-primary hover:text-surface-tint font-bold transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Sign In
    </a>
</div>

</x-layouts.guest>
