@extends('layouts.app')

@section('title', 'إدارة الإعلانات (البنرات)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة الإعلانات (البنرات)</h2>
    <a href="{{ route('banners.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>إضافة بنر جديد
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($banners->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 100px;">المعاينة</th>
                            <th>الرابط</th>
                            <th>المقاس</th>
                            <th>أماكن النشر</th>
                            <th>الترتيب</th>
                            <th>الحالة</th>
                            <th style="width: 140px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($banners as $banner)
                        <tr>
                            <td>
                                @if($banner->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner->image_path))
                                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="بنر" class="rounded" style="width: 80px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white" style="width: 80px; height: 50px;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $banner->link }}">{{ $banner->link }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $sizeBadges = [
                                        'vertical' => ['عمودي', 'bg-info'],
                                        'landscape' => ['عريض', 'bg-primary'],
                                        'rectangle' => ['مستطيل', 'bg-secondary'],
                                    ];
                                    $s = $sizeBadges[$banner->size] ?? [$banner->size, 'bg-secondary'];
                                @endphp
                                <span class="badge {{ $s[1] }}">{{ $s[0] }}</span>
                            </td>
                            <td>
                                @if($banner->show_on_home) <span class="badge bg-success me-1">رئيسية</span> @endif
                                @if($banner->show_on_video_detail) <span class="badge bg-primary me-1">تفاصيل فيديو</span> @endif
                                @if($banner->show_on_categories) <span class="badge bg-warning text-dark me-1">تصنيفات</span> @endif
                                @if(!$banner->show_on_home && !$banner->show_on_video_detail && !$banner->show_on_categories)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $banner->order }}</span></td>
                            <td>
                                @if($banner->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">غير نشط</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('banners.edit', $banner) }}" class="btn btn-outline-primary" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('banners.destroy', $banner) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا البنر؟');">
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
                    عرض {{ $banners->firstItem() ?? 0 }} إلى {{ $banners->lastItem() ?? 0 }} من {{ $banners->total() }} نتيجة
                </div>
                <div>
                    {{ $banners->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-megaphone fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد بنرات حتى الآن</p>
                <a href="{{ route('banners.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i>إضافة بنر
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
