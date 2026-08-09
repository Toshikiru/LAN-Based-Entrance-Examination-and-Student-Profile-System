<?php

namespace App\Models;

use App\Enums\CounselingNoteCategory;
use App\Enums\CounselingNoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounselingNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'counselor_id',
        'category',
        'note_date',
        'content',
        'follow_up_date',
        'follow_up_action',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'category' => CounselingNoteCategory::class,
            'status' => CounselingNoteStatus::class,
            'note_date' => 'date',
            'follow_up_date' => 'date',
        ];
    }

    // ---- Relationships ----

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CounselingNoteRevision::class)->latest('created_at');
    }
}
