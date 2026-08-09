<?php

namespace Database\Seeders;

use App\Models\YearLevel;
use Illuminate\Database\Seeder;

class YearLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];

        foreach ($levels as $order => $name) {
            YearLevel::updateOrCreate(['name' => $name], ['order' => $order + 1, 'is_active' => true]);
        }
    }
}
