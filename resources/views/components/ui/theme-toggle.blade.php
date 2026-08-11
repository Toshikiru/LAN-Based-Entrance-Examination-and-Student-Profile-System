{{--
    Toggles the `dark` class on <html> and persists the choice.
    The inline script in partials/theme-head.blade.php reads this same key on next page load
    to avoid a flash of the wrong theme.
--}}
<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    x-on:click="
        dark = !dark;
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('tpc-entrypoint-theme', dark ? 'dark' : 'light');
    "
    {{ $attributes->class(['p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full transition-colors']) }}
    aria-label="Toggle dark mode"
>
    <span class="material-symbols-outlined" x-show="!dark" x-cloak>dark_mode</span>
    <span class="material-symbols-outlined" x-show="dark" x-cloak>light_mode</span>
</button>
