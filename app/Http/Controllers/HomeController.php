<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // جلب الفيديوهات المنقولة إلى الموقع والقابلة للنشر فقط
        $query = Asset::where('relative_path', 'like', 'assets/%')
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

        // فلترة حسب اسم المتحدث
        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('speaker_name', 'like', "%{$request->speaker_name}%");
        }

        // فلترة حسب التصنيف (من relative_path)
        if ($request->has('category') && $request->category) {
            $query->where('relative_path', 'like', "%{$request->category}%");
        }

        // فلترة حسب السنة الهجرية (من relative_path أو year)
        if ($request->has('year') && $request->year) {
            $query->where(function($q) use ($request) {
                $q->where('relative_path', 'like', "%{$request->year}%")
                  ->orWhere('year', $request->year);
            });
        }

        // الترتيب
        $query->orderBy('id', 'desc');

        // استخدام select فقط للحقول المطلوبة
        // ملاحظة: category و year هما accessors وليسا أعمدة في قاعدة البيانات
        $assets = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->paginate(12);
        
        // حساب category و duration_formatted مسبقاً لتجنب استدعاء accessors في الـ loop
        $assets->getCollection()->transform(function($asset) {
            // حساب category مسبقاً
            if ($asset->relative_path) {
                $parts = explode('/', $asset->relative_path);
                if (count($parts) > 1) {
                    $firstFolder = $parts[0];
                    $category = preg_replace('/\s*\d{4}\s*/', '', $firstFolder);
                    $asset->computed_category = trim($category) ?: $firstFolder;
                } else {
                    $asset->computed_category = null;
                }
            } else {
                $asset->computed_category = null;
            }
            
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
        });
        
        // جلب Shorts (فيديوهات قصيرة وعمودية - أقل من 60 ثانية وعمودية) مع cache
        $shortsQuery = Cache::remember('home_shorts', 1800, function() {
            $shorts = Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->where('orientation', 'portrait')
                ->where(function($q) {
                    $q->where('duration_seconds', '<=', 60)
                      ->orWhereNull('duration_seconds');
                })
                ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();
            
            // حساب duration_formatted و category مسبقاً
            return $shorts->map(function($short) {
                // حساب category
                if ($short->relative_path) {
                    $parts = explode('/', $short->relative_path);
                    if (count($parts) > 1) {
                        $firstFolder = $parts[0];
                        $category = preg_replace('/\s*\d{4}\s*/', '', $firstFolder);
                        $short->computed_category = trim($category) ?: $firstFolder;
                    } else {
                        $short->computed_category = null;
                    }
                } else {
                    $short->computed_category = null;
                }
                
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

        // التصنيفات المتاحة (مع cache - استخدام SQL مباشرة)
        $categories = Cache::remember('home_categories', 3600, function() {
            // استخدام استعلام SQL مباشر لاستخراج التصنيفات من relative_path
            $categories = DB::table('assets')
                ->where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->whereNotNull('relative_path')
                ->select('relative_path')
                ->get()
                ->map(function($item) {
                    $parts = explode('/', $item->relative_path);
                    if (count($parts) >= 3) {
                        return $parts[2];
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
            
            return $categories;
        });

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

        return view('home', compact('assets', 'shortsQuery', 'stats', 'speakerNames', 'categories', 'years'));
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
        $shorts = $query->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
            ->with(['hlsVersions' => function($q) {
                $q->select('id', 'asset_id', 'resolution', 'playlist_path', 'master_playlist_path');
            }])
            ->paginate(20);

        // حساب duration_formatted و category مسبقاً
        $shorts->getCollection()->transform(function($short) {
            // حساب category
            if ($short->relative_path) {
                $parts = explode('/', $short->relative_path);
                if (count($parts) > 1) {
                    $firstFolder = $parts[0];
                    $category = preg_replace('/\s*\d{4}\s*/', '', $firstFolder);
                    $short->computed_category = trim($category) ?: $firstFolder;
                } else {
                    $short->computed_category = null;
                }
            } else {
                $short->computed_category = null;
            }
            
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

        return view('shorts', compact('shorts', 'stats', 'speakerNames'));
    }
}
