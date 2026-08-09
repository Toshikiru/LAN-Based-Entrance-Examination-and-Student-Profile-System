<?php

namespace App\Services;

use App\Enums\QuestionType;

/**
 * Parses a plain-text question document into structured, validated questions.
 *
 * Expected format (one blank line between questions):
 *
 *   TYPE: multiple_choice            (optional; auto-detected when omitted)
 *   CATEGORY: Quantitative Reasoning (optional; default "Imported")
 *   DIFFICULTY: medium               (optional; easy|medium|hard)
 *   POINTS: 2                        (optional; default 1)
 *   Q: What is 2 + 2?
 *   A) 3
 *   B) 4 *
 *   C) 5
 *
 * Marking rules:
 *   - A choice line ending with " *" or " [correct]" is the correct answer.
 *   - True/False: two choices "True" / "False" (auto-detected).
 *   - Likert: choice lines like "- Strongly Agree = 5".
 *   - Short Answer: a question with no choices.
 */
class QuestionImportParser
{
    /**
     * @return array<int, array<string, mixed>> parsed question rows
     */
    public function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Drop comment lines (starting with #) before splitting into blocks.
        $text = preg_replace('/^[ \t]*#.*$/m', '', $text);

        // Split into blocks on one or more blank lines (or a --- separator).
        $blocks = preg_split('/\n\s*\n|\n-{3,}\n/', trim($text)) ?: [];

        $questions = [];
        $number = 0;

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $number++;
            $questions[] = $this->parseBlock($block, $number);
        }

        return $questions;
    }

    protected function parseBlock(string $block, int $number): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== ''));

        $meta = [
            'type' => null,
            'category' => 'Imported',
            'difficulty' => 'medium',
            'points' => 1.0,
        ];
        $questionText = null;
        $choices = [];

        foreach ($lines as $line) {
            if (preg_match('/^TYPE:\s*(.+)$/i', $line, $m)) {
                $meta['type'] = $this->normalizeType(trim($m[1]));
                continue;
            }
            if (preg_match('/^CATEGORY:\s*(.+)$/i', $line, $m)) {
                $meta['category'] = trim($m[1]);
                continue;
            }
            if (preg_match('/^DIFFICULTY:\s*(easy|medium|hard)\s*$/i', $line, $m)) {
                $meta['difficulty'] = strtolower($m[1]);
                continue;
            }
            if (preg_match('/^POINTS:\s*([0-9]+(?:\.[0-9]+)?)\s*$/i', $line, $m)) {
                $meta['points'] = (float) $m[1];
                continue;
            }
            if (preg_match('/^Q:\s*(.+)$/i', $line, $m)) {
                $questionText = trim($m[1]);
                continue;
            }

            // Likert option:  "- Label = 5"  or  "A) Label = 5"
            if (preg_match('/^(?:[-*]|[A-Za-z][\)\.])\s*(.+?)\s*=\s*(-?\d+)\s*$/', $line, $m)) {
                $choices[] = ['text' => trim($m[1]), 'correct' => false, 'value' => (int) $m[2]];
                continue;
            }

            // Standard choice:  "A) Text"  /  "A. Text"  /  "- Text"  (optional trailing * or [correct])
            if (preg_match('/^(?:[A-Za-z][\)\.]|[-*])\s*(.+)$/', $line, $m)) {
                $choiceText = trim($m[1]);
                $isCorrect = false;

                if (preg_match('/\s*(\*|\[correct\])\s*$/i', $choiceText)) {
                    $isCorrect = true;
                    $choiceText = trim(preg_replace('/\s*(\*|\[correct\])\s*$/i', '', $choiceText));
                }

                $choices[] = ['text' => $choiceText, 'correct' => $isCorrect, 'value' => null];
                continue;
            }

            // Any other line with no question yet becomes the question text.
            if ($questionText === null) {
                $questionText = $line;
            }
        }

        // Infer the type when it wasn't declared.
        $type = $meta['type'] ?? $this->detectType($choices);

        // For an auto-detected/declared true_false with no explicit choices, add them.
        if ($type === QuestionType::TrueFalse->value && count($choices) === 0) {
            $choices = [
                ['text' => 'True', 'correct' => false, 'value' => null],
                ['text' => 'False', 'correct' => false, 'value' => null],
            ];
        }

        $parsed = [
            'number' => $number,
            'type' => $type,
            'category' => $meta['category'],
            'difficulty' => $meta['difficulty'],
            'points' => $meta['points'],
            'question' => $questionText,
            'choices' => $choices,
        ];

        $parsed['errors'] = $this->validate($parsed);
        $parsed['valid'] = count($parsed['errors']) === 0;

        return $parsed;
    }

    protected function normalizeType(string $raw): ?string
    {
        $key = strtolower(str_replace([' ', '-', '/'], '_', trim($raw)));

        return match ($key) {
            'multiple_choice', 'mc', 'multiplechoice' => QuestionType::MultipleChoice->value,
            'true_false', 'truefalse', 'tf' => QuestionType::TrueFalse->value,
            'likert', 'likert_scale', 'scale' => QuestionType::Likert->value,
            'short_answer', 'shortanswer', 'essay', 'open' => QuestionType::ShortAnswer->value,
            default => null,
        };
    }

    protected function detectType(array $choices): string
    {
        if (count($choices) === 0) {
            return QuestionType::ShortAnswer->value;
        }

        if (collect($choices)->contains(fn ($c) => $c['value'] !== null)) {
            return QuestionType::Likert->value;
        }

        $labels = array_map(fn ($c) => strtolower(trim($c['text'])), $choices);
        sort($labels);
        if ($labels === ['false', 'true']) {
            return QuestionType::TrueFalse->value;
        }

        return QuestionType::MultipleChoice->value;
    }

    /**
     * @return array<int, string> human-readable validation errors
     */
    protected function validate(array $q): array
    {
        $errors = [];

        if (empty($q['question'])) {
            $errors[] = 'Missing question text.';
        }

        if (! in_array($q['type'], array_column(QuestionType::cases(), 'value'), true)) {
            $errors[] = 'Unrecognized question type.';
            return $errors;
        }

        $type = QuestionType::from($q['type']);
        $correctCount = collect($q['choices'])->where('correct', true)->count();

        if ($type === QuestionType::MultipleChoice) {
            if (count($q['choices']) < 2) {
                $errors[] = 'Needs at least two choices.';
            }
            if ($correctCount === 0) {
                $errors[] = 'Missing correct answer (mark one choice with *).';
            } elseif ($correctCount > 1) {
                $errors[] = 'Only one choice may be marked correct.';
            }
        } elseif ($type === QuestionType::TrueFalse) {
            if ($correctCount !== 1) {
                $errors[] = 'Mark exactly one of True/False as correct.';
            }
        } elseif ($type === QuestionType::Likert) {
            if (count($q['choices']) < 2) {
                $errors[] = 'Likert needs at least two scale options.';
            }
            if (collect($q['choices'])->contains(fn ($c) => $c['value'] === null)) {
                $errors[] = 'Every Likert option needs a numeric value (e.g. "- Agree = 4").';
            }
        }

        if (! is_numeric($q['points']) || $q['points'] <= 0) {
            $errors[] = 'Points must be greater than zero.';
        }

        return $errors;
    }
}
