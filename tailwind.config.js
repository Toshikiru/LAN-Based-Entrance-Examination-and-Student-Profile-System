/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                'tertiary-fixed': 'var(--color-tertiary-fixed)',
                'outline-variant': 'var(--color-outline-variant)',
                'primary-fixed': 'var(--color-primary-fixed)',
                background: 'var(--color-background)',
                'surface-dim': 'var(--color-surface-dim)',
                outline: 'var(--color-outline)',
                'on-secondary-fixed-variant': 'var(--color-on-secondary-fixed-variant)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'on-background': 'var(--color-on-background)',
                'on-error': 'var(--color-on-error)',
                'primary-container': 'var(--color-primary-container)',
                'on-tertiary': 'var(--color-on-tertiary)',
                'on-primary': 'var(--color-on-primary)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                'inverse-surface': 'var(--color-inverse-surface)',
                'primary-fixed-dim': 'var(--color-primary-fixed-dim)',
                'on-tertiary-fixed-variant': 'var(--color-on-tertiary-fixed-variant)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'on-surface': 'var(--color-on-surface)',
                'tertiary-container': 'var(--color-tertiary-container)',
                'on-error-container': 'var(--color-on-error-container)',
                primary: 'var(--color-primary)',
                tertiary: 'var(--color-tertiary)',
                'tertiary-fixed-dim': 'var(--color-tertiary-fixed-dim)',
                'secondary-container': 'var(--color-secondary-container)',
                'surface-tint': 'var(--color-surface-tint)',
                secondary: 'var(--color-secondary)',
                error: 'var(--color-error)',
                'on-secondary-container': 'var(--color-on-secondary-container)',
                'surface-container-high': 'var(--color-surface-container-high)',
                'secondary-fixed-dim': 'var(--color-secondary-fixed-dim)',
                'secondary-fixed': 'var(--color-secondary-fixed)',
                'on-primary-fixed-variant': 'var(--color-on-primary-fixed-variant)',
                'on-primary-container': 'var(--color-on-primary-container)',
                'inverse-primary': 'var(--color-inverse-primary)',
                'on-tertiary-container': 'var(--color-on-tertiary-container)',
                'inverse-on-surface': 'var(--color-inverse-on-surface)',
                'error-container': 'var(--color-error-container)',
                'on-primary-fixed': 'var(--color-on-primary-fixed)',
                'on-secondary': 'var(--color-on-secondary)',
                'on-surface-variant': 'var(--color-on-surface-variant)',
                'on-secondary-fixed': 'var(--color-on-secondary-fixed)',
                'surface-variant': 'var(--color-surface-variant)',
                'on-tertiary-fixed': 'var(--color-on-tertiary-fixed)',
                'surface-bright': 'var(--color-surface-bright)',
                surface: 'var(--color-surface)',
            },
            borderRadius: {
                DEFAULT: '0.25rem',
                lg: '0.5rem',
                xl: '0.75rem',
                full: '9999px',
            },
            // NOTE: the `sm`/`md`/`lg`/`xl` keys in `spacing` below (Material
            // spacing values, used for padding/margin/gap e.g. `p-sm`,
            // `gap-lg`) collide by name with Tailwind's own default
            // `sm`/`md`/`lg`/`xl` max-width scale. Under Tailwind v4 (loaded
            // here via @config), `max-w-<key>` is derived directly from a
            // same-named `spacing` key when one exists — it never falls back
            // to (or even references) `--container-*` in that case — so
            // `max-w-sm`/`max-w-md`/`max-w-lg`/`max-w-xl` (used in ~17 views)
            // would otherwise collapse to a few px wide instead of
            // 24/28/32/36rem. A JS `theme.extend.maxWidth` override here has
            // no effect on that (confirmed by testing), and neither does a v4
            // `@theme` block redefining `--container-*` in app.css, since
            // those utilities never consult it once the spacing key wins.
            // It's fixed with a plain (non-`@layer`) CSS override in
            // resources/css/app.css instead — see the comment there.
            spacing: {
                lg: '24px',
                xl: '40px',
                sm: '8px',
                xs: '4px',
                gutter: '20px',
                md: '16px',
                'container-margin': '32px',
                base: '4px',
            },
            fontFamily: {
                // "Inter Variable" is the family name registered by the locally
                // bundled @fontsource-variable/inter package (see resources/css/app.css).
                'label-sm': ['Inter Variable', 'sans-serif'],
                'body-md': ['Inter Variable', 'sans-serif'],
                'headline-lg': ['Inter Variable', 'sans-serif'],
                'label-md': ['Inter Variable', 'sans-serif'],
                'display-lg': ['Inter Variable', 'sans-serif'],
                'body-lg': ['Inter Variable', 'sans-serif'],
                'headline-md': ['Inter Variable', 'sans-serif'],
                'display-lg-mobile': ['Inter Variable', 'sans-serif'],
            },
            fontSize: {
                'label-sm': ['12px', { lineHeight: '16px', letterSpacing: '0.05em', fontWeight: '600' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'headline-lg': ['30px', { lineHeight: '38px', letterSpacing: '-0.01em', fontWeight: '600' }],
                'label-md': ['14px', { lineHeight: '20px', fontWeight: '500' }],
                'display-lg': ['48px', { lineHeight: '1.1', letterSpacing: '-0.02em', fontWeight: '700' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'headline-md': ['24px', { lineHeight: '32px', fontWeight: '600' }],
                'display-lg-mobile': ['36px', { lineHeight: '1.2', fontWeight: '700' }],
            },
        },
    },
};
