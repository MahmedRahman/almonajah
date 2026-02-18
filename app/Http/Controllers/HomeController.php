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
              });
        });
    }

    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json(['results' => []]);
        }
        $query = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->whereNotNull('relative_path');
        $this->applySearchFilter($query, $q);
        $assets = $query->select('id', 'title', 'file_name', 'speaker_name', 'thumbnail_path')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        $results = $assets->map(function($asset) {
            return [
                'id' => $asset->id,
                'title' => $asset->title ?: $asset->file_name ?: 'فيديو #' . $asset->id,
                'speaker_name' => $asset->speaker_name,
                'thumbnail_path' => $asset->thumbnail_path,
                'url' => route('assets.show.public', $asset),
            ];
        });
        return response()->json(['results' => $results]);
    }

    public function index(Request $request)
    {
        // جلب الفيديوهات المنقولة إلى الموقع والقابلة للنشر فقط
        // تحسين: استخدام whereIn بدلاً من like إذا أمكن، أو استخدام index
        $query = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->whereNotNull('relative_path'); // تحسين: استبعاد null values

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

        // تحميل المزيد: قائمة موحدة (كل الفيديوهات حسب تاريخ النشر)
        if ($request->ajax() || $request->wantsJson()) {
            if ($request->get('home_section') === 'all_videos' && !$request->has('content_category')) {
                $allVideosPage = (clone $query)
                    ->select($selectFields)
                    ->with('categories:id,name')
                    ->paginate(24, ['*'], 'page', $request->get('page', 1));
                $allVideosPage->setCollection($allVideosPage->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-video-cards', ['assets' => $allVideosPage, 'forceLandscape' => true])->render();
                return response()->json([
                    'html' => $html,
                    'has_more' => $allVideosPage->hasMorePages(),
                    'next_page_url' => $allVideosPage->hasMorePages() ? $allVideosPage->appends(array_merge($request->query(), ['home_section' => 'all_videos']))->nextPageUrl() : null,
                ]);
            }
            // تحميل المزيد لصفحة التصنيف (الترتيب من $query = ترتيب التصنيف إن وُجد)
            if ($request->has('content_category') && trim((string) $request->content_category) !== '') {
                $categoryResultsPaginated = (clone $query)
                    ->select(array_merge($selectFields, ['site_description']))
                    ->with('categories:id,name')
                    ->paginate(20, ['*'], 'page', $request->get('page', 1));
                $categoryResultsPaginated->setCollection($categoryResultsPaginated->getCollection()->map([$this, 'mapAssetComputedDuration']));
                $html = view('partials.home-video-cards', ['assets' => $categoryResultsPaginated])->render();
                return response()->json([
                    'html' => $html,
                    'has_more' => $categoryResultsPaginated->hasMorePages(),
                    'next_page_url' => $categoryResultsPaginated->hasMorePages() ? $categoryResultsPaginated->appends($request->query())->nextPageUrl() : null,
                ]);
            }
        }

        $categoryResults = null;
        $allVideos = null;

        // عند عرض تصنيف معين: قائمة واحدة بكل فيديوهات التصنيف مع ترقيم الصفحات (الترتيب من $query)
        if ($hasCategoryFilter) {
            $categoryResults = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with('categories:id,name')
                ->paginate(20)
                ->through([$this, 'mapAssetComputedDuration']);
        }

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

        // قائمة موحدة: كل الفيديوهات (أفقي + طولي) بشكل أفقي واحد، مرتبة حسب تاريخ النشر
        $allVideos = null;
        if (!$hasCategoryFilter) {
            $allVideos = (clone $query)
                ->select($selectFields)
                ->with('categories:id,name')
                ->paginate(24);
            $allVideos->setCollection($allVideos->getCollection()->map([$this, 'mapAssetComputedDuration']));
            $assets = $allVideos; // للتوافق مع أي مراجع قديمة
        } else {
            $assets = null;
        }

        // عند وجود بحث: قائمة موحدة لنتائج البحث (بدون إعلانات في الواجهة)
        $searchResults = null;
        if ($request->has('search') && trim((string) $request->search) !== '') {
            $searchResults = (clone $query)
                ->select(array_merge($selectFields, ['site_description']))
                ->with('categories:id,name')
                ->paginate(20)
                ->through([$this, 'mapAssetComputedDuration']);
        }
        
        // جلب Shorts (فيديوهات قصيرة وعمودية - أقل من 60 ثانية وعمودية) مع cache
        // تحسين: تقليل عدد Shorts المعروضة
        $shortsQuery = Cache::remember('home_shorts', 1800, function() {
            $shorts = Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->whereNotNull('relative_path')
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
        $stats = \Illuminate\Support\Facades\Cache::remember('home_stats', 3600, function() {
            return [
                'total' => Asset::where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true)
                    ->count(),
                'speakers' => Asset::where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true)
                    ->whereNotNull('speaker_name')
                    ->distinct('speaker_name')
                    ->count('speaker_name'),
            ];
        });

        // أسماء المتحدثين المتاحة (مع cache)
        $speakerNames = \Illuminate\Support\Facades\Cache::remember('home_speaker_names', 3600, function() {
            return Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->whereNotNull('speaker_name')
                ->distinct()
                ->pluck('speaker_name')
                ->filter()
                ->sort()
                ->values();
        });

        // تصنيفات المحتوى المتاحة (من جدول categories) - فقط التي تُعرض في الموقع
        $contentCategories = Cache::remember('home_content_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });
        
        // جلب التصنيفات للقائمة الجانبية "استكشاف" — العدد من جدول الربط asset_category فقط (ينطبق على صفحة التصنيف)
        $categories = Category::where('show_on_site', true)
            ->withCount(['assets' => function($q) {
                $q->where('relative_path', 'like', 'assets/%')
                  ->where('is_publishable', true);
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // السنوات الهجرية المتاحة (مع cache - استخدام SQL مباشرة)
        $years = Cache::remember('home_years', 3600, function() {
            // استخدام استعلام SQL مباشر لاستخراج السنوات من relative_path
            $years = DB::table('assets')
                ->where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
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
            'assets', 'allVideos', 'shortsQuery', 'stats', 'speakerNames', 'contentCategories', 'categories', 'years',
            'bannersRectangle', 'bannersVertical', 'bannersLandscape', 'searchResults', 'categoryResults'
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

    public function shorts(Request $request)
    {
        // جلب جميع Shorts (فيديوهات عمودية - نعرض جميع الفيديوهات العمودية القابلة للنشر)
        $query = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
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
        $stats = Cache::remember('shorts_stats', 3600, function() {
            return [
                'total' => Asset::where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true)
                    ->where('orientation', 'portrait')
                    ->where(function($q) {
                        $q->where('duration_seconds', '<=', 60)
                          ->orWhereNull('duration_seconds');
                    })
                    ->count(),
            ];
        });

        // أسماء المتحدثين المتاحة
        $speakerNames = Cache::remember('shorts_speaker_names', 3600, function() {
            return Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
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

        $categories = Cache::remember('home_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
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
        $contentCategories = Cache::remember('home_content_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
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
        $contentCategories = Cache::remember('home_content_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
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
        $contentCategories = Cache::remember('home_content_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->whereHas('assets', function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
                })
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('liked', compact('assets', 'contentCategories'));
    }

    public function playlists()
    {
        // جلب قوائم التشغيل التي لها فيديوهات منشورة
        $playlists = Playlist::withCount(['assets' => function($q) {
                $q->where('relative_path', 'like', 'assets/%')
                  ->where('is_publishable', true);
            }])
            ->whereHas('assets', function($q) {
                $q->where('relative_path', 'like', 'assets/%')
                  ->where('is_publishable', true);
            })
            ->orderBy('title')
            ->get();

        // جلب التصنيفات للقائمة الجانبية
        $categories = Cache::remember('home_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('playlists.public', compact('playlists', 'categories'));
    }

    public function showPlaylist(Playlist $playlist)
    {
        // جلب فيديوهات قائمة التشغيل المنشورة فقط — نفس ترتيب لوحة الإدارة (عمود order في asset_playlist)
        $assets = $playlist->assets()
            ->where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->select('assets.id', 'assets.file_name', 'assets.relative_path', 'assets.thumbnail_path', 'assets.cover_path', 'assets.extension', 'assets.duration_seconds', 'assets.speaker_name', 'assets.title')
            ->with('categories:id,name')
            ->orderByPivot('order', 'asc')
            ->orderBy('assets.id', 'asc')
            ->paginate(12, ['*'], 'page', request()->get('page', 1));

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

        // تحميل المزيد عند التمرير (infinite scroll): إرجاع JSON
        if (request()->ajax() || request()->wantsJson()) {
            $html = view('partials.home-video-cards', ['assets' => $assets])->render();
            return response()->json([
                'html' => $html,
                'has_more' => $assets->hasMorePages(),
                'next_page_url' => $assets->hasMorePages() ? $assets->appends(request()->query())->nextPageUrl() : null,
            ]);
        }

        // جلب التصنيفات للقائمة الجانبية
        $categories = Cache::remember('home_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        return view('playlists.show', compact('playlist', 'assets', 'categories'));
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

        $categories = Cache::remember('home_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
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

        $categories = Cache::remember('home_categories', 3600, function() {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                      ->where('is_publishable', true);
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
        $assets = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
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
        $assets = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
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
