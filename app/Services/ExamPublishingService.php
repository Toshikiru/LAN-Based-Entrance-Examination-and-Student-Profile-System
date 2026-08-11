<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\ExaminationAssigned;
use App\Notifications\ExaminationPublished;
use Illuminate\Support\Str;

class ExamPublishingService
{
    /**
     * Checks shared by every path that can flip an exam to Published, so a
     * quick "activate" action can't mark an exam published without the same
     * guarantees (questions present, a real passing score) the Publish page
     * enforces.
     *
     * @return string|null Error message if the exam isn't ready, otherwise null.
     */
    public function publishError(Exam $exam): ?string
    {
        if ($exam->examQuestions()->count() === 0) {
            return 'Add at least one question before publishing.';
        }

        if ((float) $exam->passing_score <= 0) {
            return 'Set a passing score before publishing.';
        }

        return null;
    }

    /**
     * Flip the exam to Published, issuing an access code if it doesn't have
     * one yet and notifying the creator and targeted students. Callers must
     * check publishError() first.
     */
    public function publish(Exam $exam): void
    {
        $exam->update([
            'status' => ExamStatus::Published,
            'access_code' => $exam->access_code ?: $this->uniqueAccessCode(),
        ]);

        $exam->creator?->notify(new ExaminationPublished($exam));
        $this->targetedStudents($exam)->each->notify(new ExaminationAssigned($exam));
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

    protected function uniqueAccessCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Exam::where('access_code', $code)->exists());

        return $code;
    }
}
