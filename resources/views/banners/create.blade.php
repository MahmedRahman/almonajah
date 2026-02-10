@extends('layouts.app')

@section('title', 'إضافة بنر إعلاني')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إضافة بنر إعلاني</h2>
    <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right me-1"></i>العودة للقائمة
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('banners.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="image" class="form-label">الصورة <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" required>
                <small class="text-muted">الحد الأقصى: 5MB (JPEG, PNG, JPG, GIF, WEBP)</small>
                @error('image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="link" class="form-label">الرابط</label>
                <input type="url" class="form-control @error('link') is-invalid @enderror" id="link" name="link" value="{{ old('link') }}" placeholder="https://...">
                <small class="text-muted">عند الضغط على البنر يتم الانتقال لهذا الرابط (يفتح في نافذة جديدة)</small>
                @error('link')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">مقاس البنر <span class="text-danger">*</span></label>
                <div class="banner-size-cards">
                    @foreach(\App\Models\Banner::sizeLabels() as $value => $label)
                        <label class="banner-size-card {{ old('size') === $value ? 'selected' : (old('size') === null && $value === 'vertical' ? 'selected' : '') }}">
                            <input type="radio" name="size" value="{{ $value }}" class="d-none" {{ old('size') === $value ? 'checked' : (old('size') === null && $value === 'vertical' ? 'checked' : '') }} required>
                            <span class="banner-size-card-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <small class="text-muted d-block mt-2">عمودي: 9:16 — عريض: 16:9 — مستطيل: نسبة أفقية عريضة (مقترح 2:1 أو 4:1)</small>
                @error('size')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">أماكن النشر</label>
                <div class="banner-placement-cards">
                    <label class="banner-placement-card {{ old('show_on_home', true) ? 'selected' : '' }}">
                        <input type="checkbox" class="d-none" name="show_on_home" value="1" {{ old('show_on_home', true) ? 'checked' : '' }}>
                        <i class="bi bi-house-door me-2"></i>
                        <span>الصفحة الرئيسية</span>
                    </label>
                    <label class="banner-placement-card {{ old('show_on_video_detail') ? 'selected' : '' }}">
                        <input type="checkbox" class="d-none" name="show_on_video_detail" value="1" {{ old('show_on_video_detail') ? 'checked' : '' }}>
                        <i class="bi bi-play-circle me-2"></i>
                        <span>صفحة تفاصيل الفيديو</span>
                    </label>
                    <label class="banner-placement-card {{ old('show_on_categories') ? 'selected' : '' }}">
                        <input type="checkbox" class="d-none" name="show_on_categories" value="1" {{ old('show_on_categories') ? 'checked' : '' }}>
                        <i class="bi bi-tags me-2"></i>
                        <span>صفحة التصنيفات</span>
                    </label>
                </div>
            </div>
            <div class="mb-3">
                <label for="order" class="form-label">الترتيب</label>
                <input type="number" class="form-control" id="order" name="order" value="{{ old('order', 0) }}" min="0" step="1">
                <small class="text-muted">كلما قل الرقم، ظهر البنر أولاً</small>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">البنر نشط (يُعرض في الموقع)</label>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>حفظ البنر
            </button>
            <a href="{{ route('banners.index') }}" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

@push('styles')
<style>
.banner-size-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.banner-size-card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    min-height: 80px;
}
.banner-size-card:hover {
    border-color: var(--primary-color, #188781);
    background-color: rgba(24, 135, 129, 0.06);
}
.banner-size-card.selected {
    border-color: var(--primary-color, #188781);
    background-color: rgba(24, 135, 129, 0.12);
    font-weight: 600;
}
.banner-size-card-label {
    font-size: 0.95rem;
}
.banner-placement-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.banner-placement-card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    min-height: 70px;
}
.banner-placement-card:hover {
    border-color: var(--primary-color, #188781);
    background-color: rgba(24, 135, 129, 0.06);
}
.banner-placement-card.selected {
    border-color: var(--primary-color, #188781);
    background-color: rgba(24, 135, 129, 0.12);
    font-weight: 600;
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.banner-size-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.banner-size-card').forEach(function(c) { c.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});
document.querySelectorAll('.banner-placement-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var cb = this.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        this.classList.toggle('selected', cb.checked);
    });
});
</script>
@endpush
@endsection
