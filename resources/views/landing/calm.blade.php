@extends('layouts.landing')

@section('title', 'دعوة غيب — دعاء لحالتك | المناجاة')

@section('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="دعوة غيب: بماذا تشعر الآن؟ اختر حالتك واحصل على دعاء ومحتوى مناسب من منصة المناجاة — نصًا وصوتًا.">
<meta name="robots" content="index,follow">
@endsection

@section('content')
@push('styles')
<style>
.calm-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at top, rgba(13, 148, 136, 0.08), transparent 55%),
        linear-gradient(180deg, #f8fafc 0%, #f0fdfa 40%, #ffffff 100%);
    color: #334155;
    padding: 1.25rem 1rem 3rem;
}
.calm-top {
    max-width: 720px;
    margin: 0 auto 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}
.calm-brand {
    color: #0d9488;
    font-weight: 700;
    text-decoration: none;
    font-size: 1rem;
}
.calm-brand:hover { color: #0f766e; }
.calm-hero {
    text-align: center;
    max-width: 640px;
    margin: 0 auto 1.75rem;
    padding-top: 1rem;
}
.calm-hero h1 {
    font-size: clamp(2rem, 6vw, 2.75rem);
    font-weight: 700;
    color: #0f766e;
    margin-bottom: 0.65rem;
}
.calm-hero .ayah {
    color: #64748b;
    font-size: 1.05rem;
    margin-bottom: 0.75rem;
}
.calm-hero .lead {
    color: #475569;
    font-size: 1rem;
    line-height: 1.7;
    max-width: 34rem;
    margin: 0 auto;
}
.calm-card {
    max-width: 640px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    padding: 1.35rem 1.25rem 1.25rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}
.calm-card h2 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f766e;
    margin-bottom: 0.85rem;
}
.calm-textarea {
    width: 100%;
    min-height: 110px;
    resize: vertical;
    border: 1px solid #e2e8f0;
    border-radius: 0.9rem;
    padding: 0.9rem 1rem;
    background: #f8fafc;
    color: #1e293b;
    font-family: inherit;
    font-size: 1rem;
    line-height: 1.7;
}
.calm-textarea:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    background: #fff;
}
.calm-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.55rem;
    font-size: 0.82rem;
    color: #94a3b8;
}
.calm-privacy {
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0.85rem 0 1rem;
}
.calm-submit {
    width: 100%;
    border: none;
    border-radius: 999px;
    padding: 0.9rem 1.25rem;
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}
.calm-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(13, 148, 136, 0.28);
}
.calm-submit:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.calm-chips-wrap {
    max-width: 640px;
    margin: 1.25rem auto 0;
    text-align: center;
}
.calm-chips-label {
    color: #64748b;
    font-size: 0.92rem;
    margin-bottom: 0.7rem;
}
.calm-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.55rem;
}
.calm-chip {
    border: 1.5px solid #99f6e4;
    background: #fff;
    color: #0f766e;
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.calm-chip:hover,
.calm-chip.is-active {
    background: #ccfbf1;
    border-color: #0d9488;
    color: #115e59;
}
.calm-result {
    display: none;
    max-width: 640px;
    margin: 1.5rem auto 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    padding: 1.35rem 1.25rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}
.calm-result.is-visible { display: block; }
.calm-result-label {
    font-size: 0.85rem;
    color: #0d9488;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.calm-result-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.calm-result-speaker {
    color: #64748b;
    font-size: 0.92rem;
    margin-bottom: 1rem;
}
.calm-excerpt {
    background: #f8fafc;
    border-right: 3px solid #0d9488;
    border-radius: 0 0.75rem 0.75rem 0;
    padding: 0.95rem 1rem;
    line-height: 1.85;
    color: #334155;
    white-space: pre-wrap;
    margin-bottom: 1rem;
}
.calm-audio {
    width: 100%;
    margin-bottom: 1rem;
}
.calm-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}
.calm-actions .btn-outline-teal {
    border: 1.5px solid #0d9488;
    color: #0f766e;
    background: #fff;
    border-radius: 999px;
    padding: 0.55rem 1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.calm-actions .btn-outline-teal:hover {
    background: #f0fdfa;
    color: #115e59;
}
.calm-actions .btn-soft {
    border: none;
    background: #ecfdf5;
    color: #0f766e;
    border-radius: 999px;
    padding: 0.55rem 1rem;
    font-weight: 600;
}
.calm-actions .btn-soft:hover { background: #d1fae5; }
.calm-error {
    display: none;
    max-width: 640px;
    margin: 1rem auto 0;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 0.85rem;
    padding: 0.85rem 1rem;
    font-size: 0.95rem;
}
.calm-error.is-visible { display: block; }
.calm-footer-note {
    text-align: center;
    margin-top: 2rem;
    color: #94a3b8;
    font-size: 0.85rem;
}
@media (max-width: 576px) {
    .calm-card, .calm-result { padding: 1.1rem 0.95rem; }
}
</style>
@endpush

<div class="calm-page">
    <div class="calm-top">
        <a href="{{ url('/') }}" class="calm-brand"><i class="bi bi-arrow-right me-1"></i>المناجاة</a>
        <a href="{{ route('audio.home') }}" class="calm-brand" style="font-weight:600;font-size:0.92rem;">المنصة الصوتية</a>
    </div>

    <div class="calm-hero">
        <h1>دعوة غيب</h1>
        <p class="ayah">﴿أُجِيبُ دَعْوَةَ ٱلدَّاعِ إِذَا دَعَانِ﴾</p>
        <p class="lead">اكتب بما تشعر، نحلّل كلامك ونختار أقرب دعاء من محتوى منصة المناجاة — مكتوبًا وصوتيًا.</p>
    </div>

    <div class="calm-card" id="calmInputCard">
        <h2>بماذا تشعر الآن؟</h2>
        <textarea id="calmFeeling" class="calm-textarea" maxlength="600" placeholder="اكتب شعورك أو همّك هنا..."></textarea>
        <div class="calm-meta">
            <span>لا نحفظ ما تكتبه مربوطًا بهويتك.</span>
            <span id="calmCounter">0/600</span>
        </div>
        <p class="calm-privacy">
            المحتوى المعروض من مكتبتنا المنشورة، وليس نصًا مولّدًا. اختر حالة أو اكتب بحرية ثم اضغط «ادعُ لي».
        </p>
        <button type="button" class="calm-submit" id="calmSubmitBtn" disabled>
            <i class="bi bi-heart-fill"></i>
            <span>ادعُ لي</span>
        </button>
    </div>

    <div class="calm-chips-wrap" id="calmChipsWrap">
        <div class="calm-chips-label">أو ابدأ من شعور:</div>
        <div class="calm-chips">
            @foreach($chips as $chip)
                <button type="button" class="calm-chip" data-chip="{{ $chip }}">{{ $chip }}</button>
            @endforeach
        </div>
    </div>

    <div class="calm-error" id="calmError"></div>

    <div class="calm-result" id="calmResult">
        <div class="calm-result-label">أقرب دعاء لحالتك: <span id="calmFeelingKey"></span></div>
        <h3 class="calm-result-title" id="calmResultTitle"></h3>
        <div class="calm-result-speaker" id="calmResultSpeaker"></div>
        <div class="calm-excerpt" id="calmExcerpt"></div>
        <audio id="calmAudio" class="calm-audio" controls preload="none" style="display:none;"></audio>
        <div class="calm-actions">
            <a href="#" id="calmDeepLink" class="btn-outline-teal" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-left"></i> افتح المحتوى كاملًا
            </a>
            <button type="button" class="btn-soft" id="calmAnotherBtn">
                <i class="bi bi-arrow-repeat"></i> دعاء آخر لنفس الحالة
            </button>
            <button type="button" class="btn-soft" id="calmResetBtn">
                <i class="bi bi-plus-circle"></i> جلسة جديدة
            </button>
        </div>
    </div>

    <p class="calm-footer-note">© المناجاة — محتوى دعوي من المكتبة المنشورة</p>
</div>

@push('scripts')
<script>
(function () {
    const feelingEl = document.getElementById('calmFeeling');
    const counterEl = document.getElementById('calmCounter');
    const submitBtn = document.getElementById('calmSubmitBtn');
    const chips = Array.from(document.querySelectorAll('.calm-chip'));
    const errorEl = document.getElementById('calmError');
    const resultEl = document.getElementById('calmResult');
    const inputCard = document.getElementById('calmInputCard');
    const chipsWrap = document.getElementById('calmChipsWrap');
    const matchUrl = @json(route('landing.calm.match'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let activeChip = null;
    let excludeIds = [];
    let lastFeeling = '';
    let lastChip = null;

    function updateCounter() {
        const len = feelingEl.value.length;
        counterEl.textContent = len + '/600';
        submitBtn.disabled = feelingEl.value.trim().length === 0 && !activeChip;
    }

    function showError(msg) {
        errorEl.textContent = msg || '';
        errorEl.classList.toggle('is-visible', !!msg);
    }

    function setLoading(loading) {
        submitBtn.disabled = loading || (feelingEl.value.trim().length === 0 && !activeChip);
        document.getElementById('calmAnotherBtn').disabled = loading;
        submitBtn.querySelector('span').textContent = loading ? 'جاري البحث...' : 'ادعُ لي';
        chips.forEach(function (c) { c.disabled = loading; });
    }

    function selectChip(chipBtn) {
        chips.forEach(function (c) { c.classList.remove('is-active'); });
        chipBtn.classList.add('is-active');
        activeChip = chipBtn.getAttribute('data-chip');
        feelingEl.value = 'أشعر ب' + activeChip;
        updateCounter();
    }

    chips.forEach(function (chipBtn) {
        chipBtn.addEventListener('click', function () {
            selectChip(chipBtn);
        });
    });

    feelingEl.addEventListener('input', function () {
        updateCounter();
        showError('');
    });

    function renderResult(data) {
        const item = data.item;
        document.getElementById('calmFeelingKey').textContent = data.feeling_key || '';
        document.getElementById('calmResultTitle').textContent = item.title || 'دعاء';
        document.getElementById('calmResultSpeaker').textContent = item.speaker_name
            ? ('المتحدث: ' + item.speaker_name)
            : '';
        document.getElementById('calmExcerpt').textContent = item.excerpt || '';

        const audio = document.getElementById('calmAudio');
        if (item.audio_url) {
            audio.src = item.audio_url;
            audio.style.display = 'block';
        } else {
            audio.removeAttribute('src');
            audio.style.display = 'none';
        }

        const deep = document.getElementById('calmDeepLink');
        deep.href = item.deep_link || '#';

        excludeIds.push(item.id);
        if (excludeIds.length > 20) excludeIds = excludeIds.slice(-20);

        resultEl.classList.add('is-visible');
        inputCard.style.opacity = '0.72';
        resultEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function requestMatch(isAnother) {
        const feeling = feelingEl.value.trim();
        if (!feeling && !activeChip) {
            showError('اكتب شعورك أو اختر حالة من القائمة.');
            return;
        }

        lastFeeling = feeling || activeChip;
        lastChip = activeChip;
        showError('');
        setLoading(true);

        try {
            const res = await fetch(matchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    feeling: lastFeeling,
                    chip: lastChip,
                    exclude_ids: isAnother ? excludeIds : []
                })
            });

            const data = await res.json().catch(function () { return null; });
            if (!res.ok || !data || !data.success) {
                const msg = (data && data.error) ? data.error : 'تعذر جلب محتوى مناسب الآن.';
                showError(msg);
                return;
            }
            if (!isAnother) excludeIds = [];
            renderResult(data);
        } catch (e) {
            showError('حدث خطأ في الاتصال. حاول مرة أخرى.');
        } finally {
            setLoading(false);
            updateCounter();
        }
    }

    submitBtn.addEventListener('click', function () {
        requestMatch(false);
    });

    document.getElementById('calmAnotherBtn').addEventListener('click', function () {
        requestMatch(true);
    });

    document.getElementById('calmResetBtn').addEventListener('click', function () {
        feelingEl.value = '';
        activeChip = null;
        excludeIds = [];
        chips.forEach(function (c) { c.classList.remove('is-active'); });
        resultEl.classList.remove('is-visible');
        inputCard.style.opacity = '1';
        showError('');
        updateCounter();
        feelingEl.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    updateCounter();
})();
</script>
@endpush
@endsection
