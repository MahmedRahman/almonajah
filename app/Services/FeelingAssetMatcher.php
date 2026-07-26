<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FeelingAssetMatcher
{
    /**
     * @return array{feeling_key: string|null, synonyms: array<int, string>, search_terms: array<int, string>}
     */
    public function resolveFeelingKey(string $input, ?string $chip = null): array
    {
        $taxonomy = config('feelings.taxonomy', []);
        $keys = array_keys($taxonomy);

        if ($chip !== null && $chip !== '' && isset($taxonomy[$chip])) {
            return [
                'feeling_key' => $chip,
                'synonyms' => $taxonomy[$chip],
                'search_terms' => $this->uniqueTerms(array_merge([$chip], $taxonomy[$chip])),
            ];
        }

        $normalized = $this->normalizeArabic(trim($input));
        if ($normalized === '') {
            return ['feeling_key' => null, 'synonyms' => [], 'search_terms' => []];
        }

        // تطابق مباشر على مفتاح الحالة
        foreach ($keys as $key) {
            if ($this->normalizeArabic($key) === $normalized) {
                return [
                    'feeling_key' => $key,
                    'synonyms' => $taxonomy[$key],
                    'search_terms' => $this->uniqueTerms(array_merge([$key], $taxonomy[$key])),
                ];
            }
        }

        // تطابق مرادفات داخل النص (جملة كاملة أو كلمات منفصلة)
        $bestKey = null;
        $bestScore = 0;
        $inputTokens = $this->tokenizeArabic($normalized);

        foreach ($taxonomy as $key => $synonyms) {
            foreach ($synonyms as $synonym) {
                $syn = $this->normalizeArabic($synonym);
                if ($syn === '' || mb_strlen($syn) < 2) {
                    continue;
                }

                $matched = false;
                if ($normalized === $syn || str_contains($normalized, $syn) || str_contains($syn, $normalized)) {
                    $matched = true;
                } else {
                    foreach ($inputTokens as $token) {
                        if ($token === $syn
                            || (mb_strlen($syn) >= 3 && str_starts_with($token, $syn))
                            || (mb_strlen($token) >= 3 && str_starts_with($syn, $token))) {
                            $matched = true;
                            break;
                        }
                    }
                }

                if ($matched) {
                    $score = mb_strlen($syn);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestKey = $key;
                    }
                }
            }
        }

        if ($bestKey !== null) {
            $extra = $this->extractLocalSearchTerms($normalized);
            return [
                'feeling_key' => $bestKey,
                'synonyms' => $taxonomy[$bestKey],
                'search_terms' => $this->uniqueTerms(array_merge([$bestKey], $taxonomy[$bestKey], $extra)),
            ];
        }

        // تحليل النص عبر DeepSeek: أقرب شعور + كلمات بحث من محتوى المنصة
        if (config('feelings.deepseek_analyze', true) || config('feelings.deepseek_normalize', true)) {
            $ai = $this->analyzeFeelingViaDeepSeek($input, $keys);
            if ($ai !== null) {
                $feelingKey = $ai['feeling_key'];
                $synonyms = $feelingKey && isset($taxonomy[$feelingKey])
                    ? $taxonomy[$feelingKey]
                    : [];
                $terms = $this->uniqueTerms(array_merge(
                    $feelingKey ? [$feelingKey] : [],
                    $synonyms,
                    $ai['search_terms'],
                    $this->extractLocalSearchTerms($normalized)
                ));

                return [
                    'feeling_key' => $feelingKey,
                    'synonyms' => $synonyms,
                    'search_terms' => $terms,
                ];
            }
        }

        // بدون AI: استخرج كلمات من النص للبحث المباشر في المحتوى
        $terms = $this->extractLocalSearchTerms($normalized);

        return [
            'feeling_key' => null,
            'synonyms' => [],
            'search_terms' => $terms,
        ];
    }

    /**
     * @param  array<int, int|string>  $excludeIds
     * @return array{asset: Asset, audio_url: string|null, excerpt: string, deep_link: string, feeling_key: string|null}|null
     */
    public function match(string $input, ?string $chip = null, array $excludeIds = []): ?array
    {
        $resolved = $this->resolveFeelingKey($input, $chip);
        $feelingKey = $resolved['feeling_key'];
        $synonyms = $resolved['synonyms'];
        $searchTerms = $resolved['search_terms'];

        if (empty($searchTerms) && empty($synonyms)) {
            return null;
        }

        $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));
        $terms = $this->uniqueTerms(array_merge($synonyms, $searchTerms));

        $asset = $this->findBestMatchingAsset($terms, $excludeIds, preferAudio: true)
            ?? $this->findBestMatchingAsset($terms, $excludeIds, preferAudio: false);

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
     * يجلب مرشحين من محتوى المنصة ويرتّبهم حسب أقرب تطابق للنص/الشعور.
     *
     * @param  array<int, string>  $terms
     * @param  array<int, int>  $excludeIds
     */
    private function findBestMatchingAsset(array $terms, array $excludeIds, bool $preferAudio): ?Asset
    {
        // كلمات قصيرة جدًا (مثل: هم) تسبب تطابقات خاطئة داخل اللهم/مهم… نستخدمها فقط في تقييم المشاعر الدقيقة
        $queryTerms = array_values(array_filter($terms, fn ($t) => mb_strlen($this->normalizeArabic((string) $t)) >= 3));
        if (empty($queryTerms)) {
            $queryTerms = $terms;
        }

        $candidates = $this->fetchCandidates($queryTerms, $excludeIds, $preferAudio);
        if ($candidates->isEmpty()) {
            return null;
        }

        $scored = $candidates->map(function (Asset $asset) use ($terms) {
            return [
                'asset' => $asset,
                'score' => $this->scoreAsset($asset, $terms),
            ];
        })->sortByDesc('score')->values();

        $best = $scored->first();
        if (! $best || $best['score'] <= 0) {
            return null;
        }

        return $best['asset'];
    }

    /**
     * @param  array<int, string>  $terms
     * @param  array<int, int>  $excludeIds
     * @return Collection<int, Asset>
     */
    private function fetchCandidates(array $terms, array $excludeIds, bool $preferAudio): Collection
    {
        $poolSize = (int) config('feelings.candidate_pool', 80);
        $terms = array_values(array_filter($terms, fn ($t) => mb_strlen(trim((string) $t)) >= 2));

        if (empty($terms)) {
            return collect();
        }

        $query = Asset::query()
            ->publishableUnderAssets();
        $this->applyDuaOnlyConstraints($query);
        $query->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('transcription_plain')->where('transcription_plain', '!=', '');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('transcription')->where('transcription', '!=', '');
                })->orWhere(function ($q3) {
                    $q3->whereNotNull('site_description')->where('site_description', '!=', '');
                });
            })
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $q->orWhere('emotions', 'like', $like)
                        ->orWhere('topics', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('intent', 'like', $like)
                        ->orWhere('transcription_plain', 'like', $like)
                        ->orWhere('site_description', 'like', $like);
                }
            });

        if ($preferAudio) {
            $query->audioPlatform();
        }

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
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
            ->limit($poolSize)
            ->get();
    }

    /**
     * يقيّد الاستعلام على الأدعية فقط ويستبعد المواعظ/الأفلام/الحلقات العامة.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Asset>  $query
     */
    private function applyDuaOnlyConstraints($query): void
    {
        $query->where(function ($q) {
            $q->where('intent', 'like', 'دعاء%')
                ->orWhere('title', 'like', '%دعاء%')
                ->orWhere('title', 'like', 'اللهم%')
                ->orWhere('title', 'like', 'رب %')
                ->orWhere('title', 'like', 'يا رب%')
                ->orWhere('original_relative_path', 'like', '%ادعية%')
                ->orWhere('original_path', 'like', '%ادعية%');
        })->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNull('intent')
                    ->orWhere(function ($ok) {
                        $ok->where('intent', 'not like', 'تعليم%')
                            ->where('intent', 'not like', 'موعظة%')
                            ->where('intent', 'not like', 'تفسير%')
                            ->where('intent', 'not like', 'فيلم%');
                    });
            })->orWhere('title', 'like', '%دعاء%')
                ->orWhere('title', 'like', 'اللهم%');
        })->where(function ($q) {
            $q->whereNull('title')
                ->orWhere(function ($t) {
                    $t->where('title', 'not like', '%فيلم%')
                        ->where('title', 'not like', '%قصة%')
                        ->where('title', 'not like', '%حلقة%');
                })
                ->orWhere('title', 'like', '%دعاء%')
                ->orWhere('title', 'like', 'اللهم%');
        });
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function scoreAsset(Asset $asset, array $terms): int
    {
        $emotionTokens = $this->splitFieldTokens((string) ($asset->emotions ?? ''));
        $topicTokens = $this->splitFieldTokens((string) ($asset->topics ?? ''));
        $title = $this->normalizeArabic((string) ($asset->title ?? ''));
        $intent = $this->normalizeArabic((string) ($asset->intent ?? ''));
        $desc = $this->normalizeArabic((string) ($asset->site_description ?? ''));
        $plain = $this->normalizeArabic(mb_substr((string) ($asset->transcription_plain ?? $asset->transcription ?? ''), 0, 1200));

        $score = 0;

        foreach ($terms as $term) {
            $t = $this->normalizeArabic($term);
            if ($t === '' || mb_strlen($t) < 2) {
                continue;
            }

            $exactEmotionHit = false;
            foreach ($emotionTokens as $emotion) {
                if ($emotion === $t || (mb_strlen($t) >= 3 && (str_starts_with($emotion, $t) || str_starts_with($t, $emotion)))) {
                    $score += 14;
                    $exactEmotionHit = true;
                    break;
                }
            }

            // كلمات بطول حرفين: لا تُبحث داخل العنوان/النص لتجنب تطابق اللهم←هم
            if (mb_strlen($t) < 3) {
                continue;
            }

            foreach ($topicTokens as $topic) {
                if ($topic === $t || str_contains($topic, $t) || str_contains($t, $topic)) {
                    $score += 10;
                    break;
                }
            }

            if ($title !== '' && str_contains($title, $t)) {
                $score += 7;
            }
            if ($intent !== '' && str_contains($intent, $t)) {
                $score += 6;
            }
            if ($desc !== '' && str_contains($desc, $t)) {
                $score += 3;
            }
            if ($plain !== '' && str_contains($plain, $t)) {
                $score += 2;
            }

            if ($exactEmotionHit) {
                // لا تغيير إضافي
            }
        }

        if (! empty($emotionTokens)) {
            $score += 1;
        }

        // تفضيل واضح للأدعية على أي محتوى عام تسرّب للمرشحين
        if (str_contains($intent, 'دعاء')) {
            $score += 25;
        }
        if (str_contains($title, 'دعاء') || str_contains($title, 'اللهم') || str_starts_with($title, 'رب')) {
            $score += 8;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function splitFieldTokens(string $field): array
    {
        $parts = preg_split('/[\n,،|\/]+/u', $field) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $n = $this->normalizeArabic(trim($part));
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return $out;
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
     * يحلل نص المستخدم: أقرب شعور من القائمة + كلمات بحث لمحتوى الأدعية.
     *
     * @param  array<int, string>  $allowedKeys
     * @return array{feeling_key: string|null, search_terms: array<int, string>}|null
     */
    private function analyzeFeelingViaDeepSeek(string $feelingText, array $allowedKeys): ?array
    {
        $apiKey = config('deepseek.api_key');
        if (! $apiKey) {
            return null;
        }

        $cacheKey = 'feeling_analyze_v2_'.md5($this->normalizeArabic($feelingText));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('feeling_key', $cached) && array_key_exists('search_terms', $cached)) {
            return $cached;
        }

        $keysList = implode('، ', $allowedKeys);
        $prompt = "المستخدم يصف حالته النفسية أو حاجته من الدعاء.\n"
            ."المطلوب: اختر أقرب شعور واحد فقط من القائمة المغلقة التالية، واستخرج 3 إلى 6 كلمات عربية قصيرة للبحث داخل أدعية المنصة (مشاعر/مواضيع/نص).\n"
            ."القائمة: {$keysList}\n\n"
            ."نص المستخدم: {$feelingText}\n\n"
            ."أرجع JSON فقط بهذا الشكل بدون شرح:\n"
            .'{"feeling":"قلق","terms":["هم","توتر","سكينة"]}';

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(rtrim((string) config('deepseek.api_url'), '/').'/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'أنت محلل مشاعر لمنصة أدعية. تطابق نص المستخدم مع أقرب شعور وكلمات بحث داخل الأدعية فقط (وليس المواعظ أو المحتوى العام). أرجع JSON فقط.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.15,
                    'max_tokens' => 120,
                ]);

            if (! $response->successful()) {
                Log::warning('Feeling analyze DeepSeek failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                // احتياطي: التطبيع القديم لمفتاح واحد
                $legacy = $this->normalizeFeelingViaDeepSeek($feelingText, $allowedKeys);
                if ($legacy) {
                    return [
                        'feeling_key' => $legacy,
                        'search_terms' => [$legacy],
                    ];
                }

                return null;
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            $parsed = $this->parseAnalyzeJson($content, $allowedKeys);

            if ($parsed !== null) {
                Cache::put($cacheKey, $parsed, now()->addDays(7));

                return $parsed;
            }

            $legacy = $this->normalizeFeelingViaDeepSeek($feelingText, $allowedKeys);
            if ($legacy) {
                return [
                    'feeling_key' => $legacy,
                    'search_terms' => [$legacy],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Feeling analyze DeepSeek exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $allowedKeys
     * @return array{feeling_key: string|null, search_terms: array<int, string>}|null
     */
    private function parseAnalyzeJson(string $content, array $allowedKeys): ?array
    {
        $content = trim($content);
        if (preg_match('/\{.*\}/us', $content, $m)) {
            $content = $m[0];
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            return null;
        }

        $feelingRaw = trim((string) ($data['feeling'] ?? $data['feeling_key'] ?? ''));
        $feelingKey = null;
        $feelingNorm = $this->normalizeArabic($feelingRaw);

        foreach ($allowedKeys as $key) {
            $keyNorm = $this->normalizeArabic($key);
            if ($feelingNorm === $keyNorm || str_contains($feelingNorm, $keyNorm)) {
                $feelingKey = $key;
                break;
            }
        }

        $terms = [];
        foreach ((array) ($data['terms'] ?? $data['search_terms'] ?? []) as $term) {
            $term = trim((string) $term);
            if (mb_strlen($term) >= 2) {
                $terms[] = $term;
            }
        }

        if ($feelingKey) {
            $terms[] = $feelingKey;
        }

        $terms = $this->uniqueTerms($terms);
        if ($feelingKey === null && empty($terms)) {
            return null;
        }

        return [
            'feeling_key' => $feelingKey,
            'search_terms' => $terms,
        ];
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
            .'أرجع المفتاح العربي وحده بدون شرح.';

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

    /**
     * @return array<int, string>
     */
    private function extractLocalSearchTerms(string $normalizedInput): array
    {
        return $this->tokenizeArabic($normalizedInput);
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeArabic(string $normalizedInput): array
    {
        $stop = [
            'انا', 'اني', 'اشعر', 'ب', 'في', 'من', 'على', 'عن', 'الى', 'الي',
            'ال', 'هذا', 'هذه', 'ذلك', 'مع', 'جدا', 'او', 'و', 'ثم', 'قد', 'كان',
            'يكون', 'اليوم', 'الان', 'نفسي', 'حالتي', 'حالي', 'شيء', 'شيئا',
            'الي', 'ان', 'ما', 'لا', 'لم', 'لن', 'هناك', 'هنا', 'كل', 'اي',
            'اريد', 'عاوز', 'عايز', 'نفسي', 'قلي',
        ];

        $parts = preg_split('/[\s،,.\-!?؟:;]+/u', $normalizedInput) ?: [];
        $terms = [];
        foreach ($parts as $part) {
            $part = trim($part);
            // إزالة واو العطف في أول الكلمة: ومكسور → مكسور
            if (str_starts_with($part, 'و') && mb_strlen($part) > 3) {
                $part = mb_substr($part, 1);
            }
            if (mb_strlen($part) < 3) {
                continue;
            }
            if (in_array($part, $stop, true)) {
                continue;
            }
            $terms[] = $part;
        }

        return $this->uniqueTerms($terms);
    }

    /**
     * @param  array<int, string>  $terms
     * @return array<int, string>
     */
    private function uniqueTerms(array $terms): array
    {
        $seen = [];
        $out = [];
        foreach ($terms as $term) {
            $term = trim((string) $term);
            if ($term === '' || mb_strlen($term) < 2) {
                continue;
            }
            $key = $this->normalizeArabic($term);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $term;
        }

        return $out;
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
        $duas = Asset::publishableUnderAssets();
        $this->applyDuaOnlyConstraints($duas);

        $matchable = Asset::publishableUnderAssets();
        $this->applyDuaOnlyConstraints($matchable);
        $matchable->where(function ($q) {
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
        });

        return [
            'publishable_total' => (clone $base)->count(),
            'duas_only' => $duas->count(),
            'with_emotions' => (clone $base)->whereNotNull('emotions')->where('emotions', '!=', '')->count(),
            'with_topics' => (clone $base)->whereNotNull('topics')->where('topics', '!=', '')->count(),
            'audio_platform' => (clone $base)->audioPlatform()->count(),
            'emotions_and_audio' => (clone $base)->audioPlatform()
                ->whereNotNull('emotions')->where('emotions', '!=', '')
                ->count(),
            'matchable_text' => $matchable->count(),
        ];
    }
}
