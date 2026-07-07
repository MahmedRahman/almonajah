<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public const SCHOLARS_SIDEBAR_KEY = 'show_scholars_in_sidebar';

    public static function showScholarsInSidebar(): bool
    {
        return Cache::remember('site_'.self::SCHOLARS_SIDEBAR_KEY, 3600, function () {
            return Setting::getValue(self::SCHOLARS_SIDEBAR_KEY, '0') === '1';
        });
    }

    public static function setShowScholarsInSidebar(bool $value): void
    {
        Setting::setValue(self::SCHOLARS_SIDEBAR_KEY, $value ? '1' : '0', 'boolean', 'إظهار صفحة الشيوخ في القائمة الجانبية');
        Cache::forget('site_'.self::SCHOLARS_SIDEBAR_KEY);
    }
}
