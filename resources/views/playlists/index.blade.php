@extends('layouts.app')

@section('title', 'إدارة قوائم التشغيل')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="fw-bold mb-0">إدارة قوائم التشغيل</h2>
    <button type="button" class="btn btn-primary" onclick="openAddPlaylistModal()">
        <i class="bi bi-plus-circle me-1"></i>إضافة قائمة تشغيل أساسية
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if($playlists->count() > 0)
            <p class="text-muted small mb-3">
                يمكنك إنشاء قوائم فرعية داخل أي قائمة (مثل: برنامج ← موسم ← ريلز / فيديوهات) عبر زر <i class="bi bi-plus-lg"></i> بجانب القائمة.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                            @include('playlists._row', ['playlist' => $playlist, 'depth' => 0])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    عرض {{ $playlists->firstItem() ?? 0 }} إلى {{ $playlists->lastItem() ?? 0 }} من {{ $playlists->total() }} قائمة أساسية
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
                <input type="hidden" name="parent_id" id="add_parent_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPlaylistModalTitle">إضافة قائمة تشغيل جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="addParentInfo" class="alert alert-info py-2 small d-none mb-3"></div>
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
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_visible" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="add_is_visible" name="is_visible" value="1" checked>
                        <label class="form-check-label" for="add_is_visible">إظهار القائمة في الموقع</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="addPlaylistSubmitBtn">حفظ</button>
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
                    <div class="mb-3 form-check form-switch">
                        <input type="hidden" name="is_visible" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit_is_visible" name="is_visible" value="1">
                        <label class="form-check-label" for="edit_is_visible">إظهار القائمة في الموقع</label>
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
.playlist-child-row {
    background-color: rgba(0, 0, 0, 0.015);
}
.playlist-hidden-row {
    opacity: 0.6;
}
.playlist-hidden-row td:first-child img,
.playlist-hidden-row td:first-child > div {
    filter: grayscale(0.6);
}
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

function showAddPlaylistModal() {
    const el = document.getElementById('addPlaylistModal');
    if (!el || !window.bootstrap) return;
    bootstrap.Modal.getOrCreateInstance(el).show();
}

function openAddPlaylistModal() {
    document.getElementById('add_parent_id').value = '';
    document.getElementById('addPlaylistModalTitle').textContent = 'إضافة قائمة تشغيل أساسية';
    document.getElementById('addParentInfo').classList.add('d-none');
    document.getElementById('addPlaylistSubmitBtn').textContent = 'حفظ';
    const form = document.querySelector('#addPlaylistModal form');
    if (form) form.reset();
    document.getElementById('add_parent_id').value = '';
    showAddPlaylistModal();
}

function openAddSubPlaylistModal(parentId, parentTitle) {
    const form = document.querySelector('#addPlaylistModal form');
    if (form) {
        form.reset();
    }
    document.getElementById('add_parent_id').value = String(parentId);
    document.getElementById('addPlaylistModalTitle').textContent = 'إضافة قائمة فرعية';
    const info = document.getElementById('addParentInfo');
    info.textContent = 'داخل القائمة: ' + (parentTitle || '');
    info.classList.remove('d-none');
    document.getElementById('addPlaylistSubmitBtn').textContent = 'حفظ القائمة الفرعية';
    showAddPlaylistModal();
}

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

    const orderModalEl = document.getElementById('orderPlaylistModal');
    if (orderModalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(orderModalEl).show();
    }
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

document.addEventListener('click', function(e) {
    const subBtn = e.target.closest('.btn-add-sub-playlist');
    if (subBtn) {
        e.preventDefault();
        openAddSubPlaylistModal(subBtn.dataset.parentId, subBtn.dataset.parentTitle || '');
        return;
    }

    const orderBtn = e.target.closest('.btn-order-playlist');
    if (orderBtn) {
        e.preventDefault();
        openOrderModal(orderBtn.dataset.playlistId, orderBtn.dataset.playlistTitle || '');
        return;
    }

    const editBtn = e.target.closest('.btn-edit-playlist');
    if (editBtn) {
        e.preventDefault();
        editPlaylist(
            editBtn.dataset.playlistId,
            editBtn.dataset.playlistTitle || '',
            editBtn.dataset.playlistSlug || '',
            editBtn.dataset.playlistDescription || '',
            editBtn.dataset.playlistImage || '',
            editBtn.dataset.playlistVisible !== '0'
        );
    }
});

const orderPlaylistSaveBtn = document.getElementById('orderPlaylistSaveBtn');
if (orderPlaylistSaveBtn) orderPlaylistSaveBtn.addEventListener('click', function() {
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

function editPlaylist(id, title, slug, description, imagePath, isVisible) {
    document.getElementById('editPlaylistForm').action = `/admin/playlists/${id}`;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_slug').value = slug;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_is_visible').checked = isVisible !== false;

    document.getElementById('edit_image_preview').style.display = 'none';
    document.getElementById('edit_image').value = '';

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

    const editModalEl = document.getElementById('editPlaylistModal');
    if (editModalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(editModalEl).show();
    }
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
