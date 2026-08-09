<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['name' => 'BS Computer Science', 'code' => 'BSCS', 'department' => 'IT', 'years' => 4, 'semesters' => 8],
            ['name' => 'BS Information Technology', 'code' => 'BSIT', 'department' => 'IT', 'years' => 4, 'semesters' => 8],
            ['name' => 'BS Civil Engineering', 'code' => 'BSCE', 'department' => 'ENG', 'years' => 5, 'semesters' => 10],
            ['name' => 'BS Electrical Engineering', 'code' => 'BSEE', 'department' => 'ENG', 'years' => 5, 'semesters' => 10],
            ['name' => 'BA Psychology', 'code' => 'BAPSY', 'department' => 'ARTS', 'years' => 4, 'semesters' => 8],
            ['name' => 'AB Communication', 'code' => 'ABCOM', 'department' => 'ARTS', 'years' => 4, 'semesters' => 8],
            ['name' => 'BS Business Administration', 'code' => 'BSBA', 'department' => 'BUS', 'years' => 4, 'semesters' => 8],
            ['name' => 'BS Accountancy', 'code' => 'BSA', 'department' => 'BUS', 'years' => 4, 'semesters' => 8],
            ['name' => 'BS Biology', 'code' => 'BSBIO', 'department' => 'SCI', 'years' => 4, 'semesters' => 8],
        ];

        foreach ($courses as $course) {
            $department = Department::where('code', $course['department'])->first();

            Course::updateOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'department_id' => $department?->id,
                    'duration_years' => $course['years'],
                    'duration_semesters' => $course['semesters'],
                ]
            );
        }
    }
}
