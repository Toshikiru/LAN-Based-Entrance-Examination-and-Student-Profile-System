<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'avatar_path',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    // ---- Relationships ----

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function counselorProfile(): HasOne
    {
        return $this->hasOne(CounselorProfile::class);
    }

    public function examsCreated(): HasMany
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function questionsCreated(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'student_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Counseling notes written about this user (as the student).
     */
    public function counselingNotes(): HasMany
    {
        return $this->hasMany(CounselingNote::class, 'student_id');
    }

    /**
     * Counseling notes authored by this user (as the counselor).
     */
    public function counselingNotesAuthored(): HasMany
    {
        return $this->hasMany(CounselingNote::class, 'counselor_id');
    }

    public function updatedSystemSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    // ---- Helpers ----

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isCounselor(): bool
    {
        return $this->role === UserRole::Counselor;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }
}
