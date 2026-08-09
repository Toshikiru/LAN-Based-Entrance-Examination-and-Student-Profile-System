<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert any legacy question rows created under the old QuestionType enum
     * (single_choice) to the current enum so model casting no longer throws.
     */
    public function up(): void
    {
        DB::table('questions')
            ->where('type', 'single_choice')
            ->update(['type' => 'multiple_choice']);
    }

    public function down(): void
    {
        // 'single_choice' no longer exists in the enum; nothing to revert.
    }
};
