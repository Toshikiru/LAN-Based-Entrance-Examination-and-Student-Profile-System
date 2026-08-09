<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuration only: records who should receive which report and how
     * often. Actual scheduled dispatch (cron + mail) is not implemented yet,
     * so `last_run_at` stays null until that automation exists.
     */
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // App\Enums\ReportType
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->string('frequency'); // weekly | monthly | quarterly | biannual
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
