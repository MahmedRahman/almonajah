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
    --tm-bg-image: url("{{ asset('images/table-moment-bg-mobile.png') }}");
    background:
        linear-gradient(180deg, rgba(249, 248, 241, 0.72) 0%, rgba(249, 248, 241, 0.35) 38%, rgba(249, 248, 241, 0.08) 62%, transparent 100%),
        var(--tm-bg-image) center bottom / cover no-repeat;
    background-color: #e8f5f3;
}
@media (min-width: 768px) {
    .tm-page {
        --tm-bg-image: url("{{ asset('images/table-moment-bg-desktop.png') }}");
    }
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
    direction: ltr;
}
.tm-icon-btn {
    appearance: none;
    border: none;
    background: transparent;
    padding: 0;
    cursor: pointer;
    text-align: center;
    transition: transform .18s ease;
    direction: rtl;
}
.tm-icon-btn:hover { transform: translateY(-3px); }
.tm-icon-btn:active { transform: scale(.97); }
.tm-icon-btn.active .tm-icon-img {
    box-shadow: 0 0 0 4px rgba(26, 128, 127, 0.22);
    transform: scale(1.05);
}
.tm-icon-img {
    width: 92px;
    height: 92px;
    margin: 0 auto 0.65rem;
    border-radius: 50%;
    display: block;
    object-fit: cover;
    background: transparent;
    transition: transform .18s ease, box-shadow .18s ease;
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
.tm-site-footer {
    position: fixed;
    left: 50%;
    bottom: 1.1rem;
    transform: translateX(-50%);
    z-index: 15;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    text-decoration: none;
    color: var(--tm-brown);
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.tm-site-footer img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: block;
    flex-shrink: 0;
}
.tm-site-footer span {
    font-size: 0.92rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-shadow: 0 1px 6px rgba(255, 255, 255, 0.85);
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
.tm-lang-wrap {
    position: fixed;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 25;
}
.tm-lang-toggle {
    appearance: none;
    border: 1px solid var(--tm-ring);
    background: rgba(255,255,255,.92);
    color: var(--tm-teal-dark);
    width: 42px;
    height: 42px;
    border-radius: 50%;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: background .15s ease, transform .15s ease, box-shadow .15s ease;
    box-shadow: 0 6px 18px rgba(20, 102, 100, 0.12);
}
.tm-lang-toggle:hover {
    background: #fff;
    transform: translateY(-1px);
}
.tm-lang-menu {
    position: absolute;
    top: calc(100% + 0.45rem);
    right: 0;
    min-width: 190px;
    background: #fff;
    border: 1px solid rgba(26, 128, 127, 0.18);
    border-radius: 0.9rem;
    box-shadow: 0 16px 40px rgba(0,0,0,.14);
    padding: 0.35rem;
    display: none;
    max-height: 70vh;
    overflow: auto;
}
.tm-lang-menu.show { display: block; }
.tm-lang-option {
    appearance: none;
    width: 100%;
    border: none;
    background: transparent;
    text-align: start;
    padding: 0.65rem 0.75rem;
    border-radius: 0.65rem;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--tm-brown);
}
.tm-lang-option:hover,
.tm-lang-option.active {
    background: #f0fdfa;
    color: var(--tm-teal-dark);
}
html[dir="rtl"] .tm-panel-body ul {
    padding: 0 1.1rem 0 0;
}
html[dir="ltr"] .tm-panel-body ul {
    padding: 0 0 0 1.1rem;
}
html[dir="ltr"] .tm-panel-quote {
    border-right: none;
    border-left: 3px solid var(--tm-teal);
}
html[dir="rtl"] .tm-panel-quote {
    border-left: none;
    border-right: 3px solid var(--tm-teal);
}
html[dir="ltr"] .tm-icon-btn {
    direction: ltr;
}
html[dir="rtl"] .tm-icon-btn {
    direction: rtl;
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
    background: transparent;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    overflow: hidden;
}
.tm-panel-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
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
    .tm-icon-img { width: 82px; height: 82px; }
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
    <span class="tm-badge" id="tmBadge">تجربة تفاعلية</span>
    <div class="tm-lang-wrap">
        <button type="button" class="tm-lang-toggle" id="langToggle" aria-label="Languages" aria-expanded="false" aria-haspopup="listbox">
            <i class="bi bi-globe2" aria-hidden="true"></i>
        </button>
        <div class="tm-lang-menu" id="langMenu" role="listbox" aria-label="Languages"></div>
    </div>

    <div class="tm-shell">
        <header class="tm-top">
            <a href="{{ url('/') }}">
                <img class="tm-logo" src="{{ asset('images/logo.png') }}" alt="المناجاة" id="tmLogo">
            </a>
            <h1 class="tm-title" id="tmTitle">لحظة جميلة<br>على مائدتك</h1>
        </header>

        <div class="tm-grid" id="iconGrid">
            {{-- ترتيب مطابق للتصميم: صف علوي ثم سفلي من اليسار لليمين --}}
            <button type="button" class="tm-icon-btn" data-key="after">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/after.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="after">بعد الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="menu">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/menu.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="menu">قائمة الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="before">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/before.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="before">قبل الطعام</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="blessing">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/blessing.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="blessing">حفظ النعمة</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="drink">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/drink.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="drink">الشراب</div>
            </button>
            <button type="button" class="tm-icon-btn" data-key="bismillah">
                <img class="tm-icon-img" src="{{ asset('images/table-moment-icons/bismillah.png') }}" alt="" width="92" height="92" decoding="async">
                <div class="tm-icon-label" data-key="bismillah">إذا نسيت التسمية</div>
            </button>
        </div>

        <div class="tm-spacer" aria-hidden="true"></div>
    </div>

    <a href="https://almonajah.com" class="tm-site-footer" target="_blank" rel="noopener noreferrer">
        <img src="{{ asset('images/site-icon.png') }}" alt="" width="28" height="28" decoding="async">
        <span>www.almonajah.com</span>
    </a>
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
    <div class="tm-menu-hint" id="menuHint">مرّر لتصفّح صفحات المنيو</div>
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
    var currentLang = 'ar';
    var activePanelKey = null;
    var iconBase = @json(asset('images/table-moment-icons'));

    var languages = [
        { code: 'ar', label: 'العربية', dir: 'rtl' },
        { code: 'en', label: 'English', dir: 'ltr' },
        { code: 'fr', label: 'Français', dir: 'ltr' },
        { code: 'ur', label: 'اردو', dir: 'rtl' },
        { code: 'id', label: 'Bahasa Indonesia', dir: 'ltr' },
        { code: 'ha', label: 'Hausa', dir: 'ltr' },
        { code: 'es', label: 'Latina', dir: 'ltr' }
    ];

    var copy = {
        ar: {
            pageTitle: 'لحظة جميلة على مائدتك | المناجاة',
            metaDescription: 'لحظة جميلة على مائدتك — تجربة تفاعلية لآداب الطعام والشراب من منصة المناجاة.',
            badge: 'تجربة تفاعلية',
            title: 'لحظة جميلة<br>على مائدتك',
            logoAlt: 'المناجاة',
            langToggleAria: 'اختيار اللغة',
            close: 'إغلاق',
            menuTitle: 'قائمة الطعام',
            menuSwitchLang: 'تبديل اللغة',
            menuHint: 'مرّر لتصفّح صفحات المنيو',
            menuChoose: 'اختَر لغة عرض المنيو:',
            menuNoImages: 'لا توجد صور للمنيو حاليًا.',
            menuTitleAr: 'قائمة الطعام — العربية',
            menuTitleEn: 'Menu — English',
            pageWord: 'صفحة',
            pagesWord: 'صفحات',
            labels: { after: 'بعد الطعام', blessing: 'حفظ النعمة', before: 'قبل الطعام', menu: 'قائمة الطعام', drink: 'الشراب', bismillah: 'إذا نسيت التسمية' },
            topics: {
                before: { title: 'قبل الطعام', html: '<p>ابدأ مائدتك بسكينة ونية طيبة:</p><ul><li>اغسل يديك ونظّف فمك إن أمكن.</li><li>قل <strong>بسم الله</strong> قبل الأكل.</li><li>كل بيمينك ما استطعت.</li><li>لا تبدأ قبل أن يُطعَم معك أو يُؤذَن لك.</li><li>اجلس على مائدتك بتواضع وشكر.</li></ul><div class="tm-panel-quote">«إذا أكل أحدكم فليذكر اسم الله. فإن نسي أن يذكر اسم الله في أوله فليقل: بسم الله أوله وآخره.»</div>' },
                blessing: { title: 'حفظ النعمة', html: '<p>النعمة أمانة، وحفظها من شكر الله:</p><ul><li>خذ ما يكفيك ولا تُسرِف.</li><li>لا تُهدر الطعام ولا تُكثر ما لا تأكله.</li><li>اشكر الله على ما رزقك.</li><li>إن زاد عن حاجتك فتصدّق أو احفظه بإحسان.</li></ul><div class="tm-panel-quote">«ما ملأ آدمي وعاءً شرًّا من بطن.»</div>' },
                after: { title: 'بعد الطعام', html: '<p>ختم الطعام بذكر وشكر:</p><ul><li>قل: <strong>الحمد لله</strong> الذي أطعمني هذا ورزقنيه من غير حول مني ولا قوة.</li><li>امسح فمك ويديك إن أمكن.</li><li>ادعُ لصاحب الطعام بالبركة.</li><li>قُم عن المائدة وقد شكرت ولم تُسرِف.</li></ul>' },
                menu: { title: 'قائمة الطعام', html: '' },
                drink: { title: 'الشراب', html: '<p>آداب الشرب من السنة:</p><ul><li>قل <strong>بسم الله</strong> قبل الشرب.</li><li>اشرب جالسًا إن تيسّر.</li><li>لا تنفث في الإناء.</li><li>اشرب على ثلاث مرات ولا تشرب دفعة واحدة.</li><li>قل بعده: <strong>الحمد لله</strong>.</li></ul>' },
                bismillah: { title: 'إذا نسيت التسمية', html: '<p>إن بدأت الأكل ونسيت أن تقول بسم الله:</p><ul><li>قل: <strong>بسم الله في أوله وآخره</strong>.</li><li>لا تُترك الذكر لأنك نسيت في البداية.</li><li>اجعلها عادة في كل لقمة وكل جلسة.</li></ul><div class="tm-panel-quote">«بسم الله أوله وآخره» — رواه أبو داود وغيره.</div>' }
            }
        },
        en: {
            pageTitle: 'A Beautiful Moment at Your Table | Al-Monajah',
            metaDescription: 'A beautiful moment at your table — an interactive guide to food and drink etiquette from Al-Monajah.',
            badge: 'Interactive experience',
            title: 'A Beautiful Moment<br>at Your Table',
            logoAlt: 'Al-Monajah',
            langToggleAria: 'Choose language',
            close: 'Close',
            menuTitle: 'Food Menu',
            menuSwitchLang: 'Switch language',
            menuHint: 'Scroll to browse menu pages',
            menuChoose: 'Choose menu language:',
            menuNoImages: 'No menu images are available right now.',
            menuTitleAr: 'Food Menu — Arabic',
            menuTitleEn: 'Menu — English',
            pageWord: 'page',
            pagesWord: 'pages',
            labels: { after: 'After the Meal', blessing: 'Preserving the Blessing', before: 'Before the Meal', menu: 'Food Menu', drink: 'Drinks', bismillah: 'If You Forgot to Say Bismillah' },
            topics: {
                before: { title: 'Before the Meal', html: '<p>Begin your meal with calmness and a good intention:</p><ul><li>Wash your hands and clean your mouth if possible.</li><li>Say <strong>Bismillah</strong> before eating.</li><li>Eat with your right hand whenever you can.</li><li>Do not start before others are served or you are given permission.</li><li>Sit at your table with humility and gratitude.</li></ul><div class="tm-panel-quote">"When one of you eats, let him mention the name of Allah. If he forgets at the beginning, let him say: Bismillah at its beginning and its end."</div>' },
                blessing: { title: 'Preserving the Blessing', html: '<p>A blessing is a trust, and preserving it is gratitude to Allah:</p><ul><li>Take only what you need and do not be wasteful.</li><li>Do not waste food or take more than you will eat.</li><li>Thank Allah for what He has provided.</li><li>If there is more than you need, give in charity or preserve it well.</li></ul><div class="tm-panel-quote">"No human being fills a vessel worse than his stomach."</div>' },
                after: { title: 'After the Meal', html: '<p>Conclude your meal with remembrance and gratitude:</p><ul><li>Say: <strong>Alhamdulillah</strong> who fed me this and provided it for me without any power or strength from me.</li><li>Wipe your mouth and hands if possible.</li><li>Pray for blessings upon the host.</li><li>Leave the table having thanked Allah and without being wasteful.</li></ul>' },
                menu: { title: 'Food Menu', html: '' },
                drink: { title: 'Drinks', html: '<p>Etiquette of drinking from the Sunnah:</p><ul><li>Say <strong>Bismillah</strong> before drinking.</li><li>Drink while seated if possible.</li><li>Do not blow into the vessel.</li><li>Drink in three sips, not all at once.</li><li>Say afterward: <strong>Alhamdulillah</strong>.</li></ul>' },
                bismillah: { title: 'If You Forgot to Say Bismillah', html: '<p>If you started eating and forgot to say Bismillah:</p><ul><li>Say: <strong>Bismillah at its beginning and its end</strong>.</li><li>Do not leave remembrance just because you forgot at the start.</li><li>Make it a habit in every bite and every gathering.</li></ul><div class="tm-panel-quote">"Bismillah at its beginning and its end" — narrated by Abu Dawud and others.</div>' }
            }
        },
        fr: {
            pageTitle: 'Un beau moment à votre table | Al-Monajah',
            metaDescription: 'Un beau moment à votre table — un guide interactif des règles de la nourriture et de la boisson selon Al-Monajah.',
            badge: 'Expérience interactive',
            title: 'Un beau moment<br>à votre table',
            logoAlt: 'Al-Monajah',
            langToggleAria: 'Choisir la langue',
            close: 'Fermer',
            menuTitle: 'Menu',
            menuSwitchLang: 'Changer de langue',
            menuHint: 'Faites défiler pour parcourir le menu',
            menuChoose: 'Choisissez la langue du menu :',
            menuNoImages: 'Aucune image de menu n’est disponible pour le moment.',
            menuTitleAr: 'Menu — Arabe',
            menuTitleEn: 'Menu — English',
            pageWord: 'page',
            pagesWord: 'pages',
            labels: { after: 'Après le repas', blessing: 'Préserver le bienfait', before: 'Avant le repas', menu: 'Menu', drink: 'Boisson', bismillah: 'Si vous avez oublié Bismillah' },
            topics: {
                before: { title: 'Avant le repas', html: '<p>Commencez votre repas avec calme et une bonne intention :</p><ul><li>Lavez-vous les mains et nettoyez votre bouche si possible.</li><li>Dites <strong>Bismillah</strong> avant de manger.</li><li>Mangez de la main droite autant que possible.</li><li>Ne commencez pas avant que les autres soient servis ou que l’on vous y autorise.</li><li>Asseyez-vous à table avec humilité et gratitude.</li></ul><div class="tm-panel-quote">« Lorsque l’un d’entre vous mange, qu’il mentionne le nom d’Allah. S’il l’oublie au début, qu’il dise : Bismillah au début et à la fin. »</div>' },
                blessing: { title: 'Préserver le bienfait', html: '<p>Le bienfait est un dépôt, et le préserver est une forme de gratitude envers Allah :</p><ul><li>Prenez seulement ce dont vous avez besoin et ne gaspillez pas.</li><li>Ne jetez pas la nourriture et n’en prenez pas plus que vous ne mangerez.</li><li>Remerciez Allah pour ce qu’Il vous a accordé.</li><li>S’il en reste plus que nécessaire, donnez en aumône ou conservez-le correctement.</li></ul><div class="tm-panel-quote">« Aucun être humain ne remplit un récipient pire que son estomac. »</div>' },
                after: { title: 'Après le repas', html: '<p>Terminez le repas par le rappel et la gratitude :</p><ul><li>Dites : <strong>Alhamdulillah</strong> qui m’a nourri de ceci et me l’a accordé sans force ni puissance de ma part.</li><li>Essuyez votre bouche et vos mains si possible.</li><li>Invoquez la bénédiction pour l’hôte.</li><li>Quittez la table en ayant remercié Allah, sans gaspillage.</li></ul>' },
                menu: { title: 'Menu', html: '' },
                drink: { title: 'Boisson', html: '<p>Les règles de la boisson selon la Sunna :</p><ul><li>Dites <strong>Bismillah</strong> avant de boire.</li><li>Buvez assis si possible.</li><li>Ne soufflez pas dans le récipient.</li><li>Buvez en trois gorgées, pas d’un seul trait.</li><li>Dites ensuite : <strong>Alhamdulillah</strong>.</li></ul>' },
                bismillah: { title: 'Si vous avez oublié Bismillah', html: '<p>Si vous avez commencé à manger sans dire Bismillah :</p><ul><li>Dites : <strong>Bismillah au début et à la fin</strong>.</li><li>N’abandonnez pas le rappel parce que vous l’avez oublié au début.</li><li>Faites-en une habitude à chaque bouchée et à chaque repas.</li></ul><div class="tm-panel-quote">« Bismillah au début et à la fin » — rapporté par Abou Dawoud et d’autres.</div>' }
            }
        },
        ur: {
            pageTitle: 'آپ کی میز پر ایک خوبصورت لمحہ | المناجاة',
            metaDescription: 'آپ کی میز پر ایک خوبصورت لمحہ — المناجاة کی جانب سے کھانے اور پینے کے آداب کی ایک تعاملی رہنمائی۔',
            badge: 'تعاملی تجربہ',
            title: 'آپ کی میز پر<br>ایک خوبصورت لمحہ',
            logoAlt: 'المناجاة',
            langToggleAria: 'زبان منتخب کریں',
            close: 'بند کریں',
            menuTitle: 'مینو',
            menuSwitchLang: 'زبان تبدیل کریں',
            menuHint: 'مینو کے صفحات دیکھنے کے لیے سکرول کریں',
            menuChoose: 'مینو کی زبان منتخب کریں:',
            menuNoImages: 'اس وقت مینو کی تصاویر دستیاب نہیں ہیں۔',
            menuTitleAr: 'مینو — عربی',
            menuTitleEn: 'Menu — English',
            pageWord: 'صفحہ',
            pagesWord: 'صفحات',
            labels: { after: 'کھانے کے بعد', blessing: 'نعمت کی حفاظت', before: 'کھانے سے پہلے', menu: 'مینو', drink: 'مشروب', bismillah: 'اگر بسم اللہ بھول جائیں' },
            topics: {
                before: { title: 'کھانے سے پہلے', html: '<p>اپنے کھانے کا آغاز سکون اور اچھی نیت سے کریں:</p><ul><li>ہاتھ دھوئیں اور ممکن ہو تو منہ صاف کریں۔</li><li>کھانے سے پہلے <strong>بسم اللہ</strong> کہیں۔</li><li>جتنا ممکن ہو دائیں ہاتھ سے کھائیں۔</li><li>جب تک دوسروں کو کھانا نہ ملے یا اجازت نہ دی جائے شروع نہ کریں۔</li><li>میز پر عاجزی اور شکر کے ساتھ بیٹھیں۔</li></ul><div class="tm-panel-quote">«جب تم میں سے کوئی کھائے تو اللہ کا نام لے، اور اگر شروع میں بھول جائے تو کہے: بسم اللہ اولہ وآخرہ۔»</div>' },
                blessing: { title: 'نعمت کی حفاظت', html: '<p>نعمت امانت ہے، اور اس کی حفاظت اللہ کا شکر ہے:</p><ul><li>صرف اپنی ضرورت کے مطابق لیں اور اسراف نہ کریں۔</li><li>کھانا ضائع نہ کریں اور جتنا کھا سکیں اس سے زیادہ نہ لیں۔</li><li>اللہ کا شکر ادا کریں جو کچھ اس نے عطا کیا۔</li><li>اگر ضرورت سے زیادہ ہو تو صدقہ کریں یا اچھے طریقے سے محفوظ رکھیں۔</li></ul><div class="tm-panel-quote">«انسان نے پیٹ سے بدتر کوئی برتن نہیں بھرا۔»</div>' },
                after: { title: 'کھانے کے بعد', html: '<p>کھانے کا اختتام ذکر اور شکر کے ساتھ کریں:</p><ul><li>کہیں: <strong>الحمد للہ</strong> جس نے مجھے یہ کھلایا اور میری طاقت کے بغیر مجھے عطا کیا۔</li><li>ممکن ہو تو منہ اور ہاتھ صاف کریں۔</li><li>میزبان کے لیے برکت کی دعا کریں۔</li><li>شکر ادا کر کے اور اسراف کے بغیر میز سے اٹھیں۔</li></ul>' },
                menu: { title: 'مینو', html: '' },
                drink: { title: 'مشروب', html: '<p>سنت کے مطابق پینے کے آداب:</p><ul><li>پینے سے پہلے <strong>بسم اللہ</strong> کہیں۔</li><li>ممکن ہو تو بیٹھ کر پئیں۔</li><li>برتن میں نہ پھونکیں۔</li><li>تین سانسوں میں پئیں، ایک ہی بار میں نہ پئیں۔</li><li>اس کے بعد <strong>الحمد للہ</strong> کہیں۔</li></ul>' },
                bismillah: { title: 'اگر بسم اللہ بھول جائیں', html: '<p>اگر کھانا شروع کر دیا اور بسم اللہ کہنا بھول گئے:</p><ul><li>کہیں: <strong>بسم اللہ فی اولہ وآخرہ</strong>۔</li><li>صرف شروع میں بھولنے کی وجہ سے ذکر نہ چھوڑیں۔</li><li>ہر لقمے اور ہر نشست میں اسے عادت بنائیں۔</li></ul><div class="tm-panel-quote">«بسم اللہ اولہ وآخرہ» — ابو داؤد وغیرہ نے روایت کیا۔</div>' }
            }
        },
        id: {
            pageTitle: 'Momen Indah di Meja Anda | Al-Monajah',
            metaDescription: 'Momen indah di meja Anda — panduan interaktif adab makan dan minum dari Al-Monajah.',
            badge: 'Pengalaman interaktif',
            title: 'Momen Indah<br>di Meja Anda',
            logoAlt: 'Al-Monajah',
            langToggleAria: 'Pilih bahasa',
            close: 'Tutup',
            menuTitle: 'Menu Makanan',
            menuSwitchLang: 'Ganti bahasa',
            menuHint: 'Gulir untuk melihat halaman menu',
            menuChoose: 'Pilih bahasa menu:',
            menuNoImages: 'Tidak ada gambar menu saat ini.',
            menuTitleAr: 'Menu — Arab',
            menuTitleEn: 'Menu — English',
            pageWord: 'halaman',
            pagesWord: 'halaman',
            labels: { after: 'Setelah Makan', blessing: 'Menjaga Nikmat', before: 'Sebelum Makan', menu: 'Menu Makanan', drink: 'Minuman', bismillah: 'Jika Lupa Mengucap Bismillah' },
            topics: {
                before: { title: 'Sebelum Makan', html: '<p>Mulailah makan dengan tenang dan niat yang baik:</p><ul><li>Cucilah tangan dan bersihkan mulut jika memungkinkan.</li><li>Ucapkan <strong>Bismillah</strong> sebelum makan.</li><li>Makanlah dengan tangan kanan sejauh mungkin.</li><li>Jangan mulai sebelum orang lain dilayani atau Anda diizinkan.</li><li>Duduklah di meja dengan rendah hati dan bersyukur.</li></ul><div class="tm-panel-quote">“Jika salah seorang dari kalian makan, hendaklah menyebut nama Allah. Jika lupa di awal, hendaklah mengucapkan: Bismillah di awal dan di akhirnya.”</div>' },
                blessing: { title: 'Menjaga Nikmat', html: '<p>Nikmat adalah amanah, dan menjaganya adalah bentuk syukur kepada Allah:</p><ul><li>Ambil hanya yang Anda butuhkan dan jangan berlebih-lebihan.</li><li>Jangan sia-siakan makanan atau mengambil lebih dari yang akan dimakan.</li><li>Bersyukurlah kepada Allah atas apa yang Dia berikan.</li><li>Jika ada lebih dari kebutuhan, bersedekahlah atau simpan dengan baik.</li></ul><div class="tm-panel-quote">“Tidak ada wadah yang lebih buruk dipenuhi manusia daripada perutnya.”</div>' },
                after: { title: 'Setelah Makan', html: '<p>Akhiri makan dengan zikir dan syukur:</p><ul><li>Ucapkan: <strong>Alhamdulillah</strong> yang telah memberiku makanan ini dan menganugerahkannya tanpa daya dan kekuatan dariku.</li><li>Usap mulut dan tangan jika memungkinkan.</li><li>Doakan keberkahan bagi tuan rumah.</li><li>Tinggalkan meja dengan bersyukur dan tanpa berlebih-lebihan.</li></ul>' },
                menu: { title: 'Menu Makanan', html: '' },
                drink: { title: 'Minuman', html: '<p>Adab minum menurut Sunnah:</p><ul><li>Ucapkan <strong>Bismillah</strong> sebelum minum.</li><li>Minumlah sambil duduk jika memungkinkan.</li><li>Jangan meniup ke dalam wadah.</li><li>Minum dalam tiga tegukan, bukan sekaligus.</li><li>Setelahnya ucapkan: <strong>Alhamdulillah</strong>.</li></ul>' },
                bismillah: { title: 'Jika Lupa Mengucap Bismillah', html: '<p>Jika Anda sudah mulai makan dan lupa mengucapkan Bismillah:</p><ul><li>Ucapkan: <strong>Bismillah di awal dan di akhirnya</strong>.</li><li>Jangan tinggalkan zikir hanya karena lupa di awal.</li><li>Jadikan itu kebiasaan di setiap suapan dan setiap pertemuan.</li></ul><div class="tm-panel-quote">“Bismillah di awal dan di akhirnya” — diriwayatkan oleh Abu Dawud dan lainnya.</div>' }
            }
        },
        ha: {
            pageTitle: 'Kyakkyawan Lokaci a Tebur ɗinku | Al-Monajah',
            metaDescription: 'Kyakkyawan lokaci a tebur ɗinku — jagorar adabin cin abinci da sha daga Al-Monajah.',
            badge: 'Gogewa mai mu’amala',
            title: 'Kyakkyawan Lokaci<br>a Tebur ɗinku',
            logoAlt: 'Al-Monajah',
            langToggleAria: 'Zaɓi harshe',
            close: 'Rufe',
            menuTitle: 'Menu na Abinci',
            menuSwitchLang: 'Canza harshe',
            menuHint: 'Gungura don duba shafukan menu',
            menuChoose: 'Zaɓi harshen menu:',
            menuNoImages: 'Babu hotunan menu a yanzu.',
            menuTitleAr: 'Menu — Larabci',
            menuTitleEn: 'Menu — English',
            pageWord: 'shafi',
            pagesWord: 'shafuka',
            labels: { after: 'Bayan Cin Abinci', blessing: 'Kiyaye Ni’ima', before: 'Kafin Cin Abinci', menu: 'Menu na Abinci', drink: 'Sha', bismillah: 'Idan Ka Manta Bismillah' },
            topics: {
                before: { title: 'Kafin Cin Abinci', html: '<p>Fara cin abincinka da nutsuwa da kyakkyawar niyya:</p><ul><li>Wanke hannuwanka kuma tsaftace bakinka idan zai yiwu.</li><li>Faɗi <strong>Bismillah</strong> kafin cin abinci.</li><li>Ci da hannun dama gwargwadon iyawa.</li><li>Kada ka fara kafin a ba wasu ko a ba ka izini.</li><li>Zauna a tebur ɗinka da tawali’u da godiya.</li></ul><div class="tm-panel-quote">“Idan ɗayanku ya ci abinci, to ya ambaci sunan Allah. Idan ya manta a farko, to ya ce: Bismillah a farkonsa da ƙarshe.”</div>' },
                blessing: { title: 'Kiyaye Ni’ima', html: '<p>Ni’ima amanace ce, kuma kiyaye ta godiya ce ga Allah:</p><ul><li>Ɗauki abin da kake bukata kawai, kada ka yi israfi.</li><li>Kada ka ɓata abinci ko ɗauki fiye da abin da zaka ci.</li><li>Gode wa Allah akan abin da Ya ba ka.</li><li>Idan ya wuce bukatar ka, to ka yi sadaka ko ka adana shi da kyau.</li></ul><div class="tm-panel-quote">“Babu wani akwati da ɗan Adam ya cika wanda ya fi cikinsa muni.”</div>' },
                after: { title: 'Bayan Cin Abinci', html: '<p>Ƙare cin abinci da zikir da godiya:</p><ul><li>Ce: <strong>Alhamdulillah</strong> Wanda Ya ciyar da ni wannan kuma Ya ba ni shi ba tare da ƙarfi ko iko daga gare ni ba.</li><li>Goge bakinka da hannuwanka idan zai yiwu.</li><li>Yi addu’ar albarka ga mai cin abinci.</li><li>Tashi daga tebur bayan ka gode kuma ba tare da israfi ba.</li></ul>' },
                menu: { title: 'Menu na Abinci', html: '' },
                drink: { title: 'Sha', html: '<p>Adabin sha daga Sunnah:</p><ul><li>Faɗi <strong>Bismillah</strong> kafin sha.</li><li>Sha kana zaune idan zai yiwu.</li><li>Kada ka hura a cikin kwano.</li><li>Sha a sau uku, kada ka sha a lokaci ɗaya.</li><li>Bayan haka ka ce: <strong>Alhamdulillah</strong>.</li></ul>' },
                bismillah: { title: 'Idan Ka Manta Bismillah', html: '<p>Idan ka soma cin abinci ka manta ka ce Bismillah:</p><ul><li>Ce: <strong>Bismillah a farkonsa da ƙarshe</strong>.</li><li>Kada ka bar zikir saboda ka manta a farko.</li><li>Sanya shi al’ada a kowane tsinka da kowace majalisa.</li></ul><div class="tm-panel-quote">“Bismillah a farkonsa da ƙarshe” — Abu Dawud da wasu sun ruwaito.</div>' }
            }
        },
        es: {
            pageTitle: 'Un hermoso momento en tu mesa | Al-Monajah',
            metaDescription: 'Un hermoso momento en tu mesa — una guía interactiva de la etiqueta de la comida y la bebida de Al-Monajah.',
            badge: 'Experiencia interactiva',
            title: 'Un hermoso momento<br>en tu mesa',
            logoAlt: 'Al-Monajah',
            langToggleAria: 'Elegir idioma',
            close: 'Cerrar',
            menuTitle: 'Menú',
            menuSwitchLang: 'Cambiar idioma',
            menuHint: 'Desplázate para ver las páginas del menú',
            menuChoose: 'Elige el idioma del menú:',
            menuNoImages: 'No hay imágenes del menú por ahora.',
            menuTitleAr: 'Menú — Árabe',
            menuTitleEn: 'Menu — English',
            pageWord: 'página',
            pagesWord: 'páginas',
            labels: { after: 'Después de la comida', blessing: 'Preservar la bendición', before: 'Antes de la comida', menu: 'Menú', drink: 'Bebida', bismillah: 'Si olvidaste decir Bismillah' },
            topics: {
                before: { title: 'Antes de la comida', html: '<p>Comienza tu comida con calma y una buena intención:</p><ul><li>Lávate las manos y limpia tu boca si es posible.</li><li>Di <strong>Bismillah</strong> antes de comer.</li><li>Come con la mano derecha siempre que puedas.</li><li>No empieces antes de que sirvan a los demás o te den permiso.</li><li>Siéntate en la mesa con humildad y gratitud.</li></ul><div class="tm-panel-quote">“Cuando uno de vosotros coma, que mencione el nombre de Allah. Si lo olvida al principio, que diga: Bismillah al principio y al final.”</div>' },
                blessing: { title: 'Preservar la bendición', html: '<p>La bendición es un depósito, y preservarla es agradecimiento a Allah:</p><ul><li>Toma solo lo que necesites y no seas derrochador.</li><li>No desperdicies la comida ni tomes más de lo que comerás.</li><li>Agradece a Allah por lo que te ha concedido.</li><li>Si sobra más de lo necesario, da en caridad o consérvalo bien.</li></ul><div class="tm-panel-quote">“Ningún ser humano llena un recipiente peor que su estómago.”</div>' },
                after: { title: 'Después de la comida', html: '<p>Concluye la comida con recuerdo y gratitud:</p><ul><li>Di: <strong>Alhamdulillah</strong> Quien me alimentó con esto y me lo concedió sin fuerza ni poder de mi parte.</li><li>Limpia tu boca y tus manos si es posible.</li><li>Pide bendición para el anfitrión.</li><li>Levántate de la mesa habiendo agradecido y sin desperdiciar.</li></ul>' },
                menu: { title: 'Menú', html: '' },
                drink: { title: 'Bebida', html: '<p>Etiqueta de beber según la Sunnah:</p><ul><li>Di <strong>Bismillah</strong> antes de beber.</li><li>Bebe sentado si es posible.</li><li>No soples en el recipiente.</li><li>Bebe en tres sorbos, no de una vez.</li><li>Di después: <strong>Alhamdulillah</strong>.</li></ul>' },
                bismillah: { title: 'Si olvidaste decir Bismillah', html: '<p>Si empezaste a comer y olvidaste decir Bismillah:</p><ul><li>Di: <strong>Bismillah al principio y al final</strong>.</li><li>No dejes el recuerdo solo porque lo olvidaste al inicio.</li><li>Hazlo un hábito en cada bocado y en cada reunión.</li></ul><div class="tm-panel-quote">“Bismillah al principio y al final” — narrado por Abu Dawud y otros.</div>' }
            }
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
    var menuHint = document.getElementById('menuHint');
    var langToggle = document.getElementById('langToggle');
    var langMenu = document.getElementById('langMenu');
    var tmBadge = document.getElementById('tmBadge');
    var tmTitle = document.getElementById('tmTitle');
    var tmLogo = document.getElementById('tmLogo');
    var menuCloseBtn = document.getElementById('menuCloseBtn');
    var menuSwitchLang = document.getElementById('menuSwitchLang');

    function langMeta(code) {
        return languages.find(function (l) { return l.code === code; }) || languages[0];
    }

    function t() {
        return copy[currentLang] || copy.ar;
    }

    function topic(key) {
        return (t().topics[key] || copy.ar.topics[key]);
    }

    function buildLangMenu() {
        langMenu.innerHTML = languages.map(function (lang) {
            return '<button type="button" class="tm-lang-option' + (lang.code === currentLang ? ' active' : '') + '" role="option" data-lang="' + lang.code + '" aria-selected="' + (lang.code === currentLang ? 'true' : 'false') + '">' + lang.label + '</button>';
        }).join('');
    }

    function setLangMenuOpen(open) {
        langMenu.classList.toggle('show', open);
        langToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function menuChooserHtml() {
        var c = t();
        return '' +
            '<p>' + c.menuChoose + '</p>' +
            '<div class="tm-lang-pick">' +
            '  <button type="button" class="tm-lang-btn" data-menu-lang="ar">' +
            '    <strong>العربية</strong><span>' + (menuPages.ar.length || 0) + ' ' + (menuPages.ar.length === 1 ? c.pageWord : c.pagesWord) + '</span>' +
            '  </button>' +
            '  <button type="button" class="tm-lang-btn" data-menu-lang="en">' +
            '    <strong>English</strong><span>' + (menuPages.en.length || 0) + ' ' + (menuPages.en.length === 1 ? c.pageWord : c.pagesWord) + '</span>' +
            '  </button>' +
            '</div>';
    }

    function applyLanguage(lang, persist) {
        currentLang = copy[lang] ? lang : 'ar';
        if (persist !== false) {
            try { localStorage.setItem('table-moment-lang', currentLang); } catch (e) {}
        }

        var c = t();
        var metaLang = langMeta(currentLang);
        document.documentElement.lang = currentLang;
        document.documentElement.dir = metaLang.dir;
        document.title = c.pageTitle;

        var meta = document.querySelector('meta[name="description"]');
        if (meta) meta.setAttribute('content', c.metaDescription);

        tmBadge.textContent = c.badge;
        tmTitle.innerHTML = c.title;
        tmLogo.alt = c.logoAlt;
        langToggle.setAttribute('aria-label', c.langToggleAria);
        panelClose.textContent = c.close;
        menuViewerTitle.textContent = c.menuTitle;
        menuSwitchLang.textContent = c.menuSwitchLang;
        menuCloseBtn.textContent = c.close;
        menuHint.textContent = c.menuHint;
        buildLangMenu();

        document.querySelectorAll('.tm-icon-label[data-key]').forEach(function (el) {
            var key = el.getAttribute('data-key');
            if (c.labels[key]) el.textContent = c.labels[key];
        });

        if (menuViewer.classList.contains('show')) {
            menuViewerTitle.textContent = currentMenuLang === 'ar' ? c.menuTitleAr : c.menuTitleEn;
        }

        if (panel.classList.contains('show') && activePanelKey) {
            var item = topic(activePanelKey);
            panelTitle.textContent = item.title;
            panelBody.innerHTML = activePanelKey === 'menu' ? menuChooserHtml() : item.html;
            panelIcon.innerHTML = '<img src="' + iconBase + '/' + activePanelKey + '.png" alt="">';
        }
    }

    function openPanel(key) {
        var item = topic(key);
        if (!item) return;
        activePanelKey = key;
        panelTitle.textContent = item.title;
        panelBody.innerHTML = key === 'menu' ? menuChooserHtml() : item.html;
        panelIcon.innerHTML = '<img src="' + iconBase + '/' + key + '.png" alt="">';
        panelClose.style.display = key === 'menu' ? 'none' : '';
        buttons.forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-key') === key);
        });
        backdrop.classList.add('show');
        panel.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        panel.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setLangMenuOpen(false);
    }

    function closePanel() {
        backdrop.classList.remove('show');
        panel.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        panel.setAttribute('aria-hidden', 'true');
        panelClose.style.display = '';
        activePanelKey = null;
        if (!menuViewer.classList.contains('show')) {
            document.body.style.overflow = '';
        }
        buttons.forEach(function (btn) { btn.classList.remove('active'); });
    }

    function openMenu(lang) {
        var pages = menuPages[lang] || [];
        var c = t();
        if (!pages.length) {
            panelBody.innerHTML = '<p>' + c.menuNoImages + '</p>';
            panelClose.style.display = '';
            return;
        }
        currentMenuLang = lang;
        menuViewerTitle.textContent = lang === 'ar' ? c.menuTitleAr : c.menuTitleEn;
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

    function initialLang() {
        var allowed = languages.map(function (l) { return l.code; });
        try {
            var params = new URLSearchParams(window.location.search);
            var fromUrl = params.get('lang');
            if (fromUrl && allowed.indexOf(fromUrl) !== -1) return fromUrl;
            var saved = localStorage.getItem('table-moment-lang');
            if (saved && allowed.indexOf(saved) !== -1) return saved;
        } catch (e) {}
        return 'ar';
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
    menuCloseBtn.addEventListener('click', closeMenu);
    menuSwitchLang.addEventListener('click', function () {
        openMenu(currentMenuLang === 'ar' ? 'en' : 'ar');
    });

    langToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        setLangMenuOpen(!langMenu.classList.contains('show'));
    });

    langMenu.addEventListener('click', function (e) {
        var opt = e.target.closest('[data-lang]');
        if (!opt) return;
        applyLanguage(opt.getAttribute('data-lang'));
        setLangMenuOpen(false);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.tm-lang-wrap')) setLangMenuOpen(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (langMenu.classList.contains('show')) setLangMenuOpen(false);
        else if (menuViewer.classList.contains('show')) closeMenu();
        else closePanel();
    });

    applyLanguage(initialLang(), false);
})();
</script>
@endpush
@endsection
