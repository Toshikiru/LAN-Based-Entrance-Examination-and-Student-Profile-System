{{--
    Shared <head> partial included by every layout.
    This is the single source of truth for the GuidancePulse design system:
    - Design tokens (colors, spacing, radius, typography) live in resources/css/app.css
      (light + dark theme values as CSS custom properties, so every Tailwind color utility
      re-themes automatically when the `dark` class is toggled on <html> — no `dark:` prefix
      needed anywhere in the components).
    - Fonts (Inter), icons (Material Symbols), Turbo, Alpine.js, Chart.js, and SortableJS are all
      bundled locally via Vite (resources/css/app.css, resources/js/app.js) and self-hosted —
      this app runs on a LAN with no internet access, so nothing here may depend on an external
      CDN. `npm run build` must be re-run after editing either file.
--}}

{{-- Inline theme script MUST run before Tailwind paints, to avoid a flash of the wrong theme --}}
<script>
    (function () {
        var stored = localStorage.getItem('guidancepulse-theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var isDark = stored ? stored === 'dark' : prefersDark;
        document.documentElement.classList.toggle('dark', isDark);

        // Single source of truth for "does this browser support the native
        // View Transitions API" — both the CSS fallback fade below and the
        // Turbo transition script at the bottom of this file key off this
        // same class instead of each doing their own feature detection, so
        // the two can never disagree and fire at once (a double-animation,
        // visible as a flicker/flash on navigation).
        document.documentElement.classList.toggle('vt-supported', !!document.startViewTransition);
    })();
</script>

{{-- Custom favicon if one was uploaded on the Branding settings tab, otherwise the app's default. --}}
<link rel="icon" href="{{ $branding['favicon_url'] ?? asset('favicon.ico') }}"/>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    // App-wide submit feedback: visually dims the clicked submit button and
    // blocks a second click, on every form in the app, with no markup
    // changes required per-page. Deliberately does NOT set the `disabled`
    // attribute — disabling a submit button synchronously during its own
    // `submit` event can drop that button's name/value pair from the
    // request in some browsers, which would break server-side logic that
    // reads it (e.g. the exam runner's nav="prev|next|save|submit"). Adding
    // the dimmed state a frame later, after the browser has already
    // captured the submission, avoids that risk entirely.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) {
            return;
        }

        // Several forms (exam submission, session termination, database
        // restore) guard themselves with `onsubmit="return confirm(...)"`.
        // That handler runs before this one (it's attached directly to the
        // form, which fires first as the event bubbles up to `document`),
        // and calling `event.preventDefault()` — which is exactly what a
        // cancelled confirm() dialog does — doesn't stop the event from
        // still reaching this listener. Without this check, cancelling the
        // dialog once would dim and permanently disable the submit button
        // with no page reload to reset it (e.g. the exam runner's "Submit
        // Exam" button would become unusable for the rest of the attempt).
        if (event.defaultPrevented) {
            return;
        }

        var button = event.submitter || form.querySelector('button[type="submit"]');
        if (!button) {
            return;
        }

        if (button.classList.contains('is-submitting')) {
            event.preventDefault();
            return;
        }

        requestAnimationFrame(function () {
            button.classList.add('is-submitting', 'opacity-60', 'pointer-events-none');
        });
    });
</script>

<script>
    // ---- Turbo Drive navigation support -------------------------------
    // The sidebar (`components/navigation/sidebar.blade.php`) is marked
    // `data-turbo-permanent`, so its DOM node — and any Alpine state on
    // it — survives every Turbo visit unchanged. The mobile open/close
    // toggle therefore can't live in a plain `x-data` on that node (or on
    // `<body>`, which Turbo replaces on every visit); it lives in a global
    // Alpine store instead, which persists in memory across visits
    // regardless of which DOM nodes get swapped.
    document.addEventListener('alpine:init', function () {
        Alpine.store('sidebar', { open: false });
    });

    // Auto-close the mobile drawer after any completed navigation — the
    // normal expectation when tapping a link in an off-canvas nav menu.
    document.addEventListener('turbo:load', function () {
        if (window.Alpine && Alpine.store('sidebar')) {
            Alpine.store('sidebar').open = false;
        }
    });

    // Because the sidebar is permanent, its "active" link never gets
    // re-rendered by the server after the first load. Every layout prints
    // the current page's nav key into a <meta> tag (which — unlike the
    // permanent sidebar — *does* get refreshed on every visit, since head
    // merging is independent of body permanence); this re-applies the
    // active styling to match on every navigation, using exactly the
    // classes the server renders for the active/inactive states so the
    // two never drift apart.
    (function () {
        // Mirrors the two class-list branches in sidebar.blade.php's @@class([...]) exactly.
        var ACTIVE_CLASSES = ['border-l-4', 'border-primary', 'bg-primary-fixed', 'text-on-primary-fixed', 'font-bold'];
        var INACTIVE_CLASSES = ['text-on-surface-variant', 'hover:bg-surface-container-high', 'hover:translate-x-1'];

        function syncActiveNav() {
            var meta = document.querySelector('meta[name="nav-active"]');
            var active = meta ? meta.content : '';

            document.querySelectorAll('[data-nav-key]').forEach(function (el) {
                var isActive = active !== '' && el.dataset.navKey === active;
                var icon = el.querySelector('.material-symbols-outlined');

                ACTIVE_CLASSES.forEach(function (cls) { el.classList.toggle(cls, isActive); });
                INACTIVE_CLASSES.forEach(function (cls) { el.classList.toggle(cls, !isActive); });
                if (icon) { icon.classList.toggle('fill-icon', isActive); }
            });
        }

        document.addEventListener('turbo:load', syncActiveNav);
    })();
</script>

<script>
    // Route Turbo's rendering through the native View Transitions API where
    // supported, so page changes get a real browser-driven crossfade/slide
    // instead of an instant swap. `#app-sidebar` and `#app-topnav` are
    // scoped out of this transition via CSS (see resources/css/app.css),
    // so only the main content area actually animates.
    //
    // This is official, documented Turbo behavior: `turbo:before-render` is
    // cancelable — calling `preventDefault()` pauses Turbo immediately
    // before it swaps the page, and `event.detail.resume()` is the hook
    // that continues that paused render. Wrapping `resume()` inside
    // `document.startViewTransition()` lets the browser capture the
    // before/after snapshots around Turbo's own render step.
    //
    // `activeTransition` guards against rapid navigation (clicking a second
    // nav link before the first transition finishes): starting a second
    // `startViewTransition()` while one is already running forces the
    // browser to abort the first mid-flight, which is visible as a flash.
    // While one is in flight, later renders are resumed immediately instead
    // — no animation for that render, but no jarring abort either.
    var activeTransition = null;

    document.addEventListener('turbo:before-render', function (event) {
        if (!document.startViewTransition || activeTransition || document.hidden) {
            return; // unsupported browser, transition already running, or tab backgrounded
        }

        event.preventDefault();
        try {
            activeTransition = document.startViewTransition(function () {
                return event.detail.resume();
            });
        } catch (err) {
            activeTransition = null;
            event.detail.resume();
            return;
        }
        // `ready`/`finished` reject with InvalidStateError when the browser
        // skips or aborts the transition (e.g. tab backgrounded mid-flight);
        // that's expected here, so swallow it instead of letting it surface
        // as an unhandled promise rejection.
        activeTransition.ready.catch(function () {});
        activeTransition.finished.catch(function () {}).finally(function () {
            activeTransition = null;
        });
    });
</script>
