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
                            <td>
                                <a href="{{ route('assets.index', ['playlist' => $playlist->id]) }}" class="text-decoration-none fw-medium text-dark">
                                    {{ $playlist->title }}
                                </a>
                            </td>
                            <td><code>{{ $playlist->slug }}</code></td>
                            <td>{{ \Illuminate\Support\Str::limit($playlist->description, 50) }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $playlist->assets_count ?? 0 }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('assets.index', ['playlist' => $playlist->id]) }}" class="btn btn-outline-primary" title="عرض الفيديوهات في صفحة إدارة الفيديوهات">
                                        <i class="bi bi-collection-play"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" title="ترتيب الملفات"
                                            onclick="openOrderModal({{ $playlist->id }}, '{{ addslashes($playlist->title) }}')">
                                        <i class="bi bi-sort-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="editPlaylist({{ $playlist->id }}, '{{ addslashes($playlist->title) }}', '{{ addslashes($playlist->slug ?? '') }}', '{{ addslashes($playlist->description ?? '') }}', '{{ $playlist->image_path ? asset('storage/' . $playlist->image_path) : '' }}')">
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

<!-- ترتيب الملفات Modal -->
<div class="modal fade" id="orderPlaylistModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderPlaylistModalTitle">ترتيب الملفات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="orderPlaylistLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">جاري تحميل الملفات...</p>
                </div>
                <div id="orderPlaylistEmpty" class="text-center py-4 d-none">
                    <i class="bi bi-folder2-open fs-1 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد ملفات في هذه القائمة</p>
                </div>
                <p id="orderPlaylistHint" class="text-muted small mb-2 d-none">
                    <i class="bi bi-arrows-move me-1"></i>اسحب الملفات من المقبض <i class="bi bi-grip-vertical"></i> لإعادة الترتيب، ثم اضغط «حفظ الترتيب».
                </p>
                <ul id="orderPlaylistList" class="list-group list-group-flush d-none playlist-order-list"></ul>
            </div>
            <div class="modal-footer d-none" id="orderPlaylistFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="orderPlaylistSaveBtn">
                    <i class="bi bi-check-lg me-1"></i>حفظ الترتيب
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.playlist-order-list .playlist-order-item {
    cursor: default;
    user-select: none;
    transition: background-color 0.15s ease, box-shadow 0.15s ease;
}
.playlist-order-list .playlist-order-handle {
    cursor: grab;
    padding: 0.25rem 0.35rem;
    border-radius: 0.25rem;
    line-height: 1;
}
.playlist-order-list .playlist-order-handle:active {
    cursor: grabbing;
}
.playlist-order-list .playlist-order-ghost {
    opacity: 0.45;
    background: var(--bs-light);
}
.playlist-order-list .playlist-order-chosen {
    background: rgba(24, 135, 129, 0.08);
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
let orderPlaylistId = null;
let orderPlaylistItems = [];
let orderSortable = null;

function openOrderModal(playlistId, playlistTitle) {
    orderPlaylistId = playlistId;
    document.getElementById('orderPlaylistModalTitle').textContent = 'ترتيب الملفات — ' + playlistTitle;
    document.getElementById('orderPlaylistLoading').classList.remove('d-none');
    document.getElementById('orderPlaylistEmpty').classList.add('d-none');
    document.getElementById('orderPlaylistList').classList.add('d-none');
    document.getElementById('orderPlaylistHint').classList.add('d-none');
    document.getElementById('orderPlaylistFooter').classList.add('d-none');
    document.getElementById('orderPlaylistList').innerHTML = '';
    if (orderSortable) {
        orderSortable.destroy();
        orderSortable = null;
    }

    fetch(`/admin/playlists/${playlistId}/items`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('orderPlaylistLoading').classList.add('d-none');
        orderPlaylistItems = data.items || [];
        if (orderPlaylistItems.length === 0) {
            document.getElementById('orderPlaylistEmpty').classList.remove('d-none');
        } else {
            renderOrderList();
            document.getElementById('orderPlaylistList').classList.remove('d-none');
            document.getElementById('orderPlaylistHint').classList.remove('d-none');
            document.getElementById('orderPlaylistFooter').classList.remove('d-none');
            initOrderSortable();
        }
    })
    .catch(() => {
        document.getElementById('orderPlaylistLoading').classList.add('d-none');
        document.getElementById('orderPlaylistEmpty').classList.remove('d-none');
        document.getElementById('orderPlaylistEmpty').querySelector('p').textContent = 'حدث خطأ أثناء التحميل';
    });

    new bootstrap.Modal(document.getElementById('orderPlaylistModal')).show();
}

function renderOrderList() {
    const ul = document.getElementById('orderPlaylistList');
    ul.innerHTML = '';
    orderPlaylistItems.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-2 playlist-order-item';
        li.dataset.assetId = item.id;
        li.innerHTML = `
            <span class="playlist-order-handle text-muted" title="اسحب للترتيب"><i class="bi bi-grip-vertical fs-5"></i></span>
            <span class="playlist-order-num badge bg-light text-dark border">${index + 1}</span>
            <div class="flex-grow-1 min-width-0">
                <strong class="d-block text-truncate">${escapeHtml(item.title)}</strong>
                ${item.duration ? `<small class="text-muted">${item.duration}</small>` : ''}
            </div>
        `;
        ul.appendChild(li);
    });
}

function syncOrderFromDom() {
    const ul = document.getElementById('orderPlaylistList');
    const newOrder = [];
    ul.querySelectorAll('li[data-asset-id]').forEach((li, index) => {
        const id = parseInt(li.dataset.assetId, 10);
        const item = orderPlaylistItems.find(i => i.id === id);
        if (item) newOrder.push(item);
        const numSpan = li.querySelector('.playlist-order-num');
        if (numSpan) numSpan.textContent = index + 1;
    });
    orderPlaylistItems = newOrder;
}

function initOrderSortable() {
    const ul = document.getElementById('orderPlaylistList');
    if (!ul || typeof Sortable === 'undefined') return;
    if (orderSortable) {
        orderSortable.destroy();
        orderSortable = null;
    }
    orderSortable = new Sortable(ul, {
        animation: 180,
        handle: '.playlist-order-handle',
        ghostClass: 'playlist-order-ghost',
        chosenClass: 'playlist-order-chosen',
        draggable: '.playlist-order-item',
        onEnd: function() {
            syncOrderFromDom();
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.getElementById('orderPlaylistSaveBtn').addEventListener('click', function() {
    syncOrderFromDom();
    if (!orderPlaylistId || orderPlaylistItems.length === 0) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    const token = document.querySelector('meta[name="csrf-token"]');
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');

    fetch(`/admin/playlists/${orderPlaylistId}/reorder`, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({ asset_ids: orderPlaylistItems.map(i => i.id) })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('orderPlaylistModal')).hide();
        } else {
            alert(data.error || 'فشل حفظ الترتيب');
        }
    })
    .catch(() => alert('حدث خطأ في الاتصال'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>حفظ الترتيب';
    });
});

function editPlaylist(id, title, slug, description, imagePath) {
    document.getElementById('editPlaylistForm').action = `/admin/playlists/${id}`;
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
