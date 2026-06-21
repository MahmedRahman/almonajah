<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SocialLinks
{
    public static function all(): array
    {
        return Cache::remember('social_links', 3600, function () {
            return [
                'facebook' => Setting::getValue('social_facebook', ''),
                'twitter' => Setting::getValue('social_twitter', ''),
                'instagram' => Setting::getValue('social_instagram', ''),
                'youtube' => Setting::getValue('social_youtube', ''),
                'linkedin' => Setting::getValue('social_linkedin', ''),
                'tiktok' => Setting::getValue('social_tiktok', ''),
                'whatsapp' => Setting::getValue('social_whatsapp', ''),
                'telegram' => Setting::getValue('social_telegram', ''),
            ];
        });
    }

    public static function hasAny(): bool
    {
        return ! empty(array_filter(static::all()));
    }

    public static function forgetCache(): void
    {
        Cache::forget('social_links');
    }

    public static function url(string $platform, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return match ($platform) {
            'whatsapp' => 'https://wa.me/'.preg_replace('/[^0-9]/', '', $value),
            'telegram' => str_starts_with($value, 'http')
                ? $value
                : 'https://t.me/'.ltrim($value, '@'),
            default => $value,
        };
    }
}
