<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Playlist;
use App\Models\Scholar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // جلب الفيديوهات المنقولة إلى الموقع والقابلة للنشر فقط
        // تحسين: استخدام whereIn بدلاً من like إذا أمكن، أو استخدام index
        $query = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->whereNotNull('relative_path'); // تحسين: استبعاد null values

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('speaker_name', 'like', "%{$search}%");
            });
        }

        // فلترة حسب اسم المتحدث
        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('speaker_name', 'like', "%{$request->speaker_name}%");
        }

        // فلترة حسب تصنيف المحتوى (many-to-many)
        if ($request->has('content_category') && $request->content_category) {
            $categoryName = $request->content_category;
            $category = Category::where('show_on_site', true)->where('name', $categoryName)->first();
            if ($category) {
                $query->whereHas('categories', function($q) use ($category) {
                    $q->where('categories.id', $category->id);
                });
            }
        }

        // فلترة حسب السنة الهجرية (من relative_path أو year)
        if ($request->has('year') && $request->year) {
            $query->where(function($q) use ($request) {
                $q->where('relative_path', 'like', "%{$request->year}%")
                  ->orWhere('year', $request->year);
            });
        }

        // الترتيب - استخدام index على id
        $query->orderBy('id', 'desc');

        // استخدام select فقط للحقول المطلوبة
        // تحسين: تقليل عدد العناصر في الصفحة الواحدة لتسريع التحميل
        $assets = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->with('categories:id,name')
            ->paginate(9); // تقليل من 12 إلى 9 لتسريع التحميل
        
        // حساب duration_formatted مسبقاً لتجنب استدعاء accessors في الـ loop
        // تحسين: استخدام map بدلاً من transform لتقليل العمليات
        $assets->setCollection($assets->getCollection()->map(function($asset) {
            // حساب duration_formatted مسبقاً
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
        }));

        // طلب "تحميل المزيد": إرجاع HTML الكروت فقط كـ JSON
        if ($request->ajax() || $request->wantsJson()) {
            $html = view('partials.home-video-cards', ['assets' => $assets])->render();
            return response()->json([
                'html' => $html,
                'has_more' => $assets->hasMorePages(),
                'next_page_url' => $assets->hasMorePages() ? $assets->appends($request->query())->nextPageUrl() : null,
            ]);
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
        
        // جلب التصنيفات للقائمة الجانبية "استكشاف" من جدول إدارة التصنيفات (categories) بدون كاش
        // لضمان ظهور أي تغيير في الاسم أو اللوجو فوراً بعد التحديث من الإدارة
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

        return view('home', compact('assets', 'shortsQuery', 'stats', 'speakerNames', 'contentCategories', 'categories', 'years'));
    }

    public function shorts(Request $request)
    {
        // جلب جميع Shorts (فيديوهات عمودية - نعرض جميع الفيديوهات العمودية القابلة للنشر)
        $query = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->where('orientation', 'portrait');

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('speaker_name', 'like', "%{$search}%");
            });
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

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('speaker_name', 'like', "%{$search}%");
            });
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

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('speaker_name', 'like', "%{$search}%");
            });
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
        // جلب فيديوهات قائمة التشغيل المنشورة فقط
        $assets = $playlist->assets()
            ->where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->select('assets.id', 'assets.file_name', 'assets.relative_path', 'assets.thumbnail_path', 'assets.cover_path', 'assets.extension', 'assets.duration_seconds', 'assets.speaker_name', 'assets.title')
            ->with('categories:id,name')
            ->orderByPivot('order')
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

    public function scholarsPublic()
    {
        // جلب الشيوخ النشطين الذين لهم فيديوهات منشورة
        $scholars = Scholar::where('status', 'active')
            ->withCount(['assets' => function($q) {
                $q->where('relative_path', 'like', 'assets/%')
                  ->where('is_publishable', true);
            }])
            ->whereHas('assets', function($q) {
                $q->where('relative_path', 'like', 'assets/%')
                  ->where('is_publishable', true);
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
        // جلب فيديوهات الشيخ المنشورة فقط
        $assets = $scholar->assets()
            ->where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
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
}
