<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\User;
use App\Models\Asset;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $siteAssets = Asset::where('relative_path', 'like', 'assets/%');
        $publishedSiteAssets = (clone $siteAssets)->where('is_publishable', true);
        $siteVideos = (clone $siteAssets)->whereIn('extension', Asset::VIDEO_EXTENSIONS);

        $stats = [
            'total_content' => ContentItem::count(),
            'published_content' => ContentItem::published()->count(),
            'draft_content' => ContentItem::draft()->count(),
            'total_categories' => Category::count(),
            'total_media' => MediaFile::count(),
            'total_assets' => (clone $siteAssets)->count(),
            'total_videos' => (clone $siteVideos)->count(),
            'total_users' => User::count(),
            'total_playlists' => Playlist::count(),
        ];

        // إحصائيات الفيديوهات (على الموقع: assets/%)
        $video_stats = [
            'total' => (clone $siteAssets)->count(),
            'videos' => (clone $siteVideos)->count(),
            'published' => (clone $publishedSiteAssets)->count(),
            'published_videos' => (clone $publishedSiteAssets)->whereIn('extension', Asset::VIDEO_EXTENSIONS)->count(),
            'portrait' => (clone $siteAssets)->where('orientation', 'portrait')->count(),
            'landscape' => (clone $siteAssets)->where('orientation', 'landscape')->count(),
            'square' => (clone $siteAssets)->where('orientation', 'square')->count(),
            'published_portrait' => (clone $publishedSiteAssets)->where('orientation', 'portrait')->count(),
            'published_landscape' => (clone $publishedSiteAssets)->where('orientation', 'landscape')->count(),
            'published_square' => (clone $publishedSiteAssets)->where('orientation', 'square')->count(),
            'portrait_duration_hours' => $this->formatDurationHours(
                (int) (clone $publishedSiteAssets)->where('orientation', 'portrait')->sum('duration_seconds')
            ),
            'landscape_duration_hours' => $this->formatDurationHours(
                (int) (clone $publishedSiteAssets)->where('orientation', 'landscape')->sum('duration_seconds')
            ),
            'total_size_mb' => round((clone $siteAssets)->sum('size_bytes') / (1024 * 1024), 2),
            'total_duration_hours' => $this->formatDurationHours((int) (clone $siteAssets)->sum('duration_seconds')),
            'by_extension' => (clone $siteAssets)->selectRaw('extension, COUNT(*) as count')
                ->whereNotNull('extension')
                ->groupBy('extension')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];

        $publishedPlaylistAssets = function ($query) {
            $query->where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true);
        };

        $playlist_stats = [
            'total' => Playlist::count(),
            'programs' => Playlist::whereNull('parent_id')->count(),
            'seasons' => Playlist::whereHas('parent', function ($query) {
                $query->whereNull('parent_id');
            })->count(),
            'sub_playlists' => Playlist::whereHas('parent', function ($query) {
                $query->whereNotNull('parent_id');
            })->count(),
            'with_videos' => Playlist::has('assets')->count(),
            'total_links' => (int) DB::table('asset_playlist')->count(),
            'published_videos_linked' => (int) DB::table('asset_playlist')
                ->join('assets', 'assets.id', '=', 'asset_playlist.asset_id')
                ->where('assets.relative_path', 'like', 'assets/%')
                ->where('assets.is_publishable', true)
                ->distinct('asset_playlist.asset_id')
                ->count('asset_playlist.asset_id'),
        ];

        $program_playlists = Playlist::query()
            ->whereNull('parent_id')
            ->withCount([
                'assets' => $publishedPlaylistAssets,
                'children',
            ])
            ->with([
                'children' => function ($query) use ($publishedPlaylistAssets) {
                    $query->withCount([
                        'assets' => $publishedPlaylistAssets,
                        'children',
                    ])
                        ->with([
                            'children' => function ($childQuery) use ($publishedPlaylistAssets) {
                                $childQuery->withCount(['assets' => $publishedPlaylistAssets])
                                    ->orderBy('sort_order')
                                    ->orderBy('title')
                                    ->orderBy('id');
                            },
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('title')
                        ->orderBy('id');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        foreach ($program_playlists as $program) {
            $program->tree_videos_count = (int) $program->assets_count;
            foreach ($program->children as $season) {
                $program->tree_videos_count += (int) $season->assets_count;
                foreach ($season->children ?? [] as $subPlaylist) {
                    $program->tree_videos_count += (int) $subPlaylist->assets_count;
                }
            }
        }

        $program_playlists = $program_playlists->sortByDesc('tree_videos_count')->values();

        $category_stats = [
            'total' => Category::count(),
            'on_site' => Category::where('show_on_site', true)->count(),
            'with_videos' => Category::whereHas('assets', function ($query) {
                $query->where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true);
            })->count(),
        ];

        $top_categories = Category::query()
            ->withCount(['assets' => function ($query) {
                $query->where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true);
            }])
            ->orderByDesc('assets_count')
            ->orderBy('order')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'show_on_site', 'image_path']);

        $recent_content = ContentItem::with('author')
            ->latest()
            ->limit(5)
            ->get();

        $recent_assets = Asset::where('relative_path', 'like', 'assets/%')
            ->latest()
            ->limit(5)
            ->get();

        $published_assets = Asset::where('relative_path', 'like', 'assets/%')
            ->where('is_publishable', true)
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'recent_content',
            'video_stats',
            'playlist_stats',
            'program_playlists',
            'category_stats',
            'top_categories',
            'recent_assets',
            'published_assets'
        ));
    }

    private function formatDurationHours(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0';
        }

        return number_format($seconds / 3600, 1);
    }

    public function truncateAssets(Request $request)
    {
        // التحقق من التأكيد
        if (!$request->has('confirm') || $request->confirm !== 'yes') {
            return redirect()->route('dashboard')
                ->with('error', 'يجب تأكيد الحذف أولاً.');
        }

        $count = Asset::count();
        
        try {
            // تعطيل foreign key checks مؤقتاً للسماح بـ TRUNCATE
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // حذف جميع السجلات من الجداول المرتبطة أولاً
            DB::table('hls_versions')->truncate();
            DB::table('audio_files')->truncate();
            
            // حذف جميع السجلات من جدول assets
            Asset::truncate();
            
            // إعادة تفعيل foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            return redirect()->route('dashboard')
                ->with('success', "تم حذف جميع الفيديوهات بنجاح ({$count} فيديو).");
        } catch (\Exception $e) {
            // التأكد من إعادة تفعيل foreign key checks حتى في حالة الخطأ
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            \Log::error('Failed to truncate assets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'فشل حذف الفيديوهات: ' . $e->getMessage());
        }
    }
}

