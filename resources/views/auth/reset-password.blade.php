<x-layouts.guest :title="$branding['system_name'] . ' | Reset Password'">

<div class="mb-xl">
    <h1 class="font-headline-lg text-headline-lg font-bold text-on-surface mb-xs">Set a new password</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">Choose a new password for your account.</p>
</div>

@if ($errors->any())
    <x-ui.alert variant="error" class="w-full mb-lg">{{ $errors->first() }}</x-ui.alert>
@endif

<form class="w-full space-y-lg" method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <x-ui.input
        label="Email address"
        name="email"
        type="email"
        icon="mail"
        placeholder="you@example.com"
        :value="old('email', $email)"
        autocomplete="email"
        required
        autofocus
    />

    <x-ui.input
        label="New Password"
        name="password"
        type="password"
        icon="lock"
        placeholder="Minimum 8 characters"
        autocomplete="new-password"
        required
    />

    <x-ui.input
        label="Confirm New Password"
        name="password_confirmation"
        type="password"
        icon="lock"
        placeholder="Re-enter new password"
        autocomplete="new-password"
        required
    />

    <x-ui.button type="submit" size="lg" variant="primary" icon="check" class="w-full">
        Reset Password
    </x-ui.button>
</form>

<div class="mt-xl pt-lg border-t border-outline-variant text-center">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-xs font-label-md text-label-md text-primary hover:text-surface-tint font-bold transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Sign In
    </a>
</div>

</x-layouts.guest>
