<?php

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Suggested categories shown in the form's datalist. Staff can still type
     * a custom category — this is not an enforced/foreign-keyed list.
     */
    public const SUGGESTED_CATEGORIES = [
        'Math',
        'English',
        'Abstract Reasoning',
        'Science',
        'General Information',
        'Reading Comprehension',
        'Pattern Recognition',
    ];

    protected $fillable = [
        'section_category',
        'question_text',
        'reference_answer',
        'type',
        'difficulty',
        'marks',
        'negative_marks',
        'created_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => QuestionDifficulty::class,
            'status' => QuestionStatus::class,
            'marks' => 'decimal:2',
            'negative_marks' => 'decimal:2',
        ];
    }

    // ---- Relationships ----

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function correctOptions(): HasMany
    {
        return $this->options()->where('is_correct', true);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot(['exam_section_id', 'order', 'marks_override'])
            ->withTimestamps();
    }

    public function examAnswers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }
}
