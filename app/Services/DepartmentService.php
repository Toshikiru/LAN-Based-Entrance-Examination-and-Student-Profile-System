<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService
{
    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department;
    }

    public function archive(Department $department): void
    {
        $department->delete();
    }

    public function restore(Department $department): void
    {
        $department->restore();
    }
}
