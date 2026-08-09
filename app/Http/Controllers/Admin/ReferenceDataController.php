<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferenceDataController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'departments');
        $search = trim((string) $request->query('search'));

        $departments = Department::withTrashed()->withCount('users');
        $courses = Course::withTrashed()->with('department');
        $yearLevels = YearLevel::query();

        if ($search !== '') {
            $departments->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            $courses->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            $yearLevels->where('name', 'like', "%{$search}%");
        }

        return view('admin.reference-data.index', [
            'tab' => in_array($tab, ['departments', 'courses', 'year-levels'], true) ? $tab : 'departments',
            'departments' => $departments->orderBy('name')->get(),
            'courses' => $courses->orderBy('name')->get(),
            'yearLevels' => $yearLevels->get(),
            'filters' => ['search' => $search],
        ]);
    }
}
