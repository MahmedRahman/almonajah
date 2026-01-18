@extends('layouts.app')

@section('title', 'تحليل الفيديوهات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">تحليل الفيديوهات</h2>
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-right me-1"></i>رجوع للفيديوهات
    </a>
</div>

<!-- إحصائيات عامة -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card blue">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">إجمالي الفيديوهات</h6>
                    <h3 class="mb-0">{{ number_format($generalStats['total_videos']) }}</h3>
                </div>
                <i class="bi bi-play-circle fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card green">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">عدد الشيوخ</h6>
                    <h3 class="mb-0">{{ $generalStats['total_speakers'] }}</h3>
                </div>
                <i class="bi bi-people fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card orange">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">المدة الإجمالية</h6>
                    <h3 class="mb-0">{{ $generalStats['total_duration_hours'] }} ساعة</h3>
                </div>
                <i class="bi bi-clock fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card purple">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">الحجم الإجمالي</h6>
                    <h3 class="mb-0">{{ $generalStats['total_size_gb'] }} GB</h3>
                </div>
                <i class="bi bi-hdd fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- تحليل الشيوخ -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-people me-2"></i>تحليل الشيوخ
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>اسم الشيخ</th>
                        <th>إجمالي الفيديوهات</th>
                        <th>أفقي</th>
                        <th>عمودي</th>
                        <th>مربع</th>
                        <th>المدة الإجمالية</th>
                        <th>المدة المتوسطة</th>
                        <th>الحجم الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($speakers as $speaker)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $speaker['name'] }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ $speaker['total_videos'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success">{{ $speaker['landscape'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $speaker['portrait'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $speaker['square'] }}</span>
                        </td>
                        <td>
                            <strong>{{ $speaker['total_duration_formatted'] }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">{{ $speaker['avg_duration_formatted'] }}</small>
                        </td>
                        <td>
                            <strong>{{ $speaker['total_size_mb'] }} MB</strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- تحليل التصنيفات -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-tags me-2"></i>تحليل التصنيفات
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>التصنيف</th>
                        <th>إجمالي الفيديوهات</th>
                        <th>أفقي</th>
                        <th>عمودي</th>
                        <th>مربع</th>
                        <th>المدة الإجمالية</th>
                        <th>الحجم الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $category['name'] }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ $category['total_videos'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success">{{ $category['landscape'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $category['portrait'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $category['square'] }}</span>
                        </td>
                        <td>
                            <strong>{{ $category['total_duration_formatted'] }}</strong>
                        </td>
                        <td>
                            <strong>{{ $category['total_size_mb'] }} MB</strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- تحليل السنوات -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-calendar me-2"></i>تحليل السنوات
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>السنة الهجرية</th>
                        <th>إجمالي الفيديوهات</th>
                        <th>أفقي</th>
                        <th>عمودي</th>
                        <th>المدة الإجمالية</th>
                        <th>الحجم الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($years as $year)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $year['year'] }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ $year['total_videos'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success">{{ $year['landscape'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $year['portrait'] }}</span>
                        </td>
                        <td>
                            <strong>{{ $year['total_duration_formatted'] }}</strong>
                        </td>
                        <td>
                            <strong>{{ $year['total_size_mb'] }} MB</strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- إحصائيات الاتجاهات -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">الفيديوهات الأفقية</h5>
            </div>
            <div class="card-body text-center">
                <h2 class="text-success">{{ number_format($generalStats['landscape_count']) }}</h2>
                <p class="text-muted mb-0">فيديو</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">الفيديوهات العمودية</h5>
            </div>
            <div class="card-body text-center">
                <h2 class="text-info">{{ number_format($generalStats['portrait_count']) }}</h2>
                <p class="text-muted mb-0">فيديو</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">الفيديوهات المربعة</h5>
            </div>
            <div class="card-body text-center">
                <h2 class="text-secondary">{{ number_format($generalStats['square_count']) }}</h2>
                <p class="text-muted mb-0">فيديو</p>
            </div>
        </div>
    </div>
</div>
@endsection


