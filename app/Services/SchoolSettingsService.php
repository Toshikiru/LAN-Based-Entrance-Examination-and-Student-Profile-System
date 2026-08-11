<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * School Information settings, persisted through the generic SystemSetting
 * key-value store. Academic Year and General Settings are not implemented
 * yet — the source design didn't specify their fields.
 */
class SchoolSettingsService
{
    public const KEYS = [
        'school_name' => 'School Name',
        'system_display_name' => 'System Name',
        'system_full_name' => 'System Full Name',
        'short_code' => 'Short Code',
        'official_email' => 'Official Email',
        'phone_number' => 'Phone Number',
        'mailing_address' => 'Mailing Address',
    ];

    /**
     * Branding falls back to these whenever nothing has been configured yet,
     * so the app looks exactly as it does today until an admin changes it.
     */
    public const DEFAULT_SCHOOL_NAME = 'Talibon Polytechnic College';
    public const DEFAULT_SYSTEM_NAME = 'TPC EntryPoint';
    public const DEFAULT_SYSTEM_FULL_NAME = 'LAN-Based Entrance Examination and Student Profile System for Guidance Services';

    protected const LOGO_KEY = 'school_logo_path';
    protected const FAVICON_KEY = 'favicon_path';
    protected const BRANDING_DISK = 'public';
    protected const BRANDING_DIRECTORY = 'branding';

    /**
     * @return array<string, string>
     */
    public function current(): array
    {
        $values = [];

        foreach (array_keys(self::KEYS) as $key) {
            $values[$key] = (string) SystemSetting::get($key, '');
        }

        return $values;
    }

    /**
     * @param  array<string, string>  $data
     */
    public function update(array $data, User $updatedBy): void
    {
        foreach (array_keys(self::KEYS) as $key) {
            if (array_key_exists($key, $data)) {
                SystemSetting::set($key, $data[$key], $updatedBy->id);
            }
        }
    }

    /**
     * Branding values consumed by every layout, the login page, and the
     * favicon tag (shared app-wide via a view composer — see
     * AppServiceProvider). Uploading a new logo or favicon here is all it
     * takes for those to update everywhere; nothing else needs touching.
     *
     * @return array{school_name: string, system_name: string, system_full_name: string, logo_url: ?string, favicon_url: ?string}
     */
    public function branding(): array
    {
        return [
            'school_name' => SystemSetting::get('school_name') ?: self::DEFAULT_SCHOOL_NAME,
            'system_name' => SystemSetting::get('system_display_name') ?: self::DEFAULT_SYSTEM_NAME,
            'system_full_name' => SystemSetting::get('system_full_name') ?: self::DEFAULT_SYSTEM_FULL_NAME,
            'logo_url' => $this->logoUrl(),
            'favicon_url' => $this->faviconUrl(),
        ];
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        return $path ? Storage::disk(self::BRANDING_DISK)->url($path) : null;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->faviconPath();

        return $path ? Storage::disk(self::BRANDING_DISK)->url($path) : null;
    }

    public function logoPath(): ?string
    {
        return SystemSetting::get(self::LOGO_KEY) ?: null;
    }

    public function faviconPath(): ?string
    {
        return SystemSetting::get(self::FAVICON_KEY) ?: null;
    }

    public function updateLogo(UploadedFile $file, User $updatedBy): void
    {
        $this->replaceImage(self::LOGO_KEY, $file, $updatedBy);
    }

    public function updateFavicon(UploadedFile $file, User $updatedBy): void
    {
        $this->replaceImage(self::FAVICON_KEY, $file, $updatedBy);
    }

    public function removeLogo(): void
    {
        $this->deleteImage(self::LOGO_KEY);
    }

    public function removeFavicon(): void
    {
        $this->deleteImage(self::FAVICON_KEY);
    }

    protected function replaceImage(string $key, UploadedFile $file, User $updatedBy): void
    {
        $old = SystemSetting::get($key);

        $path = $file->store(self::BRANDING_DIRECTORY, self::BRANDING_DISK);

        SystemSetting::set($key, $path, $updatedBy->id);

        if ($old) {
            Storage::disk(self::BRANDING_DISK)->delete($old);
        }
    }

    protected function deleteImage(string $key): void
    {
        $path = SystemSetting::get($key);

        if ($path) {
            Storage::disk(self::BRANDING_DISK)->delete($path);
        }

        SystemSetting::where('key', $key)->delete();
    }
}
