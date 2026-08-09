<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\AuditLog;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;

class CourseController extends Controller
{
    public function __construct(protected CourseService $courses) {}

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->create($request->validated());

        $this->logAudit('course.created', $course, "Created course \"{$course->name}\".");

        return $this->back('Course added.');
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->update($course, $request->validated());

        $this->logAudit('course.updated', $course, "Updated course \"{$course->name}\".");

        return $this->back('Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $this->courses->archive($course);

        $this->logAudit('course.archived', $course, "Archived course \"{$course->name}\".");

        return $this->back('Course archived.');
    }

    public function restore(Course $course): RedirectResponse
    {
        $this->courses->restore($course);

        $this->logAudit('course.restored', $course, "Restored course \"{$course->name}\".");

        return $this->back('Course restored.');
    }

    protected function back(string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.reference-data.index', ['tab' => 'courses'])
            ->with('status', $status);
    }

    protected function logAudit(string $action, Course $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Course::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
