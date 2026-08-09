<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $departments) {}

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = $this->departments->create($request->validated());

        $this->logAudit('department.created', $department, "Created department \"{$department->name}\".");

        return $this->back('Department added.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departments->update($department, $request->validated());

        $this->logAudit('department.updated', $department, "Updated department \"{$department->name}\".");

        return $this->back('Department updated.');
    }

    public function archive(Department $department): RedirectResponse
    {
        $this->departments->archive($department);

        $this->logAudit('department.archived', $department, "Archived department \"{$department->name}\".");

        return $this->back('Department archived.');
    }

    public function restore(Department $department): RedirectResponse
    {
        $this->departments->restore($department);

        $this->logAudit('department.restored', $department, "Restored department \"{$department->name}\".");

        return $this->back('Department restored.');
    }

    protected function back(string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.reference-data.index', ['tab' => 'departments'])
            ->with('status', $status);
    }

    protected function logAudit(string $action, Department $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Department::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
