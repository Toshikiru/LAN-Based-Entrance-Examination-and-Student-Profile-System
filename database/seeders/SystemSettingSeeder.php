<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Services\SchoolSettingsService;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_version' => '4.2.0-stable',
            'exam_default_duration_minutes' => '120',
            'exam_negative_marking_default' => 'false',
            'max_tab_switch_violations' => '3',
            'backup_frequency' => 'daily',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Branding, seeded only if absent (firstOrCreate, not updateOrCreate)
        // so re-running this seeder never clobbers an admin's own School
        // Information changes — unlike the settings above, these back the
        // sidebar/login page branding an admin is expected to customize.
        SystemSetting::firstOrCreate(
            ['key' => 'school_name'],
            ['value' => SchoolSettingsService::DEFAULT_SCHOOL_NAME]
        );
        SystemSetting::firstOrCreate(
            ['key' => 'system_display_name'],
            ['value' => SchoolSettingsService::DEFAULT_SYSTEM_NAME]
        );
        SystemSetting::firstOrCreate(
            ['key' => 'system_full_name'],
            ['value' => SchoolSettingsService::DEFAULT_SYSTEM_FULL_NAME]
        );
    }
}
