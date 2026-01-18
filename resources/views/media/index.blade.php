@extends('layouts.app')

@section('title', 'إدارة الوسائط')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة الوسائط</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-upload me-1"></i>رفع ملف
    </button>
</div>

<div class="card">
    <div class="card-body">
        @if($mediaFiles->count() > 0)
            <div class="row">
                @foreach($mediaFiles as $file)
                <div class="col-md-3 mb-4">
                    <div class="card">
                        @if($file->type === 'image')
                            <img src="{{ asset('storage/' . $file->path) }}" class="card-img-top" alt="{{ $file->original_name }}" style="height: 200px; object-fit: cover;">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h6 class="card-title">{{ \Illuminate\Support\Str::limit($file->original_name, 20) }}</h6>
                            <small class="text-muted">
                                {{ $file->size_in_kb }} KB<br>
                                {{ $file->uploader->name }}<br>
                                {{ $file->created_at->format('Y-m-d') }}
                            </small>
                            <div class="mt-2">
                                <form action="{{ route('media.destroy', $file) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                        <i class="bi bi-trash"></i> حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    عرض {{ $mediaFiles->firstItem() ?? 0 }} إلى {{ $mediaFiles->lastItem() ?? 0 }} من {{ $mediaFiles->total() }} نتيجة
                </div>
                <div>
                    {{ $mediaFiles->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-images fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد ملفات حتى الآن</p>
            </div>
        @endif
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('media.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">رفع ملف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">اختر الملف <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <small class="text-muted">الحد الأقصى: 10 ميجابايت</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">رفع</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

