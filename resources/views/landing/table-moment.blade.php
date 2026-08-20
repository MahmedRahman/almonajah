@extends('layouts.landing')

@section('title', 'لحظة جميلة على مائدتك | المناجاة')

@section('meta')
<meta name="description" content="لحظة جميلة على مائدتك — تجربة تفاعلية لآداب الطعام والشراب من منصة المناجاة.">
<meta name="robots" content="index,follow">
@endsection

@section('content')
@push('styles')
<style>
:root {
    --tm-teal: #1a807f;
    --tm-teal-dark: #146664;
    --tm-brown: #6e5d52;
    --tm-cream: #f9f8f1;
    --tm-ring: rgba(26, 128, 127, 0.22);
}
.tm-page {
    min-height: 100vh;
    background: var(--tm-cream);
    color: var(--tm-brown);
    position: relative;
    overflow-x: hidden;
}
.tm-page::before {
    content: "";
    position: absolute;
    inset: 0 0 42%;
    background:
        repeating-linear-gradient(
            135deg,
            rgba(110, 93, 82, 0.025) 0 2px,
            transparent 2px 10px
        );
    pointer-events: none;
}
.tm-shell {
    max-width: 480px;
    margin: 0 auto;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 1;
}
.tm-top {
    text-align: center;
    padding: 1.5rem 1.25rem 0.75rem;
}
.tm-logo {
    width: 118px;
    height: auto;
    margin-bottom: 1.35rem;
}
.tm-title {
    font-size: clamp(1.65rem, 6vw, 2.15rem);
    font-weight: 700;
    color: var(--tm-brown);
    line-height: 1.45;
    margin: 0;
}
.tm-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.15rem 0.85rem;
    padding: 1.6rem 1.35rem 1rem;
}
.tm-icon-btn {
    appearance: none;
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    text-align: center;
    transition: transform .18s ease;
}
.tm-icon-btn:hover { transform: translateY(-3px); }
.tm-icon-btn:active { transform: scale(.97); }
.tm-icon-btn.active .tm-icon-ring {
    box-shadow: 0 0 0 4px rgba(26, 128, 127, 0.18);
    transform: scale(1.04);
}
.tm-icon-ring {
    width: 92px;
    height: 92px;
    margin: 0 auto 0.65rem;
    border-radius: 50%;
    border: 3px solid var(--tm-teal);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    transition: transform .18s ease, box-shadow .18s ease;
}
.tm-icon-inner {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--tm-teal);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.65rem;
}
.tm-icon-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--tm-brown);
    line-height: 1.35;
    min-height: 2.2rem;
}
.tm-hero {
    margin-top: auto;
    position: relative;
    min-height: 34vh;
}
.tm-hero img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: cover;
    object-position: center 72%;
}
.tm-hero-fade {
    position: absolute;
    inset: 0 0 auto;
    height: 90px;
    background: linear-gradient(180deg, var(--tm-cream), transparent);
    pointer-events: none;
}
.tm-badge {
    position: fixed;
    top: 0.75rem;
    left: 0.75rem;
    z-index: 20;
    background: rgba(255,255,255,.85);
    border: 1px solid var(--tm-ring);
    color: var(--tm-teal-dark);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.28rem 0.55rem;
    border-radius: 999px;
}
.tm-panel-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(30, 24, 20, 0.45);
    opacity: 0;
    visibility: hidden;
    transition: opacity .22s ease, visibility .22s ease;
    z-index: 30;
}
.tm-panel-backdrop.show {
    opacity: 1;
    visibility: visible;
}
.tm-panel {
    position: fixed;
    left: 50%;
    bottom: 0;
    transform: translate(-50%, 110%);
    width: min(480px, 100%);
    max-height: 78vh;
    overflow: auto;
    background: #fff;
    border-radius: 1.35rem 1.35rem 0 0;
    box-shadow: 0 -18px 50px rgba(0,0,0,.18);
    z-index: 40;
    transition: transform .28s cubic-bezier(.22, 1, .36, 1);
    padding: 1rem 1.15rem 1.5rem;
}
.tm-panel.show { transform: translate(-50%, 0); }
.tm-panel-handle {
    width: 42px;
    height: 4px;
    border-radius: 999px;
    background: #d6d3d1;
    margin: 0 auto 0.85rem;
}
.tm-panel-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}
.tm-panel-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--tm-teal);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}
.tm-panel-head h2 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--tm-brown);
}
.tm-panel-body {
    color: #57534e;
    line-height: 1.85;
    font-size: 0.96rem;
}
.tm-panel-body ul {
    margin: 0.75rem 0 0;
    padding: 0 1.1rem 0 0;
}
.tm-panel-body li { margin-bottom: 0.45rem; }
.tm-panel-quote {
    margin-top: 0.85rem;
    padding: 0.85rem 0.95rem;
    background: #f0fdfa;
    border-right: 3px solid var(--tm-teal);
    border-radius: 0.65rem;
    color: var(--tm-teal-dark);
    font-size: 0.92rem;
}
.tm-close {
    margin-top: 1rem;
    width: 100%;
    border: none;
    border-radius: 999px;
    background: var(--tm-teal);
    color: #fff;
    font-weight: 700;
    padding: 0.75rem 1rem;
    cursor: pointer;
}
@media (max-width: 360px) {
    .tm-icon-ring { width: 82px; height: 82px; }
    .tm-icon-inner { width: 64px; height: 64px; font-size: 1.45rem; }
    .tm-icon-label { font-size: 0.76rem; }
}
</style>
@endpush

<div class="tm-page">
    <span class="tm-badge">تجربة تفاعلية</span>

    <div class="tm-shell">
        <header class="tm-top">
            <a href="{{ url('/') }}">
                <img class="tm-logo" src="{{ asset('images/logo.png') }}" alt="المناجاة">
            </a>
            <h1 class="tm-title">لحظة جميلة<br>على مائدتك</h1>
        </header>

        <div class="tm-grid" id="iconGrid">
            <button type="button" class="tm-icon-btn" data-key="after">
                <div class="tm-icon-ring"><div class="tm-icon-inner"><i class="bi bi-emoji-smile"></i></div></div>
                <div class="tm-icon-label">بعد الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="blessing">
                <div class="tm-icon-ring"><div class="tm-icon-inner"><i class="bi bi-shield-check"></i></div></div>
                <div class="tm-icon-label">حفظ نعمتك</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="before">
                <div class="tm-icon-ring"><div class="tm-icon-inner"><i class="bi bi-hand-index-thumb"></i></div></div>
                <div class="tm-icon-label">قبل الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="menu">
                <div class="tm-icon-ring"><div class="tm-icon-inner"><i class="bi bi-journal-text"></i></div></div>
                <div class="tm-icon-label">قائمة الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="drink">
                <div class="tm-icon-ring"><div class="tm-icon-inner"><i class="bi bi-cup-straw"></i></div></div>
                <div class="tm-icon-label">الشراب</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="bismillah">
                <div class="tm-icon-ring"><div class="tm-icon-inner" style="font-size:1rem;font-weight:700;">بسم الله</div></div>
                <div class="tm-icon-label">إذا نسيت التسمية</div>
            </button>
        </div>

        <div class="tm-hero">
            <div class="tm-hero-fade"></div>
            <img src="{{ asset('images/table-moment-hero.png') }}" alt="مائدة طعام">
        </div>
    </div>
</div>

<div class="tm-panel-backdrop" id="panelBackdrop" aria-hidden="true"></div>
<section class="tm-panel" id="panel" aria-hidden="true" aria-labelledby="panelTitle">
    <div class="tm-panel-handle"></div>
    <div class="tm-panel-head">
        <div class="tm-panel-icon" id="panelIcon"><i class="bi bi-info-circle"></i></div>
        <h2 id="panelTitle">عنوان</h2>
    </div>
    <div class="tm-panel-body" id="panelBody"></div>
    <button type="button" class="tm-close" id="panelClose">إغلاق</button>
</section>

@push('scripts')
<script>
(function () {
    var topics = {
        before: {
            title: 'قبل الطعام',
            icon: 'bi-hand-index-thumb',
            html: '<p>ابدأ مائدتك بسكينة ونية طيبة:</p><ul><li>اغسل يديك ونظّف فمك إن أمكن.</li><li>قل <strong>بسم الله</strong> قبل الأكل.</li><li>كل بيمينك ما استطعت.</li><li>لا تبدأ قبل أن يُطعَم معك أو يُؤذَن لك.</li><li>اجلس على مائدتك بتواضع وشكر.</li></ul><div class="tm-panel-quote">«إذا أكل أحدكم فليذكر اسم الله. فإن نسي أن يذكر اسم الله في أوله فليقل: بسم الله أوله وآخره.»</div>'
        },
        blessing: {
            title: 'حفظ نعمتك',
            icon: 'bi-shield-check',
            html: '<p>النعمة أمانة، وحفظها من شكر الله:</p><ul><li>خذ ما يكفيك ولا تُسرِف.</li><li>لا تُهدر الطعام ولا تُكثر ما لا تأكله.</li><li>اشكر الله على ما رزقك.</li><li>إن زاد عن حاجتك فتصدّق أو احفظه بإحسان.</li></ul><div class="tm-panel-quote">«ما ملأ آدمي وعاءً شرًّا من بطن.»</div>'
        },
        after: {
            title: 'بعد الطعام',
            icon: 'bi-emoji-smile',
            html: '<p>ختم الطعام بذكر وشكر:</p><ul><li>قل: <strong>الحمد لله</strong> الذي أطعمني هذا ورزقنيه من غير حول مني ولا قوة.</li><li>امسح فمك ويديك إن أمكن.</li><li>ادعُ لصاحب الطعام بالبركة.</li><li>قُم عن المائدة وقد شكرت ولم تُسرِف.</li></ul>'
        },
        menu: {
            title: 'قائمة الطعام',
            icon: 'bi-journal-text',
            html: '<p>آداب المائدة في الاجتماع:</p><ul><li>كل مما يليك ولا تُطِل النظر في طعام غيرك.</li><li>لا تُنتقِ الطعام وتترك ما بجانبك.</li><li>لا تُشير بالسكين أو تُزعج جليسك.</li><li>تناول الطعام بأدب وهدوء.</li><li>إن دُعيت فلا ترفض إلا عذرًا، وإن حضرت فأكرم المضيف.</li></ul>'
        },
        drink: {
            title: 'الشراب',
            icon: 'bi-cup-straw',
            html: '<p>آداب الشرب من السنة:</p><ul><li>قل <strong>بسم الله</strong> قبل الشرب.</li><li>اشرب جالسًا إن تيسّر.</li><li>لا تنفث في الإناء.</li><li>اشرب على ثلاث مرات ولا تشرب دفعة واحدة.</li><li>قل بعده: <strong>الحمد لله</strong>.</li></ul>'
        },
        bismillah: {
            title: 'إذا نسيت التسمية',
            icon: 'bi-bookmark-heart',
            html: '<p>إن بدأت الأكل ونسيت أن تقول بسم الله:</p><ul><li>قل: <strong>بسم الله في أوله وآخره</strong>.</li><li>لا تُترك الذكر لأنك نسيت في البداية.</li><li>اجعلها عادة في كل لقمة وكل جلسة.</li></ul><div class="tm-panel-quote">«بسم الله أوله وآخره» — رواه أبو داود وغيره.</div>'
        }
    };

    var backdrop = document.getElementById('panelBackdrop');
    var panel = document.getElementById('panel');
    var panelTitle = document.getElementById('panelTitle');
    var panelBody = document.getElementById('panelBody');
    var panelIcon = document.getElementById('panelIcon');
    var buttons = document.querySelectorAll('.tm-icon-btn');

    function openPanel(key) {
        var t = topics[key];
        if (!t) return;
        panelTitle.textContent = t.title;
        panelBody.innerHTML = t.html;
        panelIcon.innerHTML = '<i class="bi ' + t.icon + '"></i>';
        buttons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-key') === key);
        });
        backdrop.classList.add('show');
        panel.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        panel.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        backdrop.classList.remove('show');
        panel.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        panel.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        buttons.forEach(function (btn) { btn.classList.remove('active'); });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            openPanel(btn.getAttribute('data-key'));
        });
    });
    document.getElementById('panelClose').addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });
})();
</script>
@endpush
@endsection
