<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\InterpretationRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterpretationRangeController extends Controller
{
    public function manage(Exam $exam): View
    {
        $exam->load('interpretationRanges');

        return view('counselor.exams.interpretations', ['exam' => $exam]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'min_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:min_percentage'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $exam->interpretationRanges()->create($data);

        return redirect()
            ->route('counselor.exams.interpretations', $exam)
            ->with('status', 'Interpretation range added.');
    }

    public function destroy(Exam $exam, InterpretationRange $range): RedirectResponse
    {
        abort_unless($range->exam_id === $exam->id, 404);

        $range->delete();

        return redirect()
            ->route('counselor.exams.interpretations', $exam)
            ->with('status', 'Interpretation range removed.');
    }
}
