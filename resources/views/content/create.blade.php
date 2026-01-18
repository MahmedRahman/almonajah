@extends('layouts.app')

@section('title', 'إضافة محتوى جديد')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">إضافة محتوى جديد</h2>
    <a href="{{ route('content.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-right me-1"></i>رجوع
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('content.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">الرابط (Slug)</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                               id="slug" name="slug" value="{{ old('slug') }}">
                        <small class="text-muted">اتركه فارغاً ليتم إنشاؤه تلقائياً</small>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="excerpt" class="form-label">الملخص</label>
                        <textarea class="form-control @error('excerpt') is-invalid @enderror" 
                                  id="excerpt" name="excerpt" rows="3">{{ old('excerpt') }}</textarea>
                        @error('excerpt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">المحتوى <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" name="content" rows="10" required>{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>مسودة</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>منشور</option>
                            <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>مؤرشف</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">النوع <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" 
                                id="type" name="type" required>
                            <option value="article" {{ old('type') === 'article' ? 'selected' : '' }}>مقال</option>
                            <option value="page" {{ old('type') === 'page' ? 'selected' : '' }}>صفحة</option>
                            <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>فيديو</option>
                            <option value="image" {{ old('type') === 'image' ? 'selected' : '' }}>صورة</option>
                            <option value="document" {{ old('type') === 'document' ? 'selected' : '' }}>مستند</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="featured_image" class="form-label">الصورة المميزة</label>
                        <input type="text" class="form-control @error('featured_image') is-invalid @enderror" 
                               id="featured_image" name="featured_image" value="{{ old('featured_image') }}">
                        @error('featured_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">التصنيفات</label>
                        @foreach($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="categories[]" value="{{ $category->id }}" 
                                       id="category_{{ $category->id }}"
                                       {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="category_{{ $category->id }}">
                                    {{ $category->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('content.index') }}" class="btn btn-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>حفظ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection


