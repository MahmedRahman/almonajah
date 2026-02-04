<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PublishScheduledAssets extends Command
{
    protected $signature = 'assets:publish-scheduled';

    protected $description = 'تفعيل النشر تلقائياً للفيديوهات التي وصل موعد جدولة نشرها (scheduled_publish_at <= now)';

    public function handle(): int
    {
        $now = now();
        $assets = Asset::whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', $now)
            ->get();

        $count = 0;
        foreach ($assets as $asset) {
            $asset->is_publishable = true;
            $asset->scheduled_publish_at = null;
            $asset->save();
            $count++;
        }

        if ($count > 0) {
            Cache::forget('home_shorts');
            Cache::forget('home_stats');
            Cache::forget('home_speaker_names');
            Cache::forget('home_categories');
            Cache::forget('home_years');
            $this->info("تم نشر {$count} فيديو تلقائياً.");
        }

        return 0;
    }
}
