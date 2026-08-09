<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('custom'); // App\Enums\ExamCategory
            $table->unsignedInteger('duration_minutes');
            $table->decimal('passing_score', 5, 2)->default(0);
            $table->boolean('negative_marking')->default(false);
            $table->string('status')->default('draft'); // App\Enums\ExamStatus
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
