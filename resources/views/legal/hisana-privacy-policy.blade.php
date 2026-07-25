@extends('layouts.landing')

@section('title', 'سياسة الخصوصية | تطبيق الحصانة')

@section('meta')
<meta name="description" content="سياسة الخصوصية لتطبيق الحصانة — أذكار المسلم في يومه وليلته. توضح كيفية جمع واستخدام وحماية بياناتك.">
<meta name="robots" content="index,follow">
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
.legal-links { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
.legal-links a { color: #0d9488; font-weight: 600; text-decoration: none; }
.legal-links a:hover { color: #0f766e; text-decoration: underline; }
</style>
@endpush

<div class="legal-page">
    <div class="container">
        <a href="{{ route('landing.hisana') }}" class="legal-back"><i class="bi bi-arrow-right"></i> العودة إلى صفحة الحصانة</a>

        <div class="legal-hero">
            <h1>سياسة الخصوصية</h1>
            <p class="sub">تطبيق الحصانة — أذكار المسلم في يومه وليلته</p>
            <p class="sub">آخر تحديث: 26 يوليو 2026</p>
        </div>

        <div class="legal-section">
            <h2>١. المقدمة</h2>
            <p>
                مرحباً بك في تطبيق <strong>الحصانة</strong> («التطبيق»)، أحد منتجات منصة <strong>المناجاة</strong>.
                نحن نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية.
                توضح هذه السياسة أنواع المعلومات التي قد نجمعها عند استخدامك للتطبيق، وكيف نستخدمها ونحميها، وما هي حقوقك بخصوصها.
            </p>
            <p>
                باستخدامك لتطبيق الحصانة، فإنك توافق على الممارسات الموضحة في هذه السياسة.
                إن لم توافق عليها، يرجى التوقف عن استخدام التطبيق.
            </p>
        </div>

        <div class="legal-section">
            <h2>٢. مسؤول الخدمة</h2>
            <p>
                مقدم الخدمة هو منصة المناجاة الرقمية.
                الموقع الرسمي: <a href="{{ url('/') }}">{{ url('/') }}</a>
            </p>
            <p>
                للاستفسارات المتعلقة بالخصوصية يمكنك التواصل عبر البريد:
                <a href="mailto:admin@almonajah.com">admin@almonajah.com</a>
            </p>
        </div>

        <div class="legal-section">
            <h2>٣. المعلومات التي نجمعها</h2>
            <p>قد يجمع التطبيق أو يعالج الأنواع التالية من المعلومات:</p>
            <ul>
                <li>
                    <strong>بيانات الاستخدام المحلية:</strong>
                    مثل التفضيلات، الإعدادات، تقدم القراءة/الأذكار، والإشعارات المحلية — غالباً تُحفظ على جهازك فقط.
                </li>
                <li>
                    <strong>معلومات الجهاز التقنية:</strong>
                    نوع الجهاز، نظام التشغيل، إصدار التطبيق، ومعرّفات تقنية عامة عند الحاجة لضمان عمل التطبيق واستقراره.
                </li>
                <li>
                    <strong>بيانات التشخيص والأعطال:</strong>
                    معلومات مجمّعة أو مجهولة الهوية للمساعدة في اكتشاف الأخطاء وتحسين الأداء (إن تم تفعيلها).
                </li>
                <li>
                    <strong>بيانات الحساب (إن وُجدت لاحقاً):</strong>
                    مثل البريد الإلكتروني أو اسم العرض عند التسجيل أو تسجيل الدخول — فقط إذا أضفنا ميزة حساب مستخدم.
                </li>
            </ul>
            <p>
                لا نطلب منك إدخال بيانات مالية، ولا نجمع بيانات حسّاسة غير ضرورية لاستخدام التطبيق.
            </p>
        </div>

        <div class="legal-section">
            <h2>٤. كيفية استخدام المعلومات</h2>
            <p>نستخدم المعلومات للأغراض التالية فقط:</p>
            <ul>
                <li>تشغيل التطبيق وتقديم محتوى الأذكار والأدعية.</li>
                <li>حفظ تفضيلاتك وتحسين تجربتك داخل التطبيق.</li>
                <li>إرسال تذكيرات محلية (إن فعّلتها أنت).</li>
                <li>تحليل الاستخدام بشكل مجمّع لتحسين الجودة والاستقرار.</li>
                <li>الامتثال للمتطلبات القانونية وحماية حقوقنا والمستخدمين.</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2>٥. مشاركة البيانات مع أطراف ثالثة</h2>
            <p>
                <strong>لا نبيع بياناتك الشخصية.</strong>
                قد نستخدم مزودي خدمات تقنيين (مثل استضافة الخوادم، توزيع التطبيق عبر Google Play، أو أدوات التحليلات/الأعطال)
                لمعالجة بيانات محدودة بالنيابة عنا، وفق التزامات السرية والقانون.
            </p>
            <p>
                عند تحميلك للتطبيق من Google Play، فإن سياسة خصوصية Google واتفاقياتها تنطبق أيضاً على عمليات المتجر والتوزيع.
            </p>
        </div>

        <div class="legal-section">
            <h2>٦. الإعلانات والتتبّع</h2>
            <p>
                إذا عرض التطبيق إعلانات أو استخدم أدوات قياس، فسنوضّح ذلك داخل التطبيق وفي تحديث لهذه السياسة.
                يمكنك التحكم في بعض خيارات التتبّع عبر إعدادات جهازك ونظام Android.
            </p>
        </div>

        <div class="legal-section">
            <h2>٧. تخزين البيانات والأمان</h2>
            <p>
                نطبق إجراءات أمنية مناسبة لحماية أي بيانات تُنقل أو تُخزَّن عبر أنظمتنا.
                مع ذلك، لا توجد وسيلة نقل أو تخزين إلكتروني آمنة بنسبة 100%، لذلك لا يمكن ضمان الأمان المطلق.
            </p>
            <p>
                البيانات المحفوظة محلياً على جهازك تبقى تحت سيطرتك، ويمكن حذفها عادةً بإزالة بيانات التطبيق أو إلغاء تثبيته.
            </p>
        </div>

        <div class="legal-section">
            <h2>٨. الاحتفاظ بالبيانات</h2>
            <p>
                نحتفظ بالبيانات الشخصية فقط للمدة اللازمة لتحقيق الأغراض المذكورة في هذه السياسة، أو حسب ما يقتضيه القانون.
                يمكنك طلب حذف بياناتك المرتبطة بحسابك (إن وُجد) عبر التواصل معنا.
            </p>
        </div>

        <div class="legal-section">
            <h2>٩. حقوقك</h2>
            <p>بحسب القوانين المعمول بها، قد يكون لديك الحق في:</p>
            <ul>
                <li>الاطلاع على بياناتك الشخصية.</li>
                <li>طلب تصحيحها أو تحديثها.</li>
                <li>طلب حذفها أو تقييد معالجتها.</li>
                <li>الاعتراض على بعض أنواع المعالجة.</li>
            </ul>
            <p>
                لممارسة هذه الحقوق، راسلنا على
                <a href="mailto:admin@almonajah.com">admin@almonajah.com</a>
                مع توضيح طلبك واسم التطبيق (الحصانة).
            </p>
        </div>

        <div class="legal-section">
            <h2>١٠. خصوصية الأطفال</h2>
            <p>
                تطبيق الحصانة محتوى ديني وتعليمي عام، ولا يستهدف جمع بيانات شخصية من الأطفال عن عمد.
                إذا كنت ولي أمر وترى أن طفلاً قدّم لنا بيانات شخصية دون موافقة مناسبة، تواصل معنا لحذفها.
            </p>
        </div>

        <div class="legal-section">
            <h2>١١. الأذونات على جهاز Android</h2>
            <p>قد يطلب التطبيق بعض الأذونات حسب الميزات، مثل:</p>
            <ul>
                <li>الإشعارات: لتذكيرك بالأذكار في الأوقات التي تختارها.</li>
                <li>التخزين/الوسائط: فقط إذا لزم حفظ ملفات صوت أو محتوى محلي.</li>
                <li>الإنترنت: لتحميل التحديثات أو المحتوى عند الحاجة.</li>
            </ul>
            <p>يمكنك رفض أو إيقاف هذه الأذونات من إعدادات النظام، مع ملاحظة أن بعض الميزات قد تتوقف عن العمل.</p>
        </div>

        <div class="legal-section">
            <h2>١٢. التعديلات على هذه السياسة</h2>
            <p>
                قد نحدّث سياسة الخصوصية من وقت لآخر.
                سننشر النسخة المحدّثة على هذه الصفحة مع تاريخ آخر تحديث.
                استمرارك في استخدام التطبيق بعد التحديث يعني موافقتك على السياسة الجديدة.
            </p>
        </div>

        <div class="legal-section">
            <h2>١٣. التواصل معنا</h2>
            <p>لأي استفسار بخصوص الخصوصية أو بياناتك في تطبيق الحصانة:</p>
            <ul>
                <li>البريد الإلكتروني: <a href="mailto:admin@almonajah.com">admin@almonajah.com</a></li>
                <li>الموقع: <a href="{{ url('/') }}">{{ url('/') }}</a></li>
                <li>صفحة التطبيق: <a href="{{ route('landing.hisana') }}">{{ route('landing.hisana') }}</a></li>
            </ul>
        </div>

        <div class="legal-links">
            <a href="{{ route('landing.hisana') }}">صفحة الحصانة</a>
            <span>·</span>
            <a href="{{ route('legal.privacy') }}">سياسة خصوصية المناجاة</a>
            <span>·</span>
            <a href="{{ route('legal.terms') }}">شروط الخدمة</a>
        </div>

        <p class="legal-updated">© المناجاة — تطبيق الحصانة. جميع الحقوق محفوظة.</p>
    </div>
</div>
@endsection
