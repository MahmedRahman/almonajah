@extends('layouts.app')

@section('title', 'اختيار فائز عشوائي — مسابقة الحصانة')

@section('content')
<style>
.draw-shell { max-width: 720px; margin: 0 auto; }
.draw-stage {
    position: relative;
    overflow: hidden;
    border-radius: 1.25rem;
    background: linear-gradient(160deg, #0f766e 0%, #134e4a 55%, #0f172a 100%);
    color: #fff;
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem 1.25rem;
    box-shadow: 0 20px 50px rgba(15, 118, 110, 0.25);
}
.draw-stage.is-spinning .draw-email {
    animation: drawPulse .18s ease-in-out infinite alternate;
}
.draw-label {
    font-size: .85rem;
    opacity: .8;
    margin-bottom: .35rem;
}
.draw-email {
    font-size: clamp(1.15rem, 3.5vw, 1.75rem);
    font-weight: 800;
    letter-spacing: .02em;
    word-break: break-all;
    direction: ltr;
    min-height: 2.2em;
}
.draw-name {
    margin-top: .4rem;
    font-size: 1rem;
    opacity: .9;
}
.draw-count {
    position: absolute;
    top: 1rem;
    inset-inline-start: 1rem;
    font-size: .8rem;
    background: rgba(255,255,255,.12);
    padding: .35rem .7rem;
    border-radius: 999px;
}
@keyframes drawPulse {
    from { transform: scale(1); opacity: .75; }
    to { transform: scale(1.03); opacity: 1; }
}
.winner-reveal {
    animation: winnerIn .45s ease;
}
@keyframes winnerIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: none; }
}
</style>

<div class="draw-shell">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1">اختيار فائز عشوائي</h2>
            <div class="text-muted">اضغط الزر لاختيار مشارك عشوائي، ثم أرسل له رسالة الفوز</div>
        </div>
        <a href="{{ route('hisana-contest.admin.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>رجوع للمشاركات
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($entries->isEmpty())
        <div class="alert alert-warning">لا توجد مشاركات للاختيار منها.</div>
    @else
        <div class="draw-stage mb-4" id="drawStage">
            <div class="draw-count">{{ $entries->count() }} مشارك</div>
            <div>
                <div class="draw-label" id="drawLabel">اضغط الزر للبدء</div>
                <div class="draw-email" id="drawEmail">؟؟؟@؟؟؟.com</div>
                <div class="draw-name" id="drawName"></div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mb-4 flex-wrap">
            <button type="button" class="btn btn-lg btn-primary px-4" id="drawBtn">
                <i class="bi bi-dice-5 me-1"></i>اختيار عشوائي
            </button>
            <button type="button" class="btn btn-lg btn-outline-primary px-4 d-none" id="redrawBtn">
                <i class="bi bi-arrow-repeat me-1"></i>اختيار مرة أخرى
            </button>
        </div>

        <div class="card border-0 shadow-sm d-none winner-reveal" id="winnerCard">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap">
                    <div>
                        <div class="badge bg-success mb-2">تم الاختيار</div>
                        <h4 class="fw-bold mb-1" id="winnerTitle">الفائز</h4>
                        <div class="text-muted small" id="winnerMeta"></div>
                    </div>
                </div>

                <div class="bg-light rounded-3 p-3 mb-4">
                    <div class="text-muted small mb-1">الإجابة</div>
                    <div id="winnerAnswer" style="white-space:pre-wrap;line-height:1.7;"></div>
                </div>

                <h5 class="fw-bold mb-3">
                    <i class="bi bi-envelope-heart me-1"></i>إرسال رسالة للفائز
                </h5>

                <form method="POST" id="winnerForm" action="#">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="winnerEmailField">إلى (يمكن تعديله)</label>
                        <input type="email" name="email" class="form-control" id="winnerEmailField" dir="ltr" required maxlength="190">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="winnerSubject">عنوان الرسالة</label>
                        <input type="text" name="subject" id="winnerSubject" class="form-control" value="{{ old('subject', $defaultSubject) }}" required maxlength="180">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="winnerMessage">نص الرسالة</label>
                        <textarea name="message" id="winnerMessage" class="form-control" rows="8" required maxlength="5000">{{ old('message', $defaultMessage) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success" id="sendWinnerBtn" onclick="return confirm('إرسال رسالة الفوز إلى هذا الإيميل؟');">
                        <i class="bi bi-send me-1"></i>إرسال الرسالة
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

@unless($entries->isEmpty())
@push('scripts')
<script>
(function () {
    var entries = @json($entriesJson);
    var winnerUrlTemplate = @json(url('/admin/hisana-contest/__ID__/winner-email'));
    var spinning = false;
    var selected = null;
    var timer = null;

    var stage = document.getElementById('drawStage');
    var label = document.getElementById('drawLabel');
    var emailEl = document.getElementById('drawEmail');
    var nameEl = document.getElementById('drawName');
    var drawBtn = document.getElementById('drawBtn');
    var redrawBtn = document.getElementById('redrawBtn');
    var winnerCard = document.getElementById('winnerCard');
    var winnerTitle = document.getElementById('winnerTitle');
    var winnerMeta = document.getElementById('winnerMeta');
    var winnerAnswer = document.getElementById('winnerAnswer');
    var winnerEmailField = document.getElementById('winnerEmailField');
    var winnerForm = document.getElementById('winnerForm');

    function pickRandom() {
        return entries[Math.floor(Math.random() * entries.length)];
    }

    function showSpinning(item) {
        emailEl.textContent = item.email;
        nameEl.textContent = item.name || '';
    }

    function reveal(item) {
        selected = item;
        label.textContent = 'الفائز المختار';
        emailEl.textContent = item.email;
        nameEl.textContent = item.name || 'بدون اسم';
        winnerTitle.textContent = (item.name ? item.name + ' — ' : '') + item.email;
        winnerMeta.textContent = 'مشاركة #' + item.id + (item.created_at ? ' · ' + item.created_at : '');
        winnerAnswer.textContent = item.answer || '—';
        winnerEmailField.value = item.email;
        winnerForm.action = winnerUrlTemplate.replace('__ID__', String(item.id));
        winnerCard.classList.remove('d-none');
        winnerCard.classList.remove('winner-reveal');
        void winnerCard.offsetWidth;
        winnerCard.classList.add('winner-reveal');
        drawBtn.classList.add('d-none');
        redrawBtn.classList.remove('d-none');
    }

    function startDraw() {
        if (spinning || !entries.length) return;
        spinning = true;
        stage.classList.add('is-spinning');
        label.textContent = 'جاري الاختيار...';
        winnerCard.classList.add('d-none');
        drawBtn.disabled = true;
        redrawBtn.disabled = true;

        var ticks = 0;
        var maxTicks = 28;
        clearInterval(timer);
        timer = setInterval(function () {
            showSpinning(pickRandom());
            ticks += 1;
            if (ticks >= maxTicks) {
                clearInterval(timer);
                var winner = pickRandom();
                stage.classList.remove('is-spinning');
                reveal(winner);
                spinning = false;
                drawBtn.disabled = false;
                redrawBtn.disabled = false;
            }
        }, 70);
    }

    drawBtn.addEventListener('click', startDraw);
    redrawBtn.addEventListener('click', startDraw);
})();
</script>
@endpush
@endunless
@endsection
