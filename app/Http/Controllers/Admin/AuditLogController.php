<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Read-only, searchable log of every recorded system action.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }
}
