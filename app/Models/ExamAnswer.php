<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'question_id',
        'selected_option_id',
        'answer_text',
        'awarded_marks',
        'is_correct',
        'is_flagged',
        'is_visited',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'is_visited' => 'boolean',
            'awarded_marks' => 'decimal:2',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
