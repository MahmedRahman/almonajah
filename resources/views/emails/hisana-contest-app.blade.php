<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد التسجيل في مسابقة الحصانة</title>
</head>
<body style="margin:0;padding:0;background:#f0fdfa;font-family:Tahoma,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f0fdfa;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #ccfbf1;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0d9488,#0f766e);padding:24px 20px;text-align:center;color:#fff;">
                            <div style="font-size:20px;font-weight:700;margin-bottom:6px;">تم تسجيلك بنجاح</div>
                            <div style="font-size:14px;opacity:.95;">مسابقة تطبيق الحصانة</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 22px;line-height:1.8;font-size:15px;">
                            <p style="margin:0 0 12px;">السلام عليكم{{ !empty($name) ? ' ' . e($name) : '' }}،</p>
                            <p style="margin:0 0 12px;">تم تسجيلك في مسابقة تطبيق <strong>الحصانة</strong> بنجاح، واستلمنا إجابتك.</p>
                            <p style="margin:0 0 12px;">سوف نعلن عن نتائج المسابقة في الموعد المحدد يوم <strong>{{ $resultsDate }}</strong> بإذن الله تعالى.</p>
                            <p style="margin:0 0 12px;">من شروط المسابقة تحميل التطبيق عبر الرابط التالي، وإبقاؤه مثبتًا على هاتفك حتى إعلان النتائج:</p>
                            <p style="margin:18px 0;text-align:center;">
                                <a href="{{ $appStoreUrl }}" style="display:inline-block;background:#0d9488;color:#fff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:999px;">
                                    تحميل تطبيق الحصانة
                                </a>
                            </p>
                            <p style="margin:0 0 12px;font-size:13px;color:#64748b;word-break:break-all;">
                                أو انسخ الرابط:<br>
                                <a href="{{ $appStoreUrl }}" style="color:#0f766e;">{{ $appStoreUrl }}</a>
                            </p>
                            <p style="margin:16px 0 0;font-size:14px;color:#475569;">
                                مدة المسابقة شهر واحد.<br>
                                الجائزة: {{ $prize }} لأول {{ $winnersCount }} فائزين بإذن الله.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 22px 22px;text-align:center;font-size:12px;color:#94a3b8;">
                            منصة المناجاة ·
                            <a href="{{ $contestUrl }}" style="color:#0f766e;text-decoration:none;">صفحة المسابقة</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
