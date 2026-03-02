@extends('layouts.landing')

@section('title', 'شروط الخدمة | المناجاة')

@section('meta')
<meta name="description" content="شروط استخدام تطبيق المناجاة والموقع.">
@endsection

@section('content')
@push('styles')
<style>
.legal-page { font-family: 'Alexandria', sans-serif; background: #f8fafc; min-height: 80vh; padding: 2rem 0 4rem; }
.legal-page .container { max-width: 720px; }
.legal-hero { text-align: center; padding: 2rem 1rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 2rem; }
.legal-hero h1 { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 700; color: #0d9488; margin-bottom: 0.5rem; }
.legal-hero .sub { color: #64748b; font-size: 0.95rem; }
.legal-section { margin-bottom: 2rem; }
.legal-section h2 { font-size: 1.2rem; font-weight: 700; color: #0f766e; margin-bottom: 0.75rem; }
.legal-section p, .legal-section ul { color: #334155; line-height: 1.8; margin-bottom: 0.75rem; }
.legal-section ul { padding-right: 1.5rem; }
.legal-section li { margin-bottom: 0.35rem; }
.legal-back { display: inline-flex; align-items: center; gap: 0.5rem; color: #0d9488; text-decoration: none; font-weight: 600; margin-bottom: 1.5rem; }
.legal-back:hover { color: #0f766e; }
.legal-updated { font-size: 0.9rem; color: #64748b; margin-top: 2rem; }
</style>
@endpush

<div class="legal-page">
    <div class="container">
        <a href="{{ url('/') }}" class="legal-back"><i class="bi bi-arrow-right"></i> العودة للرئيسية</a>

        <div class="legal-hero">
            <h1>شروط الخدمة</h1>
            <p class="sub">تطبيق المناجاة — آخر تحديث: {{ now()->translatedFormat('d F Y') }}</p>
        </div>

        <div class="legal-section">
            <h2>١. القبول بالشروط</h2>
            <p>باستخدامك تطبيق المناجاة أو الموقع الإلكتروني (almonajah.com)، فإنك توافق على الالتزام بهذه الشروط. إن كنت لا توافق عليها، يرجى عدم استخدام الخدمة.</p>
        </div>

        <div class="legal-section">
            <h2>٢. وصف الخدمة</h2>
            <p>المناجاة منصة تقدم محتوى صوتي ومرئي (محاضرات، دروس، أذكار) مع إمكانية الترجمة والتنزيل والتفاعل (تعليقات، إعجاب، مفضلة) وفق ما توفره الواجهة.</p>
        </div>

        <div class="legal-section">
            <h2>٣. استخدام مقبول</h2>
            <p>تتعهد باستخدام الخدمة لأغراض شخصية وتعليمية مشروعة فقط، وعدم:</p>
            <ul>
                <li>نسخ أو إعادة توزيع المحتوى بشكل تجاري دون إذن.</li>
                <li>استخدام التطبيق لأي نشاط غير قانوني أو يضر بالآخرين.</li>
                <li>محاولة اختراق الأنظمة أو حسابات المستخدمين.</li>
                <li>نشر محتوى مسيء أو مخالف للشريعة أو القانون.</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>٤. الملكية الفكرية</h2>
            <p>المحتوى المعروض (نصوص، صوت، فيديو، ترجمات) يخضع لحقوق النشر والملكية الفكرية لأصحابها. المناجاة لا تمنحك حقوقاً تجارية على المحتوى دون موافقة صريحة.</p>
        </div>

        <div class="legal-section">
            <h2>٥. الحساب والمسؤولية</h2>
            <p>أنت مسؤول عن الحفاظ على سرية بيانات الدخول وحسابك. أي نشاط يتم تحت حسابك يقع تحت مسؤوليتك. نحتفظ بحق تعليق أو إنهاء الحساب في حال مخالفة الشروط.</p>
        </div>

        <div class="legal-section">
            <h2>٦. إخلاء المسؤولية</h2>
            <p>الخدمة تُقدّم "كما هي". نحن نبذل جهداً معقولاً لضمان استمرارية وجودة الخدمة، لكننا لا نضمن عدم انقطاع أو أخطاء تقنية. المحتوى لأغراض تعليمية ودعوية ولا يُعدّ بديلاً عن استشارة أهل العلم عند الحاجة.</p>
        </div>

        <div class="legal-section">
            <h2>٧. التعديلات على الشروط</h2>
            <p>قد نحدّث شروط الخدمة من وقت لآخر. سننشر النسخة المحدثة على هذه الصفحة. استمرارك في استخدام الخدمة بعد التحديث يعني موافقتك على الشروط الجديدة.</p>
        </div>

        <div class="legal-section">
            <h2>٨. القانون الحاكم والتواصل</h2>
            <p>تخضع هذه الشروط للقوانين المعمول بها في بلد مقدم الخدمة. لأي استفسار بخصوص الشروط، يرجى التواصل معنا عبر الموقع أو قنوات التواصل الرسمية.</p>
        </div>

        <p class="legal-updated">© المناجاة. جميع الحقوق محفوظة.</p>
    </div>
</div>
@endsection
