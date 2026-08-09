<?php

namespace App\Services;

use App\Models\CounselingNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates Guidance & Counseling Notes, snapshotting the prior
 * state into a revision record on every edit so a full edit history can be
 * displayed alongside each note.
 */
class CounselingNoteService
{
    /**
     * @param  array{category: string, note_date: string, content: string, follow_up_date: ?string, follow_up_action: ?string, status: string}  $data
     */
    public function create(User $student, User $counselor, array $data): CounselingNote
    {
        return CounselingNote::create([
            'student_id' => $student->id,
            'counselor_id' => $counselor->id,
            'category' => $data['category'],
            'note_date' => $data['note_date'],
            'content' => $data['content'],
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'follow_up_action' => $data['follow_up_action'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * @param  array{category: string, note_date: string, content: string, follow_up_date: ?string, follow_up_action: ?string, status: string}  $data
     */
    public function update(CounselingNote $note, User $editor, array $data): CounselingNote
    {
        DB::transaction(function () use ($note, $editor, $data) {
            $note->revisions()->create([
                'edited_by' => $editor->id,
                'category' => $note->category,
                'note_date' => $note->note_date,
                'content' => $note->content,
                'follow_up_date' => $note->follow_up_date,
                'follow_up_action' => $note->follow_up_action,
                'status' => $note->status,
                'created_at' => now(),
            ]);

            $note->update([
                'category' => $data['category'],
                'note_date' => $data['note_date'],
                'content' => $data['content'],
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'follow_up_action' => $data['follow_up_action'] ?? null,
                'status' => $data['status'],
            ]);
        });

        return $note->fresh();
    }
}
