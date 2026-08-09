<?php

namespace Database\Seeders;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        if (Question::count() > 0) {
            return;
        }

        $counselor = User::where('role', UserRole::Counselor)->first();

        $questions = [
            [
                'section_category' => 'Quantitative Reasoning',
                'question_text' => "A company's revenue increased by 25% in the first quarter and then decreased by 20% in the second quarter. What is the net percentage change in revenue over the two quarters?",
                'type' => QuestionType::MultipleChoice,
                'difficulty' => QuestionDifficulty::Medium,
                'marks' => 2,
                'negative_marks' => 0.5,
                'options' => [
                    ['text' => '5% Increase', 'correct' => false],
                    ['text' => '0% (No Change)', 'correct' => true],
                    ['text' => '4.5% Increase', 'correct' => false],
                    ['text' => '2% Decrease', 'correct' => false],
                ],
            ],
            [
                'section_category' => 'Verbal Reasoning',
                'question_text' => 'Choose the word that is most nearly OPPOSITE in meaning to "Diligent".',
                'type' => QuestionType::MultipleChoice,
                'difficulty' => QuestionDifficulty::Easy,
                'marks' => 1,
                'negative_marks' => 0.25,
                'options' => [
                    ['text' => 'Hardworking', 'correct' => false],
                    ['text' => 'Lazy', 'correct' => true],
                    ['text' => 'Careful', 'correct' => false],
                    ['text' => 'Punctual', 'correct' => false],
                ],
            ],
            [
                'section_category' => 'Logical Reasoning',
                'question_text' => 'The Earth is the third planet from the Sun.',
                'type' => QuestionType::TrueFalse,
                'difficulty' => QuestionDifficulty::Easy,
                'marks' => 1,
                'negative_marks' => 0,
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'section_category' => 'Personality Assessment',
                'question_text' => 'I prefer working in a team rather than alone.',
                'type' => QuestionType::Likert,
                'difficulty' => QuestionDifficulty::Easy,
                'marks' => 1,
                'negative_marks' => 0,
                'options' => [
                    ['text' => 'Strongly Agree', 'value' => 5],
                    ['text' => 'Agree', 'value' => 4],
                    ['text' => 'Neutral', 'value' => 3],
                    ['text' => 'Disagree', 'value' => 2],
                    ['text' => 'Strongly Disagree', 'value' => 1],
                ],
            ],
            [
                'section_category' => 'Verbal Reasoning',
                'question_text' => 'In two or three sentences, describe a situation where you resolved a disagreement within a group.',
                'type' => QuestionType::ShortAnswer,
                'difficulty' => QuestionDifficulty::Medium,
                'marks' => 5,
                'negative_marks' => 0,
                'options' => [],
            ],
        ];

        foreach ($questions as $data) {
            $question = Question::create([
                'section_category' => $data['section_category'],
                'question_text' => $data['question_text'],
                'type' => $data['type'],
                'difficulty' => $data['difficulty'],
                'marks' => $data['marks'],
                'negative_marks' => $data['negative_marks'],
                'created_by' => $counselor->id,
                'status' => QuestionStatus::Active,
            ]);

            foreach ($data['options'] as $order => $option) {
                $question->options()->create([
                    'option_text' => $option['text'],
                    'is_correct' => $option['correct'] ?? false,
                    'value' => $option['value'] ?? null,
                    'order' => $order,
                ]);
            }
        }
    }
}
