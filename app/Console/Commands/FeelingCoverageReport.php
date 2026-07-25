<?php

namespace App\Console\Commands;

use App\Services\FeelingAssetMatcher;
use Illuminate\Console\Command;

class FeelingCoverageReport extends Command
{
    protected $signature = 'feelings:coverage';

    protected $description = 'تقرير تغطية المشاعر/المواضيع للأصول المنشورة قبل إطلاق صفحة دعوة غيب';

    public function handle(FeelingAssetMatcher $matcher): int
    {
        $stats = $matcher->coverageStats();

        $this->info('تقرير تغطية صفحة دعوة غيب (أصول قابلة للنشر)');
        $this->table(
            ['المؤشر', 'العدد'],
            collect($stats)->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        $chips = config('feelings.chips', []);
        $this->newLine();
        $this->info('الحالات المعروضة: '.implode('، ', $chips));

        if (($stats['matchable_text'] ?? 0) < 10) {
            $this->warn('التغطية ضعيفة: يُفضّل تشغيل تحليل المحتوى على المزيد من الأصول قبل الإطلاق.');
        } else {
            $this->info('التغطية تبدو كافية للإطلاق الأولي.');
        }

        return self::SUCCESS;
    }
}
