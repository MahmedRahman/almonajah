@extends('layouts.landing')

@section('title', 'آداب الإطعام | المناجاة')

@section('meta')
<meta name="description" content="آداب الإطعام: فضله، سننه، وكيف تطعم الناس بإحسان — صفحة من منصة المناجاة.">
<meta name="robots" content="index,follow">
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
@endsection

@section('content')
@push('styles')
<style>
.itama-page {
    min-height: 100vh;
    overflow-x: hidden;
    color: #3f2e1f;
    background:
        radial-gradient(ellipse 80% 50% at 90% -10%, rgba(245, 158, 11, 0.18), transparent 50%),
        radial-gradient(ellipse 60% 40% at 0% 20%, rgba(13, 148, 136, 0.12), transparent 45%),
        linear-gradient(180deg, #fff7ed 0%, #fefce8 28%, #f8fafc 70%, #ffffff 100%);
}
.itama-page::before {
    content: "";
    pointer-events: none;
    position: fixed;
    inset: 0;
    background-image:
        radial-gradient(rgba(180, 83, 9, 0.07) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(180deg, rgba(0,0,0,.55), transparent 70%);
}
.itama-top {
    max-width: 920px;
    margin: 0 auto;
    padding: 1.1rem 1.25rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
}
.itama-brand {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    color: #0f766e;
    font-weight: 700;
    text-decoration: none;
}
.itama-brand img { width: 36px; height: 36px; border-radius: 8px; }
.itama-brand:hover { color: #115e59; }
.itama-hero {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 760px;
    margin: 0 auto;
    padding: 3.2rem 1.25rem 2.4rem;
}
.itama-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(255,255,255,.72);
    border: 1px solid rgba(180, 83, 9, 0.18);
    color: #b45309;
    font-weight: 600;
    font-size: 0.86rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    margin-bottom: 1.1rem;
    backdrop-filter: blur(8px);
}
.itama-hero h1 {
    font-family: Amiri, "Times New Roman", serif;
    font-size: clamp(2.6rem, 8vw, 4.4rem);
    font-weight: 700;
    line-height: 1.15;
    color: #7c2d12;
    margin-bottom: 0.55rem;
    letter-spacing: -0.02em;
}
.itama-hero .lead {
    font-size: 1.12rem;
    color: #57534e;
    max-width: 36rem;
    margin: 0 auto 1.5rem;
    line-height: 1.85;
}
.itama-hadith {
    max-width: 640px;
    margin: 0 auto;
    background: linear-gradient(180deg, #fffbeb, #fff);
    border: 1px solid rgba(180, 83, 9, 0.16);
    border-radius: 1.4rem;
    padding: 1.35rem 1.4rem 1.15rem;
    box-shadow: 0 20px 50px rgba(120, 53, 15, 0.08);
}
.itama-hadith p {
    font-family: Amiri, serif;
    font-size: clamp(1.15rem, 3vw, 1.45rem);
    color: #0f766e;
    line-height: 2;
    margin: 0 0 0.45rem;
}
.itama-hadith .src {
    color: #a8a29e;
    font-size: 0.82rem;
    margin: 0;
}
.itama-wrap {
    max-width: 980px;
    margin: 0 auto;
    padding: 0 1.15rem 4.5rem;
    position: relative;
    z-index: 1;
}
.itama-section-head {
    text-align: center;
    margin: 2.6rem 0 1.4rem;
}
.itama-section-head h2 {
    font-size: clamp(1.4rem, 3.5vw, 1.85rem);
    color: #0f766e;
    font-weight: 700;
    margin-bottom: 0.4rem;
}
.itama-section-head p {
    color: #78716c;
    margin: 0;
}
.itama-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}
.itama-card {
    background: rgba(255,255,255,.86);
    border: 1px solid rgba(226, 232, 240, .95);
    border-radius: 1.15rem;
    padding: 1.2rem 1.15rem 1.15rem;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    min-height: 100%;
}
.itama-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 40px rgba(120, 53, 15, 0.1);
    border-color: rgba(13, 148, 136, 0.28);
}
.itama-num {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.7rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.92rem;
    color: #fff;
    background: linear-gradient(135deg, #d97706, #b45309);
    margin-bottom: 0.75rem;
}
.itama-card h3 {
    font-size: 1.08rem;
    font-weight: 700;
    color: #1c1917;
    margin-bottom: 0.4rem;
}
.itama-card p {
    margin: 0;
    color: #57534e;
    font-size: 0.95rem;
    line-height: 1.75;
}
.itama-virtue {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1rem;
    margin-top: 0.5rem;
}
@media (max-width: 760px) {
    .itama-virtue { grid-template-columns: 1fr; }
}
.itama-quote-big {
    background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #b45309 160%);
    color: #fff;
    border-radius: 1.4rem;
    padding: 1.6rem 1.4rem;
    box-shadow: 0 18px 40px rgba(15, 118, 110, 0.28);
}
.itama-quote-big h3 {
    font-family: Amiri, serif;
    font-size: 1.45rem;
    margin-bottom: 0.6rem;
    line-height: 1.8;
}
.itama-quote-big p { margin: 0; opacity: .92; line-height: 1.8; }
.itama-steps {
    background: #fff;
    border-radius: 1.4rem;
    border: 1px solid #e7e5e4;
    padding: 1.25rem 1.2rem;
}
.itama-steps ol {
    margin: 0;
    padding: 0 1.2rem 0 0;
    color: #44403c;
}
.itama-steps li { margin: 0.55rem 0; line-height: 1.7; }
.itama-cta {
    margin-top: 2.5rem;
    text-align: center;
    background: linear-gradient(180deg, #fff7ed, #ffffff);
    border: 1px solid rgba(180, 83, 9, 0.15);
    border-radius: 1.5rem;
    padding: 2rem 1.25rem;
}
.itama-cta h2 { color: #7c2d12; font-weight: 700; margin-bottom: 0.45rem; }
.itama-cta p { color: #78716c; margin-bottom: 1.1rem; }
.itama-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(135deg, #0d9488, #0f766e);
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    padding: 0.85rem 1.4rem;
    border-radius: 999px;
    box-shadow: 0 10px 24px rgba(13, 148, 136, 0.28);
}
.itama-btn:hover { color: #fff; transform: translateY(-1px); }
.itama-foot {
    text-align: center;
    color: #a8a29e;
    font-size: 0.82rem;
    margin-top: 1.25rem;
}
</style>
@endpush

<div class="itama-page">
    <div class="itama-top">
        <a class="itama-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo.png') }}" alt="المناجاة">
            المناجاة
        </a>
        <span class="itama-kicker" style="margin:0;"><i class="bi bi-heart-fill"></i> باب من أبواب الجنة</span>
    </div>

    <header class="itama-hero">
        <span class="itama-kicker"><i class="bi bi-stars"></i> سُنّة الإحسان إلى الخلق</span>
        <h1>آداب الإطعام</h1>
        <p class="lead">الإطعام ليس مائدةً فحسب… هو نية، وبشاشة، وستر حاجة، وشكر لله الذي أطعمك ثم جعل في يدك طعام غيرك.</p>
        <div class="itama-hadith">
            <p>«أطعموا الطعام، وأفشوا السلام، وصِلوا الأرحام، وصلّوا بالليل والناس نيام؛ تدخلوا الجنة بسلام.»</p>
            <p class="src">أخرجه الترمذي وابن ماجه — وحسّنه أهل العلم</p>
        </div>
    </header>

    <div class="itama-wrap">
        <div class="itama-virtue">
            <div class="itama-quote-big">
                <h3>«يا أيها الناس، أفشوا السلام، وأطعموا الطعام، وصلّوا والناس نيام؛ تدخلوا الجنة بسلام.»</h3>
                <p>كان السلف يعدّون إطعام الطعام من أعظم القربات: لأنه يُدخل السرور، ويقضي حاجة، ويجمع القلوب، ويُذهب الشح.</p>
            </div>
            <div class="itama-steps">
                <h3 style="color:#0f766e;font-weight:700;margin-bottom:.75rem;">روح الإطعام في ثلاث</h3>
                <ol>
                    <li><strong>لله:</strong> لا رياء ولا منّة ولا انتظار مقابل.</li>
                    <li><strong>للخلق:</strong> ابدأ بالجائع والقريب والجار والضيف.</li>
                    <li><strong>بالإحسان:</strong> طعام طيب، ووجه طلق، وكلمة حسنة.</li>
                </ol>
            </div>
        </div>

        <div class="itama-section-head">
            <h2>آداب الإطعام</h2>
            <p>اختر أدبًا، واجعله عادة في بيتك ومائدتك.</p>
        </div>

        <div class="itama-grid">
            <article class="itama-card">
                <div class="itama-num">1</div>
                <h3>أخلص النية</h3>
                <p>أطعم ابتغاء وجه الله، لا ليُقال كريم، ولا ليمنحَك الناس منزلة. النية تحول المائدة إلى عبادة.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">2</div>
                <h3>لا تمنّ ولا تؤذِ</h3>
                <p>المنّ يُبطل الصدقة. لا تُذكّر المُطعَم بمعروفك، ولا تُحرجه بسؤال، ولا تُظهر أنك تفضّلت عليه.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">3</div>
                <h3>أكرم الضيف</h3>
                <p>«من كان يؤمن بالله واليوم الآخر فليُكرم ضيفه». الاستقبال البشوش جزء من الطعام، لا يقلّ عن اللقمة.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">4</div>
                <h3>قدّم للجائع أولًا</h3>
                <p>حق الجائع والمحتاج مقدَّم. إطعام مسكين أو صائم أو عابر سبيل أحب إلى الله من ترف زائد على الشبعان.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">5</div>
                <h3>أطعم جارك</h3>
                <p>«ما زال جبريل يوصيني بالجار». طبق صغير على باب الجار يفتح باب مودة لا يغلقه مال كثير.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">6</div>
                <h3>طيب الوجه والكلمة</h3>
                <p>البشاشة صدقة. لا تُطعم وأنت متضجّر، ولا تُسمع الضيف ما يُنغّص عليه. الكلمة الطيبة من تمام القرى.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">7</div>
                <h3>نظافة المائدة وطيّب الطعام</h3>
                <p>قدّم طعامًا حلالًا نظيفًا، في إناء حسن، بلا استعجال يُهين ولا تكلّف يُرهق. الوسط أحسن.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">8</div>
                <h3>لا تسرف</h3>
                <p>الكرم غير الإسراف. خذ كفايتك، ويسّر على نفسك، وما زاد فتصدّق به أو احفظه. الله لا يحب المسرفين.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">9</div>
                <h3>سمِّ الله واشكره</h3>
                <p>علّم البيت البسملة قبل الطعام، والحمد بعده. ومن أطعم فليدعُ للآكل: «أفطر عندكم الصائمون…» أو دعاء يناسب الحال.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">10</div>
                <h3>اجلس معهم إن تيسّر</h3>
                <p>الأكل مع الضيف أنسٌ وأبعد عن الكبر. وإن لم تجلس فلا تتركه وحيدًا بلا خدمة أو حديث لطيف.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">11</div>
                <h3>استر حاجة من تطعمه</h3>
                <p>إن كان فقيرًا فأدّ الطعام في ستر. الهدية أحيانًا أكرم من الصدقة الظاهرة، لأنها تحفظ ماء الوجه.</p>
            </article>
            <article class="itama-card">
                <div class="itama-num">12</div>
                <h3>وسّع الدائرة</h3>
                <p>أطعم الأهل أولًا، ثم الرحم، ثم الجار، ثم اليتيم وابن السبيل. وكل لقمة في سبيل الله مكتوبة.</p>
            </article>
        </div>

        <div class="itama-cta">
            <h2>لقمة اليوم عبادة</h2>
            <p>اختر نفسًا تطعمها اليوم: ضيفًا، جارًا، فقيرًا، أو أهل بيتك. ثم سل الله أن يجعلها خالصة.</p>
            <a class="itama-btn" href="{{ url('/') }}"><i class="bi bi-house-heart"></i> عُد إلى المناجاة</a>
            <p class="itama-foot">منصة المناجاة — تذكير يسير بآداب عظيمة</p>
        </div>
    </div>
</div>
@endsection
