<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // App\Enums\CounselingNoteCategory
            $table->date('note_date');
            $table->text('content');
            $table->date('follow_up_date')->nullable();
            $table->string('status')->default('open'); // App\Enums\CounselingNoteStatus
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_notes');
    }
};
