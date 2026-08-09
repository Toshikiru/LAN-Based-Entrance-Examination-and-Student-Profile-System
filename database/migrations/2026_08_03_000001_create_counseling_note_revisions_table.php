<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores a snapshot of a counseling note's fields immediately before each
     * edit, so the full edit history can be reconstructed and displayed.
     */
    public function up(): void
    {
        Schema::create('counseling_note_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->date('note_date');
            $table->text('content');
            $table->date('follow_up_date')->nullable();
            $table->string('status');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_note_revisions');
    }
};
