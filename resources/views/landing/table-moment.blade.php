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
.tm-lang-toggle {
    position: fixed;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 20;
    appearance: none;
    border: 1px solid var(--tm-ring);
    background: rgba(255,255,255,.9);
    color: var(--tm-teal-dark);
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    cursor: pointer;
    transition: background .15s ease, transform .15s ease;
}
.tm-lang-toggle:hover {
    background: #fff;
    transform: translateY(-1px);
}
html[dir="ltr"] .tm-panel-body ul {
    padding: 0 0 0 1.1rem;
}
html[dir="ltr"] .tm-panel-quote {
    border-right: none;
    border-left: 3px solid var(--tm-teal);
}
html[dir="ltr"] .tm-icon-btn {
    direction: ltr;
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
    <button type="button" class="tm-lang-toggle" id="langToggle" aria-label="Switch language">EN</button>

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
    var copy = {
        ar: {
            pageTitle: 'لحظة جميلة على مائدتك | المناجاة',
            metaDescription: 'لحظة جميلة على مائدتك — تجربة تفاعلية لآداب الطعام والشراب من منصة المناجاة.',
            badge: 'تجربة تفاعلية',
            title: 'لحظة جميلة<br>على مائدتك',
            logoAlt: 'المناجاة',
            langToggle: 'EN',
            langToggleAria: 'التبديل إلى الإنجليزية',
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
            labels: {
                after: 'بعد الطعام',
                blessing: 'حفظ النعمة',
                before: 'قبل الطعام',
                menu: 'قائمة الطعام',
                drink: 'الشراب',
                bismillah: 'إذا نسيت التسمية'
            },
            topics: {
                before: {
                    title: 'قبل الطعام',
                    html: '<p>ابدأ مائدتك بسكينة ونية طيبة:</p><ul><li>اغسل يديك ونظّف فمك إن أمكن.</li><li>قل <strong>بسم الله</strong> قبل الأكل.</li><li>كل بيمينك ما استطعت.</li><li>لا تبدأ قبل أن يُطعَم معك أو يُؤذَن لك.</li><li>اجلس على مائدتك بتواضع وشكر.</li></ul><div class="tm-panel-quote">«إذا أكل أحدكم فليذكر اسم الله. فإن نسي أن يذكر اسم الله في أوله فليقل: بسم الله أوله وآخره.»</div>'
                },
                blessing: {
                    title: 'حفظ النعمة',
                    html: '<p>النعمة أمانة، وحفظها من شكر الله:</p><ul><li>خذ ما يكفيك ولا تُسرِف.</li><li>لا تُهدر الطعام ولا تُكثر ما لا تأكله.</li><li>اشكر الله على ما رزقك.</li><li>إن زاد عن حاجتك فتصدّق أو احفظه بإحسان.</li></ul><div class="tm-panel-quote">«ما ملأ آدمي وعاءً شرًّا من بطن.»</div>'
                },
                after: {
                    title: 'بعد الطعام',
                    html: '<p>ختم الطعام بذكر وشكر:</p><ul><li>قل: <strong>الحمد لله</strong> الذي أطعمني هذا ورزقنيه من غير حول مني ولا قوة.</li><li>امسح فمك ويديك إن أمكن.</li><li>ادعُ لصاحب الطعام بالبركة.</li><li>قُم عن المائدة وقد شكرت ولم تُسرِف.</li></ul>'
                },
                menu: {
                    title: 'قائمة الطعام',
                    html: ''
                },
                drink: {
                    title: 'الشراب',
                    html: '<p>آداب الشرب من السنة:</p><ul><li>قل <strong>بسم الله</strong> قبل الشرب.</li><li>اشرب جالسًا إن تيسّر.</li><li>لا تنفث في الإناء.</li><li>اشرب على ثلاث مرات ولا تشرب دفعة واحدة.</li><li>قل بعده: <strong>الحمد لله</strong>.</li></ul>'
                },
                bismillah: {
                    title: 'إذا نسيت التسمية',
                    html: '<p>إن بدأت الأكل ونسيت أن تقول بسم الله:</p><ul><li>قل: <strong>بسم الله في أوله وآخره</strong>.</li><li>لا تُترك الذكر لأنك نسيت في البداية.</li><li>اجعلها عادة في كل لقمة وكل جلسة.</li></ul><div class="tm-panel-quote">«بسم الله أوله وآخره» — رواه أبو داود وغيره.</div>'
                }
            }
        },
        en: {
            pageTitle: 'A Beautiful Moment at Your Table | Al-Monajah',
            metaDescription: 'A beautiful moment at your table — an interactive guide to food and drink etiquette from Al-Monajah.',
            badge: 'Interactive experience',
            title: 'A Beautiful Moment<br>at Your Table',
            logoAlt: 'Al-Monajah',
            langToggle: 'عربي',
            langToggleAria: 'Switch to Arabic',
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
            labels: {
                after: 'After the Meal',
                blessing: 'Preserving the Blessing',
                before: 'Before the Meal',
                menu: 'Food Menu',
                drink: 'Drinks',
                bismillah: 'If You Forgot to Say Bismillah'
            },
            topics: {
                before: {
                    title: 'Before the Meal',
                    html: '<p>Begin your meal with calmness and a good intention:</p><ul><li>Wash your hands and clean your mouth if possible.</li><li>Say <strong>Bismillah</strong> before eating.</li><li>Eat with your right hand whenever you can.</li><li>Do not start before others are served or you are given permission.</li><li>Sit at your table with humility and gratitude.</li></ul><div class="tm-panel-quote">"When one of you eats, let him mention the name of Allah. If he forgets at the beginning, let him say: Bismillah at its beginning and its end."</div>'
                },
                blessing: {
                    title: 'Preserving the Blessing',
                    html: '<p>A blessing is a trust, and preserving it is gratitude to Allah:</p><ul><li>Take only what you need and do not be wasteful.</li><li>Do not waste food or take more than you will eat.</li><li>Thank Allah for what He has provided.</li><li>If there is more than you need, give in charity or preserve it well.</li></ul><div class="tm-panel-quote">"No human being fills a vessel worse than his stomach."</div>'
                },
                after: {
                    title: 'After the Meal',
                    html: '<p>Conclude your meal with remembrance and gratitude:</p><ul><li>Say: <strong>Alhamdulillah</strong> who fed me this and provided it for me without any power or strength from me.</li><li>Wipe your mouth and hands if possible.</li><li>Pray for blessings upon the host.</li><li>Leave the table having thanked Allah and without being wasteful.</li></ul>'
                },
                menu: {
                    title: 'Food Menu',
                    html: ''
                },
                drink: {
                    title: 'Drinks',
                    html: '<p>Etiquette of drinking from the Sunnah:</p><ul><li>Say <strong>Bismillah</strong> before drinking.</li><li>Drink while seated if possible.</li><li>Do not blow into the vessel.</li><li>Drink in three sips, not all at once.</li><li>Say afterward: <strong>Alhamdulillah</strong>.</li></ul>'
                },
                bismillah: {
                    title: 'If You Forgot to Say Bismillah',
                    html: '<p>If you started eating and forgot to say Bismillah:</p><ul><li>Say: <strong>Bismillah at its beginning and its end</strong>.</li><li>Do not leave remembrance just because you forgot at the start.</li><li>Make it a habit in every bite and every gathering.</li></ul><div class="tm-panel-quote">"Bismillah at its beginning and its end" — narrated by Abu Dawud and others.</div>'
                }
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
    var tmBadge = document.getElementById('tmBadge');
    var tmTitle = document.getElementById('tmTitle');
    var tmLogo = document.getElementById('tmLogo');
    var menuCloseBtn = document.getElementById('menuCloseBtn');
    var menuSwitchLang = document.getElementById('menuSwitchLang');

    function t() {
        return copy[currentLang] || copy.ar;
    }

    function topic(key) {
        return (t().topics[key] || copy.ar.topics[key]);
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
        document.documentElement.lang = currentLang;
        document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
        document.title = c.pageTitle;

        var meta = document.querySelector('meta[name="description"]');
        if (meta) meta.setAttribute('content', c.metaDescription);

        tmBadge.textContent = c.badge;
        tmTitle.innerHTML = c.title;
        tmLogo.alt = c.logoAlt;
        langToggle.textContent = c.langToggle;
        langToggle.setAttribute('aria-label', c.langToggleAria);
        panelClose.textContent = c.close;
        menuViewerTitle.textContent = c.menuTitle;
        menuSwitchLang.textContent = c.menuSwitchLang;
        menuCloseBtn.textContent = c.close;
        menuHint.textContent = c.menuHint;

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
        try {
            var params = new URLSearchParams(window.location.search);
            var fromUrl = params.get('lang');
            if (fromUrl === 'en' || fromUrl === 'ar') return fromUrl;
            var saved = localStorage.getItem('table-moment-lang');
            if (saved === 'en' || saved === 'ar') return saved;
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
    langToggle.addEventListener('click', function () {
        applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (menuViewer.classList.contains('show')) closeMenu();
        else closePanel();
    });

    applyLanguage(initialLang(), false);
})();
</script>
@endpush
@endsection
