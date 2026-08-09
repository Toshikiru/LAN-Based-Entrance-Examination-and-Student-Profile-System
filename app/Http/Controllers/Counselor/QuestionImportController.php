<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\ImportQuestionsRequest;
use App\Models\AuditLog;
use App\Models\Question;
use App\Services\QuestionDocumentExtractor;
use App\Services\QuestionImportParser;
use App\Services\SchoolSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class QuestionImportController extends Controller
{
    public function __construct(
        protected QuestionDocumentExtractor $extractor,
        protected QuestionImportParser $parser,
        protected SchoolSettingsService $settings,
    ) {}

    /**
     * Upload page.
     */
    public function create(): View
    {
        return view('counselor.questions.import.create');
    }

    /**
     * Parse the uploaded file and show a validation preview.
     */
    public function preview(ImportQuestionsRequest $request): View|RedirectResponse
    {
        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $text = $this->extractor->extract($file->getRealPath(), $extension);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $questions = $this->parser->parse($text);

        if (count($questions) === 0) {
            return back()->with('error', 'No questions were found. Check the file follows the import format.');
        }

        return view('counselor.questions.import.preview', [
            'questions' => $questions,
            'sourceText' => $text,
            'fileName' => $file->getClientOriginalName(),
            'validCount' => collect($questions)->where('valid', true)->count(),
            'errorCount' => collect($questions)->where('valid', false)->count(),
        ]);
    }

    /**
     * Save the selected, valid questions to the bank.
     * The source text is re-parsed here so validation cannot be bypassed client-side.
     */
    public function store(Request $request): RedirectResponse
    {
        $sourceText = (string) $request->input('source_text');
        $selected = array_map('intval', (array) $request->input('selected', []));

        $questions = collect($this->parser->parse($sourceText))
            ->filter(fn ($q) => $q['valid'] && in_array($q['number'], $selected, true));

        if ($questions->isEmpty()) {
            return redirect()
                ->route('counselor.questions.index')
                ->with('error', 'No valid questions were selected to import.');
        }

        $count = DB::transaction(function () use ($questions) {
            $count = 0;

            foreach ($questions as $data) {
                $question = Question::create([
                    'section_category' => $data['category'],
                    'question_text' => $data['question'],
                    'type' => $data['type'],
                    'difficulty' => $data['difficulty'],
                    'marks' => $data['points'],
                    'negative_marks' => 0,
                    'status' => QuestionStatus::Active,
                    'created_by' => auth()->id(),
                ]);

                foreach ($data['choices'] as $order => $choice) {
                    $question->options()->create([
                        'option_text' => $choice['text'],
                        'is_correct' => (bool) $choice['correct'],
                        'value' => $choice['value'],
                        'order' => $order,
                    ]);
                }

                $count++;
            }

            return $count;
        });

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'question.imported',
            'description' => "Imported {$count} question(s) into the bank.",
            'subject_type' => Question::class,
            'subject_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('counselor.questions.index')
            ->with('status', "{$count} question(s) imported successfully.");
    }

    /**
     * Download a ready-to-fill .txt template.
     */
    public function template(): Response
    {
        $systemName = $this->settings->branding()['system_name'];

        $body = <<<TXT
        # {$systemName} - Question Import Template
        # One question per block. Separate questions with a blank line.
        # Lines starting with # are ignored.
        #
        # Optional headers: TYPE, CATEGORY, DIFFICULTY, POINTS
        # Mark the correct choice with a trailing *  (or [correct])

        TYPE: multiple_choice
        CATEGORY: Quantitative Reasoning
        DIFFICULTY: medium
        POINTS: 2
        Q: If a train travels 60 km in 45 minutes, what is its average speed?
        A) 60 km/h
        B) 80 km/h *
        C) 75 km/h
        D) 90 km/h

        TYPE: true_false
        CATEGORY: General Knowledge
        Q: The Pacific is the largest ocean on Earth.
        A) True *
        B) False

        TYPE: likert
        CATEGORY: Personality Assessment
        Q: I enjoy working as part of a team.
        - Strongly Agree = 5
        - Agree = 4
        - Neutral = 3
        - Disagree = 2
        - Strongly Disagree = 1

        TYPE: short_answer
        CATEGORY: Verbal Reasoning
        POINTS: 5
        Q: Briefly describe a time you resolved a conflict within a group.
        TXT;

        // Drop comment lines from the delivered template body but keep them as guidance above.
        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="question-import-template.txt"',
        ]);
    }
}
