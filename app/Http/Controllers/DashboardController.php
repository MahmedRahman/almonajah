<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_content' => ContentItem::count(),
            'published_content' => ContentItem::published()->count(),
            'draft_content' => ContentItem::draft()->count(),
            'total_categories' => Category::count(),
            'total_media' => MediaFile::count(),
            'total_assets' => Asset::count(),
            'total_videos' => Asset::whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v'])->count(),
            'total_users' => User::count(),
        ];

        // إحصائيات الفيديوهات
        $video_stats = [
            'total' => Asset::count(),
            'videos' => Asset::whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v'])->count(),
            'portrait' => Asset::where('orientation', 'portrait')->count(),
            'landscape' => Asset::where('orientation', 'landscape')->count(),
            'square' => Asset::where('orientation', 'square')->count(),
            'total_size_mb' => round(Asset::sum('size_bytes') / (1024 * 1024), 2),
            'total_duration_hours' => round(Asset::sum('duration_seconds') / 3600, 2),
            'by_extension' => Asset::selectRaw('extension, COUNT(*) as count')
                ->whereNotNull('extension')
                ->groupBy('extension')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];

        $recent_content = ContentItem::with('author')
            ->latest()
            ->limit(5)
            ->get();

        $recent_assets = Asset::latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recent_content', 'video_stats', 'recent_assets'));
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

