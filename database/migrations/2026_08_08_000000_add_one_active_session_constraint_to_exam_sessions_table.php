<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-level backstop for "one active session per student" (the
     * approved evaluation questionnaire's Reliability item 6). MySQL/MariaDB
     * has no partial/filtered unique index, so this uses the standard
     * workaround: a generated column that is NULL except while a session is
     * 'in_progress' or 'flagged', with a unique index on it — MySQL unique
     * indexes never conflict on NULL, so completed/terminated/not-started
     * sessions (a student legitimately has many of those over time) are
     * completely unaffected, while at most one active row per student can
     * ever exist. The application-level check in
     * ExamTakingController::start() is what produces the friendly error
     * message; this is just the guarantee behind it.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE exam_sessions
            ADD COLUMN active_session_lock BIGINT UNSIGNED
                GENERATED ALWAYS AS (
                    CASE WHEN status IN ('in_progress', 'flagged') THEN student_id ELSE NULL END
                ) STORED
        SQL);

        Schema::table('exam_sessions', function ($table) {
            $table->unique('active_session_lock', 'exam_sessions_one_active_per_student');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function ($table) {
            $table->dropUnique('exam_sessions_one_active_per_student');
            $table->dropColumn('active_session_lock');
        });
    }
};
