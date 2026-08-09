<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreQuestionRequest;
use App\Http\Requests\Counselor\UpdateQuestionRequest;
use App\Models\AuditLog;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Question bank listing — search, filter, paginate.
     */
    public function index(Request $request): View
    {
        $query = Question::query()->with('options')->withCount('options');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('question_text', 'like', "%{$search}%");
        }

        if ($type = $request->query('type')) {
            if (in_array($type, array_column(QuestionType::cases(), 'value'), true)) {
                $query->where('type', $type);
            }
        }

        if ($difficulty = $request->query('difficulty')) {
            if (in_array($difficulty, array_column(QuestionDifficulty::cases(), 'value'), true)) {
                $query->where('difficulty', $difficulty);
            }
        }

        if ($status = $request->query('status')) {
            if (in_array($status, array_column(QuestionStatus::cases(), 'value'), true)) {
                $query->where('status', $status);
            }
        }

        if ($category = $request->query('category')) {
            $query->where('section_category', $category);
        }

        $questions = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => Question::count(),
            'active' => Question::where('status', QuestionStatus::Active)->count(),
            'draft' => Question::where('status', QuestionStatus::Draft)->count(),
            'categories' => Question::distinct('section_category')->whereNotNull('section_category')->count('section_category'),
        ];

        return view('counselor.questions.index', [
            'questions' => $questions,
            'stats' => $stats,
            'categories' => $this->categories(),
            'types' => QuestionType::cases(),
            'difficulties' => QuestionDifficulty::cases(),
            'statuses' => QuestionStatus::cases(),
            'filters' => $request->only(['search', 'type', 'difficulty', 'status', 'category']),
        ]);
    }

    public function create(): View
    {
        return view('counselor.questions.create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $question = DB::transaction(function () use ($data, $request) {
            $question = Question::create([
                'section_category' => $data['section_category'],
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'difficulty' => $data['difficulty'],
                'marks' => $data['marks'],
                'negative_marks' => $data['negative_marks'] ?? 0,
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            $this->syncOptions($question, $request);

            return $question;
        });

        $this->logAudit('question.created', $question, "Created a {$question->type->label()} question.");

        return redirect()
            ->route('counselor.questions.index')
            ->with('status', 'Question was added to the bank.');
    }

    public function show(Question $question): View
    {
        $question->load('options');

        return view('counselor.questions.show', [
            'question' => $question,
        ]);
    }

    public function edit(Question $question): View
    {
        $question->load('options');

        return view('counselor.questions.edit', [
            'question' => $question,
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $question) {
            $question->update([
                'section_category' => $data['section_category'],
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'difficulty' => $data['difficulty'],
                'marks' => $data['marks'],
                'negative_marks' => $data['negative_marks'] ?? 0,
                'status' => $data['status'],
            ]);

            // Rebuild options from scratch to keep the logic simple and correct.
            $question->options()->delete();
            $this->syncOptions($question, $request);
        });

        $this->logAudit('question.updated', $question, "Updated a {$question->type->label()} question.");

        return redirect()
            ->route('counselor.questions.index')
            ->with('status', 'Question was updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        $this->logAudit('question.deleted', $question, 'Deleted a question from the bank.');

        return redirect()
            ->route('counselor.questions.index')
            ->with('status', 'Question was deleted.');
    }

    /**
     * Build the option rows for a question based on its type.
     */
    protected function syncOptions(Question $question, Request $request): void
    {
        $type = $question->type;

        if ($type === QuestionType::MultipleChoice) {
            $choices = array_values($request->input('choices', []));
            $correct = (int) $request->input('correct_choice');

            foreach ($choices as $i => $text) {
                $question->options()->create([
                    'option_text' => $text,
                    'is_correct' => $i === $correct,
                    'order' => $i,
                ]);
            }
        } elseif ($type === QuestionType::TrueFalse) {
            $answer = $request->input('tf_answer'); // 'true' | 'false'

            $question->options()->createMany([
                ['option_text' => 'True', 'is_correct' => $answer === 'true', 'order' => 0],
                ['option_text' => 'False', 'is_correct' => $answer === 'false', 'order' => 1],
            ]);
        } elseif ($type === QuestionType::Likert) {
            $texts = array_values($request->input('scale_text', []));
            $values = array_values($request->input('scale_value', []));

            foreach ($texts as $i => $text) {
                $question->options()->create([
                    'option_text' => $text,
                    'is_correct' => false,
                    'value' => isset($values[$i]) ? (int) $values[$i] : null,
                    'order' => $i,
                ]);
            }
        }
        // ShortAnswer: no options.
    }

    /**
     * Distinct, non-empty categories currently in the bank.
     */
    protected function categories(): array
    {
        return Question::query()
            ->whereNotNull('section_category')
            ->distinct()
            ->orderBy('section_category')
            ->pluck('section_category')
            ->all();
    }

    protected function logAudit(string $action, Question $subject, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Question::class,
            'subject_id' => $subject->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
