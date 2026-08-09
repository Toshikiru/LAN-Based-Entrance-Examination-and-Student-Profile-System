<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreCounselingNoteRequest;
use App\Http\Requests\Counselor\UpdateCounselingNoteRequest;
use App\Models\AuditLog;
use App\Models\CounselingNote;
use App\Models\User;
use App\Services\CounselingNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CounselingNoteController extends Controller
{
    public function __construct(protected CounselingNoteService $notes) {}

    /**
     * Read-only oversight list of all counseling notes across every
     * student, for Guidance Counselors and the Super Admin alike. Authoring
     * a note still only happens from the student's own profile page.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CounselingNote::class);

        $query = CounselingNote::query()->with(['student', 'counselor']);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('student', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('school_id', 'like', "%{$search}%"));
        }

        $notes = $query->latest('note_date')->latest('created_at')->paginate(15)->withQueryString();

        return view('counselor.counseling-notes.index', [
            'notes' => $notes,
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function store(StoreCounselingNoteRequest $request, User $student): RedirectResponse
    {
        Gate::authorize('create', CounselingNote::class);
        $this->ensureIsStudent($student);

        $note = $this->notes->create($student, $request->user(), $request->validated());

        $this->logAudit('counseling_note.created', $note, "Added a counseling note for {$student->name}.");

        return redirect()
            ->route('counselor.students.show', ['student' => $student, 'tab' => 'counseling'])
            ->with('status', 'Counseling note added.');
    }

    public function update(UpdateCounselingNoteRequest $request, User $student, CounselingNote $counselingNote): RedirectResponse
    {
        Gate::authorize('update', $counselingNote);
        $this->ensureIsStudent($student);
        abort_unless($counselingNote->student_id === $student->id, 404);

        $this->notes->update($counselingNote, $request->user(), $request->validated());

        $this->logAudit('counseling_note.updated', $counselingNote, "Updated a counseling note for {$student->name}.");

        return redirect()
            ->route('counselor.students.show', ['student' => $student, 'tab' => 'counseling'])
            ->with('status', 'Counseling note updated.');
    }

    protected function ensureIsStudent(User $student): void
    {
        abort_unless($student->role === UserRole::Student, 404);
    }

    protected function logAudit(string $action, CounselingNote $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => CounselingNote::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
