<?php

namespace App\Services;

use App\Models\Course;

class CourseService
{
    public function create(array $data): Course
    {
        return Course::create($data);
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        return $course;
    }

    public function archive(Course $course): void
    {
        $course->delete();
    }

    public function restore(Course $course): void
    {
        $course->restore();
    }
}
