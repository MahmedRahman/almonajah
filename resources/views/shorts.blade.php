@extends('layouts.public')

@section('title', 'فيديوهات قصيرة - المناجاة')

@section('content')
<div class="home-layout shorts-page-layout">
    <!-- Sidebar (نفس الصفحة الرئيسية) -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') && !request('scholar_id') ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i>
                    <span class="sidebar-item-text">الرئيسية</span>
                </a>
                <a href="{{ route('shorts') }}" class="sidebar-item {{ request()->routeIs('shorts') ? 'active' : '' }}">
                    <img src="{{ asset('images/shorts-icon.png') }}" alt="فيديوهات قصيرة" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">فيديوهات قصيرة</span>
                </a>
                <a href="{{ route('public.playlists') }}" class="sidebar-item {{ request()->routeIs('public.playlists') || request()->routeIs('public.playlist.show') ? 'active' : '' }}">
                    <img src="{{ asset('images/playlists-icon.png') }}" alt="قوائم التشغيل" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">قوائم التشغيل</span>
                </a>
                <a href="{{ route('public.scholars') }}" class="sidebar-item d-none {{ request()->routeIs('public.scholars') || request()->routeIs('public.scholar.show') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span class="sidebar-item-text">الشيوخ</span>
                </a>
                <a href="{{ route('live') }}" class="sidebar-item {{ request()->routeIs('live') ? 'active' : '' }}">
                    <img src="{{ asset('images/live-icon.png') }}" alt="بث مباشر" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">بث مباشر</span>
                </a>

                @if(isset($categories) && $categories->count() > 0)
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف</h3>
                </div>
                @foreach($categories as $category)
                <a href="{{ route('home', ['content_category' => $category->name]) }}"
                   class="sidebar-item {{ request('content_category') == $category->name ? 'active' : '' }}">
                    @if($category->image_path)
                        <img src="{{ asset('storage/' . $category->image_path) }}"
                             alt="{{ $category->name }}"
                             class="sidebar-category-image"
                             style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px; margin-left: 8px;">
                    @else
                        <i class="bi bi-tag"></i>
                    @endif
                    <span class="sidebar-item-text">{{ $category->name }}</span>
                    <span class="sidebar-category-count">({{ $category->assets_count ?? 0 }})</span>
                </a>
                @endforeach
                @endif

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">حسابي</h3>
                </div>
                @guest
                <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="تسجيل الدخول" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الدخول</span>
                </a>
                @endguest
                @auth
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="الملف الشخصي" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">الملف الشخصي</span>
                </a>
                <a href="{{ route('favorites') }}" class="sidebar-item {{ request()->routeIs('favorites') ? 'active' : '' }}">
                    <img src="{{ asset('images/favorites-icon.png') }}" alt="المفضلة" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">المفضلة</span>
                </a>
                <a href="#" class="sidebar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <img src="{{ asset('images/logout-icon.png') }}" alt="تسجيل الخروج" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الخروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                @endauth
            </nav>
        </div>
    </aside>

    <!-- Main Content: عمود Shorts بتصميم يشبه اليوتيوب -->
    <div class="main-content-wrapper shorts-main-wrapper">
        <div class="shorts-feed-column" id="shortsFeedColumn">
            <div class="shorts-viewer-container" id="shortsContainer">
                @if($shorts->count() > 0)
                    <div class="shorts-viewer" id="shortsViewer">
                        @foreach($shorts as $index => $short)
                        @php
                            // استخدام route البث ليعيد النسخة المحددة للعرض على الموقع (web_video_relative_path إن وُجدت) بدل الملف الأصلي الكبير
                            $fileUrl = null;
                            if ($short->relative_path && strpos($short->relative_path, 'assets/') === 0) {
                                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($short->relative_path)) {
                                    $fileUrl = route('assets.stream.public', $short);
                                }
                            }
                        @endphp

                        @if($fileUrl && in_array(strtolower($short->extension ?? ''), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                        <div class="short-video-container" data-index="{{ $index }}" onclick="handleShortContainerClick(event, this)">
                            @php
                                $hlsMasterPlaylist = null;
                                if ($short->hlsVersions && $short->hlsVersions->count() > 0) {
                                    $masterPlaylist = $short->hlsVersions->firstWhere('master_playlist_path', '!=', null);
                                    if ($masterPlaylist && $masterPlaylist->master_playlist_path) {
                                        $hlsMasterPlaylist = asset('storage/' . $masterPlaylist->master_playlist_path);
                                    }
                                }
                            @endphp

                            <video
                                class="short-video"
                                loop
                                playsinline
                                data-src="{{ $fileUrl }}"
                                data-hls="{{ $hlsMasterPlaylist }}"
                                data-id="{{ $short->id }}"
                                @if($index === 0) autoplay @endif>
                            </video>

                            @if($hlsMasterPlaylist && $short->hlsVersions && $short->hlsVersions->count() > 1)
                            <div class="short-quality-selector">
                                <button class="quality-btn" onclick="toggleQualityMenu(this)">
                                    <i class="bi bi-gear"></i>
                                    <span>الجودة</span>
                                </button>
                                <div class="quality-menu">
                                    @foreach($short->hlsVersions->sortByDesc('height') as $hlsVersion)
                                    <button class="quality-option"
                                            data-playlist="{{ asset('storage/' . $hlsVersion->playlist_path) }}"
                                            onclick="changeQuality(this, '{{ $hlsVersion->resolution }}')">
                                        {{ $hlsVersion->resolution }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="short-video-info" onclick="event.stopPropagation();">
                                <div class="short-video-info-left">
                                    <a href="{{ route('assets.show.public', $short) }}" class="short-channel-info">
                                        @if($short->speaker_name)
                                        <div class="short-channel-avatar">
                                            {{ mb_substr($short->speaker_name, 0, 1) }}
                                        </div>
                                        <div class="short-channel-name">{{ $short->speaker_name }}</div>
                                        @endif
                                    </a>
                                    <div class="short-video-title">{{ $short->title ?: $short->file_name }}</div>
                                </div>

                                <div class="short-video-actions" onclick="event.stopPropagation();">
                                    <button type="button" class="short-action-btn" onclick="toggleLike(this)" title="إعجاب">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                    <a href="{{ route('assets.show.public', $short) }}" class="short-action-btn" title="فتح الفيديو">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    <button type="button" class="short-action-btn" onclick="shareShort(this)" title="مشاركة">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="short-play-overlay" onclick="event.stopPropagation(); togglePlayPause(this)">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                @else
                    <div class="empty-state shorts-empty">
                        <i class="bi bi-play-circle"></i>
                        <p>لا توجد فيديوهات قصيرة متاحة</p>
                        <a href="{{ route('home') }}" class="btn btn-primary mt-3">العودة للرئيسية</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* إزالة الإطار/الخط الأسود فوق المحتوى */
body.shorts-page .navbar {
    box-shadow: none;
    border-bottom: 1px solid transparent;
}
body.shorts-page main {
    padding-top: 0;
    margin-top: 0;
}

/* تخطيط الصفحة مع القائمة الجانبية */
.home-layout.shorts-page-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 60px);
    margin-top: 0;
}

.sidebar-menu {
    position: relative;
    width: 240px;
    min-height: calc(100vh - 60px);
    background-color: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 2;
    transition: width 0.3s ease, opacity 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.sidebar-menu.collapsed { width: 0; opacity: 0; overflow: hidden; border: none; }
.sidebar-content { padding: var(--spacing-md); }
.sidebar-nav { display: flex; flex-direction: column; gap: 0.25rem; }
.sidebar-item { display: flex; align-items: center; gap: var(--spacing-sm); padding: 0.75rem var(--spacing-sm); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-primary); transition: all 0.2s ease; font-size: 0.9375rem; font-weight: 500; }
.sidebar-item:hover { background-color: var(--bg-tertiary); color: var(--primary-color); }
.sidebar-item.active { background-color: rgba(24, 135, 129, 0.1); color: var(--primary-color); font-weight: 600; }
.sidebar-item i { font-size: 1.25rem; width: 24px; text-align: center; flex-shrink: 0; }
.sidebar-item-icon-img { width: 24px; height: 24px; object-fit: contain; flex-shrink: 0; }
.sidebar-category-image { width: 24px; height: 24px; object-fit: cover; border-radius: 4px; flex-shrink: 0; }
.sidebar-item-text { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-category-count { font-size: 0.8rem; color: var(--text-secondary); margin-right: 4px; flex-shrink: 0; }
.sidebar-divider { height: 1px; background-color: var(--border-color); margin: var(--spacing-sm) 0; }
.sidebar-section-header { padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-xs); margin-top: var(--spacing-xs); }
.sidebar-section-title { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }

/* منطقة المحتوى الرئيسية - عمود Shorts */
.shorts-main-wrapper {
    flex: 1;
    min-width: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    background-color: var(--bg-secondary);
    overflow: hidden;
}

.shorts-feed-column {
    width: 100%;
    max-width: 480px;
    height: calc(100vh - 60px);
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #0a0a0a;
    box-shadow: var(--shadow-md);
    border-left: 1px solid var(--border-color);
    position: relative;
    padding-top: 0;
    overflow: hidden;
}

/* حاوية المشاهد - ارتفاع كامل، لون خلفية يطابق الفيديو */
.shorts-viewer-container {
    flex: 1;
    min-height: 0;
    position: relative;
    background: #0a0a0a;
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}

.shorts-viewer {
    width: 100%;
    height: 100%;
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    scrollbar-width: none;
}
.shorts-viewer::-webkit-scrollbar { display: none; }
.shorts-viewer { -ms-overflow-style: none; }

.short-video-container {
    width: 100%;
    height: 100%;
    min-height: 100%;
    position: relative;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0a0a0a;
    flex-shrink: 0;
    cursor: pointer;
}

.short-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: #000;
}

.short-video-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.25rem 1.5rem;
    padding-bottom: calc(2.5rem + env(safe-area-inset-bottom, 0));
    min-height: 120px;
    background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.75) 35%, rgba(0,0,0,0.4) 65%, transparent 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    z-index: 10;
}

.short-video-info-left { flex: 1; color: white; min-width: 0; }

.short-channel-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    color: inherit;
}

.short-channel-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    border: 2px solid rgba(255,255,255,0.4);
    flex-shrink: 0;
}

.short-channel-name { font-weight: 600; font-size: 0.9375rem; color: white; }
.short-video-title {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.95);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.short-video-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
    margin-right: 0.5rem;
}

.short-action-btn {
    background: rgba(255,255,255,0.15);
    border: none;
    border-radius: 50%;
    color: white;
    width: 48px;
    height: 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    backdrop-filter: blur(8px);
}
.short-action-btn:hover { background: rgba(255,255,255,0.25); transform: scale(1.08); }
.short-action-btn.liked { background: rgba(255,59,92,0.4); color: #ff3b5c; }
.short-action-btn.liked i { color: #ff3b5c; }

.short-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 20;
    border: 3px solid rgba(255,255,255,0.3);
}
.short-video-container.paused .short-play-overlay { display: flex; }
.short-play-overlay i { font-size: 3rem; color: white; }

/* الجودة */
.short-quality-selector {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 15;
}
.quality-btn {
    background: rgba(0,0,0,0.7);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    color: white;
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.quality-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
    background: rgba(0,0,0,0.9);
    border-radius: 8px;
    padding: 0.25rem;
    min-width: 120px;
    display: none;
}
.quality-option {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    text-align: right;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    font-size: 0.8125rem;
}
.quality-option:hover { background: rgba(255,255,255,0.1); }
.quality-option.active { background: rgba(24,135,129,0.4); color: var(--primary-color); }

.empty-state.shorts-empty {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    padding: 2rem;
}
.empty-state.shorts-empty i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.6; }
.empty-state.shorts-empty p { margin: 0; font-size: 1rem; }

/* موبايل: إخفاء الهيدر الصغير أو تصغيره، وإظهار القائمة الجانبية كمنسدلة */
@media (max-width: 1024px) {
    .sidebar-menu {
        position: fixed;
        top: 60px;
        right: 0;
        height: calc(100vh - 60px);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        transform: translateX(100%);
    }
    .sidebar-menu:not(.collapsed) { transform: translateX(0); width: 240px; opacity: 1; }
    .sidebar-menu.collapsed { transform: translateX(100%); width: 240px; }

    .shorts-feed-column {
        max-width: 100%;
        border-left: none;
    }
}

@media (max-width: 768px) {
    .sidebar-menu { width: 240px; top: 56px; height: calc(100vh - 56px); }
    .sidebar-menu:not(.collapsed) { width: 240px; }
    .shorts-feed-column { height: calc(100vh - 56px); }
    .short-video-info {
        padding-bottom: calc(2.75rem + env(safe-area-inset-bottom, 0));
        min-height: 110px;
    }
    .short-video-actions { gap: 0.75rem; }
    .short-action-btn { width: 44px; height: 44px; font-size: 1.25rem; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
var currentVideoIndex = 0;
var videos = [];
var hlsInstances = [];
var isScrolling = false;

document.addEventListener('DOMContentLoaded', function() {
    videos = Array.from(document.querySelectorAll('.short-video'));
    var containers = document.querySelectorAll('.short-video-container');
    var col = document.getElementById('shortsFeedColumn');
    var viewer = document.getElementById('shortsViewer');
    if (!viewer || !col) return;

    function setHeights() {
        var container = document.getElementById('shortsContainer');
        var viewerEl = document.getElementById('shortsViewer');
        var h = col.clientHeight;
        if (container) container.style.height = h + 'px';
        var viewerH = (viewerEl && viewerEl.clientHeight) ? viewerEl.clientHeight : h;
        containers.forEach(function(c) {
            c.style.height = viewerH + 'px';
            c.style.minHeight = viewerH + 'px';
        });
    }
    setHeights();
    requestAnimationFrame(function() { setHeights(); });
    window.addEventListener('resize', setHeights);

    if (videos.length > 0) loadVideo(0);

    viewer.addEventListener('scroll', function() {
        clearTimeout(window._scrollTimeout);
        window._scrollTimeout = setTimeout(handleScroll, 100);
    }, { passive: true });
    setTimeout(handleScroll, 300);
});

function loadVideo(index) {
    if (index < 0 || index >= videos.length) return;
    var video = videos[index];
    var hlsUrl = video.getAttribute('data-hls');
    var regularSrc = video.getAttribute('data-src');

    if (hlsInstances[index]) {
        hlsInstances[index].destroy();
        hlsInstances[index] = null;
    }

    if (hlsUrl && typeof Hls !== 'undefined' && Hls.isSupported()) {
        var hls = new Hls({ enableWorker: true, lowLatencyMode: true, capLevelToPlayerSize: true });
        hls.loadSource(hlsUrl);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function() { video.play().catch(function(){}); });
        hls.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal && regularSrc) {
                hls.destroy();
                video.src = regularSrc;
                video.load();
                video.play().catch(function(){});
            }
        });
        hlsInstances[index] = hls;
    } else if (hlsUrl && video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = hlsUrl;
        video.play().catch(function(){});
    } else if (regularSrc && !video.src) {
        video.src = regularSrc;
        video.load();
    }

    videos.forEach(function(v, i) {
        if (i === index) { v.play().catch(function(){}); v.muted = false; }
        else { v.pause(); v.muted = true; if (hlsInstances[i]) { hlsInstances[i].destroy(); hlsInstances[i] = null; } }
    });
    updateNavigationDots(index);
    currentVideoIndex = index;
}

function handleScroll() {
    if (isScrolling) return;
    var viewer = document.getElementById('shortsViewer');
    if (!viewer) return;
    var containers = Array.from(document.querySelectorAll('.short-video-container'));
    var vh = viewer.clientHeight;
    var st = viewer.scrollTop;
    var closestIndex = -1;
    var closestDist = Infinity;
    containers.forEach(function(container, i) {
        var rect = container.getBoundingClientRect();
        var center = rect.top + rect.height / 2;
        var viewCenter = vh / 2;
        if (rect.top < vh && rect.bottom > 0) {
            var d = Math.abs(center - viewCenter);
            if (d < closestDist) { closestDist = d; closestIndex = i; }
        }
    });
    if (closestIndex !== -1 && closestIndex !== currentVideoIndex) loadVideo(closestIndex);
}

function scrollToShort(index) {
    if (index < 0 || index >= videos.length) return;
    isScrolling = true;
    var container = document.querySelector('.short-video-container[data-index="' + index + '"]');
    var viewer = document.getElementById('shortsViewer');
    if (container && viewer) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        loadVideo(index);
    }
    setTimeout(function() { isScrolling = false; }, 800);
}

function handleShortContainerClick(ev, container) {
    if (ev.target.closest('.short-video-actions') || ev.target.closest('.short-quality-selector') || ev.target.closest('.short-video-info')) return;
    var overlay = container.querySelector('.short-play-overlay');
    if (overlay) togglePlayPause(overlay);
}

function togglePlayPause(overlay) {
    var container = overlay.closest('.short-video-container');
    var video = container.querySelector('.short-video');
    if (video.paused) { video.play(); container.classList.remove('paused'); }
    else { video.pause(); container.classList.add('paused'); }
}

function updateNavigationDots(activeIndex) {
    var dots = document.querySelectorAll('.nav-dot');
    if (dots.length) dots.forEach(function(dot, i) { dot.classList.toggle('active', i === activeIndex); });
}

function toggleLike(btn) {
    var icon = btn.querySelector('i');
    var isLiked = btn.classList.contains('liked');
    if (isLiked) {
        if (icon) { icon.classList.remove('bi-heart-fill'); icon.classList.add('bi-heart'); }
        btn.classList.remove('liked');
    } else {
        if (icon) { icon.classList.remove('bi-heart'); icon.classList.add('bi-heart-fill'); }
        btn.classList.add('liked');
    }
}

function shareShort() {
    var video = videos[currentVideoIndex];
    var id = video ? video.getAttribute('data-id') : '';
    var url = window.location.origin + '/video/' + id;
    if (navigator.share) {
        navigator.share({ title: 'شاهد هذا الفيديو', url: url });
    } else {
        navigator.clipboard.writeText(url).then(function() { alert('تم نسخ الرابط'); });
    }
}

function toggleQualityMenu(btn) {
    var menu = btn.nextElementSibling;
    document.querySelectorAll('.quality-menu').forEach(function(m) { m.style.display = 'none'; });
    if (menu) menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function changeQuality(btn, resolution) {
    var playlistUrl = btn.getAttribute('data-playlist');
    var currentVideo = videos[currentVideoIndex];
    var hlsInstance = hlsInstances[currentVideoIndex];
    if (hlsInstance && playlistUrl) {
        hlsInstance.destroy();
        var newHls = new Hls({ enableWorker: true });
        newHls.loadSource(playlistUrl);
        newHls.attachMedia(currentVideo);
        newHls.on(Hls.Events.MANIFEST_PARSED, function() { currentVideo.play().catch(function(){}); });
        hlsInstances[currentVideoIndex] = newHls;
    }
    btn.closest('.short-video-container').querySelectorAll('.quality-option').forEach(function(o) { o.classList.remove('active'); });
    btn.classList.add('active');
    if (btn.closest('.quality-menu')) btn.closest('.quality-menu').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.short-quality-selector')) {
        document.querySelectorAll('.quality-menu').forEach(function(m) { m.style.display = 'none'; });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown' && currentVideoIndex < videos.length - 1) scrollToShort(currentVideoIndex + 1);
    else if (e.key === 'ArrowUp' && currentVideoIndex > 0) scrollToShort(currentVideoIndex - 1);
    else if (e.key === ' ') {
        e.preventDefault();
        var c = document.querySelector('.short-video-container.paused, .short-video-container');
        if (c) togglePlayPause(c.querySelector('.short-play-overlay'));
    } else if (e.key === 'Escape') window.location.href = '{{ route("home") }}';
});
</script>
@endpush
