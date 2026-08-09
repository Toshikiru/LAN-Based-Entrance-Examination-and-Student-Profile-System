<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case Likert = 'likert';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::TrueFalse => 'True / False',
            self::Likert => 'Likert Scale',
            self::ShortAnswer => 'Short Answer',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MultipleChoice => 'radio_button_checked',
            self::TrueFalse => 'toggle_on',
            self::Likert => 'linear_scale',
            self::ShortAnswer => 'short_text',
        };
    }

    /** Whether this type stores answer options. */
    public function hasOptions(): bool
    {
        return $this !== self::ShortAnswer;
    }

    /** Whether a "correct" option applies (auto-scored objective items). */
    public function usesCorrectAnswer(): bool
    {
        return in_array($this, [self::MultipleChoice, self::TrueFalse], true);
    }

    /** Whether the system can auto-score this type. Short Answer is graded manually. */
    public function isAutoScored(): bool
    {
        return $this !== self::ShortAnswer;
    }
}
