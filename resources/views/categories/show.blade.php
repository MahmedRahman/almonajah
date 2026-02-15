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
    <div class="d-flex gap-2">
        @if($assets->count() > 0)
            <button type="button" class="btn btn-outline-primary" onclick="openOrderModal({{ $category->id }}, '{{ addslashes($category->name) }}')">
                <i class="bi bi-sort-down me-1"></i>ترتيب الفيديوهات
            </button>
        @endif
        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>العودة للتصنيفات
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($assets->count() > 0)
            <p class="text-muted small mb-2">يمكنك تعديل رقم الترتيب في العمود ثم الضغط على «حفظ ترتيب الجدول»، أو استخدام زر «ترتيب الفيديوهات» لإعادة الترتيب بالسهمين.</p>
            <div class="mb-2">
                <button type="button" class="btn btn-sm btn-primary" id="saveTableOrderBtn" onclick="saveTableOrder()">
                    <i class="bi bi-check-lg me-1"></i>حفظ ترتيب الجدول
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-align-middle" id="categoryAssetsTable" data-category-id="{{ $category->id }}">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">الترتيب</th>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 80px;">الصورة</th>
                            <th>العنوان</th>
                            <th>المسار الأصلي</th>
                            <th>حالة النشر</th>
                            <th style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $index => $asset)
                        <tr data-asset-id="{{ $asset->id }}">
                            <td>
                                <input type="number" class="form-control form-control-sm order-input" value="{{ $index + 1 }}" min="1" max="{{ $assets->count() }}" style="width: 4.5rem;" title="عدّل الرقم ثم احفظ الترتيب">
                            </td>
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

<!-- ترتيب الفيديوهات داخل التصنيف -->
<div class="modal fade" id="orderCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderCategoryModalTitle">ترتيب الفيديوهات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div id="orderCategoryLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">جاري تحميل الفيديوهات...</p>
                </div>
                <div id="orderCategoryEmpty" class="text-center py-4 d-none">
                    <i class="bi bi-folder2-open fs-1 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد فيديوهات في هذا التصنيف</p>
                </div>
                <ul id="orderCategoryList" class="list-group list-group-flush d-none"></ul>
            </div>
            <div class="modal-footer d-none" id="orderCategoryFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="orderCategorySaveBtn">
                    <i class="bi bi-check-lg me-1"></i>حفظ الترتيب
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let orderCategoryId = null;
let orderCategoryItems = [];

function saveTableOrder() {
    var table = document.getElementById('categoryAssetsTable');
    if (!table) return;
    var categoryId = table.getAttribute('data-category-id');
    if (!categoryId) return;
    var rows = table.querySelectorAll('tbody tr');
    var items = [];
    rows.forEach(function(row, idx) {
        var assetId = row.getAttribute('data-asset-id');
        var input = row.querySelector('.order-input');
        var orderVal = input ? parseInt(input.value, 10) : (idx + 1);
        if (assetId) items.push({ assetId: assetId, order: isNaN(orderVal) ? idx + 1 : orderVal });
    });
    items.sort(function(a, b) { return a.order - b.order || 0; });
    var assetIds = items.map(function(i) { return parseInt(i.assetId, 10); });
    var btn = document.getElementById('saveTableOrderBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...'; }
    var token = document.querySelector('meta[name="csrf-token"]');
    var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');
    fetch('{{ url("/categories") }}/' + categoryId + '/reorder-assets', {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({ asset_ids: assetIds })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) { window.location.reload(); }
        else { alert(data.message || data.error || 'فشل حفظ الترتيب'); }
    })
    .catch(function() { alert('حدث خطأ في الاتصال'); })
    .finally(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>حفظ ترتيب الجدول'; }
    });
}

function openOrderModal(categoryId, categoryName) {
    orderCategoryId = categoryId;
    document.getElementById('orderCategoryModalTitle').textContent = 'ترتيب الفيديوهات — ' + categoryName;
    document.getElementById('orderCategoryLoading').classList.remove('d-none');
    document.getElementById('orderCategoryEmpty').classList.add('d-none');
    document.getElementById('orderCategoryList').classList.add('d-none');
    document.getElementById('orderCategoryFooter').classList.add('d-none');
    document.getElementById('orderCategoryList').innerHTML = '';

    fetch('{{ url("/categories") }}/' + categoryId + '/items', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('orderCategoryLoading').classList.add('d-none');
        orderCategoryItems = data.items || [];
        if (orderCategoryItems.length === 0) {
            document.getElementById('orderCategoryEmpty').classList.remove('d-none');
        } else {
            renderOrderList();
            document.getElementById('orderCategoryList').classList.remove('d-none');
            document.getElementById('orderCategoryFooter').classList.remove('d-none');
        }
    })
    .catch(() => {
        document.getElementById('orderCategoryLoading').classList.add('d-none');
        document.getElementById('orderCategoryEmpty').classList.remove('d-none');
        document.getElementById('orderCategoryEmpty').querySelector('p').textContent = 'حدث خطأ أثناء التحميل';
    });

    new bootstrap.Modal(document.getElementById('orderCategoryModal')).show();
}

function renderOrderList() {
    const ul = document.getElementById('orderCategoryList');
    ul.innerHTML = '';
    orderCategoryItems.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center';
        li.dataset.assetId = item.id;
        li.innerHTML = `
            <span class="me-2 text-muted">${index + 1}</span>
            <div class="flex-grow-1 min-width-0">
                <strong class="d-block text-truncate">${escapeHtml(item.title)}</strong>
                ${item.duration ? `<small class="text-muted">${item.duration}</small>` : ''}
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary btn-sm order-move-up" title="تحريك لأعلى" ${index === 0 ? 'disabled' : ''}><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-sm order-move-down" title="تحريك لأسفل" ${index === orderCategoryItems.length - 1 ? 'disabled' : ''}><i class="bi bi-arrow-down"></i></button>
            </div>
        `;
        li.querySelector('.order-move-up').addEventListener('click', () => moveOrder(index, -1));
        li.querySelector('.order-move-down').addEventListener('click', () => moveOrder(index, 1));
        ul.appendChild(li);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function moveOrder(fromIndex, delta) {
    const toIndex = fromIndex + delta;
    if (toIndex < 0 || toIndex >= orderCategoryItems.length) return;
    const arr = orderCategoryItems.slice();
    const [removed] = arr.splice(fromIndex, 1);
    arr.splice(toIndex, 0, removed);
    orderCategoryItems = arr;
    renderOrderList();
}

document.getElementById('orderCategorySaveBtn').addEventListener('click', function() {
    if (!orderCategoryId || orderCategoryItems.length === 0) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    const token = document.querySelector('meta[name="csrf-token"]');
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');

    fetch('{{ url("/categories") }}/' + orderCategoryId + '/reorder-assets', {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({ asset_ids: orderCategoryItems.map(i => i.id) })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('orderCategoryModal')).hide();
            window.location.reload();
        } else {
            alert(data.message || data.error || 'فشل حفظ الترتيب');
        }
    })
    .catch(() => alert('حدث خطأ في الاتصال'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>حفظ الترتيب';
    });
});
</script>
@endpush
@endsection
