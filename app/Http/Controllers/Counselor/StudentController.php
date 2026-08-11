<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\ResetStudentPasswordRequest;
use App\Http\Requests\Counselor\StoreStudentRequest;
use App\Http\Requests\Counselor\UpdateStudentRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Models\YearLevel;
use App\Notifications\PasswordChanged;
use App\Notifications\StudentImported;
use App\Services\StudentRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(protected StudentRecordService $records) {}

    /**
     * Enrollment status options shared by filters and forms.
     */
    public const ENROLLMENT_STATUSES = [
        'enrolled' => 'Enrolled',
        'on_leave' => 'On Leave',
        'graduated' => 'Graduated',
        'dropped' => 'Dropped',
    ];

    /**
     * Student Directory — list, search, filter, paginate.
     */
    public function index(Request $request): View
    {
        $students = $this->filteredQuery($request)->paginate(10)->withQueryString();

        $stats = [
            'total' => User::where('role', UserRole::Student)->count(),
            'new_this_month' => User::where('role', UserRole::Student)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'active' => User::where('role', UserRole::Student)->where('status', UserStatus::Active)->count(),
            'archived' => User::onlyTrashed()->where('role', UserRole::Student)->count(),
        ];

        $departments = Department::orderBy('name')->get();

        return view('counselor.students.index', [
            'students' => $students,
            'stats' => $stats,
            'departments' => $departments,
            'enrollmentStatuses' => self::ENROLLMENT_STATUSES,
            'filters' => $request->only(['search', 'department_id', 'year_level', 'status']),
        ]);
    }

    /**
     * Export the (filtered) student directory as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $students = $this->filteredQuery($request)->orderBy('name')->get();

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'School ID', 'Email', 'Department', 'Year Level', 'Section', 'Enrollment Status', 'Account Status', 'Exams Taken']);

            foreach ($students as $student) {
                fputcsv($out, [
                    $student->name,
                    $student->school_id,
                    $student->email,
                    $student->department?->name,
                    $student->studentProfile?->year_level,
                    $student->studentProfile?->section,
                    self::ENROLLMENT_STATUSES[$student->studentProfile?->enrollment_status] ?? '',
                    $student->trashed() ? 'Archived' : ucfirst($student->status->value),
                    $student->exam_sessions_count,
                ]);
            }
            fclose($out);
        }, 'students-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('counselor.students.create', [
            'departments' => Department::orderBy('name')->get(),
            'yearLevels' => YearLevel::where('is_active', true)->get(),
            'enrollmentStatuses' => self::ENROLLMENT_STATUSES,
        ]);
    }

    /**
     * Register a new student (creates the User + StudentProfile together).
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $student = DB::transaction(function () use ($data) {
            $student = User::create([
                'school_id' => $data['school_id'],
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => UserRole::Student,
                'department_id' => $data['department_id'] ?? null,
                'status' => UserStatus::Active,
            ]);

            $student->studentProfile()->create([
                'year_level' => $data['year_level'] ?? null,
                'section' => $data['section'] ?? null,
                'enrollment_status' => $data['enrollment_status'] ?? 'enrolled',
            ]);

            return $student;
        });

        $this->logAudit('student.created', $student, "Created student profile for {$student->name} ({$student->school_id}).");

        User::where('role', UserRole::SuperAdmin)->get()->each->notify(new StudentImported($student));

        return redirect()
            ->route('counselor.students.index')
            ->with('status', "{$student->name} was added successfully.");
    }

    /**
     * View a single student's profile.
     */
    public function show(Request $request, User $student): View
    {
        $this->ensureIsStudent($student);

        $student->load(['studentProfile', 'department']);

        $recentActivity = AuditLog::where('subject_type', User::class)
            ->where('subject_id', $student->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $counselingNotes = $student->counselingNotes()
            ->with(['counselor', 'revisions.editor'])
            ->latest('note_date')
            ->latest('created_at')
            ->get();

        return view('counselor.students.show', [
            'student' => $student,
            'recentActivity' => $recentActivity,
            'examHistory' => $this->records->examHistory($student),
            'assessment' => $this->records->assessmentSummary($student),
            'counselingNotes' => $counselingNotes,
            'activeTab' => $request->query('tab', 'personal'),
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(User $student): View
    {
        $this->ensureIsStudent($student);

        $student->load('studentProfile');

        return view('counselor.students.edit', [
            'student' => $student,
            'departments' => Department::orderBy('name')->get(),
            'yearLevels' => YearLevel::where('is_active', true)->get(),
            'enrollmentStatuses' => self::ENROLLMENT_STATUSES,
        ]);
    }

    /**
     * Update an existing student.
     */
    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureIsStudent($student);

        $data = $request->validated();

        DB::transaction(function () use ($data, $student) {
            $student->fill([
                'name' => $data['name'],
                'school_id' => $data['school_id'],
                'email' => $data['email'] ?? null,
                'status' => $data['status'],
                'department_id' => $data['department_id'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $student->password = Hash::make($data['password']);
            }

            $student->save();

            $student->studentProfile()->updateOrCreate(
                ['user_id' => $student->id],
                [
                    'year_level' => $data['year_level'] ?? null,
                    'section' => $data['section'] ?? null,
                    'enrollment_status' => $data['enrollment_status'] ?? 'enrolled',
                ]
            );
        });

        $this->logAudit('student.updated', $student, "Updated profile details for {$student->name} ({$student->school_id}).");

        return redirect()
            ->route('counselor.students.index')
            ->with('status', "{$student->name}'s profile was updated.");
    }

    /**
     * Reset a student's password. Available to both Counselors and the Super
     * Admin (an explicit exception to the Super Admin's usual view-only
     * access here) since either can act as the Guidance Office contact
     * point for a student's "Forgot Password" request. The student is
     * forced to change it again on their next login.
     */
    public function resetPassword(ResetStudentPasswordRequest $request, User $student): RedirectResponse
    {
        $this->ensureIsStudent($student);

        $student->forceFill([
            'password' => Hash::make($request->validated()['password']),
            'password_changed_at' => now(),
            'must_change_password' => true,
        ])->save();

        $this->logAudit('student.password_reset', $student, "Reset password for {$student->name} ({$student->school_id}).");

        $student->notify(new PasswordChanged());

        return redirect()
            ->route('counselor.students.show', $student)
            ->with('status', "{$student->name}'s password was reset. They'll be asked to set a new one on their next login.");
    }

    /**
     * Archive a student (soft delete). Preserves all historical data.
     */
    public function archive(User $student): RedirectResponse
    {
        $this->ensureIsStudent($student);

        $student->delete();

        $this->logAudit('student.archived', $student, "Archived student profile for {$student->name} ({$student->school_id}).");

        return redirect()
            ->route('counselor.students.index')
            ->with('status', "{$student->name} was archived.");
    }

    /**
     * Restore a previously archived student.
     */
    public function restore(User $student): RedirectResponse
    {
        $this->ensureIsStudent($student);

        $student->restore();

        $this->logAudit('student.restored', $student, "Restored student profile for {$student->name} ({$student->school_id}).");

        return redirect()
            ->route('counselor.students.index')
            ->with('status', "{$student->name} was restored.");
    }

    /**
     * Guard against operating on a non-student user via this controller.
     */
    protected function ensureIsStudent(User $student): void
    {
        abort_unless($student->role === UserRole::Student, 404);
    }

    protected function logAudit(string $action, User $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => User::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Shared filtered query behind both the paginated directory (index) and
     * the CSV export, so the two can never drift out of sync.
     */
    protected function filteredQuery(Request $request)
    {
        $query = User::query()
            ->where('role', UserRole::Student)
            ->with(['studentProfile', 'department'])
            ->withCount('examSessions');

        $status = $request->query('status');

        if ($status === 'archived') {
            $query->onlyTrashed();
        } elseif (in_array($status, ['active', 'inactive', 'suspended'], true)) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($yearLevel = $request->query('year_level')) {
            $query->whereHas('studentProfile', fn ($q) => $q->where('year_level', $yearLevel));
        }

        return $query;
    }
}
