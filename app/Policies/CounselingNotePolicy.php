<?php

namespace App\Policies;

use App\Models\CounselingNote;
use App\Models\User;

/**
 * Guidance & Counseling Notes are sensitive records. Both Guidance Counselors
 * and the Super Administrator may view them (the Super Admin has read-only
 * oversight of guidance activity), but authoring a note is a counseling
 * operation reserved for Guidance Counselors only — the Super Admin must
 * never record or edit a counseling note. Students must never have access,
 * regardless of whether the note is about them.
 */
class CounselingNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, CounselingNote $note): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $user->isCounselor();
    }

    public function update(User $user, CounselingNote $note): bool
    {
        return $user->isCounselor();
    }

    protected function isStaff(User $user): bool
    {
        return $user->isCounselor() || $user->isSuperAdmin();
    }
}
