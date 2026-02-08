@extends('layouts.live')

@section('title', 'بث مباشر - المناجاة')

@section('content')
<div class="live-loading" id="liveLoading">
    <div class="live-loading-spinner"></div>
</div>
<video
    id="livePlayer"
    class="live-video"
    autoplay
    playsinline
    preload="auto"
    poster="{{ $posterUrl }}"
    style="width:100%;height:100%;object-fit:contain;">
    متصفحك لا يدعم تشغيل الفيديو.
</video>
<div class="live-controls">
    <button type="button" class="live-cast" id="liveCast" title="عرض على شاشة أخرى (Cast)" aria-label="عرض على شاشة أخرى">
        <svg class="live-cast-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M1 18v3h3c0-1.66-1.34-3-3-3zm0-4v2c2.76 0 5 2.24 5 5h2c0-3.87-3.13-7-7-7zm0-4v2c4.97 0 9 4.03 9 9h2c0-6.08-4.93-11-11-11zm20-7H3c-1.1 0-2 .9-2 2v3h2V5h18v14h-7v2h7c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
        </svg>
    </button>
    <button type="button" class="live-fullscreen" id="liveFullscreen" title="تكبير" aria-label="تكبير / ملء الشاشة">
        <i class="bi bi-fullscreen"></i>
    </button>
    <button type="button" class="live-unmute" id="liveUnmute" title="تفعيل الصوت" aria-label="تفعيل الصوت">
        <i class="bi bi-volume-mute-fill"></i>
    </button>
</div>

@push('scripts')
<script>
(function () {
    const feedUrl = @json(route('live.feed'));
    let queue = @json($liveQueue);
    let nextPageUrl = null;
    let index = 0;
    const video = document.getElementById('livePlayer');

    function playNext() {
        if (index >= queue.length) {
            if (nextPageUrl) {
                fetch(nextPageUrl)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        queue = queue.concat(data.assets);
                        nextPageUrl = data.next_page_url || null;
                        playNext();
                    })
                    .catch(function () {
                        index = 0;
                        playNext();
                    });
                return;
            }
            index = 0;
        }
        if (queue.length === 0) return;
        const item = queue[index];
        video.poster = item.poster || '';
        video.src = item.stream_url;
        video.load();
        video.play().catch(function () {});
        index++;
        if (typeof updateCastMedia === 'function') updateCastMedia();
    }

    video.addEventListener('ended', playNext);
    video.addEventListener('error', function () {
        index++;
        playNext();
    });

    var loadingEl = document.getElementById('liveLoading');
    function hideLoading() {
        if (loadingEl) loadingEl.classList.add('hidden');
    }
    video.addEventListener('canplay', hideLoading, { once: true });
    video.addEventListener('playing', hideLoading, { once: true });
    video.addEventListener('error', hideLoading, { once: true });

    var castBtn = document.getElementById('liveCast');
    var castSession = null;
    function updateCastMedia() {
        if (!castSession || !video.src) return;
        try {
            var contentType = video.src.indexOf('m3u8') !== -1 ? 'application/x-mpegURL' : 'video/mp4';
            var mediaInfo = new chrome.cast.media.MediaInfo(video.src, contentType);
            var request = new chrome.cast.media.LoadRequest(mediaInfo);
            request.autoplay = true;
            castSession.loadMedia(request).then(function () {}, function (err) { console.warn('Cast load error', err); });
        } catch (e) { console.warn('Cast updateCastMedia', e); }
    }
    if (castBtn && window.__castApiAvailable && typeof cast !== 'undefined' && cast.framework) {
        (function initCast() {
            var context = cast.framework.CastContext.getInstance();
            context.setOptions({
                receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
                autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
            });
            castBtn.addEventListener('click', function () {
                if (context.getCastState() === cast.framework.CastState.CONNECTED) {
                    if (castSession) castSession.endSession(true);
                    else context.getCurrentSession().then(function (s) { if (s) s.endSession(true); });
                    return;
                }
                context.requestSession().then(function (session) {
                    castSession = session;
                    updateCastMedia();
                }).catch(function (err) { console.warn('Cast session error', err); });
            });
            context.addEventListener(cast.framework.CastContextEventType.SESSION_STATE_CHANGED, function (e) {
                if (e.sessionState === cast.framework.SessionState.SESSION_STARTED) {
                    context.getCurrentSession().then(function (s) { castSession = s; });
                } else {
                    castSession = null;
                }
            });
        })();
    } else if (castBtn) {
        castBtn.addEventListener('click', function () {
            if (document.querySelector('.live-cast-unavailable')) return;
            var msg = document.createElement('span');
            msg.className = 'live-cast-unavailable';
            msg.setAttribute('style', 'position:fixed;bottom:6rem;left:1rem;z-index:1001;background:rgba(0,0,0,0.9);color:#fff;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.85rem;');
            msg.textContent = 'عرض Cast متاح في متصفح Chrome مع أجهزة Cast.';
            document.body.appendChild(msg);
            setTimeout(function () { msg.remove(); }, 3000);
        });
    }

    var fullscreenBtn = document.getElementById('liveFullscreen');
    var fullscreenIcon = fullscreenBtn ? fullscreenBtn.querySelector('i') : null;
    var container = document.querySelector('.live-container');
    if (fullscreenBtn && fullscreenIcon && container) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                container.requestFullscreen().then(function () {
                    fullscreenIcon.classList.remove('bi-fullscreen');
                    fullscreenIcon.classList.add('bi-fullscreen-exit');
                    fullscreenBtn.setAttribute('title', 'تصغير');
                    fullscreenBtn.setAttribute('aria-label', 'تصغير');
                }).catch(function () {});
            } else {
                document.exitFullscreen().then(function () {
                    fullscreenIcon.classList.remove('bi-fullscreen-exit');
                    fullscreenIcon.classList.add('bi-fullscreen');
                    fullscreenBtn.setAttribute('title', 'تكبير');
                    fullscreenBtn.setAttribute('aria-label', 'تكبير / ملء الشاشة');
                }).catch(function () {});
            }
        });
        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement && fullscreenIcon) {
                fullscreenIcon.classList.remove('bi-fullscreen-exit');
                fullscreenIcon.classList.add('bi-fullscreen');
                fullscreenBtn.setAttribute('title', 'تكبير');
                fullscreenBtn.setAttribute('aria-label', 'تكبير / ملء الشاشة');
            }
        });
    }

    var unmuteBtn = document.getElementById('liveUnmute');
    var unmuteIcon = unmuteBtn ? unmuteBtn.querySelector('i') : null;
    if (unmuteBtn && unmuteIcon) {
        unmuteBtn.addEventListener('click', function () {
            if (video.muted) {
                video.muted = false;
                unmuteIcon.classList.remove('bi-volume-mute-fill');
                unmuteIcon.classList.add('bi-volume-up-fill');
                unmuteBtn.setAttribute('title', 'كتم الصوت');
                unmuteBtn.setAttribute('aria-label', 'كتم الصوت');
            } else {
                video.muted = true;
                unmuteIcon.classList.remove('bi-volume-up-fill');
                unmuteIcon.classList.add('bi-volume-mute-fill');
                unmuteBtn.setAttribute('title', 'تفعيل الصوت');
                unmuteBtn.setAttribute('aria-label', 'تفعيل الصوت');
            }
        });
    }

    if (queue.length > 0) {
        playNext();
    } else if (loadingEl) {
        loadingEl.classList.add('hidden');
    }

    var exitLink = document.getElementById('liveExit');
    if (exitLink) {
        exitLink.addEventListener('click', function (e) {
            e.preventDefault();
            video.pause();
            video.removeAttribute('src');
            video.load();
            var url = exitLink.getAttribute('href');
            if (url) window.location.href = url;
        });
    }
})();
</script>
@endpush
@endsection
