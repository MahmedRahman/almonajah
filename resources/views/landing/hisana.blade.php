@extends('layouts.landing')

@section('title', 'الحصانة - أذكار المسلم في يومه وليلته | المناجاة')

@section('meta')
<meta name="description" content="حمّل كتاب الحصانة مجانًا: أذكار المسلم في يومه وليلته. دليلك اليومي للطمأنينة في كل لحظة من حياتك. كتاب شامل من السنة النبوية.">
@endsection

@section('content')
@push('styles')
<style>
.hisana-page { font-family: 'Alexandria', sans-serif; background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 30%); min-height: 80vh; padding: 2rem 0 4rem; }
.hisana-hero { text-align: center; padding: 2.5rem 1rem 3rem; }
.hisana-hero .icon-mosque { font-size: 3rem; margin-bottom: 0.5rem; opacity: 0.9; }
.hisana-hero h1 { font-size: clamp(1.75rem, 5vw, 2.5rem); font-weight: 700; color: #0d9488; margin-bottom: 0.5rem; }
.hisana-hero .subtitle { font-size: 1.15rem; color: #334155; margin-bottom: 0.5rem; }
.hisana-hero .tagline { font-size: 1rem; color: #0f766e; margin-bottom: 1.5rem; max-width: 520px; margin-left: auto; margin-right: auto; }
.hisana-hero .intro { max-width: 560px; margin: 0 auto 1rem; color: #475569; font-size: 1rem; line-height: 1.7; }
.hisana-cta-wrap { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem; }
.hisana-cta { display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; font-size: 1.1rem; font-weight: 600; border-radius: 50px; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; border: none; }
.hisana-cta-download { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: #fff; box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4); }
.hisana-cta-download:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13, 148, 136, 0.5); }
.hisana-cta-listen { background: #fff; color: #0d9488; border: 2px solid #0d9488; }
.hisana-cta-listen:hover { background: #f0fdfa; color: #0f766e; transform: translateY(-2px); }
.hisana-section { max-width: 680px; margin: 0 auto 3rem; padding: 0 1rem; }
.hisana-section-title { font-size: 1.25rem; font-weight: 700; color: #0f766e; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
.hisana-section-intro { color: #475569; margin-bottom: 1.25rem; line-height: 1.7; }
.hisana-list { list-style: none; padding: 0; margin: 0; }
.hisana-list li { position: relative; padding-right: 1.5rem; margin-bottom: 0.75rem; color: #334155; }
.hisana-list li::before { content: '✔'; position: absolute; right: 0; color: #0d9488; font-weight: 700; }
.hisana-bullets { list-style: none; padding: 0; margin: 0; padding-right: 1.25rem; color: #475569; }
.hisana-bullets li { margin-bottom: 0.5rem; position: relative; padding-right: 1rem; }
.hisana-bullets li::before { content: '•'; position: absolute; right: 0; color: #0d9488; font-weight: 700; }
.hisana-why { background: #f8fafc; border-radius: 1rem; padding: 1.5rem; margin-bottom: 0; }
.hisana-why .hisana-bullets { margin: 0; }
.hisana-quote { background: linear-gradient(135deg, #ccfbf1 0%, #f0fdfa 100%); border-right: 4px solid #0d9488; padding: 1rem 1.25rem; border-radius: 0 0.5rem 0.5rem 0; margin-top: 1.25rem; font-style: italic; color: #0f766e; }
.hisana-divider { height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0, transparent); margin: 2.5rem auto; max-width: 400px; }
.hisana-final-cta { text-align: center; padding: 2rem 1rem; }
.hisana-final-cta .hisana-cta { font-size: 1.05rem; }
.hisana-mission { text-align: center; color: #64748b; font-size: 0.95rem; line-height: 1.7; }
</style>
@endpush

<div class="hisana-page">
    <div class="hisana-hero">
        <div class="icon-mosque">🕌</div>
        <h1>الحصانة</h1>
        <p class="subtitle">أذكار المسلم في يومه وليلته</p>
        <p class="tagline">دليلك اليومي للطمأنينة… في كل لحظة من حياتك</p>
        <p class="intro">كتاب شامل يجمع أذكار المسلم الصحيحة من السنة النبوية،<br>مرتبة ومنظمة في يومك وليلتك وفي جميع أحوالك.</p>

        <div class="hisana-divider"></div>

        <p class="hisana-section-title justify-content-center" style="margin-bottom: 0.5rem;">📥 حمّل نسختك المجانية الآن</p>
        <div class="hisana-cta-wrap">
            <a href="https://drive.google.com/uc?export=download&id=1H3SQTQzLoQBRyQg47AfwJAMblFJnSAAY" target="_blank" rel="noopener noreferrer" download class="hisana-cta hisana-cta-download">
                <i class="bi bi-download"></i> تحميل الكتاب الآن
            </a>
            <a href="https://soundcloud.com/almonajaah/sets/alhasanah" target="_blank" rel="noopener noreferrer" class="hisana-cta hisana-cta-listen">
                <i class="bi bi-headphones"></i> الاستماع إلى الأدعية صوتيًا
            </a>
        </div>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section">
        <h2 class="hisana-section-title">لماذا هذا الكتاب مختلف؟</h2>
        <p class="hisana-section-intro">لأن حياتك مليئة بالمواقف… وهذا الكتاب يمنحك الدعاء المناسب لكل موقف دون بحث أو تشتت.</p>
        <ul class="hisana-list">
            <li>أذكار الصباح والمساء</li>
            <li>أذكار النوم والاستيقاظ</li>
            <li>أذكار دخول وخروج المنزل</li>
            <li>أذكار قبل وبعد الوضوء</li>
            <li>أذكار المسجد والصلاة بالتفصيل</li>
            <li>أذكار الطعام والشراب</li>
            <li>أذكار السفر والركوب</li>
            <li>أدعية الهم والحزن والكرب</li>
            <li>أذكار المطر والرياح والرعد</li>
            <li>أدعية الزواج والمولود</li>
            <li>أذكار المرض والتعزية والجنائز</li>
            <li>أذكار الحج والعمرة ويوم عرفة</li>
        </ul>
        <p class="hisana-section-intro mb-0">كل ذلك في مرجع واحد منظم وسهل الاستخدام.</p>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section">
        <h2 class="hisana-section-title">🌿 مميزات الكتاب</h2>
        <div class="hisana-why">
            <ul class="hisana-bullets">
                <li>شامل لكل أذكار اليوم والليلة</li>
                <li>يغطي تفاصيل الصلاة (الركوع – السجود – التشهد – الوتر – الاستغفار)</li>
                <li>منظم بطريقة فهرس واضحة وسهلة</li>
                <li>يمكنك السماع إلى الدعاء عند النقر على الفهرس</li>
                <li>مناسب للكبار والصغار</li>
                <li>تصميم مريح وسهل القراءة</li>
                <li>الكتاب تفاعلي ومسموع</li>
                <li>متوفر بنسخة صوتية للاستماع للأدعية</li>
            </ul>
        </div>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section">
        <h2 class="hisana-section-title">🤍 ماذا سيضيف ليومك؟</h2>
        <ul class="hisana-bullets">
            <li>يذكرك بالله في كل وقت</li>
            <li>يساعدك على المواظبة على الأذكار</li>
            <li>يمنحك سكينة وطمأنينة</li>
            <li>يحفظ وقتك من البحث المتكرر</li>
        </ul>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section text-center">
        <h2 class="hisana-section-title justify-content-center">🎧 استمع للأدعية بصوت واضح</h2>
        <p class="hisana-section-intro">يمكنك الاستماع إلى الأدعية مباشرة عبر المنصات الصوتية<br>ليكون الذكر معك في السيارة، في العمل، أو قبل النوم.</p>
        <a href="https://soundcloud.com/almonajaah/sets/alhasanah" target="_blank" rel="noopener noreferrer" class="hisana-cta hisana-cta-listen">
            <i class="bi bi-headphones"></i> استمع الآن
        </a>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section">
        <h2 class="hisana-section-title">📖 لمن هذا الكتاب؟</h2>
        <ul class="hisana-bullets">
            <li>لكل مسلم يريد المحافظة على الأذكار اليومية</li>
            <li>للآباء والأمهات لتعليم أبنائهم</li>
            <li>لمن يريد مرجعًا موثوقًا وسهل الاستخدام</li>
            <li>لمن يبحث عن الطمأنينة في حياته اليومية</li>
        </ul>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-section">
        <p class="hisana-section-title justify-content-center">إصدار من منصة المناجاة الرقمية</p>
        <p class="hisana-mission">إحدى مبادرات إقرأ.<br>عمل دعوي مجاني يهدف لنشر الخير وتيسير الذكر.<br>نسألكم الدعاء ونشر الكتاب لتعم الفائدة.</p>
    </div>

    <div class="hisana-divider"></div>

    <div class="hisana-final-cta">
        <h2 class="hisana-section-title justify-content-center mb-2">📥 ابدأ اليوم</h2>
        <p class="hisana-section-intro mb-3">اجعل لسانك رطبًا بذكر الله…<br>واجعل هذا الكتاب رفيقك اليومي.</p>
        <a href="https://drive.google.com/uc?export=download&id=1H3SQTQzLoQBRyQg47AfwJAMblFJnSAAY" target="_blank" rel="noopener noreferrer" download class="hisana-cta hisana-cta-download">
            <i class="bi bi-download"></i> تحميل الكتاب مجانًا الآن
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YWBWE6GE7W"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-YWBWE6GE7W');
</script>
@endpush
