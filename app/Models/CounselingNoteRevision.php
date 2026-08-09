<?php

namespace App\Models;

use App\Enums\CounselingNoteCategory;
use App\Enums\CounselingNoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselingNoteRevision extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'counseling_note_id',
        'edited_by',
        'category',
        'note_date',
        'content',
        'follow_up_date',
        'follow_up_action',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => CounselingNoteCategory::class,
            'status' => CounselingNoteStatus::class,
            'note_date' => 'date',
            'follow_up_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    // ---- Relationships ----

    public function counselingNote(): BelongsTo
    {
        return $this->belongsTo(CounselingNote::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
