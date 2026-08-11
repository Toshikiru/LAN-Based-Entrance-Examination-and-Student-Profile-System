<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Header search — a quick top-N preview across students and exams, with
     * "view all" links into each area's own (already filterable) index page.
     */
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('search'));

        $students = collect();
        $exams = collect();

        if ($query !== '') {
            $students = User::query()
                ->where('role', UserRole::Student)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('school_id', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->with('department')
                ->limit(5)
                ->get();

            $exams = Exam::query()
                ->where('title', 'like', "%{$query}%")
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('counselor.search', [
            'query' => $query,
            'students' => $students,
            'exams' => $exams,
        ]);
    }
}
