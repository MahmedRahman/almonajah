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
                'soundcloud' => Setting::getValue('social_soundcloud', 'https://soundcloud.com/almonajaah'),
            ];
        });
    }

    public static function platforms(): array
    {
        return [
            'youtube' => ['label' => 'YouTube', 'icon' => 'bi-youtube', 'class' => 'is-youtube'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'bi-instagram', 'class' => 'is-instagram'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'bi-facebook', 'class' => 'is-facebook'],
            'twitter' => ['label' => 'X', 'icon' => 'bi-twitter-x', 'class' => 'is-twitter'],
            'soundcloud' => ['label' => 'SoundCloud', 'icon' => null, 'class' => 'is-soundcloud', 'custom_icon' => 'soundcloud'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'bi-tiktok', 'class' => 'is-tiktok'],
            'telegram' => ['label' => 'Telegram', 'icon' => 'bi-telegram', 'class' => 'is-telegram'],
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'bi-whatsapp', 'class' => 'is-whatsapp'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'bi-linkedin', 'class' => 'is-linkedin'],
        ];
    }

    public static function visible(): \Illuminate\Support\Collection
    {
        $socialLinks = static::all();

        return collect(static::platforms())
            ->map(function (array $meta, string $key) use ($socialLinks) {
                $url = static::url($key, $socialLinks[$key] ?? null);

                return $url ? array_merge($meta, ['url' => $url, 'key' => $key]) : null;
            })
            ->filter()
            ->values();
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
