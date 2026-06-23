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
                    <h6 class="text-white-50 mb-2">قوائم التشغيل</h6>
                    <h3 class="mb-0">{{ $stats['total_playlists'] }}</h3>
                </div>
                <i class="bi bi-music-note-list fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Portrait / Landscape highlight -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <i class="bi bi-aspect-ratio text-success fs-2 mb-2 d-block"></i>
                <h6 class="text-muted mb-1">فيديوهات عرضية (منشورة)</h6>
                <h2 class="text-success mb-1">{{ number_format($video_stats['published_landscape']) }}</h2>
                <small class="text-muted">من {{ number_format($video_stats['landscape']) }} على الموقع</small>
                <div class="mt-2 small text-muted">{{ $video_stats['landscape_duration_hours'] }} ساعة محتوى</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <i class="bi bi-phone text-info fs-2 mb-2 d-block"></i>
                <h6 class="text-muted mb-1">فيديوهات طولية (منشورة)</h6>
                <h2 class="text-info mb-1">{{ number_format($video_stats['published_portrait']) }}</h2>
                <small class="text-muted">من {{ number_format($video_stats['portrait']) }} على الموقع</small>
                <div class="mt-2 small text-muted">{{ $video_stats['portrait_duration_hours'] }} ساعة محتوى</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-primary fs-2 mb-2 d-block"></i>
                <h6 class="text-muted mb-1">منشور على الموقع</h6>
                <h2 class="text-primary mb-1">{{ number_format($video_stats['published']) }}</h2>
                <small class="text-muted">{{ number_format($video_stats['published_videos']) }} فيديو</small>
                <div class="mt-2 small text-muted">{{ $video_stats['total_duration_hours'] }} ساعة إجمالي</div>
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
                            <h4 class="text-primary mb-1">{{ number_format($video_stats['published']) }}</h4>
                            <small class="text-muted">منشور على الموقع</small>
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
                            <h4 class="text-warning mb-1">{{ $video_stats['total_duration_hours'] }}</h4>
                            <small class="text-muted">ساعة (إجمالي)</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <h6 class="mb-3">حسب الاتجاه (منشور / الكل):</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>عرضي:</span>
                            <strong class="text-success">{{ $video_stats['published_landscape'] }} <span class="text-muted fw-normal">/ {{ $video_stats['landscape'] }}</span></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>طولي:</span>
                            <strong class="text-info">{{ $video_stats['published_portrait'] }} <span class="text-muted fw-normal">/ {{ $video_stats['portrait'] }}</span></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>مربع:</span>
                            <strong class="text-secondary">{{ $video_stats['published_square'] }} <span class="text-muted fw-normal">/ {{ $video_stats['square'] }}</span></strong>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Playlists & Categories Statistics -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-music-note-list me-2"></i>إحصائيات قوائم التشغيل
                </h5>
                <a href="{{ route('playlists.index') }}" class="btn btn-sm btn-outline-primary">إدارة القوائم</a>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4 col-md">
                        <div class="text-center p-2 border rounded">
                            <h5 class="mb-0 text-primary">{{ number_format($playlist_stats['programs']) }}</h5>
                            <small class="text-muted">برامج</small>
                        </div>
                    </div>
                    <div class="col-4 col-md">
                        <div class="text-center p-2 border rounded">
                            <h5 class="mb-0 text-success">{{ number_format($playlist_stats['seasons']) }}</h5>
                            <small class="text-muted">مواسم</small>
                        </div>
                    </div>
                    <div class="col-4 col-md">
                        <div class="text-center p-2 border rounded">
                            <h5 class="mb-0 text-info">{{ number_format($playlist_stats['sub_playlists']) }}</h5>
                            <small class="text-muted">قوائم فرعية</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-center p-2 border rounded">
                            <h5 class="mb-0 text-warning">{{ number_format($playlist_stats['published_videos_linked']) }}</h5>
                            <small class="text-muted">فيديو منشور</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-center p-2 border rounded">
                            <h5 class="mb-0 text-secondary">{{ number_format($playlist_stats['total']) }}</h5>
                            <small class="text-muted">إجمالي القوائم</small>
                        </div>
                    </div>
                </div>

                @if($program_playlists->count() > 0)
                    <h6 class="mb-2 text-muted small">البرامج والمواسم (فيديوهات منشورة)</h6>
                    <div class="dashboard-playlist-tree">
                        @foreach($program_playlists as $program)
                            <div class="dashboard-program-block mb-2">
                                <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded border bg-light">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        @if($program->image_path)
                                            <img src="{{ asset('storage/' . $program->image_path) }}" alt="" width="36" height="36" class="rounded flex-shrink-0" style="object-fit:cover;">
                                        @else
                                            <span class="dashboard-program-icon flex-shrink-0"><i class="bi bi-collection-play"></i></span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('assets.index', ['playlist' => $program->id]) }}" class="fw-semibold text-dark text-decoration-none text-truncate d-block">
                                                {{ $program->title }}
                                            </a>
                                            <small class="text-muted">
                                                برنامج
                                                @if($program->children_count > 0)
                                                    · {{ $program->children_count }} موسم
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary flex-shrink-0" title="إجمالي الفيديوهات في البرنامج والمواسم">{{ $program->tree_videos_count }}</span>
                                </div>
                                @if($program->children->count() > 0)
                                    <div class="dashboard-season-list ps-3 pe-1 pt-1">
                                        @foreach($program->children as $season)
                                            <div class="d-flex align-items-center justify-content-between gap-2 py-1 px-2 rounded dashboard-season-row">
                                                <div class="d-flex align-items-center gap-2 min-w-0">
                                                    <i class="bi bi-calendar3 text-muted flex-shrink-0"></i>
                                                    <a href="{{ route('assets.index', ['playlist' => $season->id]) }}" class="text-decoration-none text-truncate small">
                                                        {{ $season->title }}
                                                    </a>
                                                    @if(($season->children_count ?? 0) > 0)
                                                        <span class="badge bg-light text-muted border flex-shrink-0">{{ $season->children_count }} فرعي</span>
                                                    @endif
                                                </div>
                                                <span class="badge {{ $season->assets_count > 0 ? 'bg-success' : 'bg-secondary' }} flex-shrink-0">{{ $season->assets_count }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($program->assets_count > 0)
                                    <div class="ps-3 pt-1">
                                        <small class="text-muted">فيديوهات مباشرة على البرنامج: {{ $program->assets_count }}</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0">لا توجد برامج بعد</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-tags me-2"></i>إحصائيات التصنيفات
                </h5>
                <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-primary">إدارة التصنيفات</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0 text-primary">{{ number_format($category_stats['total']) }}</h4>
                            <small class="text-muted">إجمالي التصنيفات</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0 text-success">{{ number_format($category_stats['on_site']) }}</h4>
                            <small class="text-muted">ظاهر بالموقع</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0 text-info">{{ number_format($category_stats['with_videos']) }}</h4>
                            <small class="text-muted">بها فيديوهات</small>
                        </div>
                    </div>
                </div>
                @if($top_categories->count() > 0)
                    <h6 class="mb-2">عدد الفيديوهات لكل تصنيف (منشور)</h6>
                    <div class="row g-2">
                        @foreach($top_categories as $category)
                            @if($category->assets_count > 0)
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                    <span class="text-truncate me-2" title="{{ $category->name }}">
                                        @if($category->image_path)
                                            <img src="{{ asset('storage/' . $category->image_path) }}" alt="" width="20" height="20" class="rounded me-1" style="object-fit:cover;">
                                        @endif
                                        {{ $category->name }}
                                    </span>
                                    <span class="badge bg-secondary">{{ $category->assets_count }}</span>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0">لا توجد تصنيفات بعد</p>
                @endif
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
                                    <td>
                                        @if($item->created_at)
                                            <small>{{ $item->created_at->format('Y-m-d') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
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

<!-- Published Videos Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2"></i>فيديوهات تم نشرها
                </h5>
                <a href="{{ route('assets.index', ['is_publishable' => 1]) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-eye me-1"></i>عرض الكل
                </a>
            </div>
            <div class="card-body">
                @if($published_assets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>اسم الملف</th>
                                    <th>المتحدث</th>
                                    <th>الأبعاد</th>
                                    <th>المدة</th>
                                    <th>الحجم</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($published_assets as $asset)
                                <tr>
                                    <td>
                                        <small>{{ \Illuminate\Support\Str::limit($asset->file_name, 30) }}</small>
                                        @if($asset->title)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($asset->title, 30) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asset->speaker_name)
                                            <span class="badge bg-info">{{ $asset->speaker_name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
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
                                        @if($asset->created_at)
                                            <small>{{ $asset->created_at->format('Y-m-d') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-primary" title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('assets.show.public', $asset) }}" class="btn btn-outline-success" title="عرض في الموقع" target="_blank">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">لا توجد فيديوهات منشورة حتى الآن</p>
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
                            <span>التصنيفات:</span>
                            <strong>{{ $stats['total_categories'] }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-3">
                            <span>قوائم التشغيل:</span>
                            <strong>{{ $stats['total_playlists'] }}</strong>
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

@push('styles')
<style>
.dashboard-playlist-tree {
    max-height: 420px;
    overflow-y: auto;
}
.dashboard-program-icon {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-size: 1rem;
}
.dashboard-season-list {
    border-right: 2px solid rgba(13, 110, 253, 0.2);
    margin-right: 0.35rem;
}
.dashboard-season-row:hover {
    background-color: rgba(13, 110, 253, 0.06);
}
</style>
@endpush

