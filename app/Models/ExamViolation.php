<?php

namespace App\Models;

use App\Enums\ViolationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'type',
        'occurred_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => ViolationType::class,
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}
