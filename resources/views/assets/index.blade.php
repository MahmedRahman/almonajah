@extends('layouts.app')

@section('title', 'إدارة الفيديوهات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h2 class="fw-bold mb-0">إدارة الفيديوهات</h2>
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('assets.index') }}" class="btn {{ !($browse_mode ?? false) ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-list-ul me-2 ms-2"></i>عرض القائمة
            </a>
            <a href="{{ route('assets.index', ['view' => 'browse']) }}" class="btn {{ $browse_mode ?? false ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="bi bi-folder2-open me-2 ms-2"></i>تصفح بالمجلدات
            </a>
        </div>
    </div>
    <div>
        <form action="{{ route('assets.scan') }}" method="POST" class="d-inline me-2" onsubmit="return confirm('هل تريد Scan المجلدين storage/app/public/2025 و storage/app/public/videos وإضافة الفيديوهات الجديدة؟')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-search me-2 ms-2"></i>Scan
            </button>
        </form>
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

<!-- Modal نشر سريع للمحدد -->
<div class="modal fade" id="batchQuickPublishModal" tabindex="-1" aria-labelledby="batchQuickPublishModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchQuickPublishModalLabel">
                    <i class="bi bi-lightning-charge me-2"></i>نشر سريع للمحدد (<span id="batchQpTotal">0</span> فيديو)
                </h5>
                <button type="button" class="btn-close" id="batchQpCloseBtn" style="display: none;" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p id="batchQpCurrent" class="mb-2 text-muted">جاري التحضير...</p>
                <div class="progress mb-3" style="height: 22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="batchQpProgressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <ul class="list-group list-group-flush small" id="batchQpVideoList" style="max-height: 200px; overflow-y: auto;"></ul>
            </div>
            <div class="modal-footer" id="batchQpFooter">
                <span id="batchQpSummary" class="me-auto text-muted small d-none"></span>
                <button type="button" class="btn btn-secondary" id="batchQpDismissBtn" style="display: none;" data-bs-dismiss="modal">إغلاق وتحديث الصفحة</button>
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
    <div class="modal-dialog modal-dialog-centered">
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
            @if(!empty($path_prefix))
            <form action="{{ route('assets.scan') }}" method="POST" class="d-inline me-2" id="scanOpenFolderForm">
                @csrf
                <input type="hidden" name="scan_path" value="{{ $path_prefix }}">
                <input type="hidden" name="view" value="browse">
                <input type="hidden" name="path" value="{{ $path_prefix }}">
                <button type="submit" class="btn btn-success btn-sm" title="Scan المجلد المفتوح وإضافة الفيديوهات الجديدة">
                    <i class="bi bi-search me-2 ms-2 ms-2"></i>Scan المجلد المفتوح
                </button>
            </form>
            <a href="{{ route('assets.index', ['folder' => $path_prefix]) }}" class="btn btn-outline-primary btn-sm" title="عرض نفس محتويات المجلد الحالي على شكل قائمة">
                <i class="bi bi-list-ul me-2 ms-2 ms-2"></i>عرض القائمة (لهذا المجلد)
            </a>
            <script>
            document.getElementById('scanOpenFolderForm')?.addEventListener('submit', function(e) {
                if (!confirm('هل تريد Scan المجلد الحالي ({{ $path_prefix }}) وإضافة الفيديوهات الجديدة إلى قاعدة البيانات؟')) e.preventDefault();
            });
            </script>
            @endif
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
                <button type="button" class="btn btn-sm btn-primary" id="browseQuickPublishBtn">
                    <i class="bi bi-lightning-charge me-1"></i>نشر سريع للمحدد
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
                <div class="col-md-4 col-lg-3 position-relative">
                    @if(!$isMissing)
                    <input type="checkbox" class="form-check-input position-absolute asset-browse-cb" name="browse_ids[]" value="{{ $asset->id }}" data-id="{{ $asset->id }}" title="تحديد" style="top: 0.75rem; right: 0.75rem; z-index: 5;">
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('assets.index') }}" class="row g-3">
            @if(!empty($folder_filter))
            <input type="hidden" name="folder" value="{{ $folder_filter }}">
            @endif
            @if(request('path_issues') == '1')
            <input type="hidden" name="path_issues" value="1">
            @endif
            @php
                $currentSortBy = $sort_by ?? request('sort_by', 'id');
                $currentSortDir = $sort_dir ?? request('sort_dir', 'desc');
                
                // دالة مساعدة لإنشاء رابط الترتيب
                function getSortUrl($column, $currentSortBy, $currentSortDir) {
                    $query = request()->except(['sort_by', 'sort_dir', 'page']);
                    if ($currentSortBy === $column) {
                        // إذا كان نفس العمود، تبديل الاتجاه
                        $query['sort_by'] = $column;
                        $query['sort_dir'] = $currentSortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        // إذا كان عمود مختلف، الترتيب تصاعدي أولاً
                        $query['sort_by'] = $column;
                        $query['sort_dir'] = 'asc';
                    }
                    return route('assets.index', $query);
                }
            @endphp
            {{-- السطر الأول: البحث فقط --}}
            <div class="col-12">
                <label for="search" class="form-label">البحث</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="{{ request('search') }}" placeholder="العنوان أو اسم المتحدث أو الملف...">
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
                    @endphp
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
        @if(request()->hasAny(['search', 'content_categories', 'content_category', 'scholar_ids', 'scholar_id', 'gregorian_year', 'orientation', 'playlist', 'path_issues']) || !empty($folder_filter))
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

<!-- Assets Table -->
<div class="card">
    <div class="card-body">
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
                <button type="button" class="btn btn-sm btn-primary" id="bulkQuickPublishBtn">
                    <i class="bi bi-lightning-charge me-1"></i>نشر سريع للمحدد
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark" id="bulkSettingsBtn" title="تغيير اسم المتحدث وتصنيفات المحتوى للمحدد">
                    <i class="bi bi-gear me-1"></i>تغيير إعدادات عامة
                </button>
                <button type="button" class="btn btn-sm btn-outline-info d-none" id="bulkMergeBtn" title="دمج الفيديو: اختر سجلاً للإبقاء عليه وحذف الباقي">
                    <i class="bi bi-merge"></i> دمج الفيديو
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
                            <th style="width: 2.5rem;">
                                <input type="checkbox" class="form-check-input" id="selectAllAssets" title="تحديد الكل">
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
                        <tr class="{{ ($asset->file_missing ?? false) ? 'table-danger' : '' }}" data-asset-id="{{ $asset->id }}" data-asset-title="{{ e($asset->title ?? $asset->file_name ?? '') }}">
                            <td>
                                <input type="checkbox" class="form-check-input asset-row-cb" name="ids[]" value="{{ $asset->id }}" data-id="{{ $asset->id }}" title="تحديد">
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
            const applyGregorianCb = document.getElementById('apply_gregorian_year');
            if (!(applySpeakerCb && applySpeakerCb.checked) && !(applyCategoriesCb && applyCategoriesCb.checked) && !(applyGregorianCb && applyGregorianCb.checked)) {
                e.preventDefault();
                alert('فعّل تطبيق اسم المتحدث و/أو تصنيفات المحتوى و/أو السنة الميلادية.');
                return;
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
    const scholarCards = document.querySelectorAll('.filter-scholar-card');
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

// نشر سريع للمحدد (عدة فيديوهات) + ترجمة الفيديوات المحددة
(function() {
    const baseUrl = '{{ url("/assets") }}'.replace(/\/$/, '');
    const translateBaseUrl = '{{ url("/video") }}'.replace(/\/$/, '');
    const translationLangList = @json(isset($translationLanguages) ? collect($translationLanguages)->map(fn($name, $code) => ['code' => $code, 'name' => $name])->values()->all() : []);
    const csrfEl = document.querySelector('meta[name="csrf-token"]');
    const token = csrfEl ? csrfEl.getAttribute('content') : '';
    const headers = { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const headersJson = { ...headers, 'Content-Type': 'application/json' };
    const stepNames = ['نقل المحتوى', 'استخراج البيانات من المسار', 'استخراج المحتوى النصي', 'تحليل المحتوى النصي', 'تقليل حجم الفيديو', 'استخراج ملف صوتي', 'تفعيل النشر'];

    function getSelectedIds() {
        const listCb = document.querySelectorAll('.asset-row-cb:checked');
        const browseCb = document.querySelectorAll('.asset-browse-cb:checked');
        const nodes = listCb.length ? listCb : browseCb;
        return Array.from(nodes).map(function(cb) { return cb.value; });
    }

    function openBatchModal(ids) {
        const modal = document.getElementById('batchQuickPublishModal');
        if (!modal) return;
        document.getElementById('batchQpTotal').textContent = ids.length;
        document.getElementById('batchQpCurrent').textContent = 'جاري التحضير...';
        document.getElementById('batchQpProgressBar').style.width = '0%';
        document.getElementById('batchQpProgressBar').textContent = '0%';
        const listEl = document.getElementById('batchQpVideoList');
        listEl.innerHTML = ids.map(function(id) {
            return '<li class="list-group-item d-flex justify-content-between align-items-center" data-id="' + id + '"><span>فيديو ' + id + '</span><span class="badge bg-secondary">في الانتظار</span></li>';
        }).join('');
        document.getElementById('batchQpCloseBtn').style.display = 'none';
        document.getElementById('batchQpDismissBtn').style.display = 'none';
        document.getElementById('batchQpSummary').classList.add('d-none');
        const bsModal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modal) : null;
        if (bsModal) bsModal.show();
    }

    function updateBatchProgress(videoIndex, total, stepIndex, videoStatuses) {
        const currentEl = document.getElementById('batchQpCurrent');
        const barEl = document.getElementById('batchQpProgressBar');
        if (currentEl) currentEl.textContent = 'فيديو ' + (videoIndex + 1) + ' من ' + total + ' — ' + (stepNames[stepIndex] || '');
        const pct = total > 0 ? Math.round((videoIndex / total) * 100) : 0;
        if (barEl) { barEl.style.width = pct + '%'; barEl.textContent = pct + '%'; }
        videoStatuses.forEach(function(s) {
            const li = document.querySelector('#batchQpVideoList li[data-id="' + s.id + '"]');
            if (!li) return;
            const badge = li.querySelector('.badge');
            if (!badge) return;
            if (s.status === 'done') { badge.className = 'badge bg-success'; badge.textContent = 'تم'; }
            else if (s.status === 'error') { badge.className = 'badge bg-danger'; badge.textContent = 'فشل'; }
            else if (s.status === 'running') { badge.className = 'badge bg-primary'; badge.textContent = 'جاري...'; }
        });
    }

    function finishBatchModal(okCount, errCount) {
        document.getElementById('batchQpCurrent').textContent = 'انتهت المعالجة.';
        document.getElementById('batchQpProgressBar').style.width = '100%';
        document.getElementById('batchQpProgressBar').textContent = '100%';
        document.getElementById('batchQpProgressBar').classList.remove('progress-bar-animated');
        const sumEl = document.getElementById('batchQpSummary');
        sumEl.textContent = 'تم بنجاح: ' + okCount + (errCount > 0 ? '، فشل: ' + errCount : '');
        sumEl.classList.remove('d-none');
        document.getElementById('batchQpCloseBtn').style.display = 'inline-block';
        document.getElementById('batchQpDismissBtn').style.display = 'inline-block';
    }

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

    function postForm(url, formData) {
        return fetch(url, { method: 'POST', headers: headers, body: formData }).then(function(r) { return r.json().catch(function() { return {}; }); });
    }
    function postJson(url, body) {
        return fetch(url, { method: 'POST', headers: headersJson, body: typeof body === 'string' ? body : JSON.stringify(body || {}) }).then(function(r) { return r.json().catch(function() { return {}; }); });
    }
    function getJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json().catch(function() { return {}; }); });
    }

    function pollUntil(url, isDone, isError) {
        return new Promise(function(resolve, reject) {
            function poll() {
                getJson(url).then(function(data) {
                    if (isError(data)) return reject(data);
                    if (isDone(data)) return resolve(data);
                    setTimeout(poll, 2500);
                }).catch(reject);
            }
            poll();
        });
    }

    function runQuickPublishForAsset(id, total, videoIndex, videoStatuses, updateUi) {
        const formData = function() { var f = new FormData(); f.append('_token', token); return f; };
        const setRunning = function() { var s = videoStatuses.find(function(x) { return x.id === id; }); if (s) s.status = 'running'; };
        const setDone = function() { var s = videoStatuses.find(function(x) { return x.id === id; }); if (s) s.status = 'done'; };
        const setError = function() { var s = videoStatuses.find(function(x) { return x.id === id; }); if (s) s.status = 'error'; };

        setRunning(); updateUi(videoIndex, total, 0, videoStatuses);
        return postForm(baseUrl + '/' + id + '/move', formData())
            .then(function(data) {
                if (data.error && !data.success && !data.already_moved) throw new Error(data.error || 'نقل المحتوى');
                updateUi(videoIndex, total, 1, videoStatuses);
                return postForm(baseUrl + '/' + id + '/extract', formData());
            })
            .then(function(data) {
                if (data.error && !data.success) throw new Error(data.error || 'استخراج البيانات');
                updateUi(videoIndex, total, 2, videoStatuses);
                return postJson(baseUrl + '/' + id + '/transcribe', { model: 'base' });
            })
            .then(function(data) {
                if (data.error) throw new Error(data.error || 'استخراج النص');
                return pollUntil(baseUrl + '/' + id + '/transcribe-status', function(d) { return d.status === 'completed'; }, function(d) { return d.status === 'error'; });
            })
            .then(function() {
                updateUi(videoIndex, total, 3, videoStatuses);
                return postJson(baseUrl + '/' + id + '/analyze', {});
            })
            .then(function(data) {
                if (data.error) throw new Error(data.error || 'تحليل النص');
                updateUi(videoIndex, total, 4, videoStatuses);
                return postJson(baseUrl + '/' + id + '/optimize-original', { quality: 'balanced' });
            })
            .then(function(data) {
                if (data.error) throw new Error(data.error || 'تقليل الحجم');
                return pollUntil(baseUrl + '/' + id + '/optimize-original-status', function(d) { return d.status === 'completed'; }, function(d) { return d.status === 'error' || d.error; });
            })
            .then(function() {
                updateUi(videoIndex, total, 5, videoStatuses);
                return postJson(baseUrl + '/' + id + '/extract-audio', {});
            })
            .then(function(data) {
                if (data.error) throw new Error(data.error || 'استخراج الصوت');
                return pollUntil(baseUrl + '/' + id + '/extract-audio-status', function(d) { return d.status === 'completed'; }, function(d) { return d.status === 'error' || d.error; });
            })
            .then(function() {
                updateUi(videoIndex, total, 6, videoStatuses);
                return postJson(baseUrl + '/' + id + '/mark-published', {});
            })
            .then(function(data) {
                if (data.error) throw new Error(data.error || 'تفعيل النشر');
                setDone();
            })
            .catch(function() {
                setError();
            });
    }

    function startBatchQuickPublish() {
        const ids = getSelectedIds();
        if (!ids.length) {
            alert('يرجى تحديد فيديو واحد على الأقل.');
            return;
        }
        if (!confirm('سيتم تشغيل النشر السريع لـ ' + ids.length + ' فيديو (نقل → استخراج بيانات → استخراج نص → تحليل → تقليل حجم → استخراج صوت → تفعيل النشر). العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
            return;
        }
        openBatchModal(ids);
        const videoStatuses = ids.map(function(id) { return { id: id, status: 'pending' }; });
        const total = ids.length;
        const updateUi = function(videoIndex, tot, stepIndex, statuses) {
            updateBatchProgress(videoIndex, tot, stepIndex, statuses);
        };

        let chain = Promise.resolve();
        ids.forEach(function(id, index) {
            chain = chain.then(function() {
                updateBatchProgress(index, total, 0, videoStatuses);
                return runQuickPublishForAsset(id, total, index, videoStatuses, updateUi);
            });
        });
        chain.then(function() {
            const okCount = videoStatuses.filter(function(s) { return s.status === 'done'; }).length;
            const errCount = videoStatuses.filter(function(s) { return s.status === 'error'; }).length;
            finishBatchModal(okCount, errCount);
        });
    }

    document.getElementById('bulkQuickPublishBtn')?.addEventListener('click', startBatchQuickPublish);
    document.getElementById('browseQuickPublishBtn')?.addEventListener('click', startBatchQuickPublish);

    document.getElementById('bulkTranslateBtn')?.addEventListener('click', startBatchTranslate);

    document.getElementById('batchQpDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchTranslateDismissBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('batchQuickPublishModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchQpProgressBar').classList.add('progress-bar-animated');
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
</script>
@endpush
@endsection

