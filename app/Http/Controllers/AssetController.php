<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\HlsVersion;
use App\Models\AudioFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::query();

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                  ->orWhere('relative_path', 'like', "%{$search}%");
            });
        }

        // فلترة حسب اسم المتحدث
        if ($request->has('speaker_name') && $request->speaker_name) {
            $query->where('relative_path', 'like', "%{$request->speaker_name}%")
                  ->orWhere('file_name', 'like', "%{$request->speaker_name}%");
        }

        // فلترة حسب الامتداد
        if ($request->has('extension') && $request->extension) {
            $query->where('extension', $request->extension);
        }

        // فلترة حسب الاتجاه
        if ($request->has('orientation') && $request->orientation) {
            $query->where('orientation', $request->orientation);
        }

        // فلترة حسب السنة الهجرية
        if ($request->has('year') && $request->year) {
            $query->where('relative_path', 'like', "%{$request->year}%");
        }

        // فلترة حسب السنة الميلادية
        if ($request->has('gregorian_year') && $request->gregorian_year) {
            $query->where('relative_path', 'like', "%{$request->gregorian_year}%");
        }

        // فلترة حسب التصنيف
        if ($request->has('category') && $request->category) {
            $query->where('relative_path', 'like', "{$request->category}%");
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $assets = $query->paginate(20);

        // إحصائيات
        $stats = [
            'total' => Asset::count(),
            'videos' => Asset::whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v'])->count(),
            'portrait' => Asset::where('orientation', 'portrait')->count(),
            'landscape' => Asset::where('orientation', 'landscape')->count(),
            'square' => Asset::where('orientation', 'square')->count(),
            'total_size_mb' => round(Asset::sum('size_bytes') / (1024 * 1024), 2),
        ];

        // الامتدادات المتاحة
        $extensions = Asset::select('extension')
            ->distinct()
            ->whereNotNull('extension')
            ->pluck('extension')
            ->sort()
            ->values();

        // السنوات الهجرية المتاحة (استخراج من relative_path)
        $years = Asset::select('relative_path')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                if (preg_match_all('/\b(\d{4})\b/', $asset->relative_path, $matches)) {
                    foreach ($matches[1] as $year) {
                        if ($year >= 1300 && $year <= 1500) {
                            return $year;
                        }
                    }
                    // إذا لم نجد سنة هجرية، نأخذ أول رقم
                    return $matches[1][0] ?? null;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // السنوات الميلادية المتاحة (استخراج من relative_path)
        $gregorianYears = Asset::select('relative_path')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                if (preg_match_all('/\b(\d{4})\b/', $asset->relative_path, $matches)) {
                    foreach ($matches[1] as $year) {
                        if ($year >= 1900 && $year <= 2100) {
                            return $year;
                        }
                    }
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // التصنيفات المتاحة (استخراج من relative_path)
        $categories = Asset::select('relative_path')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                $parts = explode('/', $asset->relative_path);
                if (count($parts) > 1) {
                    $firstFolder = $parts[0];
                    // إزالة الأرقام من اسم المجلد
                    $category = preg_replace('/\s*\d{4}\s*/', '', $firstFolder);
                    $category = trim($category);
                    return $category ?: $firstFolder;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // أسماء المتحدثين المتاحة (استخراج من relative_path و file_name)
        $speakerNames = Asset::select('relative_path', 'file_name')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                $parts = explode('/', $asset->relative_path);
                
                // إذا كان هناك مجلدات فرعية، نأخذ ثاني مجلد
                if (count($parts) > 2) {
                    return trim($parts[1]);
                }
                
                // إذا كان هناك مجلد واحد فقط، نحاول استخراج من اسم الملف
                if (count($parts) == 2) {
                    $filename = $parts[1];
                    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                    $speakerName = preg_replace('/\s*\d+\s*$/', '', $filenameWithoutExt);
                    
                    if (preg_match('/^([^-|_]+)/', $speakerName, $matches)) {
                        return trim($matches[1]);
                    }
                    
                    return trim($speakerName) ?: null;
                }
                
                // إذا كان الملف في الجذر
                if (count($parts) == 1) {
                    $filename = $parts[0];
                    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                    $speakerName = preg_replace('/\s*\d+\s*$/', '', $filenameWithoutExt);
                    
                    if (preg_match('/^([^-|_]+)/', $speakerName, $matches)) {
                        return trim($matches[1]);
                    }
                    
                    return trim($speakerName) ?: null;
                }
                
                return null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('assets.index', compact('assets', 'stats', 'extensions', 'years', 'gregorianYears', 'categories', 'speakerNames'));
    }

    public function show(Asset $asset)
    {
        // استخدام select فقط للحقول المطلوبة
        $asset->load(['hlsVersions' => function($query) {
            $query->select('id', 'asset_id', 'resolution', 'width', 'height', 'bitrate', 'audio_bitrate', 'playlist_path', 'master_playlist_path', 'total_size_bytes', 'segment_count');
        }, 'audioFiles' => function($query) {
            $query->select('id', 'asset_id', 'format', 'bitrate', 'sample_rate', 'channels', 'file_path', 'file_size_bytes', 'duration_seconds');
        }]);
        
        // قراءة ملف JSON للـ transcription segments إذا كان موجوداً (مع cache)
        $transcriptionSegments = null;
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            $cacheKey = "transcription_segments_{$asset->id}";
            $transcriptionSegments = Cache::remember($cacheKey, 3600, function() use ($asset) {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir . '/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath = storage_path('app/public/' . $captionDir . '/' . $baseName . '.json');
                
                if (file_exists($jsonPath)) {
                    $jsonContent = file_get_contents($jsonPath);
                    $transcriptionData = json_decode($jsonContent, true);
                    if ($transcriptionData && isset($transcriptionData['segments'])) {
                        return $transcriptionData['segments'];
                    }
                }
                return null;
            });
        }
        
        return view('assets.show', compact('asset', 'transcriptionSegments'));
    }

    public function showPublic(Asset $asset)
    {
        // التحقق من أن الملف منقول إلى الموقع وقابل للنشر
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        
        // التحقق من أن الفيديو قابل للنشر
        if (!$asset->is_publishable) {
            abort(404, 'المحتوى غير متاح للعامة');
        }

        // استخدام select فقط للحقول المطلوبة
        $asset->load(['hlsVersions' => function($query) {
            $query->select('id', 'asset_id', 'resolution', 'width', 'height', 'bitrate', 'audio_bitrate', 'playlist_path', 'master_playlist_path', 'total_size_bytes', 'segment_count');
        }]);
        
        // قراءة ملف JSON للـ transcription segments إذا كان موجوداً (مع cache)
        $transcriptionSegments = null;
        $cacheKey = "transcription_segments_{$asset->id}";
        $transcriptionSegments = Cache::remember($cacheKey, 3600, function() use ($asset) {
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir . '/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath = storage_path('app/public/' . $captionDir . '/' . $baseName . '.json');
                
                if (file_exists($jsonPath)) {
                    $jsonContent = file_get_contents($jsonPath);
                    $transcriptionData = json_decode($jsonContent, true);
                    if ($transcriptionData && isset($transcriptionData['segments'])) {
                        return $transcriptionData['segments'];
                    }
                }
            }
            return null;
        });
        
        // جلب فيديوهات مقترحة (مع cache و select محدود) - فقط القابلة للنشر
        $relatedAssetsCacheKey = "related_assets_{$asset->id}";
        $relatedAssets = Cache::remember($relatedAssetsCacheKey, 1800, function() use ($asset) {
            $related = Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->where('id', '!=', $asset->id)
                ->where(function($query) use ($asset) {
                    if ($asset->speaker_name) {
                        $query->where('speaker_name', $asset->speaker_name);
                    }
                    if ($asset->category) {
                        $query->orWhere('relative_path', 'like', "%{$asset->category}%");
                    }
                })
                ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
            
            // إذا لم يكن هناك فيديوهات مقترحة، نجلب فيديوهات عشوائية (قابلة للنشر فقط)
            if ($related->count() < 5) {
                $randomAssets = Asset::where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true)
                    ->where('id', '!=', $asset->id)
                    ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'extension', 'duration_seconds', 'speaker_name', 'title')
                    ->inRandomOrder()
                    ->limit(10 - $related->count())
                    ->get();
                $related = $related->merge($randomAssets);
            }
            
            return $related;
        });
        
        return view('assets.show-public', compact('asset', 'relatedAssets', 'transcriptionSegments'));
    }

    public function extractMetadata(Asset $asset)
    {
        // استخدام المسار الأصلي إذا كان موجوداً، وإلا استخدام المسار الحالي
        $pathToUse = $asset->original_path ?: $asset->relative_path;
        
        if (!$pathToUse) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'لا يوجد مسار نسبي للملف');
        }

        $apiKey = config('deepseek.api_key');
        if (!$apiKey) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'مفتاح DeepSeek API غير موجود في ملف .env');
        }

        try {
            $prompt = "من المسار التالي للفيديو، أريد منك استخراج:
1. اسم المتحدث (الشيخ) - إذا كان موجوداً
2. العنوان - العنوان الواضح للفيديو من المسار

المسار: {$pathToUse}

التعليمات:
- اسم المتحدث (الشيخ): ابحث عن اسم شخص في المسار (مثل: الشيخ محمد، الدكتور أحمد، الأستاذ علي). إذا لم تجد اسم متحدث واضح، أعد 'null'.
- العنوان: استخرج العنوان الواضح للفيديو من المسار. العنوان عادة يكون اسم الملف بدون الامتداد، أو اسم المجلد الذي يحتوي على العنوان. اجعل العنوان واضحاً ومفهوماً.

أمثلة:
- المسار: 'ادعية 1447/الشيخ محمد بن عبدالله/اللهم داوني.mp4'
  المتحدث: الشيخ محمد بن عبدالله
  العنوان: اللهم داوني

- المسار: 'موسم ربيع الاول 2025/الحلقة الثامنة - أجمل الأقدار.mp4'
  المتحدث: null
  العنوان: الحلقة الثامنة - أجمل الأقدار

أعد النتائج بالصيغة التالية (سطر واحد لكل نتيجة):
المتحدث: [اسم المتحدث أو null]
العنوان: [العنوان الواضح]";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في استخراج المعلومات من مسارات الملفات. أعد النتائج بالصيغة المطلوبة فقط.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 200,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    return redirect()->route('assets.show', $asset)
                        ->with('error', 'فشل في استخراج البيانات من API');
                }

                $responseText = $data['choices'][0]['message']['content'];
                
                // تحليل النتائج
                $speakerName = null;
                $title = null;

                // استخراج اسم المتحدث
                if (preg_match('/المتحدث:\s*(.+?)(?:\n|$)/i', $responseText, $matches)) {
                    $speakerName = $this->cleanText(trim($matches[1]));
                    if (strtolower($speakerName) === 'null' || empty($speakerName) || strlen($speakerName) < 2) {
                        $speakerName = null;
                    }
                }

                // استخراج العنوان
                if (preg_match('/العنوان:\s*(.+?)(?:\n|$)/i', $responseText, $matches)) {
                    $title = $this->cleanText(trim($matches[1]));
                    if (empty($title) || strtolower($title) === 'null') {
                        $title = null;
                    }
                }

                // تحديث قاعدة البيانات
                $updated = false;
                if ($speakerName) {
                    $asset->speaker_name = $speakerName;
                    $updated = true;
                }
                
                if ($title) {
                    $asset->title = $title;
                    $updated = true;
                }

                if ($updated) {
                    $asset->save();
                }

                $message = 'تم استخراج البيانات بنجاح';
                if ($speakerName) {
                    $message .= ' - المتحدث: ' . $speakerName;
                }
                if ($title) {
                    $message .= ' - العنوان: ' . $title;
                }

                return redirect()->route('assets.show', $asset)
                    ->with('success', $message)
                    ->with('extracted_speaker', $speakerName)
                    ->with('extracted_title', $title);

            } else {
                $statusCode = $response->status();
                $errorData = $response->json();
                
                Log::error("DeepSeek API Error: Status {$statusCode}", [
                    'body' => $response->body(),
                    'asset_id' => $asset->id,
                    'path' => $pathToUse
                ]);

                $errorMessage = 'فشل في الاتصال بـ DeepSeek API';
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ': ' . $errorData['error']['message'];
                }

                return redirect()->route('assets.show', $asset)
                    ->with('error', $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error("Extract Metadata Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'asset_id' => $asset->id
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function analyzeContent(Asset $asset)
    {
        if (!$asset->transcription) {
            return response()->json([
                'error' => 'لا يوجد محتوى نصي للتحليل. يرجى استخراج المحتوى النصي أولاً.'
            ], 400);
        }

        $apiKey = config('deepseek.api_key');
        if (!$apiKey) {
            return response()->json([
                'error' => 'مفتاح DeepSeek API غير موجود في ملف .env'
            ], 400);
        }

        try {
            $transcription = $asset->transcription;
            
            // تقليل طول النص إذا كان طويلاً جداً (DeepSeek له حد أقصى)
            if (strlen($transcription) > 10000) {
                $transcription = mb_substr($transcription, 0, 10000) . '...';
            }

            $prompt = "قم بتحليل المحتوى النصي التالي واستخرج tags بسيطة (كلمات أو عبارات قصيرة):

المحتوى النصي:
{$transcription}

التعليمات:
- استخدم tags بسيطة (كلمة واحدة أو كلمتين كحد أقصى)
- تجنب الجمل الطويلة أو الوصف المفصل
- استخدم كلمات واضحة ومباشرة
- كل tag في سطر منفصل

أعد النتائج بالصيغة التالية (بالعربية):
التصنيف:
[تصنيف واحد فقط - كلمة واحدة أو كلمتين كحد أقصى]
يجب أن يكون واحداً من: ادعية، مواعظ، تفسير، حديث، سيرة، فقه، عقيدة
مثال: ادعية
أو: مواعظ
أو: تفسير

تصنيف المحتوى:
[تصنيف واحد فقط من القائمة التالية]
يجب أن يكون واحداً من: آخر الليل، الذرية، طلبة العلم، الصحة والشفاء، الأنس بالله، الطفل
اختر التصنيف الأنسب بناءً على محتوى النص:
- آخر الليل: للمحتوى المتعلق بآخر الليل والدعاء في السحر
- الذرية: للمحتوى المتعلق بالأبناء والذرية
- طلبة العلم: للمحتوى التعليمي والشرعي
- الصحة والشفاء: للمحتوى المتعلق بالصحة والشفاء والدعاء للمرضى
- الأنس بالله: للمحتوى المتعلق بالأنس بالله والتقرب إليه
- الطفل: للمحتوى الموجه للأطفال أو المتعلق بهم
مثال: آخر الليل
أو: الذرية
أو: طلبة العلم

المواضيع:
[قائمة tags بسيطة، كل tag في سطر منفصل]
مثال: رزق
تفريج كرب
توبة
علم

المشاعر:
[قائمة tags بسيطة للمشاعر]
مثال: رجاء
خشوع
طمأنينة
حزن

النية:
[tag واحد أو اثنين يصف الهدف]
مثال: دعاء
أو: موعظة
أو: تعليم

الجمهور:
[قائمة tags بسيطة للجمهور]
مثال: عامة
طلاب
مرضى
شباب

وصف الموقع:
[وصف مناسب ومختصر للمحتوى (2-3 جمل كحد أقصى، لا يتجاوز 200 كلمة)]
يجب أن يكون وصفاً جذاباً ومفيداً يلخص المحتوى بشكل مناسب للعرض في الموقع
مثال: دعاء مؤثر يدعو فيه المتحدث إلى الله تعالى بالرحمة والمغفرة، مع التركيز على أهمية التوبة والرجوع إلى الله في الأوقات الصعبة.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في تحليل المحتوى النصي باللغة العربية. استخرج tags بسيطة (كلمات قصيرة) فقط. لا تستخدم جمل طويلة أو وصف مفصل. استخدم كلمات واضحة ومباشرة.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['choices'][0]['message']['content'])) {
                    return response()->json([
                        'error' => 'فشل في استخراج البيانات من API'
                    ], 400);
                }

                $responseText = $data['choices'][0]['message']['content'];
                
                // تحليل النتائج
                $category = null;
                $contentCategory = null;
                $topics = null;
                $emotions = null;
                $intent = null;
                $audience = null;

                // استخراج التصنيف (الأولوية الأولى)
                if (preg_match('/التصنيف:\s*(.+?)(?=\n(?:تصنيف المحتوى|المواضيع|المشاعر|النية|الجمهور|$))/is', $responseText, $matches)) {
                    $category = trim($matches[1]);
                    // تنظيف التصنيف: أخذ أول سطر فقط وإزالة أي نص إضافي
                    $categoryLines = explode("\n", $category);
                    $category = trim($categoryLines[0]);
                    // التحقق من أن التصنيف صحيح
                    $validCategories = ['ادعية', 'مواعظ', 'تفسير', 'حديث', 'سيرة', 'فقه', 'عقيدة'];
                    $categoryLower = mb_strtolower($category, 'UTF-8');
                    foreach ($validCategories as $validCat) {
                        if (mb_strtolower($validCat, 'UTF-8') === $categoryLower || 
                            mb_strpos($categoryLower, mb_strtolower($validCat, 'UTF-8')) !== false) {
                            $category = $validCat;
                            break;
                        }
                    }
                    if (empty($category) || strtolower($category) === 'null') {
                        $category = null;
                    }
                }

                // استخراج تصنيف المحتوى
                if (preg_match('/تصنيف المحتوى:\s*(.+?)(?=\n(?:المواضيع|المشاعر|النية|الجمهور|التصنيف|وصف الموقع|$))/is', $responseText, $matches)) {
                    $contentCategory = trim($matches[1]);
                    // تنظيف تصنيف المحتوى: أخذ أول سطر فقط وإزالة أي نص إضافي
                    $contentCategoryLines = explode("\n", $contentCategory);
                    $contentCategory = trim($contentCategoryLines[0]);
                    // التحقق من أن تصنيف المحتوى صحيح
                    $validContentCategories = ['آخر الليل', 'الذرية', 'طلبة العلم', 'الصحة والشفاء', 'الأنس بالله', 'الطفل'];
                    $contentCategoryLower = mb_strtolower($contentCategory, 'UTF-8');
                    foreach ($validContentCategories as $validCat) {
                        if (mb_strtolower($validCat, 'UTF-8') === $contentCategoryLower || 
                            mb_strpos($contentCategoryLower, mb_strtolower($validCat, 'UTF-8')) !== false) {
                            $contentCategory = $validCat;
                            break;
                        }
                    }
                    if (empty($contentCategory) || strtolower($contentCategory) === 'null') {
                        $contentCategory = null;
                    }
                }

                // استخراج المواضيع
                if (preg_match('/المواضيع:\s*(.+?)(?=\n(?:المشاعر|النية|الجمهور|التصنيف|$))/is', $responseText, $matches)) {
                    $topics = trim($matches[1]);
                    if (empty($topics) || strtolower($topics) === 'null') {
                        $topics = null;
                    }
                }

                // استخراج المشاعر
                if (preg_match('/المشاعر:\s*(.+?)(?=\n(?:النية|الجمهور|المواضيع|التصنيف|$))/is', $responseText, $matches)) {
                    $emotions = trim($matches[1]);
                    if (empty($emotions) || strtolower($emotions) === 'null') {
                        $emotions = null;
                    }
                }

                // استخراج النية
                if (preg_match('/النية:\s*(.+?)(?=\n(?:الجمهور|المواضيع|المشاعر|التصنيف|$))/is', $responseText, $matches)) {
                    $intent = trim($matches[1]);
                    if (empty($intent) || strtolower($intent) === 'null') {
                        $intent = null;
                    }
                }

                // استخراج الجمهور
                if (preg_match('/الجمهور:\s*(.+?)(?=\n(?:المواضيع|المشاعر|النية|التصنيف|وصف الموقع|$)|$)/is', $responseText, $matches)) {
                    $audience = trim($matches[1]);
                    if (empty($audience) || strtolower($audience) === 'null') {
                        $audience = null;
                    }
                }

                // استخراج وصف الموقع
                if (preg_match('/وصف الموقع:\s*(.+?)(?=\n(?:المواضيع|المشاعر|النية|الجمهور|التصنيف|$)|$)/is', $responseText, $matches)) {
                    $siteDescription = trim($matches[1]);
                    // تنظيف الوصف: إزالة أي نص إضافي بعد الوصف
                    $siteDescriptionLines = explode("\n", $siteDescription);
                    $siteDescription = trim($siteDescriptionLines[0]);
                    // إذا كان الوصف طويلاً جداً، نأخذ أول 200 كلمة
                    $words = explode(' ', $siteDescription);
                    if (count($words) > 200) {
                        $siteDescription = implode(' ', array_slice($words, 0, 200)) . '...';
                    }
                    if (empty($siteDescription) || strtolower($siteDescription) === 'null') {
                        $siteDescription = null;
                    }
                }

                // تحديث قاعدة البيانات
                $updated = false;
                if ($topics) {
                    $asset->topics = $topics;
                    $updated = true;
                }
                
                if ($emotions) {
                    $asset->emotions = $emotions;
                    $updated = true;
                }
                
                if ($intent) {
                    $asset->intent = $intent;
                    $updated = true;
                }
                
                if ($audience) {
                    $asset->audience = $audience;
                    $updated = true;
                }

                // تحديث وصف الموقع
                if ($siteDescription) {
                    $asset->site_description = $siteDescription;
                    $updated = true;
                }

                // تحديث تصنيف المحتوى بناءً على التحليل من DeepSeek
                if ($contentCategory) {
                    $asset->content_category = $contentCategory;
                    $updated = true;
                }

                // تحديث التصنيف بناءً على التحليل من DeepSeek
                if ($category) {
                    $this->updateCategoryFromAnalysis($asset, $category);
                }

                if ($updated) {
                    $asset->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحليل المحتوى بنجاح',
                    'data' => [
                        'category' => $category,
                        'content_category' => $contentCategory,
                        'topics' => $topics,
                        'emotions' => $emotions,
                        'intent' => $intent,
                        'audience' => $audience,
                        'site_description' => $siteDescription,
                    ]
                ]);
            } else {
                $errorMessage = 'فشل في الاتصال بـ DeepSeek API';
                if ($response->json() && isset($response->json()['error']['message'])) {
                    $errorMessage = $response->json()['error']['message'];
                }
                
                return response()->json([
                    'error' => $errorMessage
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error analyzing content', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'حدث خطأ أثناء تحليل المحتوى: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transcribe(Asset $asset)
    {
        if (!$asset->relative_path) {
            return response()->json(['error' => 'لا يوجد مسار نسبي للملف'], 400);
        }

        // التحقق من أن الملف موجود في storage
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            return response()->json([
                'error' => 'لا يمكن استخراج المحتوى النصي. يجب نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".'
            ], 400);
        }

        if (!Storage::disk('public')->exists($asset->relative_path)) {
            return response()->json([
                'error' => 'الملف غير موجود في الموقع. يرجى نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".'
            ], 400);
        }

        // التحقق من وجود عملية جارية
        $cacheKey = "transcription_{$asset->id}";
        $existingStatus = Cache::get($cacheKey);
        
        // إذا كانت العملية مكتملة أو فاشلة، نسمح ببدء عملية جديدة
        if ($existingStatus && isset($existingStatus['status'])) {
            if ($existingStatus['status'] === 'completed' || $existingStatus['status'] === 'error') {
                // حذف Cache القديم للسماح بعملية جديدة
                Cache::forget($cacheKey);
            } elseif ($existingStatus['status'] === 'running') {
                // التحقق من أن العملية لا تزال تعمل (عن طريق PID)
                $processRunning = false;
                if (isset($existingStatus['pid'])) {
                    $pid = $existingStatus['pid'];
                    // التحقق من أن العملية لا تزال تعمل
                    if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
                        $checkCmd = "ps -p {$pid} -o pid= 2>/dev/null";
                        $result = trim(shell_exec($checkCmd));
                        $processRunning = !empty($result);
                    }
                }
                
                // التحقق أيضاً من ملف السجل إذا كان موجوداً
                if (!$processRunning && isset($existingStatus['log_file']) && file_exists($existingStatus['log_file'])) {
                    $logContent = file_get_contents($existingStatus['log_file']);
                    $hasSuccess = strpos($logContent, 'SUCCESS') !== false;
                    $hasError = strpos($logContent, 'ERROR') !== false;
                    
                    // إذا كانت العملية انتهت (نجحت أو فشلت)، نحذف الـ cache
                    if ($hasSuccess || $hasError) {
                        Log::info('Process finished but cache still shows running, clearing cache', [
                            'asset_id' => $asset->id,
                            'has_success' => $hasSuccess,
                            'has_error' => $hasError,
                        ]);
                        Cache::forget($cacheKey);
                        $processRunning = false; // السماح ببدء عملية جديدة
                    }
                }
                
                if ($processRunning) {
                    return response()->json([
                        'error' => 'هناك عملية استخراج جارية بالفعل',
                        'can_clear' => true,
                        'cache_key' => $cacheKey
                    ], 400);
                } else {
                    // العملية توقفت، حذف Cache
                    Log::info('Process stopped, clearing cache', ['asset_id' => $asset->id]);
                    Cache::forget($cacheKey);
                }
            }
        }

        $scriptPath = base_path('scripts/transcribe_video.py');
        
        if (!file_exists($scriptPath)) {
            return response()->json(['error' => 'سكريبت الاستخراج غير موجود'], 400);
        }

        try {
            // بناء المسار الكامل للفيديو من storage
            $fullVideoPath = Storage::disk('public')->path($asset->relative_path);
            
            // التحقق من وجود الملف
            if (!file_exists($fullVideoPath)) {
                Log::error("Video file not found", [
                    'asset_id' => $asset->id,
                    'relative_path' => $asset->relative_path,
                    'full_path' => $fullVideoPath,
                    'storage_disk' => config('filesystems.disks.public.root'),
                    'storage_exists' => Storage::disk('public')->exists($asset->relative_path),
                ]);
                return response()->json([
                    'error' => 'الملف غير موجود في storage: ' . $asset->relative_path,
                    'full_path' => $fullVideoPath
                ], 400);
            }
            
            // التحقق من الصلاحيات
            if (!is_readable($fullVideoPath)) {
                Log::error("Video file not readable", [
                    'asset_id' => $asset->id,
                    'full_path' => $fullVideoPath,
                    'permissions' => substr(sprintf('%o', fileperms($fullVideoPath)), -4),
                ]);
                return response()->json([
                    'error' => 'لا يمكن قراءة الملف. يرجى التحقق من الصلاحيات.'
                ], 403);
            }
            
            // استخدام المسار الكامل للفيديو
            $videoPath = $fullVideoPath;
            
            // البحث عن python3 (يعمل مع Docker و macOS)
            $pythonPaths = [
                '/usr/bin/python3',  // Docker default
                '/usr/local/bin/python3',
                '/opt/homebrew/bin/python3',  // macOS Homebrew
                '/opt/homebrew/opt/python@3.11/bin/python3.11',  // macOS specific version
                trim(shell_exec('which python3 2>/dev/null') ?: ''),
            ];
            
            // إزالة المسارات الفارغة
            $pythonPaths = array_filter($pythonPaths, function($path) {
                return !empty($path) && $path !== '';
            });
            
            $pythonCmd = null;
            $testResults = [];
            
            foreach ($pythonPaths as $path) {
                if (empty($path)) continue;
                
                // التحقق من وجود الملف أولاً
                if (!file_exists($path)) {
                    $testResults[$path] = 'file_not_exists';
                    continue;
                }
                
                // التحقق من قابلية التنفيذ
                if (!is_executable($path)) {
                    $testResults[$path] = 'not_executable';
                    continue;
                }
                
                $testCmd = escapeshellarg($path) . ' -c "import whisper; print(\"OK\")" 2>&1';
                $testOutput = [];
                exec($testCmd, $testOutput, $testCode);
                
                $testResults[$path] = [
                    'exit_code' => $testCode,
                    'output' => implode("\n", $testOutput),
                    'has_whisper' => $testCode === 0 && !empty($testOutput) && $testOutput[0] === 'OK'
                ];
                
                if ($testCode === 0 && !empty($testOutput) && $testOutput[0] === 'OK') {
                    $pythonCmd = $path;
                    Log::info('Found Python with Whisper', [
                        'path' => $path,
                        'test_results' => $testResults
                    ]);
                    break;
                }
            }
            
            if (!$pythonCmd) {
                Log::error('Python with Whisper not found', [
                    'tested_paths' => $pythonPaths,
                    'test_results' => $testResults,
                    'php_os' => PHP_OS,
                    'php_os_family' => PHP_OS_FAMILY,
                ]);
                
                // رسالة خطأ أكثر تفصيلاً
                $errorDetails = [];
                foreach ($testResults as $path => $result) {
                    if (is_array($result)) {
                        $errorDetails[] = "$path: exit_code={$result['exit_code']}, output={$result['output']}";
                    } else {
                        $errorDetails[] = "$path: $result";
                    }
                }
                
                return response()->json([
                    'error' => 'لم يتم العثور على Python3 مع مكتبة Whisper. تأكد من تثبيت openai-whisper.',
                    'details' => $errorDetails,
                    'tested_paths' => array_values($pythonPaths)
                ], 400);
            }
            
            // تهيئة حالة العملية
            Cache::put($cacheKey, [
                'status' => 'running',
                'progress' => 0,
                'message' => 'جاري البدء...'
            ], now()->addHours(2));
            
            // تشغيل السكريبت Python في الخلفية
            // نمرر رقم الفيديو (ID) كـ parameter إضافي
            // نستخدم المسار الكامل للفيديو (من storage) و basePath كمسار أساسي
            $basePath = storage_path('app/public'); // المسار الأساسي لـ storage
            
            // التأكد من وجود مجلد logs
            $logsDir = storage_path('logs');
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }
            
            $logFile = storage_path('logs/transcription_' . $asset->id . '_' . time() . '.log');
            
            // بناء الأمر مع تحسينات للأمان والاستقرار
            $command = escapeshellarg($pythonCmd) . ' ' . 
                      escapeshellarg($scriptPath) . ' ' . 
                      escapeshellarg($videoPath) . ' ' . 
                      escapeshellarg($basePath) . ' ' . 
                      escapeshellarg($asset->id) . 
                      ' > ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
            
            // محاولة تشغيل العملية باستخدام طرق مختلفة
            $pid = null;
            $method = null;
            
            // الطريقة 1: استخدام shell_exec مع nohup (الأفضل للخلفية)
            if (function_exists('shell_exec')) {
                try {
                    // استخدام nohup لضمان استمرار العملية بعد إغلاق الاتصال
                    $nohupCommand = 'nohup ' . escapeshellarg($pythonCmd) . ' ' . 
                                   escapeshellarg($scriptPath) . ' ' . 
                                   escapeshellarg($videoPath) . ' ' . 
                                   escapeshellarg($basePath) . ' ' . 
                                   escapeshellarg($asset->id) . 
                                   ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
                    
                    $pid = trim(shell_exec($nohupCommand));
                    
                    if (!empty($pid) && is_numeric($pid)) {
                        $method = 'shell_exec_nohup';
                        Log::info('Started transcription using shell_exec with nohup', [
                            'asset_id' => $asset->id,
                            'pid' => $pid
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('shell_exec with nohup failed, trying alternative method', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // الطريقة 2: استخدام shell_exec العادي
            if (!$pid && function_exists('shell_exec')) {
                try {
                    $pid = trim(shell_exec($command));
                    if (!empty($pid) && is_numeric($pid)) {
                        $method = 'shell_exec';
                        Log::info('Started transcription using shell_exec', [
                            'asset_id' => $asset->id,
                            'pid' => $pid
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('shell_exec failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // الطريقة 3: استخدام proc_open (قد لا يعمل في الخلفية بشكل صحيح)
            if (!$pid && function_exists('proc_open')) {
                try {
                    $descriptorspec = [
                        0 => ['file', '/dev/null', 'r'],
                        1 => ['file', $logFile, 'a'],
                        2 => ['file', $logFile, 'a']
                    ];
                    
                    // بناء الأمر بدون echo $! في النهاية
                    $baseCommand = escapeshellarg($pythonCmd) . ' ' . 
                                  escapeshellarg($scriptPath) . ' ' . 
                                  escapeshellarg($videoPath) . ' ' . 
                                  escapeshellarg($basePath) . ' ' . 
                                  escapeshellarg($asset->id) . ' &';
                    
                    $process = proc_open($baseCommand, $descriptorspec, $pipes);
                    
                    if (is_resource($process)) {
                        // الحصول على معلومات العملية
                        $processInfo = proc_get_status($process);
                        $pid = $processInfo['pid'];
                        $method = 'proc_open';
                        
                        // إغلاق pipes
                        if (isset($pipes[0])) fclose($pipes[0]);
                        if (isset($pipes[1])) fclose($pipes[1]);
                        if (isset($pipes[2])) fclose($pipes[2]);
                        
                        // إغلاق process handle
                        proc_close($process);
                        
                        Log::info('Started transcription using proc_open', [
                            'asset_id' => $asset->id,
                            'pid' => $pid
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('proc_open failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // الطريقة 4: استخدام exec كبديل أخير
            if (!$pid && function_exists('exec')) {
                try {
                    $output = [];
                    exec($command, $output, $returnVar);
                    // محاولة استخراج PID من output
                    foreach ($output as $line) {
                        if (is_numeric(trim($line))) {
                            $pid = trim($line);
                            $method = 'exec';
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('exec failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // التحقق من أن PID صحيح
            if (empty($pid) || !is_numeric($pid)) {
                // محاولة قراءة ملف السجل لمعرفة الخطأ
                $errorMessage = 'فشل بدء العملية';
                $errorDetails = [];
                
                if (file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    if (!empty($logContent)) {
                        $errorMessage .= ': ' . substr($logContent, 0, 200);
                        $errorDetails['log_preview'] = substr($logContent, 0, 500);
                    }
                }
                
                // إضافة معلومات إضافية
                $errorDetails['pid'] = $pid;
                $errorDetails['method'] = $method;
                $errorDetails['command'] = $command;
                $errorDetails['log_file'] = $logFile;
                $errorDetails['log_exists'] = file_exists($logFile);
                $errorDetails['proc_open_available'] = function_exists('proc_open');
                $errorDetails['shell_exec_available'] = function_exists('shell_exec');
                $errorDetails['exec_available'] = function_exists('exec');
                $errorDetails['disabled_functions'] = ini_get('disable_functions');
                $errorDetails['php_os'] = PHP_OS;
                $errorDetails['php_sapi'] = php_sapi_name();
                
                Log::error("Failed to start transcription process", [
                    'asset_id' => $asset->id,
                    'error_details' => $errorDetails,
                ]);
                
                return response()->json([
                    'error' => $errorMessage,
                    'details' => $errorDetails,
                    'suggestion' => 'يرجى التحقق من: 1) تفعيل proc_open أو shell_exec في PHP, 2) تثبيت Python و Whisper, 3) الصلاحيات على الملفات والمجلدات'
                ], 500);
            }
            
            Log::info("Started transcription process", [
                'asset_id' => $asset->id,
                'pid' => $pid,
                'method' => $method,
                'log_file' => $logFile,
                'command' => $command,
                'python_cmd' => $pythonCmd,
                'script_path' => $scriptPath,
                'video_path' => $videoPath,
                'php_os' => PHP_OS,
                'php_sapi' => php_sapi_name(),
            ]);
            
            // حفظ معلومات العملية
            Cache::put($cacheKey, [
                'status' => 'running',
                'progress' => 5,
                'message' => 'جاري تحميل النموذج...',
                'pid' => $pid,
                'log_file' => $logFile,
                'started_at' => now()->toDateTimeString()
            ], now()->addHours(2));
            
            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية الاستخراج',
                'cache_key' => $cacheKey
            ]);

        } catch (\Exception $e) {
            Log::error("Transcription Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'asset_id' => $asset->id,
                'relative_path' => $asset->relative_path ?? 'N/A',
                'file_exists' => isset($fullVideoPath) ? file_exists($fullVideoPath) : 'N/A',
                'full_path' => isset($fullVideoPath) ? $fullVideoPath : 'N/A',
                'script_exists' => file_exists($scriptPath),
                'script_path' => $scriptPath,
            ]);

            // إرجاع رسالة خطأ أكثر تفصيلاً
            $errorMessage = 'حدث خطأ أثناء بدء العملية: ' . $e->getMessage();
            
            // إضافة معلومات إضافية للمساعدة في التشخيص
            if (strpos($e->getMessage(), 'Permission denied') !== false) {
                $errorMessage .= ' (مشكلة في الصلاحيات)';
            } elseif (strpos($e->getMessage(), 'No such file') !== false) {
                $errorMessage .= ' (الملف غير موجود)';
            } elseif (strpos($e->getMessage(), 'python') !== false || strpos($e->getMessage(), 'Python') !== false) {
                $errorMessage .= ' (مشكلة في Python أو Whisper)';
            }
            
            return response()->json(['error' => $errorMessage], 500);
        }
    }

    public function transcribeStatus(Asset $asset)
    {
        $cacheKey = "transcription_{$asset->id}";
        
        // إذا كان هناك request لحذف Cache
        if (request()->has('clear')) {
            Cache::forget($cacheKey);
            return response()->json([
                'status' => 'cleared',
                'message' => 'تم حذف حالة العملية'
            ]);
        }
        
        $status = Cache::get($cacheKey);
        
        if (!$status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية'
            ]);
        }
        
        // قراءة ملف السجل لتحديث التقدم
        if (isset($status['log_file']) && file_exists($status['log_file'])) {
            $logContent = file_get_contents($status['log_file']);
            
            // إرجاع آخر 50 سطر من السجل للعرض في Terminal
            $logLines = explode("\n", $logContent);
            $recentLines = array_slice($logLines, -50);
            $status['log_lines'] = $recentLines;
            
            // التحقق من أن العملية انتهت (SUCCESS موجود في السجل)
            $hasSuccess = strpos($logContent, 'SUCCESS') !== false;
            $hasTranscriptionEnd = strpos($logContent, 'TRANSCRIPTION_END') !== false;
            
            // التحقق من أن العملية لا تزال تعمل (فحص PID)
            $isProcessRunning = false;
            if (isset($status['pid'])) {
                $pid = $status['pid'];
                // فحص إذا كان الـ process لا يزال يعمل
                $checkProcess = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
                $isProcessRunning = !empty(trim($checkProcess));
            }
            
            // إذا كانت العملية انتهت (SUCCESS موجود) أو الـ process لم يعد يعمل
            if ($hasSuccess || ($hasTranscriptionEnd && !$isProcessRunning)) {
                // العملية اكتملت
                $status['progress'] = 100;
                $status['status'] = 'completed';
                $status['message'] = '✅ تم الانتهاء بنجاح';
                
                // استخراج النص
                $transcription = null;
                if (preg_match('/TRANSCRIPTION_START\s*\n(.*?)\nTRANSCRIPTION_END/s', $logContent, $matches)) {
                    $transcription = trim($matches[1]);
                } elseif (preg_match('/TRANSCRIPTION_START\s*(.*?)\s*TRANSCRIPTION_END/s', $logContent, $matches)) {
                    $transcription = trim($matches[1]);
                }
                
                // إذا لم نجد النص، نحاول قراءته من ملف TXT
                if (empty($transcription) && strpos($logContent, 'TXT:') !== false) {
                    if (preg_match('/TXT:\s*(.+)/', $logContent, $txtMatches)) {
                        $txtPath = trim($txtMatches[1]);
                        if (file_exists($txtPath)) {
                            $transcription = trim(file_get_contents($txtPath));
                        }
                    }
                }
                
                if ($transcription) {
                    // حفظ النص في قاعدة البيانات
                    $asset->transcription = $transcription;
                    
                    // تحديث التصنيف بناءً على المحتوى النصي
                    $this->updateCategoryFromTranscription($asset, $transcription);
                    
                    $asset->save();
                    
                    $status['transcription_length'] = strlen($transcription);
                    $status['message'] = '✅ تم استخراج المحتوى النصي بنجاح (' . number_format(strlen($transcription)) . ' حرف)';
                    
                    // نقل ملفات الـ captions إلى فولدر الفيديو في storage
                    $this->moveCaptionFiles($asset, $logContent);
                } else {
                    $status['message'] = '✅ تم الانتهاء (لم يتم العثور على نص)';
                }
                
                // حذف Cache بعد الانتهاء (بعد 30 ثانية للسماح بإعادة التحميل)
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } elseif (strpos($logContent, 'ERROR:') !== false) {
                $status['status'] = 'error';
                $status['message'] = 'حدث خطأ أثناء الاستخراج';
                if (preg_match('/ERROR:\s*(.+)/', $logContent, $errorMatches)) {
                    $status['error'] = trim($errorMatches[1]);
                }
                // حذف Cache عند الخطأ بعد 30 ثانية
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } elseif (!$isProcessRunning && !$hasSuccess && !$hasTranscriptionEnd) {
                // العملية توقفت لكن لم نجد SUCCESS أو ERROR أو TRANSCRIPTION_END
                // قد تكون العملية انتهت لكن السجل لم يتم تحديثه بعد
                // نتحقق من وجود ملف TXT كدليل على الانتهاء
                $txtPath = null;
                if (preg_match('/TXT:\s*(.+)/', $logContent, $txtMatches)) {
                    $txtPath = trim($txtMatches[1]);
                }
                
                if ($txtPath && file_exists($txtPath)) {
                    // ملف TXT موجود - العملية اكتملت
                    $status['progress'] = 100;
                    $status['status'] = 'completed';
                    $status['message'] = '✅ تم الانتهاء بنجاح';
                    
                    // قراءة النص من ملف TXT
                    $transcription = trim(file_get_contents($txtPath));
                    if ($transcription) {
                        $asset->transcription = $transcription;
                        
                        // تحديث التصنيف بناءً على المحتوى النصي
                        $this->updateCategoryFromTranscription($asset, $transcription);
                        
                        $asset->save();
                        $status['transcription_length'] = strlen($transcription);
                        $status['message'] = '✅ تم استخراج المحتوى النصي بنجاح (' . number_format(strlen($transcription)) . ' حرف)';
                        
                        // نقل ملفات الـ captions إلى فولدر الفيديو في storage
                        $this->moveCaptionFiles($asset, $logContent);
                    }
                    
                    Cache::put($cacheKey, $status, now()->addSeconds(30));
                } else {
                    // العملية توقفت بشكل غير متوقع
                    $status['status'] = 'error';
                    $status['message'] = '⚠️ توقفت العملية بشكل غير متوقع';
                    Cache::put($cacheKey, $status, now()->addSeconds(30));
                }
            } else {
                // تحديث Cache للعملية الجارية
                Cache::put($cacheKey, $status, now()->addHours(2));
            }
        }
        
        return response()->json($status);
    }

    private function cleanText($text)
    {
        if (empty($text)) {
            return null;
        }

        $text = trim($text);
        // إزالة علامات الاقتباس
        $text = preg_replace('/^["\']|["\']$/', '', $text);
        $text = preg_replace('/["\'`]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\bnull\b/i', '', $text);
        $text = trim($text);

        if (empty($text) || strlen($text) < 2) {
            return null;
        }

        return $text;
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')
            ->with('success', 'تم حذف الملف بنجاح');
    }

    public function stats()
    {
        $stats = [
            'total' => Asset::count(),
            'by_extension' => Asset::select('extension', DB::raw('count(*) as count'))
                ->whereNotNull('extension')
                ->groupBy('extension')
                ->orderByDesc('count')
                ->get(),
            'by_orientation' => Asset::select('orientation', DB::raw('count(*) as count'))
                ->whereNotNull('orientation')
                ->groupBy('orientation')
                ->get(),
            'total_size_mb' => round(Asset::sum('size_bytes') / (1024 * 1024), 2),
            'total_duration_hours' => round(Asset::sum('duration_seconds') / 3600, 2),
        ];

        return response()->json($stats);
    }

    public function duplicates()
    {
        // العثور على الملفات المكررة بناءً على اسم الملف
        $duplicatesByName = Asset::select('file_name', DB::raw('count(*) as count'))
            ->groupBy('file_name')
            ->having('count', '>', 1)
            ->get();

        // تجميع الملفات المكررة
        $duplicateGroups = [];
        
        // الملفات المكررة بناءً على اسم الملف
        foreach ($duplicatesByName as $duplicate) {
            $assets = Asset::where('file_name', $duplicate->file_name)->get();
            $duplicateGroups[] = [
                'type' => 'file_name',
                'identifier' => $duplicate->file_name,
                'count' => $duplicate->count,
                'assets' => $assets,
                'total_size' => $assets->sum('size_bytes'),
            ];
        }

        // إحصائيات
        $totalDuplicates = count($duplicateGroups);
        $totalDuplicateFiles = collect($duplicateGroups)->sum('count');
        $totalWastedSpace = collect($duplicateGroups)->map(function($group) {
            // حساب المساحة المهدرة (الحجم الكلي - حجم ملف واحد)
            $oneFileSize = $group['assets']->first()->size_bytes;
            return ($group['count'] - 1) * $oneFileSize;
        })->sum();

        return view('assets.duplicates', [
            'duplicateGroups' => $duplicateGroups,
            'totalDuplicates' => $totalDuplicates,
            'totalDuplicateFiles' => $totalDuplicateFiles,
            'totalWastedSpace' => $totalWastedSpace,
        ]);
    }

    public function moveFile(Asset $asset)
    {
        // التحقق من أن الملف موجود في storage بالفعل
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0 && Storage::disk('public')->exists($asset->relative_path)) {
            return redirect()->route('assets.show', $asset)
                ->with('info', 'الملف موجود بالفعل في الموقع: ' . $asset->relative_path);
        }

        // استخدام المسار الأصلي (original_path) مباشرة
        if (!$asset->original_path) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'لا يوجد مسار أصلي للملف. يرجى التأكد من أن الملف تم استيراده بشكل صحيح.');
        }

        // تحديد المسار الكامل للملف الأصلي
        $oldFullPath = null;
        
        // تنظيف المسار الأصلي
        $originalPath = trim($asset->original_path);
        
        // إذا كان original_path مساراً كاملاً (يبدأ بـ /)، استخدمه مباشرة
        if (strpos($originalPath, '/') === 0) {
            $oldFullPath = $originalPath;
        } else {
            // إذا كان مساراً نسبياً، نحاول البحث في مواقع مختلفة
            // محاولة 1: في storage/app/public/2025 (إذا كان الملف تم نقله مسبقاً)
            $storagePath = storage_path('app/public/' . $originalPath);
            if (file_exists($storagePath)) {
                $oldFullPath = $storagePath;
            } else {
                // محاولة 2: استخدام original_path مباشرة كمسار نسبي
                $oldFullPath = $originalPath;
            }
        }

        // تسجيل المسار للمساعدة في التصحيح
        Log::info('Moving file', [
            'asset_id' => $asset->id,
            'old_full_path' => $oldFullPath,
            'original_path' => $asset->original_path,
            'file_exists' => file_exists($oldFullPath),
        ]);
        
        // التحقق من وجود الملف
        if (!file_exists($oldFullPath)) {
            $errorMessage = 'الملف غير موجود في المسار المحدد: ' . $oldFullPath . 
                           '<br><br>المسار الأصلي في قاعدة البيانات: ' . $asset->original_path .
                           '<br><br>يرجى التأكد من أن الملف موجود في المسار المحدد.';
            
            return redirect()->route('assets.show', $asset)
                ->with('error', $errorMessage);
        }

        // تحديد السنة
        $year = null;
        if ($asset->year) {
            $year = $asset->year;
        } elseif ($asset->gregorian_year) {
            $year = $asset->gregorian_year;
        } else {
            // محاولة استخراج السنة من المسار الأصلي
            if (preg_match('/\b(\d{4})\b/', $asset->original_path, $matches)) {
                $year = $matches[1];
            }
        }

        if (!$year) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'لا يمكن تحديد السنة. يرجى إضافة السنة يدوياً أولاً.');
        }

        // المسار الجديد داخل المشروع: assets/السنة/ID/master.extension
        // مثال: assets/2025/566/master.mp4
        $newStoragePath = 'assets/' . $year . '/' . $asset->id . '/master.' . $asset->extension;
        
        Log::info('Preparing to move file', [
            'asset_id' => $asset->id,
            'source' => $oldFullPath,
            'destination' => $newStoragePath,
            'year' => $year,
        ]);
        
        // استخدام Laravel Storage
        try {
            // التحقق من حجم الملف قبل النسخ
            $fileSize = filesize($oldFullPath);
            if ($fileSize === false) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن قراءة معلومات الملف.');
            }
            
            // إنشاء المجلد إذا لم يكن موجوداً
            Storage::disk('public')->makeDirectory(dirname($newStoragePath));
            
            // نسخ الملف إلى storage/app/public باستخدام stream للتعامل مع الملفات الكبيرة
            $sourceHandle = fopen($oldFullPath, 'rb');
            if (!$sourceHandle) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن فتح الملف المصدر للقراءة.');
            }
            
            // استخدام Laravel Storage stream
            $destinationPath = Storage::disk('public')->path($newStoragePath);
            $destinationHandle = fopen($destinationPath, 'wb');
            if (!$destinationHandle) {
                fclose($sourceHandle);
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن إنشاء الملف الوجهة.');
            }
            
            // نسخ الملف على دفعات (chunks) لتوفير الذاكرة
            $chunkSize = 8192; // 8KB chunks
            $copiedBytes = 0;
            while (!feof($sourceHandle)) {
                $chunk = fread($sourceHandle, $chunkSize);
                if ($chunk === false) {
                    break;
                }
                fwrite($destinationHandle, $chunk);
                $copiedBytes += strlen($chunk);
            }
            
            fclose($sourceHandle);
            fclose($destinationHandle);
            
            // التحقق من أن الملف تم نسخه بنجاح
            if (!Storage::disk('public')->exists($newStoragePath)) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'فشل في نسخ الملف. تم نسخ ' . number_format($copiedBytes) . ' بايت من ' . number_format($fileSize) . ' بايت.');
            }
            
            // التحقق من حجم الملف المنسوخ
            $copiedFileSize = Storage::disk('public')->size($newStoragePath);
            if ($copiedFileSize !== $fileSize) {
                Log::warning('File size mismatch after copy', [
                    'asset_id' => $asset->id,
                    'original_size' => $fileSize,
                    'copied_size' => $copiedFileSize,
                ]);
            }
            
            // حفظ المسار النسبي الأصلي قبل تحديثه (إذا لم يكن محفوظاً من قبل)
            // نحفظ القيمة الحالية لـ relative_path في original_relative_path دائماً
            $currentRelativePath = $asset->relative_path; // حفظ القيمة الحالية
            
            if (!$asset->original_relative_path) {
                // نحفظ المسار النسبي الحالي (حتى لو كان null أو فارغ)
                $asset->original_relative_path = $currentRelativePath;
            }
            
            // تحديث المسار النسبي في قاعدة البيانات للمسار الجديد
            // المسار الجديد: assets/{year}/{id}/master.{extension}
            // مثال: assets/2025/566/master.mp4
            // ملاحظة: لا نغير original_path - يبقى كما هو (المسار الأصلي للملف)
            $asset->relative_path = $newStoragePath;
            $asset->file_name = 'master.' . $asset->extension;
            // original_path يبقى كما هو - لا نغيره
            $asset->save();
            
            // تسجيل التغيير
            Log::info('Relative path updated', [
                'asset_id' => $asset->id,
                'old_relative_path' => $currentRelativePath,
                'new_relative_path' => $newStoragePath,
                'original_relative_path_saved' => $asset->original_relative_path,
            ]);

            // URL للوصول إلى الملف
            $fileUrl = asset('storage/' . $newStoragePath);
            
            Log::info('File moved successfully', [
                'asset_id' => $asset->id,
                'new_path' => $newStoragePath,
                'file_size' => $fileSize,
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم نقل الملف بنجاح (' . number_format($fileSize / 1024 / 1024, 2) . ' MB). يمكنك الوصول إليه عبر: ' . $fileUrl);
        } catch (\Exception $e) {
            Log::error('Failed to move file', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل في نقل الملف: ' . $e->getMessage());
        }
    }

    public function openFolder(Asset $asset)
    {
        if (!$asset->relative_path) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'لا يوجد مسار نسبي للملف');
        }

        try {
            $folderPath = null;

            // إذا كان الملف في storage
            if (strpos($asset->relative_path, 'assets/') === 0) {
                // الملف في storage - فتح فولدر الملف
                $fullPath = Storage::disk('public')->path($asset->relative_path);
                $folderPath = dirname($fullPath);
            } else {
                // الملف خارج storage - استخدام المسار القديم
                $basePath = '/Users/mohamedabdelrahman/Desktop/2025';
                $fullPath = $basePath . '/' . $asset->relative_path;
                $folderPath = dirname($fullPath);
            }

            if (!is_dir($folderPath)) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'الفولدر غير موجود: ' . $folderPath);
            }

            // فتح الفولدر في Finder (macOS)
            if (PHP_OS_FAMILY === 'Darwin') {
                exec("open '" . escapeshellarg($folderPath) . "'");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("explorer '" . escapeshellarg($folderPath) . "'");
            } elseif (PHP_OS_FAMILY === 'Linux') {
                exec("xdg-open '" . escapeshellarg($folderPath) . "'");
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم فتح الفولدر بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل في فتح الفولدر: ' . $e->getMessage());
        }
    }

    public function convertToHls(Asset $asset)
    {
        if (!$asset->relative_path) {
            return response()->json(['error' => 'لا يوجد مسار نسبي للملف'], 400);
        }

        // التحقق من أن الملف موجود في storage
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            return response()->json([
                'error' => 'يجب نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".'
            ], 400);
        }

        if (!Storage::disk('public')->exists($asset->relative_path)) {
            return response()->json([
                'error' => 'الملف غير موجود في الموقع. يرجى نقل الفيديو إلى الموقع أولاً.'
            ], 400);
        }

        // التحقق من وجود FFmpeg (يعمل مع Docker و macOS)
        $possiblePaths = [
            '/usr/bin/ffmpeg',  // Docker default
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',  // macOS Homebrew
            trim(shell_exec('which ffmpeg 2>/dev/null') ?: ''),
        ];
        
        $ffmpegPath = null;
        foreach ($possiblePaths as $path) {
            if (!empty($path) && file_exists($path) && is_executable($path)) {
                $ffmpegPath = $path;
                Log::info('Found FFmpeg', ['path' => $path]);
                break;
            }
        }

        if (empty($ffmpegPath)) {
            Log::error('FFmpeg not found', ['tested_paths' => $possiblePaths]);
            return response()->json(['error' => 'FFmpeg غير مثبت. يرجى تثبيت FFmpeg أولاً.'], 400);
        }

        try {
            // الحصول على المسار الكامل للفيديو
            $videoPath = Storage::disk('public')->path($asset->relative_path);
            $videoDir = dirname($videoPath);
            $hlsDir = $videoDir . '/hls';

            // إنشاء مجلد HLS مع الصلاحيات الصحيحة
            if (!is_dir($hlsDir)) {
                mkdir($hlsDir, 0775, true);
                chmod($hlsDir, 0775);
                // محاولة تغيير المالك (قد لا يعمل في Docker بدون sudo)
                @chown($hlsDir, 'www-data');
            }

            // إنشاء مجلدات للنسخ المختلفة (مع التحقق من الوجود)
            $hlsSubDirs = ['/v0', '/v1', '/v2'];
            foreach ($hlsSubDirs as $subDir) {
                $fullPath = $hlsDir . $subDir;
                if (!is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                    chmod($fullPath, 0775);
                    // محاولة تغيير المالك (قد لا يعمل في Docker بدون sudo)
                    @chown($fullPath, 'www-data');
                }
            }
            
            // التأكد من الصلاحيات باستخدام shell command (يعمل بشكل أفضل في Docker)
            shell_exec("chmod -R 775 " . escapeshellarg($hlsDir) . " 2>/dev/null");
            shell_exec("chown -R www-data:www-data " . escapeshellarg($hlsDir) . " 2>/dev/null");
            
            Log::info('HLS directories created', [
                'asset_id' => $asset->id,
                'hls_dir' => $hlsDir,
                'permissions' => substr(sprintf('%o', fileperms($hlsDir)), -4)
            ]);

            // التحقق من وجود عملية تحويل جارية
            $cacheKey = "hls_conversion_{$asset->id}";
            $existingStatus = Cache::get($cacheKey);
            
            if ($existingStatus && isset($existingStatus['status']) && $existingStatus['status'] === 'running') {
                // التحقق من أن العملية لا تزال تعمل (عن طريق PID)
                if (isset($existingStatus['pid'])) {
                    $pid = $existingStatus['pid'];
                    $processRunning = false;
                    if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
                        $checkCmd = "ps -p {$pid} > /dev/null 2>&1 && echo 'running' || echo 'stopped'";
                        $result = trim(shell_exec($checkCmd));
                        $processRunning = ($result === 'running');
                    }
                    
                    if ($processRunning) {
                        return response()->json(['error' => 'هناك عملية تحويل جارية بالفعل'], 400);
                    } else {
                        // العملية توقفت، حذف Cache
                        Cache::forget($cacheKey);
                    }
                } else {
                    return response()->json(['error' => 'هناك عملية تحويل جارية بالفعل'], 400);
                }
            }

            // ملف السجل
            $logFile = storage_path('logs/hls_conversion_' . $asset->id . '_' . time() . '.log');
            
            // بناء أمر FFmpeg مع إعادة توجيه output إلى ملف السجل
            $command = escapeshellarg($ffmpegPath) . ' -i ' . escapeshellarg($videoPath) . ' ' .
                '-filter_complex ' .
                '"[0:v]split=3[v1][v2][v3]; ' .
                '[v1]scale=w=640:h=360[v1out]; ' .
                '[v2]scale=w=854:h=480[v2out]; ' .
                '[v3]scale=w=1280:h=720[v3out]" ' .
                '-map "[v1out]" -map 0:a -c:v:0 h264 -b:v:0 800k -c:a:0 aac -b:a:0 96k ' .
                '-map "[v2out]" -map 0:a -c:v:1 h264 -b:v:1 1400k -c:a:1 aac -b:a:1 128k ' .
                '-map "[v3out]" -map 0:a -c:v:2 h264 -b:v:2 2800k -c:a:2 aac -b:a:2 128k ' .
                '-f hls ' .
                '-hls_time 6 ' .
                '-hls_playlist_type vod ' .
                '-hls_flags independent_segments ' .
                '-master_pl_name master.m3u8 ' .
                '-var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2" ' .
                '-hls_segment_filename ' . escapeshellarg($hlsDir . '/v%v/seg_%03d.ts') . ' ' .
                escapeshellarg($hlsDir . '/v%v/index.m3u8') . ' ' .
                '> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

            // تشغيل FFmpeg في الخلفية
            $pid = trim(shell_exec($command));
            
            Log::info("Started HLS conversion process", [
                'asset_id' => $asset->id,
                'pid' => $pid,
                'log_file' => $logFile
            ]);
            
            // حفظ معلومات العملية
            Cache::put($cacheKey, [
                'status' => 'running',
                'progress' => 5,
                'message' => 'جاري البدء...',
                'pid' => $pid,
                'log_file' => $logFile,
                'started_at' => now()->toDateTimeString(),
                'hls_dir' => $hlsDir,
                'video_path' => $videoPath
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية التحويل',
                'cache_key' => $cacheKey
            ]);

        } catch (\Exception $e) {
            Log::error("HLS conversion error", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'حدث خطأ أثناء بدء التحويل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function hlsStatus(Asset $asset)
    {
        $cacheKey = "hls_conversion_{$asset->id}";
        
        // إذا كان هناك request لحذف Cache
        if (request()->has('clear')) {
            Cache::forget($cacheKey);
            return response()->json([
                'status' => 'cleared',
                'message' => 'تم حذف حالة العملية'
            ]);
        }
        
        $status = Cache::get($cacheKey);
        
        if (!$status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية'
            ]);
        }
        
        // قراءة ملف السجل لتحديث التقدم
        if (isset($status['log_file']) && file_exists($status['log_file'])) {
            $logContent = file_get_contents($status['log_file']);
            
            // إرجاع آخر 50 سطر من السجل للعرض في Terminal
            $logLines = explode("\n", $logContent);
            $recentLines = array_slice($logLines, -50);
            $status['log_lines'] = $recentLines;
            
            // التحقق من أن العملية لا تزال تعمل (فحص PID)
            $isProcessRunning = false;
            if (isset($status['pid'])) {
                $pid = $status['pid'];
                // فحص إذا كان الـ process لا يزال يعمل
                $checkProcess = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
                $isProcessRunning = !empty(trim($checkProcess));
            }
            
            // التحقق من اكتمال العملية (فحص وجود ملفات playlist)
            $hlsDir = $status['hls_dir'] ?? null;
            $isCompleted = false;
            if ($hlsDir && is_dir($hlsDir)) {
                // التحقق من وجود ملفات playlist
                $masterPlaylist = $hlsDir . '/master.m3u8';
                $v0Playlist = $hlsDir . '/v0/index.m3u8';
                $v1Playlist = $hlsDir . '/v1/index.m3u8';
                $v2Playlist = $hlsDir . '/v2/index.m3u8';
                
                if (file_exists($masterPlaylist) && file_exists($v0Playlist) && 
                    file_exists($v1Playlist) && file_exists($v2Playlist) && !$isProcessRunning) {
                    $isCompleted = true;
                }
            }
            
            // إذا كانت العملية انتهت
            if ($isCompleted || (!$isProcessRunning && strlen($logContent) > 1000)) {
                $status['progress'] = 100;
                $status['status'] = 'completed';
                $status['message'] = '✅ تم الانتهاء بنجاح';
                
                // حفظ معلومات النسخ في قاعدة البيانات
                $this->saveHlsVersions($asset, $hlsDir);
                
                // حذف Cache بعد الانتهاء (بعد 30 ثانية)
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } elseif (strpos($logContent, 'error') !== false || strpos($logContent, 'Error') !== false) {
                $status['status'] = 'error';
                $status['message'] = 'حدث خطأ أثناء التحويل';
                // حذف Cache عند الخطأ بعد 30 ثانية
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } else {
                // تحديث التقدم بناءً على حجم ملف السجل
                $logSize = strlen($logContent);
                $estimatedProgress = min(95, max(5, intval($logSize / 1000))); // تقدير بسيط
                $status['progress'] = $estimatedProgress;
                $status['message'] = 'جاري التحويل...';
            }
        }
        
        return response()->json($status);
    }

    private function saveHlsVersions(Asset $asset, $hlsDir)
    {
        try {
            $storagePath = Storage::disk('public')->path('');
            $relativeHlsDir = str_replace($storagePath, '', $hlsDir);
            if (strpos($relativeHlsDir, '/') === 0) {
                $relativeHlsDir = substr($relativeHlsDir, 1);
            }
            
            $versions = [
                [
                    'resolution' => '360p',
                    'width' => 640,
                    'height' => 360,
                    'bitrate' => '800k',
                    'audio_bitrate' => '96k',
                    'playlist_path' => $relativeHlsDir . '/v0/index.m3u8',
                ],
                [
                    'resolution' => '480p',
                    'width' => 854,
                    'height' => 480,
                    'bitrate' => '1400k',
                    'audio_bitrate' => '128k',
                    'playlist_path' => $relativeHlsDir . '/v1/index.m3u8',
                ],
                [
                    'resolution' => '720p',
                    'width' => 1280,
                    'height' => 720,
                    'bitrate' => '2800k',
                    'audio_bitrate' => '128k',
                    'playlist_path' => $relativeHlsDir . '/v2/index.m3u8',
                ],
            ];

            $masterPlaylistPath = $relativeHlsDir . '/master.m3u8';

            foreach ($versions as $version) {
                $playlistFullPath = Storage::disk('public')->path($version['playlist_path']);
                
                // حساب حجم الملفات وعدد القطع
                $totalSize = 0;
                $segmentCount = 0;
                
                if (file_exists($playlistFullPath)) {
                    $playlistContent = file_get_contents($playlistFullPath);
                    $segmentDir = dirname($playlistFullPath);
                    
                    // حساب عدد القطع وحجمها
                    preg_match_all('/seg_\d+\.ts/', $playlistContent, $matches);
                    $segmentCount = count($matches[0]);
                    
                    foreach ($matches[0] as $segmentFile) {
                        $segmentPath = $segmentDir . '/' . $segmentFile;
                        if (file_exists($segmentPath)) {
                            $totalSize += filesize($segmentPath);
                        }
                    }
                }

                HlsVersion::updateOrCreate(
                    [
                        'asset_id' => $asset->id,
                        'resolution' => $version['resolution'],
                    ],
                    [
                        'width' => $version['width'],
                        'height' => $version['height'],
                        'bitrate' => $version['bitrate'],
                        'audio_bitrate' => $version['audio_bitrate'],
                        'playlist_path' => $version['playlist_path'],
                        'master_playlist_path' => $masterPlaylistPath,
                        'total_size_bytes' => $totalSize,
                        'segment_count' => $segmentCount,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Failed to save HLS versions", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function extractAudio(Asset $asset)
    {
        try {
            // التحقق من أن الملف موجود في storage
            if (strpos($asset->relative_path, 'assets/') !== 0) {
                return response()->json([
                    'error' => 'يجب نقل الفيديو إلى الموقع أولاً'
                ], 400);
            }

            $videoPath = Storage::disk('public')->path($asset->relative_path);
            
            if (!file_exists($videoPath)) {
                return response()->json([
                    'error' => 'الملف غير موجود'
                ], 404);
            }

            // التحقق من أن الملف فيديو
            if (!in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi'])) {
                return response()->json([
                    'error' => 'الملف ليس فيديو'
                ], 400);
            }

            // إنشاء مجلد للصوت
            $audioDir = dirname($videoPath) . '/audio';
            if (!is_dir($audioDir)) {
                mkdir($audioDir, 0755, true);
            }

            // اسم ملف الصوت
            $audioFileName = 'audio.mp3';
            $audioPath = $audioDir . '/' . $audioFileName;
            $logFile = $audioDir . '/extract_audio.log';

            // إنشاء ملف log فارغ
            file_put_contents($logFile, '');

            // أمر ffmpeg لاستخراج الصوت بصيغة MP3 (مناسبة لـ SoundCloud و Spotify)
            // استخدام -progress لكتابة التقدم في ملف log
            $command = 'ffmpeg -i ' . escapeshellarg($videoPath) . ' ' .
                '-vn ' . // لا نريد فيديو
                '-acodec libmp3lame ' . // استخدام MP3 codec
                '-ab 192k ' . // معدل البت 192k (جودة عالية)
                '-ar 44100 ' . // معدل العينة 44.1kHz (معيار CD)
                '-ac 2 ' . // ستريو (2 قنوات)
                '-y ' . // استبدال الملف إذا كان موجوداً
                '-progress ' . escapeshellarg($logFile) . ' ' .
                escapeshellarg($audioPath) . ' ' .
                '2>&1 & echo $!';

            // تشغيل ffmpeg في الخلفية
            $pid = trim(shell_exec($command));
            
            Log::info("Started audio extraction process", [
                'asset_id' => $asset->id,
                'pid' => $pid,
                'log_file' => $logFile,
                'audio_path' => $audioPath
            ]);
            
            // حفظ معلومات العملية
            $cacheKey = "audio_extraction_{$asset->id}";
            Cache::put($cacheKey, [
                'status' => 'running',
                'progress' => 5,
                'message' => 'جاري البدء...',
                'pid' => $pid,
                'log_file' => $logFile,
                'started_at' => now()->toDateTimeString(),
                'audio_path' => $audioPath,
                'audio_dir' => $audioDir
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية استخراج الصوت',
                'cache_key' => $cacheKey
            ]);

        } catch (\Exception $e) {
            Log::error("Audio extraction error", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'حدث خطأ أثناء بدء العملية: ' . $e->getMessage()
            ], 500);
        }
    }

    public function extractAudioStatus(Asset $asset)
    {
        $cacheKey = "audio_extraction_{$asset->id}";
        
        // إذا كان هناك request لحذف Cache
        if (request()->has('clear')) {
            Cache::forget($cacheKey);
            return response()->json([
                'status' => 'cleared',
                'message' => 'تم حذف حالة العملية'
            ]);
        }
        
        $status = Cache::get($cacheKey);
        
        if (!$status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية'
            ]);
        }
        
        // قراءة ملف السجل لتحديث التقدم
        if (isset($status['log_file']) && file_exists($status['log_file'])) {
            $logContent = file_get_contents($status['log_file']);
            
            // إرجاع آخر 50 سطر من السجل للعرض في Terminal
            $logLines = explode("\n", $logContent);
            $recentLines = array_slice($logLines, -50);
            $status['log_lines'] = $recentLines;
            
            // التحقق من أن العملية لا تزال تعمل (فحص PID)
            $isProcessRunning = false;
            if (isset($status['pid'])) {
                $pid = $status['pid'];
                $checkProcess = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
                $isProcessRunning = !empty(trim($checkProcess));
            }
            
            // التحقق من اكتمال العملية (فحص وجود ملف الصوت و progress=end)
            $audioPath = $status['audio_path'] ?? null;
            $isCompleted = false;
            
            // التحقق من progress=end في log file
            $hasProgressEnd = strpos($logContent, 'progress=end') !== false;
            
            // التحقق من وجود ملف الصوت وحجمه (يجب أن يكون أكبر من 0)
            if ($audioPath && file_exists($audioPath)) {
                $audioSize = filesize($audioPath);
                // إذا كان الملف موجوداً وحجمه أكبر من 0 و (العملية لم تعد تعمل أو progress=end)
                if ($audioSize > 0 && (!$isProcessRunning || $hasProgressEnd)) {
                    $isCompleted = true;
                }
            } elseif ($hasProgressEnd && !$isProcessRunning) {
                // إذا كان progress=end والعملية لم تعد تعمل، نعتبر العملية مكتملة
                $isCompleted = true;
            }
            
            // إذا كانت العملية انتهت
            if ($isCompleted) {
                $status['progress'] = 100;
                $status['status'] = 'completed';
                $status['message'] = '✅ تم الانتهاء بنجاح';
                
                // حفظ معلومات ملف الصوت
                if (file_exists($audioPath)) {
                    $audioSize = filesize($audioPath);
                    $status['audio_size'] = $audioSize;
                    $status['audio_size_mb'] = round($audioSize / (1024 * 1024), 2);
                    
                    // حساب المسار النسبي
                    $storagePath = Storage::disk('public')->path('');
                    $relativeAudioPath = str_replace($storagePath, '', $audioPath);
                    if (strpos($relativeAudioPath, '/') === 0) {
                        $relativeAudioPath = substr($relativeAudioPath, 1);
                    }
                    $status['audio_url'] = asset('storage/' . $relativeAudioPath);
                    
                    // حفظ في قاعدة البيانات
                    $this->saveAudioFile($asset, $relativeAudioPath, $audioSize);
                }
                
                // حذف Cache بعد الانتهاء (بعد 30 ثانية)
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } elseif (strpos($logContent, 'error') !== false || strpos($logContent, 'Error') !== false) {
                $status['status'] = 'error';
                $status['message'] = 'حدث خطأ أثناء الاستخراج';
                // حذف Cache عند الخطأ بعد 30 ثانية
                Cache::put($cacheKey, $status, now()->addSeconds(30));
            } else {
                // تحديث التقدم بناءً على معلومات FFmpeg progress
                $progress = 5;
                
                // محاولة استخراج التقدم من log file (FFmpeg يكتب progress في صيغة key=value)
                if (preg_match('/out_time_ms=(\d+)/', $logContent, $matches)) {
                    $currentTime = intval($matches[1]) / 1000000; // تحويل من microseconds إلى seconds
                    if ($asset->duration_seconds && $asset->duration_seconds > 0) {
                        $progress = min(95, max(5, intval(($currentTime / $asset->duration_seconds) * 100)));
                    }
                } elseif (preg_match('/out_time=([\d:]+)/', $logContent, $matches)) {
                    // محاولة أخرى بصيغة out_time (HH:MM:SS.microseconds)
                    $timeStr = $matches[1];
                    $timeParts = explode(':', $timeStr);
                    if (count($timeParts) >= 3) {
                        $hours = intval($timeParts[0]);
                        $minutes = intval($timeParts[1]);
                        $secondsParts = explode('.', $timeParts[2]);
                        $seconds = intval($secondsParts[0]);
                        $currentTime = $hours * 3600 + $minutes * 60 + $seconds;
                        
                        if ($asset->duration_seconds && $asset->duration_seconds > 0) {
                            $progress = min(95, max(5, intval(($currentTime / $asset->duration_seconds) * 100)));
                        }
                    }
                } elseif ($isProcessRunning) {
                    // إذا كانت العملية لا تزال تعمل لكن لم نجد progress، نستخدم تقدير بسيط
                    $logSize = strlen($logContent);
                    if ($logSize > 100) {
                        // إذا كان log file كبير، نعتبر أن العملية بدأت
                        $progress = min(20, max(5, intval($logSize / 500)));
                    }
                }
                
                // إذا كان progress=end، نضع التقدم على 100% ونعتبر العملية مكتملة
                if (strpos($logContent, 'progress=end') !== false) {
                    $progress = 100;
                    // إذا كان progress=end والملف موجود، نعتبر العملية مكتملة
                    if ($audioPath && file_exists($audioPath) && filesize($audioPath) > 0) {
                        $isCompleted = true;
                    }
                }
                
                $status['progress'] = $progress;
                $status['message'] = 'جاري استخراج الصوت...';
                
                // إذا تم اكتشاف اكتمال العملية من progress=end
                if ($isCompleted) {
                    $status['progress'] = 100;
                    $status['status'] = 'completed';
                    $status['message'] = '✅ تم الانتهاء بنجاح';
                    
                    // حفظ معلومات ملف الصوت
                    if ($audioPath && file_exists($audioPath)) {
                        $audioSize = filesize($audioPath);
                        $status['audio_size'] = $audioSize;
                        $status['audio_size_mb'] = round($audioSize / (1024 * 1024), 2);
                        
                        // حساب المسار النسبي
                        $storagePath = Storage::disk('public')->path('');
                        $relativeAudioPath = str_replace($storagePath, '', $audioPath);
                        if (strpos($relativeAudioPath, '/') === 0) {
                            $relativeAudioPath = substr($relativeAudioPath, 1);
                        }
                        $status['audio_url'] = asset('storage/' . $relativeAudioPath);
                        
                        // حفظ في قاعدة البيانات
                        $this->saveAudioFile($asset, $relativeAudioPath, $audioSize);
                    }
                    
                    // حذف Cache بعد الانتهاء (بعد 30 ثانية)
                    Cache::put($cacheKey, $status, now()->addSeconds(30));
                } else {
                    // تحديث Cache
                    Cache::put($cacheKey, $status, now()->addHours(2));
                }
            }
        }
        
        return response()->json($status);
    }

    private function saveAudioFile(Asset $asset, $relativeAudioPath, $fileSize)
    {
        try {
            AudioFile::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'format' => 'mp3',
                ],
                [
                    'bitrate' => '192k',
                    'sample_rate' => 44100,
                    'channels' => 2,
                    'file_path' => $relativeAudioPath,
                    'file_size_bytes' => $fileSize,
                    'duration_seconds' => $asset->duration_seconds,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to save audio file", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function togglePublishable(Asset $asset)
    {
        try {
            $asset->is_publishable = !$asset->is_publishable;
            $asset->save();
            
            // مسح cache الصفحة الرئيسية
            Cache::forget('home_shorts');
            Cache::forget('home_stats');
            Cache::forget('home_speaker_names');
            Cache::forget('home_categories');
            Cache::forget('home_years');
            
            $message = $asset->is_publishable 
                ? 'تم تفعيل النشر بنجاح' 
                : 'تم إلغاء النشر بنجاح';
            
            return redirect()->route('assets.show', $asset)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ أثناء تحديث حالة النشر: ' . $e->getMessage());
        }
    }

    public function uploadThumbnail(Asset $asset, Request $request)
    {
        $request->validate([
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            // إذا كان الملف في storage، نحفظ الصورة المصغرة في نفس المجلد
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                $videoDir = dirname($asset->relative_path);
                $thumbnailDir = $videoDir . '/thumbnails';
                
                // إنشاء مجلد thumbnails إذا لم يكن موجوداً
                Storage::disk('public')->makeDirectory($thumbnailDir);
                
                // حفظ الصورة المصغرة
                $thumbnailPath = $request->file('thumbnail')->store($thumbnailDir, 'public');
                
                // تحديث قاعدة البيانات
                $asset->thumbnail_path = $thumbnailPath;
                $asset->save();
                
                return redirect()->route('assets.show', $asset)
                    ->with('success', 'تم رفع الصورة المصغرة بنجاح');
            } else {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'يجب نقل الفيديو إلى الموقع أولاً');
            }
        } catch (\Exception $e) {
            Log::error("Thumbnail upload error", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ أثناء رفع الصورة المصغرة: ' . $e->getMessage());
        }
    }

    public function analytics()
    {
        // تحليل الشيوخ
        $speakers = Asset::select('relative_path', 'file_name', 'orientation', 'duration_seconds', 'size_bytes')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                return [
                    'speaker' => $asset->speaker_name,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function($item) {
                return !empty($item['speaker']);
            })
            ->groupBy('speaker')
            ->map(function($items, $speaker) {
                return [
                    'name' => $speaker,
                    'total_videos' => $items->count(),
                    'landscape' => $items->where('orientation', 'landscape')->count(),
                    'portrait' => $items->where('orientation', 'portrait')->count(),
                    'square' => $items->where('orientation', 'square')->count(),
                    'total_duration_seconds' => $items->sum('duration'),
                    'total_duration_formatted' => $this->formatDuration($items->sum('duration')),
                    'total_size_bytes' => $items->sum('size'),
                    'total_size_mb' => round($items->sum('size') / (1024 * 1024), 2),
                    'avg_duration_seconds' => round($items->avg('duration'), 0),
                    'avg_duration_formatted' => $this->formatDuration(round($items->avg('duration'), 0)),
                ];
            })
            ->sortByDesc('total_videos')
            ->values();

        // تحليل التصنيفات
        $categories = Asset::select('relative_path', 'orientation', 'duration_seconds', 'size_bytes')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                return [
                    'category' => $asset->category,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function($item) {
                return !empty($item['category']);
            })
            ->groupBy('category')
            ->map(function($items, $category) {
                return [
                    'name' => $category,
                    'total_videos' => $items->count(),
                    'landscape' => $items->where('orientation', 'landscape')->count(),
                    'portrait' => $items->where('orientation', 'portrait')->count(),
                    'square' => $items->where('orientation', 'square')->count(),
                    'total_duration_seconds' => $items->sum('duration'),
                    'total_duration_formatted' => $this->formatDuration($items->sum('duration')),
                    'total_size_mb' => round($items->sum('size') / (1024 * 1024), 2),
                ];
            })
            ->sortByDesc('total_videos')
            ->values();

        // تحليل السنوات
        $years = Asset::select('relative_path', 'orientation', 'duration_seconds', 'size_bytes')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function($asset) {
                return [
                    'year' => $asset->year,
                    'gregorian_year' => $asset->gregorian_year,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function($item) {
                return !empty($item['year']);
            })
            ->groupBy('year')
            ->map(function($items, $year) {
                return [
                    'year' => $year,
                    'total_videos' => $items->count(),
                    'landscape' => $items->where('orientation', 'landscape')->count(),
                    'portrait' => $items->where('orientation', 'portrait')->count(),
                    'total_duration_formatted' => $this->formatDuration($items->sum('duration')),
                    'total_size_mb' => round($items->sum('size') / (1024 * 1024), 2),
                ];
            })
            ->sortByDesc('year')
            ->values();

        // إحصائيات عامة
        $generalStats = [
            'total_videos' => Asset::count(),
            'total_speakers' => $speakers->count(),
            'total_categories' => $categories->count(),
            'total_duration_hours' => round(Asset::sum('duration_seconds') / 3600, 2),
            'total_size_gb' => round(Asset::sum('size_bytes') / (1024 * 1024 * 1024), 2),
            'landscape_count' => Asset::where('orientation', 'landscape')->count(),
            'portrait_count' => Asset::where('orientation', 'portrait')->count(),
            'square_count' => Asset::where('orientation', 'square')->count(),
        ];

        return view('assets.analytics', compact('speakers', 'categories', 'years', 'generalStats'));
    }

    private function moveCaptionFiles(Asset $asset, $logContent)
    {
        // التحقق من أن الملف موجود في storage
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            return; // الملف ليس في storage، لا حاجة لنقل الملفات
        }

        try {
            // استخراج مسارات ملفات الـ captions من السجل
            $jsonPath = null;
            $txtPath = null;
            $timedTxtPath = null;

            if (preg_match('/JSON:\s*(.+)/', $logContent, $matches)) {
                $jsonPath = trim($matches[1]);
            }
            if (preg_match('/TXT:\s*(.+)/', $logContent, $matches)) {
                $txtPath = trim($matches[1]);
            }
            if (preg_match('/TIMED_TXT:\s*(.+)/', $logContent, $matches)) {
                $timedTxtPath = trim($matches[1]);
            }

            // فولدر الفيديو في storage (نفس فولدر الملف)
            $videoDir = dirname($asset->relative_path);
            $captionDir = $videoDir . '/captions';
            
            // إنشاء فولدر captions إذا لم يكن موجوداً
            Storage::disk('public')->makeDirectory($captionDir);
            
            // تعيين الصلاحيات الصحيحة للمجلد والملفات (775 للوصول من العام)
            $captionFullPath = Storage::disk('public')->path($captionDir);
            chmod($captionFullPath, 0775);
            @chown($captionFullPath, 'www-data');
            shell_exec("chmod -R 775 " . escapeshellarg($captionFullPath) . " 2>/dev/null");
            shell_exec("chown -R www-data:www-data " . escapeshellarg($captionFullPath) . " 2>/dev/null");

            $movedFiles = [];

            // نقل ملف JSON
            if ($jsonPath && file_exists($jsonPath)) {
                $jsonContent = file_get_contents($jsonPath);
                $jsonFileName = basename($jsonPath);
                $newJsonPath = $captionDir . '/' . $jsonFileName;
                Storage::disk('public')->put($newJsonPath, $jsonContent);
                // تعيين الصلاحيات للملف
                $newJsonFullPath = Storage::disk('public')->path($newJsonPath);
                chmod($newJsonFullPath, 0664);
                @chown($newJsonFullPath, 'www-data');
                $movedFiles[] = $jsonFileName;
            }

            // نقل ملف TXT
            if ($txtPath && file_exists($txtPath)) {
                $txtContent = file_get_contents($txtPath);
                $txtFileName = basename($txtPath);
                $newTxtPath = $captionDir . '/' . $txtFileName;
                Storage::disk('public')->put($newTxtPath, $txtContent);
                // تعيين الصلاحيات للملف
                $newTxtFullPath = Storage::disk('public')->path($newTxtPath);
                chmod($newTxtFullPath, 0664);
                @chown($newTxtFullPath, 'www-data');
                $movedFiles[] = $txtFileName;
            }

            // نقل ملف TIMED_TXT
            if ($timedTxtPath && file_exists($timedTxtPath)) {
                $timedTxtContent = file_get_contents($timedTxtPath);
                $timedTxtFileName = basename($timedTxtPath);
                $newTimedTxtPath = $captionDir . '/' . $timedTxtFileName;
                Storage::disk('public')->put($newTimedTxtPath, $timedTxtContent);
                // تعيين الصلاحيات للملف
                $newTimedTxtFullPath = Storage::disk('public')->path($newTimedTxtPath);
                chmod($newTimedTxtFullPath, 0664);
                @chown($newTimedTxtFullPath, 'www-data');
                $movedFiles[] = $timedTxtFileName;
            }
            
            // التأكد من الصلاحيات النهائية لجميع الملفات
            shell_exec("chmod -R 775 " . escapeshellarg($captionFullPath) . " 2>/dev/null");
            shell_exec("chown -R www-data:www-data " . escapeshellarg($captionFullPath) . " 2>/dev/null");

            if (!empty($movedFiles)) {
                Log::info("Moved caption files to storage", [
                    'asset_id' => $asset->id,
                    'caption_dir' => $captionDir,
                    'files' => $movedFiles
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to move caption files", [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تحديث التصنيف بناءً على التحليل من DeepSeek API
     */
    private function updateCategoryFromAnalysis(Asset $asset, $detectedCategory)
    {
        // التحقق من التصنيف الحالي
        $currentCategory = $asset->category; // استخدام accessor
        
        // تحديث المسار فقط إذا:
        // 1. التصنيف المكتشف مختلف عن الحالي
        // 2. الملف موجود في storage (assets/)
        if ($currentCategory !== $detectedCategory && strpos($asset->relative_path, 'assets/') === 0) {
            $parts = explode('/', $asset->relative_path);
            
            // حفظ المسار القديم قبل التعديل
            $oldRelativePath = $asset->relative_path;
            
            // الهيكل الحالي: assets/{year}/{id}/master.ext
            // أو: assets/{category}/{year}/{id}/master.ext
            // الهيكل الجديد: assets/{category}/{year}/{id}/master.ext
            
            $year = null;
            $id = null;
            $fileName = null;
            
            if (count($parts) >= 4) {
                // الحالة 1: assets/{year}/{id}/master.ext
                if (is_numeric($parts[1])) {
                    $year = $parts[1];
                    $id = $parts[2];
                    $fileName = $parts[3];
                }
                // الحالة 2: assets/{category}/{year}/{id}/master.ext
                elseif (count($parts) >= 5 && is_numeric($parts[2])) {
                    $year = $parts[2];
                    $id = $parts[3];
                    $fileName = $parts[4];
                }
            }
            
            if ($year && $id && $fileName) {
                // بناء المسار الجديد
                $newPath = "assets/{$detectedCategory}/{$year}/{$id}/{$fileName}";
                
                // التحقق من أن المسار الجديد لا يوجد بالفعل
                if (!Storage::disk('public')->exists($newPath)) {
                    $oldPath = Storage::disk('public')->path($oldRelativePath);
                    $newFullPath = Storage::disk('public')->path($newPath);
                    
                    // إنشاء المجلد الجديد
                    Storage::disk('public')->makeDirectory(dirname($newPath));
                    
                    // نقل الملف الرئيسي
                    if (file_exists($oldPath)) {
                        $oldDir = dirname($oldPath);
                        $newDir = dirname($newFullPath);
                        
                        // نقل الملف الرئيسي
                        if (rename($oldPath, $newFullPath)) {
                            // نقل مجلدات captions و hls إذا كانت موجودة
                            $oldCaptionsDir = $oldDir . '/captions';
                            $newCaptionsDir = $newDir . '/captions';
                            if (is_dir($oldCaptionsDir)) {
                                if (!is_dir($newCaptionsDir)) {
                                    mkdir($newCaptionsDir, 0775, true);
                                }
                                shell_exec("cp -r " . escapeshellarg($oldCaptionsDir) . "/* " . escapeshellarg($newCaptionsDir) . "/ 2>/dev/null");
                            }
                            
                            $oldHlsDir = $oldDir . '/hls';
                            $newHlsDir = $newDir . '/hls';
                            if (is_dir($oldHlsDir)) {
                                if (!is_dir($newHlsDir)) {
                                    mkdir($newHlsDir, 0775, true);
                                }
                                shell_exec("cp -r " . escapeshellarg($oldHlsDir) . "/* " . escapeshellarg($newHlsDir) . "/ 2>/dev/null");
                            }
                            
                            // تعيين الصلاحيات
                            shell_exec("chmod -R 775 " . escapeshellarg($newDir) . " 2>/dev/null");
                            shell_exec("chown -R www-data:www-data " . escapeshellarg($newDir) . " 2>/dev/null");
                            
                            // تحديث relative_path
                            $asset->relative_path = $newPath;
                            $asset->save();
                            
                            Log::info('Updated category from DeepSeek analysis and moved file', [
                                'asset_id' => $asset->id,
                                'old_category' => $currentCategory,
                                'new_category' => $detectedCategory,
                                'old_path' => $oldRelativePath,
                                'new_path' => $newPath,
                            ]);
                        } else {
                            Log::error('Failed to move file for category update from analysis', [
                                'asset_id' => $asset->id,
                                'old_path' => $oldPath,
                                'new_path' => $newFullPath,
                            ]);
                        }
                    }
                } else {
                    Log::warning('New path already exists, skipping move', [
                        'asset_id' => $asset->id,
                        'new_path' => $newPath,
                    ]);
                }
            }
        } else {
            Log::info('Category from analysis not updated', [
                'asset_id' => $asset->id,
                'current_category' => $currentCategory,
                'detected_category' => $detectedCategory,
                'reason' => $currentCategory === $detectedCategory ? 'same_category' : 'not_in_storage',
            ]);
        }
    }

    private function updateCategoryFromTranscription(Asset $asset, $transcription)
    {
        // قائمة بالكلمات المفتاحية لكل تصنيف
        $categoryKeywords = [
            'ادعية' => ['دعاء', 'اللهم', 'رب', 'استغفار', 'توبة', 'دعوة', 'مناجاة', 'أدعية'],
            'مواعظ' => ['موعظة', 'عظة', 'نصيحة', 'تذكير', 'وعظ', 'إرشاد', 'نصائح'],
            'تفسير' => ['تفسير', 'آية', 'قرآن', 'سورة', 'تأويل', 'معنى', 'آيات'],
            'حديث' => ['حديث', 'رسول', 'صلى الله عليه', 'رواية', 'سنة', 'أحاديث'],
            'سيرة' => ['سيرة', 'حياة', 'قصة', 'تاريخ', 'أحداث', 'سير'],
            'فقه' => ['حكم', 'فقه', 'شرع', 'حلال', 'حرام', 'واجب', 'سنة', 'أحكام'],
            'عقيدة' => ['عقيدة', 'إيمان', 'توحيد', 'شرك', 'كفر', 'إسلام', 'عقائد'],
        ];
        
        // تحويل النص إلى أحرف صغيرة للبحث
        $transcriptionLower = mb_strtolower($transcription, 'UTF-8');
        
        // حساب عدد التطابقات لكل تصنيف
        $categoryScores = [];
        foreach ($categoryKeywords as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $count = mb_substr_count($transcriptionLower, mb_strtolower($keyword, 'UTF-8'));
                $score += $count;
            }
            if ($score > 0) {
                $categoryScores[$category] = $score;
            }
        }
        
        // إذا وجدنا تصنيفاً، نحدّث relative_path
        if (!empty($categoryScores)) {
            // أخذ التصنيف الأكثر تطابقاً
            arsort($categoryScores);
            $detectedCategory = array_key_first($categoryScores);
            $score = $categoryScores[$detectedCategory];
            
            // التحقق من التصنيف الحالي
            $currentCategory = $asset->category; // استخدام accessor
            
            // تحديث المسار فقط إذا:
            // 1. التصنيف المكتشف مختلف عن الحالي
            // 2. النتيجة قوية (2+ تطابقات على الأقل)
            // 3. الملف موجود في storage (assets/)
            if ($currentCategory !== $detectedCategory && $score >= 2 && strpos($asset->relative_path, 'assets/') === 0) {
                $parts = explode('/', $asset->relative_path);
                
                // حفظ المسار القديم قبل التعديل
                $oldRelativePath = $asset->relative_path;
                
                // الهيكل الحالي: assets/{year}/{id}/master.ext
                // الهيكل الجديد: assets/{category}/{year}/{id}/master.ext
                if (count($parts) >= 4 && is_numeric($parts[1])) {
                    // year موجود في الموضع 1
                    $year = $parts[1];
                    $id = $parts[2];
                    $fileName = $parts[3];
                    
                    // بناء المسار الجديد
                    $newPath = "assets/{$detectedCategory}/{$year}/{$id}/{$fileName}";
                    
                    // التحقق من أن المسار الجديد لا يوجد بالفعل
                    if (!Storage::disk('public')->exists($newPath)) {
                        $oldPath = Storage::disk('public')->path($oldRelativePath);
                        $newFullPath = Storage::disk('public')->path($newPath);
                        
                        // إنشاء المجلد الجديد
                        Storage::disk('public')->makeDirectory(dirname($newPath));
                        
                        // نقل الملف الرئيسي
                        if (file_exists($oldPath)) {
                            $oldDir = dirname($oldPath);
                            $newDir = dirname($newFullPath);
                            
                            // نقل الملف الرئيسي
                            if (rename($oldPath, $newFullPath)) {
                                // نقل مجلدات captions و hls إذا كانت موجودة
                                $oldCaptionsDir = $oldDir . '/captions';
                                $newCaptionsDir = $newDir . '/captions';
                                if (is_dir($oldCaptionsDir)) {
                                    if (!is_dir($newCaptionsDir)) {
                                        mkdir($newCaptionsDir, 0775, true);
                                    }
                                    shell_exec("cp -r " . escapeshellarg($oldCaptionsDir) . "/* " . escapeshellarg($newCaptionsDir) . "/ 2>/dev/null");
                                }
                                
                                $oldHlsDir = $oldDir . '/hls';
                                $newHlsDir = $newDir . '/hls';
                                if (is_dir($oldHlsDir)) {
                                    if (!is_dir($newHlsDir)) {
                                        mkdir($newHlsDir, 0775, true);
                                    }
                                    shell_exec("cp -r " . escapeshellarg($oldHlsDir) . "/* " . escapeshellarg($newHlsDir) . "/ 2>/dev/null");
                                }
                                
                                // تعيين الصلاحيات
                                shell_exec("chmod -R 775 " . escapeshellarg($newDir) . " 2>/dev/null");
                                shell_exec("chown -R www-data:www-data " . escapeshellarg($newDir) . " 2>/dev/null");
                                
                                // تحديث relative_path
                                $asset->relative_path = $newPath;
                                
                                Log::info('Updated category and moved file', [
                                    'asset_id' => $asset->id,
                                    'old_category' => $currentCategory,
                                    'new_category' => $detectedCategory,
                                    'old_path' => $oldRelativePath,
                                    'new_path' => $newPath,
                                    'confidence_score' => $score,
                                ]);
                            } else {
                                Log::error('Failed to move file for category update', [
                                    'asset_id' => $asset->id,
                                    'old_path' => $oldPath,
                                    'new_path' => $newFullPath,
                                ]);
                            }
                        }
                    } else {
                        Log::warning('New path already exists, skipping move', [
                            'asset_id' => $asset->id,
                            'new_path' => $newPath,
                        ]);
                    }
                }
            } else {
                Log::info('Category detected but not updating', [
                    'asset_id' => $asset->id,
                    'current_category' => $currentCategory,
                    'detected_category' => $detectedCategory,
                    'score' => $score,
                    'reason' => $currentCategory === $detectedCategory ? 'same_category' : ($score < 2 ? 'low_score' : 'not_in_storage'),
                ]);
            }
        }
    }

    private function formatDuration($seconds)
    {
        if (!$seconds) {
            return '0:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function scanFolder(Request $request)
    {
        $scanPath = storage_path('app/public/2025');
        
        if (!is_dir($scanPath)) {
            return redirect()->route('assets.index')
                ->with('error', 'المجلد غير موجود: ' . $scanPath);
        }

        $videoExtensions = ['mp4', 'mov', 'mkv', 'm4v', 'avi', 'webm'];
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        $processed = 0;

        try {
            // مسح المجلد بشكل متكرر
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($scanPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $files = [];
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    if (in_array($extension, $videoExtensions)) {
                        $files[] = $file->getPathname();
                    }
                }
            }

            $totalFiles = count($files);
            Log::info('Starting folder scan', ['path' => $scanPath, 'total_files' => $totalFiles]);

            foreach ($files as $filePath) {
                try {
                    $relativePath = str_replace(storage_path('app/public'), '', $filePath);
                    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
                    
                    // التأكد من أن المسار يبدأ بـ 2025/
                    if (strpos($relativePath, '2025/') !== 0) {
                        $relativePath = '2025/' . basename($filePath);
                    }

                    // التحقق من وجود الملف في قاعدة البيانات
                    $existingAsset = Asset::where('relative_path', $relativePath)->first();

                    // إذا كان الملف موجوداً، نتخطاه (عدم التكرار)
                    if ($existingAsset) {
                        continue;
                    }

                    $fileInfo = [
                        'file_name' => basename($filePath),
                        'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
                        'size_bytes' => filesize($filePath),
                        'modified_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                    ];

                    // استخراج معلومات الفيديو باستخدام ffprobe
                    $videoMeta = $this->extractVideoMetadata($filePath);
                    
                    // تحديد الاتجاه ونسبة العرض إلى الارتفاع
                    $orientation = null;
                    $aspectRatio = null;
                    $width = $videoMeta['width'] ?? null;
                    $height = $videoMeta['height'] ?? null;
                    
                    // التأكد من أن الأبعاد أرقام صحيحة
                    if ($width && $height && is_numeric($width) && is_numeric($height)) {
                        $width = (int) $width;
                        $height = (int) $height;
                        
                        if ($height > $width) {
                            $orientation = 'portrait';
                        } elseif ($width > $height) {
                            $orientation = 'landscape';
                        } else {
                            $orientation = 'square';
                        }

                        $ratio = $width / $height;
                        if (abs($ratio - (9/16)) < 0.05) {
                            $aspectRatio = '9:16';
                        } elseif (abs($ratio - (16/9)) < 0.05) {
                            $aspectRatio = '16:9';
                        } elseif (abs($ratio - 1) < 0.05) {
                            $aspectRatio = '1:1';
                        } else {
                            $aspectRatio = $width . ':' . $height;
                        }
                    } else {
                        // إذا لم يتم استخراج الأبعاد، نحاول طريقة بديلة
                        Log::warning('Video dimensions not extracted, trying alternative method', [
                            'file' => $filePath,
                            'width' => $width,
                            'height' => $height,
                        ]);
                    }

                    // استخراج اسم المتحدث من المسار
                    $speakerName = $this->extractSpeakerName($filePath, $relativePath);

                    // إنشاء السجل مع التأكد من حفظ الأبعاد
                    $asset = Asset::create([
                        'file_name' => $fileInfo['file_name'],
                        'relative_path' => $relativePath,
                        'original_path' => strlen($filePath) > 191 ? $relativePath : $filePath,
                        'extension' => $fileInfo['extension'],
                        'size_bytes' => $fileInfo['size_bytes'],
                        'modified_at' => $fileInfo['modified_at'],
                        'width' => $width, // استخدام المتغيرات المحلية المحسوبة
                        'height' => $height, // استخدام المتغيرات المحلية المحسوبة
                        'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                        'orientation' => $orientation,
                        'aspect_ratio' => $aspectRatio,
                        'speaker_name' => $speakerName,
                        'is_publishable' => false,
                    ]);
                    
                    // تسجيل الأبعاد المحفوظة للتأكد
                    Log::info('Asset created with dimensions', [
                        'asset_id' => $asset->id,
                        'file_name' => $fileInfo['file_name'],
                        'width' => $asset->width,
                        'height' => $asset->height,
                        'orientation' => $asset->orientation,
                    ]);

                    $inserted++;
                    $processed++;

                    if ($processed % 10 == 0) {
                        Log::info('Scan progress', ['processed' => $processed, 'total' => $totalFiles]);
                    }

                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Error processing file during scan', [
                        'file' => $filePath,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            $message = "تم الانتهاء من المسح: {$inserted} ملف جديد، {$errors} أخطاء";
            Log::info('Folder scan completed', [
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors,
                'total' => $processed,
            ]);

            return redirect()->route('assets.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Folder scan failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assets.index')
                ->with('error', 'فشل المسح: ' . $e->getMessage());
        }
    }

    public function updateFileMetadata(Request $request)
    {
        $request->validate([
            'original_path' => 'required|string',
        ]);

        $originalPath = $request->input('original_path');
        
        // البحث عن الملف باستخدام original_path
        $asset = Asset::where('original_path', $originalPath)->first();
        
        if (!$asset) {
            return redirect()->route('assets.index')
                ->with('error', 'الملف غير موجود في قاعدة البيانات: ' . $originalPath);
        }

        // التحقق من وجود الملف في النظام
        if (!file_exists($originalPath)) {
            return redirect()->route('assets.index')
                ->with('error', 'الملف غير موجود في المسار المحدد: ' . $originalPath);
        }

        try {
            // استخراج معلومات الفيديو
            $videoMeta = $this->extractVideoMetadata($originalPath);
            
            // تحديث معلومات الملف
            $fileInfo = [
                'size_bytes' => filesize($originalPath),
                'modified_at' => date('Y-m-d H:i:s', filemtime($originalPath)),
            ];

            // تحديد الاتجاه ونسبة العرض إلى الارتفاع
            $orientation = null;
            $aspectRatio = null;
            $width = $videoMeta['width'] ?? null;
            $height = $videoMeta['height'] ?? null;
            
            if ($width && $height && is_numeric($width) && is_numeric($height)) {
                $width = (int) $width;
                $height = (int) $height;
                
                if ($height > $width) {
                    $orientation = 'portrait';
                } elseif ($width > $height) {
                    $orientation = 'landscape';
                } else {
                    $orientation = 'square';
                }

                $ratio = $width / $height;
                if (abs($ratio - (9/16)) < 0.05) {
                    $aspectRatio = '9:16';
                } elseif (abs($ratio - (16/9)) < 0.05) {
                    $aspectRatio = '16:9';
                } elseif (abs($ratio - 1) < 0.05) {
                    $aspectRatio = '1:1';
                } else {
                    $aspectRatio = $width . ':' . $height;
                }
            }

            // تحديث البيانات
            $asset->update([
                'size_bytes' => $fileInfo['size_bytes'],
                'modified_at' => $fileInfo['modified_at'],
                'width' => $width,
                'height' => $height,
                'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                'orientation' => $orientation,
                'aspect_ratio' => $aspectRatio,
            ]);

            Log::info('File metadata updated', [
                'asset_id' => $asset->id,
                'original_path' => $originalPath,
                'width' => $width,
                'height' => $height,
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم تحديث بيانات الملف بنجاح');

        } catch (\Exception $e) {
            Log::error('Failed to update file metadata', [
                'asset_id' => $asset->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('assets.index')
                ->with('error', 'فشل تحديث بيانات الملف: ' . $e->getMessage());
        }
    }

    public function updateAllFilesMetadata(Request $request)
    {
        try {
            // جلب جميع الملفات التي لديها original_path
            $assets = Asset::whereNotNull('original_path')
                ->where('original_path', '!=', '')
                ->get();

            $totalFiles = $assets->count();
            $updated = 0;
            $errors = 0;
            $skipped = 0;

            Log::info('Starting bulk metadata update', ['total_files' => $totalFiles]);

            foreach ($assets as $asset) {
                try {
                    $originalPath = $asset->original_path;

                    // التحقق من وجود الملف
                    if (!file_exists($originalPath)) {
                        $skipped++;
                        Log::warning('File not found, skipping', [
                            'asset_id' => $asset->id,
                            'original_path' => $originalPath,
                        ]);
                        continue;
                    }

                    // استخراج معلومات الفيديو
                    $videoMeta = $this->extractVideoMetadata($originalPath);
                    
                    // تحديث معلومات الملف
                    $fileInfo = [
                        'size_bytes' => filesize($originalPath),
                        'modified_at' => date('Y-m-d H:i:s', filemtime($originalPath)),
                    ];

                    // تحديد الاتجاه ونسبة العرض إلى الارتفاع
                    $orientation = null;
                    $aspectRatio = null;
                    $width = $videoMeta['width'] ?? null;
                    $height = $videoMeta['height'] ?? null;
                    
                    if ($width && $height && is_numeric($width) && is_numeric($height)) {
                        $width = (int) $width;
                        $height = (int) $height;
                        
                        if ($height > $width) {
                            $orientation = 'portrait';
                        } elseif ($width > $height) {
                            $orientation = 'landscape';
                        } else {
                            $orientation = 'square';
                        }

                        $ratio = $width / $height;
                        if (abs($ratio - (9/16)) < 0.05) {
                            $aspectRatio = '9:16';
                        } elseif (abs($ratio - (16/9)) < 0.05) {
                            $aspectRatio = '16:9';
                        } elseif (abs($ratio - 1) < 0.05) {
                            $aspectRatio = '1:1';
                        } else {
                            $aspectRatio = $width . ':' . $height;
                        }
                    }

                    // تحديث البيانات
                    $asset->update([
                        'size_bytes' => $fileInfo['size_bytes'],
                        'modified_at' => $fileInfo['modified_at'],
                        'width' => $width,
                        'height' => $height,
                        'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                        'orientation' => $orientation,
                        'aspect_ratio' => $aspectRatio,
                    ]);

                    $updated++;

                    // تسجيل التقدم كل 10 ملفات
                    if ($updated % 10 == 0) {
                        Log::info('Bulk update progress', [
                            'updated' => $updated,
                            'total' => $totalFiles,
                        ]);
                    }

                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to update file metadata', [
                        'asset_id' => $asset->id,
                        'original_path' => $asset->original_path ?? 'N/A',
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            $message = "تم تحديث بيانات {$updated} ملف من أصل {$totalFiles}";
            if ($skipped > 0) {
                $message .= "، تم تخطي {$skipped} ملف (غير موجود)";
            }
            if ($errors > 0) {
                $message .= "، حدثت {$errors} أخطاء";
            }

            Log::info('Bulk metadata update completed', [
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => $totalFiles,
            ]);

            return redirect()->route('assets.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk metadata update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assets.index')
                ->with('error', 'فشل تحديث بيانات الملفات: ' . $e->getMessage());
        }
    }

    public function reExtractMetadata(Asset $asset)
    {
        try {
            // تحديد المسار المستخدم
            $filePath = null;
            
            // محاولة استخدام original_path أولاً
            if ($asset->original_path && file_exists($asset->original_path)) {
                $filePath = $asset->original_path;
            } 
            // إذا لم يكن موجوداً، جرب relative_path
            elseif ($asset->relative_path) {
                $relativePath = $asset->relative_path;
                // إذا كان المسار يبدأ بـ assets/ فهو في storage
                if (strpos($relativePath, 'assets/') === 0) {
                    $filePath = Storage::disk('public')->path($relativePath);
                } else {
                    $filePath = storage_path('app/public/' . $relativePath);
                }
            }

            if (!$filePath || !file_exists($filePath)) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'الملف غير موجود. يرجى التأكد من المسار الأصلي أو نقل الملف أولاً.');
            }

            // استخراج معلومات الفيديو
            $videoMeta = $this->extractVideoMetadata($filePath);
            
            // تحديث معلومات الملف
            $fileInfo = [
                'size_bytes' => filesize($filePath),
                'modified_at' => date('Y-m-d H:i:s', filemtime($filePath)),
            ];

            // تحديد الاتجاه ونسبة العرض إلى الارتفاع
            $orientation = null;
            $aspectRatio = null;
            $width = $videoMeta['width'] ?? null;
            $height = $videoMeta['height'] ?? null;
            
            if ($width && $height && is_numeric($width) && is_numeric($height)) {
                $width = (int) $width;
                $height = (int) $height;
                
                if ($height > $width) {
                    $orientation = 'portrait';
                } elseif ($width > $height) {
                    $orientation = 'landscape';
                } else {
                    $orientation = 'square';
                }

                $ratio = $width / $height;
                if (abs($ratio - (9/16)) < 0.05) {
                    $aspectRatio = '9:16';
                } elseif (abs($ratio - (16/9)) < 0.05) {
                    $aspectRatio = '16:9';
                } elseif (abs($ratio - 1) < 0.05) {
                    $aspectRatio = '1:1';
                } else {
                    $aspectRatio = $width . ':' . $height;
                }
            }

            // تحديث البيانات
            $asset->update([
                'size_bytes' => $fileInfo['size_bytes'],
                'modified_at' => $fileInfo['modified_at'],
                'width' => $width,
                'height' => $height,
                'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                'orientation' => $orientation,
                'aspect_ratio' => $aspectRatio,
            ]);

            Log::info('Metadata re-extracted', [
                'asset_id' => $asset->id,
                'file_path' => $filePath,
                'width' => $width,
                'height' => $height,
                'duration' => $videoMeta['duration_seconds'],
            ]);

            $message = 'تم إعادة استخراج بيانات الفيديو بنجاح';
            if ($width && $height) {
                $message .= " - الأبعاد: {$width}×{$height}";
            }
            if ($videoMeta['duration_seconds']) {
                $message .= " - المدة: " . $this->formatDuration($videoMeta['duration_seconds']);
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to re-extract metadata', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل إعادة استخراج البيانات: ' . $e->getMessage());
        }
    }

    public function updateSiteDescription(Asset $asset, Request $request)
    {
        $request->validate([
            'site_description' => 'nullable|string|max:1000',
        ]);

        try {
            $asset->site_description = $request->input('site_description');
            $asset->save();

            Log::info('Site description updated', [
                'asset_id' => $asset->id,
                'site_description' => $asset->site_description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ وصف الموقع بنجاح',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update site description', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ وصف الموقع: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateTranscription(Asset $asset, Request $request)
    {
        $request->validate([
            'transcription' => 'nullable|string',
        ]);

        try {
            $transcription = $request->input('transcription');
            
            // التأكد من أن النص ليس null
            if ($transcription === null) {
                $transcription = '';
            }
            
            $asset->transcription = $transcription;
            $asset->save();

            Log::info('Transcription updated', [
                'asset_id' => $asset->id,
                'transcription_length' => strlen($asset->transcription ?? ''),
                'transcription_preview' => mb_substr($asset->transcription ?? '', 0, 100),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ المحتوى النصي بنجاح',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update transcription', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ المحتوى النصي: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateTitle(Asset $asset, Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $asset->title = $request->input('title');
            $asset->save();

            Log::info('Title updated', [
                'asset_id' => $asset->id,
                'title' => $asset->title,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ العنوان بنجاح',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update title', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ العنوان: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateCategory(Asset $asset, Request $request)
    {
        $validCategories = ['آخر الليل', 'الذرية', 'طلبة العلم', 'الصحة والشفاء', 'الأنس بالله', 'الطفل'];
        
        try {
            // الحصول على البيانات من JSON body مباشرة (للتأكد من عدم الخلط مع route parameters)
            $jsonData = $request->json()->all();
            $category = $jsonData['category'] ?? null;
            
            // إذا لم تكن البيانات في JSON، جرب input
            if ($category === null) {
                $category = $request->input('category');
            }
            
            Log::info('Category update request received', [
                'asset_id' => $asset->id,
                'json_data' => $jsonData,
                'request_all' => $request->all(),
                'category_from_json' => $jsonData['category'] ?? 'not found',
                'category_from_input' => $request->input('category'),
                'category_final' => $category,
                'category_type' => gettype($category),
            ]);
            
            // تنظيف القيمة
            if ($category === '' || $category === null || $category === 'null') {
                $category = null;
            } else {
                $category = trim((string)$category);
            }
            
            // التحقق من أن التصنيف من القائمة المصرح بها (إذا كان موجوداً)
            if ($category && !in_array($category, $validCategories)) {
                Log::warning('Invalid category provided', [
                    'asset_id' => $asset->id,
                    'category' => $category,
                    'valid_categories' => $validCategories,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'التصنيف غير صحيح. يجب أن يكون واحداً من: ' . implode(', ', $validCategories),
                ], 400);
            }
            
            // حفظ القيمة القديمة للتسجيل
            $oldCategory = $asset->category;
            
            // تحديث التصنيف
            $asset->category = $category;
            $saved = $asset->save();

            if (!$saved) {
                throw new \Exception('فشل حفظ التصنيف في قاعدة البيانات');
            }

            // إعادة تحميل الـ model للتأكد من الحفظ
            $asset->refresh();

            Log::info('Category updated successfully', [
                'asset_id' => $asset->id,
                'old_category' => $oldCategory,
                'new_category' => $asset->category,
                'saved' => $saved,
                'category_from_db' => $asset->category,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التصنيف بنجاح',
                'category' => $asset->category,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update category', [
                'asset_id' => $asset->id,
                'json_data' => $request->json()->all(),
                'category_input' => $request->input('category'),
                'request_all' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ التصنيف: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateContentCategory(Asset $asset, Request $request)
    {
        $validCategories = ['آخر الليل', 'الذرية', 'طلبة العلم', 'الصحة والشفاء', 'الأنس بالله', 'الطفل'];
        
        try {
            // الحصول على البيانات من JSON body مباشرة
            $jsonData = $request->json()->all();
            $contentCategory = $jsonData['content_category'] ?? null;
            
            // إذا لم تكن البيانات في JSON، جرب input
            if ($contentCategory === null) {
                $contentCategory = $request->input('content_category');
            }
            
            Log::info('Content category update request received', [
                'asset_id' => $asset->id,
                'json_data' => $jsonData,
                'content_category_from_json' => $jsonData['content_category'] ?? 'not found',
                'content_category_from_input' => $request->input('content_category'),
                'content_category_final' => $contentCategory,
                'content_category_type' => gettype($contentCategory),
            ]);
            
            // تنظيف القيمة
            if ($contentCategory === '' || $contentCategory === null || $contentCategory === 'null') {
                $contentCategory = null;
            } else {
                $contentCategory = trim((string)$contentCategory);
            }
            
            // التحقق من أن التصنيف من القائمة المصرح بها (إذا كان موجوداً)
            if ($contentCategory && !in_array($contentCategory, $validCategories)) {
                Log::warning('Invalid content category provided', [
                    'asset_id' => $asset->id,
                    'content_category' => $contentCategory,
                    'valid_categories' => $validCategories,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'تصنيف المحتوى غير صحيح. يجب أن يكون واحداً من: ' . implode(', ', $validCategories),
                ], 400);
            }
            
            // حفظ القيمة القديمة للتسجيل
            $oldContentCategory = $asset->content_category;
            
            // تحديث تصنيف المحتوى
            $asset->content_category = $contentCategory;
            $saved = $asset->save();

            if (!$saved) {
                throw new \Exception('فشل حفظ تصنيف المحتوى في قاعدة البيانات');
            }

            // إعادة تحميل الـ model للتأكد من الحفظ
            $asset->refresh();

            Log::info('Content category updated successfully', [
                'asset_id' => $asset->id,
                'old_content_category' => $oldContentCategory,
                'new_content_category' => $asset->content_category,
                'saved' => $saved,
                'content_category_from_db' => $asset->content_category,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تصنيف المحتوى بنجاح',
                'content_category' => $asset->content_category,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update content category', [
                'asset_id' => $asset->id,
                'json_data' => $request->json()->all(),
                'content_category_input' => $request->input('content_category'),
                'request_all' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ تصنيف المحتوى: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function extractVideoMetadata($filePath)
    {
        $meta = [
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
        ];

        try {
            // استخدام ffprobe لاستخراج معلومات الفيديو
            $command = 'ffprobe -v error -select_streams v:0 -show_entries stream=width,height:format=duration -of json ' . escapeshellarg($filePath) . ' 2>&1';
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                $jsonOutput = json_decode(implode("\n", $output), true);
                
                if (isset($jsonOutput['streams'][0])) {
                    $stream = $jsonOutput['streams'][0];
                    // التأكد من أن الأبعاد موجودة وصحيحة
                    if (isset($stream['width']) && isset($stream['height'])) {
                        $meta['width'] = (int) $stream['width'];
                        $meta['height'] = (int) $stream['height'];
                    }
                }
                
                if (isset($jsonOutput['format']['duration'])) {
                    $meta['duration_seconds'] = (int) floatval($jsonOutput['format']['duration']);
                }
                
                // تسجيل الأبعاد المستخرجة للتأكد
                if ($meta['width'] && $meta['height']) {
                    Log::debug('Video metadata extracted successfully', [
                        'file' => basename($filePath),
                        'width' => $meta['width'],
                        'height' => $meta['height'],
                        'duration' => $meta['duration_seconds'],
                    ]);
                }
            } else {
                // تسجيل الخطأ إذا فشل ffprobe
                Log::warning('ffprobe failed to extract metadata', [
                    'file' => $filePath,
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract video metadata', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $meta;
    }

    private function extractSpeakerName($filePath, $relativePath)
    {
        $parts = explode('/', $relativePath);
        
        // محاولة استخراج اسم المتحدث من المسار
        if (count($parts) >= 4) {
            $potentialSpeaker = $parts[3];
            if ($potentialSpeaker && $potentialSpeaker !== basename($filePath)) {
                return $potentialSpeaker;
            }
        }

        // محاولة استخراج من اسم الملف
        $filenameWithoutExt = pathinfo(basename($filePath), PATHINFO_FILENAME);
        
        if (strpos($filenameWithoutExt, '_') !== false) {
            $parts = explode('_', $filenameWithoutExt);
            if (count($parts) > 1) {
                return $parts[0];
            }
        } elseif (strpos($filenameWithoutExt, '-') !== false) {
            $parts = explode('-', $filenameWithoutExt);
            if (count($parts) > 1) {
                return $parts[0];
            }
        }

        return null;
    }
}

