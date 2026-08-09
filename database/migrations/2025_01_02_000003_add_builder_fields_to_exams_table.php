<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('access_code')->nullable()->unique()->after('status');
            $table->boolean('shuffle_questions')->default(false)->after('access_code');
            $table->boolean('shuffle_choices')->default(false)->after('shuffle_questions');
            $table->boolean('auto_submit')->default(true)->after('shuffle_choices');
            $table->boolean('show_score')->default(true)->after('auto_submit');
            $table->boolean('show_correct_answers')->default(false)->after('show_score');
            $table->boolean('allow_resume')->default(true)->after('show_correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn([
                'access_code',
                'shuffle_questions',
                'shuffle_choices',
                'auto_submit',
                'show_score',
                'show_correct_answers',
                'allow_resume',
            ]);
        });
    }
};
