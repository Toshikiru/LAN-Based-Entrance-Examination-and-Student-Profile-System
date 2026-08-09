<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// This app has no route named `dashboard` or `home` (only role-specific
// ones: admin.dashboard, counselor.dashboard, student.dashboard). Laravel's
// `guest` middleware — which guards /login — falls back to redirecting an
// already-authenticated visitor to `/` when neither of those exist. If `/`
// then unconditionally redirected everyone to /login regardless of auth
// state (as it used to), a still-logged-in user landing on `/` — e.g.
// reopening a bookmark after closing the browser without logging out —
// would bounce forever between `/` and `/login`. Checking auth here breaks
// that loop at the source.
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->to(LoginController::redirectPathFor(Auth::user()->role));
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // ---- Password reset (see PasswordResetController for the LAN-first design) ----
    Route::get('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---- Notifications (shared across all roles) ----
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// ---- My Profile (shared across all roles; edit scope is limited by role inside the controller) ----
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
    Route::delete('/avatar', [ProfileController::class, 'destroyAvatar'])->name('avatar.destroy');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin'])
    ->group(base_path('routes/admin.php'));

// The Super Admin reuses most of the Counselor module read-only (Students,
// Question Bank, Examinations, Monitoring, Results, Reports) — see the
// role:counselor sub-group inside routes/counselor.php for what stays
// exclusive to Guidance Counselors.
Route::prefix('counselor')
    ->name('counselor.')
    ->middleware(['auth', 'role:counselor,super_admin'])
    ->group(base_path('routes/counselor.php'));

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(base_path('routes/student.php'));
