<?php

namespace App\Services;

use App\Enums\ExamSessionStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Aggregates the data shown on the Super Admin dashboard: user/role
 * breakdowns, system status, recent logins, and audit activity.
 */
class AdminDashboardService
{
    /**
     * @return array<string, int>
     */
    public function usersByRole(): array
    {
        $counts = User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return [
            'super_admin' => (int) ($counts[UserRole::SuperAdmin->value] ?? 0),
            'counselor' => (int) ($counts[UserRole::Counselor->value] ?? 0),
            'student' => (int) ($counts[UserRole::Student->value] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $byRole = $this->usersByRole();

        return [
            'total_users' => array_sum($byRole),
            'total_students' => $byRole['student'],
            'total_counselors' => $byRole['counselor'],
            'total_examinations' => Exam::query()->count(),
            'active_sessions' => ExamSession::query()->where('status', ExamSessionStatus::InProgress)->count(),
        ];
    }

    /**
     * A best-effort, real (not simulated) system status snapshot.
     *
     * @return array{database: bool, cache_driver: string, queue_driver: string, environment: string}
     */
    public function systemStatus(): array
    {
        $databaseConnected = true;

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $databaseConnected = false;
        }

        return [
            'database' => $databaseConnected,
            'cache_driver' => (string) config('cache.default'),
            'queue_driver' => (string) config('queue.default'),
            'environment' => (string) app()->environment(),
        ];
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recentAuditLogs(int $limit = 6): Collection
    {
        return AuditLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Most recently authenticated users, for the "Recent User Logins" widget.
     *
     * @return Collection<int, User>
     */
    public function recentLogins(int $limit = 6): Collection
    {
        return User::query()
            ->whereNotNull('last_login_at')
            ->orderByDesc('last_login_at')
            ->limit($limit)
            ->get();
    }

    /**
     * New user registrations per week over the last N weeks, for the
     * "System Overview" growth chart. Bucketed in PHP (not raw SQL date
     * functions) so it stays portable across database drivers.
     *
     * @return array{labels: array<int, string>, counts: array<int, int>}
     */
    public function weeklySignups(int $weeks = 8): array
    {
        $start = now()->subWeeks($weeks - 1)->startOfWeek();

        $created = User::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at');

        $labels = [];
        $counts = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $start->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $labels[] = $weekStart->format('M d');
            $counts[] = $created->filter(fn ($timestamp) => $timestamp->between($weekStart, $weekEnd))->count();
        }

        return ['labels' => $labels, 'counts' => $counts];
    }
}
