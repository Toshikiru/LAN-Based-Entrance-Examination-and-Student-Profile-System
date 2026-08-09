<?php

namespace App\Models;

use App\Enums\ExamCategory;
use App\Enums\ExamStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category',
        'department_id',
        'year_level',
        'duration_minutes',
        'passing_score',
        'negative_marking',
        'status',
        'access_code',
        'shuffle_questions',
        'shuffle_choices',
        'auto_submit',
        'show_score',
        'show_correct_answers',
        'allow_resume',
        'created_by',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExamCategory::class,
            'status' => ExamStatus::class,
            'negative_marking' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'auto_submit' => 'boolean',
            'show_score' => 'boolean',
            'show_correct_answers' => 'boolean',
            'allow_resume' => 'boolean',
            'passing_score' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('order');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot(['exam_section_id', 'order', 'marks_override'])
            ->withTimestamps()
            ->orderBy('exam_questions.order');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function interpretationRanges(): HasMany
    {
        return $this->hasMany(InterpretationRange::class)->orderBy('min_percentage');
    }

    /**
     * Resolve the interpretation label for a given percentage, if any range matches.
     */
    public function interpretationFor(float $percentage): ?string
    {
        $range = $this->interpretationRanges
            ->first(fn ($r) => $percentage >= (float) $r->min_percentage && $percentage <= (float) $r->max_percentage);

        return $range?->label;
    }

    // ---- Scopes ----

    /**
     * Exams visible to a given student: untargeted (open to everyone), or
     * matching their department and/or year level.
     */
    public function scopeTargetedTo(Builder $query, User $student): Builder
    {
        $yearLevel = $student->studentProfile?->year_level;

        return $query
            ->where(function (Builder $q) use ($student) {
                $q->whereNull('department_id')->orWhere('department_id', $student->department_id);
            })
            ->where(function (Builder $q) use ($yearLevel) {
                $q->whereNull('year_level')->orWhere('year_level', $yearLevel);
            });
    }
}
