<?php

namespace App\Providers;

use App\Services\SchoolSettingsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->shareBrandingWithViews();
        $this->syncFrameworkBrandingDefaults();
    }

    /**
     * Makes $branding (school name, system name, system full name, logo/
     * favicon URLs) available to every view — the layouts, the login page,
     * print/PDF exports, and the favicon <link> in theme-head.blade.php all
     * read from this single place, so updating System Settings is reflected
     * everywhere without touching any of those files again. Registered as a
     * composer (not View::share) so it only runs when a view is actually
     * about to render, not on every request.
     */
    protected function shareBrandingWithViews(): void
    {
        View::composer('*', function ($view) {
            try {
                $view->with('branding', app(SchoolSettingsService::class)->branding());
            } catch (\Throwable $e) {
                // Never let a settings lookup (e.g. DB unreachable, table not
                // yet migrated) break every page in the app — fall back to
                // the original hardcoded branding instead.
                $view->with('branding', [
                    'school_name' => SchoolSettingsService::DEFAULT_SCHOOL_NAME,
                    'system_name' => SchoolSettingsService::DEFAULT_SYSTEM_NAME,
                    'system_full_name' => SchoolSettingsService::DEFAULT_SYSTEM_FULL_NAME,
                    'logo_url' => null,
                    'favicon_url' => null,
                ]);
            }
        });
    }

    /**
     * Laravel's own framework internals — the default password-reset email's
     * signature/footer, in particular — read config('app.name') directly
     * rather than anything in $branding. Overriding it here at boot time
     * (once per request, before any mail can be dispatched) keeps that email
     * in sync with the System Name set in Settings, without needing a custom
     * mail template. This can't live in the view composer above: sending the
     * reset-link email happens on a redirect-only request that never renders
     * a Blade view, so that composer would never fire in time.
     */
    protected function syncFrameworkBrandingDefaults(): void
    {
        try {
            $systemName = app(SchoolSettingsService::class)->branding()['system_name'];

            config([
                'app.name' => $systemName,
                'mail.from.name' => $systemName,
            ]);
        } catch (\Throwable $e) {
            // DB unreachable / not migrated yet (e.g. during `artisan migrate`
            // itself) — leave config() at its .env-derived default.
        }
    }
}
