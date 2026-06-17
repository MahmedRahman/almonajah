@extends('layouts.app')

@section('title', 'إدارة الشيوخ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة الشيوخ</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScholarModal">
        <i class="bi bi-plus-circle me-1"></i>إضافة شيخ جديد
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('scholars.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-8 col-lg-9">
                <label for="scholar_search" class="form-label mb-1">البحث باسم الشيخ</label>
                <input type="text" class="form-control" id="scholar_search" name="search"
                       value="{{ request('search') }}" placeholder="اكتب اسم الشيخ...">
            </div>
            <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i>بحث
                </button>
                @if(request('search'))
                    <a href="{{ route('scholars.index') }}" class="btn btn-outline-secondary" title="مسح البحث">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($scholars->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>اسم الشيخ</th>
                            <th>الحالة</th>
                            <th>الترتيب</th>
                            <th>عدد الفيديوهات</th>
                            <th>الوصف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scholars as $scholar)
                        <tr>
                            <td>
                                @if($scholar->image_path)
                                    <img src="{{ asset('storage/' . $scholar->image_path) }}"
                                         alt="{{ $scholar->name }}"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-person text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                @endif
                            </td>
                            <td><strong>{{ $scholar->name }}</strong></td>
                            <td>
                                @if($scholar->status === 'active')
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">غير نشط</span>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $scholar->order ?? 0 }}</span></td>
                            <td><span class="badge bg-secondary">{{ $scholar->assets_count ?? 0 }} فيديو</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($scholar->description, 50) ?: '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary"
                                            data-update-url="{{ route('scholars.update', $scholar) }}"
                                            data-id="{{ $scholar->id }}"
                                            data-name="{{ e($scholar->name) }}"
                                            data-status="{{ $scholar->status }}"
                                            data-order="{{ $scholar->order ?? 0 }}"
                                            data-description="{{ e($scholar->description ?? '') }}"
                                            data-image="{{ $scholar->image_path ? asset('storage/' . $scholar->image_path) : '' }}"
                                            onclick="editScholarFromBtn(this)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('scholars.destroy', $scholar) }}" method="POST" class="d-inline">
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
                    عرض {{ $scholars->firstItem() ?? 0 }} إلى {{ $scholars->lastItem() ?? 0 }} من {{ $scholars->total() }} نتيجة
                </div>
                <div>
                    {{ $scholars->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-person-badge fs-1 text-muted"></i>
                <p class="text-muted mt-3">
                    @if(request('search'))
                        لا توجد نتائج لـ «{{ request('search') }}»
                    @else
                        لا يوجد شيوخ حتى الآن
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

<!-- Add Scholar Modal -->
<div class="modal fade" id="addScholarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('scholars.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة شيخ جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الشيخ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">الصورة</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">الحد الأقصى: 5MB (JPEG, PNG, JPG, GIF, WEBP)</small>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">الحالة</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="order" class="form-label">الترتيب</label>
                        <input type="number" class="form-control" id="order" name="order" value="0" min="0" step="1">
                        <small class="text-muted">كلما قل الرقم، ظهر الشيخ أولاً (0 هو الأول)</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Scholar Modal -->
<div class="modal fade" id="editScholarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editScholarForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الشيخ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">اسم الشيخ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">الصورة</label>
                        <div id="edit_image_preview" class="mb-2" style="display: none;">
                            <img id="edit_image_preview_img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*" onchange="previewEditImage(this)">
                        <small class="text-muted">الحد الأقصى: 5MB (JPEG, PNG, JPG, GIF, WEBP)</small>
                        <div id="edit_current_image" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">الحالة</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_order" class="form-label">الترتيب</label>
                        <input type="number" class="form-control" id="edit_order" name="order" value="0" min="0" step="1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editScholarFromBtn(btn) {
    var updateUrl = btn.getAttribute('data-update-url');
    var name = btn.getAttribute('data-name') || '';
    var status = btn.getAttribute('data-status') || 'active';
    var order = btn.getAttribute('data-order') || 0;
    var description = btn.getAttribute('data-description') || '';
    var imagePath = btn.getAttribute('data-image') || '';

    document.getElementById('editScholarForm').action = updateUrl;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_order').value = order;
    document.getElementById('edit_description').value = description;

    document.getElementById('edit_image_preview').style.display = 'none';
    document.getElementById('edit_image').value = '';

    var currentImageDiv = document.getElementById('edit_current_image');
    if (imagePath) {
        currentImageDiv.innerHTML = '<div class="mt-2"><label class="form-label small">الصورة الحالية:</label><div><img src="' + imagePath + '" alt="Current" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;"></div></div>';
    } else {
        currentImageDiv.innerHTML = '';
    }

    new bootstrap.Modal(document.getElementById('editScholarModal')).show();
}

function previewEditImage(input) {
    var preview = document.getElementById('edit_image_preview');
    var previewImg = document.getElementById('edit_image_preview_img');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>
@endpush
@endsection
