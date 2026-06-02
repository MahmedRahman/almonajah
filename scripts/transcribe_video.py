#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
استخراج النص من الفيديو.
- يفضّل faster-whisper (أسرع 3–5× على CPU): pip install faster-whisper
- وإلا openai-whisper مع إعدادات سرعة
"""
import json
import os
import sys
import warnings

warnings.filterwarnings("ignore", category=UserWarning)

VIDEO_PATH = sys.argv[1] if len(sys.argv) > 1 else None
BASE_PATH = sys.argv[2] if len(sys.argv) > 2 else "/var/www/html/storage/app/public"
VIDEO_ID = sys.argv[3] if len(sys.argv) > 3 else None
MODEL_ARG = sys.argv[4] if len(sys.argv) > 4 else None

if not VIDEO_PATH:
    print("ERROR: يجب توفير مسار الفيديو", flush=True)
    sys.exit(1)

if os.path.isabs(VIDEO_PATH):
    full_video_path = VIDEO_PATH
else:
    full_video_path = os.path.join(BASE_PATH, VIDEO_PATH)

full_video_path = os.path.normpath(full_video_path)

if not os.path.exists(full_video_path):
    print(f"ERROR: الملف غير موجود: {full_video_path}", flush=True)
    sys.exit(1)

print(f"INFO: معالجة الملف: {full_video_path}", flush=True)
if VIDEO_ID:
    print(f"INFO: رقم الفيديو (ID): {VIDEO_ID}", flush=True)

video_dir = os.path.dirname(full_video_path)
if "assets" in full_video_path and VIDEO_ID:
    OUT_DIR = os.path.join(video_dir, "captions")
elif VIDEO_ID:
    OUT_DIR = os.path.join(video_dir, str(VIDEO_ID), "subtitle")
else:
    OUT_DIR = os.path.join(video_dir, "whisper_output")

os.makedirs(OUT_DIR, exist_ok=True)
try:
    os.chmod(OUT_DIR, 0o775)
except OSError:
    pass

print(f"INFO: مجلد الإخراج: {OUT_DIR}", flush=True)

if BASE_PATH:
    storage_dir = os.path.dirname(os.path.dirname(BASE_PATH)) if "storage" in BASE_PATH else BASE_PATH
    whisper_cache_dir = os.path.join(storage_dir, ".whisper_cache")
else:
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(script_dir)
    whisper_cache_dir = os.path.join(project_root, "storage", ".whisper_cache")

os.makedirs(whisper_cache_dir, exist_ok=True)
try:
    os.chmod(whisper_cache_dir, 0o775)
except OSError:
    pass

if BASE_PATH:
    storage_dir = os.path.dirname(os.path.dirname(BASE_PATH)) if "storage" in BASE_PATH else BASE_PATH
    os.environ["XDG_CACHE_HOME"] = storage_dir
    os.environ["HOME"] = storage_dir

print(f"INFO: مسار cache: {whisper_cache_dir}", flush=True)

valid_models = ("tiny", "base", "small", "medium", "large", "large-v2", "large-v3")
if MODEL_ARG and MODEL_ARG.strip().lower() in valid_models:
    model_name = MODEL_ARG.strip().lower()
else:
    model_name = os.environ.get("WHISPER_MODEL", "tiny")
if model_name not in valid_models:
    model_name = "tiny"

model_sizes = {
    "tiny": "~39MB",
    "base": "~74MB",
    "small": "~244MB",
    "medium": "~769MB",
}


def save_outputs(result, base_name):
    json_path = os.path.join(OUT_DIR, f"{base_name}.json")
    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)

    txt_path = os.path.join(OUT_DIR, f"{base_name}.txt")
    transcription_text = (result.get("text") or "").strip()
    with open(txt_path, "w", encoding="utf-8") as f:
        f.write(transcription_text + "\n")

    timed_txt_path = os.path.join(OUT_DIR, f"{base_name}_timed.txt")
    with open(timed_txt_path, "w", encoding="utf-8") as f:
        for seg in result.get("segments") or []:
            start = seg.get("start", 0)
            end = seg.get("end", 0)
            text = (seg.get("text") or "").strip()
            f.write(f"[{start:.2f} --> {end:.2f}] {text}\n")

    print("SUCCESS", flush=True)
    print("TRANSCRIPTION_START", flush=True)
    print(transcription_text, flush=True)
    print("TRANSCRIPTION_END", flush=True)
    print(f"JSON: {json_path}", flush=True)
    print(f"TXT: {txt_path}", flush=True)
    print(f"TIMED_TXT: {timed_txt_path}", flush=True)


def transcribe_faster_whisper():
    from faster_whisper import WhisperModel

    print("INFO: محرك faster-whisper (int8) — الأسرع على CPU", flush=True)
    print(f"INFO: جاري تحميل النموذج: {model_name} ({model_sizes.get(model_name, '')})", flush=True)
    print("PROGRESS:8:loading_model", flush=True)

    model = WhisperModel(
        model_name,
        device="cpu",
        compute_type="int8",
        download_root=whisper_cache_dir,
    )
    print("INFO: ✅ تم تحميل النموذج", flush=True)
    print("PROGRESS:22:model_loaded", flush=True)

    print("🔄 جاري استخراج النص...", flush=True)
    print("PROGRESS:28:transcribing", flush=True)

    segments_iter, _info = model.transcribe(
        full_video_path,
        language="ar",
        beam_size=1,
        best_of=1,
        vad_filter=True,
        condition_on_previous_text=False,
    )

    segments_list = []
    text_parts = []
    for seg in segments_iter:
        segments_list.append({
            "start": float(seg.start),
            "end": float(seg.end),
            "text": seg.text or "",
        })
        text_parts.append(seg.text or "")

    print("INFO: ✅ تم استخراج النص", flush=True)
    print("PROGRESS:90:saving", flush=True)

    return {
        "text": "".join(text_parts).strip(),
        "segments": segments_list,
    }


def transcribe_openai_whisper():
    import whisper

    print("INFO: محرك openai-whisper (fp32 على CPU)", flush=True)
    print("INFO: للتسريع ثبّت: pip install faster-whisper", flush=True)
    print(f"INFO: جاري تحميل النموذج: {model_name} ({model_sizes.get(model_name, '')})", flush=True)
    print("PROGRESS:8:loading_model", flush=True)

    model = whisper.load_model(model_name, download_root=whisper_cache_dir)
    print("INFO: ✅ تم تحميل النموذج", flush=True)
    print("PROGRESS:22:model_loaded", flush=True)

    print("🔄 جاري استخراج النص...", flush=True)
    print("PROGRESS:28:transcribing", flush=True)

    result = model.transcribe(
        full_video_path,
        language="ar",
        fp16=False,
        beam_size=1,
        best_of=1,
        temperature=0,
        condition_on_previous_text=False,
        word_timestamps=False,
    )
    print("INFO: ✅ تم استخراج النص", flush=True)
    print("PROGRESS:90:saving", flush=True)

    return result


try:
    base_name = os.path.splitext(os.path.basename(full_video_path))[0]

    try:
        result = transcribe_faster_whisper()
    except ImportError:
        result = transcribe_openai_whisper()

    save_outputs(result, base_name)

except Exception as e:
    import traceback

    print(f"ERROR: {str(e)}", flush=True)
    print(f"TRACEBACK: {traceback.format_exc()}", flush=True)
    sys.exit(1)
