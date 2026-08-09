<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreYearLevelRequest;
use App\Http\Requests\Admin\UpdateYearLevelRequest;
use App\Models\AuditLog;
use App\Models\YearLevel;
use App\Services\YearLevelService;
use Illuminate\Http\RedirectResponse;

class YearLevelController extends Controller
{
    public function __construct(protected YearLevelService $yearLevels) {}

    public function store(StoreYearLevelRequest $request): RedirectResponse
    {
        $yearLevel = $this->yearLevels->create($request->validated());

        $this->logAudit('year_level.created', $yearLevel, "Created year level \"{$yearLevel->name}\".");

        return $this->back('Year level added.');
    }

    public function update(UpdateYearLevelRequest $request, YearLevel $yearLevel): RedirectResponse
    {
        $this->yearLevels->update($yearLevel, $request->validated());

        $this->logAudit('year_level.updated', $yearLevel, "Updated year level \"{$yearLevel->name}\".");

        return $this->back('Year level updated.');
    }

    public function toggle(YearLevel $yearLevel): RedirectResponse
    {
        $this->yearLevels->toggle($yearLevel);

        $this->logAudit('year_level.toggled', $yearLevel, ($yearLevel->is_active ? 'Enabled' : 'Disabled') . " year level \"{$yearLevel->name}\".");

        return $this->back($yearLevel->is_active ? 'Year level enabled.' : 'Year level disabled.');
    }

    protected function back(string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.reference-data.index', ['tab' => 'year-levels'])
            ->with('status', $status);
    }

    protected function logAudit(string $action, YearLevel $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => YearLevel::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
