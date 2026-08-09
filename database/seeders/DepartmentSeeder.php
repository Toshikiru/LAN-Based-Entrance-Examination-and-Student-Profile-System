<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Arts and Sciences', 'code' => 'ARTS'],
            ['name' => 'Business Administration', 'code' => 'BUS'],
            ['name' => 'Science', 'code' => 'SCI'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
