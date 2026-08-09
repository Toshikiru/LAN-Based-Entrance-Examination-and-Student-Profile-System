<?php

namespace App\Models;

use App\Enums\ExamSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'current_question_id',
        'time_remaining_seconds',
        'started_at',
        'submitted_at',
        'auto_submitted',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamSessionStatus::class,
            'auto_submitted' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ExamResult::class);
    }
}
