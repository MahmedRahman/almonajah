@extends('layouts.app')

@section('title', 'إدارة قوائم التشغيل')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إدارة قوائم التشغيل</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlaylistModal">
        <i class="bi bi-plus-circle me-1"></i>إضافة قائمة تشغيل جديدة
    </button>
</div>

<div class="card">
    <div class="card-body">
        @if($playlists->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>العنوان</th>
                            <th>الرابط</th>
                            <th>الوصف</th>
                            <th>عدد المحتوى</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playlists as $playlist)
                        <tr>
                            <td>
                                @if($playlist->image_path)
                                    <img src="{{ asset('storage/' . $playlist->image_path) }}" 
                                         alt="{{ $playlist->title }}" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-music-note-list text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $playlist->title }}</td>
                            <td><code>{{ $playlist->slug }}</code></td>
                            <td>{{ \Illuminate\Support\Str::limit($playlist->description, 50) }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $playlist->assets_count ?? 0 }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="editPlaylist({{ $playlist->id }}, '{{ $playlist->title }}', '{{ $playlist->slug }}', '{{ $playlist->description }}', '{{ $playlist->image_path ? asset('storage/' . $playlist->image_path) : '' }}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" class="d-inline">
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
                    عرض {{ $playlists->firstItem() ?? 0 }} إلى {{ $playlists->lastItem() ?? 0 }} من {{ $playlists->total() }} نتيجة
                </div>
                <div>
                    {{ $playlists->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-music-note-list fs-1 text-muted"></i>
                <p class="text-muted mt-3">لا توجد قوائم تشغيل حتى الآن</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Playlist Modal -->
<div class="modal fade" id="addPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('playlists.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">إضافة قائمة تشغيل جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">الرابط (Slug)</label>
                        <input type="text" class="form-control" id="slug" name="slug">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">الصورة</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">الحد الأقصى: 2MB (JPEG, PNG, JPG, GIF, WEBP)</small>
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

<!-- Edit Playlist Modal -->
<div class="modal fade" id="editPlaylistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPlaylistForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">تعديل قائمة التشغيل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_slug" class="form-label">الرابط (Slug)</label>
                        <input type="text" class="form-control" id="edit_slug" name="slug">
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">الوصف</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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
function editPlaylist(id, title, slug, description, imagePath) {
    document.getElementById('editPlaylistForm').action = `/playlists/${id}`;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_slug').value = slug;
    document.getElementById('edit_description').value = description || '';
    
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
    
    new bootstrap.Modal(document.getElementById('editPlaylistModal')).show();
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
