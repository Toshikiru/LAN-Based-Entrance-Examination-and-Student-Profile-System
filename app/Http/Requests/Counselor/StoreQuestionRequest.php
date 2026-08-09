<?php

namespace App\Http\Requests\Counselor;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCounselor() ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'section_category' => ['required', 'string', 'max:255'],
            'question_text' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in(array_column(QuestionType::cases(), 'value'))],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'marks' => ['required', 'numeric', 'min:0', 'max:100'],
            'negative_marks' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:draft,active,archived'],
        ];

        return array_merge($rules, match ($this->input('type')) {
            QuestionType::MultipleChoice->value => [
                'choices' => ['required', 'array', 'min:2', 'max:8'],
                'choices.*' => ['required', 'string', 'max:500'],
                'correct_choice' => ['required', 'integer', 'min:0'],
            ],
            QuestionType::TrueFalse->value => [
                'tf_answer' => ['required', 'in:true,false'],
            ],
            QuestionType::Likert->value => [
                'scale_text' => ['required', 'array', 'min:2', 'max:10'],
                'scale_text.*' => ['required', 'string', 'max:255'],
                'scale_value' => ['required', 'array'],
                'scale_value.*' => ['required', 'integer', 'min:0', 'max:100'],
            ],
            default => [],
        });
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') === QuestionType::MultipleChoice->value) {
                $choices = $this->input('choices', []);
                $correct = (int) $this->input('correct_choice');

                if (! array_key_exists($correct, $choices)) {
                    $validator->errors()->add('correct_choice', 'Please mark which choice is the correct answer.');
                }
            }

            if ($this->input('type') === QuestionType::Likert->value) {
                $texts = $this->input('scale_text', []);
                $values = $this->input('scale_value', []);

                if (count($texts) !== count($values)) {
                    $validator->errors()->add('scale_value', 'Each scale option must have a numeric value.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'section_category' => 'category',
            'question_text' => 'question',
            'choices.*' => 'choice',
            'correct_choice' => 'correct answer',
            'tf_answer' => 'correct answer',
            'scale_text.*' => 'scale option',
            'scale_value.*' => 'scale value',
        ];
    }
}
