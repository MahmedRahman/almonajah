<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetOptimizedVersion extends Model
{
    protected $fillable = [
        'asset_id',
        'relative_path',
        'quality_preset',
        'size_bytes',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'asset_id' => 'integer',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** تسمية الجودة بالعربية للعرض */
    public function getQualityLabelAttribute(): string
    {
        return match ($this->quality_preset) {
            'high' => 'جودة عالية',
            'small' => 'حجم أصغر (تحميل أسرع)',
            default => 'متوازن (للويب)',
        };
    }
}
