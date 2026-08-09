<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ReferenceDataController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\YearLevelController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ---- Guidance Administrator Management ----
Route::get('/administrators', [AdministratorController::class, 'index'])->name('administrators.index');
Route::get('/administrators/create', [AdministratorController::class, 'create'])->name('administrators.create');
Route::post('/administrators', [AdministratorController::class, 'store'])->name('administrators.store');
Route::get('/administrators/{administrator}/edit', [AdministratorController::class, 'edit'])->name('administrators.edit');
Route::put('/administrators/{administrator}', [AdministratorController::class, 'update'])->name('administrators.update');

Route::patch('/administrators/{administrator}/activate', [AdministratorController::class, 'activate'])->name('administrators.activate');
Route::patch('/administrators/{administrator}/deactivate', [AdministratorController::class, 'deactivate'])->name('administrators.deactivate');
Route::patch('/administrators/{administrator}/reset-password', [AdministratorController::class, 'resetPassword'])->name('administrators.reset-password');

// ---- Audit Logs ----
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

// ---- System Settings ----
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::patch('/settings/school-info', [SettingsController::class, 'updateSchoolInfo'])->name('settings.school-info.update');

Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');
Route::delete('/settings/branding/logo', [SettingsController::class, 'removeLogo'])->name('settings.branding.logo.destroy');
Route::delete('/settings/branding/favicon', [SettingsController::class, 'removeFavicon'])->name('settings.branding.favicon.destroy');

// ---- Reference Data (Departments, Courses, Year Levels) ----
Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference-data.index');

Route::post('/reference-data/departments', [DepartmentController::class, 'store'])->name('reference-data.departments.store');
Route::put('/reference-data/departments/{department}', [DepartmentController::class, 'update'])->name('reference-data.departments.update');
Route::patch('/reference-data/departments/{department}/archive', [DepartmentController::class, 'archive'])->name('reference-data.departments.archive');
Route::patch('/reference-data/departments/{department}/restore', [DepartmentController::class, 'restore'])->withTrashed()->name('reference-data.departments.restore');

Route::post('/reference-data/courses', [CourseController::class, 'store'])->name('reference-data.courses.store');
Route::put('/reference-data/courses/{course}', [CourseController::class, 'update'])->name('reference-data.courses.update');
Route::patch('/reference-data/courses/{course}/archive', [CourseController::class, 'archive'])->name('reference-data.courses.archive');
Route::patch('/reference-data/courses/{course}/restore', [CourseController::class, 'restore'])->withTrashed()->name('reference-data.courses.restore');

Route::post('/reference-data/year-levels', [YearLevelController::class, 'store'])->name('reference-data.year-levels.store');
Route::put('/reference-data/year-levels/{yearLevel}', [YearLevelController::class, 'update'])->name('reference-data.year-levels.update');
Route::patch('/reference-data/year-levels/{yearLevel}/toggle', [YearLevelController::class, 'toggle'])->name('reference-data.year-levels.toggle');

// ---- Backup & Restore ----
Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
Route::get('/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
Route::delete('/backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
