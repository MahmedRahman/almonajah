<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Meta Special-Category / European Region compliance helpers.
 *
 * Religious & spiritual content sites cannot share personal data with Meta
 * for advertising/optimization of European Region visitors under Meta terms.
 */
class MetaCompliance
{
    /**
     * EU + EEA + UK + CH (Meta "European Region" coverage used for privacy blocks).
     *
     * @var list<string>
     */
    public const EUROPEAN_REGION_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        'IS', 'LI', 'NO', // EEA
        'GB', 'UK',      // United Kingdom
        'CH',            // Switzerland
    ];

    /**
     * Only these standard custom_data keys are forwarded (no content labels).
     *
     * @var list<string>
     */
    public const ALLOWED_CUSTOM_DATA_KEYS = [
        'value',
        'currency',
        'content_ids',
        'content_type',
        'num_items',
        'order_id',
        'status',
        'predicted_ltv',
    ];

    public static function isEuropeanRegion(?string $countryCode): bool
    {
        if ($countryCode === null || $countryCode === '' || $countryCode === 'XX' || $countryCode === 'T1') {
            return false;
        }

        return in_array(strtoupper($countryCode), self::EUROPEAN_REGION_COUNTRIES, true);
    }

    /**
     * Detect visitor country from common CDN / proxy headers.
     */
    public static function countryFromRequest(Request $request): ?string
    {
        $candidates = [
            $request->header('CF-IPCountry'),
            $request->header('CloudFront-Viewer-Country'),
            $request->header('X-Country-Code'),
            $request->header('X-Vercel-IP-Country'),
            $request->server('HTTP_CF_IPCOUNTRY'),
        ];

        foreach ($candidates as $value) {
            if (!is_string($value)) {
                continue;
            }
            $code = strtoupper(trim($value));
            if ($code !== '' && preg_match('/^[A-Z]{2}$/', $code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Whether Meta Pixel / CAPI may process this visitor.
     */
    public static function allowsMetaTracking(Request $request): bool
    {
        if (!(bool) config('services.meta.block_european_tracking', true)) {
            return true;
        }

        return ! self::isEuropeanRegion(self::countryFromRequest($request));
    }

    /**
     * Remove custom_data fields that can describe religious interest or intent.
     *
     * @param  array<string, mixed>  $customData
     * @return array<string, mixed>
     */
    public static function sanitizeCustomData(array $customData): array
    {
        $clean = [];

        foreach (self::ALLOWED_CUSTOM_DATA_KEYS as $key) {
            if (!array_key_exists($key, $customData)) {
                continue;
            }
            $value = $customData[$key];
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * Strip query string from event_source_url (may contain feeling/search text).
     */
    public static function sanitizeSourceUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        return $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . $path;
    }
}
