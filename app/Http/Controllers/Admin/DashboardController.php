<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AdminDashboardService $dashboard) {}

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats(),
            'usersByRole' => $this->dashboard->usersByRole(),
            'systemStatus' => $this->dashboard->systemStatus(),
            'recentAuditLogs' => $this->dashboard->recentAuditLogs(),
            'recentLogins' => $this->dashboard->recentLogins(),
            'signups' => $this->dashboard->weeklySignups(),
        ]);
    }
}
