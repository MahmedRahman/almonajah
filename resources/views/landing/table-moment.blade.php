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
    color: var(--tm-brown);
    position: relative;
    overflow-x: hidden;
    background:
        linear-gradient(180deg, rgba(249, 248, 241, 0.72) 0%, rgba(249, 248, 241, 0.35) 38%, rgba(249, 248, 241, 0.08) 62%, transparent 100%),
        url("{{ asset('images/table-moment-bg.png') }}") center bottom / cover no-repeat;
    background-color: #e8f5f3;
}
.tm-page::before {
    display: none;
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
    padding: 1.6rem 1.35rem 2.5rem;
    flex: 1;
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
    text-shadow: 0 1px 8px rgba(255, 255, 255, 0.85);
}
.tm-spacer {
    min-height: 38vh;
    flex-shrink: 0;
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
.tm-lang-pick {
    display: grid;
    gap: 0.75rem;
    margin-top: 0.35rem;
}
.tm-lang-btn {
    appearance: none;
    border: 1px solid rgba(26, 128, 127, 0.25);
    background: linear-gradient(180deg, #f0fdfa, #fff);
    color: var(--tm-teal-dark);
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.tm-lang-btn:hover {
    transform: translateY(-2px);
    border-color: var(--tm-teal);
    box-shadow: 0 10px 24px rgba(26, 128, 127, 0.14);
}
.tm-lang-btn span {
    font-size: 0.82rem;
    font-weight: 600;
    color: #78716c;
}
.tm-menu {
    position: fixed;
    inset: 0;
    z-index: 50;
    background: #0f172a;
    display: none;
    flex-direction: column;
}
.tm-menu.show { display: flex; }
.tm-menu-bar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 0.9rem;
    background: rgba(15, 23, 42, 0.92);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #fff;
}
.tm-menu-bar h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}
.tm-menu-actions {
    display: flex;
    gap: 0.45rem;
}
.tm-menu-actions button {
    appearance: none;
    border: 1px solid rgba(255,255,255,0.18);
    background: rgba(255,255,255,0.08);
    color: #fff;
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
}
.tm-menu-actions button:hover { background: rgba(255,255,255,0.16); }
.tm-menu-scroll {
    flex: 1;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0.85rem 0.75rem 1.5rem;
    scroll-snap-type: y proximity;
}
.tm-menu-page {
    max-width: 720px;
    margin: 0 auto 0.85rem;
    border-radius: 1rem;
    overflow: hidden;
    background: #111;
    box-shadow: 0 12px 36px rgba(0,0,0,0.35);
    scroll-snap-align: start;
}
.tm-menu-page img {
    display: block;
    width: 100%;
    height: auto;
}
.tm-menu-hint {
    text-align: center;
    color: rgba(255,255,255,0.55);
    font-size: 0.8rem;
    padding: 0.25rem 0 0.75rem;
}
@media (max-width: 360px) {
    .tm-icon-ring { width: 82px; height: 82px; }
    .tm-icon-inner { width: 64px; height: 64px; font-size: 1.45rem; }
    .tm-icon-label { font-size: 0.76rem; }
}
</style>
@endpush

@php
    $menuAr = collect(glob(public_path('images/menu/ar/page-*.jpg')) ?: [])
        ->sort()
        ->values()
        ->map(fn ($p) => asset('images/menu/ar/' . basename($p)))
        ->all();
    $menuEn = collect(glob(public_path('images/menu/en/page-*.jpg')) ?: [])
        ->sort()
        ->values()
        ->map(fn ($p) => asset('images/menu/en/' . basename($p)))
        ->all();
@endphp

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

        <div class="tm-spacer" aria-hidden="true"></div>
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

<div class="tm-menu" id="menuViewer" aria-hidden="true">
    <div class="tm-menu-bar">
        <h2 id="menuViewerTitle">قائمة الطعام</h2>
        <div class="tm-menu-actions">
            <button type="button" id="menuSwitchLang">تبديل اللغة</button>
            <button type="button" id="menuCloseBtn">إغلاق</button>
        </div>
    </div>
    <div class="tm-menu-hint">مرّر لتصفّح صفحات المنيو</div>
    <div class="tm-menu-scroll" id="menuScroll"></div>
</div>

@push('scripts')
<script>
(function () {
    var menuPages = {
        ar: @json($menuAr),
        en: @json($menuEn)
    };
    var currentMenuLang = 'ar';

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
            html: ''
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
    var panelClose = document.getElementById('panelClose');
    var buttons = document.querySelectorAll('.tm-icon-btn');
    var menuViewer = document.getElementById('menuViewer');
    var menuScroll = document.getElementById('menuScroll');
    var menuViewerTitle = document.getElementById('menuViewerTitle');

    function menuChooserHtml() {
        return '' +
            '<p>اختَر لغة عرض المنيو:</p>' +
            '<div class="tm-lang-pick">' +
            '  <button type="button" class="tm-lang-btn" data-menu-lang="ar">' +
            '    <strong>العربية</strong><span>' + (menuPages.ar.length || 0) + ' صفحة</span>' +
            '  </button>' +
            '  <button type="button" class="tm-lang-btn" data-menu-lang="en">' +
            '    <strong>English</strong><span>' + (menuPages.en.length || 0) + ' pages</span>' +
            '  </button>' +
            '</div>';
    }

    function openPanel(key) {
        var t = topics[key];
        if (!t) return;
        panelTitle.textContent = t.title;
        panelBody.innerHTML = key === 'menu' ? menuChooserHtml() : t.html;
        panelIcon.innerHTML = '<i class="bi ' + t.icon + '"></i>';
        panelClose.style.display = key === 'menu' ? 'none' : '';
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
        panelClose.style.display = '';
        if (!menuViewer.classList.contains('show')) {
            document.body.style.overflow = '';
        }
        buttons.forEach(function (btn) { btn.classList.remove('active'); });
    }

    function openMenu(lang) {
        var pages = menuPages[lang] || [];
        if (!pages.length) {
            panelBody.innerHTML = '<p>لا توجد صور للمنيو حاليًا.</p>';
            panelClose.style.display = '';
            return;
        }
        currentMenuLang = lang;
        menuViewerTitle.textContent = lang === 'ar' ? 'قائمة الطعام — العربية' : 'Menu — English';
        menuScroll.innerHTML = pages.map(function (src, i) {
            return '<div class="tm-menu-page"><img src="' + src + '" alt="Menu page ' + (i + 1) + '" loading="' + (i < 2 ? 'eager' : 'lazy') + '"></div>';
        }).join('');
        closePanel();
        menuViewer.classList.add('show');
        menuViewer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        menuScroll.scrollTop = 0;
    }

    function closeMenu() {
        menuViewer.classList.remove('show');
        menuViewer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        buttons.forEach(function (btn) { btn.classList.remove('active'); });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            openPanel(btn.getAttribute('data-key'));
        });
    });

    panelBody.addEventListener('click', function (e) {
        var langBtn = e.target.closest('[data-menu-lang]');
        if (!langBtn) return;
        openMenu(langBtn.getAttribute('data-menu-lang'));
    });

    panelClose.addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);
    document.getElementById('menuCloseBtn').addEventListener('click', closeMenu);
    document.getElementById('menuSwitchLang').addEventListener('click', function () {
        openMenu(currentMenuLang === 'ar' ? 'en' : 'ar');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (menuViewer.classList.contains('show')) closeMenu();
        else closePanel();
    });
})();
</script>
@endpush
@endsection
