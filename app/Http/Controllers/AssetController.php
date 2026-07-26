<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AudioFile;
use App\Models\Category;
use App\Models\HlsVersion;
use App\Models\Playlist;
use App\Models\Scholar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /**
     * إرجاع أجزاء المسار المطبيع للعرض/التصفح (original_path أو relative_path).
     */
    private function getDisplayPathSegments(Asset $asset): array
    {
        $raw = $asset->original_path ?? $asset->relative_path;
        if ($raw === null || $raw === '') {
            return [];
        }
        $normalized = str_replace('\\', '/', trim($raw));
        $normalized = ltrim($normalized, '/');
        if ($normalized === '') {
            return [];
        }
        $parts = explode('/', $normalized);

        return array_values(array_filter($parts, function ($p) {
            return $p !== '';
        }));
    }

    /**
     * إرجاع مجلدات وملفات المستوى الحالي للتصفح بناءً على المسار النسبي على القرص (storage/app/public).
     * المجلدات والملفات تُقرأ من نظام الملفات؛ الملفات تُطابق مع قاعدة البيانات لعرض بيانات الـ Asset.
     *
     * @return array{folders: array<string>, file_assets: \Illuminate\Support\Collection, breadcrumb_segments: array<string>}
     */
    private function getBrowseData(string $pathPrefix): array
    {
        $storagePublic = storage_path('app/public');
        $videoExtensions = ['mp4', 'mov', 'mkv', 'm4v', 'avi', 'webm', 'mpg', 'mpeg', 'wmv', 'flv', '3gp'];

        $pathPrefix = str_replace('\\', '/', trim($pathPrefix));
        $pathPrefix = trim($pathPrefix, '/');
        $prefixSegments = $pathPrefix === '' ? [] : explode('/', $pathPrefix);
        $breadcrumbSegments = $prefixSegments;

        $folders = [];
        $fileAssets = collect();

        if ($pathPrefix !== '' && str_contains($pathPrefix, '..')) {
            return [
                'folders' => [],
                'file_assets' => $fileAssets,
                'breadcrumb_segments' => $breadcrumbSegments,
            ];
        }

        $fullPath = $pathPrefix === ''
            ? $storagePublic
            : $storagePublic.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pathPrefix);

        if (! is_dir($fullPath)) {
            return [
                'folders' => [],
                'file_assets' => $fileAssets,
                'breadcrumb_segments' => $breadcrumbSegments,
            ];
        }

        if ($pathPrefix === '') {
            // الجذر: عرض مجلدات المسح فقط (2025 و videos) حسب وجودها على القرص
            foreach (['2025', 'videos'] as $name) {
                $childPath = $storagePublic.DIRECTORY_SEPARATOR.$name;
                if (is_dir($childPath)) {
                    $folders[] = $name;
                }
            }
            sort($folders, SORT_STRING);

            return [
                'folders' => $folders,
                'file_assets' => $fileAssets,
                'breadcrumb_segments' => $breadcrumbSegments,
            ];
        }

        // قراءة المحتوى الفعلي من القرص (مجلدات + ملفات فيديو في هذا المستوى فقط)
        $entries = @scandir($fullPath);
        if ($entries === false) {
            return [
                'folders' => [],
                'file_assets' => $fileAssets,
                'breadcrumb_segments' => $breadcrumbSegments,
            ];
        }

        $pathPrefixWithSlash = $pathPrefix.'/';

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $childFull = $fullPath.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($childFull)) {
                $folders[] = $entry;

                continue;
            }
            if (! is_file($childFull)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (! in_array($ext, $videoExtensions)) {
                continue;
            }
            $relativePath = $pathPrefixWithSlash.$entry;
            $pathNorm = str_replace('\\', '/', trim($relativePath, '/'));
            $asset = Asset::where(function ($q) use ($pathNorm, $relativePath) {
                $q->where('relative_path', $relativePath)
                    ->orWhere('original_path', $relativePath)
                    ->orWhere('relative_path', $pathNorm)
                    ->orWhere('original_path', $pathNorm);
            })->first();
            if ($asset) {
                $fileAssets->push($asset);
            }
        }

        sort($folders, SORT_STRING);
        $fileAssets = $fileAssets->sortBy('file_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $fileAssets->each(function ($asset) {
            $pathToCheck = trim(str_replace('\\', '/', (string) ($asset->original_path ?? $asset->relative_path ?? '')), '/');
            $asset->file_missing = $pathToCheck === '' || ! Storage::disk('public')->exists($pathToCheck);
        });

        return [
            'folders' => $folders,
            'file_assets' => $fileAssets,
            'breadcrumb_segments' => $breadcrumbSegments,
        ];
    }

    /**
     * تطبيع مسار التصفح/الاستيراد ضمن storage/app/public (2025 أو videos فقط).
     */
    private function normalizeStorageBrowsePath(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = trim($path, '/');
        if (str_contains($path, '..')) {
            return null;
        }
        if ($path !== '' && ! str_starts_with($path, '2025') && ! str_starts_with($path, 'videos')) {
            return null;
        }

        return $path;
    }

    /**
     * مجلدات وملفات فيديو على القرص للاستيراد (كل الملفات، وليس فقط المسجلة في قاعدة البيانات).
     *
     * @return array{path_prefix: string, breadcrumb_segments: array<string>, folders: array<string>, files: array<int, array<string, mixed>>}
     */
    private function getImportBrowseData(string $pathPrefix): array
    {
        $storagePublic = storage_path('app/public');
        $videoExtensions = ['mp4', 'mov', 'mkv', 'm4v', 'avi', 'webm', 'mpg', 'mpeg', 'wmv', 'flv', '3gp'];
        $pathPrefix = $this->normalizeStorageBrowsePath($pathPrefix) ?? '';
        $breadcrumbSegments = $pathPrefix === '' ? [] : explode('/', $pathPrefix);
        $folders = [];
        $files = [];

        $fullPath = $pathPrefix === ''
            ? $storagePublic
            : $storagePublic.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $pathPrefix);

        if (! is_dir($fullPath)) {
            return [
                'path_prefix' => $pathPrefix,
                'breadcrumb_segments' => $breadcrumbSegments,
                'folders' => $folders,
                'files' => $files,
            ];
        }

        if ($pathPrefix === '') {
            foreach (['2025', 'videos'] as $name) {
                if (is_dir($storagePublic.DIRECTORY_SEPARATOR.$name)) {
                    $folders[] = $name;
                }
            }
            sort($folders, SORT_STRING);

            return [
                'path_prefix' => $pathPrefix,
                'breadcrumb_segments' => $breadcrumbSegments,
                'folders' => $folders,
                'files' => $files,
            ];
        }

        $entries = @scandir($fullPath);
        if ($entries === false) {
            return [
                'path_prefix' => $pathPrefix,
                'breadcrumb_segments' => $breadcrumbSegments,
                'folders' => $folders,
                'files' => $files,
            ];
        }

        $pathPrefixWithSlash = $pathPrefix.'/';

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $childFull = $fullPath.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($childFull)) {
                $folders[] = $entry;

                continue;
            }
            if (! is_file($childFull)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (! in_array($ext, $videoExtensions)) {
                continue;
            }
            $relativePath = $pathPrefixWithSlash.$entry;
            $pathNorm = str_replace('\\', '/', trim($relativePath, '/'));
            $asset = Asset::where(function ($q) use ($pathNorm) {
                $q->where('relative_path', $pathNorm)
                    ->orWhere('original_path', $pathNorm);
            })->first();
            $alreadyInSite = $asset
                && $asset->relative_path
                && str_starts_with((string) $asset->relative_path, 'assets/')
                && Storage::disk('public')->exists($asset->relative_path);

            $files[] = [
                'file_name' => $entry,
                'relative_path' => $pathNorm,
                'size_bytes' => filesize($childFull) ?: 0,
                'size_mb' => round((filesize($childFull) ?: 0) / (1024 * 1024), 2),
                'in_database' => (bool) $asset,
                'asset_id' => $asset?->id,
                'already_in_site' => $alreadyInSite,
                'asset_url' => $asset ? route('assets.show', $asset) : null,
            ];
        }

        sort($folders, SORT_STRING);
        usort($files, fn ($a, $b) => strnatcasecmp($a['file_name'], $b['file_name']));

        return [
            'path_prefix' => $pathPrefix,
            'breadcrumb_segments' => $breadcrumbSegments,
            'folders' => $folders,
            'files' => $files,
        ];
    }

    /**
     * إنشاء سجل Asset من ملف فيديو على القرص (نفس منطق المسح).
     */
    private function createAssetFromDiskFile(string $fullPath, string $pathNorm): Asset
    {
        $fileInfo = [
            'file_name' => basename($fullPath),
            'extension' => strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)),
            'size_bytes' => filesize($fullPath),
            'modified_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
        ];

        $videoMeta = $this->extractVideoMetadata($fullPath);
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
            if (abs($ratio - (9 / 16)) < 0.05) {
                $aspectRatio = '9:16';
            } elseif (abs($ratio - (16 / 9)) < 0.05) {
                $aspectRatio = '16:9';
            } elseif (abs($ratio - 1) < 0.05) {
                $aspectRatio = '1:1';
            } else {
                $aspectRatio = $width.':'.$height;
            }
        }

        return Asset::create([
            'file_name' => $fileInfo['file_name'],
            'relative_path' => $pathNorm,
            'original_path' => $pathNorm,
            'extension' => $fileInfo['extension'],
            'video_codec' => $videoMeta['video_codec'] ?? null,
            'size_bytes' => $fileInfo['size_bytes'],
            'modified_at' => $fileInfo['modified_at'],
            'width' => $width,
            'height' => $height,
            'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
            'orientation' => $orientation,
            'aspect_ratio' => $aspectRatio,
            'speaker_name' => null,
            'gregorian_year' => $this->extractGregorianYear($pathNorm),
            'is_publishable' => false,
        ]);
    }

    /**
     * JSON: تصفح مجلدات الاستيراد لاختيار فيديو.
     */
    public function importBrowse(Request $request)
    {
        $rawPath = (string) $request->get('path', '');
        if ($rawPath !== '' && $this->normalizeStorageBrowsePath($rawPath) === null) {
            return response()->json(['success' => false, 'error' => 'مسار غير صالح'], 422);
        }

        $data = $this->getImportBrowseData($rawPath);

        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * مجلد الرفع الافتراضي للفيديوهات القادمة من جهاز المستخدم.
     */
    private function defaultVideoUploadFolder(): string
    {
        $folder = 'videos/uploads';
        if (! Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        return $folder;
    }

    /**
     * رفع فيديو إلى مجلد videos أو 2025 (قبل التسجيل والنقل).
     * إن لم يُحدَّد مجلد صالح يُستخدم videos/uploads تلقائيًا.
     */
    public function uploadImportVideo(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'folder_path' => 'nullable|string|max:2000',
            'video' => 'required|file|mimes:mp4,mov,mkv,m4v,avi,webm,mpg,mpeg,wmv,flv,3gp|max:2097152',
        ]);

        $folderPath = $this->normalizeStorageBrowsePath($request->input('folder_path'));
        if ($folderPath === null || $folderPath === '' || in_array($folderPath, ['2025', 'videos'], true)) {
            $folderPath = $this->defaultVideoUploadFolder();
        }

        $file = $request->file('video');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'error' => 'ملف الرفع غير صالح'], 422);
        }

        $originalName = $file->getClientOriginalName();
        $safeName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '_', $originalName);
        $safeName = trim($safeName) !== '' ? trim($safeName) : 'video_'.time().'.mp4';
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($safeName, PATHINFO_EXTENSION) ?: 'mp4');
        if ($extension && ! str_ends_with(strtolower($safeName), '.'.$extension)) {
            $safeName .= '.'.$extension;
        }

        $relativePath = $folderPath.'/'.$safeName;
        if (Storage::disk('public')->exists($relativePath)) {
            $base = pathinfo($safeName, PATHINFO_FILENAME);
            $safeName = $base.'_'.date('Ymd_His').'.'.$extension;
            $relativePath = $folderPath.'/'.$safeName;
        }

        $stored = $file->storeAs($folderPath, $safeName, 'public');
        if (! $stored) {
            return response()->json(['success' => false, 'error' => 'فشل حفظ الملف على السيرفر'], 500);
        }

        $pathNorm = str_replace('\\', '/', trim($stored, '/'));

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح',
            'relative_path' => $pathNorm,
            'file_name' => $safeName,
            'folder_path' => $folderPath,
        ]);
    }

    /**
     * تسجيل فيديو (أو عدة فيديوهات) في قاعدة البيانات ونقله إلى assets/{id}/master.{ext}.
     */
    public function importFromPath(Request $request)
    {
        set_time_limit(0);

        if ($request->has('source_paths')) {
            return $this->importMultipleFromPaths($request);
        }

        $request->validate([
            'source_path' => 'required|string|max:2000',
        ]);

        $result = $this->importSingleVideoFromPath($request, $request->input('source_path'));
        $status = $result['http_status'] ?? ($result['success'] ? 200 : 500);

        return response()->json($result, $status);
    }

    /**
     * تسجيل ونقل عدة فيديوهات دفعة واحدة.
     */
    private function importMultipleFromPaths(Request $request)
    {
        $request->validate([
            'source_paths' => 'required|array|min:1|max:50',
            'source_paths.*' => 'string|max:2000',
        ]);

        $paths = array_values(array_unique(array_filter($request->input('source_paths', []))));
        $results = [];
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($paths as $sourcePath) {
            $result = $this->importSingleVideoFromPath($request, $sourcePath);
            $entry = [
                'source_path' => $sourcePath,
                'success' => (bool) ($result['success'] ?? false),
                'message' => $result['message'] ?? ($result['error'] ?? ''),
                'asset_id' => $result['asset_id'] ?? null,
                'asset_url' => $result['asset_url'] ?? null,
                'already_imported' => (bool) ($result['already_imported'] ?? false),
                'error' => $result['error'] ?? null,
            ];
            $results[] = $entry;

            if ($entry['success']) {
                if ($entry['already_imported']) {
                    $skipped++;
                } else {
                    $imported++;
                }
            } else {
                $failed++;
            }
        }

        $total = count($results);
        $message = "اكتملت المعالجة: نجح {$imported}";
        if ($skipped > 0) {
            $message .= " · موجود مسبقاً {$skipped}";
        }
        if ($failed > 0) {
            $message .= " · فشل {$failed}";
        }
        $message .= " من {$total}";

        return response()->json([
            'success' => $failed === 0,
            'message' => $message,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'total' => $total,
            'results' => $results,
        ], ($failed > 0 && $imported === 0 && $skipped === 0) ? 422 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function importSingleVideoFromPath(Request $request, string $sourcePathInput): array
    {
        $sourcePath = $this->normalizeStorageBrowsePath($sourcePathInput);
        if ($sourcePath === null || $sourcePath === '') {
            return ['success' => false, 'error' => 'مسار الملف غير صالح', 'http_status' => 422];
        }

        if (! str_contains($sourcePath, '/') || in_array(basename($sourcePath), ['2025', 'videos'], true)) {
            return ['success' => false, 'error' => 'يرجى اختيار ملف فيديو وليس مجلداً', 'http_status' => 422];
        }

        $fullPath = storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $sourcePath));
        if (! is_file($fullPath)) {
            return ['success' => false, 'error' => 'الملف غير موجود على القرص', 'http_status' => 404];
        }

        $pathNorm = str_replace('\\', '/', trim($sourcePath, '/'));

        $asset = Asset::where(function ($q) use ($pathNorm) {
            $q->where('relative_path', $pathNorm)
                ->orWhere('original_path', $pathNorm);
        })->first();

        if ($asset) {
            if ($asset->relative_path
                && str_starts_with((string) $asset->relative_path, 'assets/')
                && Storage::disk('public')->exists($asset->relative_path)) {
                return [
                    'success' => true,
                    'message' => 'الفيديو مسجل ومنقول إلى الموقع مسبقاً',
                    'asset_id' => $asset->id,
                    'asset_url' => route('assets.show', $asset),
                    'already_imported' => true,
                ];
            }
        } else {
            try {
                $asset = $this->createAssetFromDiskFile($fullPath, $pathNorm);
            } catch (\Throwable $e) {
                Log::error('importFromPath create failed', ['path' => $pathNorm, 'error' => $e->getMessage()]);

                return ['success' => false, 'error' => 'فشل تسجيل الفيديو: '.$e->getMessage(), 'http_status' => 500];
            }
        }

        $moveRequest = Request::create(
            '/assets/'.$asset->id.'/move',
            'POST',
            [],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']
        );
        $moveRequest->headers->set('X-CSRF-TOKEN', (string) ($request->header('X-CSRF-TOKEN') ?: $request->input('_token')));

        $moveResponse = $this->moveFile($moveRequest, $asset);
        $moveData = $moveResponse->getData(true);

        if (! ($moveData['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $moveData['error'] ?? 'فشل نقل الفيديو إلى الموقع',
                'asset_id' => $asset->id,
                'asset_url' => route('assets.show', $asset),
                'http_status' => $moveResponse->getStatusCode() >= 400 ? $moveResponse->getStatusCode() : 500,
            ];
        }

        $asset->refresh();

        return [
            'success' => true,
            'message' => $moveData['message'] ?? 'تم تسجيل الفيديو ونقله إلى الموقع بنجاح',
            'asset_id' => $asset->id,
            'asset_url' => route('assets.show', $asset),
        ];
    }

    public function index(Request $request)
    {
        if ($request->get('view') === 'browse') {
            $pathPrefix = (string) $request->get('path', '');
            $browse = $this->getBrowseData($pathPrefix);
            foreach ($browse['file_assets'] ?? [] as $asset) {
                $this->loadTranslationSegmentsFromFiles($asset);
            }
            $stats = [
                'total' => Asset::count(),
                'videos' => Asset::whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v'])->count(),
                'portrait' => Asset::where('orientation', 'portrait')->count(),
                'landscape' => Asset::where('orientation', 'landscape')->count(),
                'square' => Asset::where('orientation', 'square')->count(),
                'portrait_duration' => $this->formatDurationForStats((int) Asset::where('orientation', 'portrait')->sum('duration_seconds')),
                'landscape_duration' => $this->formatDurationForStats((int) Asset::where('orientation', 'landscape')->sum('duration_seconds')),
                'square_duration' => $this->formatDurationForStats((int) Asset::where('orientation', 'square')->sum('duration_seconds')),
                'total_duration' => $this->formatDurationForStats((int) Asset::sum('duration_seconds')),
                'total_size_mb' => round(Asset::sum('size_bytes') / (1024 * 1024), 2),
            ];

            return view('assets.index', array_merge($browse, [
                'browse_mode' => true,
                'path_prefix' => $pathPrefix,
                'assets' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'stats' => $stats,
                'extensions' => collect(),
                'years' => collect(),
                'gregorianYears' => collect(),
                'categories' => collect(),
                'playlists' => Playlist::orderBy('title')->get(['id', 'title']),
                ...$this->playlistPickerViewData(),
                'scholars' => Scholar::orderBy('order')->orderBy('name')->get(['id', 'name']),
                'contentCategories' => Category::orderBy('name')->get(['id', 'name', 'image_path']),
                'uncategorizedCount' => 0,
                'noGregorianYearCount' => 0,
                'noImageCount' => 0,
                'landscapeWithImageCount' => 0,
                'portraitWithImageCount' => 0,
                'noScholarCount' => 0,
                'speakerNames' => collect(),
                'translationLanguages' => self::TRANSLATION_LANGUAGES,
            ]));
        }

        $preparingMode = $request->boolean('preparing');

        $query = Asset::query();

        // فلترة حسب المجلد (عرض القائمة لهذا المجلد فقط) — نفس منطق التصفح بالمجلدات: استخدام original_path ?? relative_path
        if ($request->filled('folder')) {
            $folder = trim(str_replace('\\', '/', (string) $request->get('folder')), '/');
            if ($folder !== '' && ! str_contains($folder, '..')) {
                $folderLike = $folder.'/%';
                $query->where(function ($q) use ($folder, $folderLike) {
                    $q->whereRaw('COALESCE(original_path, relative_path) = ?', [$folder])
                        ->orWhereRaw('COALESCE(original_path, relative_path) LIKE ?', [$folderLike]);
                });
            }
        }

        // البحث (العنوان، اسم الملف، المسار، اسم المتحدث، اسم الشيخ)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('relative_path', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('speaker_name', 'like', "%{$search}%")
                    ->orWhereHas('scholar', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // بحث منفصل باسم الشيخ (اسم المتحدث/اسم الشيخ المرتبط)
        if ($request->filled('scholar_search')) {
            $scholarSearch = trim((string) $request->scholar_search);
            $query->where(function ($q) use ($scholarSearch) {
                $q->where('speaker_name', 'like', "%{$scholarSearch}%")
                    ->orWhereHas('scholar', function ($s) use ($scholarSearch) {
                        $s->where('name', 'like', "%{$scholarSearch}%");
                    });
            });
        }

        // فلترة حسب الشيوخ (دعم اختيارات متعددة + بدون شيخ)
        $noScholarCount = (clone $query)->whereNull('scholar_id')->count();
        if ($request->filled('scholar_ids')) {
            $rawScholarIds = is_array($request->scholar_ids) ? $request->scholar_ids : [$request->scholar_ids];
            $includeNoScholar = in_array('none', array_map('strval', $rawScholarIds), true);
            $scholarIds = array_filter(array_map('intval', $rawScholarIds));

            if ($includeNoScholar && ! empty($scholarIds)) {
                $query->where(function ($q) use ($scholarIds) {
                    $q->whereNull('scholar_id')
                        ->orWhereIn('scholar_id', $scholarIds);
                });
            } elseif ($includeNoScholar) {
                $query->whereNull('scholar_id');
            } elseif (! empty($scholarIds)) {
                $query->whereIn('scholar_id', $scholarIds);
            }
        } elseif ($request->filled('scholar_id')) {
            // دعم القديم للتوافق
            $query->where('scholar_id', (int) $request->scholar_id);
        }

        // فلترة حسب الاتجاه
        if ($request->has('orientation') && $request->orientation) {
            $query->where('orientation', $request->orientation);
        }

        // فلترة حسب السنة الميلادية
        $noGregorianYearCount = (clone $query)->where(function ($q) {
            $q->whereNull('gregorian_year')->orWhere('gregorian_year', '');
        })->count();
        if ($request->filled('gregorian_year')) {
            if ($request->gregorian_year === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('gregorian_year')->orWhere('gregorian_year', '');
                });
            } else {
                $query->where('gregorian_year', $request->gregorian_year);
            }
        }

        // فلترة حسب تصنيفات المحتوى (many-to-many - دعم اختيارات متعددة + بدون تصنيف)
        $uncategorizedCount = (clone $query)->whereDoesntHave('categories')->count();
        if ($request->filled('content_categories')) {
            $rawCategories = is_array($request->content_categories)
                ? $request->content_categories
                : [$request->content_categories];
            $includeUncategorized = in_array('none', array_map('strval', $rawCategories), true);
            $categoryIds = array_filter(array_map('intval', $rawCategories));

            if ($includeUncategorized && ! empty($categoryIds)) {
                $query->where(function ($q) use ($categoryIds) {
                    $q->whereDoesntHave('categories')
                        ->orWhereHas('categories', function ($cq) use ($categoryIds) {
                            $cq->whereIn('categories.id', $categoryIds);
                        });
                });
            } elseif ($includeUncategorized) {
                $query->whereDoesntHave('categories');
            } elseif (! empty($categoryIds)) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        } elseif ($request->filled('content_category')) {
            // دعم القديم للتوافق
            $categoryId = (int) $request->content_category;
            if ($categoryId > 0) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('categories.id', $categoryId);
                });
            }
        }

        // فلترة حسب حالة النشر: الكل | منشور | غير منشور
        $publishStatus = $preparingMode ? 'unpublished' : $request->get('publish_status', 'all');
        if ($publishStatus === 'published') {
            $query->where('is_publishable', true);
        } elseif ($publishStatus === 'unpublished') {
            $query->where(function ($q) {
                $q->where('is_publishable', false)->orWhereNull('is_publishable');
            });
        }
        // دعم القديم للتوافق
        if ($publishStatus === 'all' && $request->has('is_publishable') && $request->is_publishable == 1) {
            $query->where('is_publishable', true);
        }

        // فلترة حسب قائمة التشغيل
        if ($request->filled('playlist')) {
            $playlistId = (int) $request->playlist;
            if ($playlistId > 0) {
                $query->whereHas('playlists', function ($q) use ($playlistId) {
                    $q->where('playlists.id', $playlistId);
                });
            }
        }

        // فلترة حسب الصور (غلاف/مصغّر)
        $noImageCount = (clone $query)->where(function ($q) {
            $this->applyAssetNoImageConstraint($q);
        })->count();
        $landscapeWithImageCount = (clone $query)->where('orientation', 'landscape')->where(function ($q) {
            $this->applyAssetHasImageConstraint($q);
        })->count();
        $portraitWithImageCount = (clone $query)->where('orientation', 'portrait')->where(function ($q) {
            $this->applyAssetHasImageConstraint($q);
        })->count();
        if ($request->filled('image_filter')) {
            switch ($request->image_filter) {
                case 'none':
                    $query->where(function ($q) {
                        $this->applyAssetNoImageConstraint($q);
                    });
                    break;
                case 'landscape':
                    $query->where('orientation', 'landscape')->where(function ($q) {
                        $this->applyAssetHasImageConstraint($q);
                    });
                    break;
                case 'portrait':
                    $query->where('orientation', 'portrait')->where(function ($q) {
                        $this->applyAssetHasImageConstraint($q);
                    });
                    break;
            }
        }

        // فلترة بالملفات التي بها مشكلة في المسار (الملف غير موجود على القرص حسب المسار النسبي/الأصلي)
        if ($request->filled('path_issues') && (int) $request->get('path_issues') === 1) {
            $query->where('file_missing', true);
        }

        // فيديوهات غير متوافقة مع متصفحات كثيرة (HEVC/...) بدون نسخة ويب محسّنة
        if ($request->filled('web_compat') && (string) $request->get('web_compat') === 'problem') {
            $badCodecs = Asset::incompatibleWebVideoCodecs();
            $query->whereIn('extension', Asset::VIDEO_EXTENSIONS)
                ->whereIn('video_codec', $badCodecs)
                ->where(function ($q) {
                    $q->whereNull('web_video_relative_path')
                        ->orWhereColumn('web_video_relative_path', 'relative_path')
                        ->orWhere('web_video_relative_path', '');
                })
                ->whereDoesntHave('optimizedVersions');
        }

        // الترتيب (عمود مسموح فقط) — الافتراضي: حسب العنوان تصاعدياً
        $allowedSortColumns = ['id', 'title', 'file_name', 'duration_seconds', 'relative_path', 'is_publishable'];
        $sortBy = $request->get('sort_by', 'title');
        if (! in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'title';
        }
        $defaultSortDir = $sortBy === 'id' ? 'desc' : 'asc';
        $sortDir = strtolower($request->get('sort_dir', $defaultSortDir)) === 'asc' ? 'asc' : 'desc';

        // إنشاء نسخة من الـ query لحساب الإحصائيات قبل pagination
        $statsQuery = clone $query;

        $query->orderBy($sortBy, $sortDir);
        $query->with(['categories:id,name', 'optimizedVersions:id,asset_id,relative_path']);
        $assets = $query->paginate(100);

        // دمج ترجمات المحتوى النصي من الملفات مع DB (حتى تتطابق حالة الترجمة مع صفحة العرض)
        foreach ($assets as $asset) {
            $this->loadTranslationSegmentsFromFiles($asset);
        }

        // إحصائيات من الـ query المفلتر (بعد تطبيق جميع الفلاتر والبحث)
        $filteredTotal = $statsQuery->count();
        $filteredVideos = (clone $statsQuery)->whereIn('extension', ['mp4', 'mov', 'mkv', 'm4v'])->count();
        $filteredTotalSeconds = (int) $statsQuery->sum('duration_seconds');
        $filteredTotalSize = round($statsQuery->sum('size_bytes') / (1024 * 1024), 2);

        // إحصائيات الاتجاهات من الـ query المفلتر
        $portraitCount = (clone $statsQuery)->where('orientation', 'portrait')->count();
        $landscapeCount = (clone $statsQuery)->where('orientation', 'landscape')->count();
        $squareCount = (clone $statsQuery)->where('orientation', 'square')->count();
        $portraitSeconds = (int) (clone $statsQuery)->where('orientation', 'portrait')->sum('duration_seconds');
        $landscapeSeconds = (int) (clone $statsQuery)->where('orientation', 'landscape')->sum('duration_seconds');
        $squareSeconds = (int) (clone $statsQuery)->where('orientation', 'square')->sum('duration_seconds');

        // عدد الملفات التي بها مشكلة في المسار (للعرض في الفلتر)
        $pathIssuesCount = Asset::where('file_missing', true)->count();

        $webCompatProblemsCount = Asset::query()
            ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
            ->whereIn('video_codec', Asset::incompatibleWebVideoCodecs())
            ->where(function ($q) {
                $q->whereNull('web_video_relative_path')
                    ->orWhereColumn('web_video_relative_path', 'relative_path')
                    ->orWhere('web_video_relative_path', '');
            })
            ->whereDoesntHave('optimizedVersions')
            ->count();

        $webCompatUnknownCount = Asset::query()
            ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
            ->where(function ($q) {
                $q->whereNull('video_codec')->orWhere('video_codec', '');
            })
            ->count();

        $stats = [
            'total' => $filteredTotal,
            'videos' => $filteredVideos,
            'portrait' => $portraitCount,
            'landscape' => $landscapeCount,
            'square' => $squareCount,
            'portrait_duration' => $this->formatDurationForStats($portraitSeconds),
            'landscape_duration' => $this->formatDurationForStats($landscapeSeconds),
            'square_duration' => $this->formatDurationForStats($squareSeconds),
            'total_duration' => $this->formatDurationForStats($filteredTotalSeconds),
            'total_size_mb' => $filteredTotalSize,
            'path_issues_count' => $pathIssuesCount,
            'web_compat_problems_count' => $webCompatProblemsCount,
            'web_compat_unknown_count' => $webCompatUnknownCount,
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
            ->map(function ($asset) {
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

        // السنوات الميلادية المتاحة (من عمود gregorian_year)
        $gregorianYears = Asset::select('gregorian_year')
            ->whereNotNull('gregorian_year')
            ->where('gregorian_year', '!=', '')
            ->distinct()
            ->orderBy('gregorian_year')
            ->pluck('gregorian_year')
            ->values();

        // التصنيفات المتاحة (استخراج من relative_path)
        $categories = Asset::select('relative_path')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function ($asset) {
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

        // قوائم التشغيل (للفلتر)
        $playlists = Playlist::orderBy('title')->get(['id', 'title']);

        // الشيوخ وتصنيفات المحتوى (لنافذة تغيير الإعدادات العامة)
        $scholars = Scholar::orderBy('order')->orderBy('name')->get(['id', 'name']);
        $contentCategories = Category::orderBy('name')->get(['id', 'name', 'image_path']);

        // أسماء المتحدثين المتاحة (استخراج من relative_path و file_name)
        $speakerNames = Asset::select('relative_path', 'file_name')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function ($asset) {
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

        $unpublishedCount = Asset::where(function ($q) {
            $q->where('is_publishable', false)->orWhereNull('is_publishable');
        })->count();

        return view('assets.index', array_merge(compact('assets', 'stats', 'extensions', 'years', 'gregorianYears', 'categories', 'playlists', 'scholars', 'contentCategories', 'speakerNames', 'unpublishedCount', 'uncategorizedCount', 'noGregorianYearCount', 'noImageCount', 'landscapeWithImageCount', 'portraitWithImageCount', 'noScholarCount'), $this->playlistPickerViewData(), [
            'browse_mode' => false,
            'preparing_mode' => $preparingMode,
            'path_prefix' => '',
            'folders' => [],
            'file_assets' => collect(),
            'breadcrumb_segments' => [],
            'folder_filter' => $request->get('folder'),
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'translationLanguages' => self::TRANSLATION_LANGUAGES,
        ]));
    }

    public function show(Asset $asset)
    {
        // استخدام select فقط للحقول المطلوبة
        $asset->load(['hlsVersions' => function ($query) {
            $query->select('id', 'asset_id', 'resolution', 'width', 'height', 'bitrate', 'audio_bitrate', 'playlist_path', 'master_playlist_path', 'total_size_bytes', 'segment_count');
        }, 'optimizedVersions', 'audioFiles' => function ($query) {
            $query->select('id', 'asset_id', 'format', 'bitrate', 'sample_rate', 'channels', 'file_path', 'file_size_bytes', 'duration_seconds');
        }, 'categories:id,name', 'playlists:id,title,image_path,parent_id', 'scholar:id,name']);

        $rootPlaylists = Playlist::with(['children' => function ($query) {
            $query->with(['children' => function ($childQuery) {
                $childQuery->orderBy('sort_order')->orderBy('title')->orderBy('id');
            }])->orderBy('sort_order')->orderBy('title')->orderBy('id');
        }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        $playlistTree = $rootPlaylists->map(function ($playlist) {
            return $this->playlistToTreeArray($playlist);
        })->values();

        // قراءة ملف JSON للـ transcription segments إذا كان موجوداً (مع cache)
        $transcriptionSegments = null;
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            $cacheKey = "transcription_segments_{$asset->id}";
            $transcriptionSegments = Cache::remember($cacheKey, 3600, function () use ($asset) {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir.'/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath = storage_path('app/public/'.$captionDir.'/'.$baseName.'.json');

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

        $scholars = \App\Models\Scholar::orderBy('order')->orderBy('name')->get();
        $translationLanguages = self::TRANSLATION_LANGUAGES;

        $asset->refresh();
        $this->loadTranslationSegmentsFromFiles($asset);

        if (! $asset->width || ! $asset->height) {
            $this->syncVideoMetadataFromFile($asset);
            $asset->refresh();
        }

        return view('assets.show', compact('asset', 'transcriptionSegments', 'scholars', 'translationLanguages', 'rootPlaylists', 'playlistTree'));
    }

    private function playlistToTreeArray(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'title' => $playlist->title,
            'image_path' => $playlist->image_path,
            'parent_id' => $playlist->parent_id,
            'children' => $playlist->children->map(fn ($child) => $this->playlistToTreeArray($child))->values()->all(),
        ];
    }

    private function playlistPickerViewData(): array
    {
        $rootPlaylists = Playlist::with(['children' => function ($query) {
            $query->with(['children' => function ($childQuery) {
                $childQuery->orderBy('sort_order')->orderBy('title')->orderBy('id');
            }])->orderBy('sort_order')->orderBy('title')->orderBy('id');
        }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        $playlistTree = $rootPlaylists->map(fn ($playlist) => $this->playlistToTreeArray($playlist))->values();

        return compact('rootPlaylists', 'playlistTree');
    }

    public function updateSpeaker(Request $request, Asset $asset)
    {
        $request->validate([
            'scholar_id' => 'nullable|exists:scholars,id',
        ]);

        $scholarId = $request->scholar_id ?: null;
        $asset->scholar_id = $scholarId;
        if ($scholarId) {
            $scholar = \App\Models\Scholar::find($scholarId);
            $asset->speaker_name = $scholar ? $scholar->name : null;
        } else {
            $asset->speaker_name = null;
        }
        $asset->save();

        \Illuminate\Support\Facades\Cache::forget('home_speaker_names');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'speaker_name' => $asset->scholar?->name ?? $asset->speaker_name,
                'scholar_id' => $asset->scholar_id,
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث اسم المتحدث بنجاح');
    }

    public function showPublic(Asset $asset)
    {
        // التحقق من أن الملف منقول إلى الموقع وقابل للنشر
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }

        // التحقق من أن الفيديو قابل للنشر
        if (! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح للعامة');
        }

        if ($asset->isAudio() && request()->routeIs('assets.show.public')) {
            return redirect()->route('audio.show', $asset, 302);
        }

        // استخدام select فقط للحقول المطلوبة + النسخ المحسّنة لتحديد الفيديو المعروض على الويب
        $asset->load(['hlsVersions' => function ($query) {
            $query->select('id', 'asset_id', 'resolution', 'width', 'height', 'bitrate', 'audio_bitrate', 'playlist_path', 'master_playlist_path', 'total_size_bytes', 'segment_count');
        }, 'optimizedVersions', 'categories:id,name', 'playlists' => function ($query) {
            $query->select('playlists.id', 'playlists.title', 'playlists.slug', 'playlists.parent_id', 'playlists.image_path', 'playlists.is_visible')
                ->withPivot('order')
                ->orderByPivot('order', 'asc');
        }]);
        $asset->loadCount(['likes', 'favorites']);
        $effectiveVideoPath = $this->getWebVideoPath($asset);
        $programPlaylist = $asset->primaryProgramPlaylist();
        $playlistContext = $this->resolvePlaylistContextForAsset($asset);

        // قراءة ملف JSON للـ transcription segments إذا كان موجوداً (مع cache)
        $transcriptionSegments = null;
        $cacheKey = "transcription_segments_{$asset->id}";
        $transcriptionSegments = Cache::remember($cacheKey, 3600, function () use ($asset) {
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir.'/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath = storage_path('app/public/'.$captionDir.'/'.$baseName.'.json');

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

        // جلب حالة Like و Favorite للمستخدم المسجل
        $userLiked = false;
        $userFavorited = false;
        if (auth()->check()) {
            $userLiked = \App\Models\Like::where('user_id', auth()->id())
                ->where('asset_id', $asset->id)
                ->exists();
            $userFavorited = \App\Models\Favorite::where('user_id', auth()->id())
                ->where('asset_id', $asset->id)
                ->exists();
        }

        // جلب فيديوهات مقترحة (حد أقصى ٨)
        $relatedAssetsCacheKey = "related_assets_v2_{$asset->id}";
        $relatedAssets = Cache::remember($relatedAssetsCacheKey, 1800, function () use ($asset) {
            $related = Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->where('id', '!=', $asset->id)
                ->where(function ($query) use ($asset) {
                    if ($asset->speaker_name) {
                        $query->where('speaker_name', $asset->speaker_name);
                    }
                    // البحث عن فيديوهات بنفس التصنيفات
                    if ($asset->categories && $asset->categories->count() > 0) {
                        $categoryIds = $asset->categories->pluck('id')->toArray();
                        $query->orWhereHas('categories', function ($q) use ($categoryIds) {
                            $q->whereIn('categories.id', $categoryIds);
                        });
                    }
                })
                ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'orientation', 'extension', 'duration_seconds', 'speaker_name', 'title')
                ->with('categories:id,name')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();

            // إذا لم يكن هناك فيديوهات مقترحة، نجلب فيديوهات عشوائية (قابلة للنشر فقط)
            if ($related->count() < 5) {
                $randomAssets = Asset::where('relative_path', 'like', 'assets/%')
                    ->where('is_publishable', true)
                    ->where('id', '!=', $asset->id)
                    ->select('id', 'file_name', 'relative_path', 'thumbnail_path', 'cover_path', 'orientation', 'extension', 'duration_seconds', 'speaker_name', 'title')
                    ->with('categories:id,name')
                    ->inRandomOrder()
                    ->limit(8 - $related->count())
                    ->get();
                $related = $related->merge($randomAssets);
            }

            return $related->take(8)->values();
        });

        // تصنيفات المحتوى المتاحة (مع cache)
        $contentCategories = Cache::remember('home_content_categories', 3600, function () {
            $validCategories = ['آخر الليل', 'الذرية', 'طلبة العلم', 'الصحة والشفاء', 'الأنس بالله', 'الطفل'];

            $availableCategories = Asset::where('relative_path', 'like', 'assets/%')
                ->where('is_publishable', true)
                ->whereNotNull('content_category')
                ->whereIn('content_category', $validCategories)
                ->distinct()
                ->pluck('content_category')
                ->filter()
                ->values()
                ->toArray();

            $orderedCategories = [];
            foreach ($validCategories as $category) {
                foreach ($availableCategories as $availableCategory) {
                    if ($category === $availableCategory) {
                        $orderedCategories[] = $category;
                        break;
                    }
                }
            }

            return collect($orderedCategories);
        });

        // القائمة الجانبية نفس الصفحة الرئيسية (تصنيفات من جدول categories مع show_on_site)
        $categories = Cache::remember('home_categories', 3600, function () {
            return Category::where('show_on_site', true)
                ->withCount(['assets' => function ($q) {
                    $q->where('relative_path', 'like', 'assets/%')
                        ->where('is_publishable', true);
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });

        // بنرات صفحة تفاصيل الفيديو (مع cache)
        $banners = Cache::remember('banners_video_detail', 3600, function () {
            return \App\Models\Banner::active()
                ->forPlacement(\App\Models\Banner::PLACEMENT_VIDEO_DETAIL)
                ->orderBy('order')
                ->orderBy('id')
                ->get();
        });

        $translationLanguages = \App\Http\Controllers\AssetController::TRANSLATION_LANGUAGES;

        if (request()->routeIs('audio.show')) {
            $asset->load('audioFiles');
        }
        $playback = $this->resolvePublicPlaybackContext($asset, $effectiveVideoPath);
        $this->loadTranslationSegmentsFromFiles($asset);

        return view('assets.show-public', compact('asset', 'relatedAssets', 'transcriptionSegments', 'userLiked', 'userFavorited', 'contentCategories', 'categories', 'effectiveVideoPath', 'banners', 'translationLanguages', 'programPlaylist', 'playback', 'playlistContext'));
    }

    /**
     * بث ملف الفيديو/الصوت مع دعم طلبات النطاق (Range) حتى يعمل شريط التقدم والنقر عليه.
     * للصفحة العامة (فيديو منشور فقط).
     */
    public function streamPublic(Asset $asset)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        if (! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح للعامة');
        }
        $asset->load('optimizedVersions');
        try {
            return $this->streamFileWithRange($asset);
        } catch (\Throwable $e) {
            $selectedRelativePath = null;
            $absolutePath = null;
            $exists = false;
            $readable = false;
            $size = null;
            try {
                $selectedRelativePath = $this->getWebVideoPath($asset);
                $absolutePath = $selectedRelativePath ? Storage::disk('public')->path($selectedRelativePath) : null;
                $exists = $absolutePath ? is_file($absolutePath) : false;
                $readable = $absolutePath ? is_readable($absolutePath) : false;
                $size = ($absolutePath && $exists) ? @filesize($absolutePath) : null;
            } catch (\Throwable $inner) {
                // Ignore nested diagnostics errors.
            }

            Log::error('Stream public failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'relative_path' => $asset->relative_path,
                'web_video_relative_path' => $asset->web_video_relative_path,
                'selected_relative_path' => $selectedRelativePath,
                'absolute_path' => $absolutePath,
                'exists' => $exists,
                'readable' => $readable,
                'size_bytes' => $size,
                'trace' => $e->getTraceAsString(),
            ]);

            // للحماية من استهلاك الذاكرة، نُبقي الاستجابة قصيرة ونضع التفاصيل في ملف log فقط.
            if (config('app.debug')) {
                return response(
                    "Stream debug for asset {$asset->id}\n"
                    .'relative_path: '.($asset->relative_path ?? 'null')."\n"
                    .'web_video_relative_path: '.($asset->web_video_relative_path ?? 'null')."\n"
                    .'selected_relative_path: '.($selectedRelativePath ?? 'null')."\n"
                    .'absolute_path: '.($absolutePath ?? 'null')."\n"
                    .'exists: '.($exists ? 'yes' : 'no')."\n"
                    .'readable: '.($readable ? 'yes' : 'no')."\n"
                    .'size_bytes: '.($size !== null ? (string) $size : 'null')."\n\n"
                    .'error: '.$e->getMessage(),
                    500,
                    ['Content-Type' => 'text/plain; charset=utf-8']
                );
            }

            abort(500, 'خطأ أثناء بث الملف');
        }
    }

    /**
     * تنزيل الملف الصوتي المعروض في المنصة الصوتية (أصلي أو مستخرج من audio_files).
     */
    public function downloadPublicAudio(Asset $asset)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        if (! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح للعامة');
        }

        $asset->load('audioFiles');
        if (! $asset->hasAudioPlatformPlayback()) {
            abort(404, 'المحتوى غير متاح');
        }

        $relativePath = null;
        $extension = 'mp3';

        if ($asset->isAudio()) {
            $asset->load('optimizedVersions');
            $relativePath = $this->getWebVideoPath($asset);
            $extension = strtolower((string) (pathinfo((string) $relativePath, PATHINFO_EXTENSION) ?: $asset->extension ?: 'mp3'));
        } else {
            $first = $asset->audioFiles->sortBy('id')->first();
            if (! $first || ! $first->file_path || strpos($first->file_path, 'assets/') !== 0) {
                abort(404, 'الملف الصوتي غير متاح');
            }
            if (! Storage::disk('public')->exists($first->file_path)) {
                abort(404, 'الملف غير موجود');
            }
            $relativePath = $first->file_path;
            $extension = strtolower((string) ($first->format ?? pathinfo($first->file_path, PATHINFO_EXTENSION) ?: 'mp3'));
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            abort(404, 'الملف غير متاح');
        }

        $baseName = $asset->title ?: pathinfo((string) $asset->file_name, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'audio-'.$asset->id;
        $baseName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '', $baseName);
        $baseName = trim(mb_substr($baseName, 0, 120));
        if ($baseName === '') {
            $baseName = 'audio-'.$asset->id;
        }

        $filename = $baseName.'.'.$extension;

        return response()->download($absolutePath, $filename);
    }

    /**
     * تنزيل ملف الفيديو المعروض في الصفحة العامة (الأصلي أو النسخة المحسّنة للويب).
     */
    public function downloadPublicVideo(Asset $asset)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        if (! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح للعامة');
        }
        if ($asset->isAudio()) {
            abort(404, 'المحتوى غير متاح');
        }

        $asset->load('optimizedVersions');
        $relativePath = $this->getWebVideoPath($asset);
        if (! $relativePath || strpos($relativePath, 'assets/') !== 0
            || ! Storage::disk('public')->exists($relativePath)
            || Storage::disk('public')->size($relativePath) <= 0) {
            if ($asset->relative_path && Storage::disk('public')->exists($asset->relative_path)
                && Storage::disk('public')->size($asset->relative_path) > 0) {
                $relativePath = $asset->relative_path;
            } else {
                abort(404, 'ملف الفيديو غير متاح للتحميل');
            }
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            abort(404, 'ملف الفيديو غير متاح للتحميل');
        }

        $extension = strtolower((string) (pathinfo($relativePath, PATHINFO_EXTENSION) ?: $asset->extension ?: 'mp4'));
        $baseName = $asset->title ?: pathinfo((string) $asset->file_name, PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'video-'.$asset->id;
        $baseName = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/u', '', $baseName);
        $baseName = trim(mb_substr($baseName, 0, 120));
        if ($baseName === '') {
            $baseName = 'video-'.$asset->id;
        }

        return response()->download($absolutePath, $baseName.'.'.$extension);
    }

    /** اللغات المسموحة لترجمة المحتوى النصي (كود => اسم باللغة الأم) */
    public const TRANSLATION_LANGUAGES = [
        'en' => 'English',
        'fr' => 'Français',
        'ur' => 'اردو',
        'id' => 'Bahasa Indonesia',
        'ha' => 'Hausa',
        'la' => 'Latina',
    ];

    /**
     * جلب مقاطع النص (عربي) للفيديو المنشور — من الـ cache/ملف JSON أو من transcription كنص واحد.
     */
    private function getTranscriptionSegmentsForPublic(Asset $asset): ?array
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            return null;
        }
        if (! auth()->check() && ! $asset->is_publishable) {
            return null;
        }
        $cacheKey = "transcription_segments_{$asset->id}";
        $segments = Cache::remember($cacheKey, 3600, function () use ($asset) {
            $videoDir = dirname($asset->relative_path);
            $captionDir = $videoDir.'/captions';
            $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
            $jsonPath = storage_path('app/public/'.$captionDir.'/'.$baseName.'.json');
            if (file_exists($jsonPath)) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if (! empty($data['segments']) && is_array($data['segments'])) {
                    return $data['segments'];
                }
            }

            return null;
        });
        if ($segments !== null) {
            return $segments;
        }
        $plain = trim((string) ($asset->transcription_plain ?? $asset->transcription ?? ''));
        if ($plain === '') {
            return null;
        }
        $duration = (float) ($asset->duration_seconds ?? 0);

        return [['start' => 0.0, 'end' => max(0.1, $duration), 'text' => $plain]];
    }

    /**
     * ترجمة المحتوى النصي إلى لغة محددة عبر DeepSeek مع الحفاظ على التوقيت.
     */
    public function translateTranscription(Asset $asset, Request $request)
    {
        set_time_limit(600);
        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '600');
        }

        try {
            if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
                return response()->json(['success' => false, 'error' => 'المحتوى غير متاح للترجمة'], 404);
            }
            if (! auth()->check() && ! $asset->is_publishable) {
                return response()->json(['success' => false, 'error' => 'المحتوى غير متاح للترجمة'], 404);
            }
            $lang = $request->input('lang', 'en');
            if (! array_key_exists($lang, self::TRANSLATION_LANGUAGES)) {
                return response()->json(['success' => false, 'error' => 'لغة غير مدعومة'], 400);
            }
            $segments = $this->getTranscriptionSegmentsForPublic($asset);
            if (! $segments || empty($segments)) {
                return response()->json(['success' => false, 'error' => 'لا يوجد محتوى نصي لترجمته'], 400);
            }
            $apiKey = config('deepseek.api_key');
            if (! $apiKey) {
                return response()->json(['success' => false, 'error' => 'مفتاح DeepSeek API غير مُعد'], 500);
            }
            $langName = self::TRANSLATION_LANGUAGES[$lang];
            // ترجمة على دفعات صغيرة (٥ مقاطع لكل طلب) لتجنّب timeout وخطأ الخادم
            $chunkSize = 5;
            $chunks = array_chunk($segments, $chunkSize);
            $translatedSegments = [];
            $baseIndex = 0;
            foreach ($chunks as $chunk) {
                $chunkJson = json_encode(array_map(function ($s) {
                    return ['start' => (float) ($s['start'] ?? 0), 'end' => (float) ($s['end'] ?? 0), 'text' => (string) ($s['text'] ?? '')];
                }, $chunk), JSON_UNESCAPED_UNICODE);
                $prompt = "Translate the Arabic text in the following JSON array to {$langName}. Keep the exact same structure: only translate the \"text\" field of each object. Return valid JSON only, no other text.\n\n{$chunkJson}";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(90)->connectTimeout(15)->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a translator. Output only valid JSON array with same structure (start, end, text). Translate only the "text" values to the requested language.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 4000,
                ]);
                if (! $response->successful()) {
                    $body = $response->body();
                    Log::warning('DeepSeek translate failed', ['status' => $response->status(), 'body' => $body]);
                    $errMsg = 'فشل الاتصال بخدمة الترجمة';
                    $bodyData = json_decode($body, true);
                    if (isset($bodyData['error']['message'])) {
                        $errMsg = $bodyData['error']['message'];
                    } elseif ($response->status() === 401) {
                        $errMsg = 'مفتاح API غير صحيح أو منتهي';
                    } elseif ($response->status() === 429) {
                        $errMsg = 'تجاوز حد الطلبات، جرّب لاحقاً';
                    }

                    return response()->json(['success' => false, 'error' => $errMsg], 502);
                }
                $data = $response->json();
                if (! is_array($data) || empty($data['choices'][0])) {
                    Log::warning('DeepSeek translate: unexpected response structure', ['data_keys' => is_array($data) ? array_keys($data) : null]);

                    return response()->json(['success' => false, 'error' => 'رد غير متوقع من خدمة الترجمة'], 502);
                }
                $firstChoice = $data['choices'][0];
                $content = trim((string) ($firstChoice['message']['content'] ?? ''));
                if ($content === '') {
                    $finishReason = $firstChoice['finish_reason'] ?? '';
                    if ($finishReason === 'length') {
                        return response()->json(['success' => false, 'error' => 'النص طويل جداً، جرّب لاحقاً أو قلّل المحتوى'], 502);
                    }

                    return response()->json(['success' => false, 'error' => 'لم تُرجع الخدمة أي محتوى'], 502);
                }
                $decoded = $this->extractJsonArrayFromTranslationResponse($content);
                if (! is_array($decoded)) {
                    Log::warning('DeepSeek translate: could not parse JSON', ['content_preview' => mb_substr($content, 0, 500)]);

                    return response()->json(['success' => false, 'error' => 'تعذر تحليل نتيجة الترجمة من الخدمة'], 502);
                }
                foreach ($decoded as $i => $item) {
                    $idx = $baseIndex + $i;
                    $text = (string) ($item['text'] ?? $item['translated_text'] ?? $item['content'] ?? '');
                    $origSegment = $segments[$idx] ?? $chunk[$i] ?? null;
                    $translatedSegments[$idx] = [
                        'start' => (float) ($origSegment['start'] ?? $item['start'] ?? 0),
                        'end' => (float) ($origSegment['end'] ?? $item['end'] ?? 0),
                        'text' => $text,
                    ];
                }
                $baseIndex += count($chunk);
            }

            ksort($translatedSegments);
            $all = is_array($asset->translation_segments) ? $asset->translation_segments : [];
            $all[$lang] = array_values($translatedSegments);

            try {
                $json = json_encode($all, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    throw new \RuntimeException('Failed to encode translation_segments to JSON');
                }
                DB::table('assets')->where('id', $asset->id)->update(['translation_segments' => $json]);
                $asset->setAttribute('translation_segments', $all);

                $this->saveTranslationSegmentsToFile($asset, $lang, array_values($translatedSegments));
            } catch (\Throwable $e) {
                Log::error('Translate transcription save failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);

                return response()->json(['success' => false, 'error' => 'فشل حفظ الترجمة: '.$e->getMessage()], 500);
            }

            Cache::forget("transcription_segments_{$asset->id}");

            return response()->json(['success' => true, 'segments' => array_values($translatedSegments), 'lang' => $lang]);
        } catch (\Throwable $e) {
            Log::error('Translate transcription exception', ['asset_id' => $asset->id, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            return response()->json(['success' => false, 'error' => 'خطأ: '.$e->getMessage()], 500);
        }
    }

    /**
     * حفظ مقاطع الترجمة كملف JSON في مجلد captions بجانب الفيديو (مثلاً: اسمالفيديو_en.json).
     */
    private function saveTranslationSegmentsToFile(Asset $asset, string $lang, array $segments): void
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            return;
        }
        $videoDir = dirname($asset->relative_path);
        $captionDir = $videoDir.'/captions';
        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $dirPath = storage_path('app/public/'.$captionDir);
        if (! is_dir($dirPath)) {
            @mkdir($dirPath, 0755, true);
        }
        $filePath = $dirPath.'/'.$baseName.'_'.$lang.'.json';
        $content = json_encode(['segments' => $segments], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($content !== false) {
            @file_put_contents($filePath, $content);
        }
    }

    /**
     * تحميل ترجمات من ملفات JSON في مجلد captions (اسمالفيديو_en.json إلخ) ودمجها مع translation_segments.
     */
    private function loadTranslationSegmentsFromFiles(Asset $asset): void
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            return;
        }
        $videoDir = dirname($asset->relative_path);
        $captionDir = $videoDir.'/captions';
        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $dirPath = storage_path('app/public/'.$captionDir);
        if (! is_dir($dirPath)) {
            return;
        }
        $all = is_array($asset->translation_segments) ? $asset->translation_segments : [];
        $updated = false;
        foreach (array_keys(self::TRANSLATION_LANGUAGES) as $lang) {
            $filePath = $dirPath.'/'.$baseName.'_'.$lang.'.json';
            if (! file_exists($filePath) || ! empty($all[$lang])) {
                continue;
            }
            $content = @file_get_contents($filePath);
            if ($content === false) {
                continue;
            }
            $data = json_decode($content, true);
            if (is_array($data) && ! empty($data['segments'])) {
                $all[$lang] = $data['segments'];
                $updated = true;
            }
        }
        if ($updated) {
            $asset->setAttribute('translation_segments', $all);
            DB::table('assets')->where('id', $asset->id)->update([
                'translation_segments' => json_encode($all, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * استخراج مصفوفة JSON من رد DeepSeek (قد يكون داخل ```json ... ``` أو نص إضافي).
     */
    private function extractJsonArrayFromTranslationResponse(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $m)) {
            $content = trim($m[1]);
        }
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\[[\s\S]*\]/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * بناء محتوى SBV من مقاطع (توقيت بتنسيق H:MM:SS.mmm).
     */
    private function buildSbvFromSegments(array $segments): string
    {
        $lines = [];
        foreach ($segments as $seg) {
            $start = (float) ($seg['start'] ?? 0);
            $end = (float) ($seg['end'] ?? 0);
            $text = trim((string) ($seg['text'] ?? ''));
            $startStr = $this->formatSecondsToSbv($start);
            $endStr = $this->formatSecondsToSbv($end);
            $lines[] = $startStr.','.$endStr;
            if ($text !== '') {
                $lines[] = $text;
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function formatSecondsToSbv(float $seconds): string
    {
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = (int) floor($seconds % 60);
        $ms = (int) round(($seconds - floor($seconds)) * 1000);

        return sprintf('%d:%02d:%02d.%03d', $h, $m, $s, $ms);
    }

    /**
     * تحميل المحتوى النصي بلغة واحدة (عربي أو لغة مترجمة) — صيغة SBV أو TXT.
     */
    public function downloadTranscription(Asset $asset, Request $request)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        if (! auth()->check() && ! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح');
        }
        $lang = $request->input('lang', 'ar');
        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $segments = null;
        $filename = $baseName;
        if ($lang === 'ar') {
            $segments = $this->getTranscriptionSegmentsForPublic($asset);
            $filename .= '_ar';
        } else {
            if (! array_key_exists($lang, self::TRANSLATION_LANGUAGES)) {
                abort(400, 'لغة غير مدعومة');
            }
            $all = $asset->translation_segments ?? [];
            $segments = $all[$lang] ?? null;
            $filename .= '_'.$lang;
        }
        if (! $segments || empty($segments)) {
            abort(404, 'لا يوجد محتوى لهذه اللغة');
        }
        $content = "\xEF\xBB\xBF".$this->buildSbvFromSegments($segments);
        $filename .= '.sbv';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ])->withHeaders(['Content-Length' => (string) strlen($content)]);
    }

    /**
     * تحميل كل الترجمات (عربي + كل اللغات المترجمة) في ملف ZIP واحد.
     * اسم الملف المضغوط = عنوان الفيديو، وأسماء الملفات الداخلية = اسم اللغة كاملاً (مثل English، العربية).
     */
    public function downloadTranscriptionAll(Asset $asset)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            abort(404, 'المحتوى غير متاح');
        }
        if (! auth()->check() && ! $asset->is_publishable) {
            abort(404, 'المحتوى غير متاح');
        }
        $this->loadTranslationSegmentsFromFiles($asset);
        $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
        $titleForFile = $this->sanitizeFilenameForZip($asset->title ?? $baseName);
        $zipPath = storage_path('app/temp/'.$baseName.'_transcriptions_'.time().'.zip');
        $dir = dirname($zipPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'تعذر إنشاء الملف');
        }
        $bom = "\xEF\xBB\xBF";
        $arSegments = $this->getTranscriptionSegmentsForPublic($asset);
        if ($arSegments && ! empty($arSegments)) {
            $zip->addFromString($titleForFile.'_العربية.sbv', $bom.$this->buildSbvFromSegments($arSegments));
        }
        $all = $asset->translation_segments ?? [];
        foreach (self::TRANSLATION_LANGUAGES as $lang => $langName) {
            if (! empty($all[$lang])) {
                $zip->addFromString($titleForFile.'_'.$langName.'.sbv', $bom.$this->buildSbvFromSegments($all[$lang]));
            }
        }
        $zip->close();
        if (filesize($zipPath) === 0) {
            @unlink($zipPath);
            abort(404, 'لا توجد ترجمات متاحة');
        }
        $content = file_get_contents($zipPath);
        @unlink($zipPath);
        $zipFilename = $titleForFile.'_transcriptions.zip';

        return response($content, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$zipFilename.'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    /**
     * جعل نص صالحاً لاستخدامه في اسم ملف (إزالة أحرف غير مسموحة في أسماء الملفات).
     */
    private function sanitizeFilenameForZip(string $title): string
    {
        $s = trim(preg_replace('/[\s]+/u', ' ', $title));
        $s = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $s);
        $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s);

        return mb_substr($s ?: 'transcriptions', 0, 200);
    }

    /**
     * بث ملف الفيديو/الصوت مع دعم Range (للوحة التحكم، للمستخدمين المسجلين).
     */
    public function stream(Asset $asset)
    {
        if (! $asset->relative_path || ! Storage::disk('public')->exists($asset->relative_path)) {
            abort(404, 'الملف غير موجود');
        }
        $asset->load('optimizedVersions');
        try {
            return $this->streamFileWithRange($asset);
        } catch (\Throwable $e) {
            Log::error('Stream failed', ['asset_id' => $asset->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            abort(500, 'خطأ أثناء بث الملف');
        }
    }

    /**
     * المسار الفعلي للفيديو المعروض على الويب (الأصلي أو النسخة المحددة بعد الضغط في الداشبورد).
     */
    private function getWebVideoPath(Asset $asset): ?string
    {
        return $asset->getWebPlaybackRelativePath();
    }

    private function applyAssetHasImageConstraint($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNotNull('cover_path')->where('cover_path', '!=', '');
            })->orWhere(function ($inner) {
                $inner->whereNotNull('thumbnail_path')->where('thumbnail_path', '!=', '');
            });
        });
    }

    private function applyAssetNoImageConstraint($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNull('cover_path')->orWhere('cover_path', '');
            })->where(function ($inner) {
                $inner->whereNull('thumbnail_path')->orWhere('thumbnail_path', '');
            });
        });
    }

    /**
     * روابط التشغيل والغلاف للصفحة العامة (فحص الملفات مرة واحدة في الـ Controller).
     *
     * @return array{
     *     fileUrl: ?string,
     *     streamUrl: ?string,
     *     posterUrl: string,
     *     pathForPlayer: ?string,
     *     dbPathForPlayer: ?string,
     *     hlsMasterPlaylist: ?string,
     *     useExtractedAudioForAudioPlatform: bool,
     *     schemaFileUrl: ?string,
     *     schemaThumbnailUrl: string,
     * }
     */
    private function resolvePublicPlaybackContext(Asset $asset, ?string $effectiveVideoPath): array
    {
        $defaultPoster = asset('images/logo_min.png');
        $defaultThumbnail = url('images/logo.png');

        $useExtractedAudioForAudioPlatform = request()->routeIs('audio.show')
            && $asset->relationLoaded('audioFiles')
            && $asset->audioFiles->isNotEmpty()
            && $asset->isVideo();

        $pathForPlayer = $effectiveVideoPath ?? $asset->relative_path;
        $dbPathForPlayer = $asset->web_video_relative_path ?: $asset->relative_path;
        $fileUrl = null;
        $streamUrl = null;

        if ($useExtractedAudioForAudioPlatform) {
            $firstExtractedAudio = $asset->audioFiles->sortBy('id')->first();
            $pathForPlayer = $firstExtractedAudio->file_path;
            $dbPathForPlayer = $firstExtractedAudio->file_path;
        }

        if ($pathForPlayer && strpos($pathForPlayer, 'assets/') === 0
            && Storage::disk('public')->exists($pathForPlayer)
            && Storage::disk('public')->size($pathForPlayer) > 0
            && ($useExtractedAudioForAudioPlatform || Asset::isVideoRelativePath($pathForPlayer))) {
            $fileUrl = asset('storage/'.$pathForPlayer);
            if (! $useExtractedAudioForAudioPlatform) {
                $streamUrl = route('assets.stream.public', $asset);
            }
        }

        $posterUrl = $defaultPoster;
        if ($asset->cover_path && Storage::disk('public')->exists($asset->cover_path)) {
            $posterUrl = asset('storage/'.$asset->cover_path);
        } elseif ($asset->thumbnail_path && Storage::disk('public')->exists($asset->thumbnail_path)) {
            $posterUrl = asset('storage/'.$asset->thumbnail_path);
        }

        $schemaThumbnailUrl = $defaultThumbnail;
        if ($asset->thumbnail_path && Storage::disk('public')->exists($asset->thumbnail_path)) {
            $schemaThumbnailUrl = url('storage/'.$asset->thumbnail_path);
        }

        $useSelectedWebVersion = $effectiveVideoPath && $effectiveVideoPath !== $asset->relative_path;
        $hlsMasterPlaylist = null;
        if (! $useSelectedWebVersion && $asset->hlsVersions && $asset->hlsVersions->count() > 0) {
            $masterPlaylist = $asset->hlsVersions->firstWhere('master_playlist_path', '!=', null);
            if ($masterPlaylist && $masterPlaylist->master_playlist_path
                && Storage::disk('public')->exists($masterPlaylist->master_playlist_path)) {
                $hlsMasterPlaylist = asset('storage/'.$masterPlaylist->master_playlist_path);
            }
        }

        return [
            'fileUrl' => $fileUrl,
            'streamUrl' => $streamUrl,
            'posterUrl' => $posterUrl,
            'pathForPlayer' => $pathForPlayer,
            'dbPathForPlayer' => $dbPathForPlayer,
            'hlsMasterPlaylist' => $hlsMasterPlaylist,
            'useExtractedAudioForAudioPlatform' => $useExtractedAudioForAudioPlatform,
            'schemaFileUrl' => $fileUrl ? url('storage/'.($effectiveVideoPath ?? $asset->relative_path)) : null,
            'schemaThumbnailUrl' => $schemaThumbnailUrl,
        ];
    }

    /**
     * سياق قائمة التشغيل لصفحة الفيديو: القائمة الأقرب (الأعمق) والحلقات بترتيبها.
     *
     * @return array{playlist: Playlist, videos: \Illuminate\Support\Collection, current_index: int}|null
     */
    private function resolvePlaylistContextForAsset(Asset $asset): ?array
    {
        if (! $asset->relationLoaded('playlists') || $asset->playlists->isEmpty()) {
            return null;
        }

        $playlist = $this->pickPlaylistContextForAsset($asset);
        if (! $playlist) {
            return null;
        }

        $videos = $this->fetchPlaylistContextVideos($playlist);
        if ($videos->isEmpty()) {
            return null;
        }

        $currentIndex = $videos->search(fn (Asset $video) => $video->id === $asset->id);
        if ($currentIndex === false) {
            return null;
        }

        $totalDurationSeconds = (int) $videos->sum(fn (Asset $video) => (int) ($video->duration_seconds ?? 0));
        if ($totalDurationSeconds < 7200) {
            return null;
        }

        $playlist->loadMissing('parent');

        return [
            'playlist' => $playlist,
            'videos' => $videos,
            'current_index' => $currentIndex,
        ];
    }

    private function pickPlaylistContextForAsset(Asset $asset): ?Playlist
    {
        $indexed = Playlist::indexedForRootLookup();
        $best = null;
        $bestDepth = -1;
        $bestPivotOrder = PHP_INT_MAX;

        foreach ($asset->playlists as $playlist) {
            // تخطي القوائم المخفية أو التي لها أب مخفي في السلسلة
            if (isset($playlist->is_visible) && ! $playlist->is_visible) {
                continue;
            }
            if ($this->playlistChainHasHiddenAncestor($playlist->parent_id, $indexed)) {
                continue;
            }

            $depth = 0;
            $parentId = $playlist->parent_id;
            while ($parentId && $indexed->has($parentId)) {
                $depth++;
                $parentId = $indexed[$parentId]->parent_id;
            }

            $pivotOrder = (int) ($playlist->pivot->order ?? PHP_INT_MAX);
            if ($depth > $bestDepth || ($depth === $bestDepth && $pivotOrder < $bestPivotOrder)) {
                $best = $playlist;
                $bestDepth = $depth;
                $bestPivotOrder = $pivotOrder;
            }
        }

        return $best;
    }

    private function playlistChainHasHiddenAncestor(?int $parentId, $indexed): bool
    {
        while ($parentId && $indexed->has($parentId)) {
            $node = $indexed[$parentId];
            if (isset($node->is_visible) && ! $node->is_visible) {
                return true;
            }
            $parentId = $node->parent_id;
        }

        return false;
    }

    private function fetchPlaylistContextVideos(Playlist $playlist): \Illuminate\Support\Collection
    {
        $hasChildPlaylists = $playlist->children()
            ->where('is_visible', true)
            ->where(function ($query) {
                $query->whereHas('assets', fn ($assets) => $assets->publishableUnderAssets()->videos())
                    ->orWhereHas('children', function ($children) {
                        $children->where('is_visible', true)
                            ->whereHas('assets', fn ($assets) => $assets->publishableUnderAssets()->videos());
                    });
            })
            ->exists();

        if ($hasChildPlaylists) {
            return $this->fetchPlaylistTreeVideosCollection($playlist);
        }

        return $playlist->assets()
            ->publishableUnderAssets()
            ->videos()
            ->select('assets.id', 'assets.file_name', 'assets.relative_path', 'assets.thumbnail_path', 'assets.cover_path', 'assets.orientation', 'assets.extension', 'assets.duration_seconds', 'assets.speaker_name', 'assets.title')
            ->orderByPivot('order', 'asc')
            ->orderBy('assets.id', 'asc')
            ->get();
    }

    private function fetchPlaylistTreeVideosCollection(Playlist $playlist): \Illuminate\Support\Collection
    {
        $playlistIds = $playlist->visibleDescendantPlaylistIdsInOrder();
        if ($playlistIds === []) {
            return collect();
        }

        $playlistOrderSql = $this->playlistIdsCaseOrderSql('asset_playlist.playlist_id', $playlistIds);
        $pivotOrderColumn = DB::connection()->getDriverName() === 'sqlite'
            ? 'asset_playlist."order"'
            : 'asset_playlist.`order`';

        return Asset::query()
            ->select([
                'assets.id',
                'assets.file_name',
                'assets.relative_path',
                'assets.thumbnail_path',
                'assets.cover_path',
                'assets.orientation',
                'assets.extension',
                'assets.duration_seconds',
                'assets.speaker_name',
                'assets.title',
            ])
            ->join('asset_playlist', 'assets.id', '=', 'asset_playlist.asset_id')
            ->whereIn('asset_playlist.playlist_id', $playlistIds)
            ->publishableUnderAssets()
            ->videos()
            ->groupBy(
                'assets.id',
                'assets.file_name',
                'assets.relative_path',
                'assets.thumbnail_path',
                'assets.cover_path',
                'assets.orientation',
                'assets.extension',
                'assets.duration_seconds',
                'assets.speaker_name',
                'assets.title',
            )
            ->orderByRaw("MIN({$playlistOrderSql})")
            ->orderByRaw("MIN({$pivotOrderColumn})")
            ->orderBy('assets.id')
            ->get();
    }

    private function playlistIdsCaseOrderSql(string $column, array $playlistIds): string
    {
        if ($playlistIds === []) {
            return '0';
        }

        $cases = [];
        foreach (array_values($playlistIds) as $index => $id) {
            $cases[] = 'WHEN '.(int) $id.' THEN '.$index;
        }

        return 'CASE '.$column.' '.implode(' ', $cases).' ELSE '.count($playlistIds).' END';
    }

    /**
     * إرجاع استجابة بث الملف مع دعم Range للانتقال داخل الفيديو/الصوت.
     */
    private function streamFileWithRange(Asset $asset)
    {
        $relativePath = $this->getWebVideoPath($asset);
        $path = $relativePath ? Storage::disk('public')->path($relativePath) : null;
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            if ($asset->relative_path && $relativePath !== $asset->relative_path) {
                $relativePath = $asset->relative_path;
                $path = Storage::disk('public')->path($relativePath);
            }
        }
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            abort(404, 'الملف غير متاح');
        }
        $size = filesize($path);
        if ($size <= 0) {
            abort(404, 'الملف غير متاح');
        }
        $ext = strtolower($asset->extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $mimes = [
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'mkv' => 'video/x-matroska',
            'm4v' => 'video/x-m4v', 'webm' => 'video/webm', 'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4', 'aac' => 'audio/aac',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';

        $range = request()->header('Range');
        if ($range && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            $start = $m[1] === '' ? 0 : (int) $m[1];
            $end = $m[2] === '' ? $size - 1 : min((int) $m[2], $size - 1);
            if ($start > $end || $start >= $size) {
                return response('', 416, [
                    'Content-Range' => "bytes */$size",
                    'Accept-Ranges' => 'bytes',
                ]);
            }
            $length = $end - $start + 1;
            $stream = fopen($path, 'rb');
            fseek($stream, $start);
            $content = fread($stream, $length);
            fclose($stream);

            return response($content, 206, [
                'Content-Type' => $mime,
                'Content-Length' => (string) $length,
                'Content-Range' => "bytes $start-$end/$size",
                'Accept-Ranges' => 'bytes',
            ]);
        }

        return response()->file($path, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $size,
        ]);
    }

    /**
     * تحديد النسخة المعروضة على الويب (من جدول ملفات الفيديو المتاحة).
     */
    public function setWebVideo(Request $request, Asset $asset)
    {
        $request->validate(['relative_path' => 'nullable|string|max:500']);

        $relativePath = $request->input('relative_path');
        $allowed = collect([$asset->relative_path])->merge($asset->optimizedVersions()->pluck('relative_path'))->filter()->unique()->values();

        if ($relativePath === null || $relativePath === '') {
            $asset->web_video_relative_path = null;
            $asset->save();

            return response()->json(['success' => true, 'message' => 'تم استخدام الفيديو الأصلي للعرض على الويب']);
        }

        if (! $allowed->contains($relativePath)) {
            return response()->json(['error' => 'النسخة المحددة غير مسموحة لهذا الفيديو'], 400);
        }
        if (! Storage::disk('public')->exists($relativePath)) {
            return response()->json(['error' => 'الملف غير موجود'], 400);
        }

        $asset->web_video_relative_path = $relativePath;
        $asset->save();

        return response()->json(['success' => true, 'message' => 'تم تحديد النسخة المعروضة على الويب']);
    }

    public function extractMetadata(Request $request, Asset $asset)
    {
        $wantsJson = $request->wantsJson() || $request->ajax();

        // استخدام المسار الأصلي إذا كان موجوداً، وإلا استخدام المسار الحالي
        $pathToUse = $asset->original_path ?: $asset->relative_path;

        if (! $pathToUse) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'لا يوجد مسار نسبي للملف'], 400);
            }

            return redirect()->route('assets.show', $asset)
                ->with('error', 'لا يوجد مسار نسبي للملف');
        }

        $apiKey = config('deepseek.api_key');
        if (! $apiKey) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'مفتاح DeepSeek API غير موجود في ملف .env'], 400);
            }

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
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في استخراج المعلومات من مسارات الملفات. أعد النتائج بالصيغة المطلوبة فقط.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 200,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (! isset($data['choices'][0]['message']['content'])) {
                    if ($wantsJson) {
                        return response()->json(['success' => false, 'error' => 'فشل في استخراج البيانات من API'], 400);
                    }

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
                    $message .= ' - المتحدث: '.$speakerName;
                }
                if ($title) {
                    $message .= ' - العنوان: '.$title;
                }

                if ($wantsJson) {
                    return response()->json(['success' => true, 'message' => $message]);
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
                    'path' => $pathToUse,
                ]);

                $errorMessage = 'فشل في الاتصال بـ DeepSeek API';
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ': '.$errorData['error']['message'];
                }

                if ($wantsJson) {
                    return response()->json(['success' => false, 'error' => $errorMessage], 400);
                }

                return redirect()->route('assets.show', $asset)
                    ->with('error', $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('Extract Metadata Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'asset_id' => $asset->id,
            ]);

            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'حدث خطأ: '.$e->getMessage()], 500);
            }

            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function analyzeContent(Asset $asset)
    {
        $apiKey = config('deepseek.api_key');
        if (! $apiKey) {
            return response()->json([
                'error' => 'مفتاح DeepSeek API غير موجود في ملف .env',
            ], 400);
        }

        try {
            // إرسال النسخة النصية فقط إلى DeepSeek (بدون توقيتات): من المقاطع إن وُجدت، وإلا من النص بعد تنقيته
            $transcription = $this->getPlainTextForAnalysis($asset);

            if ($transcription === '' || $transcription === null) {
                return response()->json([
                    'error' => 'لا يوجد محتوى نصي للتحليل. يرجى استخراج المحتوى النصي أولاً.',
                ], 400);
            }

            // تقليل طول النص إذا كان طويلاً جداً (DeepSeek له حد أقصى)
            if (strlen($transcription) > 10000) {
                $transcription = mb_substr($transcription, 0, 10000).'...';
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
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد متخصص في تحليل المحتوى النصي باللغة العربية. استخرج tags بسيطة (كلمات قصيرة) فقط. لا تستخدم جمل طويلة أو وصف مفصل. استخدم كلمات واضحة ومباشرة.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.5,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (! isset($data['choices'][0]['message']['content'])) {
                    return response()->json([
                        'error' => 'فشل في استخراج البيانات من API',
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
                    $category = Asset::sanitizeAnalysisValue($category);
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
                    $contentCategory = Asset::sanitizeAnalysisValue($contentCategory);
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
                    $siteDescription = Asset::sanitizeAnalysisValue($siteDescription);
                    // تنظيف الوصف: إزالة أي نص إضافي بعد الوصف
                    $siteDescriptionLines = explode("\n", $siteDescription);
                    $siteDescription = trim($siteDescriptionLines[0]);
                    // إذا كان الوصف طويلاً جداً، نأخذ أول 200 كلمة
                    $words = explode(' ', $siteDescription);
                    if (count($words) > 200) {
                        $siteDescription = implode(' ', array_slice($words, 0, 200)).'...';
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

                // تنظيف القيم قبل الإرجاع حتى لا تظهر في أي عرض (مثل alert)
                $responseData = [
                    'category' => $category ? Asset::sanitizeAnalysisValue($category) : $category,
                    'content_category' => $contentCategory ? Asset::sanitizeAnalysisValue($contentCategory) : $contentCategory,
                    'topics' => $topics,
                    'emotions' => $emotions,
                    'intent' => $intent,
                    'audience' => $audience,
                    'site_description' => $siteDescription ? Asset::sanitizeAnalysisValue($siteDescription) : $siteDescription,
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'تم تحليل المحتوى بنجاح',
                    'data' => $responseData,
                ]);
            } else {
                $errorMessage = 'فشل في الاتصال بـ DeepSeek API';
                if ($response->json() && isset($response->json()['error']['message'])) {
                    $errorMessage = $response->json()['error']['message'];
                }

                return response()->json([
                    'error' => $errorMessage,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error analyzing content', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'حدث خطأ أثناء تحليل المحتوى: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{clip_start_seconds: int, clip_end_seconds: int, clip_duration_seconds: ?int}|array{error: string}
     */
    private function resolveTranscriptionClipBounds(Request $request, Asset $asset): array
    {
        $start = max(0, (int) $request->input('clip_start_seconds', 0));
        $endRaw = $request->input('clip_end_seconds');
        $hasEnd = $endRaw !== null && $endRaw !== '';
        $end = $hasEnd ? max(0, (int) $endRaw) : null;

        if ($hasEnd && $end <= $start) {
            return ['error' => 'وقت النهاية يجب أن يكون بعد وقت البداية'];
        }

        $duration = $asset->duration_seconds ? (int) $asset->duration_seconds : null;

        if ($hasEnd && $duration !== null && $end > $duration) {
            $end = $duration;
        }

        if ($duration !== null && $start >= $duration) {
            return ['error' => 'وقت البداية يتجاوز مدة الفيديو'];
        }

        $clipDuration = null;
        if ($hasEnd) {
            $clipDuration = $end - $start;
        } elseif ($duration !== null) {
            $clipDuration = $duration - $start;
        }

        if ($clipDuration !== null && $clipDuration < 1) {
            return ['error' => 'مدة المقطع يجب أن تكون ثانية واحدة على الأقل'];
        }

        return [
            'clip_start_seconds' => $start,
            'clip_end_seconds' => $hasEnd ? (int) $end : 0,
            'clip_duration_seconds' => $clipDuration,
        ];
    }

    public function transcribe(Request $request, Asset $asset)
    {
        if (! $asset->relative_path) {
            return response()->json(['error' => 'لا يوجد مسار نسبي للملف'], 400);
        }

        // التحقق من أن الملف موجود في storage
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            return response()->json([
                'error' => 'لا يمكن استخراج المحتوى النصي. يجب نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".',
            ], 400);
        }

        if (! Storage::disk('public')->exists($asset->relative_path)) {
            return response()->json([
                'error' => 'الملف غير موجود في الموقع. يرجى نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".',
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
                        $processRunning = ! empty($result);
                    }
                }

                // التحقق أيضاً من ملف السجل إذا كان موجوداً
                if (! $processRunning && isset($existingStatus['log_file']) && file_exists($existingStatus['log_file'])) {
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
                        'cache_key' => $cacheKey,
                    ], 400);
                } else {
                    // العملية توقفت، حذف Cache
                    Log::info('Process stopped, clearing cache', ['asset_id' => $asset->id]);
                    Cache::forget($cacheKey);
                }
            }
        }

        // سكريبت النسخ: scripts/transcribe_video.py داخل المشروع
        $scriptPath = base_path('scripts/transcribe_video.py');

        if (! file_exists($scriptPath)) {
            return response()->json(['error' => 'سكريبت الاستخراج غير موجود'], 400);
        }

        try {
            // بناء المسار الكامل للفيديو من storage
            $fullVideoPath = Storage::disk('public')->path($asset->relative_path);

            // التحقق من وجود الملف
            if (! file_exists($fullVideoPath)) {
                Log::error('Video file not found', [
                    'asset_id' => $asset->id,
                    'relative_path' => $asset->relative_path,
                    'full_path' => $fullVideoPath,
                    'storage_disk' => config('filesystems.disks.public.root'),
                    'storage_exists' => Storage::disk('public')->exists($asset->relative_path),
                ]);

                return response()->json([
                    'error' => 'الملف غير موجود في storage: '.$asset->relative_path,
                    'full_path' => $fullVideoPath,
                ], 400);
            }

            // التحقق من الصلاحيات
            if (! is_readable($fullVideoPath)) {
                Log::error('Video file not readable', [
                    'asset_id' => $asset->id,
                    'full_path' => $fullVideoPath,
                    'permissions' => substr(sprintf('%o', fileperms($fullVideoPath)), -4),
                ]);

                return response()->json([
                    'error' => 'لا يمكن قراءة الملف. يرجى التحقق من الصلاحيات.',
                ], 403);
            }

            // استخدام المسار الكامل للفيديو
            $videoPath = $fullVideoPath;

            // مسار البايثون الافتراضي (حرفيًا) ثم باقي الاحتمالات
            $pythonPaths = [
                '/home/webadmin/Desktop/almonajah/.venv/bin/python',
                '/usr/bin/python3',  // Docker default
                '/usr/local/bin/python3',
                '/opt/homebrew/bin/python3',  // macOS Homebrew
                '/opt/homebrew/opt/python@3.11/bin/python3.11',  // macOS specific version
                trim(shell_exec('which python3 2>/dev/null') ?: ''),
            ];

            // إزالة المسارات الفارغة
            $pythonPaths = array_filter($pythonPaths, function ($path) {
                return ! empty($path) && $path !== '';
            });

            $pythonCmd = null;
            $testResults = [];

            foreach ($pythonPaths as $path) {
                if (empty($path)) {
                    continue;
                }

                // التحقق من وجود الملف أولاً
                if (! file_exists($path)) {
                    $testResults[$path] = 'file_not_exists';

                    continue;
                }

                // التحقق من قابلية التنفيذ
                if (! is_executable($path)) {
                    $testResults[$path] = 'not_executable';

                    continue;
                }

                $testCmd = escapeshellarg($path).' -c "import importlib.util; ok=bool(importlib.util.find_spec(\'faster_whisper\') or importlib.util.find_spec(\'whisper\')); print(\'OK\' if ok else \'NO\')" 2>&1';
                $testOutput = [];
                exec($testCmd, $testOutput, $testCode);

                $testResults[$path] = [
                    'exit_code' => $testCode,
                    'output' => implode("\n", $testOutput),
                    'has_whisper' => $testCode === 0 && ! empty($testOutput) && $testOutput[0] === 'OK',
                ];

                if ($testCode === 0 && ! empty($testOutput) && $testOutput[0] === 'OK') {
                    $pythonCmd = $path;
                    Log::info('Found Python with Whisper', [
                        'path' => $path,
                        'test_results' => $testResults,
                    ]);
                    break;
                }
            }

            if (! $pythonCmd) {
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
                    'tested_paths' => array_values($pythonPaths),
                ], 400);
            }

            // تهيئة حالة العملية
            Cache::put($cacheKey, [
                'status' => 'running',
                'progress' => 0,
                'message' => 'جاري البدء...',
            ], now()->addHours(2));

            // تشغيل السكريبت Python في الخلفية
            // نمرر رقم الفيديو (ID) كـ parameter إضافي
            // نستخدم المسار الكامل للفيديو (من storage) و basePath كمسار أساسي
            $basePath = storage_path('app/public'); // المسار الأساسي لـ storage

            // التأكد من وجود مجلد logs
            $logsDir = storage_path('logs');
            if (! is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }

            $logFile = storage_path('logs/transcription_'.$asset->id.'_'.time().'.log');

            // جودة النموذج: tiny = الأسرع على CPU (يفضّل مع faster-whisper)
            $validModels = ['tiny', 'base', 'small', 'medium'];
            $whisperModel = $request->input('model', 'tiny');
            if (! in_array($whisperModel, $validModels)) {
                $whisperModel = 'tiny';
            }

            $clipBounds = $this->resolveTranscriptionClipBounds($request, $asset);
            if (isset($clipBounds['error'])) {
                return response()->json(['error' => $clipBounds['error']], 400);
            }
            $clipStart = $clipBounds['clip_start_seconds'];
            $clipEnd = $clipBounds['clip_end_seconds'];

            $clipArgs = escapeshellarg($clipStart).' '.escapeshellarg($clipEnd);

            // بناء الأمر: video, basePath, assetId, model, clipStart, clipEnd
            $command = escapeshellarg($pythonCmd).' '.
                      escapeshellarg($scriptPath).' '.
                      escapeshellarg($videoPath).' '.
                      escapeshellarg($basePath).' '.
                      escapeshellarg($asset->id).' '.
                      escapeshellarg($whisperModel).' '.
                      $clipArgs.
                      ' > '.escapeshellarg($logFile).' 2>&1 & echo $!';

            // محاولة تشغيل العملية باستخدام طرق مختلفة
            $pid = null;
            $method = null;

            // الطريقة 1: استخدام shell_exec مع nohup (الأفضل للخلفية)
            if (function_exists('shell_exec')) {
                try {
                    // استخدام nohup لضمان استمرار العملية بعد إغلاق الاتصال
                    $nohupCommand = 'nohup '.escapeshellarg($pythonCmd).' '.
                                   escapeshellarg($scriptPath).' '.
                                   escapeshellarg($videoPath).' '.
                                   escapeshellarg($basePath).' '.
                                   escapeshellarg($asset->id).' '.
                                   escapeshellarg($whisperModel).' '.
                                   $clipArgs.
                                   ' >> '.escapeshellarg($logFile).' 2>&1 & echo $!';

                    $pid = trim(shell_exec($nohupCommand));

                    if (! empty($pid) && is_numeric($pid)) {
                        $method = 'shell_exec_nohup';
                        Log::info('Started transcription using shell_exec with nohup', [
                            'asset_id' => $asset->id,
                            'pid' => $pid,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('shell_exec with nohup failed, trying alternative method', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // الطريقة 2: استخدام shell_exec العادي
            if (! $pid && function_exists('shell_exec')) {
                try {
                    $pid = trim(shell_exec($command));
                    if (! empty($pid) && is_numeric($pid)) {
                        $method = 'shell_exec';
                        Log::info('Started transcription using shell_exec', [
                            'asset_id' => $asset->id,
                            'pid' => $pid,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('shell_exec failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // الطريقة 3: استخدام proc_open (قد لا يعمل في الخلفية بشكل صحيح)
            if (! $pid && function_exists('proc_open')) {
                try {
                    $descriptorspec = [
                        0 => ['file', '/dev/null', 'r'],
                        1 => ['file', $logFile, 'a'],
                        2 => ['file', $logFile, 'a'],
                    ];

                    // بناء الأمر بدون echo $! في النهاية
                    $baseCommand = escapeshellarg($pythonCmd).' '.
                                  escapeshellarg($scriptPath).' '.
                                  escapeshellarg($videoPath).' '.
                                  escapeshellarg($basePath).' '.
                                  escapeshellarg($asset->id).' '.
                                  escapeshellarg($whisperModel).' '.
                                  $clipArgs.' &';

                    $process = proc_open($baseCommand, $descriptorspec, $pipes);

                    if (is_resource($process)) {
                        // الحصول على معلومات العملية
                        $processInfo = proc_get_status($process);
                        $pid = $processInfo['pid'];
                        $method = 'proc_open';

                        // إغلاق pipes
                        if (isset($pipes[0])) {
                            fclose($pipes[0]);
                        }
                        if (isset($pipes[1])) {
                            fclose($pipes[1]);
                        }
                        if (isset($pipes[2])) {
                            fclose($pipes[2]);
                        }

                        // إغلاق process handle
                        proc_close($process);

                        Log::info('Started transcription using proc_open', [
                            'asset_id' => $asset->id,
                            'pid' => $pid,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('proc_open failed', [
                        'asset_id' => $asset->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // الطريقة 4: استخدام exec كبديل أخير
            if (! $pid && function_exists('exec')) {
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
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // التحقق من أن PID صحيح
            if (empty($pid) || ! is_numeric($pid)) {
                // محاولة قراءة ملف السجل لمعرفة الخطأ
                $errorMessage = 'فشل بدء العملية';
                $errorDetails = [];

                if (file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    if (! empty($logContent)) {
                        $errorMessage .= ': '.substr($logContent, 0, 200);
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

                Log::error('Failed to start transcription process', [
                    'asset_id' => $asset->id,
                    'error_details' => $errorDetails,
                ]);

                return response()->json([
                    'error' => $errorMessage,
                    'details' => $errorDetails,
                    'suggestion' => 'يرجى التحقق من: 1) تفعيل proc_open أو shell_exec في PHP, 2) تثبيت Python و Whisper, 3) الصلاحيات على الملفات والمجلدات',
                ], 500);
            }

            Log::info('Started transcription process', [
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
                'started_at' => now()->toDateTimeString(),
                'clip_start_seconds' => $clipStart,
                'clip_end_seconds' => $clipEnd,
                'clip_duration_seconds' => $clipBounds['clip_duration_seconds'],
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية الاستخراج',
                'cache_key' => $cacheKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Transcription Exception', [
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
            $errorMessage = 'حدث خطأ أثناء بدء العملية: '.$e->getMessage();

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
                'message' => 'تم حذف حالة العملية',
            ]);
        }

        $status = Cache::get($cacheKey);

        if (! $status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية',
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
                $isProcessRunning = ! empty(trim($checkProcess));
            }

            // إذا كانت العملية انتهت (SUCCESS موجود) أو الـ process لم يعد يعمل
            if ($hasSuccess || ($hasTranscriptionEnd && ! $isProcessRunning)) {
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
                    // حفظ نسخة منقاة من التوقيتات لإرسالها لـ DeepSeek لاحقاً
                    $asset->transcription_plain = $this->stripTimestampsFromTranscription($transcription);

                    // تحديث التصنيف بناءً على المحتوى النصي
                    $this->updateCategoryFromTranscription($asset, $transcription);

                    $asset->save();

                    $status['transcription_length'] = strlen($transcription);
                    $status['message'] = '✅ تم استخراج المحتوى النصي بنجاح ('.number_format(strlen($transcription)).' حرف)';

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
            } elseif (! $isProcessRunning && ! $hasSuccess && ! $hasTranscriptionEnd) {
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
                        // حفظ نسخة منقاة من التوقيتات لإرسالها لـ DeepSeek لاحقاً
                        $asset->transcription_plain = $this->stripTimestampsFromTranscription($transcription);

                        // تحديث التصنيف بناءً على المحتوى النصي
                        $this->updateCategoryFromTranscription($asset, $transcription);

                        $asset->save();
                        $status['transcription_length'] = strlen($transcription);
                        $status['message'] = '✅ تم استخراج المحتوى النصي بنجاح ('.number_format(strlen($transcription)).' حرف)';

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
                $this->refreshTranscriptionRunningProgress($status, $logContent, $asset);
                Cache::put($cacheKey, $status, now()->addHours(2));
            }
        }

        return response()->json($status);
    }

    private function refreshTranscriptionRunningProgress(array &$status, string $logContent, Asset $asset): void
    {
        $progress = (int) ($status['progress'] ?? 5);
        $message = 'جاري المعالجة...';

        if (preg_match_all('/PROGRESS:(\d+):(\w+)/', $logContent, $progressMatches, PREG_SET_ORDER) && count($progressMatches) > 0) {
            $progressMatch = $progressMatches[count($progressMatches) - 1];
            $progress = min(95, max(5, (int) $progressMatch[1]));
            $stage = trim($progressMatch[2]);
            $stageMessages = [
                'loading_model' => 'جاري تحميل نموذج Whisper (أول مرة قد تنزّل ~74MB)...',
                'model_loaded' => 'تم تحميل النموذج — جاري استخراج النص...',
                'transcribing' => 'جاري استخراج النص من الفيديو (على المعالج قد يستغرق وقتاً)...',
                'saving' => 'جاري حفظ ملفات الترجمة...',
            ];
            $message = $stageMessages[$stage] ?? $message;
        } elseif (strpos($logContent, 'جاري استخراج النص') !== false || stripos($logContent, 'transcribe') !== false) {
            $message = 'جاري استخراج النص من الفيديو (على المعالج قد يستغرق وقتاً)...';
            $progress = max($progress, 25);
            $durationForEstimate = ! empty($status['clip_duration_seconds'])
                ? (int) $status['clip_duration_seconds']
                : ($asset->duration_seconds ? (int) $asset->duration_seconds : null);
            if (! empty($status['started_at']) && $durationForEstimate) {
                $elapsed = \Carbon\Carbon::parse($status['started_at'])->diffInSeconds(now());
                $estimated = max(90, (int) ($durationForEstimate * 1.8));
                $progress = min(92, 25 + (int) (($elapsed / $estimated) * 67));
            }
        } elseif (strpos($logContent, 'تم تحميل النموذج') !== false) {
            $message = 'تم تحميل النموذج — جاري استخراج النص...';
            $progress = max($progress, 22);
        } elseif (strpos($logContent, 'جاري تحميل النموذج') !== false) {
            $message = 'جاري تحميل نموذج Whisper (أول مرة قد تنزّل ~74MB)...';
            if (! empty($status['started_at'])) {
                $elapsed = \Carbon\Carbon::parse($status['started_at'])->diffInSeconds(now());
                $progress = min(22, 5 + (int) min(17, $elapsed / 4));
            } else {
                $progress = max($progress, 8);
            }
        } elseif ($logContent !== '') {
            $progress = max($progress, 8);
            $message = 'جاري بدء المعالجة...';
        }

        $status['progress'] = $progress;
        $status['message'] = $message;
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
        $query = request()->only('view', 'path');

        return redirect()->route('assets.index', $query)
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
        $totalWastedSpace = collect($duplicateGroups)->map(function ($group) {
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

    public function moveFile(Request $request, Asset $asset)
    {
        $wantsJson = $request->wantsJson() || $request->ajax();

        // التحقق من أن الملف موجود في storage بالفعل
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0 && Storage::disk('public')->exists($asset->relative_path)) {
            if ($wantsJson) {
                return response()->json(['success' => true, 'message' => 'الملف موجود بالفعل في الموقع', 'already_moved' => true]);
            }

            return redirect()->route('assets.show', $asset)
                ->with('info', 'الملف موجود بالفعل في الموقع: '.$asset->relative_path);
        }

        // استخدام المسار الأصلي (original_path) مباشرة
        if (! $asset->original_path) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'لا يوجد مسار أصلي للملف. يرجى التأكد من أن الملف تم استيراده بشكل صحيح.'], 400);
            }

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
            $storagePath = storage_path('app/public/'.$originalPath);
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
        if (! file_exists($oldFullPath)) {
            $errorMessage = 'الملف غير موجود في المسار المحدد. المسار الأصلي: '.$asset->original_path;
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => $errorMessage], 400);
            }

            return redirect()->route('assets.show', $asset)
                ->with('error', 'الملف غير موجود في المسار المحدد: '.$oldFullPath.'<br><br>المسار الأصلي في قاعدة البيانات: '.$asset->original_path.'<br><br>يرجى التأكد من أن الملف موجود في المسار المحدد.');
        }

        // المسار الجديد: مجلد باسم ID الملف فقط، وداخله ملف الفيديو (بدون سنة أو مسارات أخرى)
        // مثال: assets/566/master.mp4
        $newStoragePath = 'assets/'.$asset->id.'/master.'.$asset->extension;

        Log::info('Preparing to move file', [
            'asset_id' => $asset->id,
            'source' => $oldFullPath,
            'destination' => $newStoragePath,
        ]);

        // استخدام Laravel Storage
        try {
            // التحقق من حجم الملف قبل النسخ
            $fileSize = filesize($oldFullPath);
            if ($fileSize === false) {
                if ($wantsJson) {
                    return response()->json(['success' => false, 'error' => 'لا يمكن قراءة معلومات الملف.'], 400);
                }

                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن قراءة معلومات الملف.');
            }

            // إنشاء المجلد إذا لم يكن موجوداً
            Storage::disk('public')->makeDirectory(dirname($newStoragePath));

            // نسخ الملف إلى storage/app/public باستخدام stream للتعامل مع الملفات الكبيرة
            $sourceHandle = fopen($oldFullPath, 'rb');
            if (! $sourceHandle) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن فتح الملف المصدر للقراءة.');
            }

            // استخدام Laravel Storage stream
            $destinationPath = Storage::disk('public')->path($newStoragePath);
            $destinationHandle = fopen($destinationPath, 'wb');
            if (! $destinationHandle) {
                fclose($sourceHandle);
                if ($wantsJson) {
                    return response()->json(['success' => false, 'error' => 'لا يمكن إنشاء الملف الوجهة.'], 500);
                }

                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن إنشاء الملف الوجهة.');
            }

            // نسخ الملف على دفعات (chunks) لتوفير الذاكرة
            $chunkSize = 8192; // 8KB chunks
            $copiedBytes = 0;
            while (! feof($sourceHandle)) {
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
            if (! Storage::disk('public')->exists($newStoragePath)) {
                if ($wantsJson) {
                    return response()->json(['success' => false, 'error' => 'فشل في نسخ الملف.'], 500);
                }

                return redirect()->route('assets.show', $asset)
                    ->with('error', 'فشل في نسخ الملف. تم نسخ '.number_format($copiedBytes).' بايت من '.number_format($fileSize).' بايت.');
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

            if (! $asset->original_relative_path) {
                // نحفظ المسار النسبي الحالي (حتى لو كان null أو فارغ)
                $asset->original_relative_path = $currentRelativePath;
            }

            // تحديث المسار النسبي في قاعدة البيانات للمسار الجديد
            // المسار الجديد: مجلد بالـ ID فقط، وداخله ملف الفيديو — assets/{id}/master.{extension}
            // ملاحظة: لا نغير original_path - يبقى كما هو (المسار الأصلي للملف)
            $asset->relative_path = $newStoragePath;
            $asset->file_name = 'master.'.$asset->extension;
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
            $fileUrl = asset('storage/'.$newStoragePath);

            Log::info('File moved successfully', [
                'asset_id' => $asset->id,
                'new_path' => $newStoragePath,
                'file_size' => $fileSize,
            ]);

            if ($wantsJson) {
                return response()->json(['success' => true, 'message' => 'تم نقل الملف بنجاح']);
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم نقل الملف بنجاح ('.number_format($fileSize / 1024 / 1024, 2).' MB). يمكنك الوصول إليه عبر: '.$fileUrl);
        } catch (\Exception $e) {
            Log::error('Failed to move file', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($wantsJson) {
                return response()->json(['success' => false, 'error' => 'فشل في نقل الملف: '.$e->getMessage()], 500);
            }

            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل في نقل الملف: '.$e->getMessage());
        }
    }

    public function openFolder(Asset $asset)
    {
        if (! $asset->relative_path) {
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
                $fullPath = $basePath.'/'.$asset->relative_path;
                $folderPath = dirname($fullPath);
            }

            if (! is_dir($folderPath)) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'الفولدر غير موجود: '.$folderPath);
            }

            // فتح الفولدر في Finder (macOS)
            if (PHP_OS_FAMILY === 'Darwin') {
                exec("open '".escapeshellarg($folderPath)."'");
            } elseif (PHP_OS_FAMILY === 'Windows') {
                exec("explorer '".escapeshellarg($folderPath)."'");
            } elseif (PHP_OS_FAMILY === 'Linux') {
                exec("xdg-open '".escapeshellarg($folderPath)."'");
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم فتح الفولدر بنجاح');
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل في فتح الفولدر: '.$e->getMessage());
        }
    }

    /**
     * بدء عملية تقليل مساحة الملف الأصلي (إعادة ترميز مناسبة للنشر على الويب).
     * الإعدادات: جودة عالية / متوازن / حجم أصغر — مع الحفاظ على أفضل جودة ممكنة.
     */
    public function startOptimizeOriginal(Request $request, Asset $asset)
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            return response()->json(['error' => 'يجب نقل الفيديو إلى الموقع أولاً.'], 400);
        }
        if (! Storage::disk('public')->exists($asset->relative_path)) {
            return response()->json(['error' => 'الملف غير موجود في الموقع.'], 400);
        }

        $quality = $request->input('quality', 'balanced');
        $validQualities = ['high' => 1, 'balanced' => 1, 'small' => 1];
        if (! isset($validQualities[$quality])) {
            $quality = 'balanced';
        }

        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            trim(shell_exec('which ffmpeg 2>/dev/null') ?: ''),
        ];
        $ffmpegPath = null;
        foreach ($possiblePaths as $path) {
            if (! empty($path) && file_exists($path) && is_executable($path)) {
                $ffmpegPath = $path;
                break;
            }
        }
        if (! $ffmpegPath) {
            return response()->json(['error' => 'FFmpeg غير مثبت.'], 400);
        }

        $videoPath = Storage::disk('public')->path($asset->relative_path);
        $videoDir = dirname($videoPath);
        $ext = pathinfo($asset->relative_path, PATHINFO_EXTENSION) ?: 'mp4';
        $tempName = 'temp_optimized_'.$asset->id.'_'.time().'.'.$ext;
        $tempPath = $videoDir.'/'.$tempName;

        // إعدادات حسب الاختيار: جودة عالية / متوازن / حجم أصغر
        $crf = $quality === 'high' ? 20 : ($quality === 'small' ? 26 : 23);
        $preset = $quality === 'high' ? 'slow' : 'medium';
        $audioBitrate = $quality === 'small' ? '96k' : '128k';
        // حجم أصغر: حد أقصى 720p (ارتفاع 720، العرض يتكيف) — مناسب للويب
        $scaleFilter = $quality === 'small' ? '-vf "scale=-2:720" ' : '';

        $logFile = storage_path('logs/optimize_original_'.$asset->id.'_'.time().'.log');
        $cmd = escapeshellarg($ffmpegPath).' -i '.escapeshellarg($videoPath).' '.
            '-c:v libx264 -crf '.(int) $crf.' -preset '.$preset.' '.
            $scaleFilter.
            '-c:a aac -b:a '.$audioBitrate.' -movflags +faststart -y '.
            escapeshellarg($tempPath).' >> '.escapeshellarg($logFile).' 2>&1 & echo $!';
        $pid = trim(shell_exec($cmd));

        $cacheKey = "optimize_original_{$asset->id}";
        Cache::put($cacheKey, [
            'status' => 'running',
            'progress' => 5,
            'message' => 'جاري إنشاء نسخة محسّنة...',
            'pid' => $pid,
            'log_file' => $logFile,
            'video_path' => $videoPath,
            'temp_path' => $tempPath,
            'quality' => $quality,
            'started_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        return response()->json([
            'success' => true,
            'message' => 'تم بدء عملية تقليل المساحة',
        ]);
    }

    /**
     * حالة عملية تقليل مساحة الملف الأصلي.
     */
    public function optimizeOriginalStatus(Asset $asset)
    {
        $cacheKey = "optimize_original_{$asset->id}";
        if (request()->has('clear')) {
            Cache::forget($cacheKey);

            return response()->json(['status' => 'cleared']);
        }

        $status = Cache::get($cacheKey);
        if (! $status) {
            return response()->json(['status' => 'not_started', 'progress' => 0, 'message' => 'لا توجد عملية جارية']);
        }

        $pid = $status['pid'] ?? null;
        $logFile = $status['log_file'] ?? null;
        $videoPath = $status['video_path'] ?? null;
        $tempPath = $status['temp_path'] ?? null;

        $processRunning = false;
        if ($pid && (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux')) {
            $r = trim(shell_exec("ps -p {$pid} > /dev/null 2>&1 && echo running || echo stopped"));
            $processRunning = ($r === 'running');
        }

        $logContent = '';
        if ($logFile && file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
        }

        if (! $processRunning) {
            // العملية انتهت — إن أنتجت ملفاً نحمله كنسخة جديدة (لا نستبدل الأصلي)
            $quality = $status['quality'] ?? 'balanced';
            if ($tempPath && file_exists($tempPath) && filesize($tempPath) > 0) {
                $ext = pathinfo($asset->relative_path, PATHINFO_EXTENSION) ?: 'mp4';
                $baseName = pathinfo($asset->relative_path, PATHINFO_FILENAME);
                $dir = dirname($asset->relative_path);
                $finalFileName = $baseName.'_optimized_'.$quality.'.'.$ext;
                $finalRelativePath = $dir.'/'.$finalFileName;
                $finalFullPath = Storage::disk('public')->path($finalRelativePath);

                if (@rename($tempPath, $finalFullPath)) {
                    $newSize = filesize($finalFullPath);
                    $width = null;
                    $height = null;
                    // محاولة قراءة الأبعاد من ffprobe إن وُجد (اختياري)
                    $asset->optimizedVersions()->create([
                        'relative_path' => $finalRelativePath,
                        'quality_preset' => $quality,
                        'size_bytes' => $newSize,
                        'width' => $width,
                        'height' => $height,
                    ]);
                    // تعيين هذه النسخة المحسّنة كنسخة العرض على الويب تلقائياً
                    $asset->web_video_relative_path = $finalRelativePath;
                    $asset->save();
                    Cache::forget($cacheKey);

                    return response()->json([
                        'status' => 'completed',
                        'progress' => 100,
                        'message' => 'تم إنشاء نسخة ويب متوافقة (H.264) واختيارها للعرض على الويب.',
                        'web_video_relative_path' => $finalRelativePath,
                        'log' => $logContent,
                    ]);
                }
            }
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            Cache::forget($cacheKey);

            return response()->json([
                'status' => 'error',
                'progress' => 0,
                'message' => 'فشلت العملية أو لم يُنتج ملفاً',
                'log' => $logContent,
            ]);
        }

        // تقدير تقدم من السجل إن وُجد
        $progress = (int) ($status['progress'] ?? 10);
        if (preg_match('/time=(\d+):(\d+):(\d+)/', $logContent, $m)) {
            $sec = (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3];
            $progress = min(90, 10 + (int) ($sec / 10));
        }

        return response()->json([
            'status' => 'running',
            'progress' => $progress,
            'message' => 'جاري تقليل مساحة الملف...',
            'log' => $logContent,
        ]);
    }

    public function convertToHls(Asset $asset)
    {
        if (! $asset->relative_path) {
            return response()->json(['error' => 'لا يوجد مسار نسبي للملف'], 400);
        }

        // التحقق من أن الملف موجود في storage
        if (strpos($asset->relative_path, 'assets/') !== 0) {
            return response()->json([
                'error' => 'يجب نقل الفيديو إلى الموقع أولاً باستخدام زر "نقل المحتوى".',
            ], 400);
        }

        if (! Storage::disk('public')->exists($asset->relative_path)) {
            return response()->json([
                'error' => 'الملف غير موجود في الموقع. يرجى نقل الفيديو إلى الموقع أولاً.',
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
            if (! empty($path) && file_exists($path) && is_executable($path)) {
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
            $hlsDir = $videoDir.'/hls';

            // إنشاء مجلد HLS مع الصلاحيات الصحيحة
            if (! is_dir($hlsDir)) {
                mkdir($hlsDir, 0775, true);
                chmod($hlsDir, 0775);
                // محاولة تغيير المالك (قد لا يعمل في Docker بدون sudo)
                @chown($hlsDir, 'www-data');
            }

            // إنشاء مجلدات للنسخ المختلفة (مع التحقق من الوجود)
            $hlsSubDirs = ['/v0', '/v1', '/v2'];
            foreach ($hlsSubDirs as $subDir) {
                $fullPath = $hlsDir.$subDir;
                if (! is_dir($fullPath)) {
                    mkdir($fullPath, 0775, true);
                    chmod($fullPath, 0775);
                    // محاولة تغيير المالك (قد لا يعمل في Docker بدون sudo)
                    @chown($fullPath, 'www-data');
                }
            }

            // التأكد من الصلاحيات باستخدام shell command (يعمل بشكل أفضل في Docker)
            shell_exec('chmod -R 775 '.escapeshellarg($hlsDir).' 2>/dev/null');
            shell_exec('chown -R www-data:www-data '.escapeshellarg($hlsDir).' 2>/dev/null');

            Log::info('HLS directories created', [
                'asset_id' => $asset->id,
                'hls_dir' => $hlsDir,
                'permissions' => substr(sprintf('%o', fileperms($hlsDir)), -4),
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
            $logFile = storage_path('logs/hls_conversion_'.$asset->id.'_'.time().'.log');

            // بناء أمر FFmpeg مع إعادة توجيه output إلى ملف السجل
            $command = escapeshellarg($ffmpegPath).' -i '.escapeshellarg($videoPath).' '.
                '-filter_complex '.
                '"[0:v]split=3[v1][v2][v3]; '.
                '[v1]scale=w=640:h=360[v1out]; '.
                '[v2]scale=w=854:h=480[v2out]; '.
                '[v3]scale=w=1280:h=720[v3out]" '.
                '-map "[v1out]" -map 0:a -c:v:0 h264 -b:v:0 800k -c:a:0 aac -b:a:0 96k '.
                '-map "[v2out]" -map 0:a -c:v:1 h264 -b:v:1 1400k -c:a:1 aac -b:a:1 128k '.
                '-map "[v3out]" -map 0:a -c:v:2 h264 -b:v:2 2800k -c:a:2 aac -b:a:2 128k '.
                '-f hls '.
                '-hls_time 6 '.
                '-hls_playlist_type vod '.
                '-hls_flags independent_segments '.
                '-master_pl_name master.m3u8 '.
                '-var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2" '.
                '-hls_segment_filename '.escapeshellarg($hlsDir.'/v%v/seg_%03d.ts').' '.
                escapeshellarg($hlsDir.'/v%v/index.m3u8').' '.
                '> '.escapeshellarg($logFile).' 2>&1 & echo $!';

            // تشغيل FFmpeg في الخلفية
            $pid = trim(shell_exec($command));

            Log::info('Started HLS conversion process', [
                'asset_id' => $asset->id,
                'pid' => $pid,
                'log_file' => $logFile,
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
                'video_path' => $videoPath,
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية التحويل',
                'cache_key' => $cacheKey,
            ]);

        } catch (\Exception $e) {
            Log::error('HLS conversion error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'حدث خطأ أثناء بدء التحويل: '.$e->getMessage(),
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
                'message' => 'تم حذف حالة العملية',
            ]);
        }

        $status = Cache::get($cacheKey);

        if (! $status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية',
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
                $isProcessRunning = ! empty(trim($checkProcess));
            }

            // التحقق من اكتمال العملية (فحص وجود ملفات playlist)
            $hlsDir = $status['hls_dir'] ?? null;
            $isCompleted = false;
            if ($hlsDir && is_dir($hlsDir)) {
                // التحقق من وجود ملفات playlist
                $masterPlaylist = $hlsDir.'/master.m3u8';
                $v0Playlist = $hlsDir.'/v0/index.m3u8';
                $v1Playlist = $hlsDir.'/v1/index.m3u8';
                $v2Playlist = $hlsDir.'/v2/index.m3u8';

                if (file_exists($masterPlaylist) && file_exists($v0Playlist) &&
                    file_exists($v1Playlist) && file_exists($v2Playlist) && ! $isProcessRunning) {
                    $isCompleted = true;
                }
            }

            // إذا كانت العملية انتهت
            if ($isCompleted || (! $isProcessRunning && strlen($logContent) > 1000)) {
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
                    'playlist_path' => $relativeHlsDir.'/v0/index.m3u8',
                ],
                [
                    'resolution' => '480p',
                    'width' => 854,
                    'height' => 480,
                    'bitrate' => '1400k',
                    'audio_bitrate' => '128k',
                    'playlist_path' => $relativeHlsDir.'/v1/index.m3u8',
                ],
                [
                    'resolution' => '720p',
                    'width' => 1280,
                    'height' => 720,
                    'bitrate' => '2800k',
                    'audio_bitrate' => '128k',
                    'playlist_path' => $relativeHlsDir.'/v2/index.m3u8',
                ],
            ];

            $masterPlaylistPath = $relativeHlsDir.'/master.m3u8';

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
                        $segmentPath = $segmentDir.'/'.$segmentFile;
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
            Log::error('Failed to save HLS versions', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function extractAudio(Asset $asset)
    {
        try {
            // التحقق من أن الملف موجود في storage
            if (strpos($asset->relative_path, 'assets/') !== 0) {
                return response()->json([
                    'error' => 'يجب نقل الفيديو إلى الموقع أولاً',
                ], 400);
            }

            $videoPath = Storage::disk('public')->path($asset->relative_path);

            if (! file_exists($videoPath)) {
                return response()->json([
                    'error' => 'الملف غير موجود',
                ], 404);
            }

            // التحقق من أن الملف فيديو
            if (! in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi'])) {
                return response()->json([
                    'error' => 'الملف ليس فيديو',
                ], 400);
            }

            // إنشاء مجلد للصوت
            $audioDir = dirname($videoPath).'/audio';
            if (! is_dir($audioDir)) {
                mkdir($audioDir, 0755, true);
            }

            // اسم ملف الصوت
            $audioFileName = 'audio.mp3';
            $audioPath = $audioDir.'/'.$audioFileName;
            $logFile = $audioDir.'/extract_audio.log';

            // إنشاء ملف log فارغ
            file_put_contents($logFile, '');

            // أمر ffmpeg لاستخراج الصوت بصيغة MP3 (مناسبة لـ SoundCloud و Spotify)
            // استخدام -progress لكتابة التقدم في ملف log
            $command = 'ffmpeg -i '.escapeshellarg($videoPath).' '.
                '-vn '. // لا نريد فيديو
                '-acodec libmp3lame '. // استخدام MP3 codec
                '-ab 192k '. // معدل البت 192k (جودة عالية)
                '-ar 44100 '. // معدل العينة 44.1kHz (معيار CD)
                '-ac 2 '. // ستريو (2 قنوات)
                '-y '. // استبدال الملف إذا كان موجوداً
                '-progress '.escapeshellarg($logFile).' '.
                escapeshellarg($audioPath).' '.
                '2>&1 & echo $!';

            // تشغيل ffmpeg في الخلفية
            $pid = trim(shell_exec($command));

            Log::info('Started audio extraction process', [
                'asset_id' => $asset->id,
                'pid' => $pid,
                'log_file' => $logFile,
                'audio_path' => $audioPath,
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
                'audio_dir' => $audioDir,
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية استخراج الصوت',
                'cache_key' => $cacheKey,
            ]);

        } catch (\Exception $e) {
            Log::error('Audio extraction error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'حدث خطأ أثناء بدء العملية: '.$e->getMessage(),
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
                'message' => 'تم حذف حالة العملية',
            ]);
        }

        $status = Cache::get($cacheKey);

        if (! $status) {
            return response()->json([
                'status' => 'not_started',
                'progress' => 0,
                'message' => 'لا توجد عملية جارية',
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
                $isProcessRunning = ! empty(trim($checkProcess));
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
                if ($audioSize > 0 && (! $isProcessRunning || $hasProgressEnd)) {
                    $isCompleted = true;
                }
            } elseif ($hasProgressEnd && ! $isProcessRunning) {
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
                    $status['audio_url'] = asset('storage/'.$relativeAudioPath);

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
                        $status['audio_url'] = asset('storage/'.$relativeAudioPath);

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

    public function uploadAudio(Asset $asset, Request $request)
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,mpeg,wav,m4a,ogg|max:512000',
        ]);

        if (strpos($asset->relative_path ?? '', 'assets/') !== 0) {
            return response()->json([
                'success' => false,
                'error' => 'يجب نقل الفيديو إلى الموقع أولاً',
            ], 400);
        }

        $file = $request->file('audio_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp3');
        if (! in_array($ext, ['mp3', 'mpeg', 'wav', 'm4a', 'ogg'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'يرجى رفع ملف صوتي بصيغة MP3 أو WAV أو M4A أو OGG.',
            ], 422);
        }

        $format = in_array($ext, ['mp3', 'mpeg'], true) ? 'mp3' : $ext;
        $targetName = $format === 'mp3' ? 'audio.mp3' : 'audio.'.$format;
        $audioDir = dirname($asset->relative_path).'/audio';

        try {
            Storage::disk('public')->makeDirectory($audioDir);
            $stored = $file->storeAs($audioDir, $targetName, 'public');
            if (! $stored) {
                return response()->json([
                    'success' => false,
                    'error' => 'فشل حفظ الملف الصوتي.',
                ], 500);
            }

            $fullPath = Storage::disk('public')->path($stored);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : $file->getSize();
            $this->saveAudioFile($asset, $stored, $fileSize, $format);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف الصوتي بنجاح',
                'audio_url' => asset('storage/'.$stored),
            ]);
        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء رفع الملف: '.$e->getMessage(),
            ], 500);
        }
    }

    private function saveAudioFile(Asset $asset, $relativeAudioPath, $fileSize, ?string $format = null)
    {
        $format = $format ?? pathinfo($relativeAudioPath, PATHINFO_EXTENSION) ?: 'mp3';
        if ($format === 'mpeg') {
            $format = 'mp3';
        }

        try {
            AudioFile::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'format' => $format,
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
            Log::error('Failed to save audio file', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * تفعيل النشر (للاستخدام من النشر السريع عبر AJAX).
     */
    public function markPublished(Asset $asset)
    {
        try {
            $asset->is_publishable = true;
            $asset->scheduled_publish_at = null;
            $asset->published_at = $asset->published_at ?? now();
            $asset->save();

            Cache::forget('home_shorts');
            Cache::forget('home_stats');
            Cache::forget('home_speaker_names');
            Cache::forget('home_categories');
            Cache::forget('home_years');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'حدث خطأ أثناء تفعيل النشر: '.$e->getMessage()], 500);
        }
    }

    /**
     * تفعيل النشر لعدة فيديوهات (إجراء جماعي).
     */
    public function bulkPublish(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:assets,id']);
        $ids = $request->input('ids', []);
        $now = now();
        $updated = Asset::whereIn('id', $ids)->update([
            'is_publishable' => true,
            'scheduled_publish_at' => null,
            'published_at' => $now,
        ]);
        Cache::forget('home_shorts');
        Cache::forget('home_stats');
        Cache::forget('home_speaker_names');
        Cache::forget('home_categories');
        Cache::forget('home_years');

        return redirect()->back()->with('success', "تم تفعيل النشر لـ {$updated} فيديو.");
    }

    /**
     * إلغاء النشر لعدة فيديوهات (إجراء جماعي).
     */
    public function bulkUnpublish(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:assets,id']);
        $ids = $request->input('ids', []);
        $updated = Asset::whereIn('id', $ids)->update(['is_publishable' => false]);
        Cache::forget('home_shorts');
        Cache::forget('home_stats');
        Cache::forget('home_speaker_names');
        Cache::forget('home_categories');
        Cache::forget('home_years');

        return redirect()->back()->with('success', "تم إلغاء النشر لـ {$updated} فيديو.");
    }

    /**
     * دمج الفيديو: الإبقاء على سجل واحد (المختار)، نقل المسار النسبي الصحيح من المحددين إن وُجد، وحذف بقية السجلات المحددة.
     */
    public function merge(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:2',
            'ids.*' => 'integer|exists:assets,id',
            'keep_id' => 'required|integer|exists:assets,id',
        ]);

        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        $keepId = (int) $request->input('keep_id');

        if (! in_array($keepId, $ids, true)) {
            return redirect()->back()->with('error', 'السجل المختار للإبقاء عليه يجب أن يكون من المحددين.');
        }

        $keep = Asset::findOrFail($keepId);
        $others = Asset::whereIn('id', $ids)->where('id', '!=', $keepId)->get();

        // إذا كان السجل المحفوظ يعاني من مشكلة في المسار، نأخذ المسار الصحيح من أحد المحددين إن وُجد
        if ($keep->file_missing && $others->isNotEmpty()) {
            $withValidPath = $others->firstWhere('file_missing', false);
            if ($withValidPath) {
                $keep->relative_path = $withValidPath->relative_path;
                $keep->original_path = $withValidPath->original_path;
                $keep->file_missing = false;
                $keep->save();
            }
        }

        // حذف بقية السجلات المحددة (فقط من قاعدة البيانات)
        $deleted = Asset::whereIn('id', $ids)->where('id', '!=', $keepId)->delete();

        return redirect()->back()->with('success', "تم دمج الفيديو: الإبقاء على السجل #{$keepId} وحذف {$deleted} سجل.");
    }

    /**
     * حذف السجلات المحددة من قاعدة البيانات (إجراء جماعي).
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:assets,id',
        ]);

        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        $deleted = Asset::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "تم حذف {$deleted} سجل من قاعدة البيانات.");
    }

    /**
     * حذف جميع الفيديوهات غير المنشورة من قاعدة البيانات.
     */
    public function deleteAllUnpublished(Request $request)
    {
        if ($request->input('confirm') !== 'yes') {
            return redirect()->route('assets.index', ['preparing' => 1])
                ->with('error', 'يجب تأكيد الحذف أولاً.');
        }

        $query = Asset::where(function ($q) {
            $q->where('is_publishable', false)->orWhereNull('is_publishable');
        });

        $count = $query->count();
        if ($count === 0) {
            return redirect()->route('assets.index', ['preparing' => 1])
                ->with('info', 'لا توجد فيديوهات غير منشورة للحذف.');
        }

        try {
            $deleted = $query->delete();

            return redirect()->route('assets.index', ['preparing' => 1])
                ->with('success', "تم حذف {$deleted} فيديو غير منشور من قاعدة البيانات.");
        } catch (\Exception $e) {
            Log::error('Failed to delete unpublished assets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assets.index', ['preparing' => 1])
                ->with('error', 'فشل الحذف: '.$e->getMessage());
        }
    }

    /**
     * تغيير أسماء (عناوين) و/أو صور مصغرة لعدة حلقات دفعة واحدة.
     * body: titles[asset_id] => العنوان الجديد، thumbnails[asset_id] => ملف الصورة
     */
    public function bulkRenameTitles(Request $request)
    {
        $request->validate([
            'titles' => 'nullable|array',
            'titles.*' => 'nullable|string|max:255',
            'thumbnails' => 'nullable|array',
            'thumbnails.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $titleInputs = $request->input('titles', []);
        $thumbnailFiles = $request->file('thumbnails', []);
        $assetIds = array_unique(array_merge(
            array_map('intval', array_keys($titleInputs)),
            array_map('intval', array_keys($thumbnailFiles))
        ));

        $updatedTitles = 0;
        $updatedThumbnails = 0;
        $thumbnailErrors = [];

        foreach ($assetIds as $assetId) {
            if ($assetId <= 0) {
                continue;
            }
            $asset = Asset::find($assetId);
            if (! $asset) {
                continue;
            }

            $dirty = false;

            if (array_key_exists($assetId, $titleInputs) || array_key_exists((string) $assetId, $titleInputs)) {
                $newTitle = trim((string) ($titleInputs[$assetId] ?? $titleInputs[(string) $assetId] ?? ''));
                $currentTitle = trim((string) ($asset->title ?? ''));
                if ($newTitle !== $currentTitle) {
                    $asset->title = $newTitle !== '' ? $newTitle : null;
                    $dirty = true;
                    $updatedTitles++;
                }
            }

            $file = $thumbnailFiles[$assetId] ?? $thumbnailFiles[(string) $assetId] ?? null;
            if ($file && $file->isValid()) {
                $thumbnailPath = $this->storeAssetThumbnail($asset, $file);
                if ($thumbnailPath) {
                    $asset->thumbnail_path = $thumbnailPath;
                    $dirty = true;
                    $updatedThumbnails++;
                } else {
                    $thumbnailErrors[] = $assetId;
                }
            }

            if ($dirty) {
                $asset->save();
            }
        }

        if ($updatedTitles === 0 && $updatedThumbnails === 0) {
            $message = 'لم يتم تغيير أي اسم أو صورة — تأكد من كتابة أسماء جديدة أو اختيار صور.';
            if (! empty($thumbnailErrors)) {
                $message .= ' بعض الحلقات لم تُرفع صورها لأن الفيديو غير منقول إلى الموقع.';
            }

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('info', $message);
        }

        $parts = [];
        if ($updatedTitles > 0) {
            $parts[] = "أسماء {$updatedTitles} حلقة";
        }
        if ($updatedThumbnails > 0) {
            $parts[] = "صور {$updatedThumbnails} حلقة";
        }
        $message = 'تم تحديث '.implode(' و', $parts).' بنجاح.';
        if (! empty($thumbnailErrors)) {
            $message .= ' تعذّر رفع صورة لـ '.count($thumbnailErrors).' حلقة (يجب نقل الفيديو إلى الموقع أولاً).';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_titles' => $updatedTitles,
                'updated_thumbnails' => $updatedThumbnails,
                'thumbnail_errors' => $thumbnailErrors,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * تغيير إعدادات عامة لعدة فيديوهات: اسم المتحدث و/أو تصنيفات المحتوى و/أو السنة و/أو قائمة تشغيل و/أو إظهار الترجمة.
     */
    public function bulkUpdateSettings(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:assets,id',
            'scholar_id' => 'nullable|exists:scholars,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'gregorian_year' => ['nullable', 'string', 'size:4', 'regex:/^(19|20)\d{2}$/'],
            'playlist_id' => 'nullable|exists:playlists,id',
            'playlist_ids' => 'nullable|array',
            'playlist_ids.*' => 'integer|exists:playlists,id',
            'remove_from_playlist_id' => 'nullable|integer|exists:playlists,id',
            'show_translation' => 'nullable',
            'show_comments' => 'nullable',
        ]);

        $ids = array_values(array_map('intval', (array) $request->input('ids', [])));
        $applySpeaker = $request->boolean('apply_speaker');
        $applyCategories = $request->boolean('apply_categories');
        $applyGregorianYear = $request->boolean('apply_gregorian_year');
        $applyPlaylist = $request->boolean('apply_playlist');
        $applyRemovePlaylist = $request->boolean('apply_remove_playlist');
        $applyShowTranslation = $request->boolean('apply_show_translation');
        $applyShowComments = $request->boolean('apply_show_comments');

        $scholarId = null;
        if ($applySpeaker) {
            $scholarId = $request->filled('scholar_id') ? (int) $request->scholar_id : null;
        }

        $categoryIds = null;
        if ($applyCategories) {
            $categoryIds = array_values(array_map('intval', (array) $request->input('category_ids', [])));
        }

        $gregorianYear = null;
        if ($applyGregorianYear && $request->filled('gregorian_year')) {
            $y = (int) $request->gregorian_year;
            if ($y >= 1900 && $y <= 2100) {
                $gregorianYear = (string) $y;
            }
        } elseif ($applyGregorianYear) {
            $gregorianYear = ''; // مسح السنة
        }

        $playlistIds = [];
        if ($applyPlaylist) {
            $playlistIds = array_values(array_unique(array_map('intval', (array) $request->input('playlist_ids', []))));
            if (empty($playlistIds) && $request->filled('playlist_id')) {
                $playlistIds = [(int) $request->playlist_id];
            }
        }

        $removeFromPlaylistId = null;
        if ($applyRemovePlaylist && $request->filled('remove_from_playlist_id')) {
            $removeFromPlaylistId = (int) $request->remove_from_playlist_id;
        }

        $showTranslation = null;
        if ($applyShowTranslation) {
            $raw = $request->input('show_translation');
            $showTranslation = ($raw === '0' || $raw === 0 || $raw === false) ? false : true;
        }

        $showComments = null;
        if ($applyShowComments) {
            $raw = $request->input('show_comments');
            $showComments = ($raw === '0' || $raw === 0 || $raw === false) ? false : true;
        }

        if (empty($ids)) {
            return redirect()->back()->with('error', 'لم يتم تحديد أي فيديو.');
        }
        if (! $applySpeaker && ! $applyCategories && ! $applyGregorianYear && ! $applyPlaylist && ! $applyRemovePlaylist && ! $applyShowTranslation && ! $applyShowComments) {
            return redirect()->back()->with('error', 'فعّل تطبيق اسم المتحدث و/أو تصنيفات المحتوى و/أو السنة الميلادية و/أو قائمة التشغيل و/أو إظهار الترجمة و/أو إظهار التعليقات.');
        }
        if ($applyPlaylist && empty($playlistIds)) {
            return redirect()->back()->with('error', 'اختر قائمة تشغيل واحدة على الأقل عند تفعيل «إضافة المحدد إلى قائمة تشغيل».');
        }
        if ($applyPlaylist && $applyRemovePlaylist) {
            return redirect()->back()->with('error', 'لا يمكن تفعيل إضافة وإزالة قائمة التشغيل معاً — اختر إجراءً واحداً.');
        }

        $scholar = $scholarId ? \App\Models\Scholar::find($scholarId) : null;
        $speakerName = $scholar ? $scholar->name : null;

        $updated = 0;
        foreach ($ids as $assetId) {
            $asset = Asset::find($assetId);
            if (! $asset) {
                continue;
            }
            if ($applySpeaker) {
                $asset->scholar_id = $scholarId;
                $asset->speaker_name = $speakerName;
            }
            if ($applyCategories) {
                $asset->categories()->sync($categoryIds);
            }
            if ($applyGregorianYear) {
                $asset->gregorian_year = $gregorianYear;
            }
            if ($applyShowTranslation && $showTranslation !== null) {
                $asset->show_translation = $showTranslation;
            }
            if ($applyShowComments && $showComments !== null) {
                $asset->show_comments = $showComments;
            }
            if ($applySpeaker || $applyGregorianYear || $applyShowTranslation || $applyShowComments) {
                $asset->save();
            }
            $updated++;
        }

        $playlistAdded = 0;
        $playlistRemoved = 0;
        if ($applyPlaylist && ! empty($playlistIds)) {
            foreach ($playlistIds as $playlistId) {
                $playlist = Playlist::find($playlistId);
                if (! $playlist) {
                    continue;
                }
                $maxOrder = (int) DB::table('asset_playlist')->where('playlist_id', $playlistId)->max('order');
                $existingIds = $playlist->assets()->pluck('assets.id')->toArray();
                foreach ($ids as $assetId) {
                    if (in_array($assetId, $existingIds, true)) {
                        continue;
                    }
                    $maxOrder++;
                    DB::table('asset_playlist')->insert([
                        'playlist_id' => $playlistId,
                        'asset_id' => $assetId,
                        'order' => $maxOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $playlistAdded++;
                }
            }
        }

        if ($applyRemovePlaylist) {
            $removeQuery = DB::table('asset_playlist')->whereIn('asset_id', $ids);
            if ($removeFromPlaylistId) {
                $removeQuery->where('playlist_id', $removeFromPlaylistId);
            }
            $playlistRemoved = $removeQuery->delete();
        }

        Cache::forget('home_speaker_names');
        Cache::forget('home_categories');
        Cache::forget('home_stats');
        Cache::forget('home_shorts');
        Cache::forget('home_years');

        $msg = 'تم تطبيق الإعدادات على '.$updated.' فيديو.';
        if ($playlistAdded > 0) {
            $msg .= ' تمت إضافة '.$playlistAdded.' ربطاً بقوائم التشغيل المختارة.';
        }
        if ($playlistRemoved > 0) {
            if ($removeFromPlaylistId) {
                $plTitle = Playlist::find($removeFromPlaylistId)?->title;
                $msg .= ' تمت إزالة '.$playlistRemoved.' فيديو من قائمة التشغيل'.($plTitle ? ' «'.$plTitle.'»' : '').'.';
            } else {
                $msg .= ' تم فصل '.$playlistRemoved.' ربطاً من قوائم التشغيل.';
            }
        } elseif ($applyRemovePlaylist) {
            $msg .= ' لم يكن المحدد مرتبطاً بأي قائمة تشغيل.';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function togglePublishable(Asset $asset)
    {
        try {
            $asset->is_publishable = ! $asset->is_publishable;
            if ($asset->is_publishable) {
                $asset->scheduled_publish_at = null;
                $asset->published_at = $asset->published_at ?? now();
            }
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
                ->with('error', 'حدث خطأ أثناء تحديث حالة النشر: '.$e->getMessage());
        }
    }

    /**
     * تبديل حالة "مميز": الفيديو المميز يظهر في أول ٨ فيديوهات بالصفحة الرئيسية.
     */
    public function toggleFeatured(Asset $asset)
    {
        try {
            $asset->is_featured = ! $asset->is_featured;
            $asset->save();

            Cache::forget('home_shorts');
            Cache::forget('home_stats');
            Cache::forget('home_speaker_names');
            Cache::forget('home_categories');
            Cache::forget('home_years');

            $message = $asset->is_featured
                ? 'تم تمييز الفيديو — سيظهر في أول ٨ فيديوهات بالصفحة الرئيسية'
                : 'تم إلغاء تمييز الفيديو';

            return redirect()->route('assets.show', $asset)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    /**
     * تحديث ترتيب عرض الفيديو المميز في الصفحة الرئيسية (الأصغر يظهر أولاً، مثلاً 1 ثم 2 ثم 3).
     */
    public function updateFeaturedOrder(Request $request, Asset $asset)
    {
        $request->validate([
            'featured_order' => 'nullable|integer|min:0',
        ]);

        try {
            $asset->featured_order = $request->filled('featured_order') ? (int) $request->input('featured_order') : null;
            $asset->save();

            Cache::forget('home_shorts');
            Cache::forget('home_stats');
            Cache::forget('home_speaker_names');
            Cache::forget('home_categories');
            Cache::forget('home_years');

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم حفظ ترتيب العرض في المميزة');
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    /**
     * جدولة نشر الفيديو (اليوم والوقت). عند حلول الموعد يُفعّل النشر تلقائياً عبر أمر PublishScheduledAssets.
     */
    public function schedulePublish(Request $request, Asset $asset)
    {
        $request->validate([
            'scheduled_at' => 'nullable|string|max:50',
            'clear_schedule' => 'nullable|boolean',
        ]);

        try {
            if ($request->boolean('clear_schedule') || $request->input('scheduled_at') === '' || $request->input('scheduled_at') === null) {
                $asset->scheduled_publish_at = null;
                $asset->save();

                return redirect()->route('assets.show', $asset)
                    ->with('success', 'تم إلغاء جدولة النشر');
            }

            $scheduledAt = $request->input('scheduled_at');
            $tz = config('app.timezone', 'UTC');
            $parsed = \Carbon\Carbon::parse($scheduledAt, $tz);
            if ($parsed->isPast()) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'لا يمكن جدولة النشر في وقت ماضي. اختر تاريخاً ووقتاً مستقبلياً.');
            }

            $asset->scheduled_publish_at = $parsed;
            $asset->save();

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم حفظ جدولة النشر: '.$parsed->format('Y-m-d H:i').' ('.$tz.')');
        } catch (\Exception $e) {
            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ أثناء حفظ الجدولة: '.$e->getMessage());
        }
    }

    public function uploadThumbnail(Asset $asset, Request $request)
    {
        $request->validate([
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $thumbnailPath = $this->storeAssetThumbnail($asset, $request->file('thumbnail'));
            if (! $thumbnailPath) {
                $error = 'يجب نقل الفيديو إلى الموقع أولاً';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'error' => $error], 400);
                }

                return redirect()->route('assets.show', $asset)->with('error', $error);
            }

            $asset->thumbnail_path = $thumbnailPath;
            $asset->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم رفع الصورة المصغرة بنجاح',
                    'thumbnail_url' => asset('storage/'.$thumbnailPath),
                ]);
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', 'تم رفع الصورة المصغرة بنجاح');
        } catch (\Exception $e) {
            Log::error('Thumbnail upload error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            $error = 'حدث خطأ أثناء رفع الصورة المصغرة: '.$e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $error], 500);
            }

            return redirect()->route('assets.show', $asset)->with('error', $error);
        }
    }

    /**
     * @return string|null المسار النسبي على قرص public أو null إذا لم يكن الفيديو في storage
     */
    private function storeAssetThumbnail(Asset $asset, $file): ?string
    {
        if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
            return null;
        }

        $videoDir = dirname($asset->relative_path);
        $thumbnailDir = $videoDir.'/thumbnails';
        Storage::disk('public')->makeDirectory($thumbnailDir);

        return $file->store($thumbnailDir, 'public');
    }

    public function uploadCover(Asset $asset, Request $request)
    {
        $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                $videoDir = dirname($asset->relative_path);
                $coverDir = $videoDir.'/covers';

                Storage::disk('public')->makeDirectory($coverDir);

                $coverPath = $request->file('cover')->store($coverDir, 'public');

                $asset->cover_path = $coverPath;
                $asset->thumbnail_path = $coverPath;
                $asset->save();

                return redirect()->route('assets.show', $asset)
                    ->with('success', 'تم رفع صورة الغلاف بنجاح');
            } else {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'يجب نقل الفيديو إلى الموقع أولاً');
            }
        } catch (\Exception $e) {
            Log::error('Cover upload error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ أثناء رفع صورة الغلاف: '.$e->getMessage());
        }
    }

    public function captureRandomCover(Asset $asset)
    {
        try {
            if (! $asset->relative_path || strpos($asset->relative_path, 'assets/') !== 0) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'يجب نقل الفيديو إلى الموقع أولاً');
            }

            if (! Storage::disk('public')->exists($asset->relative_path)) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'ملف الفيديو غير موجود على الخادم');
            }

            $ffmpegPath = $this->resolveFfmpegPath();
            if (! $ffmpegPath) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'FFmpeg غير مثبت على الخادم — لا يمكن أخذ لقطة من الفيديو');
            }

            $videoPath = Storage::disk('public')->path($asset->relative_path);
            $displayMeta = $this->extractVideoMetadata($videoPath);
            $targetWidth = (int) ($displayMeta['width'] ?? $asset->width ?? 0);
            $targetHeight = (int) ($displayMeta['height'] ?? $asset->height ?? 0);
            $rotation = (int) ($displayMeta['rotation'] ?? 0);

            if ($targetWidth <= 0 || $targetHeight <= 0) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'تعذر تحديد أبعاد الفيديو. استخدم «إعادة استخراج بيانات الفيديو» ثم حاول مرة أخرى.');
            }

            $duration = (float) ($displayMeta['duration_seconds'] ?? $asset->duration_seconds ?? 0);
            if ($duration <= 0) {
                $duration = $this->probeVideoDurationSeconds($videoPath) ?? 0;
            }
            if ($duration <= 0) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'تعذر تحديد مدة الفيديو لاختيار لقطة عشوائية');
            }

            $maxSecond = max(0, (int) floor($duration) - 1);
            $randomSecond = $maxSecond > 0 ? random_int(0, $maxSecond) : 0;

            $videoDir = dirname($asset->relative_path);
            $coverDir = $videoDir.'/covers';
            Storage::disk('public')->makeDirectory($coverDir);

            $fileName = 'cover_frame_'.time().'_'.$randomSecond.'s.jpg';
            $coverRelativePath = $coverDir.'/'.$fileName;
            $outputPath = Storage::disk('public')->path($coverRelativePath);

            $videoFilter = $this->buildCoverFrameVideoFilter($targetWidth, $targetHeight, $rotation);

            // -noautorotate + transpose يدوي لتفادي دوران مزدوج؛ scale لأبعاد العرض من ffprobe
            $cmd = escapeshellarg($ffmpegPath)
                .' -y -noautorotate -i '.escapeshellarg($videoPath)
                .' -ss '.escapeshellarg((string) $randomSecond)
                .' -frames:v 1'
                .' -vf '.escapeshellarg($videoFilter)
                .' -q:v 2 '
                .escapeshellarg($outputPath)
                .' 2>&1';

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0 || ! is_file($outputPath) || filesize($outputPath) < 100) {
                if (is_file($outputPath)) {
                    @unlink($outputPath);
                }
                Log::error('Random cover capture failed', [
                    'asset_id' => $asset->id,
                    'exit_code' => $exitCode,
                    'output' => implode("\n", array_slice($output, -20)),
                ]);

                return redirect()->route('assets.show', $asset)
                    ->with('error', 'فشل استخراج اللقطة من الفيديو');
            }

            $asset->cover_path = $coverRelativePath;
            $asset->thumbnail_path = $coverRelativePath;
            if (! $asset->width || ! $asset->height) {
                $asset->width = $targetWidth;
                $asset->height = $targetHeight;
            }
            $asset->save();

            $minute = intdiv($randomSecond, 60);
            $second = $randomSecond % 60;

            return redirect()->route('assets.show', $asset)
                ->with('success', sprintf(
                    'تم تعيين لقطة عشوائية كصورة غلاف (%d×%d) عند %d:%02d',
                    $targetWidth,
                    $targetHeight,
                    $minute,
                    $second
                ));
        } catch (\Exception $e) {
            Log::error('Random cover capture error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('error', 'حدث خطأ أثناء أخذ اللقطة: '.$e->getMessage());
        }
    }

    /**
     * فلتر ffmpeg لإطار الغلاف: دوران (إن وُجد) ثم scale لأبعاد عرض الفيديو بالضبط.
     */
    private function buildCoverFrameVideoFilter(int $targetWidth, int $targetHeight, int $rotation): string
    {
        $filters = [];
        $rotation = (($rotation % 360) + 360) % 360;

        if ($rotation === 90) {
            $filters[] = 'transpose=1';
        } elseif ($rotation === 270) {
            $filters[] = 'transpose=2';
        } elseif ($rotation === 180) {
            $filters[] = 'hflip,vflip';
        }

        $filters[] = 'scale='.$targetWidth.':'.$targetHeight.':force_original_aspect_ratio=disable:flags=lanczos';
        $filters[] = 'setsar=1';

        return implode(',', $filters);
    }

    private function resolveFfmpegPath(): ?string
    {
        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            trim(shell_exec('which ffmpeg 2>/dev/null') ?: ''),
        ];
        foreach ($possiblePaths as $path) {
            if (! empty($path) && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolveFfprobePath(): ?string
    {
        $ffmpegPath = $this->resolveFfmpegPath();
        if ($ffmpegPath) {
            $nextToFfmpeg = dirname($ffmpegPath).'/ffprobe';
            if (is_executable($nextToFfmpeg)) {
                return $nextToFfmpeg;
            }
        }

        $possiblePaths = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            '/opt/homebrew/bin/ffprobe',
            trim(shell_exec('which ffprobe 2>/dev/null') ?: ''),
        ];
        foreach ($possiblePaths as $path) {
            if (! empty($path) && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function probeVideoDurationSeconds(string $videoPath): ?float
    {
        $ffprobePath = $this->resolveFfprobePath();
        if (! $ffprobePath) {
            return null;
        }

        $cmd = escapeshellarg($ffprobePath)
            .' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            .escapeshellarg($videoPath).' 2>/dev/null';
        $output = trim(shell_exec($cmd) ?: '');
        if ($output === '' || ! is_numeric($output)) {
            return null;
        }

        return (float) $output;
    }

    public function analytics()
    {
        // تحليل الشيوخ
        $speakers = Asset::select('relative_path', 'file_name', 'orientation', 'duration_seconds', 'size_bytes')
            ->whereNotNull('relative_path')
            ->get()
            ->map(function ($asset) {
                return [
                    'speaker' => $asset->speaker_name,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function ($item) {
                return ! empty($item['speaker']);
            })
            ->groupBy('speaker')
            ->map(function ($items, $speaker) {
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
            ->map(function ($asset) {
                return [
                    'category' => $asset->category,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function ($item) {
                return ! empty($item['category']);
            })
            ->groupBy('category')
            ->map(function ($items, $category) {
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
            ->map(function ($asset) {
                return [
                    'year' => $asset->year,
                    'gregorian_year' => $asset->gregorian_year,
                    'orientation' => $asset->orientation,
                    'duration' => $asset->duration_seconds,
                    'size' => $asset->size_bytes,
                ];
            })
            ->filter(function ($item) {
                return ! empty($item['year']);
            })
            ->groupBy('year')
            ->map(function ($items, $year) {
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
            $captionDir = $videoDir.'/captions';

            // إنشاء فولدر captions إذا لم يكن موجوداً
            Storage::disk('public')->makeDirectory($captionDir);

            // تعيين الصلاحيات الصحيحة للمجلد والملفات (775 للوصول من العام)
            $captionFullPath = Storage::disk('public')->path($captionDir);
            chmod($captionFullPath, 0775);
            @chown($captionFullPath, 'www-data');
            shell_exec('chmod -R 775 '.escapeshellarg($captionFullPath).' 2>/dev/null');
            shell_exec('chown -R www-data:www-data '.escapeshellarg($captionFullPath).' 2>/dev/null');

            $movedFiles = [];

            // نقل ملف JSON
            if ($jsonPath && file_exists($jsonPath)) {
                $jsonContent = file_get_contents($jsonPath);
                $jsonFileName = basename($jsonPath);
                $newJsonPath = $captionDir.'/'.$jsonFileName;
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
                $newTxtPath = $captionDir.'/'.$txtFileName;
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
                $newTimedTxtPath = $captionDir.'/'.$timedTxtFileName;
                Storage::disk('public')->put($newTimedTxtPath, $timedTxtContent);
                // تعيين الصلاحيات للملف
                $newTimedTxtFullPath = Storage::disk('public')->path($newTimedTxtPath);
                chmod($newTimedTxtFullPath, 0664);
                @chown($newTimedTxtFullPath, 'www-data');
                $movedFiles[] = $timedTxtFileName;
            }

            // التأكد من الصلاحيات النهائية لجميع الملفات
            shell_exec('chmod -R 775 '.escapeshellarg($captionFullPath).' 2>/dev/null');
            shell_exec('chown -R www-data:www-data '.escapeshellarg($captionFullPath).' 2>/dev/null');

            if (! empty($movedFiles)) {
                Log::info('Moved caption files to storage', [
                    'asset_id' => $asset->id,
                    'caption_dir' => $captionDir,
                    'files' => $movedFiles,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to move caption files', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
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
                if (! Storage::disk('public')->exists($newPath)) {
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
                            $oldCaptionsDir = $oldDir.'/captions';
                            $newCaptionsDir = $newDir.'/captions';
                            if (is_dir($oldCaptionsDir)) {
                                if (! is_dir($newCaptionsDir)) {
                                    mkdir($newCaptionsDir, 0775, true);
                                }
                                shell_exec('cp -r '.escapeshellarg($oldCaptionsDir).'/* '.escapeshellarg($newCaptionsDir).'/ 2>/dev/null');
                            }

                            $oldHlsDir = $oldDir.'/hls';
                            $newHlsDir = $newDir.'/hls';
                            if (is_dir($oldHlsDir)) {
                                if (! is_dir($newHlsDir)) {
                                    mkdir($newHlsDir, 0775, true);
                                }
                                shell_exec('cp -r '.escapeshellarg($oldHlsDir).'/* '.escapeshellarg($newHlsDir).'/ 2>/dev/null');
                            }

                            // تعيين الصلاحيات
                            shell_exec('chmod -R 775 '.escapeshellarg($newDir).' 2>/dev/null');
                            shell_exec('chown -R www-data:www-data '.escapeshellarg($newDir).' 2>/dev/null');

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

    /**
     * استخراج النسخة النصية فقط للمحتوى (بدون توقيتات) لإرسالها إلى DeepSeek.
     * نفضّل transcription_plain إن وُجدت؛ وإلا من المقاطع أو من transcription بعد التنقية.
     */
    private function getPlainTextForAnalysis(Asset $asset): string
    {
        // النسخة المنقاة المحفوظة عند الاستخراج/الحفظ — نستخدمها مباشرة لـ DeepSeek
        $plain = $asset->transcription_plain ?? '';
        if (trim((string) $plain) !== '') {
            return trim((string) $plain);
        }

        // إن وُجد ملف JSON للمقاطع، نستخدم نصوص الجمل فقط (بدون أي توقيت)
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            $videoDir = dirname($asset->relative_path);
            $captionDir = $videoDir.'/captions';
            $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
            $jsonPath = storage_path('app/public/'.$captionDir.'/'.$baseName.'.json');

            if (file_exists($jsonPath)) {
                $jsonContent = @file_get_contents($jsonPath);
                if ($jsonContent !== false) {
                    $data = json_decode($jsonContent, true);
                    if (! empty($data['segments']) && is_array($data['segments'])) {
                        $texts = [];
                        foreach ($data['segments'] as $seg) {
                            $t = isset($seg['text']) ? trim((string) $seg['text']) : '';
                            if ($t !== '') {
                                $texts[] = $t;
                            }
                        }
                        if (! empty($texts)) {
                            return implode(' ', $texts);
                        }
                    }
                }
            }
        }

        // إن لم يوجد مقاطع، نستخدم المحتوى النصي المخزن بعد تنقيته من التوقيتات
        $raw = $asset->transcription ?? '';

        return $this->stripTimestampsFromTranscription($raw);
    }

    /**
     * تنقية النص من التوقيتات (VTT, SRT, أو تنسيق [00:00]) قبل إرساله للتحليل.
     */
    private function stripTimestampsFromTranscription(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $cleaned = [];

        foreach ($lines as $line) {
            $original = $line;
            $line = trim($line);

            // حذف السطور الفارغة (نحتفظ بها كمسافة واحدة لاحقاً)
            if ($line === '') {
                continue;
            }

            // حذف سطر التوقيت VTT/SRT: 00:00:01.000 --> 00:00:05.000 أو 00:00:00,000 --> ...
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?[.,]\d{2,3}\s*-->\s*\d{1,2}:\d{2}(:\d{2})?[.,]\d{2,3}\s*$/', $line)) {
                continue;
            }

            // حذف السطر إذا كان رقماً فقط (رقم المقطع في SRT)
            if (preg_match('/^\d+\s*$/', $line)) {
                continue;
            }

            // إزالة التوقيت في بداية السطر مثل: 00:00:01.000 النص أو 0:00 النص
            $line = preg_replace('/^\d{1,2}:\d{2}(:\d{2})?[.,]?\d*\s+/u', '', $line);

            // إزالة التوقيت بين قوسين معقودين أو عاديين: [00:01] أو [0:00:05.500] أو (00:01:30)
            $line = preg_replace('/\[\d{1,2}:\d{2}(:\d{2})?([.,]\d+)?\]/u', '', $line);
            $line = preg_replace('/\(\d{1,2}:\d{2}(:\d{2})?([.,]\d+)?\)/u', '', $line);

            // إزالة توقيت بصيغة 00:00:00.000 متبقي في السطر (نادر في الكلام العادي)
            $line = preg_replace('/\d{1,2}:\d{2}:\d{2}[.,]\d{2,3}/u', '', $line);

            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line !== '') {
                $cleaned[] = $line;
            }
        }

        return implode("\n", $cleaned);
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
        if (! empty($categoryScores)) {
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
                    if (! Storage::disk('public')->exists($newPath)) {
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
                                $oldCaptionsDir = $oldDir.'/captions';
                                $newCaptionsDir = $newDir.'/captions';
                                if (is_dir($oldCaptionsDir)) {
                                    if (! is_dir($newCaptionsDir)) {
                                        mkdir($newCaptionsDir, 0775, true);
                                    }
                                    shell_exec('cp -r '.escapeshellarg($oldCaptionsDir).'/* '.escapeshellarg($newCaptionsDir).'/ 2>/dev/null');
                                }

                                $oldHlsDir = $oldDir.'/hls';
                                $newHlsDir = $newDir.'/hls';
                                if (is_dir($oldHlsDir)) {
                                    if (! is_dir($newHlsDir)) {
                                        mkdir($newHlsDir, 0775, true);
                                    }
                                    shell_exec('cp -r '.escapeshellarg($oldHlsDir).'/* '.escapeshellarg($newHlsDir).'/ 2>/dev/null');
                                }

                                // تعيين الصلاحيات
                                shell_exec('chmod -R 775 '.escapeshellarg($newDir).' 2>/dev/null');
                                shell_exec('chown -R www-data:www-data '.escapeshellarg($newDir).' 2>/dev/null');

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
        if (! $seconds) {
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

    /** تنسيق المدة للإحصائيات (مثل: 45 د، 2س 30د) */
    private function formatDurationForStats(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}س {$minutes}د" : "{$hours}س";
        }

        return "{$minutes} د";
    }

    public function scanFolder(Request $request)
    {
        $storagePublic = storage_path('app/public');
        $videoExtensions = ['mp4', 'mov', 'mkv', 'm4v', 'avi', 'webm', 'mpg', 'mpeg', 'wmv', 'flv', '3gp'];
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        $processed = 0;
        $files = [];
        $scanFolderNames = [];
        $validPrefixes = [];
        $redirectQuery = request()->only('view', 'path');

        // رفع حد الوقت والذاكرة لضمان معالجة كل الملفات حتى لو استغرق المسح وقتاً
        set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            // مسح المجلد المفتوح فقط (عند التصفح بالمجلدات) — استخدام المسار الفعلي و RecursiveDirectoryIterator لضمان ظهور الفيديوهات حتى مع الأسماء العربية
            if ($request->filled('scan_path')) {
                $scanPathInput = trim(str_replace('\\', '/', (string) $request->get('scan_path')), '/');
                if ($scanPathInput === '' || str_contains($scanPathInput, '..')) {
                    return redirect()->route('assets.index', $redirectQuery)->with('error', 'مسار المسح غير صالح.');
                }
                if (! str_starts_with($scanPathInput, '2025') && ! str_starts_with($scanPathInput, 'videos')) {
                    return redirect()->route('assets.index', $redirectQuery)->with('error', 'المسح مسموح فقط لمجلدي 2025 أو videos.');
                }
                $fullScanPath = $storagePublic.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $scanPathInput);
                $dirToScan = null;
                if (is_dir($fullScanPath)) {
                    $dirToScan = $fullScanPath;
                } elseif (is_file($fullScanPath)) {
                    $dirToScan = dirname($fullScanPath);
                    $scanPathInputForDir = trim(str_replace([$storagePublic.DIRECTORY_SEPARATOR, $storagePublic], '', $dirToScan), DIRECTORY_SEPARATOR);
                    $scanPathInputForDir = str_replace('\\', '/', $scanPathInputForDir);
                    if ($scanPathInputForDir !== '' && ! str_contains($scanPathInputForDir, '..') && (str_starts_with($scanPathInputForDir, '2025') || str_starts_with($scanPathInputForDir, 'videos'))) {
                        $scanPathInput = $scanPathInputForDir;
                    }
                }
                if ($dirToScan === null || ! is_dir($dirToScan)) {
                    return redirect()->route('assets.index', $redirectQuery)->with('error', 'المجلد غير موجود: '.$scanPathInput);
                }
                try {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dirToScan, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
                        \RecursiveIteratorIterator::SELF_FIRST,
                        \RecursiveIteratorIterator::CATCH_GET_CHILD
                    );
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $extension = strtolower($file->getExtension());
                            if (in_array($extension, $videoExtensions)) {
                                $files[] = $file->getPathname();
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Scan folder list failed', ['path' => $scanPathInput, 'full_path' => $dirToScan, 'error' => $e->getMessage()]);
                }
                $scanFolderNames = [$scanPathInput];
                $validPrefixes = [$scanPathInput.'/'];
            } else {
                // مسح المجلدين 2025 و videos (السلوك الافتراضي)
                $scanFolderNames = ['2025', 'videos'];
                foreach ($scanFolderNames as $folderName) {
                    $scanPath = $storagePublic.DIRECTORY_SEPARATOR.$folderName;
                    if (! is_dir($scanPath)) {
                        Log::info('Scan folder skipped (not found)', ['path' => $scanPath]);

                        continue;
                    }
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($scanPath, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
                        \RecursiveIteratorIterator::SELF_FIRST,
                        \RecursiveIteratorIterator::CATCH_GET_CHILD
                    );
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $extension = strtolower($file->getExtension());
                            if (in_array($extension, $videoExtensions)) {
                                $files[] = $file->getPathname();
                            }
                        }
                    }
                }
                $validPrefixes = ['2025/', 'videos/'];
            }

            $totalFiles = count($files);
            if ($totalFiles === 0) {
                $msg = $request->filled('scan_path') ? 'لم يُعثر على أي ملف فيديو في هذا المجلد.' : 'لم يُعثر على أي ملف فيديو في المجلدات 2025 أو videos.';

                return redirect()->route('assets.index', $redirectQuery)->with('info', $msg);
            }

            Log::info('Starting folder scan', ['folders' => $scanFolderNames, 'total_files' => $totalFiles]);

            foreach ($files as $filePath) {
                try {
                    $relativePath = str_replace($storagePublic, '', $filePath);
                    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

                    $hasValidPrefix = false;
                    foreach ($validPrefixes as $p) {
                        if (strpos($relativePath, $p) === 0) {
                            $hasValidPrefix = true;
                            break;
                        }
                    }
                    if (! $hasValidPrefix) {
                        $relativePath = ($scanFolderNames[0] ?? '2025').'/'.basename($filePath);
                    }

                    // التحقق من عدم تكرار الملف: المسار النسبي/الأصلي الكامل (relative_path أو original_path)
                    $pathNorm = trim(str_replace('\\', '/', $relativePath), '/');
                    $existingAsset = Asset::where(function ($q) use ($pathNorm) {
                        $q->where('relative_path', $pathNorm)
                            ->orWhere('original_path', $pathNorm);
                    })->first();
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
                        if (abs($ratio - (9 / 16)) < 0.05) {
                            $aspectRatio = '9:16';
                        } elseif (abs($ratio - (16 / 9)) < 0.05) {
                            $aspectRatio = '16:9';
                        } elseif (abs($ratio - 1) < 0.05) {
                            $aspectRatio = '1:1';
                        } else {
                            $aspectRatio = $width.':'.$height;
                        }
                    } else {
                        // إذا لم يتم استخراج الأبعاد، نحاول طريقة بديلة
                        Log::warning('Video dimensions not extracted, trying alternative method', [
                            'file' => $filePath,
                            'width' => $width,
                            'height' => $height,
                        ]);
                    }

                    // استخراج السنة الميلادية من المسار
                    $gregorianYear = $this->extractGregorianYear($relativePath);

                    // إنشاء السجل: المسار النسبي/الأصلي الكامل موحّد (بدون شرطات في البداية أو النهاية)
                    // ملاحظة: لا يتم استخراج اسم المتحدث من المسار - يجب تحديده يدوياً
                    $asset = Asset::create([
                        'file_name' => $fileInfo['file_name'],
                        'relative_path' => $pathNorm,
                        'original_path' => $pathNorm,
                        'extension' => $fileInfo['extension'],
                        'video_codec' => $videoMeta['video_codec'] ?? null,
                        'size_bytes' => $fileInfo['size_bytes'],
                        'modified_at' => $fileInfo['modified_at'],
                        'width' => $width, // استخدام المتغيرات المحلية المحسوبة
                        'height' => $height, // استخدام المتغيرات المحلية المحسوبة
                        'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                        'orientation' => $orientation,
                        'aspect_ratio' => $aspectRatio,
                        'speaker_name' => null, // لا يتم استخراج اسم المتحدث من المسار
                        'gregorian_year' => $gregorianYear,
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

            // Sync: مطابقة المسار النسبي/الأصلي في قاعدة البيانات مع الملفات الفعلية (تحديث file_missing)
            $missingAssetIds = [];
            foreach ($validPrefixes as $prefix) {
                $assetsInScope = Asset::where(function ($q) use ($prefix) {
                    $q->where('relative_path', 'like', $prefix.'%')
                        ->orWhere('original_path', 'like', $prefix.'%');
                })->get();
                foreach ($assetsInScope as $asset) {
                    $pathToCheck = trim(str_replace('\\', '/', (string) ($asset->original_path ?? $asset->relative_path ?? '')), '/');
                    $exists = $pathToCheck !== '' && Storage::disk('public')->exists($pathToCheck);
                    if ($asset->file_missing !== ! $exists) {
                        $asset->file_missing = ! $exists;
                        $asset->save();
                    }
                    if (! $exists) {
                        $missingAssetIds[] = $asset->id;
                    }
                }
            }
            $missingAssetIds = array_values(array_unique($missingAssetIds));
            if (count($missingAssetIds) > 0) {
                session(['sync_missing_asset_ids' => $missingAssetIds]);
            }

            $message = "تم الانتهاء من المسح: تم فحص {$totalFiles} ملف، إضافة {$inserted} ملف جديد، {$errors} أخطاء";
            Log::info('Folder scan completed', [
                'total_files_found' => $totalFiles,
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors,
                'processed' => $processed,
                'missing_count' => count($missingAssetIds),
            ]);

            $redirect = redirect()->route('assets.index', request()->only('view', 'path'))->with('success', $message);
            if (count($missingAssetIds) > 0) {
                $redirect->with('sync_missing_count', count($missingAssetIds))
                    ->with('warning', 'يوجد '.count($missingAssetIds).' ملف في قاعدة البيانات غير موجودة في المجلد. يمكنك حذف هذه السجلات من قاعدة البيانات من الزر أدناه.');
            }

            return $redirect;

        } catch (\Exception $e) {
            Log::error('Folder scan failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assets.index')
                ->with('error', 'فشل المسح: '.$e->getMessage());
        }
    }

    /**
     * حذف جميع سجلات الفيديو من قاعدة البيانات.
     */
    public function truncateAll(Request $request)
    {
        if (! $request->has('confirm') || $request->input('confirm') !== 'yes') {
            return redirect()->route('assets.index', request()->only('view', 'path'))
                ->with('error', 'يجب تأكيد الحذف أولاً.');
        }

        $count = Asset::count();

        try {
            // حذف الجداول المرتبطة أولاً (متوافق مع SQLite و MySQL)
            DB::table('hls_versions')->delete();
            DB::table('audio_files')->delete();
            DB::table('asset_optimized_versions')->delete();
            DB::table('likes')->delete();
            DB::table('favorites')->delete();
            DB::table('comments')->delete();
            DB::table('asset_playlist')->delete();
            DB::table('asset_category')->delete();
            Asset::query()->delete();

            session()->forget('sync_missing_asset_ids');

            return redirect()->route('assets.index', request()->only('view', 'path'))
                ->with('success', "تم حذف جميع سجلات الفيديو بنجاح ({$count} سجل).");
        } catch (\Exception $e) {
            Log::error('Failed to truncate assets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assets.index', request()->only('view', 'path'))
                ->with('error', 'فشل حذف السجلات: '.$e->getMessage());
        }
    }

    /**
     * حذف سجلات الأصول المحذوفة من المجلد (المحفوظة في الجلسة بعد مسح المجلد).
     */
    public function removeMissingFromSync(Request $request)
    {
        $ids = session('sync_missing_asset_ids', []);
        if (empty($ids)) {
            return redirect()->route('assets.index', request()->only('view', 'path'))
                ->with('info', 'لا توجد سجلات محذوفة من المجلد لحذفها.');
        }
        $ids = array_values(array_map('intval', $ids));
        $deleted = Asset::whereIn('id', $ids)->delete();
        session()->forget('sync_missing_asset_ids');

        return redirect()->route('assets.index', request()->only('view', 'path'))
            ->with('success', 'تم حذف '.$deleted.' سجل من قاعدة البيانات.');
    }

    public function updateFileMetadata(Request $request)
    {
        $request->validate([
            'original_path' => 'required|string',
        ]);

        $originalPath = $request->input('original_path');

        // البحث عن الملف باستخدام original_path
        $asset = Asset::where('original_path', $originalPath)->first();

        if (! $asset) {
            return redirect()->route('assets.index')
                ->with('error', 'الملف غير موجود في قاعدة البيانات: '.$originalPath);
        }

        // التحقق من وجود الملف في النظام
        if (! file_exists($originalPath)) {
            return redirect()->route('assets.index')
                ->with('error', 'الملف غير موجود في المسار المحدد: '.$originalPath);
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
                if (abs($ratio - (9 / 16)) < 0.05) {
                    $aspectRatio = '9:16';
                } elseif (abs($ratio - (16 / 9)) < 0.05) {
                    $aspectRatio = '16:9';
                } elseif (abs($ratio - 1) < 0.05) {
                    $aspectRatio = '1:1';
                } else {
                    $aspectRatio = $width.':'.$height;
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
                ->with('error', 'فشل تحديث بيانات الملف: '.$e->getMessage());
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
                    if (! file_exists($originalPath)) {
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
                        if (abs($ratio - (9 / 16)) < 0.05) {
                            $aspectRatio = '9:16';
                        } elseif (abs($ratio - (16 / 9)) < 0.05) {
                            $aspectRatio = '16:9';
                        } elseif (abs($ratio - 1) < 0.05) {
                            $aspectRatio = '1:1';
                        } else {
                            $aspectRatio = $width.':'.$height;
                        }
                    }

                    // تحديث البيانات
                    $asset->update([
                        'size_bytes' => $fileInfo['size_bytes'],
                        'modified_at' => $fileInfo['modified_at'],
                        'width' => $width,
                        'height' => $height,
                        'duration_seconds' => $videoMeta['duration_seconds'] ?? null,
                        'video_codec' => $videoMeta['video_codec'] ?? $asset->video_codec,
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
                ->with('error', 'فشل تحديث بيانات الملفات: '.$e->getMessage());
        }
    }

    /**
     * فحص كوديكات الفيديوهات المنشورة وتحديد غير المتوافق مع متصفحات الويب (مثل HEVC).
     */
    public function scanWebCompatibility(Request $request)
    {
        $limit = max(1, min(500, (int) $request->input('limit', 200)));
        $onlyMissing = ! $request->boolean('rescan_all');

        $query = Asset::query()
            ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
            ->where('is_publishable', true)
            ->whereNotNull('relative_path')
            ->where('relative_path', 'like', 'assets/%')
            ->orderBy('id');

        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->whereNull('video_codec')->orWhere('video_codec', '');
            });
        }

        $assets = $query->limit($limit)->get();
        $checked = 0;
        $updated = 0;
        $problems = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($assets as $asset) {
            $checked++;
            try {
                $filePath = $this->resolveAssetVideoFilePath($asset);
                if (! $filePath) {
                    $skipped++;
                    continue;
                }

                $meta = $this->extractVideoMetadata($filePath);
                $codec = $meta['video_codec'] ?? null;
                if ($codec) {
                    $asset->video_codec = $codec;
                    $asset->save();
                    $updated++;
                } else {
                    $skipped++;
                }

                $asset->loadMissing('optimizedVersions');
                if ($asset->needsWebCompatibleTranscode()) {
                    $problems++;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('Web compatibility scan failed for asset', [
                    'asset_id' => $asset->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $remainingUnknown = Asset::query()
            ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
            ->where('is_publishable', true)
            ->where(function ($q) {
                $q->whereNull('video_codec')->orWhere('video_codec', '');
            })
            ->count();

        $totalProblems = Asset::query()
            ->whereIn('extension', Asset::VIDEO_EXTENSIONS)
            ->whereIn('video_codec', Asset::incompatibleWebVideoCodecs())
            ->where(function ($q) {
                $q->whereNull('web_video_relative_path')
                    ->orWhereColumn('web_video_relative_path', 'relative_path')
                    ->orWhere('web_video_relative_path', '');
            })
            ->whereDoesntHave('optimizedVersions')
            ->count();

        $message = "تم فحص {$checked} فيديو، تحديث كوديك: {$updated}. مشاكل توافق في هذه الدفعة: {$problems}. الإجمالي الحالي للمشكلات: {$totalProblems}.";
        if ($remainingUnknown > 0) {
            $message .= " متبقٍ بدون فحص كوديك: {$remainingUnknown} — شغّل الفحص مرة أخرى.";
        }
        if ($errors > 0) {
            $message .= " أخطاء: {$errors}.";
        }

        return redirect()
            ->route('assets.index', ['web_compat' => 'problem', 'publish_status' => 'published'])
            ->with('success', $message);
    }

    public function reExtractMetadata(Asset $asset)
    {
        try {
            $filePath = $this->resolveAssetVideoFilePath($asset);

            if (! $filePath) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'الملف غير موجود. يرجى التأكد من المسار الأصلي أو نقل الملف أولاً.');
            }

            $videoMeta = $this->syncVideoMetadataFromFile($asset, $filePath, true);
            $asset->refresh();

            Log::info('Metadata re-extracted', [
                'asset_id' => $asset->id,
                'file_path' => $filePath,
                'width' => $asset->width,
                'height' => $asset->height,
                'duration' => $asset->duration_seconds,
                'orientation' => $asset->orientation,
                'extracted' => $videoMeta,
            ]);

            if (! $asset->width && ! $asset->height && ! $asset->duration_seconds) {
                return redirect()->route('assets.show', $asset)
                    ->with('error', 'تعذر قراءة الأبعاد أو المدة من الملف. تأكد أن ffprobe مثبت وأن الملف فيديو صالح.');
            }

            $message = 'تم إعادة استخراج بيانات الفيديو من الملف';
            if ($asset->width && $asset->height) {
                $message .= " — الأبعاد: {$asset->width}×{$asset->height}";
                if ($asset->orientation) {
                    $message .= ' ('.($asset->orientation === 'portrait' ? 'عمودي' : ($asset->orientation === 'landscape' ? 'أفقي' : 'مربع')).')';
                }
            } else {
                $message .= ' — تعذر قراءة الأبعاد';
            }
            if ($asset->duration_seconds) {
                $message .= ' — المدة: '.$this->formatDuration($asset->duration_seconds);
            } else {
                $message .= ' — تعذر قراءة المدة';
            }

            return redirect()->route('assets.show', $asset)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to re-extract metadata', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('assets.show', $asset)
                ->with('error', 'فشل إعادة استخراج البيانات: '.$e->getMessage());
        }
    }

    /**
     * مسار ملف الفيديو الأصلي لاستخراج الأبعاد والمدة (وليس نسخة العرض المحسّنة).
     * الأولوية: master على الموقع (assets/...) ثم original_path ثم relative_path.
     */
    private function resolveAssetVideoFilePath(Asset $asset): ?string
    {
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            $sitePath = Storage::disk('public')->path($asset->relative_path);
            if (is_file($sitePath) && is_readable($sitePath)) {
                return $sitePath;
            }
        }

        if ($asset->original_path && is_file($asset->original_path) && is_readable($asset->original_path)) {
            return $asset->original_path;
        }

        if (! $asset->relative_path) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $asset->relative_path), '/');
        if (strpos($relativePath, 'assets/') === 0) {
            $filePath = Storage::disk('public')->path($relativePath);
        } else {
            $filePath = storage_path('app/public/'.$relativePath);
        }

        return (is_file($filePath) && is_readable($filePath)) ? $filePath : null;
    }

    private function syncVideoMetadataFromFile(Asset $asset, ?string $filePath = null, bool $overwriteExisting = false): array
    {
        $filePath = $filePath ?? $this->resolveAssetVideoFilePath($asset);
        if (! $filePath) {
            return ['width' => null, 'height' => null, 'duration_seconds' => null];
        }

        $videoMeta = $this->extractVideoMetadata($filePath);

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
            if (abs($ratio - (9 / 16)) < 0.05) {
                $aspectRatio = '9:16';
            } elseif (abs($ratio - (16 / 9)) < 0.05) {
                $aspectRatio = '16:9';
            } elseif (abs($ratio - 1) < 0.05) {
                $aspectRatio = '1:1';
            } else {
                $aspectRatio = $width.':'.$height;
            }
        }

        $asset->update([
            'size_bytes' => filesize($filePath),
            'modified_at' => date('Y-m-d H:i:s', filemtime($filePath)),
            'width' => $width,
            'height' => $height,
            'duration_seconds' => $overwriteExisting
                ? ($videoMeta['duration_seconds'] ?? null)
                : ($videoMeta['duration_seconds'] ?? $asset->duration_seconds),
            'video_codec' => $overwriteExisting
                ? ($videoMeta['video_codec'] ?? null)
                : ($videoMeta['video_codec'] ?? $asset->video_codec),
            'orientation' => $overwriteExisting ? $orientation : ($orientation ?? $asset->orientation),
            'aspect_ratio' => $overwriteExisting ? $aspectRatio : ($aspectRatio ?? $asset->aspect_ratio),
        ]);

        return $videoMeta;
    }

    /**
     * استخراج مدة الفيديو بالثواني من مخرجات ffprobe (format ثم مسار الفيديو).
     */
    private function extractDurationSecondsFromFfprobeJson(array $jsonOutput, ?array $videoStream): ?int
    {
        $candidates = [];

        if (isset($jsonOutput['format']['duration']) && is_numeric($jsonOutput['format']['duration'])) {
            $candidates[] = (float) $jsonOutput['format']['duration'];
        }

        if ($videoStream && isset($videoStream['duration']) && is_numeric($videoStream['duration'])) {
            $candidates[] = (float) $videoStream['duration'];
        }

        foreach ($jsonOutput['streams'] ?? [] as $stream) {
            if (($stream['codec_type'] ?? '') !== 'video') {
                continue;
            }
            if (isset($stream['duration']) && is_numeric($stream['duration'])) {
                $candidates[] = (float) $stream['duration'];
            }
        }

        if ($candidates === []) {
            return null;
        }

        $duration = max($candidates);

        return $duration > 0 ? (int) round($duration) : null;
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
                'error' => 'فشل حفظ وصف الموقع: '.$e->getMessage(),
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
            // تحديث النسخة المنقاة لإرسالها لـ DeepSeek
            $asset->transcription_plain = $this->stripTimestampsFromTranscription($transcription);
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
                'error' => 'فشل حفظ المحتوى النصي: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateTranscriptionSegments(Asset $asset, Request $request)
    {
        $request->validate([
            'segments' => 'required|array',
            'segments.*.start' => 'required|numeric|min:0',
            'segments.*.end' => 'required|numeric|min:0',
            'segments.*.text' => 'nullable|string',
        ]);

        try {
            $segments = $request->input('segments');

            // بناء النص الكامل من الجمل (نص فقط بدون توقيتات)
            $fullText = collect($segments)->pluck('text')->map(function ($t) {
                return trim((string) $t);
            })->filter()->implode(' ');

            $asset->transcription = $fullText;
            // النص من المقاطع هو بالفعل نسخة منقاة — نستخدمها لـ DeepSeek
            $asset->transcription_plain = $fullText;
            $asset->save();

            // حفظ ملف JSON للـ segments في مجلد captions
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir.'/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath = storage_path('app/public/'.$captionDir.'/'.$baseName.'.json');

                $directory = dirname($jsonPath);
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                $data = [
                    'segments' => array_map(function ($seg) {
                        return [
                            'start' => (float) $seg['start'],
                            'end' => (float) $seg['end'],
                            'text' => isset($seg['text']) ? trim((string) $seg['text']) : '',
                        ];
                    }, $segments),
                ];
                file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                Cache::forget("transcription_segments_{$asset->id}");
            }

            Log::info('Transcription segments updated', [
                'asset_id' => $asset->id,
                'segments_count' => count($segments),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ المحتوى النصي بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update transcription segments', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ المحتوى النصي: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * رفع ملف SRT واستبدال المحتوى النصي والتوقيت بالمحتوى المرفوع.
     */
    public function uploadTranscriptionSrt(Asset $asset, Request $request)
    {
        $request->validate([
            'srt_file' => 'required|file|max:10240',
        ]);
        $ext = strtolower($request->file('srt_file')->getClientOriginalExtension());
        if (! in_array($ext, ['srt', 'txt'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'يرجى رفع ملف بصيغة SRT أو TXT.',
            ], 422);
        }

        $file = $request->file('srt_file');
        $content = file_get_contents($file->getRealPath());
        // دعم UTF-8 مع BOM وتهيئة النص للتحليل
        $content = $this->normalizeTranscriptionFileContent($content);
        $segments = $this->parseSrtContent($content);

        if (empty($segments)) {
            return response()->json([
                'success' => false,
                'error' => 'لم يتم العثور على مقاطع صالحة في الملف. تأكد من أن الملف بصيغة SRT.',
            ], 422);
        }

        $fullText = collect($segments)->pluck('text')->map(function ($t) {
            return trim((string) $t);
        })->filter()->implode(' ');

        // حفظ النص في قاعدة البيانات أولاً (مستقل عن الملفات)
        try {
            $asset->transcription = $fullText;
            $asset->transcription_plain = $fullText;
            $asset->save();
        } catch (\Exception $e) {
            Log::error('Failed to save transcription from SRT to DB', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ المحتوى النصي في قاعدة البيانات: '.$e->getMessage(),
            ], 500);
        }

        // كتابة ملف JSON للـ segments (غير حرجة — لا تفشّل العملية كلها)
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            try {
                $videoDir = dirname($asset->relative_path);
                $captionDir = $videoDir.'/captions';
                $baseName = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $captionJsonPath = $captionDir.'/'.$baseName.'.json';
                $data = [
                    'segments' => array_map(function ($seg) {
                        return [
                            'start' => (float) $seg['start'],
                            'end'   => (float) $seg['end'],
                            'text'  => isset($seg['text']) ? trim((string) $seg['text']) : '',
                        ];
                    }, $segments),
                ];
                Storage::disk('public')->makeDirectory($captionDir);
                Storage::disk('public')->put($captionJsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                Cache::forget("transcription_segments_{$asset->id}");
            } catch (\Exception $e) {
                Log::warning('SRT uploaded to DB but JSON file write failed', [
                    'asset_id' => $asset->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        Log::info('Transcription uploaded from SRT', [
            'asset_id'       => $asset->id,
            'segments_count' => count($segments),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الملف واستبدال المحتوى النصي والتوقيت بنجاح. ('.\count($segments).' مقطع)',
        ]);
    }

    public function uploadTranslationSrt(Asset $asset, Request $request, string $lang)
    {
        if (! array_key_exists($lang, self::TRANSLATION_LANGUAGES)) {
            return response()->json(['success' => false, 'error' => 'لغة غير مدعومة: '.$lang], 422);
        }

        $request->validate(['srt_file' => 'required|file|max:10240']);

        $ext = strtolower($request->file('srt_file')->getClientOriginalExtension());
        if (! in_array($ext, ['srt', 'txt'], true)) {
            return response()->json(['success' => false, 'error' => 'يرجى رفع ملف بصيغة SRT أو TXT.'], 422);
        }

        $content = file_get_contents($request->file('srt_file')->getRealPath());
        $content = $this->normalizeTranscriptionFileContent($content);
        $segments = $this->parseSrtContent($content);

        if (empty($segments)) {
            return response()->json(['success' => false, 'error' => 'لم يتم العثور على مقاطع صالحة في الملف.'], 422);
        }

        // حفظ في قاعدة البيانات
        try {
            $all = is_array($asset->translation_segments) ? $asset->translation_segments : [];
            $all[$lang] = array_map(fn($seg) => [
                'start' => (float) $seg['start'],
                'end'   => (float) $seg['end'],
                'text'  => trim((string) ($seg['text'] ?? '')),
            ], $segments);
            $asset->translation_segments = $all;
            $asset->save();
        } catch (\Exception $e) {
            Log::error('uploadTranslationSrt DB save failed', ['asset_id' => $asset->id, 'lang' => $lang, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'فشل الحفظ في قاعدة البيانات: '.$e->getMessage()], 500);
        }

        // حفظ ملف JSON
        if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
            try {
                $captionDir  = dirname($asset->relative_path).'/captions';
                $baseName    = pathinfo($asset->file_name, PATHINFO_FILENAME);
                $jsonPath    = $captionDir.'/'.$baseName.'_'.$lang.'.json';
                $data = ['segments' => $all[$lang]];
                Storage::disk('public')->makeDirectory($captionDir);
                Storage::disk('public')->put($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            } catch (\Exception $e) {
                Log::warning('uploadTranslationSrt JSON write failed', ['asset_id' => $asset->id, 'lang' => $lang, 'error' => $e->getMessage()]);
            }
        }

        Log::info('Translation SRT uploaded', ['asset_id' => $asset->id, 'lang' => $lang, 'segments' => count($segments)]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع ترجمة '.self::TRANSLATION_LANGUAGES[$lang].' بنجاح ('.count($segments).' مقطع)',
        ]);
    }

    /**
     * تهيئة محتوى الملف النصي: توحيد نهايات الأسطر، إزالة BOM، وتحويل الترميز عند الحاجة.
     */
    private function normalizeTranscriptionFileContent(string $content): string
    {
        $content = preg_replace('/\r\n|\r/', "\n", $content);
        $bom = "\xef\xbb\xbf";
        if (str_starts_with($content, $bom)) {
            $content = substr($content, strlen($bom));
        }
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['UTF-8', 'Windows-1256', 'ISO-8859-6', 'ISO-8859-1'], true) ?: 'UTF-8');
        }

        return $content;
    }

    /**
     * تحويل سطر توقيت SRT إلى ثوانٍ (float).
     * يدعم: HH:MM:SS,mmm أو MM:SS,mmm (بدون ساعات).
     */
    private function parseSrtTimeToSeconds(string $timePart): float
    {
        $timePart = trim($timePart);
        if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})[,.](\d{1,3})$/', $timePart, $m)) {
            return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3] + (int) str_pad($m[4], 3, '0', STR_PAD_LEFT) / 1000;
        }
        if (preg_match('/^(\d{1,2}):(\d{1,2})[,.](\d{1,3})$/', $timePart, $m)) {
            return (int) $m[1] * 60 + (int) $m[2] + (int) str_pad($m[3], 3, '0', STR_PAD_LEFT) / 1000;
        }

        return 0.0;
    }

    /**
     * تحويل محتوى SRT إلى مصفوفة segments [ ['start' => float, 'end' => float, 'text' => string], ... ].
     * يدعم توقيت مختلط في السطر نفسه، مثل: 00:00:59,500 --> 01:01,066 (بداية كاملة، نهاية دقائق:ثوانٍ).
     */
    private function parseSrtContent(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }
        $blocks = preg_split('/\n\s*\n/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $segments = [];
        // أي سطر يبدو كتوقيت SRT (بداية ونهاية مع " --> ") — قد يكون الطرفان بصيغتين مختلفتين
        $timeLinePattern = '/^\d{1,2}:\d{1,2}(?::\d{1,2})?[,.]\d{1,3}\s*-->\s*\d{1,2}:\d{1,2}(?::\d{1,2})?[,.]\d{1,3}/';

        foreach ($blocks as $block) {
            $lines = array_map('trim', explode("\n", $block));
            $timeLine = null;
            $textLines = [];

            foreach ($lines as $line) {
                if (preg_match($timeLinePattern, $line)) {
                    $timeLine = $line;

                    continue;
                }
                if ($timeLine !== null && $line !== '') {
                    $textLines[] = $line;
                }
            }

            if ($timeLine === null || empty($textLines)) {
                continue;
            }

            $parts = preg_split('/\s*-->\s*/', $timeLine, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $start = $this->parseSrtTimeToSeconds(trim($parts[0]));
            $end = $this->parseSrtTimeToSeconds(trim($parts[1]));
            $segments[] = [
                'start' => $start,
                'end' => $end,
                'text' => implode("\n", $textLines),
            ];
        }

        return $segments;
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
                'error' => 'فشل حفظ العنوان: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateGregorianYear(Asset $asset, Request $request)
    {
        $request->validate([
            'gregorian_year' => 'nullable|integer|min:1900|max:2100',
        ]);

        try {
            $value = $request->input('gregorian_year');
            $asset->gregorian_year = $value ? (string) $value : null;
            $asset->save();

            Log::info('Gregorian year updated', [
                'asset_id' => $asset->id,
                'gregorian_year' => $asset->gregorian_year,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ السنة الميلادية بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update gregorian year', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ السنة الميلادية: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث فلاج إظهار الترجمة على صفحة الفيديو العامة (شريط لغة الترجمة + الإعدادات + نمط الترجمة).
     */
    public function updateShowTranslation(Asset $asset, Request $request)
    {
        $request->validate([
            'show_translation' => 'required|boolean',
        ]);

        try {
            $asset->show_translation = $request->boolean('show_translation');
            $asset->save();

            Log::info('Show translation updated', [
                'asset_id' => $asset->id,
                'show_translation' => $asset->show_translation,
            ]);

            return response()->json([
                'success' => true,
                'message' => $asset->show_translation ? 'سيتم إظهار الترجمة على صفحة الفيديو' : 'تم إخفاء الترجمة عن صفحة الفيديو',
                'show_translation' => $asset->show_translation,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update show_translation', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل الحفظ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث فلاج إظهار التعليقات على صفحة الفيديو العامة.
     */
    public function updateShowComments(Asset $asset, Request $request)
    {
        $request->validate([
            'show_comments' => 'required|boolean',
        ]);

        try {
            $asset->show_comments = $request->boolean('show_comments');
            $asset->save();

            Log::info('Show comments updated', [
                'asset_id' => $asset->id,
                'show_comments' => $asset->show_comments,
            ]);

            return response()->json([
                'success' => true,
                'message' => $asset->show_comments ? 'سيتم إظهار التعليقات على صفحة الفيديو' : 'تم إخفاء التعليقات عن صفحة الفيديو',
                'show_comments' => $asset->show_comments,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update show_comments', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل الحفظ: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateProductionDate(Asset $asset, Request $request)
    {
        $request->validate([
            'production_date' => 'nullable|date',
        ]);

        try {
            $value = $request->filled('production_date') ? $request->input('production_date') : null;
            $asset->production_date = $value;
            $asset->save();

            Log::info('Production date updated', [
                'asset_id' => $asset->id,
                'production_date' => $asset->production_date?->format('Y-m-d'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تاريخ الإنتاج بنجاح',
                'production_date' => $asset->production_date?->format('Y-m-d'),
                'production_date_formatted' => $asset->production_date?->format('d/m/Y'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update production date', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ تاريخ الإنتاج: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updatePublishUrls(Asset $asset, Request $request)
    {
        $request->validate([
            'youtube_publish_url' => 'nullable|url|max:500',
            'soundcloud_publish_url' => 'nullable|url|max:500',
        ]);

        try {
            $asset->youtube_publish_url = $request->input('youtube_publish_url') ?: null;
            $asset->soundcloud_publish_url = $request->input('soundcloud_publish_url') ?: null;
            $asset->save();

            Log::info('Publish URLs updated', [
                'asset_id' => $asset->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ روابط النشر بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update publish URLs', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ روابط النشر: '.$e->getMessage(),
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
                $category = trim((string) $category);
            }

            // التحقق من أن التصنيف من القائمة المصرح بها (إذا كان موجوداً)
            if ($category && ! in_array($category, $validCategories)) {
                Log::warning('Invalid category provided', [
                    'asset_id' => $asset->id,
                    'category' => $category,
                    'valid_categories' => $validCategories,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'التصنيف غير صحيح. يجب أن يكون واحداً من: '.implode(', ', $validCategories),
                ], 400);
            }

            // حفظ القيمة القديمة للتسجيل
            $oldCategory = $asset->category;

            // تحديث التصنيف
            $asset->category = $category;
            $saved = $asset->save();

            if (! $saved) {
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
                'error' => 'فشل حفظ التصنيف: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateContentCategory(Asset $asset, Request $request)
    {
        try {
            // الحصول على البيانات من JSON body مباشرة
            $jsonData = $request->json()->all();
            $categoryIds = $jsonData['category_ids'] ?? $jsonData['categories'] ?? null;

            // إذا لم تكن البيانات في JSON، جرب input
            if ($categoryIds === null) {
                $categoryIds = $request->input('category_ids') ?? $request->input('categories');
            }

            Log::info('Content categories update request received', [
                'asset_id' => $asset->id,
                'json_data' => $jsonData,
                'category_ids' => $categoryIds,
                'category_ids_type' => gettype($categoryIds),
            ]);

            // تحويل إلى مصفوفة إذا كانت قيمة واحدة
            if (! is_array($categoryIds)) {
                if ($categoryIds === null || $categoryIds === '' || $categoryIds === 'null') {
                    $categoryIds = [];
                } else {
                    $categoryIds = [$categoryIds];
                }
            }

            // تنظيف: إزالة القيم الفارغة وتحويل إلى أعداد صحيحة
            $categoryIds = array_filter(array_map('intval', $categoryIds));

            // التحقق من وجود التصنيفات في قاعدة البيانات
            $validCategoryIds = \App\Models\Category::whereIn('id', $categoryIds)->pluck('id')->toArray();
            $invalidIds = array_diff($categoryIds, $validCategoryIds);

            if (! empty($invalidIds)) {
                Log::warning('Invalid category IDs provided', [
                    'asset_id' => $asset->id,
                    'invalid_ids' => $invalidIds,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'بعض التصنيفات غير موجودة: '.implode(', ', $invalidIds),
                ], 400);
            }

            // حفظ القيمة القديمة للتسجيل
            $oldCategoryIds = $asset->categories->pluck('id')->toArray();

            // تحديث تصنيفات المحتوى (many-to-many)
            $asset->categories()->sync($categoryIds);

            // إعادة تحميل العلاقة
            $asset->load('categories');

            Log::info('Content categories updated successfully', [
                'asset_id' => $asset->id,
                'old_category_ids' => $oldCategoryIds,
                'new_category_ids' => $categoryIds,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ تصنيفات المحتوى بنجاح',
                'categories' => $asset->categories->map(function ($cat) {
                    return ['id' => $cat->id, 'name' => $cat->name];
                })->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update content categories', [
                'asset_id' => $asset->id,
                'json_data' => $request->json()->all(),
                'category_ids_input' => $request->input('category_ids'),
                'request_all' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ تصنيفات المحتوى: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث قوائم التشغيل للفيديو (many-to-many)
     */
    public function updatePlaylists(Asset $asset, Request $request)
    {
        try {
            $jsonData = $request->json()->all();
            $playlistIds = $jsonData['playlist_ids'] ?? $jsonData['playlists'] ?? null;
            if ($playlistIds === null) {
                $playlistIds = $request->input('playlist_ids') ?? $request->input('playlists');
            }
            if (! is_array($playlistIds)) {
                $playlistIds = ($playlistIds === null || $playlistIds === '' || $playlistIds === 'null') ? [] : [$playlistIds];
            }
            $playlistIds = array_filter(array_map('intval', $playlistIds));
            $validIds = \App\Models\Playlist::whereIn('id', $playlistIds)->pluck('id')->toArray();
            $invalidIds = array_diff($playlistIds, $validIds);
            if (! empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'بعض قوائم التشغيل غير موجودة: '.implode(', ', $invalidIds),
                ], 400);
            }
            $syncData = [];
            foreach ($validIds as $pid) {
                $maxOrder = \Illuminate\Support\Facades\DB::table('asset_playlist')->where('playlist_id', $pid)->max('order');
                $syncData[$pid] = ['order' => $maxOrder === null ? 0 : (int) $maxOrder + 1];
            }
            $asset->playlists()->sync($syncData);
            $asset->load(['playlists.parent.parent']);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ قوائم التشغيل بنجاح',
                'playlists' => $asset->playlists->map(function ($p) {
                    $labels = [];
                    $current = $p;
                    while ($current) {
                        array_unshift($labels, $current->title);
                        $current = $current->parent;
                    }

                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'label' => implode(' › ', $labels),
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update playlists', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'فشل حفظ قوائم التشغيل: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * اختيار مسار الفيديو الأساسي (أكبر مساحة عرض بعد تطبيق الدوران ونسبة البكسل).
     */
    private function pickPrimaryVideoStream(array $streams): ?array
    {
        $bestStream = null;
        $bestArea = 0;

        foreach ($streams as $stream) {
            if (($stream['codec_type'] ?? '') !== 'video') {
                continue;
            }

            [$displayWidth, $displayHeight] = $this->normalizeVideoDisplayDimensions($stream);
            if (! $displayWidth || ! $displayHeight) {
                continue;
            }

            $area = $displayWidth * $displayHeight;
            if ($area > $bestArea) {
                $bestArea = $area;
                $bestStream = $stream;
            }
        }

        return $bestStream;
    }

    private function extractVideoStreamRotation(array $stream): int
    {
        $rotation = 0;

        if (! empty($stream['tags']['rotate']) && is_numeric($stream['tags']['rotate'])) {
            $rotation = (int) $stream['tags']['rotate'];
        }

        if (! empty($stream['side_data_list']) && is_array($stream['side_data_list'])) {
            foreach ($stream['side_data_list'] as $sideData) {
                if (isset($sideData['rotation']) && is_numeric($sideData['rotation'])) {
                    $rotation = (int) $sideData['rotation'];
                    break;
                }
            }
        }

        return $rotation;
    }

    /**
     * أبعاد العرض الفعلية: تطبيق sample_aspect_ratio ثم دوران 90°/270° (شائع في فيديوهات الموبايل).
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function normalizeVideoDisplayDimensions(array $stream): array
    {
        $width = isset($stream['width']) ? (int) $stream['width'] : 0;
        $height = isset($stream['height']) ? (int) $stream['height'] : 0;

        if ($width <= 0 || $height <= 0) {
            return [null, null];
        }

        $sar = $stream['sample_aspect_ratio'] ?? '1:1';
        if (is_string($sar) && preg_match('/^(\d+):(\d+)$/', $sar, $matches)) {
            $sarNum = (int) $matches[1];
            $sarDen = (int) $matches[2];
            if ($sarNum > 0 && $sarDen > 0 && $sarNum !== $sarDen) {
                $width = (int) round($width * $sarNum / $sarDen);
            }
        }

        $rotation = $this->extractVideoStreamRotation($stream);
        $rotation = (($rotation % 360) + 360) % 360;
        if ($rotation === 90 || $rotation === 270) {
            return [$height, $width];
        }

        return [$width, $height];
    }

    private function extractVideoMetadata($filePath)
    {
        $meta = [
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
            'rotation' => 0,
            'video_codec' => null,
        ];

        try {
            $ffprobePath = $this->resolveFfprobePath();
            if (! $ffprobePath) {
                Log::warning('ffprobe not found — cannot extract video metadata', [
                    'file' => $filePath,
                ]);

                return $meta;
            }

            $command = escapeshellarg($ffprobePath)
                .' -v error -show_streams -show_format -of json '
                .escapeshellarg($filePath).' 2>&1';

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && ! empty($output)) {
                $jsonOutput = json_decode(implode("\n", $output), true);
                $videoStream = $this->pickPrimaryVideoStream($jsonOutput['streams'] ?? []);
                $meta['duration_seconds'] = $this->extractDurationSecondsFromFfprobeJson($jsonOutput, $videoStream);

                if ($videoStream) {
                    $meta['rotation'] = $this->extractVideoStreamRotation($videoStream);
                    [$displayWidth, $displayHeight] = $this->normalizeVideoDisplayDimensions($videoStream);
                    $meta['width'] = $displayWidth;
                    $meta['height'] = $displayHeight;
                    $meta['video_codec'] = Asset::normalizeVideoCodec(
                        (string) ($videoStream['codec_name'] ?? $videoStream['codec_tag_string'] ?? '')
                    );

                    if ($meta['width'] && $meta['height']) {
                        Log::debug('Video metadata extracted successfully', [
                            'file' => basename($filePath),
                            'raw_width' => (int) ($videoStream['width'] ?? 0),
                            'raw_height' => (int) ($videoStream['height'] ?? 0),
                            'display_width' => $meta['width'],
                            'display_height' => $meta['height'],
                            'rotation' => $meta['rotation'],
                            'video_codec' => $meta['video_codec'],
                            'sample_aspect_ratio' => $videoStream['sample_aspect_ratio'] ?? null,
                            'duration' => $meta['duration_seconds'],
                        ]);
                    }
                }
            } else {
                Log::warning('ffprobe failed to extract metadata', [
                    'file' => $filePath,
                    'return_code' => $returnCode,
                    'output' => implode("\n", array_slice($output, -20)),
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

    /**
     * استخراج السنة الميلادية من المسار
     * البحث عن رقم مكون من 4 أرقام في المسار (عادة بين 1900-2100)
     */
    private function extractGregorianYear($relativePath)
    {
        if (! $relativePath) {
            return null;
        }

        // البحث عن جميع الأرقام المكونة من 4 أرقام في المسار
        if (preg_match_all('/\b(\d{4})\b/', $relativePath, $matches)) {
            foreach ($matches[1] as $year) {
                $yearInt = (int) $year;
                // السنة الميلادية عادة بين 1900-2100
                if ($yearInt >= 1900 && $yearInt <= 2100) {
                    return (string) $year;
                }
            }
        }

        return null;
    }

    public function toggleLike(Asset $asset)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $like = \App\Models\Like::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            \App\Models\Like::create([
                'user_id' => $user->id,
                'asset_id' => $asset->id,
            ]);
            $liked = true;
        }

        $likesCount = $asset->likes()->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    public function toggleFavorite(Asset $asset)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $favorite = \App\Models\Favorite::where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $favorited = false;
        } else {
            \App\Models\Favorite::create([
                'user_id' => $user->id,
                'asset_id' => $asset->id,
            ]);
            $favorited = true;
        }

        $favoritesCount = $asset->favorites()->count();

        return response()->json([
            'success' => true,
            'favorited' => $favorited,
            'favorites_count' => $favoritesCount,
        ]);
    }

    public function addComment(Asset $asset, Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ], [
            'content.required' => 'يجب إدخال نص التعليق',
            'content.max' => 'التعليق طويل جداً (الحد الأقصى 2000 حرف)',
        ]);

        $comment = \App\Models\Comment::create([
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user_name' => $comment->user->name,
                'user_id' => $comment->user->id,
                'created_at' => $comment->created_at->diffForHumans(),
                'created_at_full' => $comment->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function getComments(Asset $asset)
    {
        $comments = $asset->comments()->with(['user', 'replies.user'])->get();

        $commentsData = $comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'user_name' => $comment->user->name,
                'user_id' => $comment->user->id,
                'created_at' => $comment->created_at->diffForHumans(),
                'created_at_full' => $comment->created_at->format('Y-m-d H:i:s'),
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'user_name' => $reply->user->name,
                        'user_id' => $reply->user->id,
                        'created_at' => $reply->created_at->diffForHumans(),
                        'created_at_full' => $reply->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'comments' => $commentsData,
            'comments_count' => $comments->count(),
        ]);
    }

    public function deleteComment(\App\Models\Comment $comment)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        if ($comment->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['error' => 'غير مصرح لك بحذف هذا التعليق'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التعليق بنجاح',
        ]);
    }
}
