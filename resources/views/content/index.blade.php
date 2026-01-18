@extends('layouts.app')

@section('title', 'إدارة المحتوى')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة المحتوى</h2>
    <a href="{{ route('content.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>إضافة محتوى جديد
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($contentItems->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th>المؤلف</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contentItems as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
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
                            <td>{{ $item->author->name }}</td>
                            <td>{{ $item->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('content.show', $item) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('content.edit', $item) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('content.destroy', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" 
                                                onclick="return confirm('هل أنت متأكد من الحذف؟')">
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
                    عرض {{ $contentItems->firstItem() ?? 0 }} إلى {{ $contentItems->lastItem() ?? 0 }} من {{ $contentItems->total() }} نتيجة
                </div>
                <div>
                    {{ $contentItems->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-file-text fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا يوجد محتوى حتى الآن</p>
                <a href="{{ route('content.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>إضافة محتوى جديد
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

