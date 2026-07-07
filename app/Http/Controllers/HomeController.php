<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\Scholar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /** استعلام أساسي: محتوى مرئي منشور تحت assets/ */
    private function basePublicVideoQuery()
    {
        return Asset::publishableUnderAssets()->videos();
    }

    private function applyPublishedPlaylistAssetsConstraint($query): void
    {
        $query->publishableUnderAssets()->videos();
    }

    private function playlistTreeHasPublishedVideosConstraint($query): void
    {
        $query->where(function ($q) {
            $q->whereHas('assets', fn ($assets) => $this->applyPublishedPlaylistAssetsConstraint($assets))
                ->orWhereHas('children', function ($children) {
                    $children->where('is_visible', true)
                        ->where(function ($childQuery) {
                            $childQuery->whereHas('assets', fn ($assets) => $this->applyPublishedPlaylistAssetsConstraint($assets))
                                ->orWhereHas('children', function ($grandchildren) {
                                    $grandchildren->where('is_visible', true)
                                        ->whereHas('assets', fn ($assets) => $this->applyPublishedPlaylistAssetsConstraint($assets));
                                });
                        });
                });
        });
    }

    /**
     * تطبيق فلتر البحث (ذكي، عدم مراعاة حالة الأحرف): عنوان، شيخ، وصف، محتوى نصي، topics، تصنيفات
     */
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
              })
              ->orWhereHas('playlists', function ($pq) use ($term) {
                  $pq->whereRaw('LOWER(COALESCE(playlists.title,"")) LIKE ?', [$term])
                      ->orWhereRaw('LOWER(COALESCE(playlists.description,"")) LIKE ?', [$term]);
              });
        });
    }

    private function basePublicSearchablePlaylistsQuery()
    {
        return Playlist::query()
            ->where('is_visible', true)
            ->where(fn ($q) => $this->playlistTreeHasPublishedVideosConstraint($q));
    }

    private function applyPlaylistSearchFilter($query, string $search): void
    {
        $term = '%'.mb_strtolower($search).'%';
        $query->where(function ($q) use ($term) {
            $q->whereRaw('LOWER(COALESCE(title,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(description,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(slug,"")) LIKE ?', [$term]);
        });
    }

    private function playlistSearchSubtitle(Playlist $playlist): string
    {
        $playlist->loadMissing(['parent.parent']);
        $parts = [];
        if ($playlist->parent?->parent) {
            $parts[] = $playlist->parent->parent->title;
        }
        if ($playlist->parent) {
            $parts[] = $playlist->parent->title;
        }

        return $parts !== [] ? implode(' / ', $parts) : 'قائمة تشغيل';
    }

    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['results' => []]);
        }
        $isAudio = $request->get('type') === 'audio';
        $results = collect();

        if (! $isAudio) {
            $playlistQuery = $this->basePublicSearchablePlaylistsQuery();
            $this->applyPlaylistSearchFilter($playlistQuery, $q);
            $playlists = $playlistQuery
                ->select('id', 'title', 'description', 'image_path', 'parent_id')
                ->with(['parent:id,title,parent_id', 'parent.parent:id,title'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(5)
                ->get();

            $results = $results->concat($playlists->map(function (Playlist $playlist) {
                return [
                    'type' => 'playlist',
                    'id' => $playlist->id,
                    'title' => $playlist->title,
                    'subtitle' => $this->playlistSearchSubtitle($playlist),
                    'image_path' => $playlist->image_path,
                    'url' => route('public.playlist.show', $playlist),
                ];
            }));
        }

        $query = $isAudio
            ? Asset::publishableUnderAssets()->audioPlatform()
            : $this->basePublicVideoQuery();
        $this->applySearchFilter($query, $q);
        $assetLimit = $isAudio ? 10 : 8;
        $assets = $query->select('id', 'title', 'file_name', 'speaker_name', 'thumbnail_path')
            ->orderBy('id', 'desc')
            ->limit($assetLimit)
            ->get();
        $results = $results->concat($assets->map(function ($asset) use ($isAudio) {
            return [
                'type' => 'asset',
                'id' => $asset->id,
                'title' => $asset->title ?: $asset->file_name ?: ($isAudio ? 'صوت #'.$asset->id : 'فيديو #'.$asset->id),
                'speaker_name' => $asset->speaker_name,
                'thumbnail_path' => $asset->thumbnail_path,
                'url' => $isAudio ? route('audio.show', $asset) : route('assets.show.public', $asset),
            ];
        }));

        return response()->json(['results' => $results->values()]);
    }

    public function index(Request $request)
    {
        // جلب الفيديوهات المنقولة إلى الموقع والقابلة للنشر فقط
        // تحسين: استخدام whereIn بدلاً من like إذا أمكن، أو استخدام index
        $query = $this->basePublicVideoQuery();

        // البحث (ذكي، عدم مراعاة حالة الأحرف)
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        // فلترة حسب اسم المتحدث
        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('speaker_name', 'like', "%{$request->speaker_name}%");
        }

        // فلترة حسب تصنيف المحتوى: إظهار الفيديوهات المرتبطة بالتصنيف فقط (جدول الربط asset_category)
        // عند الضغط من السايدبار التصنيف من جدول categories فيُعتمد الربط فقط ليتطابق العدد مع الصفحة
        if ($request->has('content_category') && $request->content_category) {
            $categoryName = trim((string) $request->content_category);
            // المطابقة بالاسم فقط — تجنّب استخدام slug لأن slug(النص العربي) قد يعيد قيمة فارغة فيطابق تصنيفاً خاطئاً
            $category = Category::where('show_on_site', true)->where('name', $categoryName)->first();
            $hasContentCategoryColumn = Schema::hasColumn((new Asset)->getTable(), 'content_category');

            if ($category || $hasContentCategoryColumn) {
                $query->where(function ($q) use ($category, $categoryName, $hasContentCategoryColumn) {
                    if ($category) {
                        // التصنيف من القائمة: نعتمد على الربط فقط ليتطابق مع العدد المعروض بجانب التصنيف
                        $q->whereHas('categories', function ($sub) use ($category) {
                            $sub->where('categories.id', $category->id);
                        });
                    } else {
                        // تصنيف غير موجود في الجدول (رابط قديم): الاعتماد على عمود content_category فقط
                        if ($hasContentCategoryColumn) {
                            $q->where('content_category', $categoryName);
                        }
                    }
                });
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        // فلترة حسب السنة الهجرية (من relative_path أو year)
        if ($request->has('year') && $request->year) {
            $query->where(function($q) use ($request) {
                $q->where('relative_path', 'like', "%{$request->year}%")
                  ->orWhere('year', $request->year);
            });
        }

        // الترتيب: الرئيسية = الأحدث أولاً (آخر فيديو نُشر يظهر في الأول). عند التصنيف = ترتيب التصنيف
        $hasCategoryFilter = $request->has('content_category') && trim((string) $request->content_category) !== '';
        if ($hasCategoryFilter && $category) {
            $query->orderByRaw('(SELECT ac.`order` FROM asset_category ac WHERE ac.asset_id = assets.id AND ac.category_id = ?) ASC', [$category->id])
                  ->orderBy('assets.id', 'asc');
        } else {
            // الأحدث ← الأقدم حسب تاريخ النشر (published_at)، ثم id للقيم الفارغة
            $query->orderByRaw('published_at IS NULL ASC')->orderByDesc('published_at')->orderBy('assets.id', 'desc');
        }

        $selectFields = ['id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title', 'orientation'];

        // تحميل المزيد: الفيديوهات العرضية المتبقية (بعد المميزة + الطولية)
        if ($request->ajax() || $request->wantsJson()) {
            if ($request->get('home_section') === 'all_videos' && !$request->has('content_category')) {
                $excludeIds = $request->get('exclude_ids', []);
                $excludeIds = is_array($excludeIds) ? array_filter(array_map('intval', $excludeIds)) : [];
                $restQuery = (clone $query)->select($selectFields)->with('categories:id,name');
                if (!empty($excludeIds)) {
                    $restQuery->whereNotIn('id', $excludeIds);
                }
                $restQuery->where(function ($q) {
                    $q->where('orientation', '!=', 'portrait')
                        ->orWhereNull('orientation');
                });
                $allVideosPage = $restQuery->paginate(24, ['*'], 'page', $request->get('page', 1));
                $allVideosPage->setCollection($allVideosPage->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-video-cards', ['assets' => $allVideosPage, 'forceLandscape' => true])->render();
                $nextUrl = $allVideosPage->hasMorePages()
                    ? $allVideosPage->appends(array_merge($request->query(), ['home_section' => 'all_videos', 'exclude_ids' => $excludeIds]))->nextPageUrl()
                    : null;
                return response()->json([
                    'html' => $html,
                    'has_more' => $allVideosPage->hasMorePages(),
                    'next_page_url' => $nextUrl,
                ]);
            }
            // تحميل المزيد لصفحة التصنيف — عرضي أو طولي
            if ($request->has('content_category') && trim((string) $request->content_category) !== '') {
                $section = $request->get('category_section', 'landscape');
                $pageName = $section === 'portrait' ? 'portrait_page' : 'landscape_page';
                $categoryQuery = (clone $query)
                    ->select(array_merge($selectFields, ['site_description']))
                    ->with('categories:id,name');

                if ($section === 'portrait') {
                    $categoryQuery->where('orientation', 'portrait');
                    $cardOptions = ['forceLandscape' => false, 'useThumbnail' => true];
                } else {
                    $this->applyNonPortraitOrientationConstraint($categoryQuery);
                    $cardOptions = ['forceLandscape' => true];
                }

                $categoryResultsPaginated = $categoryQuery
                    ->paginate(20, ['*'], $pageName, (int) $request->get($pageName, $request->get('page', 1)));
                $categoryResultsPaginated->setCollection($categoryResultsPaginated->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-video-cards', array_merge(['assets' => $categoryResultsPaginated], $cardOptions))->render();

                return response()->json([
                    'html' => $html,
                    'has_more' => $categoryResultsPaginated->hasMorePages(),
                    'next_page_url' => $categoryResultsPaginated->hasMorePages()
                        ? $categoryResultsPaginated->appends(array_merge($request->query(), ['category_section' => $section]))->nextPageUrl()
                        : null,
                ]);
            }
        }

        $categoryLandscapeResults = null;
        $categoryPortraitResults = null;

        // عند عرض تصنيف: فيديوهات عرضية أولاً ثم الطولية
        if ($hasCategoryFilter) {
            $categoryBaseQuery = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with('categories:id,name');

            $categoryLandscapeResults = (clone $categoryBaseQuery);
            $this->applyNonPortraitOrientationConstraint($categoryLandscapeResults);
            $categoryLandscapeResults = $categoryLandscapeResults
                ->paginate(20, ['*'], 'landscape_page')
                ->through([$this, 'mapAssetComputedDuration']);

            $categoryPortraitResults = (clone $categoryBaseQuery)
                ->where('orientation', 'portrait')
                ->paginate(20, ['*'], 'portrait_page')
                ->through([$this, 'mapAssetComputedDuration']);
        }

        $categoryResults = null;

        // بنرات الرئيسية (مطلوبة قبل تقسيم المحتوى لاحتساب البنر الأفقي ضمن الـ ٨)
        $bannersHome = Cache::remember('banners_home', 3600, function() {
            return Banner::active()
                ->where(function($q) {
                    $q->where('show_on_home', true)->orWhere('show_on_categories', true);
                })
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        });
        $bannersRectangle = $bannersHome->where('size', 'rectangle')->values();
        $bannersVertical = $bannersHome->where('size', 'vertical')->values();
        $bannersLandscape = $bannersHome->where('size', 'landscape')->values();

        // مميزة (٨) + طولية + عرضية (الباقي مع تحميل المزيد)
        $first8 = null;
        $portraitSection = null;
        $restVideos = null;
        $excludeIdsForRest = [];
        if (!$hasCategoryFilter) {
            // أول ٨: المميزة أولاً، ثم حسب ترتيب featured_order (الأصغر أولاً)، ثم تاريخ النشر
            $first8 = (clone $query)
                ->reorder()
                ->orderByDesc('is_featured')
                ->orderByRaw('featured_order IS NULL ASC')  // ذات الترتيب المعيّن أولاً
                ->orderBy('featured_order', 'asc')
                ->orderByRaw('published_at IS NULL ASC')
                ->orderByDesc('published_at')
                ->orderBy('assets.id', 'desc')
                ->select($selectFields)
                ->with('categories:id,name')
                ->limit(8)
                ->get();
            $first8 = $first8->map([$this, 'mapAssetComputedDuration']);
            $first8Ids = $first8->pluck('id')->toArray();

            // فيديوهات طولية متنوعة (حد أقصى ٢ من كل برنامج/قائمة رئيسية)
            $portraitSection = $this->diversifiedPortraitSection(
                (clone $query)->select($selectFields)->with('categories:id,name'),
                $first8Ids,
                16,
                2
            );
            $portraitIds = $portraitSection->pluck('id')->toArray();

            $excludeIdsForRest = array_merge($first8Ids, $portraitIds);
            $restVideos = (clone $query)->whereNotIn('id', $excludeIdsForRest)
                ->where(function ($q) {
                    $q->where('orientation', '!=', 'portrait')
                        ->orWhereNull('orientation');
                })
                ->select($selectFields)
                ->with('categories:id,name')
                ->paginate(24);
            $restVideos->setCollection($restVideos->getCollection()->map([$this, 'mapAssetComputedDuration']));

            $totalHomeVideos = (clone $query)->count();
            $assets = $restVideos;
        } else {
            $totalHomeVideos = null;
            $assets = null;
        }

        // عند وجود بحث: قوائم التشغيل + قائمة موحدة لنتائج البحث (بدون إعلانات في الواجهة)
        $searchPlaylistResults = null;
        $searchResults = null;
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $searchTerm = trim((string) $request->search);

            $playlistResultsQuery = $this->basePublicSearchablePlaylistsQuery();
            $this->applyPlaylistSearchFilter($playlistResultsQuery, $searchTerm);
            $searchPlaylistResults = $playlistResultsQuery
                ->select('id', 'title', 'description', 'image_path', 'parent_id')
                ->with(['parent:id,title,parent_id', 'parent.parent:id,title'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->paginate(12, ['*'], 'playlists_page');
            $searchPlaylistResults->getCollection()->transform(function (Playlist $playlist) {
                $playlist->search_subtitle = $this->playlistSearchSubtitle($playlist);
                $playlist->search_video_count = $playlist->totalPublishedVideosCount();

                return $playlist;
            });

            $searchResults = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with('categories:id,name')
                ->paginate(20)
                ->through([$this, 'mapAssetComputedDuration']);
        }
        
        // جلب Shorts (فيديوهات قصيرة وعمودية - أقل من 60 ثانية وعمودية) مع cache
        // تحسين: تقليل عدد Shorts المعروضة
        $shortsQuery = Cache::remember('home_shorts_video', 1800, function() {
            $shorts = Asset::publishableUnderAssets()
                ->videos()
                ->where('orientation', 'portrait')
                ->where(function($q) {
                    $q->where('duration_seconds', '<=', 60)
                      ->orWhereNull('duration_seconds');
                })
                ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
                ->orderBy('id', 'desc')
                ->limit(15) // تقليل من 20 إلى 15
                ->get();
            
            // حساب duration_formatted مسبقاً
            return $shorts->map(function($short) {
                // حساب duration_formatted
                if ($short->duration_seconds) {
                    $hours = floor($short->duration_seconds / 3600);
                    $minutes = floor(($short->duration_seconds % 3600) / 60);
                    $seconds = $short->duration_seconds % 60;
                    if ($hours > 0) {
                        $short->computed_duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                    } else {
                        $short->computed_duration = sprintf('%d:%02d', $minutes, $seconds);
                    }
                } else {
                    $short->computed_duration = null;
                }
                
                return $short;
            });
        });

        // إحصائيات (مع cache)
        $stats = \Illuminate\Support\Facades\Cache::remember('home_stats_video', 3600, function() {
            return [
                'total' => Asset::publishableUnderAssets()->videos()->count(),
                'speakers' => Asset::publishableUnderAssets()->videos()
                    ->whereNotNull('speaker_name')
                    ->distinct('speaker_name')
                    ->count('speaker_name'),
            ];
        });

        // أسماء المتحدثين المتاحة (مع cache)
        $speakerNames = \Illuminate\Support\Facades\Cache::remember('home_speaker_names_video', 3600, function() {
            return Asset::publishableUnderAssets()->videos()
                ->whereNotNull('speaker_name')
                ->distinct()
                ->pluck('speaker_name')
                ->filter()
                ->sort()
                ->values();
        });

        // تصنيفات المحتوى المتاحة (من جدول categories) - فقط التي تُعرض في الموقع
        $contentCategories = Cache::remember('home_content_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->publishableUnderAssets()->videos();
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });
        
        // جلب التصنيفات للقائمة الجانبية "استكشاف" — العدد من جدول الربط asset_category فقط (ينطبق على صفحة التصنيف)
        $categories = Category::where('show_on_site', true)
            ->withCount(['assets' => function($q) {
                $q->publishableUnderAssets()->videos();
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // السنوات الهجرية المتاحة (مع cache - استخدام SQL مباشرة)
        $years = Cache::remember('home_years_video', 3600, function() {
            // استخدام استعلام SQL مباشر لاستخراج السنوات من relative_path
            $years = DB::table('assets')
                ->where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
                ->whereNotNull('relative_path')
                ->select('relative_path')
                ->get()
                ->map(function($item) {
                    if (preg_match('/\b(1[3-4]\d{2})\b/', $item->relative_path, $matches)) {
                        return $matches[1];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
            
            return $years;
        });

        return view('home', compact(
            'assets', 'first8', 'portraitSection', 'restVideos', 'excludeIdsForRest', 'totalHomeVideos',
            'shortsQuery', 'stats', 'speakerNames', 'contentCategories', 'categories', 'years',
            'bannersRectangle', 'bannersVertical', 'bannersLandscape', 'searchResults', 'searchPlaylistResults', 'categoryResults',
            'categoryLandscapeResults', 'categoryPortraitResults'
        ));
    }

    /**
     * إضافة computed_duration لأجل العرض في الكروت.
     */
    public function mapAssetComputedDuration($asset)
    {
        if ($asset->duration_seconds) {
            $hours = floor($asset->duration_seconds / 3600);
            $minutes = floor(($asset->duration_seconds % 3600) / 60);
            $seconds = $asset->duration_seconds % 60;
            $asset->computed_duration = $hours > 0
                ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
                : sprintf('%d:%02d', $minutes, $seconds);
        } else {
            $asset->computed_duration = null;
        }
        return $asset;
    }

    private function applyNonPortraitOrientationConstraint($query): void
    {
        $query->where(function ($q) {
            $q->where('orientation', '!=', 'portrait')
                ->orWhereNull('orientation');
        });
    }

    /**
     * خريطة playlist_id => root playlist id (البرنامج الرئيسي).
     */
    private function playlistRootIdMap(): array
    {
        return Cache::remember('playlist_root_id_map', 3600, function () {
            $playlists = Playlist::query()->select('id', 'parent_id')->get()->keyBy('id');
            $map = [];

            foreach ($playlists as $playlist) {
                $current = $playlist;
                while ($current->parent_id && isset($playlists[$current->parent_id])) {
                    $current = $playlists[$current->parent_id];
                }
                $map[$playlist->id] = $current->id;
            }

            return $map;
        });
    }

    private function portraitProgramKey(Asset $asset, array $rootMap): string
    {
        $playlistIds = $asset->relationLoaded('playlists')
            ? $asset->playlists->pluck('id')
            : collect();

        if ($playlistIds->isEmpty()) {
            return 'standalone:'.$asset->id;
        }

        $rootId = $playlistIds
            ->map(fn ($id) => $rootMap[$id] ?? $id)
            ->sort()
            ->first();

        return 'program:'.$rootId;
    }

    /**
     * فيديوهات طولية من برامج مختلفة — حد أقصى $maxPerProgram لكل قائمة تشغيل رئيسية.
     */
    private function diversifiedPortraitSection($baseQuery, array $excludeIds, int $limit = 16, int $maxPerProgram = 2)
    {
        $rootMap = $this->playlistRootIdMap();
        $poolSize = max($limit * 4, 48);

        $candidates = (clone $baseQuery)
            ->where('orientation', 'portrait')
            ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->with('playlists:id')
            ->limit($poolSize)
            ->get();

        $programCounts = [];
        $selected = collect();

        foreach ($candidates as $asset) {
            if ($selected->count() >= $limit) {
                break;
            }

            $programKey = $this->portraitProgramKey($asset, $rootMap);
            $currentCount = $programCounts[$programKey] ?? 0;
            if ($currentCount >= $maxPerProgram) {
                continue;
            }

            $programCounts[$programKey] = $currentCount + 1;
            $selected->push($asset);
        }

        return $selected->map([$this, 'mapAssetComputedDuration']);
    }

    public function shorts(Request $request)
    {
        // جلب جميع Shorts (فيديوهات عمودية - نعرض جميع الفيديوهات العمودية القابلة للنشر)
        $query = Asset::publishableUnderAssets()
            ->videos()
            ->where('orientation', 'portrait');

        // البحث (ذكي، عدم مراعاة حالة الأحرف)
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        // فلترة حسب اسم المتحدث
        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('speaker_name', 'like', "%{$request->speaker_name}%");
        }

        // الترتيب
        $query->orderBy('id', 'desc');

        // استخدام select فقط للحقول المطلوبة مع eager load لـ HLS versions
        $shorts = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->with(['hlsVersions' => function($q) {
                $q->select('id', 'asset_id', 'resolution', 'playlist_path', 'master_playlist_path');
            }, 'categories:id,name'])
            ->paginate(20);

        // حساب duration_formatted مسبقاً
        $shorts->getCollection()->transform(function($short) {
            // حساب duration_formatted
            if ($short->duration_seconds) {
                $hours = floor($short->duration_seconds / 3600);
                $minutes = floor(($short->duration_seconds % 3600) / 60);
                $seconds = $short->duration_seconds % 60;
                if ($hours > 0) {
                    $short->computed_duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $short->computed_duration = sprintf('%d:%02d', $minutes, $seconds);
                }
            } else {
                $short->computed_duration = null;
            }
            
            return $short;
        });

        // إحصائيات
        $stats = Cache::remember('shorts_stats_video', 3600, function() {
            return [
                'total' => Asset::publishableUnderAssets()
                    ->videos()
                    ->where('orientation', 'portrait')
                    ->where(function($q) {
                        $q->where('duration_seconds', '<=', 60)
                          ->orWhereNull('duration_seconds');
                    })
                    ->count(),
            ];
        });

        // أسماء المتحدثين المتاحة
        $speakerNames = Cache::remember('shorts_speaker_names_video', 3600, function() {
            return Asset::publishableUnderAssets()
                ->videos()
                ->where('orientation', 'portrait')
                ->where(function($q) {
                    $q->where('duration_seconds', '<=', 60)
                      ->orWhereNull('duration_seconds');
                })
                ->whereNotNull('speaker_name')
                ->distinct()
                ->pluck('speaker_name')
                ->filter()
                ->sort()
                ->values();
        });

        $categories = Cache::remember('home_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('shorts', compact('shorts', 'stats', 'speakerNames', 'categories'));
    }

    public function favorites(Request $request)
    {
        $user = auth()->user();
        
        // جلب الفيديوهات المفضلة للمستخدم
        $query = Asset::whereHas('favorites', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('relative_path', 'like', 'assets/%')
        ->where('is_publishable', true);

        // البحث (ذكي، عدم مراعاة حالة الأحرف)
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        // الترتيب
        $query->orderBy('id', 'desc');

        // استخدام select فقط للحقول المطلوبة
        $assets = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->with('categories:id,name')
            ->paginate(12);
        
        // حساب duration_formatted مسبقاً
        $assets->getCollection()->transform(function($asset) {
            if ($asset->duration_seconds) {
                $hours = floor($asset->duration_seconds / 3600);
                $minutes = floor(($asset->duration_seconds % 3600) / 60);
                $seconds = $asset->duration_seconds % 60;
                if ($hours > 0) {
                    $asset->computed_duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $asset->computed_duration = sprintf('%d:%02d', $minutes, $seconds);
                }
            } else {
                $asset->computed_duration = null;
            }
            
            return $asset;
        });

        // تصنيفات المحتوى المتاحة (مع cache) - فقط التي تُعرض في الموقع ولها فيديوهات منشورة
        $contentCategories = Cache::remember('home_content_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->publishableUnderAssets()->videos();
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('favorites', compact('assets', 'contentCategories'));
    }

    public function profile()
    {
        $user = auth()->user();
        
        // إحصائيات المستخدم
        $stats = [
            'likes_count' => $user->likes()->count(),
            'favorites_count' => $user->favorites()->count(),
            'comments_count' => $user->comments()->count(),
        ];
        
        // تصنيفات المحتوى المتاحة (مع cache) - فقط التي تُعرض في الموقع
        $contentCategories = Cache::remember('home_content_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->publishableUnderAssets()->videos();
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('profile', compact('user', 'stats', 'contentCategories'));
    }

    public function liked(Request $request)
    {
        $user = auth()->user();
        
        // جلب الفيديوهات المعجب بها للمستخدم
        $query = Asset::whereHas('likes', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('relative_path', 'like', 'assets/%')
        ->where('is_publishable', true);

        // البحث (ذكي، عدم مراعاة حالة الأحرف)
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        // الترتيب
        $query->orderBy('id', 'desc');

        // استخدام select فقط للحقول المطلوبة
        $assets = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->with('categories:id,name')
            ->paginate(12);
        
        // حساب duration_formatted مسبقاً
        $assets->getCollection()->transform(function($asset) {
            if ($asset->duration_seconds) {
                $hours = floor($asset->duration_seconds / 3600);
                $minutes = floor(($asset->duration_seconds % 3600) / 60);
                $seconds = $asset->duration_seconds % 60;
                if ($hours > 0) {
                    $asset->computed_duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $asset->computed_duration = sprintf('%d:%02d', $minutes, $seconds);
                }
            } else {
                $asset->computed_duration = null;
            }
            
            return $asset;
        });

        // تصنيفات المحتوى المتاحة (مع cache) - فقط التي تُعرض في الموقع
        $contentCategories = Cache::remember('home_content_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->publishableUnderAssets()->videos();
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('liked', compact('assets', 'contentCategories'));
    }

    public function about()
    {
        $categories = Cache::remember('home_categories_video', 3600, function () {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function ($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('about', compact('categories'));
    }

    public function portraitVideos(Request $request)
    {
        $query = $this->basePublicVideoQuery()
            ->where('orientation', 'portrait')
            ->orderByRaw('published_at IS NULL ASC')
            ->orderByDesc('published_at')
            ->orderBy('assets.id', 'desc');

        if ($request->has('search') && trim((string) $request->search) !== '') {
            $this->applySearchFilter($query, trim($request->search));
        }

        $selectFields = ['id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title', 'orientation'];

        $assets = (clone $query)->select($selectFields)
            ->with('categories:id,name')
            ->paginate(24, ['*'], 'page', $request->get('page', 1));
        $assets->setCollection($assets->getCollection()->map([$this, 'mapAssetComputedDuration']));

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('partials.home-video-cards', [
                'assets' => $assets,
                'forceLandscape' => false,
                'useThumbnail' => true,
            ])->render();

            return response()->json([
                'html' => $html,
                'has_more' => $assets->hasMorePages(),
                'next_page_url' => $assets->hasMorePages()
                    ? $assets->appends($request->query())->nextPageUrl()
                    : null,
            ]);
        }

        $totalPortraitVideos = (clone $query)->count();

        $categories = Cache::remember('home_categories_video', 3600, function () {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function ($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('portrait-videos', compact('assets', 'categories', 'totalPortraitVideos'));
    }

    public function playlists()
    {
        // القوائم الرئيسية فقط التي لها فيديوهات منشورة (مباشرة أو في قوائم فرعية) والظاهرة
        $playlists = Playlist::query()
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->where(fn ($q) => $this->playlistTreeHasPublishedVideosConstraint($q))
            ->withCount(['assets' => fn ($q) => $this->applyPublishedPlaylistAssetsConstraint($q)])
            ->with(['children' => function ($query) {
                $query->where('is_visible', true)
                    ->withCount(['assets' => fn ($q) => $this->applyPublishedPlaylistAssetsConstraint($q)])
                    ->with(['children' => fn ($childQuery) => $childQuery->where('is_visible', true)->withCount(['assets' => fn ($q) => $this->applyPublishedPlaylistAssetsConstraint($q)])])
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->each(function (Playlist $playlist) {
                $playlist->setAttribute('total_videos_count', $playlist->totalPublishedVideosCount());
            });

        // جلب التصنيفات للقائمة الجانبية
        $categories = Cache::remember('home_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('playlists.public', compact('playlists', 'categories'));
    }

    public function showPlaylist(Playlist $playlist)
    {
        if (! $playlist->is_visible) {
            abort(404, 'قائمة التشغيل غير متاحة');
        }

        $playlist->load('parent');

        $childPlaylists = $playlist->children()
            ->where('is_visible', true)
            ->withCount(['assets' => fn ($q) => $this->applyPublishedPlaylistAssetsConstraint($q)])
            ->with(['children' => fn ($q) => $q->where('is_visible', true)->withCount(['assets' => fn ($assets) => $this->applyPublishedPlaylistAssetsConstraint($assets)])])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->filter(fn (Playlist $child) => $child->hasPublishedContentInTree())
            ->each(function (Playlist $child) {
                $child->setAttribute('total_videos_count', $child->totalPublishedVideosCount());
            })
            ->values();

        $categories = Cache::remember('home_categories_video', 3600, function () {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function ($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        if ($childPlaylists->isNotEmpty()) {
            $assets = $this->paginatePlaylistTreePublishedVideos($playlist);
        } else {
            $assets = $playlist->assets()
                ->publishableUnderAssets()
                ->videos()
                ->select('assets.id', 'assets.file_name', 'assets.relative_path', 'assets.thumbnail_path', 'assets.cover_path', 'assets.extension', 'assets.duration_seconds', 'assets.speaker_name', 'assets.title')
                ->with('categories:id,name')
                ->orderByPivot('order', 'asc')
                ->orderBy('assets.id', 'asc')
                ->paginate(12, ['*'], 'page', request()->get('page', 1));
        }

        $assets->getCollection()->transform(fn ($asset) => $this->formatPlaylistAssetDuration($asset));

        if (request()->ajax() || request()->wantsJson()) {
            $html = view('partials.home-video-cards', ['assets' => $assets, 'forceLandscape' => true, 'useCover' => true])->render();

            return response()->json([
                'html' => $html,
                'has_more' => $assets->hasMorePages(),
                'next_page_url' => $assets->hasMorePages() ? $assets->appends(request()->query())->nextPageUrl() : null,
            ]);
        }

        return view('playlists.show', [
            'playlist' => $playlist,
            'childPlaylists' => $childPlaylists,
            'assets' => $assets,
            'categories' => $categories,
        ]);
    }

    private function paginatePlaylistTreePublishedVideos(Playlist $playlist)
    {
        $playlistIds = $playlist->visibleDescendantPlaylistIdsInOrder();
        $playlistOrderSql = $this->playlistIdsCaseOrderSql('asset_playlist.playlist_id', $playlistIds);
        $pivotOrderColumn = DB::connection()->getDriverName() === 'sqlite'
            ? 'asset_playlist."order"'
            : 'asset_playlist.`order`';

        return Asset::query()
            ->select([
                'assets.id',
                'assets.file_name',
                'assets.relative_path',
                'assets.thumbnail_path',
                'assets.cover_path',
                'assets.extension',
                'assets.duration_seconds',
                'assets.speaker_name',
                'assets.title',
            ])
            ->join('asset_playlist', 'assets.id', '=', 'asset_playlist.asset_id')
            ->whereIn('asset_playlist.playlist_id', $playlistIds)
            ->publishableUnderAssets()
            ->videos()
            ->groupBy(
                'assets.id',
                'assets.file_name',
                'assets.relative_path',
                'assets.thumbnail_path',
                'assets.cover_path',
                'assets.extension',
                'assets.duration_seconds',
                'assets.speaker_name',
                'assets.title',
            )
            ->orderByRaw("MIN({$playlistOrderSql})")
            ->orderByRaw("MIN({$pivotOrderColumn})")
            ->with('categories:id,name')
            ->paginate(12, ['*'], 'page', request()->get('page', 1));
    }

    /**
     * ترتيب قوائم التشغيل بـ CASE (متوافق مع SQLite و MySQL بدلاً من FIELD).
     */
    private function playlistIdsCaseOrderSql(string $column, array $playlistIds): string
    {
        if ($playlistIds === []) {
            return '0';
        }

        $cases = [];
        foreach (array_values($playlistIds) as $index => $id) {
            $cases[] = 'WHEN '.(int) $id.' THEN '.$index;
        }

        return 'CASE '.$column.' '.implode(' ', $cases).' ELSE '.count($playlistIds).' END';
    }

    private function formatPlaylistAssetDuration(Asset $asset): Asset
    {
        if ($asset->duration_seconds) {
            $hours = floor($asset->duration_seconds / 3600);
            $minutes = floor(($asset->duration_seconds % 3600) / 60);
            $seconds = $asset->duration_seconds % 60;
            $asset->computed_duration = $hours > 0
                ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
                : sprintf('%d:%02d', $minutes, $seconds);
        } else {
            $asset->computed_duration = null;
        }

        return $asset;
    }

    /**
     * شرط جلب الأصول المنشورة للعرض العام (assets + videos + 2025) لصفحة الشيوخ.
     */
    private function scholarAssetsPublicQuery($query)
    {
        $query->where('is_publishable', true)->where(function ($q) {
            $q->where('relative_path', 'like', 'assets/%')
                ->orWhere('relative_path', 'like', 'videos/%')
                ->orWhere('relative_path', 'like', '2025/%');
        });
    }

    public function scholarsPublic()
    {
        // جلب الشيوخ النشطين (نفس ترتيب الأدمن) الذين لهم فيديوهات منشورة تحت assets أو videos أو 2025
        $scholars = Scholar::where('status', 'active')
            ->withCount(['assets' => function ($q) {
                $this->scholarAssetsPublicQuery($q);
            }])
            ->whereHas('assets', function ($q) {
                $this->scholarAssetsPublicQuery($q);
            })
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $categories = Cache::remember('home_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('scholars.public', compact('scholars', 'categories'));
    }

    public function showScholarPublic(Scholar $scholar)
    {
        // جلب فيديوهات الشيخ المنشورة (assets أو videos أو 2025)
        $assets = $scholar->assets()
            ->where('is_publishable', true)
            ->where(function ($q) {
                $q->where('relative_path', 'like', 'assets/%')
                    ->orWhere('relative_path', 'like', 'videos/%')
                    ->orWhere('relative_path', 'like', '2025/%');
            })
            ->select('assets.id', 'assets.file_name', 'assets.relative_path', 'assets.thumbnail_path', 'assets.extension', 'assets.duration_seconds', 'assets.speaker_name', 'assets.title')
            ->with('categories:id,name')
            ->orderBy('assets.id', 'desc')
            ->paginate(12);

        $assets->getCollection()->transform(function($asset) {
            if ($asset->duration_seconds) {
                $hours = floor($asset->duration_seconds / 3600);
                $minutes = floor(($asset->duration_seconds % 3600) / 60);
                $seconds = $asset->duration_seconds % 60;
                if ($hours > 0) {
                    $asset->computed_duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $asset->computed_duration = sprintf('%d:%02d', $minutes, $seconds);
                }
            } else {
                $asset->computed_duration = null;
            }
            return $asset;
        });

        $categories = Cache::remember('home_categories_video', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->publishableUnderAssets()->videos();
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('scholars.show-public', compact('scholar', 'assets', 'categories'));
    }

    /**
     * صفحة بث مباشر: player فقط يعرض فيديوهات الموقع بشكل متسلسل عشوائي.
     */
    public function live()
    {
        $assets = Asset::publishableUnderAssets()
            ->videos()
            ->where('orientation', 'landscape')
            ->whereNotNull('relative_path')
            ->select('id', 'title', 'thumbnail_path')
            ->inRandomOrder()
            ->limit(50)
            ->get();

        $liveQueue = $assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'stream_url' => url(route('assets.stream.public', $asset)),
                'title' => $asset->title,
                'poster' => $asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path)
                    ? asset('storage/' . $asset->thumbnail_path)
                    : asset('images/logo_min.png'),
            ];
        })->values()->toArray();

        $posterUrl = count($liveQueue) > 0 ? $liveQueue[0]['poster'] : '';

        return view('live', compact('liveQueue', 'posterUrl'));
    }

    /**
     * API لجلب المزيد من الفيديوهات للبث المباشر (عشوائي، paginated).
     */
    public function liveFeed(Request $request)
    {
        $assets = Asset::publishableUnderAssets()
            ->videos()
            ->where('orientation', 'landscape')
            ->whereNotNull('relative_path')
            ->select('id', 'title', 'thumbnail_path')
            ->inRandomOrder()
            ->paginate(30, ['*'], 'page', $request->get('page', 1));

        $items = $assets->getCollection()->map(function ($asset) {
            return [
                'id' => $asset->id,
                'stream_url' => url(route('assets.stream.public', $asset)),
                'title' => $asset->title,
                'poster' => $asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path)
                    ? asset('storage/' . $asset->thumbnail_path)
                    : asset('images/logo_min.png'),
            ];
        });

        return response()->json([
            'assets' => $items,
            'has_more' => $assets->hasMorePages(),
            'next_page_url' => $assets->hasMorePages() ? $assets->nextPageUrl() : null,
        ]);
    }
}
