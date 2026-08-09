<?php

namespace App\Services;

use App\Models\YearLevel;

class YearLevelService
{
    public function create(array $data): YearLevel
    {
        return YearLevel::create([
            ...$data,
            'is_active' => true,
        ]);
    }

    public function update(YearLevel $yearLevel, array $data): YearLevel
    {
        $yearLevel->update($data);

        return $yearLevel;
    }

    public function toggle(YearLevel $yearLevel): YearLevel
    {
        $yearLevel->update(['is_active' => ! $yearLevel->is_active]);

        return $yearLevel->fresh();
    }
}
