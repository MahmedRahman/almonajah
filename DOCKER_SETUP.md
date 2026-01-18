# إعداد Docker للمشروع

هذا الدليل يشرح كيفية تشغيل المشروع باستخدام Docker.

## المتطلبات

- Docker Desktop (أو Docker Engine + Docker Compose)
- Git

## الملفات المطلوبة

### 1. إنشاء ملف `.env`

انسخ محتوى `.env.example` إلى `.env`:

```bash
cp .env.example .env
```

أو أنشئ ملف `.env` يدوياً مع الإعدادات التالية:

```env
APP_NAME="المناجاة"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration (Docker)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=almonajah
DB_USERNAME=app_user
DB_PASSWORD=app_password
DB_ROOT_PASSWORD=root_password

# Redis Configuration (Docker)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# DeepSeek API Configuration
DEEPSEEK_API_KEY=your_api_key_here

# Docker Ports
APP_PORT=80
PHPMYADMIN_PORT=8081
```

## تشغيل المشروع

### 1. بناء وتشغيل الحاويات

```bash
docker-compose up -d --build
```

هذا الأمر سيقوم بـ:
- بناء Laravel application container
- تشغيل MySQL database
- تشغيل Redis cache
- تشغيل Nginx web server
- تشغيل phpMyAdmin

### 2. تثبيت التبعيات

```bash
docker-compose exec app composer install
```

### 3. إنشاء Application Key

```bash
docker-compose exec app php artisan key:generate
```

### 4. تشغيل Migrations

```bash
docker-compose exec app php artisan migrate
```

### 5. إنشاء رابط التخزين

```bash
docker-compose exec app php artisan storage:link
```

### 6. تعيين الصلاحيات

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## الوصول إلى الخدمات

- **الموقع**: http://localhost
- **phpMyAdmin**: http://localhost:8081
  - Server: `mysql`
  - Username: `app_user` أو `root`
  - Password: `app_password` أو `root_password`

## الأوامر المفيدة

### عرض السجلات (Logs)

```bash
# جميع السجلات
docker-compose logs -f

# سجلات تطبيق Laravel
docker-compose logs -f app

# سجلات Nginx
docker-compose logs -f nginx

# سجلات MySQL
docker-compose logs -f mysql
```

### تنفيذ أوامر Artisan

```bash
docker-compose exec app php artisan [command]
```

مثال:
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:list
docker-compose exec app php artisan tinker
```

### الوصول إلى Shell داخل Container

```bash
docker-compose exec app sh
```

### إيقاف الحاويات

```bash
docker-compose stop
```

### إيقاف وحذف الحاويات

```bash
docker-compose down
```

### إيقاف وحذف الحاويات مع Volumes (سيحذف قاعدة البيانات!)

```bash
docker-compose down -v
```

### إعادة بناء الحاويات

```bash
docker-compose up -d --build --force-recreate
```

## البنية

المشروع يتكون من الحاويات التالية:

1. **app** - Laravel Application (PHP 8.2-FPM)
   - Port: 9000 (internal)
   - Volumes: المشروع الكامل + storage

2. **nginx** - Nginx Web Server
   - Port: 80
   - يعمل كـ reverse proxy لـ PHP-FPM

3. **mysql** - MySQL 8.0 Database
   - Port: 3306
   - Volume: mysql_data (persistent storage)

4. **redis** - Redis Cache
   - Port: 6379
   - Volume: redis_data (persistent storage)

5. **phpmyadmin** - phpMyAdmin
   - Port: 8081
   - للوصول إلى قاعدة البيانات

## استكشاف الأخطاء

### المشكلة: الحاوية لا تبدأ

```bash
# تحقق من السجلات
docker-compose logs [service_name]

# تحقق من حالة الحاويات
docker-compose ps
```

### المشكلة: خطأ في الاتصال بقاعدة البيانات

تأكد من:
1. أن MySQL container يعمل: `docker-compose ps mysql`
2. أن `DB_HOST=mysql` في ملف `.env`
3. انتظر بضع ثوانٍ حتى يبدأ MySQL تماماً

### المشكلة: خطأ في الصلاحيات

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### المشكلة: التغييرات لا تظهر

```bash
# مسح الكاش
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear
```

## الإنتاج (Production)

للاستخدام في الإنتاج:

1. قم بتغيير `APP_ENV=production` و `APP_DEBUG=false` في `.env`
2. قم بتغيير `APP_URL` إلى عنوان النطاق الخاص بك
3. قم بتعيين `APP_KEY` بشكل آمن
4. قم بتعطيل phpMyAdmin أو حمايته
5. استخدم SSL/TLS (HTTPS)
6. قم بتعيين كلمات مرور قوية لقاعدة البيانات

## ملاحظات

- جميع البيانات في Volumes محفوظة حتى بعد إيقاف الحاويات
- لتغيير المنافذ، قم بتعديل `APP_PORT` و `PHPMYADMIN_PORT` في `.env`
- لتغيير إعدادات PHP، قم بتعديل `docker/php/php.ini`
- لتغيير إعدادات Nginx، قم بتعديل `docker/nginx/default.conf`


