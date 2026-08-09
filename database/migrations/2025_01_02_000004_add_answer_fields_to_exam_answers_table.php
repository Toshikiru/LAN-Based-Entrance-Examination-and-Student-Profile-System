<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            // Free-text response for Short Answer questions.
            $table->text('answer_text')->nullable()->after('selected_option_id');
            // Per-answer scoring (auto for objective items, manual for Short Answer).
            $table->decimal('awarded_marks', 6, 2)->nullable()->after('answer_text');
            $table->boolean('is_correct')->nullable()->after('awarded_marks');
        });
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text', 'awarded_marks', 'is_correct']);
        });
    }
};
