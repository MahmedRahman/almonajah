@extends('layouts.app')

@section('title', $contentItem->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">{{ $contentItem->title }}</h2>
    <div>
        <a href="{{ route('content.edit', $contentItem) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>تعديل
        </a>
        <a href="{{ route('content.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="mb-3">
                    <strong>الحالة:</strong>
                    @if($contentItem->status === 'published')
                        <span class="badge bg-success">منشور</span>
                    @elseif($contentItem->status === 'draft')
                        <span class="badge bg-warning">مسودة</span>
                    @else
                        <span class="badge bg-secondary">مؤرشف</span>
                    @endif
                </div>
                <div class="mb-3">
                    <strong>النوع:</strong> 
                    <span class="badge bg-info">{{ $contentItem->type }}</span>
                </div>
                @if($contentItem->excerpt)
                <div class="mb-3">
                    <strong>الملخص:</strong>
                    <p class="text-muted">{{ $contentItem->excerpt }}</p>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <strong>المؤلف:</strong> {{ $contentItem->author->name }}
                </div>
                <div class="mb-3">
                    <strong>تاريخ الإنشاء:</strong> {{ $contentItem->created_at->format('Y-m-d H:i') }}
                </div>
                @if($contentItem->published_at)
                <div class="mb-3">
                    <strong>تاريخ النشر:</strong> {{ $contentItem->published_at->format('Y-m-d H:i') }}
                </div>
                @endif
                @if($contentItem->categories->count() > 0)
                <div class="mb-3">
                    <strong>التصنيفات:</strong>
                    <div class="mt-2">
                        @foreach($contentItem->categories as $category)
                            <span class="badge bg-secondary me-1">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <hr>

        <div class="content-body">
            <h5>المحتوى:</h5>
            <div class="p-3 bg-light rounded">
                {!! nl2br(e($contentItem->content)) !!}
            </div>
        </div>
    </div>
</div>
@endsection


