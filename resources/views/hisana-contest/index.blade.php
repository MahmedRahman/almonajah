@extends('layouts.app')

@section('title', 'مسابقة الحصانة — المشاركات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1">مسابقة تطبيق الحصانة</h2>
        <div class="text-muted">عرض الإجابات والمشاركين المسجّلين</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('landing.hisana-contest') }}" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-box-arrow-up-left me-1"></i>فتح صفحة المسابقة
        </a>
        <form method="POST" action="{{ route('hisana-contest.admin.toggle') }}">
            @csrf
            <input type="hidden" name="open" value="{{ $contestOpen ? 0 : 1 }}">
            @if($contestOpen)
                <button type="submit" class="btn btn-warning" onclick="return confirm('قفل رابط المسابقة وإيقاف التسجيل؟');">
                    <i class="bi bi-lock me-1"></i>قفل المسابقة
                </button>
            @else
                <button type="submit" class="btn btn-success" onclick="return confirm('فتح رابط المسابقة والسماح بالتسجيل؟');">
                    <i class="bi bi-unlock me-1"></i>فتح المسابقة
                </button>
            @endif
        </form>
    </div>
</div>

<div class="alert {{ $contestOpen ? 'alert-success' : 'alert-danger' }} d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <strong>حالة الرابط:</strong>
        @if($contestOpen)
            المسابقة <span class="badge bg-success">مفتوحة</span> — التسجيل متاح.
        @else
            المسابقة <span class="badge bg-danger">مقفولة</span> — التسجيل متوقف.
        @endif
    </div>
    <code dir="ltr" class="small">{{ route('landing.hisana-contest') }}</code>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">الإجمالي</div>
                <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">اليوم</div>
                <div class="fs-3 fw-bold text-primary">{{ $stats['today'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">آخر 7 أيام</div>
                <div class="fs-3 fw-bold">{{ $stats['week'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">تم إرسال إيميل</div>
                <div class="fs-3 fw-bold text-success">{{ $stats['emailed'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">بانتظار الإرسال</div>
                <div class="fs-3 fw-bold text-warning">{{ $stats['pending'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">بأسماء</div>
                <div class="fs-3 fw-bold">{{ $stats['with_name'] }}</div>
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
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllEntries">
                    <label class="form-check-label" for="selectAllEntries">تحديد الكل في الصفحة</label>
                </div>
                <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                    <i class="bi bi-trash me-1"></i>حذف المحدد (<span id="selectedCount">0</span>)
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الإيميل</th>
                            <th>الإجابة</th>
                            <th>تاريخ التسجيل</th>
                            <th>حالة الإيميل</th>
                            <th style="width:170px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td>
                                    <input class="form-check-input entry-check" type="checkbox" value="{{ $entry->id }}">
                                </td>
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

            <form id="bulkDeleteForm" method="POST" action="{{ route('hisana-contest.admin.bulk-destroy') }}" class="d-none">
                @csrf
                @method('DELETE')
                <div id="bulkIds"></div>
            </form>

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

@push('scripts')
<script>
(function () {
    var selectAll = document.getElementById('selectAllEntries');
    var checks = Array.prototype.slice.call(document.querySelectorAll('.entry-check'));
    var countEl = document.getElementById('selectedCount');
    var bulkBtn = document.getElementById('bulkDeleteBtn');
    var bulkForm = document.getElementById('bulkDeleteForm');
    var bulkIds = document.getElementById('bulkIds');
    if (!selectAll || !bulkBtn) return;

    function selectedChecks() {
        return checks.filter(function (c) { return c.checked; });
    }

    function refresh() {
        var selected = selectedChecks().length;
        countEl.textContent = selected;
        bulkBtn.disabled = selected === 0;
        selectAll.checked = selected > 0 && selected === checks.length;
        selectAll.indeterminate = selected > 0 && selected < checks.length;
    }

    selectAll.addEventListener('change', function () {
        checks.forEach(function (c) { c.checked = selectAll.checked; });
        refresh();
    });
    checks.forEach(function (c) { c.addEventListener('change', refresh); });

    bulkBtn.addEventListener('click', function () {
        var selected = selectedChecks();
        if (!selected.length) return;
        if (!confirm('حذف المشاركات المحددة؟')) return;
        bulkIds.innerHTML = '';
        selected.forEach(function (c) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = c.value;
            bulkIds.appendChild(input);
        });
        bulkForm.submit();
    });

    refresh();
})();
</script>
@endpush
@endsection
