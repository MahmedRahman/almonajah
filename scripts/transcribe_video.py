#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
import os

# محاولة استيراد whisper
try:
    import whisper
except ImportError:
    # محاولة استيراد مباشرة
    try:
        import importlib.util
        spec = importlib.util.find_spec("whisper")
        if spec is None:
            raise ImportError("whisper module not found")
        import whisper
    except Exception as e:
        print(f"ERROR: فشل في استيراد whisper: {str(e)}", flush=True)
        print(f"ERROR: Python path: {sys.path}", flush=True)
        print(f"ERROR: Python executable: {sys.executable}", flush=True)
        sys.exit(1)

import json

# الحصول على المسار من argument
if len(sys.argv) < 2:
    print("ERROR: يجب توفير مسار الفيديو")
    sys.exit(1)

VIDEO_PATH = sys.argv[1]
BASE_PATH = sys.argv[2] if len(sys.argv) > 2 else "/var/www/html/storage/app/public"
VIDEO_ID = sys.argv[3] if len(sys.argv) > 3 else None

# بناء المسار الكامل
# VIDEO_PATH يأتي من relative_path في قاعدة البيانات (مثل: "assets/2025/565/master.mp4")
# أو قد يكون مساراً كاملاً بالفعل
if os.path.isabs(VIDEO_PATH):
    # مسار مطلق - نستخدمه كما هو
    full_video_path = VIDEO_PATH
else:
    # المسار النسبي - نضيف BASE_PATH
    full_video_path = os.path.join(BASE_PATH, VIDEO_PATH)

# تنظيف المسار (إزالة المسارات المكررة)
full_video_path = os.path.normpath(full_video_path)

if not os.path.exists(full_video_path):
    print(f"ERROR: الملف غير موجود: {full_video_path}")
    print(f"ERROR: VIDEO_PATH الممرر: {VIDEO_PATH}")
    print(f"ERROR: BASE_PATH: {BASE_PATH}")
    sys.exit(1)

print(f"INFO: معالجة الملف: {full_video_path}", flush=True)
if VIDEO_ID:
    print(f"INFO: رقم الفيديو (ID): {VIDEO_ID}", flush=True)

# إنشاء مجلد الإخراج في نفس مجلد الفيديو
# إذا كان الفيديو في storage (assets/), ننشئ مجلد captions في نفس فولدر الفيديو
# وإلا نستخدم الهيكل القديم: {ID}/subtitle/
video_dir = os.path.dirname(full_video_path)
if "assets" in full_video_path and VIDEO_ID:
    # الفيديو في storage - ننشئ مجلد captions في نفس فولدر الفيديو
    OUT_DIR = os.path.join(video_dir, "captions")
elif VIDEO_ID:
    # الفيديو خارج storage - نستخدم الهيكل القديم
    OUT_DIR = os.path.join(video_dir, str(VIDEO_ID), "subtitle")
else:
    # إذا لم يكن هناك ID، نستخدم whisper_output كبديل
    OUT_DIR = os.path.join(video_dir, "whisper_output")
os.makedirs(OUT_DIR, exist_ok=True)
# التأكد من الصلاحيات
os.chmod(OUT_DIR, 0o775)
print(f"INFO: مجلد الإخراج: {OUT_DIR}", flush=True)
print(f"INFO: صلاحيات المجلد: {oct(os.stat(OUT_DIR).st_mode)[-3:]}", flush=True)

# تعيين مسار cache لـ Whisper إلى مجلد داخل المشروع (يمكن للمستخدم الكتابة فيه)
# استخدام مسار نسبي داخل المشروع بدلاً من مسار Docker الثابت
# نحسب المسار بناءً على BASE_PATH الممرر
if BASE_PATH:
    # استخدام storage/.whisper_cache داخل المشروع
    storage_dir = os.path.dirname(os.path.dirname(BASE_PATH)) if "storage" in BASE_PATH else BASE_PATH
    whisper_cache_dir = os.path.join(storage_dir, ".whisper_cache")
else:
    # إذا لم يكن BASE_PATH موجوداً، نستخدم مجلد storage في المشروع الحالي
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(script_dir)
    whisper_cache_dir = os.path.join(project_root, "storage", ".whisper_cache")

os.makedirs(whisper_cache_dir, exist_ok=True)
# التأكد من الصلاحيات
try:
    os.chmod(whisper_cache_dir, 0o775)
except:
    pass  # تجاهل خطأ الصلاحيات إذا لم نستطع تغييرها

# تعيين متغيرات البيئة لـ Whisper لاستخدام مجلد cache مخصص
if BASE_PATH:
    storage_dir = os.path.dirname(os.path.dirname(BASE_PATH)) if "storage" in BASE_PATH else BASE_PATH
    os.environ["XDG_CACHE_HOME"] = storage_dir
    os.environ["HOME"] = storage_dir
else:
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(script_dir)
    storage_path = os.path.join(project_root, "storage")
    os.environ["XDG_CACHE_HOME"] = storage_path
    os.environ["HOME"] = storage_path

print(f"INFO: مسار cache لـ Whisper: {whisper_cache_dir}", flush=True)

try:
    # ✅ load model (base - أصغر وأسرع من medium)
    # استخدام download_root لتحديد مكان حفظ النماذج
    # يمكن تغيير "base" إلى "small" أو "medium" حسب الحاجة
    # base: ~74MB, small: ~244MB, medium: ~769MB, large: ~1550MB
    model_name = os.environ.get("WHISPER_MODEL", "base")
    print(f"INFO: جاري تحميل النموذج: {model_name} (قد يستغرق بضع دقائق للمرة الأولى)...", flush=True)
    print(f"INFO: حجم النموذج المتوقع: base=74MB, small=244MB, medium=769MB, large=1550MB", flush=True)
    
    # تحميل النموذج - سيتم عرض شريط التقدم تلقائياً
    model = whisper.load_model(model_name, download_root=whisper_cache_dir)
    print(f"INFO: ✅ تم تحميل النموذج بنجاح", flush=True)
    
    # ✅ transcribe Arabic with timestamps
    print("🔄 جاري استخراج النص من الفيديو...", flush=True)
    print(f"INFO: هذا قد يستغرق وقتاً طويلاً حسب طول الفيديو...", flush=True)
    result = model.transcribe(full_video_path, language="ar")
    print(f"INFO: ✅ تم استخراج النص بنجاح", flush=True)
    
    base_name = os.path.splitext(os.path.basename(full_video_path))[0]
    
    # 1) JSON كامل (فيه segments بالتوقيت)
    json_path = os.path.join(OUT_DIR, f"{base_name}.json")
    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    
    # 2) TXT (نص كامل بدون توقيت)
    txt_path = os.path.join(OUT_DIR, f"{base_name}.txt")
    with open(txt_path, "w", encoding="utf-8") as f:
        f.write(result["text"].strip() + "\n")
    
    # 3) TXT مع توقيت لكل جملة (سهل القراءة)
    timed_txt_path = os.path.join(OUT_DIR, f"{base_name}_timed.txt")
    with open(timed_txt_path, "w", encoding="utf-8") as f:
        for seg in result["segments"]:
            start = seg["start"]
            end = seg["end"]
            text = (seg["text"] or "").strip()
            f.write(f"[{start:.2f} --> {end:.2f}] {text}\n")
    
    # إرجاع النص فقط (لإضافته في قاعدة البيانات)
    transcription_text = result["text"].strip()
    
    print("SUCCESS", flush=True)
    print("TRANSCRIPTION_START", flush=True)
    print(transcription_text, flush=True)
    print("TRANSCRIPTION_END", flush=True)
    print(f"JSON: {json_path}", flush=True)
    print(f"TXT: {txt_path}", flush=True)
    print(f"TIMED_TXT: {timed_txt_path}", flush=True)
    
except Exception as e:
    import traceback
    print(f"ERROR: {str(e)}", flush=True)
    print(f"TRACEBACK: {traceback.format_exc()}", flush=True)
    sys.exit(1)
