@extends('layouts.app')

@section('title', 'التصنيف: ' . $category->name)

@section('content')
@if(!empty($coverImageUrl))
<div class="mb-4 rounded-3 overflow-hidden shadow-sm" style="max-height: 280px; background: var(--bs-light, #f8f9fa);">
    <img src="{{ $coverImageUrl }}" alt="{{ $category->name }}" class="w-100" style="height: 280px; object-fit: cover;">
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">التصنيفات</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">
            @if($category->image_path)
                <img src="{{ asset('storage/' . $category->image_path) }}" alt="{{ $category->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
            @elseif(!empty($coverImageUrl))
                <img src="{{ $coverImageUrl }}" alt="{{ $category->name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
            @endif
            {{ $category->name }}
        </h2>
        <p class="text-muted small mb-0">عدد الملفات: {{ $assets->count() }}</p>
    </div>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>العودة للتصنيفات
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($assets->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 80px;">الصورة</th>
                            <th>العنوان</th>
                            <th>المسار الأصلي</th>
                            <th>حالة النشر</th>
                            <th style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                        <tr>
                            <td>{{ $asset->id }}</td>
                            <td>
                                @if($asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path))
                                    <img src="{{ asset('storage/' . $asset->thumbnail_path) }}" alt="" class="rounded" style="width: 56px; height: 36px; object-fit: cover;">
                                @else
                                    <div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white small" style="width: 56px; height: 36px;"><i class="bi bi-camera-video"></i></div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $asset->title ?: $asset->file_name }}</strong>
                            </td>
                            <td>
                                <code class="small text-break" style="word-break: break-all;">{{ $asset->original_path ?: $asset->relative_path ?: '—' }}</code>
                            </td>
                            <td>
                                @if($asset->is_publishable)
                                    <span class="badge bg-success">منشور</span>
                                @else
                                    <span class="badge bg-secondary">غير منشور</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary" title="عرض الملف">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($asset->is_publishable)
                                <a href="{{ route('assets.show.public', $asset) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="فتح في الموقع">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-folder2-open fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد ملفات تحت هذا التصنيف</p>
                <a href="{{ route('categories.index') }}" class="btn btn-primary">العودة للتصنيفات</a>
            </div>
        @endif
    </div>
</div>
@endsection
