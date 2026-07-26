@extends('layouts.app')

@section('title', 'إضافة فيديو جديد')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1">إضافة فيديو جديد</h2>
        <p class="text-muted mb-0">ارفع فيديو من جهازك ليُضاف مباشرة إلى مكتبة المناجاة</p>
    </div>
    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>العودة للمكتبة
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
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

        <div class="card border-0 shadow-sm mb-4" id="importVideoUploadCard" style="background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 70%);">
            <div class="card-body py-5 px-4 text-center">
                <div class="mb-3" style="font-size: 3rem; color: #0d9488; line-height: 1;"><i class="bi bi-cloud-arrow-up"></i></div>
                <h4 class="fw-bold mb-2">رفع فيديو من جهازك</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 28rem;">
                    اختر ملف الفيديو ثم اضغط الزر. سيتم الرفع وإضافة الفيديو إلى المكتبة تلقائيًا.
                </p>
                <input type="file" class="form-control form-control-lg mb-3 mx-auto" id="importVideoFileInput" accept="video/*,.mp4,.mov,.mkv,.m4v,.avi,.webm" style="max-width: 480px;">
                <p class="small text-muted mb-4" id="importVideoSelectedFileName">لم يتم اختيار ملف بعد</p>
                <button type="button" class="btn btn-primary btn-lg px-5" id="importVideoUploadImportBtn" disabled>
                    <i class="bi bi-upload me-2"></i>رفع وإضافة للمكتبة
                </button>
                <p class="text-muted small mt-4 mb-0">الصيغ المدعومة: MP4, MOV, MKV, WebM · حتى عدة جيجابايت</p>
                <input type="hidden" id="importVideoDefaultFolder" value="videos/uploads">
                <button type="button" class="d-none" id="importVideoUploadBtn" tabindex="-1" aria-hidden="true"></button>
            </div>
        </div>

        <div id="importVideoResult" class="alert d-none mb-4"></div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <button class="btn btn-link text-decoration-none text-dark fw-semibold p-0 w-100 text-start d-flex justify-content-between align-items-center"
                        type="button" data-bs-toggle="collapse" data-bs-target="#importVideoServerCollapse" aria-expanded="false" aria-controls="importVideoServerCollapse">
                    <span><i class="bi bi-folder2-open me-2 text-primary"></i>استيراد فيديوهات موجودة على السيرفر</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
            <div id="importVideoServerCollapse" class="collapse">
                <div class="card-body">
                    <p class="text-muted small mb-3" id="importVideoUploadTarget">تصفح مجلدات السيرفر ثم حدّد ملفًا أو أكثر للتسجيل.</p>
                    <div id="importVideoSelectBar" class="d-none d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="text-muted small" id="importVideoSelectedCount">0 محدد</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="importVideoSelectAllBtn">تحديد الكل</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="importVideoClearSelectionBtn">إلغاء التحديد</button>
                        <button type="button" class="btn btn-primary btn-sm ms-auto" id="importVideoSubmitBtn" disabled>
                            <i class="bi bi-box-arrow-in-down me-1"></i><span id="importVideoSubmitBtnLabel">تسجيل المحدد من السيرفر</span>
                        </button>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('assets.partials.import-video-script')
@endpush
