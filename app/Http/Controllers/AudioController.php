<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AudioController extends Controller
{
    private function applySearchFilter($query, string $search): void
    {
        $term = '%' . mb_strtolower($search) . '%';
        $query->where(function($q) use ($term) {
            $q->whereRaw('LOWER(COALESCE(title,"")) LIKE ?', [$term])
              ->orWhereRaw('LOWER(COALESCE(file_name,"")) LIKE ?', [$term])
              ->orWhereRaw('LOWER(COALESCE(speaker_name,"")) LIKE ?', [$term])
              ->orWhereRaw('LOWER(COALESCE(site_description,"")) LIKE ?', [$term])
              ->orWhereRaw('LOWER(COALESCE(transcription_plain,"")) LIKE ?', [$term])
              ->orWhereRaw('LOWER(COALESCE(topics,"")) LIKE ?', [$term])
              ->orWhereHas('categories', function($cq) use ($term) {
                  $cq->whereRaw('LOWER(COALESCE(categories.name,"")) LIKE ?', [$term]);
              });
        });
    }

    public function index(Request $request)
    {
        $query = Asset::publishableUnderAssets()->audioPlatform();
        $category = null;

        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('speaker_name', 'like', "%{$request->speaker_name}%");
        }

        if ($request->has('content_category') && $request->content_category) {
            $categoryName = trim((string) $request->content_category);
            $category = Category::where('show_on_site', true)->where('name', $categoryName)->first();
            $hasContentCategoryColumn = Schema::hasColumn((new Asset)->getTable(), 'content_category');

            if ($category || $hasContentCategoryColumn) {
                $query->where(function ($q) use ($category, $categoryName, $hasContentCategoryColumn) {
                    if ($category) {
                        $q->whereHas('categories', function ($sub) use ($category) {
                            $sub->where('categories.id', $category->id);
                        });
                    } elseif ($hasContentCategoryColumn) {
                        $q->where('content_category', $categoryName);
                    }
                });
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->has('year') && $request->year) {
            $query->where(function($q) use ($request) {
                $q->where('relative_path', 'like', "%{$request->year}%")
                  ->orWhere('year', $request->year);
            });
        }

        $hasCategoryFilter = $request->has('content_category') && trim((string) $request->content_category) !== '';
        $hasSearch = $request->has('search') && trim((string) $request->search) !== '';

        $selectFields = ['id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title', 'orientation', 'is_featured', 'featured_order'];
        $listWith = ['categories:id,name', 'audioFiles:id,asset_id,duration_seconds'];

        if ($hasCategoryFilter && $category) {
            $query->orderByRaw('(SELECT ac.`order` FROM asset_category ac WHERE ac.asset_id = assets.id AND ac.category_id = ?) ASC', [$category->id])
                  ->orderBy('assets.id', 'asc');
        } else {
            $query->orderByRaw('published_at IS NULL ASC')->orderByDesc('published_at')->orderBy('assets.id', 'desc');
        }

        // تحميل المزيد (JSON) — مطابقة لسلوك الرئيسية المرئية
        if ($request->ajax() || $request->wantsJson()) {
            if ($request->get('home_section') === 'all_audio' && ! $request->has('content_category')) {
                $excludeIds = $request->get('exclude_ids', []);
                $excludeIds = is_array($excludeIds) ? array_filter(array_map('intval', $excludeIds)) : [];
                $restQuery = (clone $query)->select($selectFields)->with($listWith);
                if (! empty($excludeIds)) {
                    $restQuery->whereNotIn('id', $excludeIds);
                }
                $page = $restQuery->paginate(24, ['*'], 'page', (int) $request->get('page', 1));
                $page->setCollection($page->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-audio-cards', ['assets' => $page])->render();
                $nextUrl = $page->hasMorePages()
                    ? $page->appends(array_merge($request->query(), ['home_section' => 'all_audio', 'exclude_ids' => $excludeIds]))->nextPageUrl()
                    : null;

                return response()->json([
                    'html' => $html,
                    'has_more' => $page->hasMorePages(),
                    'next_page_url' => $nextUrl,
                ]);
            }
            if ($hasCategoryFilter) {
                $categoryResultsPaginated = (clone $query)
                    ->select(array_merge($selectFields, ['site_description']))
                    ->with($listWith)
                    ->paginate(20, ['*'], 'page', (int) $request->get('page', 1));
                $categoryResultsPaginated->setCollection($categoryResultsPaginated->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-audio-cards', ['assets' => $categoryResultsPaginated])->render();

                return response()->json([
                    'html' => $html,
                    'has_more' => $categoryResultsPaginated->hasMorePages(),
                    'next_page_url' => $categoryResultsPaginated->hasMorePages() ? $categoryResultsPaginated->appends($request->query())->nextPageUrl() : null,
                ]);
            }
        }

        $searchResults = null;
        if ($hasSearch) {
            $searchResults = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with($listWith)
                ->paginate(20)
                ->through([$this, 'mapAssetComputedDuration']);
        }

        $categoryResults = null;
        if ($hasCategoryFilter) {
            $categoryResults = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with($listWith)
                ->paginate(20)
                ->through([$this, 'mapAssetComputedDuration']);
        }

        $bannersHome = Cache::remember('banners_home', 3600, function () {
            return Banner::active()
                ->where(function ($q) {
                    $q->where('show_on_home', true)->orWhere('show_on_categories', true);
                })
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        });
        $bannersRectangle = $bannersHome->where('size', 'rectangle')->values();

        $contentCategories = Cache::remember('home_content_categories_audio_platform', 3600, function () {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function ($q) {
                    $q->publishableUnderAssets()->audioPlatform();
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        $first8 = null;
        $middle16 = null;
        $audioCategorySections = [];
        $restAudios = null;
        $excludeIdsForRest = [];
        $totalHomeAudios = null;
        $assets = null;
        $totalAudio = null;

        if (! $hasCategoryFilter && ! $hasSearch) {
            $totalHomeAudios = (clone $query)->count();
            $totalAudio = $totalHomeAudios;

            $first8 = (clone $query)
                ->reorder()
                ->orderByDesc('is_featured')
                ->orderByRaw('featured_order IS NULL ASC')
                ->orderBy('featured_order', 'asc')
                ->orderByRaw('published_at IS NULL ASC')
                ->orderByDesc('published_at')
                ->orderBy('assets.id', 'desc')
                ->select($selectFields)
                ->with($listWith)
                ->limit(8)
                ->get()
                ->map([$this, 'mapAssetComputedDuration']);
            $first8Ids = $first8->pluck('id')->toArray();
            $excludeAccum = $first8Ids;

            if ($contentCategories->isNotEmpty()) {
                $zigzagCats = $this->zigzagCategories($contentCategories)->take(7);
                $variants = ['scroll', 'grid', 'panel', 'scroll', 'grid', 'panel', 'scroll'];
                $vi = 0;
                foreach ($zigzagCats as $cat) {
                    $catAssets = (clone $query)->whereHas('categories', function ($q) use ($cat) {
                        $q->where('categories.id', $cat->id);
                    })->whereNotIn('id', $excludeAccum)
                        ->select($selectFields)
                        ->with($listWith)
                        ->orderByRaw('published_at IS NULL ASC')
                        ->orderByDesc('published_at')
                        ->orderBy('assets.id', 'desc')
                        ->limit(6)
                        ->get()
                        ->map([$this, 'mapAssetComputedDuration']);
                    if ($catAssets->isEmpty()) {
                        continue;
                    }
                    $audioCategorySections[] = [
                        'category' => $cat,
                        'assets' => $catAssets,
                        'variant' => $variants[$vi % count($variants)],
                    ];
                    $excludeAccum = array_merge($excludeAccum, $catAssets->pluck('id')->all());
                    $vi++;
                }
            }

            if ($audioCategorySections === []) {
                $middle16 = (clone $query)->whereNotIn('id', $first8Ids)
                    ->select($selectFields)
                    ->with($listWith)
                    ->limit(16)
                    ->get()
                    ->map([$this, 'mapAssetComputedDuration']);
                $middle16Ids = $middle16->pluck('id')->toArray();
                $excludeIdsForRest = array_merge($first8Ids, $middle16Ids);
            } else {
                $excludeIdsForRest = $excludeAccum;
            }

            $restAudios = (clone $query)->whereNotIn('id', $excludeIdsForRest)
                ->select($selectFields)
                ->with($listWith)
                ->paginate(24);
            $restAudios->setCollection($restAudios->getCollection()->map([$this, 'mapAssetComputedDuration']));

            $assets = $restAudios;
        }

        $stats = Cache::remember('home_stats_audio_platform', 3600, function() {
            return [
                'total' => Asset::publishableUnderAssets()->audioPlatform()->count(),
                'speakers' => Asset::publishableUnderAssets()->audioPlatform()
                    ->whereNotNull('speaker_name')
                    ->distinct('speaker_name')
                    ->count('speaker_name'),
            ];
        });

        $speakerNames = Cache::remember('home_speaker_names_audio_platform', 3600, function() {
            return Asset::publishableUnderAssets()->audioPlatform()
                ->whereNotNull('speaker_name')
                ->distinct()
                ->pluck('speaker_name')
                ->filter()
                ->sort()
                ->values();
        });

        $categories = Category::where('show_on_site', true)
            ->withCount(['assets' => function($q) {
                $q->publishableUnderAssets()->audioPlatform();
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $years = Cache::remember('home_years_audio_platform', 3600, function() {
            return Asset::publishableUnderAssets()
                ->audioPlatform()
                ->select('relative_path')
                ->get()
                ->map(function ($asset) {
                    if (preg_match('/\b(1[3-4]\d{2})\b/', $asset->relative_path, $matches)) {
                        return $matches[1];
                    }

                    return null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
        });

        return view('audio.home', compact(
            'assets', 'totalAudio', 'totalHomeAudios', 'stats', 'speakerNames', 'contentCategories', 'categories', 'years',
            'searchResults', 'categoryResults',
            'bannersRectangle', 'first8', 'middle16', 'audioCategorySections', 'restAudios', 'excludeIdsForRest'
        ));
    }

    /**
     * يخلط ترتيب التصنيفات (من البداية ومن النهاية) حتى لا تظهر كل الأقسام بنفس التسلسل الممل.
     */
    private function zigzagCategories(Collection $categories): Collection
    {
        $list = $categories->values();
        $n = $list->count();
        if ($n === 0) {
            return $list;
        }
        $out = collect();
        $l = 0;
        $r = $n - 1;
        while ($l <= $r) {
            if ($l === $r) {
                $out->push($list[$l]);
                break;
            }
            $out->push($list[$l]);
            $out->push($list[$r]);
            $l++;
            $r--;
        }

        return $out;
    }

    public function show(Asset $asset, AssetController $assetController)
    {
        if (!$asset->hasAudioPlatformPlayback()) {
            return redirect()->route('assets.show.public', $asset, 302);
        }

        return $assetController->showPublic($asset);
    }

    public function mapAssetComputedDuration($asset)
    {
        $totalSeconds = $asset->duration_seconds;
        if (!$totalSeconds && $asset->relationLoaded('audioFiles')) {
            $totalSeconds = $asset->audioFiles->firstWhere('duration_seconds', '>', 0)?->duration_seconds;
        }
        if ($totalSeconds) {
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;
            $asset->computed_duration = $hours > 0
                ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
                : sprintf('%d:%02d', $minutes, $seconds);
        } else {
            $asset->computed_duration = null;
        }

        return $asset;
    }
}
