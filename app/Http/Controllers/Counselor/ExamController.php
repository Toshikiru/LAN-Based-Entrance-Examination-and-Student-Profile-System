<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\ExamCategory;
use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreExamRequest;
use App\Http\Requests\Counselor\UpdateExamRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Year-level options an exam can target.
     */
    public const YEAR_LEVELS = [
        'Freshman' => 'Freshman',
        'Sophomore' => 'Sophomore',
        'Junior' => 'Junior',
        'Senior' => 'Senior',
        'Graduate' => 'Graduate',
        'Transferee' => 'Transferee',
    ];

    /**
     * Examination list — search, filter, paginate.
     */
    public function index(Request $request): View
    {
        $query = Exam::query()->with('department');

        if ($status = $request->query('status')) {
            if (in_array($status, array_column(ExamStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($yearLevel = $request->query('year_level')) {
            $query->where('year_level', $yearLevel);
        }

        $exams = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => Exam::count(),
            'active' => Exam::where('status', ExamStatus::Published)->count(),
            'draft' => Exam::where('status', ExamStatus::Draft)->count(),
            'closed' => Exam::where('status', ExamStatus::Closed)->count(),
        ];

        return view('counselor.exams.index', [
            'exams' => $exams,
            'stats' => $stats,
            'departments' => Department::orderBy('name')->get(),
            'yearLevels' => self::YEAR_LEVELS,
            'statuses' => ExamStatus::cases(),
            'filters' => $request->only(['search', 'status', 'department_id', 'year_level']),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('counselor.exams.create', [
            'departments' => Department::orderBy('name')->get(),
            'yearLevels' => self::YEAR_LEVELS,
        ]);
    }

    /**
     * Store a new examination.
     */
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $exam = Exam::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => ExamCategory::Entrance,
            'department_id' => $data['department_id'] ?? null,
            'year_level' => $data['year_level'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'status' => ExamStatus::Draft,
            'created_by' => auth()->id(),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $this->logAudit('exam.created', $exam, "Created examination \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.index')
            ->with('status', "\"{$exam->title}\" was created as a draft.");
    }

    /**
     * Show the edit form.
     */
    public function edit(Exam $exam): View
    {
        return view('counselor.exams.edit', [
            'exam' => $exam,
            'departments' => Department::orderBy('name')->get(),
            'yearLevels' => self::YEAR_LEVELS,
        ]);
    }

    /**
     * Update an examination.
     */
    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $data = $request->validated();

        $exam->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'year_level' => $data['year_level'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $this->logAudit('exam.updated', $exam, "Updated examination \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.index')
            ->with('status', "\"{$exam->title}\" was updated.");
    }

    /**
     * Delete (soft delete) an examination.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $title = $exam->title;

        $exam->delete();

        $this->logAudit('exam.deleted', $exam, "Deleted examination \"{$title}\".");

        return redirect()
            ->route('counselor.exams.index')
            ->with('status', "\"{$title}\" was deleted.");
    }

    /**
     * Activate an examination (make it available to students).
     */
    public function activate(Exam $exam): RedirectResponse
    {
        $exam->update(['status' => ExamStatus::Published]);

        $this->logAudit('exam.activated', $exam, "Activated examination \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.index')
            ->with('status', "\"{$exam->title}\" is now active.");
    }

    /**
     * Deactivate an examination (return it to draft, blocking access).
     */
    public function deactivate(Exam $exam): RedirectResponse
    {
        $exam->update(['status' => ExamStatus::Draft]);

        $this->logAudit('exam.deactivated', $exam, "Deactivated examination \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.index')
            ->with('status', "\"{$exam->title}\" was deactivated.");
    }

    protected function logAudit(string $action, Exam $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Exam::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
