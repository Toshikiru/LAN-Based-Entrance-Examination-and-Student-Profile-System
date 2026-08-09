<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The approved proposal's Definition of Terms describes a bio-note as
     * containing "an observation, the date and time it was recorded, and
     * any corresponding follow-up action" — the table had a follow_up_date
     * but no distinct free-text field for the action itself. This adds the
     * smallest field that closes that gap, mirrored onto the revisions
     * table so edit history keeps snapshotting the full note.
     */
    public function up(): void
    {
        Schema::table('counseling_notes', function (Blueprint $table) {
            $table->text('follow_up_action')->nullable()->after('follow_up_date');
        });

        Schema::table('counseling_note_revisions', function (Blueprint $table) {
            $table->text('follow_up_action')->nullable()->after('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::table('counseling_notes', function (Blueprint $table) {
            $table->dropColumn('follow_up_action');
        });

        Schema::table('counseling_note_revisions', function (Blueprint $table) {
            $table->dropColumn('follow_up_action');
        });
    }
};
