<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Http\Requests\Admin\UpdateSchoolSettingsRequest;
use App\Models\AuditLog;
use App\Services\SchoolSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected SchoolSettingsService $settings) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'school-info');

        return view('admin.settings.index', [
            'tab' => in_array($tab, ['school-info', 'branding', 'academic-year', 'general'], true) ? $tab : 'school-info',
            'schoolInfo' => $this->settings->current(),
            'branding' => $this->settings->branding(),
        ]);
    }

    public function updateSchoolInfo(UpdateSchoolSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated(), $request->user());

        $this->logAudit('settings.school_info_updated', 'Updated school information settings.');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'school-info'])
            ->with('status', 'School information updated.');
    }

    /**
     * Upload a new school logo and/or browser favicon. Either file is
     * optional so the same form can be used to update just one of them.
     */
    public function updateBranding(UpdateBrandingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->settings->updateLogo($request->file('logo'), $request->user());
        }

        if ($request->hasFile('favicon')) {
            $this->settings->updateFavicon($request->file('favicon'), $request->user());
        }

        $this->logAudit('settings.branding_updated', 'Updated school logo and/or browser favicon.');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'branding'])
            ->with('status', 'Branding updated.');
    }

    public function removeLogo(): RedirectResponse
    {
        $this->settings->removeLogo();

        $this->logAudit('settings.branding_logo_removed', 'Removed the custom school logo (reverted to default branding).');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'branding'])
            ->with('status', 'School logo removed.');
    }

    public function removeFavicon(): RedirectResponse
    {
        $this->settings->removeFavicon();

        $this->logAudit('settings.branding_favicon_removed', 'Removed the custom browser favicon (reverted to default branding).');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'branding'])
            ->with('status', 'Favicon removed.');
    }

    protected function logAudit(string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => null,
            'subject_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
