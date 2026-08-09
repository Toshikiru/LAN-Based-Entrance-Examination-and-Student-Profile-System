<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\ExamStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ExaminationAssigned;
use App\Notifications\ExaminationPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamBuilderController extends Controller
{
    /**
     * The builder canvas: sections, attached questions, live summary.
     */
    public function builder(Exam $exam): View
    {
        $exam->load([
            'sections',
            'examQuestions' => fn ($q) => $q->orderBy('order'),
            'examQuestions.question.options',
            'examQuestions.examSection',
        ]);

        return view('counselor.exams.builder', [
            'exam' => $exam,
            'summary' => $this->summary($exam),
        ]);
    }

    /**
     * Question Bank selector — search, filter, multi-select questions to add.
     */
    public function questionPicker(Request $request, Exam $exam): View
    {
        $attachedIds = $exam->examQuestions()->pluck('question_id')->all();

        $query = Question::query()
            ->where('status', QuestionStatus::Active)
            ->whereNotIn('id', $attachedIds)
            ->withCount('options');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('question_text', 'like', "%{$search}%");
        }
        if ($category = $request->query('category')) {
            $query->where('section_category', $category);
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($difficulty = $request->query('difficulty')) {
            $query->where('difficulty', $difficulty);
        }

        $questions = $query->latest()->paginate(10)->withQueryString();

        $categories = Question::whereNotNull('section_category')->distinct()->orderBy('section_category')->pluck('section_category');

        return view('counselor.exams.questions-select', [
            'exam' => $exam,
            'questions' => $questions,
            'categories' => $categories,
            'types' => QuestionType::cases(),
            'difficulties' => \App\Enums\QuestionDifficulty::cases(),
            'sections' => $exam->sections,
            'filters' => $request->only(['search', 'category', 'type', 'difficulty', 'section_id']),
        ]);
    }

    /**
     * Attach the selected bank questions to the exam.
     */
    public function attachQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
            'exam_section_id' => ['nullable', 'integer'],
        ]);

        $sectionId = $this->validSectionId($exam, $data['exam_section_id'] ?? null);
        $existing = $exam->examQuestions()->pluck('question_id')->all();
        $order = (int) $exam->examQuestions()->max('order');

        $added = 0;
        foreach ($data['question_ids'] as $questionId) {
            if (in_array((int) $questionId, $existing, true)) {
                continue;
            }
            $order++;
            $exam->examQuestions()->create([
                'question_id' => $questionId,
                'exam_section_id' => $sectionId,
                'order' => $order,
            ]);
            $added++;
        }

        $this->logAudit('exam.questions_added', $exam, "Added {$added} question(s) to \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.builder', $exam)
            ->with('status', "{$added} question(s) added.");
    }

    /**
     * Remove a question from the exam.
     */
    public function detachQuestion(Exam $exam, ExamQuestion $examQuestion): RedirectResponse
    {
        abort_unless($examQuestion->exam_id === $exam->id, 404);

        $examQuestion->delete();

        return redirect()
            ->route('counselor.exams.builder', $exam)
            ->with('status', 'Question removed from the exam.');
    }

    /**
     * Persist a new question order (array of exam_question ids in order).
     */
    public function reorder(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $owned = $exam->examQuestions()->pluck('id')->all();

        DB::transaction(function () use ($data, $owned, $exam) {
            $position = 0;
            foreach ($data['order'] as $examQuestionId) {
                if (! in_array((int) $examQuestionId, $owned, true)) {
                    continue;
                }
                $position++;
                ExamQuestion::where('id', $examQuestionId)
                    ->where('exam_id', $exam->id)
                    ->update(['order' => $position]);
            }
        });

        return redirect()
            ->route('counselor.exams.builder', $exam)
            ->with('status', 'Question order saved.');
    }

    public function storeSection(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $exam->sections()->create([
            'title' => $data['title'],
            'order' => (int) $exam->sections()->max('order') + 1,
        ]);

        return redirect()
            ->route('counselor.exams.builder', $exam)
            ->with('status', 'Section added.');
    }

    public function destroySection(Exam $exam, ExamSection $section): RedirectResponse
    {
        abort_unless($section->exam_id === $exam->id, 404);

        // Detach questions from the section rather than deleting them.
        $exam->examQuestions()->where('exam_section_id', $section->id)->update(['exam_section_id' => null]);
        $section->delete();

        return redirect()
            ->route('counselor.exams.builder', $exam)
            ->with('status', 'Section removed.');
    }

    public function settings(Exam $exam): View
    {
        return view('counselor.exams.settings', ['exam' => $exam]);
    }

    public function updateSettings(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_choices' => ['nullable', 'boolean'],
            'auto_submit' => ['nullable', 'boolean'],
            'show_score' => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
            'allow_resume' => ['nullable', 'boolean'],
        ]);

        $exam->update([
            'passing_score' => $data['passing_score'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
            'shuffle_choices' => $request->boolean('shuffle_choices'),
            'auto_submit' => $request->boolean('auto_submit'),
            'show_score' => $request->boolean('show_score'),
            'show_correct_answers' => $request->boolean('show_correct_answers'),
            'allow_resume' => $request->boolean('allow_resume'),
        ]);

        $this->logAudit('exam.settings_updated', $exam, "Updated settings for \"{$exam->title}\".");

        return redirect()
            ->route('counselor.exams.settings', $exam)
            ->with('status', 'Settings saved.');
    }

    public function preview(Exam $exam): View
    {
        $exam->load(['examQuestions' => fn ($q) => $q->orderBy('order'), 'examQuestions.question.options']);

        return view('counselor.exams.preview', [
            'exam' => $exam,
            'summary' => $this->summary($exam),
        ]);
    }

    public function publishForm(Exam $exam): View
    {
        $exam->load('department');

        return view('counselor.exams.publish', [
            'exam' => $exam,
            'summary' => $this->summary($exam),
        ]);
    }

    public function publish(Exam $exam): RedirectResponse
    {
        if ($exam->examQuestions()->count() === 0) {
            return redirect()
                ->route('counselor.exams.builder', $exam)
                ->with('error', 'Add at least one question before publishing.');
        }

        $exam->update([
            'status' => ExamStatus::Published,
            'access_code' => $exam->access_code ?: $this->uniqueAccessCode(),
        ]);

        $this->logAudit('exam.published', $exam, "Published \"{$exam->title}\" (code {$exam->access_code}).");

        $exam->creator?->notify(new ExaminationPublished($exam));
        $this->targetedStudents($exam)->each->notify(new ExaminationAssigned($exam));

        return redirect()
            ->route('counselor.exams.publish', $exam)
            ->with('status', "\"{$exam->title}\" is now published. Access code: {$exam->access_code}");
    }

    /**
     * Students eligible for this exam based on its department/year-level targeting
     * (null on either field means "open to everyone" for that dimension).
     */
    protected function targetedStudents(Exam $exam)
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->when($exam->department_id, fn ($q) => $q->where('department_id', $exam->department_id))
            ->when($exam->year_level, fn ($q) => $q->whereHas(
                'studentProfile',
                fn ($q) => $q->where('year_level', $exam->year_level)
            ))
            ->get();
    }

    public function generateAccessCode(Exam $exam): RedirectResponse
    {
        $exam->update(['access_code' => $this->uniqueAccessCode()]);

        return back()->with('status', "New access code generated: {$exam->access_code}");
    }

    // ---- Helpers ----

    protected function summary(Exam $exam): array
    {
        $examQuestions = $exam->relationLoaded('examQuestions')
            ? $exam->examQuestions
            : $exam->examQuestions()->with('question')->get();

        $totalPoints = $examQuestions->sum(function (ExamQuestion $eq) {
            return (float) ($eq->marks_override ?? $eq->question?->marks ?? 0);
        });

        $breakdown = $examQuestions
            ->groupBy(fn (ExamQuestion $eq) => $eq->question?->type?->label() ?? 'Unknown')
            ->map->count();

        return [
            'total_questions' => $examQuestions->count(),
            'total_points' => $totalPoints,
            'duration' => $exam->duration_minutes,
            'breakdown' => $breakdown,
        ];
    }

    protected function validSectionId(Exam $exam, $sectionId): ?int
    {
        if (! $sectionId) {
            return null;
        }

        return $exam->sections()->whereKey($sectionId)->exists() ? (int) $sectionId : null;
    }

    protected function uniqueAccessCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Exam::where('access_code', $code)->exists());

        return $code;
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
