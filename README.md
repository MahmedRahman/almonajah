# المناجاة - منصة إدارة المحتوى الرقمي

منصة متكاملة لإدارة المحتوى الرقمي مبني على Laravel 10.

## المتطلبات

### للتطوير المحلي (بدون Docker)
- PHP >= 8.1
- Composer
- MySQL 5.7+ أو MariaDB 10.3+
- Node.js & NPM

### للتطوير مع Docker (موصى به)
- Docker Desktop (أو Docker Engine + Docker Compose)
- Git

## التثبيت

### الطريقة 1: استخدام Docker (موصى به)

1. استنسخ المشروع:
```bash
git clone <repository-url>
cd almonajah
```

2. أنشئ ملف `.env`:
```bash
cp .env.example .env
```

3. قم بتعديل ملف `.env` وأضف:
   - `DEEPSEEK_API_KEY` (مطلوب لاستخراج البيانات)
   - أي إعدادات أخرى مطلوبة

4. شغّل المشروع:
```bash
docker-compose up -d --build
```

5. ثبت التبعيات:
```bash
docker-compose exec app composer install
```

6. أنشئ Application Key:
```bash
docker-compose exec app php artisan key:generate
```

7. شغّل Migrations:
```bash
docker-compose exec app php artisan migrate
```

8. أنشئ رابط التخزين:
```bash
docker-compose exec app php artisan storage:link
```

9. الوصول إلى الموقع:
   - الموقع: http://localhost
   - phpMyAdmin: http://localhost:8081

**للمزيد من التفاصيل، راجع [DOCKER_SETUP.md](DOCKER_SETUP.md)**

### الطريقة 2: التثبيت المحلي (بدون Docker)

1. استنسخ المشروع:
```bash
git clone <repository-url>
cd almonajah
```

2. ثبت التبعيات:
```bash
composer install
npm install
```

3. إعداد البيئة:
```bash
cp .env.example .env
php artisan key:generate
```

4. قم بتعديل ملف `.env` وأضف بيانات قاعدة البيانات

5. تشغيل الـ Migrations:
```bash
php artisan migrate
php artisan db:seed
```

6. تشغيل المشروع:
```bash
php artisan serve
npm run dev
```

## المميزات

- لوحة تحكم لإدارة المحتوى الرقمي
- إدارة المستخدمين والصلاحيات
- إدارة المقالات والمحتوى
- إدارة الوسائط والملفات
- معالجة الفيديو والصوت (FFmpeg)
- استخراج النصوص من الفيديو (Whisper)
- تحليل المحتوى باستخدام DeepSeek API
- HLS Streaming للفيديو
- واجهة عربية متجاوبة
- دعم YouTube Shorts/TikTok-like video player
- Redis Caching للأداء العالي

## البنية التقنية

- **Backend**: Laravel 10
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Web Server**: Nginx
- **PHP**: 8.2-FPM
- **Video Processing**: FFmpeg
- **Transcription**: OpenAI Whisper
- **Streaming**: HLS (HTTP Live Streaming)

## الرخصة

MIT License


