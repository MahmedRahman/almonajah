<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Asset extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'file_name',
        'relative_path',
        'original_relative_path',
        'original_path',
        'thumbnail_path',
        'speaker_name',
        'title',
        'site_description',
        'transcription',
        'category',
        'content_category',
        'topics',
        'emotions',
        'intent',
        'audience',
        'extension',
        'size_bytes',
        'modified_at',
        'width',
        'height',
        'duration_seconds',
        'orientation',
        'aspect_ratio',
        'sha256',
        'is_publishable',
    ];

    protected function casts(): array
    {
        return [
            'modified_at' => 'datetime',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'is_publishable' => 'boolean',
        ];
    }

    public function getModifiedAtAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            return Carbon::parse($value);
        }
        
        return $value;
    }

    public function getSizeInKbAttribute(): float
    {
        return round($this->size_bytes / 1024, 2);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->size_bytes / (1024 * 1024), 2);
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function scopeVideos($query)
    {
        return $query->whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v']);
    }

    public function scopePortrait($query)
    {
        return $query->where('orientation', 'portrait');
    }

    public function scopeLandscape($query)
    {
        return $query->where('orientation', 'landscape');
    }

    public function hlsVersions()
    {
        return $this->hasMany(HlsVersion::class);
    }

    public function audioFiles()
    {
        return $this->hasMany(AudioFile::class);
    }

    /**
     * استخراج العنوان من المسار
     * إذا كان العنوان محفوظ في قاعدة البيانات، نستخدمه
     * وإلا نستخرجه من المسار
     */
    public function getTitleAttribute($value): string
    {
        // إذا كان العنوان محفوظ في قاعدة البيانات، نستخدمه
        if (!empty($value)) {
            return $value;
        }

        // وإلا نستخرجه من المسار
        if (!$this->relative_path) {
            return $this->file_name ? pathinfo($this->file_name, PATHINFO_FILENAME) : '';
        }

        $parts = explode('/', $this->relative_path);
        
        // إذا كان الملف في مجلد، نأخذ اسم المجلد
        if (count($parts) > 1) {
            // نأخذ آخر مجلد قبل اسم الملف
            return $parts[count($parts) - 2];
        }
        
        // إذا كان الملف في الجذر، نأخذ اسم الملف بدون الامتداد
        $filename = $parts[count($parts) - 1];
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * استخراج السنة الهجرية من المسار
     * البحث عن رقم مكون من 4 أرقام في المسار (عادة بين 1300-1500)
     */
    public function getYearAttribute(): ?string
    {
        if (!$this->relative_path) {
            return null;
        }

        // البحث عن جميع الأرقام المكونة من 4 أرقام في المسار
        if (preg_match_all('/\b(\d{4})\b/', $this->relative_path, $matches)) {
            foreach ($matches[1] as $year) {
                // السنة الهجرية عادة بين 1300-1500
                if ($year >= 1300 && $year <= 1500) {
                    return $year;
                }
            }
            // إذا لم نجد سنة هجرية، نأخذ أول رقم 4 أرقام
            return $matches[1][0];
        }

        return null;
    }

    /**
     * استخراج السنة الميلادية من المسار
     * البحث عن رقم مكون من 4 أرقام في المسار (عادة بين 1900-2100)
     */
    public function getGregorianYearAttribute(): ?string
    {
        if (!$this->relative_path) {
            return null;
        }

        // البحث عن جميع الأرقام المكونة من 4 أرقام في المسار
        if (preg_match_all('/\b(\d{4})\b/', $this->relative_path, $matches)) {
            foreach ($matches[1] as $year) {
                // السنة الميلادية عادة بين 1900-2100
                if ($year >= 1900 && $year <= 2100) {
                    return $year;
                }
            }
        }

        return null;
    }

    /**
     * استخراج التصنيف من المسار
     * التصنيف هو أول مجلد في المسار (إذا كان الملف داخل مجلد)
     * أو جزء من اسم الملف
     */
    public function getCategoryAttribute(): ?string
    {
        if (!$this->relative_path) {
            return null;
        }

        $parts = explode('/', $this->relative_path);
        
        // إذا كان الملف في مجلد، نأخذ أول مجلد كتصنيف
        if (count($parts) > 1) {
            $firstFolder = $parts[0];
            // إزالة الأرقام من اسم المجلد للحصول على التصنيف النقي
            // مثال: "ادعية 1447" -> "ادعية"
            $category = preg_replace('/\s*\d{4}\s*/', '', $firstFolder);
            $category = trim($category);
            return $category ?: $firstFolder;
        }
        
        // إذا كان الملف في الجذر، نحاول استخراج التصنيف من اسم الملف
        // أو نرجع null
        return null;
    }

    /**
     * استخراج اسم المتحدث من المسار
     * إذا كان speaker_name محفوظ في قاعدة البيانات، نستخدمه
     * وإلا نستخرجه من المسار
     */
    public function getSpeakerNameAttribute($value): ?string
    {
        // إذا كان speaker_name محفوظ في قاعدة البيانات، نستخدمه
        if (!empty($value)) {
            return $value;
        }

        // وإلا نستخرجه من المسار (المنطق القديم)
        if (!$this->relative_path) {
            return null;
        }

        $parts = explode('/', $this->relative_path);
        
        // إذا كان هناك مجلدات فرعية، نأخذ ثاني مجلد كاسم المتحدث
        if (count($parts) > 2) {
            return trim($parts[1]);
        }
        
        // إذا كان هناك مجلد واحد فقط، نحاول استخراج اسم المتحدث من اسم الملف
        if (count($parts) == 2) {
            $filename = $parts[1];
            // إزالة الامتداد
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
            
            // محاولة استخراج اسم المتحدث من اسم الملف
            // مثال: "الشيخ محمد - اللهم داوني.mp4" -> "الشيخ محمد"
            // أو "محمد بن عبدالله 123.mp4" -> "محمد بن عبدالله"
            
            // إزالة الأرقام من النهاية
            $speakerName = preg_replace('/\s*\d+\s*$/', '', $filenameWithoutExt);
            
            // إذا كان هناك فاصل (- أو | أو _)، نأخذ الجزء الأول
            if (preg_match('/^([^-|_]+)/', $speakerName, $matches)) {
                return trim($matches[1]);
            }
            
            return trim($speakerName) ?: null;
        }
        
        // إذا كان الملف في الجذر، نحاول استخراج من اسم الملف
        if (count($parts) == 1) {
            $filename = $parts[0];
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
            
            // إزالة الأرقام من النهاية
            $speakerName = preg_replace('/\s*\d+\s*$/', '', $filenameWithoutExt);
            
            // إذا كان هناك فاصل، نأخذ الجزء الأول
            if (preg_match('/^([^-|_]+)/', $speakerName, $matches)) {
                return trim($matches[1]);
            }
            
            return trim($speakerName) ?: null;
        }
        
        return null;
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size_bytes) {
            return '<span class="text-muted">غير متوفر</span>';
        }

        $bytes = $this->size_bytes;
        $units = ['بايت', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return sprintf('<strong><span class="text-primary fs-5">%.2f %s</span></strong> <small class="text-muted">(%s بايت)</small>', $bytes, $units[$i], number_format($this->size_bytes));
    }
}

