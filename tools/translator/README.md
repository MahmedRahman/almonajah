# مترجم ملفات SRT — المناجاة

أداة داخلية منفصلة (Node/Express) لترجمة ملفات الترجمة العربية إلى عدة لغات عبر DeepSeek.

## الرابط على الإنتاج

https://almonajah.com/translator/

## التشغيل المحلي

```bash
cd tools/translator
cp .env.example .env
# ضع DEEPSEEK_API_KEY في .env
npm install
npm start
```

يفتح على `http://127.0.0.1:3010` (أو قيمة `PORT`).

## الإنتاج

- المسار على السيرفر: `/var/www/almonajah/tools/translator`
- الخدمة: `almonajah-translation.service`
- Nginx يعيد توجيه `/translator/` إلى `127.0.0.1:3010`
- ملف `.env` على السيرفر فقط (لا يُرفع على Git)

بعد تحديث الكود عبر `deploy.sh` يتم إعادة تشغيل الخدمة تلقائيًا.
