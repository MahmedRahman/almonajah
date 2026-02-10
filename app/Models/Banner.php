<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    public const SIZE_VERTICAL = 'vertical';
    public const SIZE_LANDSCAPE = 'landscape';
    public const SIZE_RECTANGLE = 'rectangle';

    public const PLACEMENT_HOME = 'home';
    public const PLACEMENT_VIDEO_DETAIL = 'video_detail';
    public const PLACEMENT_CATEGORIES = 'categories';

    protected $fillable = [
        'image_path',
        'link',
        'size',
        'show_on_home',
        'show_on_video_detail',
        'show_on_categories',
        'order',
        'is_active',
    ];

    protected $casts = [
        'show_on_home' => 'boolean',
        'show_on_video_detail' => 'boolean',
        'show_on_categories' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter banners by placement (home, video_detail, categories).
     */
    public function scopeForPlacement($query, string $placement)
    {
        $column = match ($placement) {
            self::PLACEMENT_HOME => 'show_on_home',
            self::PLACEMENT_VIDEO_DETAIL => 'show_on_video_detail',
            self::PLACEMENT_CATEGORIES => 'show_on_categories',
            default => null,
        };
        if ($column) {
            return $query->where($column, true);
        }
        return $query;
    }

    public static function sizeLabels(): array
    {
        return [
            self::SIZE_VERTICAL => 'عمودي (فيديو عمودي 9:16)',
            self::SIZE_LANDSCAPE => 'عريض (فيديو عريض 16:9)',
            self::SIZE_RECTANGLE => 'مستطيل (بانر أفقي)',
        ];
    }

    public static function placementLabels(): array
    {
        return [
            'show_on_home' => 'الصفحة الرئيسية',
            'show_on_video_detail' => 'صفحة تفاصيل الفيديو',
            'show_on_categories' => 'صفحة التصنيفات',
        ];
    }
}
