@extends('layouts.landing')

@section('title', 'سياسة الخصوصية | المناجاة')

@section('meta')
<meta name="description" content="سياسة الخصوصية لتطبيق المناجاة - كيفية جمع واستخدام وحماية بياناتك.">
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
            <h1>سياسة الخصوصية</h1>
            <p class="sub">تطبيق المناجاة — آخر تحديث: {{ now()->translatedFormat('d F Y') }}</p>
        </div>

        <div class="legal-section">
            <h2>١. المقدمة</h2>
            <p>مرحباً بك في تطبيق المناجاة. نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. توضح هذه السياسة كيفية جمعنا واستخدامنا وحمايتنا للمعلومات عند استخدامك للتطبيق أو الموقع.</p>
        </div>

        <div class="legal-section">
            <h2>٢. المعلومات التي نجمعها</h2>
            <p>قد نجمع الأنواع التالية من المعلومات:</p>
            <ul>
                <li><strong>معلومات الحساب:</strong> البريد الإلكتروني واسم المستخدم عند التسجيل (إن وُجد).</li>
                <li><strong>بيانات الاستخدام:</strong> تفاعلك مع المحتوى (مشاهدات، إعجابات، قوائم التشغيل) لتحسين الخدمة.</li>
                <li><strong>معلومات الجهاز:</strong> نوع الجهاز ونظام التشغيل عند الضرورة لضمان توافق التطبيق.</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>٣. كيفية استخدام المعلومات</h2>
            <p>نستخدم البيانات التي نجمعها من أجل:</p>
            <ul>
                <li>تقديم الخدمة وتحسينها (محتوى، ترجمة، بث).</li>
                <li>تخصيص تجربتك (المفضلة، التعليقات، القوائم).</li>
                <li>تحليل الاستخدام بشكل مجمّع لتحسين التطبيق.</li>
                <li>الامتثال للقانون وحماية حقوقنا.</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>٤. مشاركة البيانات مع طرف ثالث</h2>
            <p>لا نبيع بياناتك الشخصية. قد نشارك بيانات مجمّعة أو مجهولة الهوية مع شركاء تقنيين (مثل استضافة الخوادم أو خدمات التحليلات) وفق التزامات السرية والقانون.</p>
        </div>

        <div class="legal-section">
            <h2>٥. الأمان والاحتفاظ بالبيانات</h2>
            <p>نطبق إجراءات أمنية مناسبة لحماية بياناتك. نحتفظ بالبيانات طالما كان حسابك نشطاً أو كما يقتضيه القانون.</p>
        </div>

        <div class="legal-section">
            <h2>٦. حقوقك</h2>
            <p>لديك الحق في الوصول إلى بياناتك وتصحيحها أو حذفها، وتقييد المعالجة أو الاعتراض عليها وفق القوانين المعمول بها. يمكنك أيضاً إلغاء حسابك من إعدادات التطبيق أو بالاتصال بنا.</p>
        </div>

        <div class="legal-section">
            <h2>٧. التعديلات</h2>
            <p>قد نحدّث سياسة الخصوصية من وقت لآخر. سننشر النسخة المحدثة على هذه الصفحة مع تاريخ آخر تحديث. ننصحك بمراجعتها دورياً.</p>
        </div>

        <div class="legal-section">
            <h2>٨. التواصل معنا</h2>
            <p>لأي استفسار حول الخصوصية أو بياناتك، يرجى التواصل معنا عبر الموقع أو البريد الإلكتروني المذكور في صفحة التواصل.</p>
        </div>

        <p class="legal-updated">© المناجاة. جميع الحقوق محفوظة.</p>
    </div>
</div>
@endsection
