@extends('layouts.app')

@section('title', ($preparing_mode ?? false) ? 'فيديوهات جاري التجهيز' : 'إدارة الفيديوهات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <h2 class="fw-bold mb-0">{{ ($preparing_mode ?? false) ? 'فيديوهات جاري التجهيز' : 'إدارة الفيديوهات' }}</h2>
        @if(!($preparing_mode ?? false))
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('assets.index') }}" class="btn {{ !($browse_mode ?? false) ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-list-ul me-2 ms-2"></i>عرض القائمة
                </a>
                <a href="{{ route('assets.index', ['view' => 'browse']) }}" class="btn {{ $browse_mode ?? false ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-folder2-open me-2 ms-2"></i>تصفح بالمجلدات
                </a>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importVideoModal" id="openImportVideoModalBtn"
                data-initial-path="{{ ($browse_mode ?? false) ? ($path_prefix ?? '') : '' }}">
                <i class="bi bi-plus-circle me-2 ms-2"></i>إضافة فيديو جديد
            </button>
        </div>
        @else
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importVideoModal" id="openImportVideoModalBtn" data-initial-path="">
                <i class="bi bi-plus-circle me-2"></i>إضافة فيديو جديد
            </button>
            <form action="{{ route('assets.delete-unpublished') }}" method="POST" class="d-inline"
                  onsubmit="return confirm('هل أنت متأكد من حذف جميع الفيديوهات غير المنشورة من قاعدة البيانات؟\n\nالعدد: {{ $unpublishedCount ?? 0 }} فيديو\n\nلا يمكن التراجع عن هذا الإجراء.');">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger btn-sm" {{ ($unpublishedCount ?? 0) === 0 ? 'disabled' : '' }}>
                    <i class="bi bi-trash me-2"></i>حذف جميع الفيديوهات غير المنشورة
                    @if(($unpublishedCount ?? 0) > 0)
                        <span class="badge bg-light text-danger ms-1">{{ $unpublishedCount }}</span>
                    @endif
                </button>
            </form>
        </div>
        @endif
    </div>
    <div>
        @if(!($preparing_mode ?? false))
        <div style="display: none;">
        <form action="{{ route('assets.scan') }}" method="POST" class="d-inline me-2" onsubmit="return confirm('هل تريد Scan المجلدين storage/app/public/2025 و storage/app/public/videos وإضافة الفيديوهات الجديدة؟')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-search me-2 ms-2"></i>Scan
            </button>
        </form>
        </div>
        <div style="display: none;">
            <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#updateMetadataModal">
                <i class="bi bi-arrow-clockwise me-1"></i>تحديد بيانات الملف
            </button>
            <form action="{{ route('assets.update-all-metadata') }}" method="POST" class="d-inline me-2" onsubmit="return confirm('هل تريد تحديث بيانات جميع الملفات الموجودة في قاعدة البيانات؟\n\nهذه العملية قد تستغرق وقتاً طويلاً حسب عدد الملفات.')">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>تحديث بيانات جميع الملفات
                </button>
            </form>
            <a href="{{ route('assets.duplicates') }}" class="btn btn-warning btn-sm me-2">
                <i class="bi bi-files me-1"></i>تقرير الملفات المكررة
            </a>
        </div>
        @endif
        <span class="badge bg-primary me-2">إجمالي: {{ $stats['total'] }}</span>
        <span class="badge bg-info me-2">فيديوهات: {{ $stats['videos'] }}</span>
        <span class="badge bg-success">الحجم: {{ $stats['total_size_mb'] }} MB</span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $syncMissingIds = session('sync_missing_asset_ids', []);
    $syncMissingCount = is_array($syncMissingIds) ? count($syncMissingIds) : 0;
@endphp
@if($syncMissingCount > 0)
    <div class="alert alert-warning mb-4" role="alert">
        <strong>تنبيه:</strong> يوجد {{ $syncMissingCount }} ملف في قاعدة البيانات غير موجودة في المجلد.
        <form action="{{ route('assets.remove-missing-sync') }}" method="POST" class="d-inline mt-2" id="removeMissingSyncForm">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn btn-outline-danger btn-sm" id="removeMissingSyncBtn">
                حذف هذه السجلات من قاعدة البيانات
            </button>
        </form>
    </div>
    <script>
        document.getElementById('removeMissingSyncForm').addEventListener('submit', function(e) {
            if (!confirm('هل أنت متأكد من حذف {{ $syncMissingCount }} سجل من قاعدة البيانات؟ لا يمكن التراجع عن هذا الإجراء.')) {
                e.preventDefault();
            }
        });
    </script>
@endif

<!-- Modal استيراد فيديو من المجلدات -->
<div class="modal fade" id="importVideoModal" tabindex="-1" aria-labelledby="importVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importVideoModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>إضافة فيديو جديد
                </h5>
                <button type="button" class="btn-close" id="importVideoModalCloseBtn" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="importVideoProgress" class="border rounded p-3 mb-3 bg-light d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <span id="importVideoProgressLabel" class="fw-semibold">جاري المعالجة...</span>
                        <span id="importVideoProgressPercent" class="badge bg-primary">0%</span>
                    </div>
                    <div class="progress mb-2" style="height: 24px;">
                        <div id="importVideoProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <small id="importVideoProgressDetail" class="text-muted d-block"></small>
                </div>

                <div class="card border-primary mb-3" id="importVideoUploadCard">
                    <div class="card-body py-3">
                        <label class="form-label fw-semibold mb-1"><i class="bi bi-cloud-upload me-1"></i>رفع فيديو من جهازك</label>
                        <p class="text-muted small mb-2" id="importVideoUploadTarget">مجلد الحفظ: <span class="text-warning">اختر مجلداً من الأسفل أولاً</span></p>
                        <input type="file" class="form-control form-control-sm mb-2" id="importVideoFileInput" accept="video/*,.mp4,.mov,.mkv,.m4v,.avi,.webm">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="importVideoUploadBtn" disabled>
                                <i class="bi bi-upload me-1"></i>رفع فقط
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="importVideoUploadImportBtn" disabled>
                                <i class="bi bi-lightning-charge me-1"></i>رفع وتسجيل مباشرة
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-3">
                <p class="text-muted small mb-2">أو اختر فيديوهات موجودة على السيرفر (يمكن اختيار أكثر من واحد):</p>
                <div id="importVideoSelectBar" class="d-none d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="text-muted small" id="importVideoSelectedCount">0 محدد</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="importVideoSelectAllBtn">تحديد الكل</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="importVideoClearSelectionBtn">إلغاء التحديد</button>
                </div>
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb mb-0 flex-wrap" id="importVideoBreadcrumb"></ol>
                </nav>
                <div id="importVideoLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">جاري تحميل المحتوى...</p>
                </div>
                <div id="importVideoError" class="alert alert-danger d-none mb-0"></div>
                <div id="importVideoBrowseContent" class="d-none">
                    <div class="row g-2 mb-3" id="importVideoFolders"></div>
                    <div class="list-group mb-0" id="importVideoFiles"></div>
                    <p id="importVideoEmpty" class="text-muted small mb-0 d-none">لا توجد مجلدات أو ملفات فيديو في هذا المستوى.</p>
                </div>
                <div id="importVideoResult" class="alert d-none mb-0 mt-3"></div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" id="importVideoCancelBtn" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="importVideoSubmitBtn" disabled>
                    <i class="bi bi-box-arrow-in-down me-1"></i><span id="importVideoSubmitBtnLabel">تسجيل ونقل المحدد</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal استخراج المحتوى النصي للمحدد -->
<div class="modal fade" id="batchTranscribeModal" tabindex="-1" aria-labelledby="batchTranscribeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchTranscribeModalLabel">
                    <i class="bi bi-file-text me-2"></i>استخراج المحتوى النصي (<span id="batchTranscribeSettingsLabel">tiny · أول 5 دقائق</span>) — <span id="batchTranscribeTotal">0</span> فيديو
                </h5>
                <button type="button" class="btn-close" id="batchTranscribeCloseBtn" style="display: none;" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p id="batchTranscribeCurrent" class="mb-2 text-muted">جاري التحضير...</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="batchTranscribeProgressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <ul class="list-group list-group-flush small" id="batchTranscribeVideoList" style="max-height: 220px; overflow-y: auto;"></ul>
            </div>
            <div class="modal-footer" id="batchTranscribeFooter">
                <span id="batchTranscribeSummary" class="me-auto text-muted small d-none"></span>
                <button type="button" class="btn btn-secondary" id="batchTranscribeDismissBtn" style="display: none;" data-bs-dismiss="modal">إغلاق وتحديث الصفحة</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تحويل الفيديو إلى ملف صوتي للمحدد -->
<div class="modal fade" id="batchExtractAudioModal" tabindex="-1" aria-labelledby="batchExtractAudioModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchExtractAudioModalLabel">
                    <i class="bi bi-music-note-beamed me-2"></i>تحويل الفيديو إلى ملف صوتي — <span id="batchExtractAudioTotal">0</span> فيديو
                </h5>
                <button type="button" class="btn-close" id="batchExtractAudioCloseBtn" style="display: none;" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p id="batchExtractAudioCurrent" class="mb-2 text-muted">جاري التحضير...</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="batchExtractAudioProgressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <ul class="list-group list-group-flush small" id="batchExtractAudioVideoList" style="max-height: 220px; overflow-y: auto;"></ul>
            </div>
            <div class="modal-footer" id="batchExtractAudioFooter">
                <span id="batchExtractAudioSummary" class="me-auto text-muted small d-none"></span>
                <button type="button" class="btn btn-secondary" id="batchExtractAudioDismissBtn" style="display: none;" data-bs-dismiss="modal">إغلاق وتحديث الصفحة</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تحليل المحتوى النصي للمحدد -->
<div class="modal fade" id="batchAnalyzeModal" tabindex="-1" aria-labelledby="batchAnalyzeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchAnalyzeModalLabel">
                    <i class="bi bi-tags me-2"></i>تحليل المحتوى النصي — <span id="batchAnalyzeTotal">0</span> فيديو
                </h5>
                <button type="button" class="btn-close" id="batchAnalyzeCloseBtn" style="display: none;" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p id="batchAnalyzeCurrent" class="mb-2 text-muted">جاري التحضير...</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="batchAnalyzeProgressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <ul class="list-group list-group-flush small" id="batchAnalyzeVideoList" style="max-height: 220px; overflow-y: auto;"></ul>
            </div>
            <div class="modal-footer" id="batchAnalyzeFooter">
                <span id="batchAnalyzeSummary" class="me-auto text-muted small d-none"></span>
                <button type="button" class="btn btn-secondary" id="batchAnalyzeDismissBtn" style="display: none;" data-bs-dismiss="modal">إغلاق وتحديث الصفحة</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ترجمة الفيديوات المحددة -->
<div class="modal fade" id="batchTranslateModal" tabindex="-1" aria-labelledby="batchTranslateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchTranslateModalLabel">
                    <i class="bi bi-translate me-2"></i>ترجمة الفيديوات المحددة (<span id="batchTranslateTotal">0</span> فيديو)
                </h5>
                <button type="button" class="btn-close" id="batchTranslateCloseBtn" style="display: none;" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p id="batchTranslateCurrent" class="mb-2 text-muted">جاري التحضير...</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="batchTranslateProgressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <ul class="list-group list-group-flush small" id="batchTranslateVideoList" style="max-height: 200px; overflow-y: auto;"></ul>
            </div>
            <div class="modal-footer" id="batchTranslateFooter">
                <span id="batchTranslateSummary" class="me-auto text-muted small d-none"></span>
                <button type="button" class="btn btn-secondary" id="batchTranslateDismissBtn" style="display: none;" data-bs-dismiss="modal">إغلاق وتحديث الصفحة</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal تغيير إعدادات عامة (اسم المتحدث + تصنيفات المحتوى) للمحدد -->
<div class="modal fade" id="bulkSettingsModal" tabindex="-1" aria-labelledby="bulkSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkSettingsModalLabel">
                    <i class="bi bi-gear me-2"></i>تغيير إعدادات عامة — <span id="bulkSettingsCount">0</span> فيديو
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="bulkSettingsForm" method="POST" action="{{ route('assets.bulk-update-settings') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">فعّل ما تريد تطبيقه على الفيديوهات المحددة ثم اختر القيمة.</p>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_speaker" value="1" id="apply_speaker">
                            <label class="form-check-label fw-medium" for="apply_speaker">تطبيق اسم المتحدث (الشيخ)</label>
                        </div>
                        <select class="form-select" name="scholar_id" id="bulk_scholar_id">
                            <option value="">— بدون شيخ —</option>
                            @foreach($scholars ?? [] as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_categories" value="1" id="apply_categories">
                            <label class="form-check-label fw-medium" for="apply_categories">تطبيق تصنيفات المحتوى</label>
                        </div>
                        <div class="bulk-category-cards" id="bulkCategoryCards">
                            @foreach($contentCategories ?? [] as $c)
                                <label class="bulk-category-card" data-category-id="{{ $c->id }}">
                                    <input type="checkbox" name="category_ids[]" value="{{ $c->id }}" class="d-none bulk-category-cb">
                                    <span class="bulk-category-card-text">{{ $c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <small class="text-muted">اضغط على الكارد لاختيار أو إلغاء، يمكن اختيار أكثر من تصنيف</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_gregorian_year" value="1" id="apply_gregorian_year">
                            <label class="form-check-label fw-medium" for="apply_gregorian_year">تطبيق السنة الميلادية</label>
                        </div>
                        <input type="number" class="form-control" name="gregorian_year" id="bulk_gregorian_year" placeholder="مثال: 2025" min="1900" max="2100" step="1" style="max-width: 8rem;">
                        <small class="text-muted">اتركه فارغاً لمسح السنة المحفوظة (1900–2100)</small>
                    </div>
                    @php
                        $bulkFilterPlaylistId = request()->filled('playlist') ? (int) request('playlist') : null;
                        $bulkFilterPlaylist = $bulkFilterPlaylistId
                            ? \App\Models\Playlist::find($bulkFilterPlaylistId)
                            : null;
                    @endphp
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_playlist" value="1" id="apply_playlist">
                            <label class="form-check-label fw-medium" for="apply_playlist">إضافة المحدد إلى قائمة تشغيل</label>
                        </div>
                        <div id="bulkPlaylistPicker" class="bulk-playlist-picker mb-2">
                            <p class="text-muted small mb-2">1. اختر القائمة الرئيسية (يمكن اختيار أكثر من قائمة)</p>
                            <div class="row g-2" id="bulkPlaylistRootCardsContainer">
                                @foreach($rootPlaylists ?? [] as $playlist)
                                    <div class="col-auto">
                                        <div class="playlist-card-selectable playlist-root-card bulk-playlist-card"
                                             data-playlist-id="{{ $playlist->id }}"
                                             onclick="toggleBulkPlaylistCard(this)">
                                            @if($playlist->image_path)
                                                <img src="{{ asset('storage/' . $playlist->image_path) }}"
                                                     alt="{{ $playlist->title }}"
                                                     class="playlist-card-image">
                                            @else
                                                <div class="playlist-card-icon">
                                                    <i class="bi bi-collection-play"></i>
                                                </div>
                                            @endif
                                            <div class="playlist-card-title">{{ $playlist->title }}</div>
                                            <div class="playlist-card-check">
                                                <i class="bi bi-check-circle-fill"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div id="bulkPlaylistSubSection" class="d-none mt-3">
                                <p class="text-muted small mb-2 fw-medium">2. اختر القائمة الفرعية (أو أكثر)</p>
                                <div class="row g-2" id="bulkPlaylistSubCardsContainer"></div>
                            </div>
                            <div id="bulkPlaylistSub2Section" class="d-none mt-3">
                                <p class="text-muted small mb-2 fw-medium">3. اختر القائمة الفرعية الأعمق</p>
                                <div class="row g-2" id="bulkPlaylistSub2CardsContainer"></div>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-3">يُضاف المحدد إلى نهاية كل قائمة مختارة دون إزالة قوائم أخرى لكل فيديو.</small>

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="apply_remove_playlist" value="1" id="apply_remove_playlist">
                            <label class="form-check-label fw-medium" for="apply_remove_playlist">إزالة المحدد من قائمة التشغيل</label>
                        </div>
                        @if($bulkFilterPlaylistId && $bulkFilterPlaylist)
                            <input type="hidden" name="remove_from_playlist_id" value="{{ $bulkFilterPlaylistId }}">
                            <small class="text-muted d-block">سيتم الإزالة من القائمة المفلترة حالياً: <strong>{{ $bulkFilterPlaylist->title }}</strong></small>
                        @else
                            <small class="text-muted d-block">سيتم إزالة المحدد من <strong>جميع</strong> قوائم التشغيل المرتبطة به (بدون اختيار قائمة).</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_show_translation" value="1" id="apply_show_translation">
                            <label class="form-check-label fw-medium" for="apply_show_translation">تطبيق إظهار الترجمة على صفحة الفيديو</label>
                        </div>
                        <select class="form-select" name="show_translation" id="bulk_show_translation" style="max-width: 12rem;">
                            <option value="1">إظهار الترجمة</option>
                            <option value="0">إخفاء الترجمة</option>
                        </select>
                        <small class="text-muted">يتحكم في ظهور شريط لغة الترجمة والإعدادات ونمط الترجمة للزائر</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="apply_show_comments" value="1" id="apply_show_comments">
                            <label class="form-check-label fw-medium" for="apply_show_comments">تطبيق إظهار التعليقات على صفحة الفيديو</label>
                        </div>
                        <select class="form-select" name="show_comments" id="bulk_show_comments" style="max-width: 12rem;">
                            <option value="1">إظهار التعليقات</option>
                            <option value="0">إخفاء التعليقات</option>
                        </select>
                        <small class="text-muted">يتحكم في ظهور قسم التعليقات للزائر</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="bulkSettingsSubmitBtn">
                        <i class="bi bi-check-lg me-1"></i>تطبيق على المحدد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تغيير أسماء الحلقات -->
<div class="modal fade" id="bulkRenameTitlesModal" tabindex="-1" aria-labelledby="bulkRenameTitlesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkRenameTitlesModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>تغيير أسماء الحلقات — <span id="bulkRenameTitlesCount">0</span> حلقة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="bulkRenameTitlesForm" method="POST" action="{{ route('assets.bulk-rename-titles') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body bulk-rename-modal-body">
                    <p class="text-muted small mb-2">عدّل اسم كل حلقة و/أو اختر صورة مصغرة جديدة. تُحفظ فقط الحلقات التي تغيّر اسمها أو صورتها.</p>
                    <p class="text-muted small mb-3 d-none" id="bulkRenameScrollHint">
                        <i class="bi bi-arrows-expand-vertical me-1"></i>مرّر للأسفل لعرض كل الحلقات
                    </p>
                    <div class="bulk-rename-table-wrap table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 4rem;">ID</th>
                                    <th style="width: 7rem;">الصورة</th>
                                    <th>الاسم الحالي</th>
                                    <th>الاسم الجديد</th>
                                </tr>
                            </thead>
                            <tbody id="bulkRenameTitlesList"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch">
                    <div id="bulkRenameTitlesStatus" class="small text-muted mb-2 d-none"></div>
                    <div class="d-flex justify-content-end gap-2 w-100">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="bulkRenameTitlesCancelBtn">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="bulkRenameTitlesSubmitBtn">
                            <i class="bi bi-check-lg me-1"></i>حفظ التغييرات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal دمج الفيديو -->
<div class="modal fade" id="bulkMergeModal" tabindex="-1" aria-labelledby="bulkMergeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkMergeModalLabel">
                    <i class="bi bi-merge me-2"></i>دمج الفيديو
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="bulkMergeForm" method="POST" action="{{ route('assets.merge') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">اختر السجل الذي تريد الإبقاء عليه. ستُحفظ جميع بياناته، ويُنقل المسار النسبي الصحيح من المحددين إن وُجد، وتُحذف بقية السجلات المحددة.</p>
                    <div id="mergeAssetList" class="list-group"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info" id="bulkMergeSubmitBtn">
                        <i class="bi bi-merge me-1"></i>تنفيذ الدمج
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
.bulk-rename-thumb {
    width: 72px;
    height: 48px;
    object-fit: cover;
    border-radius: 0.25rem;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-light);
}
.bulk-rename-thumb-placeholder {
    width: 72px;
    height: 48px;
    border-radius: 0.25rem;
    border: 1px dashed var(--bs-border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-secondary);
    font-size: 1.25rem;
    background: var(--bs-light);
}
.bulk-rename-thumb-input {
    max-width: 9rem;
}
#bulkRenameTitlesModal .modal-dialog {
    max-width: min(960px, calc(100vw - 2rem));
}
#bulkRenameTitlesModal .modal-content {
    max-height: calc(100vh - 2rem);
}
#bulkRenameTitlesModal .bulk-rename-modal-body {
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
#bulkRenameTitlesModal .bulk-rename-table-wrap {
    flex: 1 1 auto;
    min-height: 0;
    max-height: min(65vh, 620px);
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.375rem;
}
#bulkRenameTitlesModal .bulk-rename-table-wrap thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--bs-light);
    box-shadow: 0 1px 0 var(--bs-border-color);
}
.bulk-category-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
}
.bulk-category-card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 0.75rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.2s, background-color 0.2s;
    margin: 0;
}
.bulk-category-card:hover {
    border-color: #188781;
    background-color: rgba(24, 135, 129, 0.06);
}
.bulk-category-card:has(.bulk-category-cb:checked) {
    border-color: #188781;
    background-color: rgba(24, 135, 129, 0.12);
    font-weight: 600;
}
.bulk-category-card-text {
    font-size: 0.9rem;
    text-align: center;
}

.bulk-playlist-picker {
    max-height: 320px;
    overflow-y: auto;
    padding: 0.25rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fafafa;
}
.playlist-card-selectable {
    position: relative;
    width: 110px;
    height: 130px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background-color: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    text-align: center;
    overflow: hidden;
}
.playlist-card-selectable:hover {
    border-color: #188781;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.playlist-card-selectable.selected {
    border-color: #188781;
    background-color: rgba(24, 135, 129, 0.1);
    box-shadow: 0 0 0 3px rgba(24, 135, 129, 0.15);
}
.playlist-card-image {
    width: 52px;
    height: 52px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 0.4rem;
}
.playlist-card-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    border-radius: 6px;
    margin-bottom: 0.4rem;
    font-size: 1.5rem;
    color: #6c757d;
}
.playlist-card-selectable.selected .playlist-card-icon {
    background-color: #188781;
    color: #fff;
}
.playlist-card-title {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.playlist-card-selectable.selected .playlist-card-title {
    color: #188781;
    font-weight: 600;
}
.playlist-card-check {
    position: absolute;
    top: 0.35rem;
    right: 0.35rem;
    width: 20px;
    height: 20px;
    display: none;
    align-items: center;
    justify-content: center;
    background-color: #188781;
    border-radius: 50%;
    color: #fff;
    font-size: 0.7rem;
}
.playlist-card-selectable.selected .playlist-card-check {
    display: flex;
}
.playlist-sub-card {
    border-style: dashed;
}

/* كاردات تصنيفات المحتوى للفلترة */
.filter-category-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.filter-category-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    min-height: 120px;
}
.filter-category-card:hover {
    border-color: #188781;
    background-color: rgba(24, 135, 129, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.filter-category-card.selected {
    border-color: #188781;
    background-color: rgba(24, 135, 129, 0.15);
    font-weight: 600;
}
.filter-category-card--none {
    border-style: dashed;
}
.filter-category-card--none .filter-category-icon {
    background-color: #f3f4f6;
    color: #6b7280;
}
.filter-category-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}
.filter-category-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(24, 135, 129, 0.1);
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
    color: #188781;
}
.filter-category-text {
    font-size: 0.9rem;
    text-align: center;
    color: #333;
}
.filter-category-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #188781;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.filter-category-card.selected .filter-category-check {
    opacity: 1;
}

/* كاردات الشيوخ للفلترة */
.filter-scholar-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.filter-scholar-card.is-hidden-by-more {
    display: none;
}
.filter-scholar-more-wrap {
    margin-bottom: 0.5rem;
}
.filter-scholar-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    min-height: 100px;
}
.filter-scholar-card:hover {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.filter-scholar-card.selected {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.15);
    font-weight: 600;
}
.filter-scholar-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 50%;
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
    color: #0d6efd;
}
.filter-scholar-text {
    font-size: 0.9rem;
    text-align: center;
    color: #333;
}
.filter-scholar-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #0d6efd;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.filter-scholar-card.selected .filter-scholar-check {
    opacity: 1;
}

/* كاردات السنة الميلادية للفلترة */
.filter-year-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.filter-year-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    min-height: 90px;
}
.filter-year-card:hover {
    border-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.filter-year-card.selected {
    border-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.15);
    font-weight: 600;
}
.filter-year-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
    color: #ffc107;
}
.filter-year-text {
    font-size: 0.95rem;
    text-align: center;
    color: #333;
    font-weight: 500;
}
.filter-year-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #ffc107;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.filter-year-card.selected .filter-year-check {
    opacity: 1;
}

/* كاردات قائمة التشغيل للفلترة */
.filter-playlist-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.filter-playlist-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    min-height: 100px;
}
.filter-playlist-card:hover {
    border-color: #6f42c1;
    background-color: rgba(111, 66, 193, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.filter-playlist-card.selected {
    border-color: #6f42c1;
    background-color: rgba(111, 66, 193, 0.15);
    font-weight: 600;
}
.filter-playlist-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(111, 66, 193, 0.1);
    border-radius: 8px;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
    color: #6f42c1;
}
.filter-playlist-text {
    font-size: 0.9rem;
    text-align: center;
    color: #333;
    line-height: 1.3;
}
.filter-playlist-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #6f42c1;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.filter-playlist-card.selected .filter-playlist-check {
    opacity: 1;
}
</style>

<!-- Modal عرض المسار -->
<div class="modal fade" id="showPathModal" tabindex="-1" aria-labelledby="showPathModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showPathModalLabel">
                    <i class="bi bi-folder me-2"></i>المسار
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">المسار الأصلي:</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="modalOriginalPath" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPath('modalOriginalPath')" title="نسخ">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">المسار النسبي:</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="modalRelativePath" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPath('modalRelativePath')" title="نسخ">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal لتحديد بيانات الملف (الزر مخفي أعلاه) -->
<div class="modal fade" id="updateMetadataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديد بيانات الملف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('assets.update-metadata') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="original_path" class="form-label">المسار الأصلي للملف</label>
                        <input type="text" class="form-control" id="original_path" name="original_path" 
                               placeholder="أدخل المسار الكامل للملف" required>
                        <small class="text-muted">مثال: /path/to/video.mp4</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise me-1"></i>تحديث البيانات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($browse_mode ?? false)
{{-- وضع التصفح بالمجلدات --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('assets.index', ['view' => 'browse']) }}">الرئيسية</a>
                    </li>
                    @foreach($breadcrumb_segments ?? [] as $i => $seg)
                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                        @if($loop->last)
                            {{ $seg }}
                        @else
                            <a href="{{ route('assets.index', ['view' => 'browse', 'path' => implode('/', array_slice($breadcrumb_segments, 0, $i + 1))]) }}">{{ $seg }}</a>
                        @endif
                    </li>
                    @endforeach
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-3">
                @if(!empty($path_prefix))
                <div style="display: none;">
                <form action="{{ route('assets.scan') }}" method="POST" class="d-inline" id="scanOpenFolderForm">
                    @csrf
                    <input type="hidden" name="scan_path" value="{{ $path_prefix }}">
                    <input type="hidden" name="view" value="browse">
                    <input type="hidden" name="path" value="{{ $path_prefix }}">
                    <button type="submit" class="btn btn-success btn-sm" title="Scan المجلد المفتوح وإضافة الفيديوهات الجديدة">
                        <i class="bi bi-search me-2 ms-2"></i>Scan المجلد المفتوح
                    </button>
                </form>
                </div>
                <a href="{{ route('assets.index', ['folder' => $path_prefix]) }}" class="btn btn-outline-primary btn-sm" title="عرض نفس محتويات المجلد الحالي على شكل قائمة">
                    <i class="bi bi-list-ul me-2 ms-2"></i>عرض القائمة (لهذا المجلد)
                </a>
                <script>
                document.getElementById('scanOpenFolderForm')?.addEventListener('submit', function(e) {
                    if (!confirm('هل تريد Scan المجلد الحالي ({{ $path_prefix }}) وإضافة الفيديوهات الجديدة إلى قاعدة البيانات؟')) e.preventDefault();
                });
                </script>
                @endif
            </div>
        </div>
        @php
            $foldersCount = count($folders ?? []);
            $filesCount = ($file_assets ?? collect())->count();
            $emptyBrowse = $foldersCount === 0 && $filesCount === 0;
        @endphp
        @if($emptyBrowse)
            <p class="text-muted mb-0">هذا المجلد فارغ.</p>
        @else
            <div id="browseBulkBar" class="alert alert-secondary py-2 mb-3 d-none" role="alert">
                <span class="me-3"><strong id="browseSelectedCount">0</strong> فيديو محدد</span>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="browseBulkClear">
                    <i class="bi bi-x-circle me-1"></i>إلغاء التحديد
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="browseBulkPublish">
                    <i class="bi bi-check-circle me-1"></i>تفعيل النشر للمحدد
                </button>
                <button type="button" class="btn btn-sm btn-warning me-2" id="browseBulkUnpublish">
                    <i class="bi bi-x-circle me-1"></i>إلغاء النشر للمحدد
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="browseBulkTranscribeBtn" title="استخراج المحتوى النصي (tiny · أول 5 دقائق)">
                    <i class="bi bi-file-text me-1"></i>استخراج المحتوى النصي
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="browseBulkExtractAudioBtn" title="تحويل الفيديو إلى ملف صوتي MP3">
                    <i class="bi bi-music-note-beamed me-1"></i>تحويل إلى صوت
                </button>
                <button type="button" class="btn btn-sm btn-info text-white" id="browseBulkAnalyzeBtn" title="تحليل المحتوى النصي عبر DeepSeek">
                    <i class="bi bi-tags me-1"></i>تحليل المحتوى النصي
                </button>
            </div>
            <p class="text-muted mb-3">
                في هذا المستوى: <strong>{{ $foldersCount }}</strong> مجلد، <strong>{{ $filesCount }}</strong> ملف
                @if($filesCount > 9)
                    — مرّر للأسفل لرؤية كل الملفات
                @endif
            </p>
            <div class="row g-3">
                @foreach($folders ?? [] as $folderName)
                <div class="col-md-4 col-lg-3">
                    <a href="{{ route('assets.index', ['view' => 'browse', 'path' => ($path_prefix ?? '') === '' ? $folderName : ($path_prefix . '/' . $folderName)]) }}" class="text-decoration-none">
                        <div class="card h-100 border shadow-sm" style="cursor: pointer; transition: all 0.2s;">
                            <div class="card-body d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded p-2 me-3 ms-0">
                                    <i class="bi bi-folder-fill text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1 text-truncate">
                                    <strong>{{ $folderName }}</strong>
                                </div>
                                <i class="bi bi-chevron-left ms-2 flex-shrink-0"></i>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
                @foreach($file_assets ?? [] as $asset)
                @php
                    $pathRaw = $asset->original_path ?? $asset->relative_path ?? '';
                    $pathNorm = trim(ltrim(str_replace('\\', '/', trim($pathRaw)), '/'));
                    $pathParts = $pathNorm !== '' ? explode('/', $pathNorm) : [];
                    $fileNameInPath = count($pathParts) > 0 ? end($pathParts) : ($asset->file_name ?? $asset->title ?? 'ملف');
                    $isMissing = !empty($asset->file_missing);
                @endphp
                <div class="col-md-4 col-lg-3 position-relative" data-asset-title="{{ e($asset->title ?? $fileNameInPath) }}">
                    @if(!$isMissing)
                    <input type="checkbox" class="form-check-input position-absolute asset-browse-cb" name="browse_ids[]" value="{{ $asset->id }}" data-id="{{ $asset->id }}" title="اختر حلقة" style="top: 0.75rem; right: 0.75rem; z-index: 5;">
                    @endif
                    <a href="{{ route('assets.show', $asset) }}" class="text-decoration-none d-block card-link-browse {{ $isMissing ? 'asset-card-missing-link' : '' }}" target="_blank" rel="noopener noreferrer">
                        <div class="card h-100 shadow-sm {{ $isMissing ? 'border-danger border-2 bg-danger bg-opacity-10' : ($asset->is_publishable ? 'border-success border-2' : 'border-warning border-2') }}" style="cursor: pointer; transition: all 0.2s;">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded p-2 me-3 ms-0 {{ $isMissing ? 'bg-danger bg-opacity-25' : ($asset->is_publishable ? 'bg-success bg-opacity-10' : 'bg-warning bg-opacity-10') }}">
                                    <i class="bi bi-file-earmark-play-fill fs-4 {{ $isMissing ? 'text-danger' : ($asset->is_publishable ? 'text-success' : 'text-warning') }}"></i>
                                </div>
                                <div class="flex-grow-1 text-truncate min-width-0">
                                    <strong class="{{ $isMissing ? 'text-danger' : '' }}">{{ $fileNameInPath }}</strong>
                                    @if($isMissing)
                                        <span class="badge bg-danger mt-1">الملف غير موجود على القرص</span>
                                    @else
                                        @if($asset->duration_seconds)
                                            <small class="text-muted d-block">{{ $asset->duration_formatted ?? null }}</small>
                                        @endif
                                        <span class="badge mt-1 {{ $asset->is_publishable ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ $asset->is_publishable ? 'منشور' : 'غير منشور' }}
                                        </span>
                                        @php
                                            $translationLangsBrowse = $translationLanguages ?? [];
                                            $segmentsBrowse = is_array($asset->translation_segments ?? null) ? $asset->translation_segments : [];
                                            $translatedBrowse = array_keys(array_filter(array_intersect_key($segmentsBrowse, $translationLangsBrowse)));
                                            $totalBrowse = count($translationLangsBrowse);
                                            $numTranslatedBrowse = count($translatedBrowse);
                                        @endphp
                                        @if($totalBrowse > 0)
                                            @if($numTranslatedBrowse === 0)
                                                <span class="badge mt-1 bg-secondary" title="غير مترجم"><i class="bi bi-translate me-1"></i>غير مترجم</span>
                                            @elseif($numTranslatedBrowse >= $totalBrowse)
                                                <span class="badge mt-1 bg-success" title="مترجم لكل اللغات"><i class="bi bi-check-circle me-1"></i>مترجم</span>
                                            @else
                                                <span class="badge mt-1 bg-warning text-dark" title="جزئي: {{ $numTranslatedBrowse }}/{{ $totalBrowse }} لغة"><i class="bi bi-translate me-1"></i>{{ $numTranslatedBrowse }}/{{ $totalBrowse }}</span>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                                <i class="bi bi-chevron-left ms-2 flex-shrink-0"></i>
                            </div>
                        </div>
                    </a>
                    @if($isMissing)
                    <form action="{{ route('assets.destroy', $asset) }}" method="POST" class="position-absolute d-inline" style="bottom: 0.75rem; right: 0.75rem; z-index: 6;" onsubmit="return confirm('حذف هذا السجل من قاعدة البيانات؟ الملف غير موجود على القرص.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="view" value="browse">
                        <input type="hidden" name="path" value="{{ $path_prefix ?? '' }}">
                        <button type="submit" class="btn btn-danger btn-sm" title="حذف السجل من قاعدة البيانات">
                            <i class="bi bi-trash"></i> حذف
                        </button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@else
<!-- Total Stats Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <i class="bi bi-collection-play text-primary me-2"></i>إجمالي عدد الفيديوهات
                        </h5>
                        <p class="text-muted mb-0 fs-4 fw-bold">{{ $stats['total'] ?? 0 }} فيديو</p>
                    </div>
                    <div class="text-end">
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-collection-play fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <i class="bi bi-clock-history text-success me-2"></i>إجمالي المدة
                        </h5>
                        <p class="text-muted mb-0 fs-4 fw-bold">{{ $stats['total_duration'] ?? '—' }}</p>
                    </div>
                    <div class="text-end">
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-clock-history fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orientation Filter Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <a href="{{ route('assets.index', array_merge(request()->query(), ['orientation' => 'landscape'])) }}" 
           class="text-decoration-none">
            <div class="card h-100 {{ request('orientation') == 'landscape' ? 'border-primary border-3' : '' }}" 
                 style="cursor: pointer; transition: all 0.3s;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="bi bi-arrows-expand-h text-success me-2"></i>أفقي
                            </h5>
                            <p class="text-muted mb-0">{{ $stats['landscape'] ?? 0 }} فيديو · {{ $stats['landscape_duration'] ?? '—' }}</p>
                        </div>
                        <div class="text-end">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-arrows-expand-h fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('assets.index', array_merge(request()->query(), ['orientation' => 'portrait'])) }}" 
           class="text-decoration-none">
            <div class="card h-100 {{ request('orientation') == 'portrait' ? 'border-primary border-3' : '' }}" 
                 style="cursor: pointer; transition: all 0.3s;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="bi bi-arrows-expand-v text-info me-2"></i>عمودي
                            </h5>
                            <p class="text-muted mb-0">{{ $stats['portrait'] ?? 0 }} فيديو · {{ $stats['portrait_duration'] ?? '—' }}</p>
                        </div>
                        <div class="text-end">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-arrows-expand-v fs-1 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

@php
    $currentSortBy = $sort_by ?? request('sort_by', 'id');
    $currentSortDir = $sort_dir ?? request('sort_dir', 'desc');

    if (! function_exists('getSortUrl')) {
        function getSortUrl($column, $currentSortBy, $currentSortDir) {
            $query = request()->except(['sort_by', 'sort_dir', 'page']);
            if ($currentSortBy === $column) {
                $query['sort_by'] = $column;
                $query['sort_dir'] = $currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                $query['sort_by'] = $column;
                $query['sort_dir'] = 'asc';
            }

            return route('assets.index', $query);
        }
    }
@endphp

<!-- Quick Search -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('assets.index') }}" class="row g-2 align-items-end">
            @foreach(request()->except(['search', 'scholar_search', 'page']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $item)
                        <input type="hidden" name="{{ $k }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <div class="col-12 col-lg-5">
                <label for="quick_search" class="form-label mb-1">البحث بالاسم</label>
                <input type="text" class="form-control" id="quick_search" name="search"
                       value="{{ request('search') }}" placeholder="اكتب اسم الحلقة...">
            </div>
            <div class="col-12 col-lg-4">
                <label for="quick_scholar_search" class="form-label mb-1">البحث باسم الشيخ</label>
                <input type="text" class="form-control" id="quick_scholar_search" name="scholar_search"
                       value="{{ request('scholar_search') }}" placeholder="اكتب اسم الشيخ...">
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i>بحث
                </button>
                <a href="{{ route('assets.index', request()->except(['search', 'scholar_search', 'page'])) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Filters -->
@if(!($preparing_mode ?? false))
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('assets.index') }}" class="row g-3">
            @if(!empty($folder_filter))
            <input type="hidden" name="folder" value="{{ $folder_filter }}">
            @endif
            @if(request('path_issues') == '1')
            <input type="hidden" name="path_issues" value="1">
            @endif
            {{-- كاردات فلتر حالة النشر: الكل | منشور | غير منشور --}}
            @php
                $currentPublishStatus = request('publish_status', 'all');
            @endphp
            <input type="hidden" name="publish_status" value="{{ $currentPublishStatus }}">
            <div class="col-12">
                <label class="form-label mb-2">حالة النشر</label>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('assets.index', array_merge(request()->except(['page']), ['publish_status' => 'all'])) }}"
                       class="btn btn-sm {{ $currentPublishStatus === 'all' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-collection"></i>
                        <span>الكل</span>
                    </a>
                    <a href="{{ route('assets.index', array_merge(request()->except(['page']), ['publish_status' => 'published'])) }}"
                       class="btn btn-sm {{ $currentPublishStatus === 'published' ? 'btn-success' : 'btn-outline-secondary' }} px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-globe"></i>
                        <span>منشور</span>
                    </a>
                    <a href="{{ route('assets.index', array_merge(request()->except(['page']), ['publish_status' => 'unpublished'])) }}"
                       class="btn btn-sm {{ $currentPublishStatus === 'unpublished' ? 'btn-warning' : 'btn-outline-secondary' }} px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-eye-slash"></i>
                        <span>غير منشور</span>
                    </a>
                </div>
            </div>
            {{-- السطر الثاني: تصنيفات المحتوى (كاردات) --}}
            <div class="col-12">
                <label class="form-label mb-2">تصنيفات المحتوى</label>
                <div class="filter-category-cards">
                    @php
                        $selectedCategoryIds = [];
                        if (request()->has('content_categories')) {
                            $selectedCategoryIds = is_array(request('content_categories')) ? request('content_categories') : [request('content_categories')];
                        } elseif (request()->has('content_category')) {
                            $selectedCategoryIds = [request('content_category')];
                        }
                        $selectedCategoryIds = array_map('strval', $selectedCategoryIds);
                        $noneCategorySelected = in_array('none', $selectedCategoryIds, true);
                    @endphp
                    <label class="filter-category-card filter-category-card--none {{ $noneCategorySelected ? 'selected' : '' }}" data-category-id="none">
                        <input type="checkbox" name="content_categories[]" value="none" class="d-none filter-category-cb" {{ $noneCategorySelected ? 'checked' : '' }}>
                        <div class="filter-category-icon">
                            <i class="bi bi-slash-circle"></i>
                        </div>
                        <span class="filter-category-text">بدون تصنيف</span>
                        @if(($uncategorizedCount ?? 0) > 0)
                            <span class="badge bg-secondary mt-1">{{ $uncategorizedCount }}</span>
                        @endif
                        <div class="filter-category-check">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </label>
                    @foreach($contentCategories ?? [] as $cat)
                        <label class="filter-category-card {{ in_array((string)$cat->id, $selectedCategoryIds) ? 'selected' : '' }}" data-category-id="{{ $cat->id }}">
                            <input type="checkbox" name="content_categories[]" value="{{ $cat->id }}" class="d-none filter-category-cb" {{ in_array((string)$cat->id, $selectedCategoryIds) ? 'checked' : '' }}>
                            @if($cat->image_path)
                                <img src="{{ asset('storage/' . $cat->image_path) }}" alt="{{ $cat->name }}" class="filter-category-image">
                            @else
                                <div class="filter-category-icon">
                                    <i class="bi bi-tag"></i>
                                </div>
                            @endif
                            <span class="filter-category-text">{{ $cat->name }}</span>
                            <div class="filter-category-check">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
                <small class="text-muted">اضغط على الكارد لاختيار أو إلغاء، يمكن اختيار أكثر من تصنيف</small>
            </div>
            {{-- السطر الثالث: الشيوخ (كاردات) --}}
            <div class="col-12">
                <label class="form-label mb-2">الشيوخ</label>
                <div class="filter-scholar-cards">
                    @php
                        $selectedScholarIds = [];
                        if (request()->has('scholar_ids')) {
                            $selectedScholarIds = is_array(request('scholar_ids')) ? request('scholar_ids') : [request('scholar_ids')];
                        } elseif (request()->has('scholar_id')) {
                            $selectedScholarIds = [request('scholar_id')];
                        }
                        $selectedScholarIds = array_map('strval', $selectedScholarIds);
                    @endphp
                    @foreach($scholars ?? [] as $s)
                        <label class="filter-scholar-card {{ in_array((string)$s->id, $selectedScholarIds) ? 'selected' : '' }}" data-scholar-id="{{ $s->id }}">
                            <input type="checkbox" name="scholar_ids[]" value="{{ $s->id }}" class="d-none filter-scholar-cb" {{ in_array((string)$s->id, $selectedScholarIds) ? 'checked' : '' }}>
                            <div class="filter-scholar-icon">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <span class="filter-scholar-text">{{ $s->name }}</span>
                            <div class="filter-scholar-check">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
                <div class="filter-scholar-more-wrap d-none">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="filterScholarMoreBtn">More</button>
                </div>
                <small class="text-muted">اضغط على الكارد لاختيار أو إلغاء، يمكن اختيار أكثر من شيخ</small>
            </div>
            {{-- السنة الميلادية (كاردات) --}}
            <div class="col-12">
                <label class="form-label mb-2">السنة الميلادية</label>
                <div class="filter-year-cards">
                    <label class="filter-year-card {{ !request('gregorian_year') ? 'selected' : '' }}" data-year="">
                        <input type="radio" name="gregorian_year" value="" class="d-none filter-year-radio" {{ !request('gregorian_year') ? 'checked' : '' }}>
                        <div class="filter-year-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <span class="filter-year-text">الكل</span>
                        <div class="filter-year-check">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </label>
                    @foreach($gregorianYears ?? [] as $year)
                        <label class="filter-year-card {{ request('gregorian_year') == $year ? 'selected' : '' }}" data-year="{{ $year }}">
                            <input type="radio" name="gregorian_year" value="{{ $year }}" class="d-none filter-year-radio" {{ request('gregorian_year') == $year ? 'checked' : '' }}>
                            <div class="filter-year-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <span class="filter-year-text">{{ $year }}</span>
                            <div class="filter-year-check">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
                <small class="text-muted">اضغط على الكارد لاختيار السنة</small>
            </div>
            {{-- السطر الثالث: ٣ اختيارات --}}
            {{-- قائمة التشغيل (كاردات) --}}
            <div class="col-12">
                <label class="form-label mb-2">قائمة التشغيل</label>
                <div class="filter-playlist-cards">
                    <label class="filter-playlist-card {{ !request('playlist') ? 'selected' : '' }}" data-playlist="">
                        <input type="radio" name="playlist" value="" class="d-none filter-playlist-radio" {{ !request('playlist') ? 'checked' : '' }}>
                        <div class="filter-playlist-icon">
                            <i class="bi bi-list-ul"></i>
                        </div>
                        <span class="filter-playlist-text">الكل</span>
                        <div class="filter-playlist-check">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </label>
                    @foreach($playlists ?? [] as $pl)
                        <label class="filter-playlist-card {{ request('playlist') == (string)$pl->id ? 'selected' : '' }}" data-playlist="{{ $pl->id }}">
                            <input type="radio" name="playlist" value="{{ $pl->id }}" class="d-none filter-playlist-radio" {{ request('playlist') == (string)$pl->id ? 'checked' : '' }}>
                            <div class="filter-playlist-icon">
                                <i class="bi bi-music-note-list"></i>
                            </div>
                            <span class="filter-playlist-text">{{ \Illuminate\Support\Str::limit($pl->title, 30) }}</span>
                            <div class="filter-playlist-check">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
                <small class="text-muted">اضغط على الكارد لاختيار قائمة التشغيل</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-search me-2 ms-2"></i>بحث
                </button>
            </div>
        </form>
        {{-- فلتر الملفات التي بها مشاكل في المسار النسبي (الأصلي) --}}
        <div class="col-12 mt-2">
            @if(request('path_issues') == '1')
                <span class="badge bg-warning text-dark me-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>عرض الملفات التي بها مشاكل في المسار فقط
                </span>
                <a href="{{ route('assets.index', request()->except(['path_issues', 'page'])) }}" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-x-circle me-1"></i>إلغاء فلتر المشاكل
                </a>
            @else
                <a href="{{ route('assets.index', array_merge(request()->query(), ['path_issues' => 1])) }}" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>عرض الملفات التي بها مشاكل في المسار فقط
                    @if(($stats['path_issues_count'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $stats['path_issues_count'] }}</span>
                    @endif
                </a>
                <small class="text-muted ms-2">(ملفات مسجلة في القاعدة وغير موجودة على القرص حسب المسار النسبي/الأصلي)</small>
            @endif
        </div>
        @if(request()->hasAny(['search', 'content_categories', 'content_category', 'scholar_ids', 'scholar_id', 'gregorian_year', 'orientation', 'playlist', 'path_issues']) || !empty($folder_filter) || (request('publish_status') && request('publish_status') !== 'all'))
            <div class="mt-3 col-12">
                @if(!empty($folder_filter))
                <span class="text-muted me-2">تعرض النتائج للمجلد: <strong>{{ $folder_filter }}</strong></span>
                <a href="{{ route('assets.index', ['view' => 'browse', 'path' => $folder_filter]) }}" class="btn btn-sm btn-outline-primary me-1">
                    <i class="bi bi-folder2-open me-2 ms-2"></i>تصفح بالمجلدات
                </a>
                @endif
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-2 ms-2"></i>إزالة الفلاتر
                </a>
            </div>
        @endif
    </div>
</div>
@endif

<!-- Assets Table -->
<div class="card">
    <div class="card-body">
        @if($preparing_mode ?? false)
        <p class="text-muted small mb-3">
            <i class="bi bi-hourglass-split me-1"></i>
            يعرض هذا القسم الفيديوهات غير المنشورة على الموقع (قيد التجهيز).
        </p>
        @endif
        @if($assets->count() > 0)
            <div id="bulkActionsBar" class="alert alert-secondary py-2 mb-3 d-none" role="alert">
                <span class="me-3"><strong id="bulkSelectedCount">0</strong> فيديو محدد</span>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="bulkClearSelection">
                    <i class="bi bi-x-circle me-1"></i>إلغاء التحديد
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="bulkPublishBtn">
                    <i class="bi bi-check-circle me-1"></i>تفعيل النشر للمحدد
                </button>
                <button type="button" class="btn btn-sm btn-warning me-2" id="bulkUnpublishBtn">
                    <i class="bi bi-x-circle me-1"></i>إلغاء النشر للمحدد
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark" id="bulkSettingsBtn" title="تغيير اسم المتحدث وتصنيفات المحتوى للمحدد">
                    <i class="bi bi-gear me-1"></i>تغيير إعدادات عامة
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="bulkRenameTitlesBtn" title="تغيير أسماء وصور الحلقات المحددة">
                    <i class="bi bi-pencil-square me-1"></i>تغيير أسماء الحلقات
                </button>
                <button type="button" class="btn btn-sm btn-outline-info d-none" id="bulkMergeBtn" title="دمج الفيديو: اختر سجلاً للإبقاء عليه وحذف الباقي">
                    <i class="bi bi-merge"></i> دمج الفيديو
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="bulkTranscribeBtn" title="استخراج المحتوى النصي تلقائياً (tiny · أول 5 دقائق)">
                    <i class="bi bi-file-text me-1"></i>استخراج المحتوى النصي
                </button>
                <button type="button" class="btn btn-sm btn-success me-2" id="bulkExtractAudioBtn" title="تحويل الفيديو إلى ملف صوتي MP3">
                    <i class="bi bi-music-note-beamed me-1"></i>تحويل إلى صوت
                </button>
                <button type="button" class="btn btn-sm btn-info text-white me-2" id="bulkAnalyzeBtn" title="تحليل المحتوى النصي عبر DeepSeek">
                    <i class="bi bi-tags me-1"></i>تحليل المحتوى النصي
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary me-2" id="bulkTranslateBtn" title="ترجمة المحتوى النصي إلى كل اللغات المتاحة لكل فيديو محدد">
                    <i class="bi bi-translate me-1"></i>ترجمة الفيديوات المحددة
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" title="حذف السجلات المحددة">
                    <i class="bi bi-trash"></i> حذف
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 2.5rem;" title="اختر حلقة">
                                <input type="checkbox" class="form-check-input" id="selectAllAssets" title="تحديد كل الحلقات">
                            </th>
                            <th>
                                <a href="{{ getSortUrl('id', $currentSortBy, $currentSortDir) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    ID
                                    @if($currentSortBy === 'id')
                                        <i class="bi bi-arrow-{{ $currentSortDir === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up ms-1 text-muted opacity-50"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ getSortUrl('title', $currentSortBy, $currentSortDir) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    العنوان
                                    @if($currentSortBy === 'title')
                                        <i class="bi bi-arrow-{{ $currentSortDir === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up ms-1 text-muted opacity-50"></i>
                                    @endif
                                </a>
                            </th>
                            <th>اسم المتحدث</th>
                            <th>تصنيفات المحتوى</th>
                            <th>السنة الميلادية</th>
                            <th>
                                <a href="{{ getSortUrl('duration_seconds', $currentSortBy, $currentSortDir) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    المدة
                                    @if($currentSortBy === 'duration_seconds')
                                        <i class="bi bi-arrow-{{ $currentSortDir === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up ms-1 text-muted opacity-50"></i>
                                    @endif
                                </a>
                            </th>
                            <th>الاتجاه</th>
                            <th>
                                <a href="{{ getSortUrl('is_publishable', $currentSortBy, $currentSortDir) }}" class="text-decoration-none text-dark d-flex align-items-center">
                                    حالة النشر
                                    @if($currentSortBy === 'is_publishable')
                                        <i class="bi bi-arrow-{{ $currentSortDir === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up ms-1 text-muted opacity-50"></i>
                                    @endif
                                </a>
                            </th>
                            <th>حالة الترجمة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                        <tr class="{{ ($asset->file_missing ?? false) ? 'table-danger' : '' }}" data-asset-id="{{ $asset->id }}" data-asset-title="{{ e($asset->title ?? $asset->file_name ?? '') }}" data-asset-thumbnail="{{ ($asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path)) ? asset('storage/' . $asset->thumbnail_path) : '' }}">
                            <td>
                                <input type="checkbox" class="form-check-input asset-row-cb" name="ids[]" value="{{ $asset->id }}" data-id="{{ $asset->id }}" title="اختر حلقة">
                            </td>
                            <td>{{ $asset->id }}</td>
                            <td>
                                @if($asset->file_missing ?? false)
                                    <span class="badge bg-danger me-1" title="الملف غير موجود على القرص">مشكلة</span>
                                @endif
                                <strong class="text-primary">{{ $asset->title }}</strong>
                                @php
                                    $pathForName = $asset->original_path ?? $asset->relative_path ?? '';
                                    $originalFileName = $pathForName !== '' ? basename(str_replace('\\', '/', $pathForName)) : ($asset->file_name ?? '');
                                @endphp
                                @if($originalFileName)
                                    <div class="small text-muted mt-1" title="{{ $pathForName }}">{{ $originalFileName }}</div>
                                @endif
                            </td>
                            <td>
                                @if($asset->speaker_name)
                                    <span class="badge bg-success">{{ $asset->speaker_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->categories && $asset->categories->count() > 0)
                                    @foreach($asset->categories as $cat)
                                        <a href="{{ route('categories.show', $cat) }}" class="badge bg-secondary text-decoration-none me-1">{{ $cat->name }}</a>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->gregorian_year)
                                    <span class="badge bg-info">{{ $asset->gregorian_year }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->duration_seconds)
                                    {{ $asset->duration_formatted }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->orientation)
                                    @if($asset->orientation == 'portrait')
                                        <span class="badge bg-info">عمودي</span>
                                    @elseif($asset->orientation == 'landscape')
                                        <span class="badge bg-success">أفقي</span>
                                    @else
                                        <span class="badge bg-secondary">مربع</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($asset->is_publishable)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-2 ms-2"></i>منشور
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-x-circle me-2 ms-2"></i>غير منشور
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $translationLangs = $translationLanguages ?? [];
                                    $segments = is_array($asset->translation_segments ?? null) ? $asset->translation_segments : [];
                                    $translatedCodes = array_keys(array_filter(array_intersect_key($segments, $translationLangs)));
                                    $totalLangs = count($translationLangs);
                                    $numTranslated = count($translatedCodes);
                                    $missingCodes = array_diff(array_keys($translationLangs), $translatedCodes);
                                @endphp
                                @if($totalLangs === 0)
                                    <span class="badge bg-secondary" title="لا توجد لغات ترجمة معرّفة">-</span>
                                @elseif($numTranslated === 0)
                                    <span class="badge bg-secondary" title="لا توجد ترجمة للمحتوى النصي">
                                        <i class="bi bi-translate me-1"></i>غير مترجم
                                    </span>
                                @elseif($numTranslated >= $totalLangs)
                                    <span class="badge bg-success" title="مترجم إلى كل اللغات المتاحة">
                                        <i class="bi bi-check-circle me-1"></i>مترجم (كل اللغات)
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark" title="اللغات المترجمة: {{ implode(', ', array_map(fn($c) => $translationLangs[$c] ?? $c, $translatedCodes)) }} — الناقص: {{ implode(', ', array_map(fn($c) => $translationLangs[$c] ?? $c, $missingCodes)) }}">
                                        <i class="bi bi-translate me-1"></i>جزئي ({{ $numTranslated }}/{{ $totalLangs }} لغة)
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-primary" title="عرض التفاصيل">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="showAssetPath({{ $asset->id }}, '{{ addslashes($asset->original_path ?? $asset->relative_path ?? '-') }}', '{{ addslashes($asset->relative_path ?? '-') }}')"
                                            title="عرض المسار">
                                        <i class="bi bi-folder"></i>
                                    </button>
                                    <form action="{{ route('assets.destroy', $asset) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" 
                                                onclick="return confirm('هل أنت متأكد من حذف هذا الملف؟')"
                                                title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    عرض {{ $assets->firstItem() ?? 0 }} إلى {{ $assets->lastItem() ?? 0 }} من {{ $assets->total() }} نتيجة
                </div>
                <div>
                    {{ $assets->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-play-circle fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد فيديوهات</p>
            </div>
        @endif
    </div>
</div>
@endif

@push('scripts')
<script>
// المسار الثابت للمجلد الرئيسي
const BASE_PATH = '/Users/mohamedabdelrahman/Desktop/2025';

function copyFolderPath(relativePath, button) {
    // إزالة اسم الملف من المسار (أخذ المجلد فقط)
    const folderPath = getFolderPath(relativePath);
    
    // بناء المسار الكامل
    const fullPath = BASE_PATH + '/' + folderPath;
    
    // نسخ المسار
    copyToClipboard(fullPath, button);
}

function getFolderPath(relativePath) {
    // إزالة اسم الملف من المسار
    // مثال: "ادعية 1447/اللهم داوني بدوايِك.mp4" -> "ادعية 1447"
    const parts = relativePath.split('/');
    if (parts.length > 1) {
        // إزالة آخر جزء (اسم الملف)
        parts.pop();
        return parts.join('/');
    }
    // إذا كان الملف في الجذر، نعيد string فارغ
    return '';
}

function copyToClipboard(text, button) {
    // إنشاء input مؤقت لنسخ النص
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // للجوالات
    
    try {
        // نسخ النص
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // تغيير الأيقونة مؤقتاً لإظهار النجاح
        const icon = button.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'bi bi-check-circle-fill';
        button.classList.remove('btn-outline-success');
        button.classList.add('btn-success');
        
        // إظهار رسالة نجاح
        showToast('تم نسخ مسار المجلد بنجاح!', 'success');
        
        // إعادة الأيقونة الأصلية بعد ثانيتين
        setTimeout(() => {
            icon.className = originalClass;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-success');
        }, 2000);
    } catch (err) {
        document.body.removeChild(tempInput);
        // استخدام Clipboard API كبديل
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('تم نسخ مسار المجلد بنجاح!', 'success');
            }).catch(() => {
                showToast('فشل نسخ المسار', 'error');
            });
        } else {
            showToast('المتصفح لا يدعم نسخ النص', 'error');
        }
    }
}

function showToast(message, type) {
    // إنشاء toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // إزالة Toast بعد 3 ثواني
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// اختيار متعدد (عرض القائمة)
(function() {
    const selectAllEl = document.getElementById('selectAllAssets');
    const rowCheckboxes = document.querySelectorAll('.asset-row-cb');
    const bulkBar = document.getElementById('bulkActionsBar');
    const bulkCountEl = document.getElementById('bulkSelectedCount');
    const bulkClearBtn = document.getElementById('bulkClearSelection');
    const bulkPublishBtn = document.getElementById('bulkPublishBtn');
    const bulkUnpublishBtn = document.getElementById('bulkUnpublishBtn');

    const bulkMergeBtn = document.getElementById('bulkMergeBtn');
    function updateBulkBar() {
        const checked = document.querySelectorAll('.asset-row-cb:checked');
        const n = checked.length;
        if (bulkBar) bulkBar.classList.toggle('d-none', n === 0);
        if (bulkCountEl) bulkCountEl.textContent = n;
        if (selectAllEl) selectAllEl.checked = n > 0 && n === rowCheckboxes.length;
        if (selectAllEl) selectAllEl.indeterminate = n > 0 && n < rowCheckboxes.length;
        if (bulkMergeBtn) bulkMergeBtn.classList.toggle('d-none', n < 2);
    }

    if (selectAllEl && rowCheckboxes.length) {
        selectAllEl.addEventListener('change', function() {
            rowCheckboxes.forEach(function(cb) { cb.checked = selectAllEl.checked; });
            updateBulkBar();
        });
    }
    rowCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', updateBulkBar);
    });
    if (bulkClearBtn) {
        bulkClearBtn.addEventListener('click', function() {
            rowCheckboxes.forEach(function(cb) { cb.checked = false; });
            if (selectAllEl) selectAllEl.checked = false;
            updateBulkBar();
        });
    }

    function submitBulk(action) {
        const ids = Array.from(document.querySelectorAll('.asset-row-cb:checked')).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action === 'publish' ? '{{ route("assets.bulk-publish") }}' : '{{ route("assets.bulk-unpublish") }}';
        form.style.display = 'none';
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = '_token'; input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }
        ids.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
    if (bulkPublishBtn) bulkPublishBtn.addEventListener('click', function() { submitBulk('publish'); });
    if (bulkUnpublishBtn) bulkUnpublishBtn.addEventListener('click', function() { submitBulk('unpublish'); });

    function submitBulkDelete() {
        const ids = Array.from(document.querySelectorAll('.asset-row-cb:checked')).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;
        if (!confirm('هل تريد حذف ' + ids.length + ' سجل من قاعدة البيانات؟ لا يمكن التراجع عن هذا الإجراء.')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("assets.bulk-delete") }}';
        form.style.display = 'none';
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = '_token'; input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }
        ids.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) bulkDeleteBtn.addEventListener('click', submitBulkDelete);

    // دمج الفيديو: اختيار سجل للإبقاء عليه وحذف الباقي
    const bulkMergeModal = document.getElementById('bulkMergeModal');
    const bulkMergeForm = document.getElementById('bulkMergeForm');
    const mergeAssetList = document.getElementById('mergeAssetList');
    if (bulkMergeBtn) {
        bulkMergeBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.asset-row-cb:checked');
            if (checked.length < 2) {
                alert('يجب تحديد فيديوين على الأقل لاستخدام دمج الفيديو.');
                return;
            }
            const items = [];
            checked.forEach(function(cb) {
                const tr = cb.closest('tr');
                if (tr) {
                    items.push({ id: cb.value, title: tr.getAttribute('data-asset-title') || ('فيديو ' + cb.value) });
                }
            });
            mergeAssetList.innerHTML = '';
            items.forEach(function(item, idx) {
                const label = document.createElement('label');
                label.className = 'list-group-item list-group-item-action d-flex align-items-center';
                label.style.cursor = 'pointer';
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'keep_id';
                radio.value = item.id;
                radio.className = 'form-check-input me-2';
                radio.required = true;
                if (idx === 0) radio.checked = true;
                label.appendChild(radio);
                label.appendChild(document.createTextNode(' #' + item.id + ' — ' + item.title));
                label.addEventListener('click', function() { radio.checked = true; });
                mergeAssetList.appendChild(label);
            });
            bulkMergeForm.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) {
                let tok = bulkMergeForm.querySelector('input[name="_token"]');
                if (!tok) { tok = document.createElement('input'); tok.type = 'hidden'; tok.name = '_token'; bulkMergeForm.appendChild(tok); }
                tok.value = csrf.getAttribute('content');
            }
            items.forEach(function(item) {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'ids[]'; input.value = item.id;
                bulkMergeForm.appendChild(input);
            });
            bootstrap.Modal.getOrCreateInstance(bulkMergeModal).show();
        });
    }
    if (bulkMergeForm) {
        bulkMergeForm.addEventListener('submit', function(e) {
            const keepId = bulkMergeForm.querySelector('input[name="keep_id"]:checked');
            if (!keepId) {
                e.preventDefault();
                alert('اختر السجل الذي تريد الإبقاء عليه.');
                return;
            }
        });
    }

    // تغيير أسماء الحلقات
    const bulkRenameTitlesBtn = document.getElementById('bulkRenameTitlesBtn');
    const bulkRenameTitlesModal = document.getElementById('bulkRenameTitlesModal');
    const bulkRenameTitlesForm = document.getElementById('bulkRenameTitlesForm');
    const bulkRenameTitlesList = document.getElementById('bulkRenameTitlesList');
    const bulkRenameTitlesCountEl = document.getElementById('bulkRenameTitlesCount');
    const bulkRenameScrollHint = document.getElementById('bulkRenameScrollHint');
    const bulkRenameTitlesStatus = document.getElementById('bulkRenameTitlesStatus');
    const bulkRenameTitlesSubmitBtn = document.getElementById('bulkRenameTitlesSubmitBtn');
    const bulkRenameTitlesCancelBtn = document.getElementById('bulkRenameTitlesCancelBtn');
    const assetsBaseUrl = '{{ url("/assets") }}'.replace(/\/$/, '');

    function escapeHtmlText(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    if (bulkRenameTitlesBtn) {
        bulkRenameTitlesBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.asset-row-cb:checked');
            if (checked.length === 0) {
                alert('يجب اختيار حلقة واحدة على الأقل.');
                return;
            }
            const items = [];
            checked.forEach(function(cb) {
                const tr = cb.closest('tr');
                if (tr) {
                    items.push({
                        id: cb.value,
                        title: tr.getAttribute('data-asset-title') || ('حلقة ' + cb.value),
                        thumbnail: tr.getAttribute('data-asset-thumbnail') || ''
                    });
                }
            });
            if (bulkRenameTitlesCountEl) bulkRenameTitlesCountEl.textContent = items.length;
            if (bulkRenameScrollHint) bulkRenameScrollHint.classList.toggle('d-none', items.length <= 8);
            setBulkRenameStatus('', false);
            setBulkRenameBusy(false);
            if (bulkRenameTitlesList) {
                bulkRenameTitlesList.innerHTML = '';
                items.forEach(function(item) {
                    const tr = document.createElement('tr');
                    tr.dataset.originalTitle = item.title;
                    tr.dataset.assetId = item.id;
                    const thumbHtml = item.thumbnail
                        ? `<img src="${escapeHtmlText(item.thumbnail)}" alt="" class="bulk-rename-thumb mb-1" data-role="current-thumb">`
                        : `<div class="bulk-rename-thumb-placeholder mb-1" data-role="current-thumb"><i class="bi bi-image"></i></div>`;
                    tr.innerHTML = `
                        <td class="text-muted">#${escapeHtmlText(item.id)}</td>
                        <td>
                            ${thumbHtml}
                            <img src="" alt="" class="bulk-rename-thumb mb-1 d-none" data-role="new-thumb-preview">
                            <input type="file" class="form-control form-control-sm bulk-rename-thumb-input" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-role="thumb-input">
                            <small class="text-muted d-none" data-role="thumb-status"></small>
                        </td>
                        <td class="text-truncate" style="max-width: 12rem;" title="${escapeHtmlText(item.title)}">${escapeHtmlText(item.title)}</td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="titles[${escapeHtmlText(item.id)}]" value="${escapeHtmlText(item.title)}" maxlength="255" placeholder="الاسم الجديد" data-role="title-input">
                        </td>
                    `;
                    const thumbInput = tr.querySelector('[data-role="thumb-input"]');
                    const newPreview = tr.querySelector('[data-role="new-thumb-preview"]');
                    const currentThumb = tr.querySelector('[data-role="current-thumb"]');
                    if (thumbInput && newPreview) {
                        thumbInput.addEventListener('change', function() {
                            const file = thumbInput.files && thumbInput.files[0];
                            if (!file) {
                                newPreview.classList.add('d-none');
                                newPreview.removeAttribute('src');
                                if (currentThumb) currentThumb.classList.remove('d-none');
                                return;
                            }
                            const reader = new FileReader();
                            reader.onload = function(ev) {
                                newPreview.src = ev.target.result;
                                newPreview.classList.remove('d-none');
                                if (currentThumb) currentThumb.classList.add('d-none');
                            };
                            reader.readAsDataURL(file);
                        });
                    }
                    bulkRenameTitlesList.appendChild(tr);
                });
            }
            bootstrap.Modal.getOrCreateInstance(bulkRenameTitlesModal).show();
        });
    }
    function setBulkRenameStatus(message, isError) {
        if (!bulkRenameTitlesStatus) return;
        bulkRenameTitlesStatus.textContent = message || '';
        bulkRenameTitlesStatus.classList.toggle('d-none', !message);
        bulkRenameTitlesStatus.classList.toggle('text-danger', !!isError);
        bulkRenameTitlesStatus.classList.toggle('text-muted', !isError);
    }

    function setBulkRenameBusy(busy) {
        if (bulkRenameTitlesSubmitBtn) bulkRenameTitlesSubmitBtn.disabled = busy;
        if (bulkRenameTitlesCancelBtn) bulkRenameTitlesCancelBtn.disabled = busy;
        if (bulkRenameTitlesForm) {
            bulkRenameTitlesForm.querySelectorAll('input, button').forEach(function(el) {
                if (el.id === 'bulkRenameTitlesSubmitBtn' || el.id === 'bulkRenameTitlesCancelBtn') return;
                el.disabled = busy;
            });
        }
    }

    function postFormData(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        }).then(async function(res) {
            let data = null;
            try { data = await res.json(); } catch (e) {}
            if (!res.ok) {
                const err = data && (data.message || data.error) ? (data.message || data.error) : ('خطأ ' + res.status);
                throw new Error(err);
            }
            return data || {};
        });
    }

    function uploadThumbnailOne(assetId, file, csrf) {
        const formData = new FormData();
        formData.append('_token', csrf);
        formData.append('thumbnail', file);
        return postFormData(assetsBaseUrl + '/' + assetId + '/upload-thumbnail', formData);
    }

    if (bulkRenameTitlesForm) {
        bulkRenameTitlesForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const rows = bulkRenameTitlesForm.querySelectorAll('#bulkRenameTitlesList tr');
            if (rows.length === 0) {
                alert('لم يتم اختيار أي حلقة.');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const titleChanges = [];
            const thumbUploads = [];

            rows.forEach(function(row) {
                const assetId = row.dataset.assetId;
                const originalTitle = (row.dataset.originalTitle || '').trim();
                const titleInput = row.querySelector('[data-role="title-input"]');
                const thumbInput = row.querySelector('[data-role="thumb-input"]');
                const newTitle = titleInput ? titleInput.value.trim() : '';
                if (newTitle !== originalTitle) {
                    titleChanges.push({ assetId: assetId, title: newTitle });
                }
                if (thumbInput && thumbInput.files && thumbInput.files.length > 0) {
                    thumbUploads.push({ assetId: assetId, file: thumbInput.files[0], row: row });
                }
            });

            if (titleChanges.length === 0 && thumbUploads.length === 0) {
                alert('لم يتم تغيير أي اسم أو صورة — عدّل الاسم أو اختر صورة جديدة.');
                return;
            }

            setBulkRenameBusy(true);
            setBulkRenameStatus('جاري الحفظ...', false);

            try {
                if (titleChanges.length > 0) {
                    const titlesForm = new FormData();
                    titlesForm.append('_token', csrf);
                    titleChanges.forEach(function(item) {
                        titlesForm.append('titles[' + item.assetId + ']', item.title);
                    });
                    setBulkRenameStatus('جاري حفظ الأسماء...', false);
                    await postFormData(bulkRenameTitlesForm.action, titlesForm);
                }

                const thumbErrors = [];
                for (let i = 0; i < thumbUploads.length; i++) {
                    const item = thumbUploads[i];
                    const statusEl = item.row.querySelector('[data-role="thumb-status"]');
                    if (statusEl) {
                        statusEl.classList.remove('d-none', 'text-success', 'text-danger');
                        statusEl.classList.add('text-muted');
                        statusEl.textContent = 'جاري الرفع...';
                    }
                    setBulkRenameStatus('جاري رفع الصورة ' + (i + 1) + ' من ' + thumbUploads.length + '...', false);
                    try {
                        await uploadThumbnailOne(item.assetId, item.file, csrf);
                        if (statusEl) {
                            statusEl.classList.remove('text-muted', 'text-danger');
                            statusEl.classList.add('text-success');
                            statusEl.textContent = 'تم الرفع';
                        }
                    } catch (err) {
                        thumbErrors.push({ assetId: item.assetId, message: err.message || 'فشل الرفع' });
                        if (statusEl) {
                            statusEl.classList.remove('text-muted', 'text-success');
                            statusEl.classList.add('text-danger');
                            statusEl.textContent = err.message || 'فشل الرفع';
                        }
                    }
                }

                if (thumbErrors.length > 0 && titleChanges.length === 0) {
                    setBulkRenameStatus('فشل رفع ' + thumbErrors.length + ' صورة. راجع التفاصيل بجانب كل حلقة.', true);
                    setBulkRenameBusy(false);
                    return;
                }

                if (thumbErrors.length > 0) {
                    alert('تم حفظ الأسماء، لكن فشل رفع ' + thumbErrors.length + ' صورة.');
                }

                window.location.reload();
            } catch (err) {
                setBulkRenameStatus(err.message || 'حدث خطأ أثناء الحفظ', true);
                setBulkRenameBusy(false);
            }
        });
    }

    // اختيار قوائم التشغيل الهرمي (إجراء جماعي)
    const bulkPlaylistTree = @json($playlistTree ?? []);
    const bulkPlaylistById = {};
    (function indexBulkPlaylistTree(nodes) {
        (nodes || []).forEach(function(node) {
            bulkPlaylistById[node.id] = node;
            if (node.children && node.children.length) {
                indexBulkPlaylistTree(node.children);
            }
        });
    })(bulkPlaylistTree);

    function getBulkPlaylistCardContainers() {
        return [
            document.getElementById('bulkPlaylistRootCardsContainer'),
            document.getElementById('bulkPlaylistSubCardsContainer'),
            document.getElementById('bulkPlaylistSub2CardsContainer')
        ].filter(Boolean);
    }

    function getBulkSelectedPlaylistIds() {
        const ids = new Set();
        getBulkPlaylistCardContainers().forEach(function(container) {
            container.querySelectorAll('.playlist-card-selectable.selected').forEach(function(card) {
                ids.add(parseInt(card.getAttribute('data-playlist-id'), 10));
            });
        });
        return Array.from(ids);
    }

    function setBulkPlaylistCardSelected(id, selected) {
        getBulkPlaylistCardContainers().forEach(function(container) {
            const card = container.querySelector('.playlist-card-selectable[data-playlist-id="' + id + '"]');
            if (card) {
                card.classList.toggle('selected', selected);
            }
        });
    }

    function selectBulkPlaylistAncestors(playlistId) {
        let node = bulkPlaylistById[playlistId];
        while (node && node.parent_id) {
            setBulkPlaylistCardSelected(node.parent_id, true);
            node = bulkPlaylistById[node.parent_id];
        }
    }

    function deselectBulkPlaylistDescendants(playlistId) {
        const node = bulkPlaylistById[playlistId];
        if (!node || !node.children) return;
        node.children.forEach(function(child) {
            setBulkPlaylistCardSelected(child.id, false);
            deselectBulkPlaylistDescendants(child.id);
        });
    }

    function escapeBulkPlaylistHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function renderBulkPlaylistCardHtml(node, extraClass) {
        const imageHtml = node.image_path
            ? '<img src="/storage/' + String(node.image_path).replace(/^\/+/, '') + '" alt="" class="playlist-card-image">'
            : '<div class="playlist-card-icon"><i class="bi bi-collection-play"></i></div>';
        const selectedClass = getBulkSelectedPlaylistIds().includes(node.id) ? 'selected' : '';
        return '<div class="col-auto"><div class="playlist-card-selectable bulk-playlist-card ' + (extraClass || '') + ' ' + selectedClass + '" data-playlist-id="' + node.id + '" onclick="toggleBulkPlaylistCard(this)">' +
            imageHtml +
            '<div class="playlist-card-title">' + escapeBulkPlaylistHtml(node.title) + '</div>' +
            '<div class="playlist-card-check"><i class="bi bi-check-circle-fill"></i></div>' +
            '</div></div>';
    }

    function renderBulkChildrenCards(parentIds, containerId, sectionId, extraClass) {
        const container = document.getElementById(containerId);
        const section = document.getElementById(sectionId);
        if (!container || !section) return;
        const html = [];
        (parentIds || []).forEach(function(parentId) {
            const parent = bulkPlaylistById[parentId];
            if (!parent || !parent.children) return;
            parent.children.forEach(function(child) {
                html.push(renderBulkPlaylistCardHtml(child, extraClass));
            });
        });
        container.innerHTML = html.join('');
        section.classList.toggle('d-none', html.length === 0);
    }

    function refreshBulkPlaylistHierarchyPanels() {
        const selectedRootIds = [];
        const rootContainer = document.getElementById('bulkPlaylistRootCardsContainer');
        if (rootContainer) {
            rootContainer.querySelectorAll('.playlist-card-selectable.selected').forEach(function(card) {
                selectedRootIds.push(parseInt(card.getAttribute('data-playlist-id'), 10));
            });
        }
        renderBulkChildrenCards(selectedRootIds, 'bulkPlaylistSubCardsContainer', 'bulkPlaylistSubSection', 'playlist-sub-card');

        const selectedSubIds = [];
        const subContainer = document.getElementById('bulkPlaylistSubCardsContainer');
        if (subContainer) {
            subContainer.querySelectorAll('.playlist-card-selectable.selected').forEach(function(card) {
                selectedSubIds.push(parseInt(card.getAttribute('data-playlist-id'), 10));
            });
        }
        renderBulkChildrenCards(selectedSubIds, 'bulkPlaylistSub2CardsContainer', 'bulkPlaylistSub2Section', 'playlist-sub-card');
    }

    window.toggleBulkPlaylistCard = function(el) {
        const id = parseInt(el.getAttribute('data-playlist-id'), 10);
        const willSelect = !el.classList.contains('selected');
        setBulkPlaylistCardSelected(id, willSelect);
        if (willSelect) {
            selectBulkPlaylistAncestors(id);
        } else {
            deselectBulkPlaylistDescendants(id);
        }
        refreshBulkPlaylistHierarchyPanels();
        getBulkSelectedPlaylistIds().forEach(function(selectedId) {
            setBulkPlaylistCardSelected(selectedId, true);
        });
    };

    function resetBulkPlaylistSelection() {
        getBulkPlaylistCardContainers().forEach(function(container) {
            container.querySelectorAll('.playlist-card-selectable').forEach(function(card) {
                card.classList.remove('selected');
            });
        });
        refreshBulkPlaylistHierarchyPanels();
    }

    // تغيير إعدادات عامة: عند فتح النافذة تحديث العدد، وعند الإرسال إضافة المحدد
    const bulkSettingsBtn = document.getElementById('bulkSettingsBtn');
    const bulkSettingsModal = document.getElementById('bulkSettingsModal');
    const bulkSettingsForm = document.getElementById('bulkSettingsForm');
    const bulkSettingsCountEl = document.getElementById('bulkSettingsCount');
    const applySpeakerCb = document.getElementById('apply_speaker');
    const applyCategoriesCb = document.getElementById('apply_categories');

    if (bulkSettingsBtn) {
        bulkSettingsBtn.addEventListener('click', function() {
            const n = document.querySelectorAll('.asset-row-cb:checked').length;
            if (n === 0) {
                alert('يجب تحديد فيديو واحد على الأقل.');
                return;
            }
            if (bulkSettingsCountEl) bulkSettingsCountEl.textContent = n;
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkSettingsModal'));
            modal.show();
        });
    }
    if (bulkSettingsModal) {
        bulkSettingsModal.addEventListener('show.bs.modal', function() {
            const n = document.querySelectorAll('.asset-row-cb:checked').length;
            if (bulkSettingsCountEl) bulkSettingsCountEl.textContent = n;
            if (typeof resetBulkPlaylistSelection === 'function') {
                resetBulkPlaylistSelection();
            }
        });
    }
    var applyPlaylistCb = document.getElementById('apply_playlist');
    var applyRemovePlaylistCb = document.getElementById('apply_remove_playlist');
    if (applyPlaylistCb && applyRemovePlaylistCb) {
        applyPlaylistCb.addEventListener('change', function() {
            if (applyPlaylistCb.checked) applyRemovePlaylistCb.checked = false;
        });
        applyRemovePlaylistCb.addEventListener('change', function() {
            if (applyRemovePlaylistCb.checked) applyPlaylistCb.checked = false;
        });
    }
    if (bulkSettingsForm) {
        bulkSettingsForm.addEventListener('submit', function(e) {
            const ids = Array.from(document.querySelectorAll('.asset-row-cb:checked')).map(function(cb) { return cb.value; });
            if (ids.length === 0) {
                e.preventDefault();
                alert('لم يتم تحديد أي فيديو.');
                return;
            }
            var form = e.target;
            var applySpeaker = form.querySelector('input[name="apply_speaker"]') && form.querySelector('input[name="apply_speaker"]').checked;
            var applyCategories = form.querySelector('input[name="apply_categories"]') && form.querySelector('input[name="apply_categories"]').checked;
            var applyGregorian = form.querySelector('input[name="apply_gregorian_year"]') && form.querySelector('input[name="apply_gregorian_year"]').checked;
            var applyPlaylist = form.querySelector('input[name="apply_playlist"]') && form.querySelector('input[name="apply_playlist"]').checked;
            var applyRemovePlaylist = form.querySelector('input[name="apply_remove_playlist"]') && form.querySelector('input[name="apply_remove_playlist"]').checked;
            var applyShowTranslation = form.querySelector('input[name="apply_show_translation"]') && form.querySelector('input[name="apply_show_translation"]').checked;
            var applyShowComments = form.querySelector('input[name="apply_show_comments"]') && form.querySelector('input[name="apply_show_comments"]').checked;
            if (!applySpeaker && !applyCategories && !applyGregorian && !applyPlaylist && !applyRemovePlaylist && !applyShowTranslation && !applyShowComments) {
                e.preventDefault();
                alert('فعّل خياراً واحداً على الأقل: اسم المتحدث، تصنيفات المحتوى، السنة الميلادية، قائمة التشغيل، إظهار الترجمة، أو إظهار التعليقات.');
                return;
            }
            if (applyPlaylist) {
                var selectedPlaylistIds = typeof getBulkSelectedPlaylistIds === 'function'
                    ? getBulkSelectedPlaylistIds()
                    : [];
                if (!selectedPlaylistIds.length) {
                    e.preventDefault();
                    alert('اختر قائمة تشغيل واحدة على الأقل عند تفعيل «إضافة المحدد إلى قائمة تشغيل».');
                    return;
                }
                bulkSettingsForm.querySelectorAll('input[name="playlist_ids[]"]').forEach(function(el) { el.remove(); });
                selectedPlaylistIds.forEach(function(pid) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'playlist_ids[]';
                    input.value = pid;
                    bulkSettingsForm.appendChild(input);
                });
            }
            // إزالة أي ids[] قديمة من النموذج ثم إضافة المحدد
            bulkSettingsForm.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
            const csrf = document.querySelector('meta[name="csrf-token"]');
            ids.forEach(function(id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                bulkSettingsForm.appendChild(input);
            });
        });
    }
})();

// اختيار متعدد (وضع التصفح بالمجلدات)
(function() {
    const browseCbs = document.querySelectorAll('.asset-browse-cb');
    if (!browseCbs.length) return;
    browseCbs.forEach(function(cb) {
        cb.addEventListener('click', function(e) { e.stopPropagation(); });
    });
    const browseBar = document.getElementById('browseBulkBar');
    const browseCountEl = document.getElementById('browseSelectedCount');
    const browseClearBtn = document.getElementById('browseBulkClear');
    const browsePublishBtn = document.getElementById('browseBulkPublish');
    const browseUnpublishBtn = document.getElementById('browseBulkUnpublish');

    function updateBrowseBar() {
        const checked = document.querySelectorAll('.asset-browse-cb:checked');
        const n = checked.length;
        if (browseBar) browseBar.classList.toggle('d-none', n === 0);
        if (browseCountEl) browseCountEl.textContent = n;
    }
    browseCbs.forEach(function(cb) { cb.addEventListener('change', updateBrowseBar); });
    if (browseClearBtn) {
        browseClearBtn.addEventListener('click', function() {
            browseCbs.forEach(function(cb) { cb.checked = false; });
            updateBrowseBar();
        });
    }
    function submitBrowseBulk(action) {
        const ids = Array.from(document.querySelectorAll('.asset-browse-cb:checked')).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action === 'publish' ? '{{ route("assets.bulk-publish") }}' : '{{ route("assets.bulk-unpublish") }}';
        form.style.display = 'none';
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = '_token'; input.value = csrf.getAttribute('content');
            form.appendChild(input);
        }
        ids.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }
    if (browsePublishBtn) browsePublishBtn.addEventListener('click', function() { submitBrowseBulk('publish'); });
    if (browseUnpublishBtn) browseUnpublishBtn.addEventListener('click', function() { submitBrowseBulk('unpublish'); });
})();

// التعامل مع كاردات تصنيفات المحتوى للفلترة
(function() {
    const categoryCards = document.querySelectorAll('.filter-category-card');
    categoryCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const checkbox = this.querySelector('.filter-category-cb');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                if (checkbox.checked) {
                    this.classList.add('selected');
                } else {
                    this.classList.remove('selected');
                }
            }
        });
    });
})();

// التعامل مع كاردات الشيوخ للفلترة
(function() {
    const scholarContainer = document.querySelector('.filter-scholar-cards');
    const scholarCards = document.querySelectorAll('.filter-scholar-card');
    const scholarMoreWrap = document.querySelector('.filter-scholar-more-wrap');
    const scholarMoreBtn = document.getElementById('filterScholarMoreBtn');
    let scholarExpanded = false;

    function applyScholarCollapseState() {
        if (!scholarContainer || scholarCards.length === 0) return;

        scholarCards.forEach(function(card) {
            card.classList.remove('is-hidden-by-more');
        });

        if (scholarExpanded) {
            if (scholarMoreBtn) scholarMoreBtn.textContent = 'Less';
            return;
        }

        const rowsTop = [];
        scholarCards.forEach(function(card) {
            const top = card.offsetTop;
            if (!rowsTop.includes(top)) rowsTop.push(top);
        });

        if (rowsTop.length <= 2) {
            if (scholarMoreWrap) scholarMoreWrap.classList.add('d-none');
            return;
        }

        const secondRowTop = rowsTop[1];
        scholarCards.forEach(function(card) {
            if (card.offsetTop > secondRowTop) {
                card.classList.add('is-hidden-by-more');
            }
        });

        if (scholarMoreBtn) scholarMoreBtn.textContent = 'More';
    }

    function updateScholarMoreVisibility() {
        if (!scholarContainer || scholarCards.length === 0 || !scholarMoreWrap) return;

        scholarCards.forEach(function(card) {
            card.classList.remove('is-hidden-by-more');
        });
        const rowsTop = [];
        scholarCards.forEach(function(card) {
            const top = card.offsetTop;
            if (!rowsTop.includes(top)) rowsTop.push(top);
        });
        const hasMoreThanTwoRows = rowsTop.length > 2;
        scholarMoreWrap.classList.toggle('d-none', !hasMoreThanTwoRows);

        if (!hasMoreThanTwoRows) {
            scholarExpanded = false;
            if (scholarMoreBtn) scholarMoreBtn.textContent = 'More';
            return;
        }

        applyScholarCollapseState();
    }

    scholarCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const checkbox = this.querySelector('.filter-scholar-cb');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                if (checkbox.checked) {
                    this.classList.add('selected');
                } else {
                    this.classList.remove('selected');
                }
            }
        });
    });

    if (scholarMoreBtn) {
        scholarMoreBtn.addEventListener('click', function() {
            scholarExpanded = !scholarExpanded;
            applyScholarCollapseState();
        });
    }

    updateScholarMoreVisibility();
    window.addEventListener('resize', function() {
        updateScholarMoreVisibility();
    });
})();

// التعامل مع كاردات السنة الميلادية للفلترة
(function() {
    const yearCards = document.querySelectorAll('.filter-year-card');
    yearCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const radio = this.querySelector('.filter-year-radio');
            if (radio) {
                // إلغاء تحديد جميع الكاردات الأخرى
                yearCards.forEach(function(c) {
                    c.classList.remove('selected');
                    const r = c.querySelector('.filter-year-radio');
                    if (r) r.checked = false;
                });
                // تحديد الكارد الحالي
                radio.checked = true;
                this.classList.add('selected');
            }
        });
    });
})();

// التعامل مع كاردات قائمة التشغيل للفلترة
(function() {
    const playlistCards = document.querySelectorAll('.filter-playlist-card');
    playlistCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const radio = this.querySelector('.filter-playlist-radio');
            if (radio) {
                // إلغاء تحديد جميع الكاردات الأخرى
                playlistCards.forEach(function(c) {
                    c.classList.remove('selected');
                    const r = c.querySelector('.filter-playlist-radio');
                    if (r) r.checked = false;
                });
                // تحديد الكارد الحالي
                radio.checked = true;
                this.classList.add('selected');
            }
        });
    });
})();

// استخراج المحتوى النصي + ترجمة الفيديوات المحددة
(function() {
    const assetsBaseUrl = '{{ url("/assets") }}'.replace(/\/$/, '');
    const translateBaseUrl = '{{ url("/video") }}'.replace(/\/$/, '');
    const translationLangList = @json(isset($translationLanguages) ? collect($translationLanguages)->map(fn($name, $code) => ['code' => $code, 'name' => $name])->values()->all() : []);
    const csrfEl = document.querySelector('meta[name="csrf-token"]');
    const token = csrfEl ? csrfEl.getAttribute('content') : '';
    const headersJson = {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };
    const batchTranscribePayload = {
        model: 'tiny',
        clip_start_seconds: 0,
        clip_end_seconds: 300
    };

    function getSelectedItems() {
        const listCb = document.querySelectorAll('.asset-row-cb:checked');
        const browseCb = document.querySelectorAll('.asset-browse-cb:checked');
        const nodes = listCb.length ? listCb : browseCb;
        return Array.from(nodes).map(function(cb) {
            var title = 'فيديو ' + cb.value;
            var tr = cb.closest('tr');
            if (tr && tr.getAttribute('data-asset-title')) {
                title = tr.getAttribute('data-asset-title');
            } else {
                var card = cb.closest('[data-asset-title]');
                if (card) title = card.getAttribute('data-asset-title') || title;
            }
            return { id: cb.value, title: title };
        });
    }

    function getSelectedIds() {
        return getSelectedItems().map(function(item) { return item.id; });
    }

    function openBatchTranscribeModal(items) {
        const modal = document.getElementById('batchTranscribeModal');
        if (!modal) return;
        document.getElementById('batchTranscribeTotal').textContent = items.length;
        document.getElementById('batchTranscribeCurrent').textContent = 'جاري التحضير...';
        document.getElementById('batchTranscribeProgressBar').style.width = '0%';
        document.getElementById('batchTranscribeProgressBar').textContent = '0%';
        const listEl = document.getElementById('batchTranscribeVideoList');
        listEl.innerHTML = items.map(function(item) {
            return '<li class="list-group-item d-flex justify-content-between align-items-center gap-2" data-id="' + item.id + '"><span class="text-truncate">#' + item.id + ' — ' + escapeHtmlBatch(item.title) + '</span><span class="badge bg-secondary flex-shrink-0">في الانتظار</span></li>';
        }).join('');
        document.getElementById('batchTranscribeCloseBtn').style.display = 'none';
        document.getElementById('batchTranscribeDismissBtn').style.display = 'none';
        document.getElementById('batchTranscribeSummary').classList.add('d-none');
        document.getElementById('batchTranscribeProgressBar').classList.add('progress-bar-animated');
        const bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
        if (bsModal) bsModal.show();
    }

    function escapeHtmlBatch(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function updateBatchTranscribeProgress(videoIndex, total, videoStatuses, message) {
        const currentEl = document.getElementById('batchTranscribeCurrent');
        const barEl = document.getElementById('batchTranscribeProgressBar');
        if (currentEl) {
            currentEl.textContent = 'فيديو ' + (videoIndex + 1) + ' من ' + total + (message ? ' — ' + message : '');
        }
        const pct = total > 0 ? Math.round((videoIndex / total) * 100) : 0;
        if (barEl) { barEl.style.width = pct + '%'; barEl.textContent = pct + '%'; }
        videoStatuses.forEach(function(s) {
            const li = document.querySelector('#batchTranscribeVideoList li[data-id="' + s.id + '"]');
            if (!li) return;
            const badge = li.querySelector('.badge');
            if (!badge) return;
            if (s.status === 'done') { badge.className = 'badge bg-success flex-shrink-0'; badge.textContent = 'تم'; }
            else if (s.status === 'error') { badge.className = 'badge bg-danger flex-shrink-0'; badge.textContent = 'فشل'; }
            else if (s.status === 'running') { badge.className = 'badge bg-primary flex-shrink-0'; badge.textContent = 'جاري...'; }
        });
    }

    function finishBatchTranscribeModal(okCount, errCount) {
        document.getElementById('batchTranscribeCurrent').textContent = 'انتهى الاستخراج.';
        document.getElementById('batchTranscribeProgressBar').style.width = '100%';
        document.getElementById('batchTranscribeProgressBar').textContent = '100%';
        document.getElementById('batchTranscribeProgressBar').classList.remove('progress-bar-animated');
        const sumEl = document.getElementById('batchTranscribeSummary');
        sumEl.textContent = 'تم بنجاح: ' + okCount + (errCount > 0 ? '، فشل: ' + errCount : '');
        sumEl.classList.remove('d-none');
        document.getElementById('batchTranscribeCloseBtn').style.display = 'inline-block';
        document.getElementById('batchTranscribeDismissBtn').style.display = 'inline-block';
    }

    function pollTranscribeStatus(assetId) {
        return new Promise(function(resolve, reject) {
            function poll() {
                fetch(assetsBaseUrl + '/' + assetId + '/transcribe-status', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.status === 'completed') {
                            fetch(assetsBaseUrl + '/' + assetId + '/transcribe-status?clear=1', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            }).catch(function() {});
                            return resolve(data);
                        }
                        if (data.status === 'error') {
                            return reject(new Error(data.message || data.error || 'فشل استخراج النص'));
                        }
                        setTimeout(poll, 2500);
                    })
                    .catch(reject);
            }
            poll();
        });
    }

    function runTranscribeForAsset(assetId, total, videoIndex, videoStatuses) {
        var setRunning = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'running'; };
        var setDone = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'done'; };
        var setError = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'error'; };
        setRunning();
        updateBatchTranscribeProgress(videoIndex, total, videoStatuses, 'بدء الاستخراج');
        return fetch(assetsBaseUrl + '/' + assetId + '/transcribe', {
            method: 'POST',
            headers: headersJson,
            body: JSON.stringify(batchTranscribePayload)
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) throw new Error(data.error);
                updateBatchTranscribeProgress(videoIndex, total, videoStatuses, 'جاري المعالجة...');
                return pollTranscribeStatus(assetId);
            })
            .then(function() { setDone(); })
            .catch(function() { setError(); });
    }

    function startBatchTranscribe() {
        var items = getSelectedItems();
        if (!items.length) {
            alert('يرجى تحديد فيديو واحد على الأقل.');
            return;
        }
        if (!confirm('سيتم استخراج المحتوى النصي (tiny · أول 5 دقائق) لـ ' + items.length + ' فيديو، واحداً تلو الآخر. العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
            return;
        }
        openBatchTranscribeModal(items);
        var videoStatuses = items.map(function(item) { return { id: item.id, status: 'pending' }; });
        var total = items.length;
        var chain = Promise.resolve();
        items.forEach(function(item, index) {
            chain = chain.then(function() {
                updateBatchTranscribeProgress(index, total, videoStatuses, null);
                return runTranscribeForAsset(item.id, total, index, videoStatuses);
            });
        });
        chain.then(function() {
            var okCount = videoStatuses.filter(function(s) { return s.status === 'done'; }).length;
            var errCount = videoStatuses.filter(function(s) { return s.status === 'error'; }).length;
            finishBatchTranscribeModal(okCount, errCount);
        });
    }

    document.getElementById('bulkTranscribeBtn')?.addEventListener('click', startBatchTranscribe);
    document.getElementById('browseBulkTranscribeBtn')?.addEventListener('click', startBatchTranscribe);

    document.getElementById('batchTranscribeDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchTranscribeModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchTranscribeProgressBar').classList.add('progress-bar-animated');
    });

    function openBatchExtractAudioModal(items) {
        const modal = document.getElementById('batchExtractAudioModal');
        if (!modal) return;
        document.getElementById('batchExtractAudioTotal').textContent = items.length;
        document.getElementById('batchExtractAudioCurrent').textContent = 'جاري التحضير...';
        document.getElementById('batchExtractAudioProgressBar').style.width = '0%';
        document.getElementById('batchExtractAudioProgressBar').textContent = '0%';
        const listEl = document.getElementById('batchExtractAudioVideoList');
        listEl.innerHTML = items.map(function(item) {
            return '<li class="list-group-item d-flex justify-content-between align-items-center gap-2" data-id="' + item.id + '"><span class="text-truncate">#' + item.id + ' — ' + escapeHtmlBatch(item.title) + '</span><span class="badge bg-secondary flex-shrink-0">في الانتظار</span></li>';
        }).join('');
        document.getElementById('batchExtractAudioCloseBtn').style.display = 'none';
        document.getElementById('batchExtractAudioDismissBtn').style.display = 'none';
        document.getElementById('batchExtractAudioSummary').classList.add('d-none');
        document.getElementById('batchExtractAudioProgressBar').classList.add('progress-bar-animated');
        const bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
        if (bsModal) bsModal.show();
    }

    function updateBatchExtractAudioProgress(videoIndex, total, videoStatuses, message) {
        const currentEl = document.getElementById('batchExtractAudioCurrent');
        const barEl = document.getElementById('batchExtractAudioProgressBar');
        if (currentEl) {
            currentEl.textContent = 'فيديو ' + (videoIndex + 1) + ' من ' + total + (message ? ' — ' + message : '');
        }
        const pct = total > 0 ? Math.round((videoIndex / total) * 100) : 0;
        if (barEl) { barEl.style.width = pct + '%'; barEl.textContent = pct + '%'; }
        videoStatuses.forEach(function(s) {
            const li = document.querySelector('#batchExtractAudioVideoList li[data-id="' + s.id + '"]');
            if (!li) return;
            const badge = li.querySelector('.badge');
            if (!badge) return;
            if (s.status === 'done') { badge.className = 'badge bg-success flex-shrink-0'; badge.textContent = 'تم'; }
            else if (s.status === 'error') { badge.className = 'badge bg-danger flex-shrink-0'; badge.textContent = 'فشل'; }
            else if (s.status === 'running') { badge.className = 'badge bg-primary flex-shrink-0'; badge.textContent = 'جاري...'; }
        });
    }

    function finishBatchExtractAudioModal(okCount, errCount) {
        document.getElementById('batchExtractAudioCurrent').textContent = 'انتهى التحويل.';
        document.getElementById('batchExtractAudioProgressBar').style.width = '100%';
        document.getElementById('batchExtractAudioProgressBar').textContent = '100%';
        document.getElementById('batchExtractAudioProgressBar').classList.remove('progress-bar-animated');
        const sumEl = document.getElementById('batchExtractAudioSummary');
        sumEl.textContent = 'تم بنجاح: ' + okCount + (errCount > 0 ? '، فشل: ' + errCount : '');
        sumEl.classList.remove('d-none');
        document.getElementById('batchExtractAudioCloseBtn').style.display = 'inline-block';
        document.getElementById('batchExtractAudioDismissBtn').style.display = 'inline-block';
    }

    function pollExtractAudioStatus(assetId) {
        return new Promise(function(resolve, reject) {
            function poll() {
                fetch(assetsBaseUrl + '/' + assetId + '/extract-audio-status', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.status === 'completed') {
                            fetch(assetsBaseUrl + '/' + assetId + '/extract-audio-status?clear=1', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            }).catch(function() {});
                            return resolve(data);
                        }
                        if (data.status === 'error' || data.error) {
                            return reject(new Error(data.message || data.error || 'فشل تحويل الفيديو إلى صوت'));
                        }
                        setTimeout(poll, 2500);
                    })
                    .catch(reject);
            }
            poll();
        });
    }

    function runExtractAudioForAsset(assetId, total, videoIndex, videoStatuses) {
        var setRunning = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'running'; };
        var setDone = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'done'; };
        var setError = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'error'; };
        setRunning();
        updateBatchExtractAudioProgress(videoIndex, total, videoStatuses, 'بدء التحويل');
        return fetch(assetsBaseUrl + '/' + assetId + '/extract-audio', {
            method: 'POST',
            headers: headersJson,
            body: '{}'
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) throw new Error(data.error);
                updateBatchExtractAudioProgress(videoIndex, total, videoStatuses, 'جاري التحويل...');
                return pollExtractAudioStatus(assetId);
            })
            .then(function() { setDone(); })
            .catch(function() { setError(); });
    }

    function startBatchExtractAudio() {
        var items = getSelectedItems();
        if (!items.length) {
            alert('يرجى تحديد فيديو واحد على الأقل.');
            return;
        }
        if (!confirm('سيتم تحويل ' + items.length + ' فيديو إلى ملف صوتي MP3، واحداً تلو الآخر. العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
            return;
        }
        openBatchExtractAudioModal(items);
        var videoStatuses = items.map(function(item) { return { id: item.id, status: 'pending' }; });
        var total = items.length;
        var chain = Promise.resolve();
        items.forEach(function(item, index) {
            chain = chain.then(function() {
                updateBatchExtractAudioProgress(index, total, videoStatuses, null);
                return runExtractAudioForAsset(item.id, total, index, videoStatuses);
            });
        });
        chain.then(function() {
            var okCount = videoStatuses.filter(function(s) { return s.status === 'done'; }).length;
            var errCount = videoStatuses.filter(function(s) { return s.status === 'error'; }).length;
            finishBatchExtractAudioModal(okCount, errCount);
        });
    }

    document.getElementById('bulkExtractAudioBtn')?.addEventListener('click', startBatchExtractAudio);
    document.getElementById('browseBulkExtractAudioBtn')?.addEventListener('click', startBatchExtractAudio);

    document.getElementById('batchExtractAudioDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchExtractAudioModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchExtractAudioProgressBar').classList.add('progress-bar-animated');
    });

    function openBatchAnalyzeModal(items) {
        const modal = document.getElementById('batchAnalyzeModal');
        if (!modal) return;
        document.getElementById('batchAnalyzeTotal').textContent = items.length;
        document.getElementById('batchAnalyzeCurrent').textContent = 'جاري التحضير...';
        document.getElementById('batchAnalyzeProgressBar').style.width = '0%';
        document.getElementById('batchAnalyzeProgressBar').textContent = '0%';
        const listEl = document.getElementById('batchAnalyzeVideoList');
        listEl.innerHTML = items.map(function(item) {
            return '<li class="list-group-item d-flex justify-content-between align-items-center gap-2" data-id="' + item.id + '"><span class="text-truncate">#' + item.id + ' — ' + escapeHtmlBatch(item.title) + '</span><span class="badge bg-secondary flex-shrink-0">في الانتظار</span></li>';
        }).join('');
        document.getElementById('batchAnalyzeCloseBtn').style.display = 'none';
        document.getElementById('batchAnalyzeDismissBtn').style.display = 'none';
        document.getElementById('batchAnalyzeSummary').classList.add('d-none');
        document.getElementById('batchAnalyzeProgressBar').classList.add('progress-bar-animated');
        const bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
        if (bsModal) bsModal.show();
    }

    function updateBatchAnalyzeProgress(videoIndex, total, videoStatuses, message) {
        const currentEl = document.getElementById('batchAnalyzeCurrent');
        const barEl = document.getElementById('batchAnalyzeProgressBar');
        if (currentEl) {
            currentEl.textContent = 'فيديو ' + (videoIndex + 1) + ' من ' + total + (message ? ' — ' + message : '');
        }
        const pct = total > 0 ? Math.round((videoIndex / total) * 100) : 0;
        if (barEl) { barEl.style.width = pct + '%'; barEl.textContent = pct + '%'; }
        videoStatuses.forEach(function(s) {
            const li = document.querySelector('#batchAnalyzeVideoList li[data-id="' + s.id + '"]');
            if (!li) return;
            const badge = li.querySelector('.badge');
            if (!badge) return;
            if (s.status === 'done') { badge.className = 'badge bg-success flex-shrink-0'; badge.textContent = 'تم'; }
            else if (s.status === 'error') { badge.className = 'badge bg-danger flex-shrink-0'; badge.textContent = 'فشل'; }
            else if (s.status === 'running') { badge.className = 'badge bg-primary flex-shrink-0'; badge.textContent = 'جاري...'; }
        });
    }

    function finishBatchAnalyzeModal(okCount, errCount) {
        document.getElementById('batchAnalyzeCurrent').textContent = 'انتهى التحليل.';
        document.getElementById('batchAnalyzeProgressBar').style.width = '100%';
        document.getElementById('batchAnalyzeProgressBar').textContent = '100%';
        document.getElementById('batchAnalyzeProgressBar').classList.remove('progress-bar-animated');
        const sumEl = document.getElementById('batchAnalyzeSummary');
        sumEl.textContent = 'تم بنجاح: ' + okCount + (errCount > 0 ? '، فشل: ' + errCount : '');
        sumEl.classList.remove('d-none');
        document.getElementById('batchAnalyzeCloseBtn').style.display = 'inline-block';
        document.getElementById('batchAnalyzeDismissBtn').style.display = 'inline-block';
    }

    function runAnalyzeForAsset(assetId, total, videoIndex, videoStatuses) {
        var setRunning = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'running'; };
        var setDone = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'done'; };
        var setError = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'error'; };
        setRunning();
        updateBatchAnalyzeProgress(videoIndex, total, videoStatuses, 'جاري التحليل...');
        return fetch(assetsBaseUrl + '/' + assetId + '/analyze', {
            method: 'POST',
            headers: headersJson,
            body: '{}'
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error || !data.success) throw new Error(data.error || 'فشل التحليل');
                setDone();
            })
            .catch(function() { setError(); });
    }

    function startBatchAnalyze() {
        var items = getSelectedItems();
        if (!items.length) {
            alert('يرجى تحديد فيديو واحد على الأقل.');
            return;
        }
        if (!confirm('سيتم إرسال المحتوى النصي إلى DeepSeek API لتحليل ' + items.length + ' فيديو، واحداً تلو الآخر. يجب أن يكون المحتوى النصي مستخرجاً مسبقاً. هل تريد المتابعة؟')) {
            return;
        }
        openBatchAnalyzeModal(items);
        var videoStatuses = items.map(function(item) { return { id: item.id, status: 'pending' }; });
        var total = items.length;
        var chain = Promise.resolve();
        items.forEach(function(item, index) {
            chain = chain.then(function() {
                updateBatchAnalyzeProgress(index, total, videoStatuses, null);
                return runAnalyzeForAsset(item.id, total, index, videoStatuses);
            });
        });
        chain.then(function() {
            var okCount = videoStatuses.filter(function(s) { return s.status === 'done'; }).length;
            var errCount = videoStatuses.filter(function(s) { return s.status === 'error'; }).length;
            finishBatchAnalyzeModal(okCount, errCount);
        });
    }

    document.getElementById('bulkAnalyzeBtn')?.addEventListener('click', startBatchAnalyze);
    document.getElementById('browseBulkAnalyzeBtn')?.addEventListener('click', startBatchAnalyze);

    document.getElementById('batchAnalyzeDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchAnalyzeModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchAnalyzeProgressBar').classList.add('progress-bar-animated');
    });

    function openBatchTranslateModal(ids) {
        const modal = document.getElementById('batchTranslateModal');
        if (!modal) return;
        document.getElementById('batchTranslateTotal').textContent = ids.length;
        document.getElementById('batchTranslateCurrent').textContent = 'جاري التحضير...';
        document.getElementById('batchTranslateProgressBar').style.width = '0%';
        document.getElementById('batchTranslateProgressBar').textContent = '0%';
        const listEl = document.getElementById('batchTranslateVideoList');
        listEl.innerHTML = ids.map(function(id) {
            return '<li class="list-group-item d-flex justify-content-between align-items-center" data-id="' + id + '"><span>فيديو ' + id + '</span><span class="badge bg-secondary">في الانتظار</span></li>';
        }).join('');
        document.getElementById('batchTranslateCloseBtn').style.display = 'none';
        document.getElementById('batchTranslateDismissBtn').style.display = 'none';
        document.getElementById('batchTranslateSummary').classList.add('d-none');
        document.getElementById('batchTranslateProgressBar').classList.add('progress-bar-animated');
        const bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
        if (bsModal) bsModal.show();
    }

    function updateBatchTranslateProgress(videoIndex, total, langName, videoStatuses, currentStep, totalSteps) {
        const currentEl = document.getElementById('batchTranslateCurrent');
        const barEl = document.getElementById('batchTranslateProgressBar');
        if (currentEl) currentEl.textContent = 'فيديو ' + (videoIndex + 1) + ' من ' + total + (langName ? ' — جاري الترجمة إلى ' + langName : '');
        const pct = (totalSteps > 0 && currentStep >= 0) ? Math.round((currentStep / totalSteps) * 100) : (total > 0 ? Math.round((videoIndex / total) * 100) : 0);
        if (barEl) { barEl.style.width = Math.min(100, pct) + '%'; barEl.textContent = Math.min(100, pct) + '%'; }
        videoStatuses.forEach(function(s) {
            const li = document.querySelector('#batchTranslateVideoList li[data-id="' + s.id + '"]');
            if (!li) return;
            const badge = li.querySelector('.badge');
            if (!badge) return;
            if (s.status === 'done') { badge.className = 'badge bg-success'; badge.textContent = 'تم'; }
            else if (s.status === 'error') { badge.className = 'badge bg-danger'; badge.textContent = 'فشل'; }
            else if (s.status === 'running') { badge.className = 'badge bg-primary'; badge.textContent = 'جاري...'; }
        });
    }

    function finishBatchTranslateModal(okCount, errCount) {
        document.getElementById('batchTranslateCurrent').textContent = 'انتهت الترجمة.';
        document.getElementById('batchTranslateProgressBar').style.width = '100%';
        document.getElementById('batchTranslateProgressBar').textContent = '100%';
        document.getElementById('batchTranslateProgressBar').classList.remove('progress-bar-animated');
        const sumEl = document.getElementById('batchTranslateSummary');
        sumEl.textContent = 'تم بنجاح: ' + okCount + (errCount > 0 ? '، فشل: ' + errCount : '');
        sumEl.classList.remove('d-none');
        document.getElementById('batchTranslateCloseBtn').style.display = 'inline-block';
        document.getElementById('batchTranslateDismissBtn').style.display = 'inline-block';
    }

    function runTranslateForAsset(assetId, total, videoIndex, videoStatuses, updateUi, totalSteps) {
        var setRunning = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'running'; };
        var setDone = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'done'; };
        var setError = function() { var s = videoStatuses.find(function(x) { return x.id === assetId; }); if (s) s.status = 'error'; };
        setRunning();
        var baseStep = videoIndex * (translationLangList ? translationLangList.length : 1);
        updateUi(videoIndex, total, null, videoStatuses, baseStep, totalSteps);
        var promise = Promise.resolve();
        var langList = translationLangList && translationLangList.length ? translationLangList : [{ code: 'en', name: 'English' }];
        for (var i = 0; i < langList.length; i++) {
            (function(langCode, langName, stepIndex) {
                promise = promise.then(function() {
                    updateUi(videoIndex, total, langName, videoStatuses, baseStep + stepIndex, totalSteps);
                    var fd = new FormData();
                    fd.append('_token', token);
                    fd.append('lang', langCode);
                    return fetch(translateBaseUrl + '/' + assetId + '/translate-transcription', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        if (data && data.success) return;
                        throw new Error(data && data.error ? data.error : 'فشل الترجمة');
                    });
                });
            })(langList[i].code, langList[i].name, i);
        }
        return promise.then(function() { setDone(); }).catch(function() { setError(); });
    }

    function startBatchTranslate() {
        var ids = getSelectedIds();
        if (!ids.length) {
            alert('يرجى تحديد فيديو واحد على الأقل.');
            return;
        }
        if (!translationLangList || !translationLangList.length) {
            alert('لا توجد لغات ترجمة متاحة.');
            return;
        }
        if (!confirm('سيتم ترجمة المحتوى النصي إلى كل اللغات المتاحة (' + translationLangList.length + ' لغة) لـ ' + ids.length + ' فيديو. العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
            return;
        }
        openBatchTranslateModal(ids);
        var videoStatuses = ids.map(function(id) { return { id: id, status: 'pending' }; });
        var total = ids.length;
        var numLangs = translationLangList ? translationLangList.length : 1;
        var totalSteps = total * numLangs;
        var updateUi = function(videoIndex, tot, langName, statuses, currentStep, stepsTotal) {
            updateBatchTranslateProgress(videoIndex, tot, langName, statuses, currentStep, stepsTotal);
        };
        var chain = Promise.resolve();
        ids.forEach(function(id, index) {
            chain = chain.then(function() {
                return runTranslateForAsset(id, total, index, videoStatuses, updateUi, totalSteps);
            });
        });
        chain.then(function() {
            var okCount = videoStatuses.filter(function(s) { return s.status === 'done'; }).length;
            var errCount = videoStatuses.filter(function(s) { return s.status === 'error'; }).length;
            finishBatchTranslateModal(okCount, errCount);
        });
    }

    document.getElementById('bulkTranslateBtn')?.addEventListener('click', startBatchTranslate);

    document.getElementById('batchTranslateDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchTranslateModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchTranslateProgressBar').classList.add('progress-bar-animated');
    });
})();
    // عرض المسار في نافذة منبثقة
    function showAssetPath(assetId, originalPath, relativePath) {
        document.getElementById('modalOriginalPath').value = originalPath || '-';
        document.getElementById('modalRelativePath').value = relativePath || '-';
        const modal = new bootstrap.Modal(document.getElementById('showPathModal'));
        modal.show();
    }

    // نسخ المسار
    function copyPath(inputId) {
        const input = document.getElementById(inputId);
        if (input && input.value && input.value !== '-') {
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(function() {
                const btn = input.nextElementSibling;
                if (btn) {
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check"></i>';
                    btn.classList.add('btn-success');
                    btn.classList.remove('btn-outline-secondary');
                    setTimeout(function() {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                    }, 1500);
                }
            }).catch(function() {
                alert('فشل نسخ المسار');
            });
        }
    }

    // إضافة فيديو: رفع مع تقدم + تصفح + تسجيل ونقل
    (function() {
        const modalEl = document.getElementById('importVideoModal');
        if (!modalEl) return;

        const browseUrl = @json(route('assets.import.browse'));
        const uploadUrl = @json(route('assets.import.upload'));
        const importUrl = @json(route('assets.import'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const breadcrumbEl = document.getElementById('importVideoBreadcrumb');
        const loadingEl = document.getElementById('importVideoLoading');
        const errorEl = document.getElementById('importVideoError');
        const contentEl = document.getElementById('importVideoBrowseContent');
        const foldersEl = document.getElementById('importVideoFolders');
        const filesEl = document.getElementById('importVideoFiles');
        const emptyEl = document.getElementById('importVideoEmpty');
        const resultEl = document.getElementById('importVideoResult');
        const submitBtn = document.getElementById('importVideoSubmitBtn');
        const openBtn = document.getElementById('openImportVideoModalBtn');
        const progressPanel = document.getElementById('importVideoProgress');
        const progressLabel = document.getElementById('importVideoProgressLabel');
        const progressPercent = document.getElementById('importVideoProgressPercent');
        const progressBar = document.getElementById('importVideoProgressBar');
        const progressDetail = document.getElementById('importVideoProgressDetail');
        const uploadTargetEl = document.getElementById('importVideoUploadTarget');
        const fileInput = document.getElementById('importVideoFileInput');
        const uploadBtn = document.getElementById('importVideoUploadBtn');
        const uploadImportBtn = document.getElementById('importVideoUploadImportBtn');
        const cancelBtn = document.getElementById('importVideoCancelBtn');
        const closeBtn = document.getElementById('importVideoModalCloseBtn');
        const uploadCard = document.getElementById('importVideoUploadCard');
        const selectBar = document.getElementById('importVideoSelectBar');
        const selectedCountEl = document.getElementById('importVideoSelectedCount');
        const selectAllBtn = document.getElementById('importVideoSelectAllBtn');
        const clearSelectionBtn = document.getElementById('importVideoClearSelectionBtn');
        const submitBtnLabel = document.getElementById('importVideoSubmitBtnLabel');

        const LAST_BROWSE_PATH_KEY = 'almonajah_import_video_last_browse_path';

        let currentPath = '';
        let selectedPaths = [];
        let busy = false;
        let modalInstance = null;

        function saveLastBrowsePath(path) {
            if (path === null || path === undefined) return;
            try {
                localStorage.setItem(LAST_BROWSE_PATH_KEY, String(path));
            } catch (e) { /* private mode / quota */ }
        }

        function getLastBrowsePath() {
            try {
                return localStorage.getItem(LAST_BROWSE_PATH_KEY) || '';
            } catch (e) {
                return '';
            }
        }

        function getInitialBrowsePath() {
            const saved = getLastBrowsePath();
            if (saved) return saved;
            return openBtn?.getAttribute('data-initial-path') || '';
        }

        function formatBytes(bytes) {
            if (!bytes || bytes < 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            let n = bytes;
            while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
            return (i === 0 ? n : n.toFixed(1)) + ' ' + units[i];
        }

        function canUploadToPath(path) {
            return path && path !== '2025' && path !== 'videos';
        }

        function updateUploadTarget() {
            if (!uploadTargetEl) return;
            if (canUploadToPath(currentPath)) {
                uploadTargetEl.innerHTML = 'مجلد الحفظ: <code dir="ltr">' + currentPath + '</code>';
            } else {
                uploadTargetEl.innerHTML = 'مجلد الحفظ: <span class="text-warning">اختر مجلداً فرعياً من الأسفل (داخل videos أو 2025)</span>';
            }
            const ok = canUploadToPath(currentPath) && fileInput?.files?.length;
            if (uploadBtn) uploadBtn.disabled = !canUploadToPath(currentPath) || busy || !fileInput?.files?.length;
            if (uploadImportBtn) uploadImportBtn.disabled = !canUploadToPath(currentPath) || busy || !fileInput?.files?.length;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function getSelectedPaths() {
            return Array.from(filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:checked:not(:disabled)'))
                .map(function(cb) { return cb.value; });
        }

        function updateSelectionUi() {
            selectedPaths = getSelectedPaths();
            const n = selectedPaths.length;
            if (selectedCountEl) selectedCountEl.textContent = n + ' محدد';
            if (submitBtnLabel) {
                submitBtnLabel.textContent = n > 1
                    ? ('تسجيل ونقل المحدد (' + n + ')')
                    : 'تسجيل ونقل المحدد';
            }
            if (submitBtn) submitBtn.disabled = busy || n === 0;
            if (selectBar) {
                const selectable = filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:not(:disabled)');
                selectBar.classList.toggle('d-none', selectable.length === 0);
            }
        }

        function setBusy(on) {
            busy = on;
            updateSelectionUi();
            if (uploadBtn) uploadBtn.disabled = on || !canUploadToPath(currentPath) || !fileInput?.files?.length;
            if (uploadImportBtn) uploadImportBtn.disabled = on || !canUploadToPath(currentPath) || !fileInput?.files?.length;
            if (cancelBtn) cancelBtn.disabled = on;
            if (closeBtn) closeBtn.disabled = on;
            fileInput.disabled = on;
            if (modalInstance) {
                if (on) {
                    modalInstance._config.backdrop = 'static';
                    modalInstance._config.keyboard = false;
                } else {
                    modalInstance._config.backdrop = true;
                    modalInstance._config.keyboard = true;
                }
            }
        }

        function showProgress(label, percent, detail) {
            progressPanel.classList.remove('d-none');
            progressLabel.textContent = label;
            if (percent === null || percent === undefined) {
                progressPercent.textContent = '...';
                progressBar.style.width = '100%';
                progressBar.textContent = '';
                progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
            } else {
                const p = Math.max(0, Math.min(100, Math.round(percent)));
                progressPercent.textContent = p + '%';
                progressBar.style.width = p + '%';
                progressBar.textContent = p >= 8 ? p + '%' : '';
                progressBar.classList.toggle('progress-bar-animated', p < 100);
                progressBar.classList.toggle('progress-bar-striped', p < 100);
            }
            progressDetail.textContent = detail || '';
            if (uploadCard) uploadCard.classList.add('opacity-50');
            contentEl.classList.add('d-none');
        }

        function hideProgress() {
            progressPanel.classList.add('d-none');
            if (uploadCard) uploadCard.classList.remove('opacity-50');
        }

        function setLoading(on) {
            loadingEl.classList.toggle('d-none', !on);
            if (on) {
                contentEl.classList.add('d-none');
                errorEl.classList.add('d-none');
            }
        }

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('d-none');
            contentEl.classList.add('d-none');
        }

        function formatSize(mb) {
            if (mb >= 1024) return (mb / 1024).toFixed(2) + ' GB';
            return mb + ' MB';
        }

        function renderBreadcrumb(segments) {
            breadcrumbEl.innerHTML = '';
            const homeLi = document.createElement('li');
            homeLi.className = 'breadcrumb-item';
            const homeA = document.createElement('a');
            homeA.href = '#';
            homeA.textContent = 'الرئيسية';
            homeA.addEventListener('click', function(e) {
                e.preventDefault();
                loadBrowse('');
            });
            homeLi.appendChild(homeA);
            breadcrumbEl.appendChild(homeLi);

            segments.forEach(function(seg, i) {
                const li = document.createElement('li');
                const isLast = i === segments.length - 1;
                li.className = 'breadcrumb-item' + (isLast ? ' active' : '');
                if (isLast) {
                    li.textContent = seg;
                } else {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.textContent = seg;
                    const path = segments.slice(0, i + 1).join('/');
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        loadBrowse(path);
                    });
                    li.appendChild(a);
                }
                breadcrumbEl.appendChild(li);
            });
        }

        function renderBrowse(data) {
            currentPath = data.path_prefix || '';
            saveLastBrowsePath(currentPath);
            selectedPaths = [];
            renderBreadcrumb(data.breadcrumb_segments || []);

            foldersEl.innerHTML = '';
            (data.folders || []).forEach(function(folder) {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'btn btn-outline-primary w-100 text-start py-3';
                card.innerHTML = '<i class="bi bi-folder-fill me-2"></i>' + folder;
                card.addEventListener('click', function() {
                    const next = currentPath ? currentPath + '/' + folder : folder;
                    loadBrowse(next);
                });
                col.appendChild(card);
                foldersEl.appendChild(col);
            });

            filesEl.innerHTML = '';
            (data.files || []).forEach(function(file) {
                const item = document.createElement('label');
                item.className = 'list-group-item list-group-item-action d-flex align-items-start gap-2' + (file.already_in_site ? ' disabled opacity-75' : '');
                const disabled = file.already_in_site;
                const badge = file.already_in_site
                    ? '<span class="badge bg-success mt-1">منقول للموقع مسبقاً</span>'
                    : (file.in_database ? '<span class="badge bg-info text-dark mt-1">مسجل — بانتظار النقل</span>' : '<span class="badge bg-secondary mt-1">جديد</span>');
                item.innerHTML =
                    '<input type="checkbox" name="import_video_file" class="form-check-input mt-1 flex-shrink-0" value="' + escapeHtml(file.relative_path) + '"' + (disabled ? ' disabled' : '') + '>' +
                    '<div class="flex-grow-1 min-width-0">' +
                    '<div class="fw-semibold text-truncate">' + escapeHtml(file.file_name) + '</div>' +
                    '<small class="text-muted d-block" dir="ltr">' + escapeHtml(file.relative_path) + ' · ' + formatSize(file.size_mb) + '</small>' +
                    badge +
                    '</div>';
                if (!disabled) {
                    const checkbox = item.querySelector('input');
                    checkbox.addEventListener('change', updateSelectionUi);
                }
                filesEl.appendChild(item);
            });

            const hasFolders = (data.folders || []).length > 0;
            const hasFiles = (data.files || []).length > 0;
            emptyEl.classList.toggle('d-none', hasFolders || hasFiles);
            contentEl.classList.remove('d-none');
            updateUploadTarget();
            updateSelectionUi();
        }

        function uploadVideoFile(file) {
            return new Promise(function(resolve, reject) {
                const fd = new FormData();
                fd.append('video', file);
                fd.append('folder_path', currentPath);
                fd.append('_token', csrfToken);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const pct = (e.loaded / e.total) * 100;
                        showProgress(
                            'جاري رفع الملف...',
                            pct,
                            formatBytes(e.loaded) + ' من ' + formatBytes(e.total) + ' · ' + file.name
                        );
                    } else {
                        showProgress('جاري رفع الملف...', null, file.name + ' — جاري الإرسال...');
                    }
                });

                xhr.onload = function() {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (err) {}
                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        resolve(data);
                    } else {
                        reject(new Error(data.error || data.message || 'فشل رفع الملف'));
                    }
                };
                xhr.onerror = function() { reject(new Error('انقطع الاتصال أثناء الرفع')); };
                xhr.send(fd);
            });
        }

        function runImport(sourcePath, options) {
            const opts = options || {};
            if (!opts.silentProgress) {
                showProgress('جاري التسجيل ونقل الفيديو...', null, 'قد تستغرق العملية دقائق للملفات الكبيرة — لا تغلق النافذة');
            }

            return fetch(importUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ source_path: sourcePath })
            }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); });
        }

        function runImportBatchSequential(paths) {
            const total = paths.length;
            const results = [];
            let imported = 0;
            let skipped = 0;
            let failed = 0;
            let chain = Promise.resolve();

            paths.forEach(function(sourcePath, index) {
                chain = chain.then(function() {
                    const pct = Math.round(((index) / total) * 100);
                    const fileName = sourcePath.split('/').pop();
                    showProgress(
                        'جاري التسجيل والنقل...',
                        pct,
                        (index + 1) + ' / ' + total + ' — ' + fileName
                    );
                    return runImport(sourcePath, { silentProgress: true }).then(function(res) {
                        const d = res.data || {};
                        const row = {
                            source_path: sourcePath,
                            success: !!d.success,
                            message: d.message || d.error || '',
                            asset_id: d.asset_id || null,
                            asset_url: d.asset_url || null,
                            already_imported: !!d.already_imported,
                            error: d.error || null
                        };
                        results.push(row);
                        if (row.success) {
                            if (row.already_imported) skipped++;
                            else imported++;
                        } else {
                            failed++;
                        }
                    }).catch(function() {
                        results.push({
                            source_path: sourcePath,
                            success: false,
                            message: '',
                            error: 'خطأ في الاتصال'
                        });
                        failed++;
                    });
                });
            });

            return chain.then(function() {
                const message = 'اكتملت المعالجة: نجح ' + imported
                    + (skipped > 0 ? ' · موجود مسبقاً ' + skipped : '')
                    + (failed > 0 ? ' · فشل ' + failed : '')
                    + ' من ' + total;
                return {
                    success: failed === 0,
                    message: message,
                    imported: imported,
                    skipped: skipped,
                    failed: failed,
                    total: total,
                    results: results
                };
            });
        }

        function showResultSuccess(data) {
            setBusy(false);
            showProgress('تم بنجاح', 100, data.message || 'اكتملت العملية');
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            contentEl.classList.remove('d-none');
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert alert-success mb-0 mt-3';
            const link = data.asset_url ? ' <a href="' + data.asset_url + '" class="alert-link" target="_blank" rel="noopener">فتح صفحة الفيديو</a>' : '';
            resultEl.innerHTML = (data.message || 'تم بنجاح') + link;
            if (!data.already_imported) {
                setTimeout(function() { window.location.reload(); }, 1800);
            }
        }

        function showBatchResultSuccess(data) {
            setBusy(false);
            const imported = data.imported || 0;
            const failed = data.failed || 0;
            const skipped = data.skipped || 0;
            showProgress('اكتملت الدفعة', 100, data.message || '');
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            contentEl.classList.remove('d-none');
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert ' + (failed > 0 ? 'alert-warning' : 'alert-success') + ' mb-0 mt-3';
            let html = '<strong>' + escapeHtml(data.message || 'اكتملت المعالجة') + '</strong>';
            if (Array.isArray(data.results) && data.results.length) {
                html += '<ul class="mb-0 mt-2 small" style="max-height: 200px; overflow-y: auto;">';
                data.results.forEach(function(row) {
                    const name = row.source_path ? row.source_path.split('/').pop() : '';
                    const icon = row.success ? (row.already_imported ? '○' : '✓') : '✗';
                    const cls = row.success ? (row.already_imported ? 'text-muted' : 'text-success') : 'text-danger';
                    html += '<li class="' + cls + '">' + icon + ' ' + escapeHtml(name);
                    if (row.error) html += ' — ' + escapeHtml(row.error);
                    else if (row.message && !row.success) html += ' — ' + escapeHtml(row.message);
                    if (row.asset_url && row.success && !row.already_imported) {
                        html += ' <a href="' + escapeHtml(row.asset_url) + '" target="_blank" rel="noopener">فتح</a>';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            }
            resultEl.innerHTML = html;
            if (imported > 0 || skipped > 0) {
                setTimeout(function() { window.location.reload(); }, failed > 0 ? 3500 : 2000);
            } else {
                loadBrowse(currentPath);
            }
        }

        function showResultError(msg) {
            hideProgress();
            setBusy(false);
            resultEl.classList.remove('d-none');
            resultEl.className = 'alert alert-danger mb-0 mt-3';
            resultEl.textContent = msg;
        }

        function loadBrowse(path, allowFallbackToRoot) {
            setLoading(true);
            errorEl.classList.add('d-none');
            resultEl.classList.add('d-none');

            const url = browseUrl + (path ? '?path=' + encodeURIComponent(path) : '');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    setLoading(false);
                    if (!data.success) {
                        if (allowFallbackToRoot && path) {
                            saveLastBrowsePath('');
                            loadBrowse('', false);
                            return;
                        }
                        showError(data.error || 'تعذر تحميل المجلد');
                        return;
                    }
                    renderBrowse(data);
                })
                .catch(function() {
                    setLoading(false);
                    if (allowFallbackToRoot && path) {
                        saveLastBrowsePath('');
                        loadBrowse('', false);
                        return;
                    }
                    showError('تعذر الاتصال بالخادم');
                });
        }

        modalEl.addEventListener('show.bs.modal', function() {
            modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            resultEl.classList.add('d-none');
            resultEl.innerHTML = '';
            hideProgress();
            setBusy(false);
            if (fileInput) fileInput.value = '';
            loadBrowse(getInitialBrowsePath(), true);
        });

        if (fileInput) {
            fileInput.addEventListener('change', updateUploadTarget);
        }

        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                const file = fileInput?.files?.[0];
                if (!file || !canUploadToPath(currentPath)) return;
                setBusy(true);
                resultEl.classList.add('d-none');
                uploadVideoFile(file)
                    .then(function(data) {
                        showProgress('اكتمل الرفع', 100, data.file_name);
                        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                        setBusy(false);
                        fileInput.value = '';
                        loadBrowse(currentPath);
                    })
                    .catch(function(err) {
                        showResultError(err.message || 'فشل الرفع');
                    });
            });
        }

        if (uploadImportBtn) {
            uploadImportBtn.addEventListener('click', function() {
                const file = fileInput?.files?.[0];
                if (!file || !canUploadToPath(currentPath)) return;
                setBusy(true);
                resultEl.classList.add('d-none');
                uploadVideoFile(file)
                    .then(function(data) {
                        showProgress('اكتمل الرفع — جاري التسجيل...', 100, data.file_name);
                        return runImport(data.relative_path);
                    })
                    .then(function(res) {
                        if (res.data.success) {
                            showResultSuccess(res.data);
                        } else {
                            showResultError(res.data.error || 'فشل التسجيل والنقل');
                        }
                    })
                    .catch(function(err) {
                        showResultError(err.message || 'حدث خطأ');
                    });
            });
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]:not(:disabled)').forEach(function(cb) {
                    cb.checked = true;
                });
                updateSelectionUi();
            });
        }

        if (clearSelectionBtn) {
            clearSelectionBtn.addEventListener('click', function() {
                filesEl.querySelectorAll('input[type="checkbox"][name="import_video_file"]').forEach(function(cb) {
                    cb.checked = false;
                });
                updateSelectionUi();
            });
        }

        submitBtn.addEventListener('click', function() {
            const paths = getSelectedPaths();
            if (!paths.length || busy) return;

            if (!confirm('تسجيل ونقل ' + paths.length + ' فيديو إلى الموقع؟\nقد تستغرق العملية وقتاً طويلاً.')) {
                return;
            }

            setBusy(true);
            resultEl.classList.add('d-none');

            if (paths.length === 1) {
                runImport(paths[0])
                    .then(function(res) {
                        if (res.data.success) {
                            showResultSuccess(res.data);
                        } else {
                            showResultError(res.data.error || 'فشل الاستيراد');
                        }
                    })
                    .catch(function() {
                        showResultError('حدث خطأ أثناء التسجيل والنقل');
                    });
                return;
            }

            runImportBatchSequential(paths)
                .then(function(summary) {
                    showBatchResultSuccess(summary);
                })
                .catch(function() {
                    showResultError('حدث خطأ أثناء التسجيل والنقل');
                });
        });
    })();
</script>
@endpush
@endsection

