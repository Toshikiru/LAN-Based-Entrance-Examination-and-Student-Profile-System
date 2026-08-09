<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\CounselorProfile;
use App\Models\Department;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $it = Department::where('code', 'IT')->first();
        $eng = Department::where('code', 'ENG')->first();
        $arts = Department::where('code', 'ARTS')->first();
        $bus = Department::where('code', 'BUS')->first();
        $sci = Department::where('code', 'SCI')->first();

        // ---- Super Admin ----
        User::updateOrCreate(
            ['school_id' => 'ADMIN-0001'],
            [
                'name' => 'Admin Root',
                'email' => 'admin@guidancepulse.test',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Active,
            ]
        );

        // ---- Counselors ----
        $counselors = [
            ['school_id' => 'COUNSELOR-0001', 'name' => 'Maria Santillan', 'position' => 'Department Head'],
            ['school_id' => 'COUNSELOR-0002', 'name' => 'Roberto Mendoza', 'position' => 'Guidance Counselor'],
            ['school_id' => 'COUNSELOR-0003', 'name' => 'Elena Rodriguez', 'position' => 'Guidance Counselor'],
        ];

        foreach ($counselors as $data) {
            $user = User::updateOrCreate(
                ['school_id' => $data['school_id']],
                [
                    'name' => $data['name'],
                    'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@guidancepulse.test',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Counselor,
                    'status' => UserStatus::Active,
                ]
            );

            CounselorProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['position_title' => $data['position']]
            );
        }

        // ---- Students ----
        $students = [
            ['school_id' => '2021-0452', 'name' => 'Mark J. Aranas', 'department_id' => $it->id, 'year_level' => '3rd Year', 'section' => 'IT-3A', 'status' => UserStatus::Active],
            ['school_id' => '2021-0891', 'name' => 'Sarah Jane Smith', 'department_id' => $eng->id, 'year_level' => '2nd Year', 'section' => 'ENG-2B', 'status' => UserStatus::Active],
            ['school_id' => '2021-0112', 'name' => 'Rico Blanco', 'department_id' => $arts->id, 'year_level' => '1st Year', 'section' => 'ARTS-1A', 'status' => UserStatus::Active],
            ['school_id' => '2021-0234', 'name' => 'Jasmine Reyes', 'department_id' => $it->id, 'year_level' => '4th Year', 'section' => 'IT-4A', 'status' => UserStatus::Active],
            ['school_id' => '2021-0345', 'name' => 'Kevin Dela Cruz', 'department_id' => $eng->id, 'year_level' => '3rd Year', 'section' => 'ENG-3A', 'status' => UserStatus::Active],
            ['school_id' => '2022-0156', 'name' => 'Angela Cruz', 'department_id' => $bus->id, 'year_level' => '2nd Year', 'section' => 'BUS-2A', 'status' => UserStatus::Active],
            ['school_id' => '2022-0278', 'name' => 'Miguel Santos', 'department_id' => $bus->id, 'year_level' => '1st Year', 'section' => 'BUS-1B', 'status' => UserStatus::Active],
            ['school_id' => '2022-0389', 'name' => 'Patricia Lim', 'department_id' => $sci->id, 'year_level' => '2nd Year', 'section' => 'SCI-2A', 'status' => UserStatus::Active],
            ['school_id' => '2020-0501', 'name' => 'Joshua Tan', 'department_id' => $sci->id, 'year_level' => '4th Year', 'section' => 'SCI-4A', 'status' => UserStatus::Active],
            ['school_id' => '2021-0612', 'name' => 'Nicole Ramos', 'department_id' => $arts->id, 'year_level' => '3rd Year', 'section' => 'ARTS-3B', 'status' => UserStatus::Active],
            ['school_id' => '2022-0723', 'name' => 'Christian Bautista', 'department_id' => $it->id, 'year_level' => '1st Year', 'section' => 'IT-1C', 'status' => UserStatus::Active],
            ['school_id' => '2020-0834', 'name' => 'Bianca Villanueva', 'department_id' => $eng->id, 'year_level' => '4th Year', 'section' => 'ENG-4A', 'status' => UserStatus::Inactive],
            ['school_id' => '2021-0945', 'name' => 'Daniel Garcia', 'department_id' => $bus->id, 'year_level' => '3rd Year', 'section' => 'BUS-3A', 'status' => UserStatus::Suspended],
        ];

        foreach ($students as $data) {
            $user = User::updateOrCreate(
                ['school_id' => $data['school_id']],
                [
                    'name' => $data['name'],
                    'email' => null,
                    'password' => Hash::make('password'),
                    'role' => UserRole::Student,
                    'department_id' => $data['department_id'],
                    'status' => $data['status'],
                ]
            );

            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'year_level' => $data['year_level'],
                    'section' => $data['section'],
                    'enrollment_status' => $data['status'] === UserStatus::Active ? 'enrolled' : 'on_leave',
                ]
            );
        }
    }
}
