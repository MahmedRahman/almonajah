# تحليل أداء الصفحة الرئيسية

## المشاكل المكتشفة:

### 1. **استعلامات بطيئة جداً (Critical)**
- **السطر 89-107**: `categories` - يجلب **جميع** السجلات ثم يعمل `map()`, `filter()`, `unique()`, `sort()` في PHP
- **السطر 110-127**: `years` - نفس المشكلة
- **الحل**: استخدام استعلامات SQL مباشرة بدلاً من `->get()->map()`

### 2. **استدعاء Accessors في Loop (High Priority)**
- **السطر 96 في home.blade.php**: `$asset->category` يستدعي accessor لكل asset
- **السطر 38, 77**: `$asset->duration_formatted` يستدعي accessor لكل asset
- **الحل**: حساب القيم مسبقاً في Controller أو استخدام eager loading

### 3. **استعلامات غير محسّنة (Medium Priority)**
- `speakerNames` يستخدم `pluck()` ثم `filter()` و `sort()` - يمكن تحسينه
- `stats` يستخدم `count()` مرتين - يمكن دمجهما

### 4. **مشاكل في الـ View (Low Priority)**
- استخدام `asset()` و `route()` في loop - يمكن تحسينه
- JavaScript يعمل على جميع الفيديوهات حتى غير المرئية

## الحلول المقترحة:

1. **تحسين استعلامات categories و years** - استخدام SQL مباشرة
2. **حساب category و duration_formatted مسبقاً** - في Controller
3. **تحسين استعلام speakerNames** - استخدام `groupBy` في SQL
4. **إضافة eager loading** - إذا لزم الأمر
5. **تحسين JavaScript** - استخدام Intersection Observer بشكل أفضل

