@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">لوحة التحكم</h2>
    <span class="text-muted">مرحباً، {{ auth()->user()->name }}</span>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card blue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">إجمالي المحتوى</h6>
                    <h3 class="mb-0">{{ $stats['total_content'] }}</h3>
                </div>
                <i class="bi bi-file-text fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card green">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">منشور</h6>
                    <h3 class="mb-0">{{ $stats['published_content'] }}</h3>
                </div>
                <i class="bi bi-check-circle fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card orange">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">الفيديوهات</h6>
                    <h3 class="mb-0">{{ $video_stats['total'] }}</h3>
                    <small class="text-white-50">{{ $video_stats['videos'] }} فيديو</small>
                </div>
                <i class="bi bi-play-circle fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card purple">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">التصنيفات</h6>
                    <h3 class="mb-0">{{ $stats['total_categories'] }}</h3>
                </div>
                <i class="bi bi-tags fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Video Statistics -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-play-circle me-2"></i>إحصائيات الفيديوهات
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-primary mb-1">{{ $video_stats['total'] }}</h4>
                            <small class="text-muted">إجمالي الملفات</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-success mb-1">{{ $video_stats['videos'] }}</h4>
                            <small class="text-muted">فيديوهات</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-info mb-1">{{ number_format($video_stats['total_size_mb'], 2) }}</h4>
                            <small class="text-muted">ميجابايت</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded">
                            <h4 class="text-warning mb-1">{{ number_format($video_stats['total_duration_hours'], 2) }}</h4>
                            <small class="text-muted">ساعة</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <h6 class="mb-3">حسب الاتجاه:</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>أفقي:</span>
                            <strong class="text-success">{{ $video_stats['landscape'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>عمودي:</span>
                            <strong class="text-info">{{ $video_stats['portrait'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>مربع:</span>
                            <strong class="text-secondary">{{ $video_stats['square'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="mb-3">حسب الامتداد:</h6>
                        <div class="row">
                            @foreach($video_stats['by_extension'] as $ext)
                            <div class="col-md-4 mb-2">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="badge bg-secondary">{{ strtoupper($ext->extension) }}</span>
                                    <strong>{{ $ext->count }}</strong>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('assets.index') }}" class="btn btn-primary">
                        <i class="bi bi-play-circle me-1"></i>عرض جميع الفيديوهات
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmTruncateAssets()">
                        <i class="bi bi-trash me-1"></i>حذف جميع الفيديوهات
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر المحتوى</h5>
                <a href="{{ route('content.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>إضافة محتوى جديد
                </a>
            </div>
            <div class="card-body">
                @if($recent_content->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>النوع</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_content as $item)
                                <tr>
                                    <td>{{ \Illuminate\Support\Str::limit($item->title, 30) }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $item->type }}</span>
                                    </td>
                                    <td>
                                        @if($item->status === 'published')
                                            <span class="badge bg-success">منشور</span>
                                        @elseif($item->status === 'draft')
                                            <span class="badge bg-warning">مسودة</span>
                                        @else
                                            <span class="badge bg-secondary">مؤرشف</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $item->created_at->format('Y-m-d') }}</small></td>
                                    <td>
                                        <a href="{{ route('content.show', $item) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">لا يوجد محتوى حتى الآن</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر الفيديوهات</h5>
                <a href="{{ route('assets.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-play-circle me-1"></i>عرض الكل
                </a>
            </div>
            <div class="card-body">
                @if($recent_assets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>اسم الملف</th>
                                    <th>الأبعاد</th>
                                    <th>المدة</th>
                                    <th>الحجم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_assets as $asset)
                                <tr>
                                    <td>
                                        <small>{{ \Illuminate\Support\Str::limit($asset->file_name, 30) }}</small>
                                    </td>
                                    <td>
                                        @if($asset->width && $asset->height)
                                            <span class="badge bg-secondary">{{ $asset->width }}×{{ $asset->height }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asset->duration_seconds)
                                            <small>{{ $asset->duration_formatted }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asset->size_bytes)
                                            <small>{{ $asset->size_mb < 1 ? $asset->size_kb . ' KB' : $asset->size_mb . ' MB' }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">لا توجد فيديوهات حتى الآن</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">إحصائيات سريعة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>الوسائط:</span>
                            <strong>{{ $stats['total_media'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>الفيديوهات:</span>
                            <strong>{{ $stats['total_assets'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>المستخدمون:</span>
                            <strong>{{ $stats['total_users'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>المسودات:</span>
                            <strong>{{ $stats['draft_content'] }}</strong>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <a href="{{ route('content.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>إنشاء محتوى جديد
                    </a>
                    <a href="{{ route('assets.index') }}" class="btn btn-success">
                        <i class="bi bi-play-circle me-1"></i>عرض الفيديوهات
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmTruncateAssets() {
    const count = {{ $video_stats['total'] }};
    
    if (confirm(`هل أنت متأكد من حذف جميع الفيديوهات؟\n\nسيتم حذف ${count} فيديو نهائياً ولا يمكن التراجع عن هذا الإجراء!`)) {
        // إنشاء form لإرسال POST request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("dashboard.truncate-assets") }}';
        
        // إضافة CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // إضافة confirm parameter
        const confirmInput = document.createElement('input');
        confirmInput.type = 'hidden';
        confirmInput.name = 'confirm';
        confirmInput.value = 'yes';
        form.appendChild(confirmInput);
        
        // إرسال form
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush

