@extends('layouts.landing')

@section('title', 'مسابقة تطبيق الحصانة | المناجاة')

@section('meta')
<meta name="description" content="مسابقة تطبيق الحصانة لمدة شهر: ما هو دعاء سيد الاستغفار؟ سجّل بإيميلك الآن. سيتم إرسال رابط التطبيق خلال الأيام القادمة. الجائزة 1000 جنيه لأول 3 فائزين.">
<meta name="robots" content="index,follow">
@endsection

@section('content')
@push('styles')
<style>
:root {
    --hc-teal: #0d9488;
    --hc-teal-dark: #0f766e;
    --hc-ink: #1e293b;
    --hc-muted: #64748b;
    --hc-cream: #f0fdfa;
    --hc-gold: #b45309;
}
.hc-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse 80% 50% at 50% -10%, rgba(13, 148, 136, 0.18), transparent 55%),
        linear-gradient(180deg, #ecfdf5 0%, #ffffff 42%, #f8fafc 100%);
    color: var(--hc-ink);
    padding: 1.25rem 1rem 3.5rem;
}
.hc-shell {
    max-width: 560px;
    margin: 0 auto;
}
.hc-brand {
    text-align: center;
    margin-bottom: 1.25rem;
}
.hc-brand img {
    width: 88px;
    height: auto;
    margin-bottom: 0.65rem;
}
.hc-badge {
    display: inline-block;
    background: rgba(13, 148, 136, 0.12);
    color: var(--hc-teal-dark);
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    margin-bottom: 0.85rem;
}
.hc-title {
    font-size: clamp(1.45rem, 5.2vw, 1.95rem);
    font-weight: 700;
    color: var(--hc-teal-dark);
    line-height: 1.45;
    margin: 0 0 0.5rem;
}
.hc-subtitle {
    color: var(--hc-muted);
    font-size: 0.98rem;
    margin: 0;
    line-height: 1.7;
}
.hc-card {
    background: #fff;
    border: 1px solid rgba(13, 148, 136, 0.14);
    border-radius: 1.25rem;
    padding: 1.25rem 1.2rem 1.4rem;
    box-shadow: 0 14px 40px rgba(15, 118, 110, 0.08);
    margin-bottom: 1rem;
}
.hc-card h2 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--hc-teal-dark);
    margin: 0 0 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}
.hc-question {
    background: linear-gradient(135deg, #ccfbf1 0%, #f0fdfa 100%);
    border-right: 4px solid var(--hc-teal);
    border-radius: 0 0.85rem 0.85rem 0;
    padding: 1rem 1.1rem;
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--hc-teal-dark);
    line-height: 1.75;
}
.hc-prize {
    text-align: center;
    padding: 0.35rem 0 0.15rem;
}
.hc-prize-amount {
    font-size: clamp(1.6rem, 6vw, 2.1rem);
    font-weight: 800;
    color: var(--hc-gold);
    margin: 0.15rem 0;
}
.hc-prize-label {
    color: var(--hc-muted);
    font-size: 0.9rem;
    margin: 0;
}
.hc-rules {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: hc;
}
.hc-rules li {
    counter-increment: hc;
    position: relative;
    padding: 0.7rem 0.85rem 0.7rem 0.85rem;
    padding-right: 2.7rem;
    margin-bottom: 0.55rem;
    background: var(--hc-cream);
    border-radius: 0.85rem;
    color: #334155;
    line-height: 1.65;
    font-size: 0.94rem;
}
.hc-rules li::before {
    content: counter(hc);
    position: absolute;
    right: 0.7rem;
    top: 0.75rem;
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 50%;
    background: var(--hc-teal);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hc-qr-wrap {
    text-align: center;
}
.hc-qr {
    width: min(220px, 70vw);
    height: auto;
    border-radius: 1rem;
    background: #fff;
    padding: 0.65rem;
    border: 1px solid rgba(13, 148, 136, 0.18);
    margin: 0.35rem auto 0.75rem;
    display: block;
}
.hc-qr-hint {
    color: var(--hc-muted);
    font-size: 0.88rem;
    margin: 0 0 0.85rem;
}
.hc-download {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: #fff !important;
    text-decoration: none !important;
    font-weight: 700;
    padding: 0.75rem 1.25rem;
    border-radius: 999px;
    box-shadow: 0 8px 22px rgba(13, 148, 136, 0.28);
}
.hc-download:hover { filter: brightness(1.05); color: #fff; }
.hc-form label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.35rem;
}
.hc-form .field { margin-bottom: 0.9rem; }
.hc-form input,
.hc-form textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.85rem;
    padding: 0.8rem 0.95rem;
    font-family: inherit;
    font-size: 0.98rem;
    background: #fff;
    color: var(--hc-ink);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.hc-form input:focus,
.hc-form textarea:focus {
    outline: none;
    border-color: var(--hc-teal);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18);
}
.hc-form textarea { min-height: 120px; resize: vertical; }
.hc-submit {
    width: 100%;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: #fff;
    font-weight: 700;
    font-size: 1.02rem;
    padding: 0.9rem 1rem;
    cursor: pointer;
    margin-top: 0.25rem;
}
.hc-submit:disabled {
    opacity: 0.65;
    cursor: wait;
}
.hc-alert {
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    margin-bottom: 0.95rem;
    font-size: 0.92rem;
    line-height: 1.6;
}
.hc-alert-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.hc-alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.hc-note {
    text-align: center;
    color: var(--hc-muted);
    font-size: 0.82rem;
    margin-top: 0.85rem;
    line-height: 1.6;
}
.hc-footer {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.85rem;
    color: var(--hc-muted);
}
.hc-footer a { color: var(--hc-teal-dark); font-weight: 600; text-decoration: none; }
</style>
@endpush

<div class="hc-page">
    <div class="hc-shell">
        <header class="hc-brand">
            <a href="{{ url('/') }}">
                <img src="{{ asset('images/logo.png') }}" alt="المناجاة">
            </a>
            <div class="hc-badge">مسابقة تطبيق الحصانة</div>
            <h1 class="hc-title">مسابقة تطبيق (الحصانة) بين يديك</h1>
            <p class="hc-subtitle">مدة المسابقة {{ $duration }} — سجّل بإيميلك الآن. سيتم إرسال رابط التطبيق إلى بريدك خلال الأيام القادمة. النتائج تُعلن يوم {{ $resultsDate }} بإذن الله.</p>
        </header>

        <section class="hc-card">
            <h2><i class="bi bi-chat-quote"></i> السؤال</h2>
            <div class="hc-question">ما هو دعاء سيد الاستغفار؟</div>
        </section>

        <section class="hc-card hc-prize">
            <h2 style="justify-content:center;margin-bottom:0.35rem;"><i class="bi bi-trophy"></i> الجائزة</h2>
            <p class="hc-prize-amount">{{ $prize }}</p>
            <p class="hc-prize-label">لأول {{ $winnersCount }} فائزين بإذن الله</p>
        </section>

        <section class="hc-card">
            <h2><i class="bi bi-list-check"></i> شروط المسابقة</h2>
            <ol class="hc-rules">
                <li>تسجيل الإيميل الشخصي والإجابة في النموذج أدناه.</li>
                <li>تحميل التطبيق من الرابط الذي سيُرسل إلى إيميلك خلال الأيام القادمة.</li>
                <li>إبقاء التطبيق محمّلًا في هاتفك الشخصي لمدة شهر كامل.</li>
                <li>مدة المسابقة {{ $duration }}، وتُعلن النتائج يوم {{ $resultsDate }} بإذن الله تعالى.</li>
            </ol>
        </section>

        <section class="hc-card" style="text-align:center;">
            <h2 style="justify-content:center;"><i class="bi bi-envelope-paper"></i> تحميل التطبيق</h2>
            <p class="hc-qr-hint" style="margin-bottom:0;">سيتم إرسال رابط التطبيق إلى بريدك الإلكتروني خلال الأيام القادمة.</p>
        </section>

        <section class="hc-card" id="register">
            <h2><i class="bi bi-envelope-at"></i> سجّل الآن</h2>

            @if (session('success'))
                <div class="hc-alert hc-alert-success">{{ session('success') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="hc-alert hc-alert-error">
                    <ul style="margin:0;padding-right:1.1rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="hcFormAlert" class="hc-alert" style="display:none;"></div>

            <form class="hc-form" id="hcForm" method="POST" action="{{ route('landing.hisana-contest.store') }}">
                @csrf
                <div class="field">
                    <label for="hcName">الاسم (اختياري)</label>
                    <input type="text" id="hcName" name="name" value="{{ old('name') }}" maxlength="120" placeholder="اسمك">
                </div>
                <div class="field">
                    <label for="hcEmail">البريد الإلكتروني *</label>
                    <input type="email" id="hcEmail" name="email" value="{{ old('email') }}" required maxlength="190" placeholder="name@example.com" autocomplete="email" dir="ltr">
                </div>
                <div class="field">
                    <label for="hcAnswer">إجابة دعاء سيد الاستغفار *</label>
                    <textarea id="hcAnswer" name="answer" required maxlength="2000" placeholder="اكتب الدعاء هنا...">{{ old('answer') }}</textarea>
                </div>
                <button type="submit" class="hc-submit" id="hcSubmit">تسجيل الاشتراك الآن</button>
            </form>
            <p class="hc-note">بعد التسجيل سنرسل لك رابط التحميل على إيميلك. أبقِ التطبيق مثبتًا حتى إعلان النتائج.</p>
        </section>

        <footer class="hc-footer">
            <a href="{{ route('landing.hisana') }}">صفحة الحصانة</a>
            ·
            <a href="{{ route('legal.hisana.privacy') }}">سياسة الخصوصية</a>
            ·
            <a href="{{ url('/') }}">المناجاة</a>
        </footer>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('hcForm');
    var submit = document.getElementById('hcSubmit');
    var alertBox = document.getElementById('hcFormAlert');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alertBox.style.display = 'none';
        submit.disabled = true;
        submit.textContent = 'جارٍ التسجيل...';

        var data = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (json) {
                return { ok: res.ok, status: res.status, json: json };
            }).catch(function () {
                return { ok: res.ok, status: res.status, json: null };
            });
        }).then(function (result) {
            if (result.ok && result.json && result.json.success) {
                alertBox.className = 'hc-alert hc-alert-success';
                alertBox.textContent = result.json.message || 'تم تسجيلك بنجاح.';
                alertBox.style.display = 'block';
                form.reset();
                if (typeof window.almonajahMetaTrack === 'function') {
                    window.almonajahMetaTrack('Lead');
                }
                return;
            }
            var msg = 'تعذر التسجيل. حاول مرة أخرى.';
            if (result.json && result.json.errors) {
                msg = Object.values(result.json.errors).flat().join(' ');
            } else if (result.json && result.json.message) {
                msg = result.json.message;
            }
            alertBox.className = 'hc-alert hc-alert-error';
            alertBox.textContent = msg;
            alertBox.style.display = 'block';
        }).catch(function () {
            alertBox.className = 'hc-alert hc-alert-error';
            alertBox.textContent = 'حدث خطأ في الاتصال. حاول مرة أخرى.';
            alertBox.style.display = 'block';
        }).finally(function () {
            submit.disabled = false;
            submit.textContent = 'تسجيل الاشتراك الآن';
        });
    });
})();
</script>
@endpush
@endsection
