<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\ExamTakingController;
use App\Http\Controllers\Student\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/results', [ResultController::class, 'index'])->name('results.index');

// ---- Exam taking ----
Route::get('/exams/join', [ExamTakingController::class, 'join'])->name('exams.join');
Route::post('/exams/join', [ExamTakingController::class, 'start'])->name('exams.start');
Route::get('/exams/{exam}/take', [ExamTakingController::class, 'take'])->name('exams.take');
Route::post('/exams/{exam}/answer', [ExamTakingController::class, 'answer'])->name('exams.answer');
Route::post('/exams/{exam}/submit', [ExamTakingController::class, 'submit'])->name('exams.submit');
Route::get('/exams/{exam}/result', [ExamTakingController::class, 'result'])->name('exams.result');
