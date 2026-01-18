@extends('layouts.app')

@section('title', 'تقرير الملفات المكررة')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">تقرير الملفات المكررة</h2>
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-right me-1"></i>العودة إلى القائمة
    </a>
</div>

@if($totalDuplicates > 0)
<!-- إحصائيات -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning mb-0">{{ $totalDuplicates }}</h3>
                <small class="text-muted">مجموعات مكررة</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-danger mb-0">{{ $totalDuplicateFiles }}</h3>
                <small class="text-muted">ملفات مكررة</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info mb-0">
                    @php
                        $wastedGB = round($totalWastedSpace / 1073741824, 2);
                        $wastedMB = round($totalWastedSpace / 1048576, 2);
                    @endphp
                    @if($wastedGB >= 1)
                        {{ $wastedGB }} GB
                    @else
                        {{ $wastedMB }} MB
                    @endif
                </h3>
                <small class="text-muted">مساحة مهدرة</small>
            </div>
        </div>
    </div>
</div>

<!-- قائمة الملفات المكررة -->
@foreach($duplicateGroups as $index => $group)
<div class="card mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">
                    <i class="bi bi-files text-warning me-2"></i>
                    مجموعة {{ $index + 1 }}: {{ $group['count'] }} ملف مكرر
                </h5>
                <small class="text-muted">
                    اسم الملف: <strong>{{ $group['identifier'] }}</strong>
                </small>
            </div>
            <div>
                <span class="badge bg-danger">
                    @php
                        $groupWasted = ($group['count'] - 1) * $group['assets']->first()->size_bytes;
                        $groupWastedMB = round($groupWasted / 1048576, 2);
                    @endphp
                    مساحة مهدرة: {{ $groupWastedMB }} MB
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>اسم الملف</th>
                        <th>المسار</th>
                        <th>الحجم</th>
                        <th>تاريخ التعديل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['assets'] as $asset)
                    <tr>
                        <td>{{ $asset->id }}</td>
                        <td>
                            <strong>{{ $asset->file_name }}</strong>
                            @if($asset->speaker_name)
                                <br><small class="text-muted">{{ $asset->speaker_name }}</small>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $asset->relative_path }}</small>
                        </td>
                        <td>
                            @php
                                $sizeBytes = $asset->size_bytes;
                                if ($sizeBytes >= 1073741824) {
                                    $size = round($sizeBytes / 1073741824, 2);
                                    $unit = 'GB';
                                } elseif ($sizeBytes >= 1048576) {
                                    $size = round($sizeBytes / 1048576, 2);
                                    $unit = 'MB';
                                } elseif ($sizeBytes >= 1024) {
                                    $size = round($sizeBytes / 1024, 2);
                                    $unit = 'KB';
                                } else {
                                    $size = $sizeBytes;
                                    $unit = 'بايت';
                                }
                            @endphp
                            <strong>{{ number_format($size, $unit === 'بايت' ? 0 : 2) }} {{ $unit }}</strong>
                        </td>
                        <td>
                            @if($asset->modified_at)
                                {{ $asset->modified_at->format('Y-m-d H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> عرض
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

@else
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    <strong>ممتاز!</strong> لا توجد ملفات مكررة في قاعدة البيانات.
</div>
@endif
@endsection

