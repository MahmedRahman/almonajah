<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractSpeakerNames extends Command
{
    protected $signature = 'assets:extract-speakers 
                            {--limit= : عدد السجلات المراد معالجتها}
                            {--batch-size=50 : عدد المسارات في كل طلب API}
                            {--dry-run : تشغيل تجريبي بدون تحديث قاعدة البيانات}';

    protected $description = 'استخراج أسماء المتحدثين من مسارات الفيديوهات باستخدام DeepSeek API (معالجة مجمعة)';

    public function handle()
    {
        $this->info('🚀 بدء استخراج أسماء المتحدثين من المسارات...');
        
        $apiKey = config('deepseek.api_key');
        if (!$apiKey) {
            $this->error('❌ مفتاح DeepSeek API غير موجود في ملف .env');
            return 1;
        }

        $limit = $this->option('limit') ? (int)$this->option('limit') : null;
        $batchSize = (int)$this->option('batch-size');
        $dryRun = $this->option('dry-run');

        // جلب السجلات التي لا تحتوي على speaker_name محفوظ في قاعدة البيانات
        $query = Asset::whereNotNull('relative_path')
            ->where(function($q) {
                $q->whereNull('speaker_name')
                  ->orWhere('speaker_name', '');
            });

        if ($limit) {
            $query->limit($limit);
        }

        $assets = $query->get();
        $total = $assets->count();

        if ($total === 0) {
            $this->info('✅ لا توجد سجلات للمعالجة');
            return 0;
        }

        $this->info("📊 تم العثور على {$total} سجل للمعالجة");
        $this->info("📦 حجم المجموعة: {$batchSize} مسار لكل طلب");
        
        if ($dryRun) {
            $this->warn('⚠️  وضع التشغيل التجريبي - لن يتم تحديث قاعدة البيانات');
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $failed = 0;
        $skipped = 0;

        // تقسيم السجلات إلى مجموعات
        $batches = $assets->chunk($batchSize);
        $batchNumber = 0;

        foreach ($batches as $batch) {
            $batchNumber++;
            $this->newLine();
            $this->info("🔄 معالجة المجموعة {$batchNumber} ({$batch->count()} مسار)...");

            try {
                // استخراج أسماء المتحدثين للمجموعة
                $results = $this->extractSpeakersBatch($batch, $apiKey);

                // تحديث قاعدة البيانات
                foreach ($batch as $asset) {
                    $assetId = $asset->id;
                    $relativePath = $asset->relative_path;

                    // التحقق من وجود النتيجة (حتى لو كانت null)
                    if (array_key_exists($assetId, $results)) {
                        $speakerName = $results[$assetId];

                        if ($speakerName) {
                            if (!$dryRun) {
                                $asset->speaker_name = $speakerName;
                                $asset->save();
                            }
                            $updated++;
                            $this->line("   ✅ {$assetId}: {$speakerName}");
                        } else {
                            // null ليس فشل، بل يعني عدم وجود متحدث - نحدث القاعدة بـ null
                            if (!$dryRun) {
                                $asset->speaker_name = null;
                                $asset->save();
                            }
                            $this->line("   ⚪ {$assetId}: لا يوجد متحدث (null)");
                            $updated++; // نحسبها كتحديث ناجح
                        }
                    } else {
                        $failed++;
                        $this->line("   ❌ {$assetId}: فشل الاستخراج (لم يتم العثور على نتيجة)");
                    }

                    $bar->advance();
                }

                // تأخير صغير بين المجموعات لتجنب rate limit
                if ($batchNumber < $batches->count()) {
                    usleep(1000000); // 1 ثانية بين المجموعات
                }

            } catch (\Exception $e) {
                $this->error("❌ خطأ في معالجة المجموعة {$batchNumber}: " . $e->getMessage());
                Log::error("Error processing batch {$batchNumber}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                // تحديث شريط التقدم للسجلات الفاشلة
                foreach ($batch as $asset) {
                    $failed++;
                    $bar->advance();
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ تمت المعالجة:");
        $this->line("   - محدث: {$updated}");
        $this->line("   - متخطى: {$skipped}");
        $this->line("   - فشل: {$failed}");

        if ($dryRun) {
            $this->warn("\n⚠️  كان هذا تشغيل تجريبي. قم بتشغيل الأمر بدون --dry-run لتحديث قاعدة البيانات");
        }

        return 0;
    }

    /**
     * استخراج أسماء المتحدثين لمجموعة من المسارات في طلب واحد
     */
    private function extractSpeakersBatch($assets, $apiKey)
    {
        $results = [];
        
        // بناء قائمة المسارات مع معرفاتها
        $pathsList = [];
        foreach ($assets as $asset) {
            if ($asset->relative_path) {
                $pathsList[] = [
                    'id' => $asset->id,
                    'path' => $asset->relative_path
                ];
            }
        }

        if (empty($pathsList)) {
            return $results;
        }

        // بناء الـ prompt للمجموعة
        $pathsText = "";
        foreach ($pathsList as $index => $item) {
            $pathsText .= ($index + 1) . ". ID: {$item['id']}, المسار: {$item['path']}\n";
        }

        $prompt = "من المسارات التالية للفيديوهات، استخرج اسم المتحدث (الشيخ) لكل مسار.

{$pathsText}

التعليمات:
1. لكل مسار، ابحث عن اسم المتحدث (الشيخ) في المسار
2. اسم المتحدث قد يكون:
   - في اسم المجلد الفرعي (مثل: ادعية 1447/الشيخ محمد بن عبدالله/...)
   - في بداية اسم الملف (مثل: الشيخ محمد - اللهم داوني.mp4)
   - جزء من المسار يحتوي على كلمات مثل: الشيخ، الدكتور، الأستاذ، أو أي اسم شخص
3. إذا وجدت اسم متحدث واضح، أعد الاسم فقط
4. إذا لم تجد اسم متحدث واضح، أعد كلمة 'null' فقط

أعد النتائج بالصيغة التالية (سطر واحد لكل مسار):
ID: [رقم المعرف], المتحدث: [اسم المتحدث أو null]

مثال:
ID: 1, المتحدث: الشيخ محمد بن عبدالله
ID: 2, المتحدث: null
ID: 3, المتحدث: الدكتور أحمد";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في استخراج أسماء المتحدثين من مسارات الملفات. أعد النتائج بالصيغة المطلوبة فقط.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000, // زيادة الـ tokens للمجموعات الكبيرة
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    Log::warning("DeepSeek API: No content in batch response", ['response' => $data]);
                    return $results;
                }
                
                $responseText = $data['choices'][0]['message']['content'];
                
                if ($this->option('verbose')) {
                    $this->line("   📄 استجابة API كاملة:");
                    $this->line("   " . str_replace("\n", "\n   ", $responseText));
                }
                
                // تحليل النتائج - نماذج مختلفة محتملة
                $lines = explode("\n", $responseText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // النمط 1: ID: [رقم], المتحدث: [اسم] (الأكثر شيوعاً)
                    // مثال: "ID: 1, المتحدث: null" أو "ID: 182, المتحدث: الشيخ الشعراوي"
                    if (preg_match('/ID:\s*(\d+).*?المتحدث:\s*(.+?)$/i', $line, $matches)) {
                        $assetId = (int)$matches[1];
                        $speakerName = trim($matches[2]);
                        $results[$assetId] = $this->cleanSpeakerName($speakerName);
                        if ($this->option('verbose')) {
                            $this->line("   ✓ تم تحليل ID {$assetId}: " . ($results[$assetId] ?? 'null'));
                        }
                        continue;
                    }
                    // النمط 2: [رقم]. ID: [رقم], المتحدث: [اسم]
                    if (preg_match('/\d+\.\s*ID:\s*(\d+).*?المتحدث:\s*(.+?)$/i', $line, $matches)) {
                        $assetId = (int)$matches[1];
                        $speakerName = trim($matches[2]);
                        $results[$assetId] = $this->cleanSpeakerName($speakerName);
                        if ($this->option('verbose')) {
                            $this->line("   ✓ تم تحليل ID {$assetId}: " . ($results[$assetId] ?? 'null'));
                        }
                        continue;
                    }
                    // النمط 3: [رقم]. [اسم] أو null
                    if (preg_match('/^(\d+)\.\s*(.+?)$/i', $line, $matches)) {
                        $assetId = (int)$matches[1];
                        $speakerName = trim($matches[2]);
                        $results[$assetId] = $this->cleanSpeakerName($speakerName);
                        if ($this->option('verbose')) {
                            $this->line("   ✓ تم تحليل ID {$assetId}: " . ($results[$assetId] ?? 'null'));
                        }
                        continue;
                    }
                }
                
                // التأكد من أننا حصلنا على نتائج لجميع السجلات
                // إذا كانت النتيجة null، نضعها في المصفوفة أيضاً
                foreach ($assets as $asset) {
                    if (!array_key_exists($asset->id, $results)) {
                        $results[$asset->id] = null;
                        if ($this->option('verbose')) {
                            $this->warn("   ⚠️  لم يتم العثور على نتيجة لـ ID {$asset->id}");
                        }
                    }
                }

                // إذا لم نتمكن من تحليل النتائج، نعود للطريقة الفردية
                if (empty($results)) {
                    $this->warn("   ⚠️  لم يتم تحليل النتائج بشكل صحيح، جاري المعالجة الفردية...");
                    foreach ($assets as $asset) {
                        $speakerName = $this->extractSpeakerFromPath($asset->relative_path, $apiKey, $asset->id);
                        $results[$asset->id] = $speakerName;
                    }
                }

            } else {
                $errorBody = $response->body();
                $statusCode = $response->status();
                
                Log::error("DeepSeek API Batch Error: Status {$statusCode}", [
                    'body' => $errorBody,
                    'batch_size' => count($pathsList)
                ]);
                
                $this->error("   ❌ خطأ في API: Status {$statusCode}");
                
                // في حالة الخطأ، نعود للطريقة الفردية
                $this->warn("   ⚠️  جاري المعالجة الفردية كبديل...");
                foreach ($assets as $asset) {
                    $speakerName = $this->extractSpeakerFromPath($asset->relative_path, $apiKey, $asset->id);
                    $results[$asset->id] = $speakerName;
                }
            }
        } catch (\Exception $e) {
            Log::error("DeepSeek API Batch Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_size' => count($pathsList)
            ]);
            
            $this->error("   ❌ خطأ: " . $e->getMessage());
            
            // في حالة الخطأ، نعود للطريقة الفردية
            $this->warn("   ⚠️  جاري المعالجة الفردية كبديل...");
            foreach ($assets as $asset) {
                $speakerName = $this->extractSpeakerFromPath($asset->relative_path, $apiKey, $asset->id);
                $results[$asset->id] = $speakerName;
            }
        }

        return $results;
    }

    /**
     * تنظيف اسم المتحدث من النتيجة
     */
    private function cleanSpeakerName($speakerName)
    {
        if (empty($speakerName)) {
            return null;
        }

        // تنظيف النتيجة
        $speakerName = trim($speakerName);
        // إزالة علامات الاقتباس
        $speakerName = preg_replace('/^["\']|["\']$/', '', $speakerName);
        $speakerName = preg_replace('/["\'`]/', '', $speakerName);
        $speakerName = preg_replace('/\s+/', ' ', $speakerName);
        
        // إزالة كلمة "null" إذا كانت موجودة
        $speakerName = preg_replace('/\bnull\b/i', '', $speakerName);
        $speakerName = trim($speakerName);
        
        // إذا كانت النتيجة null أو فارغة أو قصيرة جداً، نرجع null
        if (empty($speakerName) || strlen($speakerName) < 2 || strtolower($speakerName) === 'null') {
            return null;
        }

        return $speakerName;
    }

    /**
     * استخراج اسم متحدث من مسار واحد (للطريقة الفردية كبديل)
     */
    private function extractSpeakerFromPath($relativePath, $apiKey, $assetId = null)
    {
        if (!$relativePath) {
            return null;
        }

        $prompt = "من المسار التالي للفيديو، استخرج اسم المتحدث (الشيخ) فقط.

المسار: {$relativePath}

التعليمات:
1. ابحث عن اسم المتحدث (الشيخ) في المسار
2. اسم المتحدث قد يكون:
   - في اسم المجلد الفرعي (مثل: ادعية 1447/الشيخ محمد بن عبدالله/...)
   - في بداية اسم الملف (مثل: الشيخ محمد - اللهم داوني.mp4)
   - جزء من المسار يحتوي على كلمات مثل: الشيخ، الدكتور، الأستاذ، أو أي اسم شخص
3. إذا وجدت اسم متحدث واضح، أعد الاسم فقط
4. إذا لم تجد اسم متحدث واضح، أعد كلمة 'null' فقط

أمثلة:
- المسار: 'ادعية 1447/الشيخ محمد بن عبدالله/اللهم داوني.mp4'
  النتيجة: الشيخ محمد بن عبدالله

- المسار: 'ادعية 1447/اللهم داوني بدوايِك.mp4'
  النتيجة: null

- المسار: 'موسم ربيع الاول 2025/ من دعاء الحبيب عدد22سنة 2025/الحلقة الثامنة.mp4'
  النتيجة: null (لأنه لا يوجد اسم متحدث واضح)

المهم: أعد فقط اسم المتحدث أو كلمة 'null' بدون أي نص إضافي أو شرح.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في استخراج أسماء المتحدثين من مسارات الملفات. أعد فقط اسم المتحدث أو null.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    Log::warning("DeepSeek API: No content in response", ['response' => $data]);
                    if (app()->runningInConsole() && $this->option('verbose')) {
                        $this->warn("Response data: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                    }
                    return null;
                }
                
                $speakerName = $data['choices'][0]['message']['content'];
                return $this->cleanSpeakerName($speakerName);
            } else {
                $errorBody = $response->body();
                $statusCode = $response->status();
                $errorData = $response->json();
                
                Log::error("DeepSeek API Error: Status {$statusCode}", [
                    'body' => $errorBody,
                    'asset_id' => $assetId,
                    'path' => $relativePath
                ]);
                
                // طباعة الخطأ في الـ console أيضاً
                if (app()->runningInConsole()) {
                    $this->error("API Error: Status {$statusCode}");
                    if (isset($errorData['error']['message'])) {
                        $this->error("الرسالة: " . $errorData['error']['message']);
                    }
                    if ($statusCode === 401) {
                        $this->error("مفتاح API غير صحيح أو منتهي الصلاحية");
                    } elseif ($statusCode === 429) {
                        $this->warn("تم تجاوز حد الطلبات. انتظر قليلاً ثم حاول مرة أخرى");
                    }
                }
                
                return null;
            }
        } catch (\Exception $e) {
            Log::error("DeepSeek API Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'asset_id' => $assetId,
                'path' => $relativePath
            ]);
            
            if (app()->runningInConsole()) {
                $this->error("Exception: " . $e->getMessage());
            }
            
            return null;
        }
    }
}
