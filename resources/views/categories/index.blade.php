@extends('layouts.app')

@section('title', 'إدارة التصنيفات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة التصنيفات</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle me-1"></i>إضافة تصنيف جديد
    </button>
</div>

<div class="card">
    <div class="card-body">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الاسم</th>
                            <th>عدد المحتوى</th>
                            <th>إظهار في الموقع</th>
                            <th>الترتيب</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td>
                                @if($category->image_path)
                                    <img src="{{ asset('storage/' . $category->image_path) }}" 
                                         alt="{{ $category->name }}" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="text-decoration-none">
                                    @if($category->color)
                                        <span class="badge" style="background-color: {{ $category->color }}">
                                            {{ $category->name }}
                                        </span>
                                    @else
                                        {{ $category->name }}
                                    @endif
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="text-decoration-none">{{ $category->assets_count }}</a>
                            </td>
                            <td>
                                @if($category->show_on_site ?? true)
                                    <span class="badge bg-success">نعم</span>
                                @else
                                    <span class="badge bg-secondary">لا</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $category->order ?? 0 }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ $category->color ?? '' }}', '{{ $category->image_path ? asset('storage/' . $category->image_path) : '' }}', {{ ($category->show_on_site ?? true) ? 'true' : 'false' }}, {{ $category->order ?? 0 }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
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
                    عرض {{ $categories->firstItem() ?? 0 }} إلى {{ $categories->lastItem() ?? 0 }} من {{ $categories->total() }} نتيجة
                </div>
                <div>
                    {{ $categories->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-tags fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد تصنيفات حتى الآن</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة تصنيف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="color" class="form-label">اللون</label>
                        <input type="color" class="form-control form-control-color" id="color" name="color">
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">الصورة</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">الحد الأقصى: 2MB (JPEG, PNG, JPG, GIF, WEBP)</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="show_on_site" name="show_on_site" value="1" checked>
                        <label class="form-check-label" for="show_on_site">إظهار التصنيف في الموقع (القائمة الجانبية والفلترة)</label>
                    </div>
                    <div class="mb-3">
                        <label for="order" class="form-label">الترتيب</label>
                        <input type="number" class="form-control" id="order" name="order" value="0" min="0" step="1">
                        <small class="text-muted">كلما قل الرقم، ظهر التصنيف أولاً (0 هو الأول)</small>
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

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">تعديل التصنيف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_color" class="form-label">اللون</label>
                        <input type="color" class="form-control form-control-color" id="edit_color" name="color">
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">الصورة</label>
                        <div id="edit_image_preview" class="mb-2" style="display: none;">
                            <img id="edit_image_preview_img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*" onchange="previewEditImage(this)">
                        <small class="text-muted">الحد الأقصى: 2MB (JPEG, PNG, JPG, GIF, WEBP)</small>
                        <div id="edit_current_image" class="mt-2"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_show_on_site" name="show_on_site" value="1">
                        <label class="form-check-label" for="edit_show_on_site">إظهار التصنيف في الموقع (القائمة الجانبية والفلترة)</label>
                    </div>
                    <div class="mb-3">
                        <label for="edit_order" class="form-label">الترتيب</label>
                        <input type="number" class="form-control" id="edit_order" name="order" value="0" min="0" step="1">
                        <small class="text-muted">كلما قل الرقم، ظهر التصنيف أولاً (0 هو الأول)</small>
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
function editCategory(id, name, color, imagePath, showOnSite, order) {
    document.getElementById('editCategoryForm').action = `/categories/${id}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_color').value = color || '#000000';
    document.getElementById('edit_show_on_site').checked = showOnSite !== false;
    document.getElementById('edit_order').value = order || 0;
    
    // Reset image preview
    document.getElementById('edit_image_preview').style.display = 'none';
    document.getElementById('edit_image').value = '';
    
    // Show current image if exists
    const currentImageDiv = document.getElementById('edit_current_image');
    if (imagePath) {
        currentImageDiv.innerHTML = `
            <div class="mt-2">
                <label class="form-label small">الصورة الحالية:</label>
                <div>
                    <img src="${imagePath}" alt="Current Image" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                </div>
            </div>
        `;
    } else {
        currentImageDiv.innerHTML = '';
    }
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function previewEditImage(input) {
    const preview = document.getElementById('edit_image_preview');
    const previewImg = document.getElementById('edit_image_preview_img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>
@endpush
@endsection

