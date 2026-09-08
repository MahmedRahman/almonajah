@extends('layouts.app')

@section('title', 'مسابقة الحصانة — المشاركات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1">مسابقة تطبيق الحصانة</h2>
        <div class="text-muted">عرض الإجابات والمشاركين المسجّلين</div>
    </div>
    <a href="{{ route('landing.hisana-contest') }}" target="_blank" class="btn btn-outline-primary">
        <i class="bi bi-box-arrow-up-left me-1"></i>فتح صفحة المسابقة
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">إجمالي المشاركات</div>
                <div class="fs-3 fw-bold text-teal">{{ $total }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">إيميلات تم إرسالها</div>
                <div class="fs-3 fw-bold">{{ $emailed }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">بانتظار الإرسال</div>
                <div class="fs-3 fw-bold">{{ max(0, $total - $emailed) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('hisana-contest.admin.index') }}" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">بحث</label>
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="إيميل / اسم / إجابة">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">بحث</button>
                <a href="{{ route('hisana-contest.admin.index') }}" class="btn btn-outline-secondary">مسح</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($entries->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الإيميل</th>
                            <th>الإجابة</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإيميل</th>
                            <th style="width:150px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->id }}</td>
                                <td>{{ $entry->name ?: '—' }}</td>
                                <td dir="ltr" class="text-start">{{ $entry->email }}</td>
                                <td style="max-width:360px;white-space:pre-wrap;">{{ $entry->answer }}</td>
                                <td>{{ optional($entry->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($entry->email_sent_at)
                                        <span class="badge bg-success">أُرسل {{ $entry->email_sent_at->format('m-d H:i') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">لم يُرسل</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <form action="{{ route('hisana-contest.admin.resend', $entry) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-envelope me-1"></i>إرسال
                                        </button>
                                    </form>
                                    <form action="{{ route('hisana-contest.admin.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف هذه المشاركة؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    عرض {{ $entries->firstItem() ?? 0 }} إلى {{ $entries->lastItem() ?? 0 }} من {{ $entries->total() }}
                </div>
                {{ $entries->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="text-center text-muted py-5">لا توجد مشاركات حتى الآن.</div>
        @endif
    </div>
</div>
@endsection
