<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FeelingAssetMatcher
{
    /**
     * @return array{feeling_key: string|null, synonyms: array<int, string>}
     */
    public function resolveFeelingKey(string $input, ?string $chip = null): array
    {
        $taxonomy = config('feelings.taxonomy', []);
        $keys = array_keys($taxonomy);

        if ($chip !== null && $chip !== '' && isset($taxonomy[$chip])) {
            return [
                'feeling_key' => $chip,
                'synonyms' => $taxonomy[$chip],
            ];
        }

        $normalized = $this->normalizeArabic(trim($input));
        if ($normalized === '') {
            return ['feeling_key' => null, 'synonyms' => []];
        }

        // تطابق مباشر على مفتاح الحالة
        foreach ($keys as $key) {
            if ($this->normalizeArabic($key) === $normalized) {
                return [
                    'feeling_key' => $key,
                    'synonyms' => $taxonomy[$key],
                ];
            }
        }

        // تطابق مرادفات داخل النص
        $bestKey = null;
        $bestScore = 0;
        foreach ($taxonomy as $key => $synonyms) {
            foreach ($synonyms as $synonym) {
                $syn = $this->normalizeArabic($synonym);
                if ($syn === '') {
                    continue;
                }
                if ($normalized === $syn || str_contains($normalized, $syn) || str_contains($syn, $normalized)) {
                    $score = mb_strlen($syn);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestKey = $key;
                    }
                }
            }
        }

        if ($bestKey !== null) {
            return [
                'feeling_key' => $bestKey,
                'synonyms' => $taxonomy[$bestKey],
            ];
        }

        // احتياطي: تطبيع عبر DeepSeek إلى مفتاح من القائمة المغلقة
        if (config('feelings.deepseek_normalize', true)) {
            $aiKey = $this->normalizeFeelingViaDeepSeek($input, $keys);
            if ($aiKey !== null && isset($taxonomy[$aiKey])) {
                return [
                    'feeling_key' => $aiKey,
                    'synonyms' => $taxonomy[$aiKey],
                ];
            }
        }

        return ['feeling_key' => null, 'synonyms' => []];
    }

    /**
     * @param  array<int, int|string>  $excludeIds
     * @return array{asset: Asset, audio_url: string|null, excerpt: string, deep_link: string, feeling_key: string}|null
     */
    public function match(string $input, ?string $chip = null, array $excludeIds = []): ?array
    {
        $resolved = $this->resolveFeelingKey($input, $chip);
        $feelingKey = $resolved['feeling_key'];
        $synonyms = $resolved['synonyms'];

        if ($feelingKey === null || empty($synonyms)) {
            return null;
        }

        $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));

        $asset = $this->findMatchingAsset($synonyms, $excludeIds, preferAudio: true)
            ?? $this->findMatchingAsset($synonyms, $excludeIds, preferAudio: false);

        if (! $asset) {
            return null;
        }

        return [
            'asset' => $asset,
            'audio_url' => $this->resolveAudioUrl($asset),
            'excerpt' => $this->buildExcerpt($asset),
            'deep_link' => $this->resolveDeepLink($asset),
            'feeling_key' => $feelingKey,
        ];
    }

    /**
     * @param  array<int, string>  $synonyms
     * @param  array<int, int>  $excludeIds
     */
    private function findMatchingAsset(array $synonyms, array $excludeIds, bool $preferAudio): ?Asset
    {
        $poolSize = (int) config('feelings.candidate_pool', 40);

        $query = Asset::query()
            ->publishableUnderAssets()
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('transcription_plain')->where('transcription_plain', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('transcription')->where('transcription', '!=', '');
                })->orWhere(function ($q3) {
                    $q3->whereNotNull('site_description')->where('site_description', '!=', '');
                });
            })
            ->where(function ($q) use ($synonyms) {
                foreach ($synonyms as $synonym) {
                    $like = '%'.$synonym.'%';
                    $q->orWhere('emotions', 'like', $like)
                        ->orWhere('topics', 'like', $like);
                }
            });

        if ($preferAudio) {
            $query->audioPlatform();
        }

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $candidates = $query
            ->with(['audioFiles' => function ($q) {
                $q->orderBy('id');
            }])
            ->select([
                'id',
                'title',
                'file_name',
                'speaker_name',
                'scholar_id',
                'relative_path',
                'extension',
                'thumbnail_path',
                'cover_path',
                'site_description',
                'transcription',
                'transcription_plain',
                'emotions',
                'topics',
                'intent',
                'duration_seconds',
                'is_publishable',
            ])
            ->inRandomOrder()
            ->limit($poolSize)
            ->get();

        return $candidates->first();
    }

    private function resolveAudioUrl(Asset $asset): ?string
    {
        $audioFile = $asset->relationLoaded('audioFiles')
            ? $asset->audioFiles->sortBy('id')->first()
            : $asset->audioFiles()->orderBy('id')->first();

        if ($audioFile && $audioFile->file_path) {
            return asset('storage/'.$audioFile->file_path);
        }

        $ext = strtolower((string) ($asset->extension ?? ''));
        if (in_array($ext, Asset::AUDIO_EXTENSIONS, true) && $asset->relative_path) {
            return asset('storage/'.$asset->relative_path);
        }

        return null;
    }

    private function resolveDeepLink(Asset $asset): string
    {
        $hasAudio = ($asset->relationLoaded('audioFiles') && $asset->audioFiles->isNotEmpty())
            || in_array(strtolower((string) ($asset->extension ?? '')), Asset::AUDIO_EXTENSIONS, true);

        if ($hasAudio) {
            return route('audio.show', $asset);
        }

        return route('assets.show.public', $asset);
    }

    private function buildExcerpt(Asset $asset): string
    {
        $text = trim((string) ($asset->transcription_plain ?: $asset->transcription ?: $asset->site_description ?: ''));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $limit = (int) config('feelings.excerpt_length', 420);

        return Str::limit($text, $limit, '…');
    }

    /**
     * @param  array<int, string>  $allowedKeys
     */
    private function normalizeFeelingViaDeepSeek(string $feelingText, array $allowedKeys): ?string
    {
        $apiKey = config('deepseek.api_key');
        if (! $apiKey) {
            return null;
        }

        $cacheKey = 'feeling_normalize_'.md5($this->normalizeArabic($feelingText));
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && in_array($cached, $allowedKeys, true)) {
            return $cached;
        }

        $keysList = implode('، ', $allowedKeys);
        $prompt = "صنّف الشعور التالي إلى مفتاح واحد فقط من هذه القائمة المغلقة:\n{$keysList}\n\n"
            ."النص: {$feelingText}\n\n"
            ."أرجع المفتاح العربي وحده بدون شرح.";

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(rtrim((string) config('deepseek.api_url'), '/').'/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'أنت مصنّف مشاعر. أرجع مفتاحًا واحدًا فقط من القائمة المعطاة.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 20,
                ]);

            if (! $response->successful()) {
                Log::warning('Feeling normalize DeepSeek failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            $content = trim($content, " \t\n\r\0\x0B\"'`");

            foreach ($allowedKeys as $key) {
                if ($this->normalizeArabic($content) === $this->normalizeArabic($key)
                    || str_contains($this->normalizeArabic($content), $this->normalizeArabic($key))) {
                    Cache::put($cacheKey, $key, now()->addDays(7));

                    return $key;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Feeling normalize DeepSeek exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function normalizeArabic(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    /**
     * تقرير تغطية سريع للإطلاق.
     *
     * @return array<string, int>
     */
    public function coverageStats(): array
    {
        $base = Asset::publishableUnderAssets();

        return [
            'publishable_total' => (clone $base)->count(),
            'with_emotions' => (clone $base)->whereNotNull('emotions')->where('emotions', '!=', '')->count(),
            'with_topics' => (clone $base)->whereNotNull('topics')->where('topics', '!=', '')->count(),
            'audio_platform' => (clone $base)->audioPlatform()->count(),
            'emotions_and_audio' => (clone $base)->audioPlatform()
                ->whereNotNull('emotions')->where('emotions', '!=', '')
                ->count(),
            'matchable_text' => (clone $base)->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('transcription_plain')->where('transcription_plain', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('transcription')->where('transcription', '!=', '');
                })->orWhere(function ($q3) {
                    $q3->whereNotNull('site_description')->where('site_description', '!=', '');
                });
            })->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('emotions')->where('emotions', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('topics')->where('topics', '!=', '');
                });
            })->count(),
        ];
    }
}
