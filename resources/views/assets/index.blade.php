@extends('layouts.app')

@section('title', 'إدارة الفيديوهات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة الفيديوهات</h2>
    <div>
        <form action="{{ route('assets.scan') }}" method="POST" class="d-inline me-2" onsubmit="return confirm('هل تريد مسح المجلد storage/app/public/2025 وإضافة الفيديوهات الجديدة؟')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-search me-1"></i>مسح المجلد
            </button>
        </form>
        <a href="{{ route('assets.duplicates') }}" class="btn btn-warning btn-sm me-2">
            <i class="bi bi-files me-1"></i>تقرير الملفات المكررة
        </a>
        <span class="badge bg-primary me-2">إجمالي: {{ $stats['total'] }}</span>
        <span class="badge bg-info me-2">فيديوهات: {{ $stats['videos'] }}</span>
        <span class="badge bg-success">الحجم: {{ $stats['total_size_mb'] }} MB</span>
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
                            <p class="text-muted mb-0">{{ $stats['landscape'] }} فيديو</p>
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
                            <p class="text-muted mb-0">{{ $stats['portrait'] }} فيديو</p>
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
            <div class="col-md-3">
                <label for="search" class="form-label">البحث</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="{{ request('search') }}" placeholder="ابحث في العنوان...">
            </div>
            <div class="col-md-2">
                <label for="category" class="form-label">التصنيف</label>
                <select class="form-select" id="category" name="category">
                    <option value="">الكل</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="speaker_name" class="form-label">اسم المتحدث</label>
                <select class="form-select" id="speaker_name" name="speaker_name">
                    <option value="">الكل</option>
                    @foreach($speakerNames as $speaker)
                        <option value="{{ $speaker }}" {{ request('speaker_name') == $speaker ? 'selected' : '' }}>
                            {{ $speaker }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label for="extension" class="form-label">الامتداد</label>
                <select class="form-select" id="extension" name="extension">
                    <option value="">الكل</option>
                    @foreach($extensions as $ext)
                        <option value="{{ $ext }}" {{ request('extension') == $ext ? 'selected' : '' }}>
                            {{ strtoupper($ext) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label for="year" class="form-label">السنة الهجرية</label>
                <select class="form-select" id="year" name="year">
                    <option value="">الكل</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label for="orientation" class="form-label">الاتجاه</label>
                <select class="form-select" id="orientation" name="orientation">
                    <option value="">الكل</option>
                    <option value="portrait" {{ request('orientation') == 'portrait' ? 'selected' : '' }}>عمودي</option>
                    <option value="landscape" {{ request('orientation') == 'landscape' ? 'selected' : '' }}>أفقي</option>
                    <option value="square" {{ request('orientation') == 'square' ? 'selected' : '' }}>مربع</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>بحث
                </button>
            </div>
        </form>
        @if(request()->hasAny(['search', 'category', 'speaker_name', 'extension', 'year', 'orientation']))
            <div class="mt-3">
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>إزالة الفلاتر
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="card-body">
        @if($assets->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>العنوان</th>
                            <th>التصنيف</th>
                            <th>اسم المتحدث</th>
                            <th>السنة الهجرية</th>
                            <th>الامتداد</th>
                            <th>المدة</th>
                            <th>الاتجاه</th>
                            <th>حالة النشر</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                        <tr>
                            <td>{{ $asset->id }}</td>
                            <td>
                                <strong class="text-primary">{{ $asset->title }}</strong>
                            </td>
                            <td>
                                @if($asset->category)
                                    <span class="badge bg-primary">{{ $asset->category }}</span>
                                @else
                                    <span class="text-muted">-</span>
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
                                @if($asset->year)
                                    <span class="badge bg-info">{{ $asset->year }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ strtoupper($asset->extension) }}</span>
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
                                        <i class="bi bi-check-circle me-1"></i>منشور
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-x-circle me-1"></i>غير منشور
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-primary" title="عرض التفاصيل">
                                        <i class="bi bi-eye"></i>
                                    </a>
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
</script>
@endpush
@endsection

