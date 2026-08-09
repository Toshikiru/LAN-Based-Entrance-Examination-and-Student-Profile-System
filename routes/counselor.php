<?php

use App\Http\Controllers\Counselor\CounselingNoteController;
use App\Http\Controllers\Counselor\DashboardController;
use App\Http\Controllers\Counselor\ExamBuilderController;
use App\Http\Controllers\Counselor\ExamController;
use App\Http\Controllers\Counselor\ExamMonitoringController;
use App\Http\Controllers\Counselor\ExamResultController;
use App\Http\Controllers\Counselor\InterpretationRangeController;
use App\Http\Controllers\Counselor\QuestionController;
use App\Http\Controllers\Counselor\QuestionImportController;
use App\Http\Controllers\Counselor\ReportController;
use App\Http\Controllers\Counselor\ReportScheduleController;
use App\Http\Controllers\Counselor\StudentController;
use App\Http\Controllers\Counselor\StudentRecordController;
use Illuminate\Support\Facades\Route;

// ---- Read-only routes: reused as-is by the Super Admin module. ----
// The outer route group (routes/web.php) allows both `counselor` and
// `super_admin` into this whole file; everything that must stay exclusive
// to Guidance Counselors is wrapped in the `role:counselor` group further
// down, which adds one more check on top of the outer one.
//
// `students.create` and `questions.import.create` are GET routes with a
// single static path segment ("/students/create", "/questions/import"),
// which is the same shape as the {student}/{question} show routes below.
// Laravel matches routes in registration order, so both must be registered
// here — before the shared `show` routes — even though they stay
// counselor-only via their own inline middleware.

Route::get('/students/create', [StudentController::class, 'create'])->middleware('role:counselor')->name('students.create');
Route::get('/questions/import', [QuestionImportController::class, 'create'])->middleware('role:counselor')->name('questions.import.create');
Route::get('/questions/create', [QuestionController::class, 'create'])->middleware('role:counselor')->name('questions.create');

Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');

Route::resource('students', StudentController::class)->only(['index', 'show'])->withTrashed(['show']);

Route::get('/students/{student}/cumulative-record/print', [StudentRecordController::class, 'print'])
    ->name('students.record.print');

// Reset password is intentionally shared (not counselor-only): either the
// Guidance Counselor or the Super Admin can act as the "Guidance Office"
// contact point when a student reports a forgotten password.
Route::patch('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])
    ->name('students.reset-password');

Route::get('/counseling-notes', [CounselingNoteController::class, 'index'])->name('counseling-notes.index');

Route::resource('exams', ExamController::class)->only(['index']);

Route::get('/exams/{exam}/preview', [ExamBuilderController::class, 'preview'])->name('exams.preview');

Route::get('/monitoring', [ExamMonitoringController::class, 'index'])->name('monitoring.index');
Route::get('/exams/{exam}/monitor', [ExamMonitoringController::class, 'monitor'])->name('exams.monitor');
Route::get('/exams/{exam}/monitor/data', [ExamMonitoringController::class, 'monitorData'])->name('exams.monitor.data');

Route::get('/results', [ExamResultController::class, 'index'])->name('results.index');
Route::get('/exams/{exam}/results', [ExamResultController::class, 'results'])->name('exams.results');
Route::get('/exams/{exam}/results/export', [ExamResultController::class, 'exportCsv'])->name('exams.results.csv');
Route::get('/exams/{exam}/results/print', [ExamResultController::class, 'exportPrint'])->name('exams.results.print');

Route::resource('questions', QuestionController::class)->only(['index', 'show']);

// ---- Institutional Reports & Performance Analytics ----
// Full Access for both roles — nothing here needs restricting.
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/student-summary/print', [ReportController::class, 'studentSummaryPrint'])->name('reports.student-summary.print');
Route::get('/reports/student-summary/csv', [ReportController::class, 'studentSummaryCsv'])->name('reports.student-summary.csv');
Route::get('/reports/exam-statistics/print', [ReportController::class, 'examStatisticsPrint'])->name('reports.exam-statistics.print');
Route::get('/reports/exam-statistics/csv', [ReportController::class, 'examStatisticsCsv'])->name('reports.exam-statistics.csv');
Route::get('/reports/item-analysis/csv', [ReportController::class, 'itemAnalysisCsv'])->name('reports.item-analysis.csv');
Route::post('/reports/schedules', [ReportScheduleController::class, 'store'])->name('reports.schedules.store');
Route::patch('/reports/schedules/{schedule}/toggle', [ReportScheduleController::class, 'toggle'])->name('reports.schedules.toggle');

// ---- Guidance Counselor-only routes: create/edit/delete/grade/publish/import/monitor-actions. ----
// The Super Admin can view everything above but must never reach anything below.
Route::middleware('role:counselor')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('students', StudentController::class)
        ->only(['store', 'edit', 'update'])
        ->withTrashed(['edit', 'update']);

    Route::patch('/students/{student}/archive', [StudentController::class, 'archive'])
        ->name('students.archive');

    Route::patch('/students/{student}/restore', [StudentController::class, 'restore'])
        ->withTrashed()
        ->name('students.restore');

    // ---- Guidance & Counseling Notes (authoring stays counselor-only; viewing is shared above) ----
    Route::post('/students/{student}/counseling-notes', [CounselingNoteController::class, 'store'])
        ->name('students.counseling-notes.store');
    Route::put('/students/{student}/counseling-notes/{counselingNote}', [CounselingNoteController::class, 'update'])
        ->name('students.counseling-notes.update');

    // ---- Entrance Examination Management ----
    Route::resource('exams', ExamController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::patch('/exams/{exam}/activate', [ExamController::class, 'activate'])
        ->name('exams.activate');

    Route::patch('/exams/{exam}/deactivate', [ExamController::class, 'deactivate'])
        ->name('exams.deactivate');

    // ---- Exam Builder ----
    Route::get('/exams/{exam}/builder', [ExamBuilderController::class, 'builder'])->name('exams.builder');
    Route::get('/exams/{exam}/builder/select', [ExamBuilderController::class, 'questionPicker'])->name('exams.builder.select');
    Route::post('/exams/{exam}/builder/attach', [ExamBuilderController::class, 'attachQuestions'])->name('exams.builder.attach');
    Route::delete('/exams/{exam}/builder/questions/{examQuestion}', [ExamBuilderController::class, 'detachQuestion'])->name('exams.builder.detach');
    Route::post('/exams/{exam}/builder/reorder', [ExamBuilderController::class, 'reorder'])->name('exams.builder.reorder');
    Route::post('/exams/{exam}/builder/sections', [ExamBuilderController::class, 'storeSection'])->name('exams.builder.sections.store');
    Route::delete('/exams/{exam}/builder/sections/{section}', [ExamBuilderController::class, 'destroySection'])->name('exams.builder.sections.destroy');

    Route::get('/exams/{exam}/settings', [ExamBuilderController::class, 'settings'])->name('exams.settings');
    Route::patch('/exams/{exam}/settings', [ExamBuilderController::class, 'updateSettings'])->name('exams.settings.update');

    Route::get('/exams/{exam}/publish', [ExamBuilderController::class, 'publishForm'])->name('exams.publish');
    Route::post('/exams/{exam}/publish', [ExamBuilderController::class, 'publish'])->name('exams.publish.store');
    Route::post('/exams/{exam}/access-code', [ExamBuilderController::class, 'generateAccessCode'])->name('exams.access-code');

    // ---- Live Monitoring actions ----
    Route::post('/exams/{exam}/monitor/{session}/flag', [ExamMonitoringController::class, 'flag'])->name('exams.monitor.flag');
    Route::post('/exams/{exam}/monitor/{session}/terminate', [ExamMonitoringController::class, 'terminate'])->name('exams.monitor.terminate');

    // ---- Manual Grading ----
    Route::get('/exams/{exam}/grading', [ExamResultController::class, 'grading'])->name('exams.grading');
    Route::get('/exams/{exam}/grading/{session}', [ExamResultController::class, 'gradeSession'])->name('exams.grading.session');
    Route::post('/exams/{exam}/grading/{session}', [ExamResultController::class, 'storeGrades'])->name('exams.grading.store');

    // ---- Score Interpretation configuration ----
    Route::get('/exams/{exam}/interpretations', [InterpretationRangeController::class, 'manage'])->name('exams.interpretations');
    Route::post('/exams/{exam}/interpretations', [InterpretationRangeController::class, 'store'])->name('exams.interpretations.store');
    Route::delete('/exams/{exam}/interpretations/{range}', [InterpretationRangeController::class, 'destroy'])->name('exams.interpretations.destroy');

    // ---- Question Bank ----
    // `questions.import.create` is registered near the top of this file (see
    // comment there) — the rest of the import flow has no collision risk.
    Route::get('/questions/import/template', [QuestionImportController::class, 'template'])->name('questions.import.template');
    Route::post('/questions/import/preview', [QuestionImportController::class, 'preview'])->name('questions.import.preview');
    Route::post('/questions/import', [QuestionImportController::class, 'store'])->name('questions.import.store');

    Route::resource('questions', QuestionController::class)->only(['store', 'edit', 'update', 'destroy']);
});
