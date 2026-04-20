{{--
  مشغّل صوتي بأسلوب يشبه SoundCloud بألوان المناجاة (متغيرات public.css).
  المتغيرات: $asset, $fileUrl, $streamUrl (اختياري), $posterUrl, $useExtractedAudioForAudioPlatform, $firstExtractedAudio
--}}
@php
    $audioSrc = $streamUrl ?? $fileUrl;
    if ($useExtractedAudioForAudioPlatform && isset($firstExtractedAudio)) {
        $f = strtolower((string) $firstExtractedAudio->format);
        $scMime = match ($f) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a', 'aac' => 'audio/mp4',
            default => 'audio/mpeg',
        };
    } else {
        $ext = strtolower((string) ($asset->extension ?? 'mp3'));
        $scMime = match ($ext) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a', 'aac' => 'audio/mp4',
            default => 'audio/mpeg',
        };
    }
    $durationHint = $asset->duration_seconds;
    if (!$durationHint && $useExtractedAudioForAudioPlatform && isset($firstExtractedAudio) && $firstExtractedAudio->duration_seconds) {
        $durationHint = $firstExtractedAudio->duration_seconds;
    }
    $waveBars = [];
    for ($i = 0; $i < 96; $i++) {
        $waveBars[] = 18 + (crc32((string) $asset->id . '-' . $i) % 72);
    }
    // نفس منطق "المحتوى النصي" في لوحة التحكم: transcription_plain ثم transcription ثم site_description
    $audioTextContent = trim((string) strip_tags($asset->transcription_plain ?? ''));
    if ($audioTextContent === '' && !empty($asset->transcription)) {
        $audioTextContent = trim((string) strip_tags($asset->transcription));
    }
    if ($audioTextContent === '' && !empty($asset->site_description)) {
        $audioTextContent = trim((string) strip_tags($asset->site_description));
    }
@endphp

<div class="sc-audio-track" data-duration-hint="{{ (int) $durationHint }}">
    <div class="sc-audio-track__inner">
        <div class="sc-audio-track__art">
            <img src="{{ $posterUrl }}" alt="" class="sc-audio-track__art-img" width="640" height="360" loading="eager" decoding="async">
            <span class="sc-audio-track__art-shade" aria-hidden="true"></span>
        </div>
        <div class="sc-audio-track__body">
            <div class="sc-audio-track__topline">
                <button type="button" class="sc-audio-track__play" id="scAudioPlayBtn" aria-label="تشغيل أو إيقاف">
                    <i class="bi bi-play-fill sc-audio-track__play-icon-play" aria-hidden="true"></i>
                    <i class="bi bi-pause-fill sc-audio-track__play-icon-pause d-none" aria-hidden="true"></i>
                </button>
                <div class="sc-audio-track__titles">
                    <h1 class="sc-audio-track__title">{{ $asset->title ?: $asset->file_name }}</h1>
                    @if($asset->speaker_name)
                        <div class="sc-audio-track__artist">{{ $asset->speaker_name }}</div>
                    @endif
                </div>
            </div>

            <div class="sc-audio-track__wave-block">
                <div class="sc-audio-track__wave" id="scAudioWave" role="presentation" aria-hidden="true">
                    @foreach($waveBars as $h)
                        <span class="sc-audio-track__wave-bar" style="--h: {{ $h }}%"></span>
                    @endforeach
                </div>
                <div class="sc-audio-track__controls">
                    <span class="sc-audio-track__time" id="scAudioTimeCur">0:00</span>
                    <div class="sc-audio-track__range-wrap">
                        <div class="sc-audio-track__range-fill" id="scAudioRangeFill"></div>
                        <input type="range" class="sc-audio-track__range" id="scAudioSeek" min="0" max="1000" value="0" step="1" aria-label="موضع التشغيل">
                    </div>
                    <span class="sc-audio-track__time" id="scAudioTimeDur">—:—</span>
                </div>
            </div>

            <div class="sc-audio-track__actions">
                @if($useExtractedAudioForAudioPlatform && $asset->isVideo())
                    <a href="{{ route('assets.show.public', $asset) }}" class="sc-audio-track__link-video">
                        <i class="bi bi-film"></i> مشاهدة النسخة المرئية
                    </a>
                @endif
                <a href="{{ route('assets.download.public-audio', $asset) }}" class="sc-audio-track__link-video" rel="nofollow">
                    <i class="bi bi-download"></i> تحميل الصوت
                </a>
                <button type="button" class="sc-audio-track__link-video sc-audio-track__link-video--btn" id="scAudioShareBtn">
                    <i class="bi bi-share"></i> مشاركة الرابط
                </button>
            </div>

            <audio id="scAudioElement" class="visually-hidden" preload="metadata" playsinline @if($useExtractedAudioForAudioPlatform) data-extracted="1" @endif>
                <source src="{{ $audioSrc }}" type="{{ $scMime }}">
            </audio>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var globalPlayer = window.AlmonajahAudioGlobal || null;
    var audio = globalPlayer && globalPlayer.getAudioElement ? globalPlayer.getAudioElement() : document.getElementById('scAudioElement');
    var btn = document.getElementById('scAudioPlayBtn');
    var seek = document.getElementById('scAudioSeek');
    var fill = document.getElementById('scAudioRangeFill');
    var tCur = document.getElementById('scAudioTimeCur');
    var tDur = document.getElementById('scAudioTimeDur');
    var wave = document.getElementById('scAudioWave');
    var iconPlay = btn && btn.querySelector('.sc-audio-track__play-icon-play');
    var iconPause = btn && btn.querySelector('.sc-audio-track__play-icon-pause');
    var shareBtn = document.getElementById('scAudioShareBtn');
    if (!audio || !btn || !seek) return;

    function getTrackPayload() {
        var srcEl = document.querySelector('#scAudioElement source');
        return {
            id: @json($asset->id),
            src: srcEl ? srcEl.src : '',
            title: @json($asset->title ?: $asset->file_name),
            speaker: @json($asset->speaker_name ?: 'المنصة الصوتية'),
            poster: @json($posterUrl),
            pageUrl: window.location.href,
            nextUrl: getNextTrackUrl(),
            queue: getQueueItems(),
            textContent: @json($audioTextContent)
        };
    }

    function isThisTrackCurrentInGlobal() {
        if (!globalPlayer || typeof globalPlayer.isCurrentTrack !== 'function') return true;
        var track = getTrackPayload();
        return !!globalPlayer.isCurrentTrack(track.id, track.src);
    }

    function fmt(s) {
        if (!isFinite(s) || s < 0) return '0:00';
        s = Math.floor(s);
        var m = Math.floor(s / 60);
        var sec = s % 60;
        return m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function setPlayingUI(playing) {
        if (!iconPlay || !iconPause) return;
        if (playing) {
            iconPlay.classList.add('d-none');
            iconPause.classList.remove('d-none');
            btn.setAttribute('aria-label', 'إيقاف مؤقت');
        } else {
            iconPlay.classList.remove('d-none');
            iconPause.classList.add('d-none');
            btn.setAttribute('aria-label', 'تشغيل');
        }
        if (wave) wave.classList.toggle('is-playing', playing);
    }

    function getNextTrackUrl() {
        var nextCard = document.querySelector('.related-audio-suggestions__grid .related-audio-card[href]');
        if (!nextCard) return '';
        return nextCard.href || '';
    }

    function getQueueItems() {
        var cards = document.querySelectorAll('.related-audio-suggestions__grid .related-audio-card[href]');
        var out = [];
        cards.forEach(function(card) {
            var href = card.getAttribute('href') || '';
            if (!href) return;
            var titleEl = card.querySelector('.related-audio-card__title');
            var metaEl = card.querySelector('.related-audio-card__meta');
            var imgEl = card.querySelector('img');
            out.push({
                pageUrl: card.href,
                title: titleEl ? titleEl.textContent.trim() : 'محتوى صوتي',
                speaker: metaEl ? metaEl.textContent.trim() : 'المنصة الصوتية',
                poster: imgEl ? imgEl.src : '',
                src: '',
                id: ''
            });
        });
        return out;
    }

    function syncSeek() {
        if (!isThisTrackCurrentInGlobal()) return;
        var d = audio.duration;
        if (!isFinite(d) || d <= 0) return;
        var p = (audio.currentTime / d) * 1000;
        seek.value = String(Math.min(1000, Math.max(0, p)));
        if (fill) fill.style.width = (audio.currentTime / d * 100) + '%';
        if (tCur) tCur.textContent = fmt(audio.currentTime);
    }

    audio.addEventListener('loadedmetadata', function() {
        if (!isThisTrackCurrentInGlobal()) return;
        if (tDur) tDur.textContent = fmt(audio.duration);
        syncSeek();
    });
    audio.addEventListener('timeupdate', syncSeek);
    audio.addEventListener('play', function() { setPlayingUI(true); });
    audio.addEventListener('pause', function() { setPlayingUI(false); });
    audio.addEventListener('ended', function() {
        setPlayingUI(false);
        syncSeek();
        var nextUrl = getNextTrackUrl();
        if (nextUrl) {
            window.location.href = nextUrl;
        }
    });

    btn.addEventListener('click', function() {
        var track = getTrackPayload();
        if (globalPlayer && typeof globalPlayer.setTrack === 'function') {
            globalPlayer.setTrack(track, { autoplay: true, toggle: true });
        } else {
            if (audio.paused) audio.play().catch(function() {});
            else audio.pause();
        }
    });

    seek.addEventListener('input', function() {
        if (!isThisTrackCurrentInGlobal()) return;
        var d = audio.duration;
        if (!isFinite(d) || d <= 0) return;
        var v = parseFloat(seek.value) / 1000;
        audio.currentTime = v * d;
        if (fill) fill.style.width = (v * 100) + '%';
        if (tCur) tCur.textContent = fmt(audio.currentTime);
    });

    if (wave) {
        wave.addEventListener('click', function(e) {
            if (!isThisTrackCurrentInGlobal()) return;
            var r = wave.getBoundingClientRect();
            var x = e.clientX - r.left;
            var ratio = r.width ? x / r.width : 0;
            var d = audio.duration;
            if (isFinite(d) && d > 0) {
                audio.currentTime = Math.max(0, Math.min(d, ratio * d));
                syncSeek();
            }
        });
    }

    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            var url = window.location.href;
            if (navigator.share) {
                navigator.share({ title: document.title, url: url }).catch(function() {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    shareBtn.classList.add('sc-audio-track__link-video--copied');
                    setTimeout(function() { shareBtn.classList.remove('sc-audio-track__link-video--copied'); }, 2000);
                }).catch(function() { prompt('انسخ الرابط:', url); });
            } else {
                prompt('انسخ الرابط:', url);
            }
        });
    }

    (function syncLocalUiWithGlobal() {
        if (!globalPlayer || typeof globalPlayer.getState !== 'function') return;
        var st = globalPlayer.getState() || {};
        var track = getTrackPayload();
        var isCurrent = globalPlayer.isCurrentTrack && globalPlayer.isCurrentTrack(track.id, track.src);
        // إذا نفس الدعاء هو الجاري تشغيله، حدّث النص المرتبط مباشرة بدون اشتراط الضغط على Play.
        if (isCurrent && typeof globalPlayer.saveState === 'function') {
            globalPlayer.saveState({
                textContent: track.textContent || '',
                title: track.title || st.title || '',
                speaker: track.speaker || st.speaker || '',
                poster: track.poster || st.poster || '',
                pageUrl: track.pageUrl || st.pageUrl || ''
            });
        }
        setPlayingUI(!!isCurrent && st.paused === false);
        if (!isCurrent) {
            seek.value = '0';
            if (fill) fill.style.width = '0%';
            if (tCur) tCur.textContent = '0:00';
            if (tDur) tDur.textContent = '—:—';
        }
    })();
})();
</script>
@endpush
