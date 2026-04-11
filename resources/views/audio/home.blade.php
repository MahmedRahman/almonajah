@extends('layouts.audio-public')

@section('title', 'المنصة الصوتية — المناجاة')

@section('meta')
    <meta name="description" content="محاضرات ومحتوى صوتي من منصة المناجاة الرقمية.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="المنصة الصوتية — المناجاة">
    <meta property="og:description" content="محاضرات ومحتوى صوتي من منصة المناجاة الرقمية.">
    <meta property="og:image" content="{{ url(asset('images/logo.png')) }}">
    <meta property="og:site_name" content="المناجاة">
@endsection

@push('styles')
<style>
@keyframes homeFadeIn { from { opacity: 0; } to { opacity: 1; } }
.home-reveal { animation: homeFadeIn 0.5s ease-out forwards; }
.home-layout { display: flex; position: relative; min-height: calc(100vh - 200px); margin-top: 0; }
/* —— الرئيسية الصوتية: وضع نهاري بألوان الموقع (متغيرات public.css) —— */
.audio-home-hero {
    margin: 0 calc(-1 * var(--spacing-md, 1rem)) 1.75rem;
    padding: 1.75rem var(--spacing-md, 1rem) 1.5rem;
    border-radius: 0 0 12px 12px;
    background:
        linear-gradient(165deg, rgba(24, 135, 129, 0.2) 0%, rgba(249, 250, 251, 0.98) 48%, var(--bg-secondary) 100%);
    border-bottom: 1px solid var(--border-color);
}
.audio-home-hero__title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    color: var(--text-primary);
    margin: 0 0 0.85rem;
    letter-spacing: -0.03em;
    line-height: 1.2;
}
.audio-home-hero__sub {
    font-size: 0.95rem;
    color: var(--text-secondary);
    margin: 0 0 1rem;
    max-width: 42rem;
}
.audio-home-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.audio-home-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--text-primary);
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: background 0.2s, border-color 0.2s, color 0.2s;
}
.audio-home-chip:hover {
    background: rgba(24, 135, 129, 0.1);
    border-color: var(--primary-color);
    color: var(--primary-color);
}
.audio-home-section { margin-bottom: 2rem; }
.audio-home-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.85rem;
}
.audio-home-section__title {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0;
    letter-spacing: -0.02em;
}
.audio-home-row {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 0.15rem 0 0.75rem;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}
.audio-home-row::-webkit-scrollbar { height: 6px; }
.audio-home-row::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 4px;
}
.audio-home-row .video-card {
    flex: 0 0 clamp(132px, 38vw, 198px);
    scroll-snap-align: start;
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 10px;
    padding-bottom: 0.25rem;
}
.audio-home-row .video-card:hover {
    background: var(--bg-tertiary) !important;
}
.audio-home-row .video-thumbnail {
    border-radius: 8px;
    box-shadow: var(--shadow-md);
}
.audio-home-row--square .video-thumbnail { aspect-ratio: 1 / 1; }
.audio-home-row .video-title { color: var(--text-primary); font-size: 0.88rem; }
.audio-home-row .video-meta { color: var(--text-secondary); font-size: 0.76rem; }
.audio-home-row .video-info { padding: 0.65rem 0.2rem 0.35rem; }
.audio-home-section--grid .video-grid--4col {
    gap: 1rem;
}
.audio-home-section--grid .video-card {
    background: var(--bg-primary) !important;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
}
.audio-home-section--grid .video-card:hover {
    background: var(--bg-tertiary) !important;
    box-shadow: var(--shadow-md);
}
.audio-home-section--grid .video-title { color: var(--text-primary); }
.audio-home-section--grid .video-meta { color: var(--text-secondary); }
.audio-home-section--grid .video-info { padding: 0.65rem 0.75rem 0.85rem; }
.audio-home-section--grid .video-thumbnail { aspect-ratio: 1 / 1; }
/* أقسام التصنيفات — تنويع بصري */
.audio-cat-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 0.85rem;
}
.audio-cat-section__title-wrap {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}
.audio-cat-section__badge-img {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 10px;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
}
.audio-cat-section__title { margin: 0; }
.audio-cat-section__see-all {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--primary-color);
    text-decoration: none;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.audio-cat-section__see-all:hover { text-decoration: underline; color: var(--primary-hover, var(--primary-color)); }
.audio-cat-section--panel {
    padding: 1rem 1.1rem 1.15rem;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-primary) 100%);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
}
.audio-cat-section--panel .audio-cat-section__panel-inner {
    margin: 0 -0.15rem;
}
.audio-cat-section--scroll {
    border-inline-start: 4px solid var(--audio-cat-accent, var(--primary-color));
    padding-inline-start: 0.85rem;
}
.audio-cat-section--grid .audio-cat-section__grid {
    margin-top: 0;
}
.audio-cat-section--grid .video-grid--4col {
    gap: 0.75rem;
}
.audio-cat-section--grid .video-thumbnail {
    aspect-ratio: 1 / 1;
}
.audio-cat-section--grid .video-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    box-shadow: var(--shadow-sm);
}
.audio-cat-section--grid .video-card:hover {
    box-shadow: var(--shadow-md);
}
.sidebar-menu { position: relative; width: 240px; min-height: calc(100vh - 60px); background-color: var(--bg-primary); border-left: 1px solid var(--border-color); box-shadow: var(--shadow-sm); z-index: 1; transition: width 0.3s ease, opacity 0.3s ease; overflow-y: auto; overflow-x: hidden; flex-shrink: 0; }
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
.sidebar-category-count { font-size: 0.8rem; color: var(--text-secondary, #6b7280); margin-right: 4px; flex-shrink: 0; }
.sidebar-divider { height: 1px; background-color: var(--border-color); margin: var(--spacing-sm) 0; }
.sidebar-section-header { padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-xs); margin-top: var(--spacing-xs); }
.sidebar-section-title { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
.main-content-wrapper { flex: 1; margin-right: 0; transition: margin-right 0.3s ease; width: 100%; min-width: 0; }
.container-main { max-width: 1400px; margin: 0 auto; padding: var(--spacing-lg) var(--spacing-md); }
.home-banners-rectangle-section { margin-bottom: var(--spacing-lg, 2rem); }
.home-banners-rectangle-list { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-start; }
.home-banners-rectangle-list .banner-link.banner-wrap { display: block; overflow: hidden; border-radius: var(--radius-md, 0.75rem); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); text-decoration: none; width: 100%; aspect-ratio: 4 / 1; }
.home-banners-rectangle-list .banner-link .banner-img { display: block; width: 100%; height: 100%; object-fit: cover; }
.home-section { margin-bottom: 2.5rem; }
.home-section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary); }
.search-results-section { padding: 1rem 0; max-width: 100%; }
.search-results-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 0 0 1rem; }
.search-results-list { display: flex; flex-direction: column; gap: 0.5rem; }
.search-result-row { display: flex; align-items: flex-start; gap: 1rem; padding: 0.5rem 0; text-decoration: none; color: inherit; border-radius: var(--radius-md, 0.75rem); transition: background-color 0.2s; }
.search-result-row:hover { background-color: rgba(0,0,0,0.05); }
.search-result-thumb { position: relative; flex-shrink: 0; width: 320px; max-width: 45%; aspect-ratio: 16 / 9; border-radius: 0.75rem; overflow: hidden; background: var(--bg-secondary, #eee); }
.search-result-thumb img { width: 100%; height: 100%; object-fit: cover; }
.search-result-duration { position: absolute; bottom: 6px; right: 6px; font-size: 0.75rem; font-weight: 600; background: rgba(0,0,0,0.8); color: #fff; padding: 2px 6px; border-radius: 4px; }
.search-result-body { flex: 1; min-width: 0; }
.search-result-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 0.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.search-result-meta { font-size: 0.875rem; color: var(--text-secondary); margin: 0 0 0.25rem; }
.search-result-desc { font-size: 0.8125rem; color: #888; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.video-grid--4col { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.8rem; }
@media (max-width: 1400px) { .video-grid--4col { grid-template-columns: repeat(6, 1fr); } }
@media (max-width: 1200px) { .video-grid--4col { grid-template-columns: repeat(6, 1fr); } }
@media (max-width: 992px) { .video-grid--4col { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
    .video-grid--4col { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-sm); }
    .search-result-thumb { max-width: 100%; width: 100%; }
}
@media (max-width: 520px) {
    .video-grid--4col { grid-template-columns: 1fr; }
}
.video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--spacing-md); margin-top: var(--spacing-md); }
.video-card { display: flex; flex-direction: column; background-color: var(--bg-primary); border-radius: var(--radius-md); overflow: hidden; cursor: pointer; text-decoration: none; color: inherit; box-shadow: var(--shadow-sm); }
.video-card:hover { box-shadow: var(--shadow-md); }
.video-thumbnail { position: relative; width: 100%; aspect-ratio: 16 / 9; background-color: var(--bg-tertiary); overflow: hidden; }
.video-thumbnail .shimmer-placeholder { position: absolute; inset: 0; background: linear-gradient(90deg, var(--bg-tertiary) 0%, #e8e8e8 20%, var(--bg-tertiary) 40%); background-size: 200% 100%; animation: shimmer 1.5s ease-in-out infinite; }
.video-thumbnail.img-loaded .shimmer-placeholder { opacity: 0; pointer-events: none; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.video-thumbnail img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.video-duration { position: absolute; bottom: 0.5rem; left: 0.5rem; background-color: rgba(0, 0, 0, 0.8); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; }
.video-info { padding: var(--spacing-sm); }
.video-info-header { display: flex; gap: 0.75rem; margin-top: 0.75rem; }
.video-channel-avatar { flex-shrink: 0; width: 36px; height: 36px; }
.avatar-logo { width: 36px; height: 36px; object-fit: contain; border-radius: 50%; background-color: var(--bg-primary); padding: 4px; }
.video-info-content { flex: 1; min-width: 0; }
.video-title { font-size: 0.9375rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
.video-meta { font-size: 0.8125rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.125rem; }
.video-channel-name { display: block; }
.video-category { display: block; color: var(--text-secondary); }
.video-grid--4col .video-info { padding: 0.55rem 0.6rem; }
.video-grid--4col .video-title { font-size: 0.84rem; line-height: 1.35; }
.video-grid--4col .video-meta { font-size: 0.74rem; }
.video-grid--4col .video-info-header { margin-top: 0; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 999; opacity: 0; transition: opacity 0.3s ease; }
.sidebar-overlay.active { display: block; opacity: 1; }
@media (max-width: 1024px) {
    .sidebar-menu { position: fixed; top: 60px; right: 0; height: calc(100vh - 60px); box-shadow: var(--shadow-lg); z-index: 1000; transform: translateX(100%); }
    .sidebar-menu:not(.collapsed) { transform: translateX(0); width: 240px; opacity: 1; }
    .sidebar-menu.collapsed { transform: translateX(100%); width: 240px; }
    .sidebar-overlay.active { display: block; }
}
@media (max-width: 768px) { .sidebar-menu { width: 240px; top: 56px; height: calc(100vh - 56px); } }
.audio-card-badge { position: absolute; top: 8px; left: 8px; background: rgba(24, 135, 129, 0.92); color: #fff; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; z-index: 2; pointer-events: none; }
</style>
@endpush

@section('content')
<div class="home-layout">
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i>
                    <span class="sidebar-item-text">الرئيسية</span>
                </a>
                <a href="{{ route('audio.home') }}" class="sidebar-item {{ request()->routeIs('audio.home') && !request('content_category') ? 'active' : '' }}">
                    <i class="bi bi-mic"></i>
                    <span class="sidebar-item-text">المنصة الصوتية</span>
                </a>
                <a href="{{ route('shorts') }}" class="sidebar-item {{ request()->routeIs('shorts') ? 'active' : '' }}">
                    <img src="{{ asset('images/shorts-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">فيديوهات قصيرة</span>
                </a>
                <a href="{{ route('public.playlists') }}" class="sidebar-item {{ request()->routeIs('public.playlists') || request()->routeIs('public.playlist.show') ? 'active' : '' }}">
                    <img src="{{ asset('images/playlists-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">قوائم التشغيل</span>
                </a>
                <a href="{{ route('public.scholars') }}" class="sidebar-item d-none {{ request()->routeIs('public.scholars') || request()->routeIs('public.scholar.show') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span class="sidebar-item-text">الشيوخ</span>
                </a>
                <a href="{{ route('live') }}" class="sidebar-item {{ request()->routeIs('live') ? 'active' : '' }}">
                    <img src="{{ asset('images/live-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">بث مباشر</span>
                </a>

                @if(isset($categories) && $categories->count() > 0)
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف (صوت)</h3>
                </div>
                @foreach($categories as $category)
                <a href="{{ route('audio.home', ['content_category' => $category->name]) }}"
                   class="sidebar-item {{ request('content_category') == $category->name ? 'active' : '' }}">
                    @if($category->image_path)
                        <img src="{{ asset('storage/' . $category->image_path) }}" alt="" class="sidebar-category-image" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px; margin-left: 8px;">
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
                <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login" onclick="if(window.closeSidebar) closeSidebar();">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الدخول</span>
                </a>
                @endguest
                @auth
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">الملف الشخصي</span>
                </a>
                <a href="{{ route('favorites') }}" class="sidebar-item {{ request()->routeIs('favorites') ? 'active' : '' }}">
                    <img src="{{ asset('images/favorites-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">المفضلة</span>
                </a>
                <a href="#" class="sidebar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <img src="{{ asset('images/logout-icon.png') }}" alt="" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الخروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                @endauth
            </nav>
        </div>
    </aside>

    <div class="main-content-wrapper home-reveal">
        <div class="container-main">
            @if(!request('search') && isset($bannersRectangle) && $bannersRectangle->count() > 0)
            <section class="home-banners-rectangle-section">
                <div class="home-banners-rectangle-list">
                    @foreach($bannersRectangle as $banner)
                        @include('partials.banner', ['banner' => $banner])
                    @endforeach
                </div>
            </section>
            @endif

            @if(request('search') && isset($searchResults))
            <section class="search-results-section">
                <h2 class="search-results-title">نتائج البحث: «{{ request('search') }}»</h2>
                @if($searchResults->count() > 0)
                    <div class="search-results-list">
                        @foreach($searchResults as $asset)
                            @php
                                $thumbImg = ($asset->cover_path ?? $asset->thumbnail_path)
                                    ? asset('storage/' . ($asset->cover_path ?? $asset->thumbnail_path))
                                    : asset('images/logo_min.png');
                                $descSnippet = $asset->site_description ? \Illuminate\Support\Str::limit(strip_tags($asset->site_description), 120) : null;
                            @endphp
                            <a href="{{ route('audio.show', $asset) }}" class="search-result-row">
                                <div class="search-result-thumb">
                                    <img src="{{ $thumbImg }}" alt="" loading="lazy" decoding="async" onerror="this.src='{{ asset('images/logo_min.png') }}'">
                                    @if($asset->computed_duration ?? null)
                                        <span class="search-result-duration">{{ $asset->computed_duration }}</span>
                                    @endif
                                </div>
                                <div class="search-result-body">
                                    <h3 class="search-result-title">{{ $asset->title ?: $asset->file_name }}</h3>
                                    @if($asset->speaker_name)
                                        <p class="search-result-meta">{{ $asset->speaker_name }}</p>
                                    @endif
                                    @if($descSnippet)
                                        <p class="search-result-desc">{{ $descSnippet }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <div class="search-results-pagination mt-4">
                        {{ $searchResults->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                @else
                    <p class="text-muted">لا توجد نتائج.</p>
                @endif
            </section>

            @elseif(request('content_category') && isset($categoryResults))
            <section class="home-section category-results-section">
                <h2 class="home-section-title mb-3">تصنيف (صوت): {{ request('content_category') }}</h2>
                @if($categoryResults->count() > 0)
                    <div class="video-grid video-grid--4col" id="audioCategoryGrid">
                        @include('partials.home-audio-cards', ['assets' => $categoryResults])
                    </div>
                    @if($categoryResults->hasMorePages())
                    <div class="load-more-wrapper" id="audioCategoryLoadMoreWrapper" style="text-align: center; margin: 2rem 0; min-height: 60px;" data-total="{{ $categoryResults->total() }}" data-next-url="{{ $categoryResults->appends(request()->query())->nextPageUrl() }}">
                        <p class="text-muted small mb-2" id="audioCategoryLoadMoreCount">عرض {{ $categoryResults->count() }} من {{ $categoryResults->total() }} صوت</p>
                        <div id="audioCategoryLoadMoreSentinel" style="height: 1px; visibility: hidden;"></div>
                        <div id="audioCategoryLoadMoreSpinner" class="load-more-spinner d-none" style="padding: 1rem;">
                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            <span class="ms-2 text-muted small">جاري تحميل المزيد...</span>
                        </div>
                    </div>
                    @else
                    <p class="text-muted small text-center mt-2">عرض {{ $categoryResults->total() }} من {{ $categoryResults->total() }} صوت</p>
                    @endif
                @else
                    <p class="text-muted">لا يوجد محتوى صوتي في هذا التصنيف.</p>
                @endif
            </section>

            @elseif(!request('search') && !request('content_category') && (
                (isset($first8) && $first8->count() > 0)
                || !empty($audioCategorySections)
                || (isset($middle16) && $middle16->count() > 0)
                || (isset($restAudios) && $restAudios->count() > 0)
            ))
            <div id="audioAllContainer">
                <header class="audio-home-hero">
                    <h1 class="audio-home-hero__title">استمع في أي وقت</h1>
                    <p class="audio-home-hero__sub">محاضرات ومحتوى صوتي من منصة المناجاة. تصفّح التصنيفات أدناه أو ابدأ من الأقسام المميّزة.</p>
                    @if(isset($categories) && $categories->count() > 0)
                    <nav class="audio-home-chips" aria-label="تصنيفات سريعة">
                        @foreach($categories->take(14) as $category)
                            <a href="{{ route('audio.home', ['content_category' => $category->name]) }}" class="audio-home-chip">{{ $category->name }}</a>
                        @endforeach
                    </nav>
                    @endif
                </header>

                @if(isset($first8) && $first8->count() > 0)
                <section class="audio-home-section">
                    <div class="audio-home-section__head">
                        <h2 class="audio-home-section__title">مميّز</h2>
                    </div>
                    <div class="audio-home-row audio-home-row--square">
                        @include('partials.home-audio-cards', ['assets' => $first8])
                    </div>
                </section>
                @endif

                @foreach($audioCategorySections ?? [] as $section)
                    @include('partials.audio-home-category-section', ['section' => $section])
                @endforeach

                @if(isset($middle16) && $middle16->count() > 0)
                <section class="audio-home-section">
                    <div class="audio-home-section__head">
                        <h2 class="audio-home-section__title">اكتشف</h2>
                    </div>
                    <div class="audio-home-row audio-home-row--square">
                        @include('partials.home-audio-cards', ['assets' => $middle16])
                    </div>
                </section>
                @endif

                @if(isset($restAudios) && $restAudios->count() > 0)
                <section class="home-section-all-audio audio-home-section audio-home-section--grid">
                    <div class="audio-home-section__head">
                        <h2 class="audio-home-section__title">المزيد</h2>
                    </div>
                    <div class="video-grid video-grid--4col" id="audioHomeGrid">
                        @include('partials.home-audio-cards', ['assets' => $restAudios])
                    </div>
                    @if($restAudios->hasMorePages())
                    @php
                        $loadMoreUrl = $restAudios->appends(array_merge(request()->query(), ['home_section' => 'all_audio', 'exclude_ids' => $excludeIdsForRest ?? []]))->nextPageUrl();
                        $catShown = 0;
                        if (!empty($audioCategorySections)) {
                            foreach ($audioCategorySections as $__s) {
                                $catShown += isset($__s['assets']) ? $__s['assets']->count() : 0;
                            }
                        }
                        $shownInit = ($first8 ? $first8->count() : 0) + $catShown + ($middle16 ? $middle16->count() : 0) + ($restAudios ? $restAudios->count() : 0);
                    @endphp
                    <div class="load-more-wrapper" id="audioLoadMoreWrapper" style="text-align: center; margin: 2rem 0; min-height: 60px;" data-total="{{ $totalHomeAudios ?? 0 }}" data-next-url="{{ $loadMoreUrl }}">
                        <p class="text-muted small mb-2" id="audioLoadMoreCount">عرض <span id="audioShownCount">{{ $shownInit }}</span> من {{ $totalHomeAudios ?? 0 }} صوت</p>
                        <div id="audioLoadMoreSentinel" style="height: 1px; visibility: hidden;"></div>
                        <div id="audioLoadMoreSpinner" class="load-more-spinner d-none" style="padding: 1rem;">
                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            <span class="ms-2 text-muted small">جاري تحميل المزيد...</span>
                        </div>
                    </div>
                    @else
                    <p class="text-muted small text-center mt-2">عرض {{ $totalHomeAudios ?? 0 }} من {{ $totalHomeAudios ?? 0 }} صوت</p>
                    @endif
                </section>
                @endif
            </div>

            @elseif(!request('search') && !request('content_category'))
            <div class="empty-state text-center py-5">
                <p class="text-muted mb-0">لا يوجد محتوى صوتي متاح حالياً.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var grid = document.getElementById('audioHomeGrid');
    var wrapper = document.getElementById('audioLoadMoreWrapper');
    var sentinel = document.getElementById('audioLoadMoreSentinel');
    var spinner = document.getElementById('audioLoadMoreSpinner');
    var container = document.getElementById('audioAllContainer');
    if (!grid || !wrapper || !sentinel) return;

    function updateShownCount() {
        var span = document.getElementById('audioShownCount');
        if (!span || !container) return;
        span.textContent = container.querySelectorAll('.video-card').length;
    }

    var loading = false;
    function loadMore() {
        var url = wrapper.getAttribute('data-next-url');
        if (!url || loading) return;
        loading = true;
        if (spinner) spinner.classList.remove('d-none');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.html) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = data.html.trim();
                    while (wrap.firstChild) grid.appendChild(wrap.firstChild);
                }
                updateShownCount();
                if (data.has_more && data.next_page_url) {
                    wrapper.setAttribute('data-next-url', data.next_page_url);
                } else {
                    wrapper.setAttribute('data-next-url', '');
                    if (sentinel) sentinel.style.display = 'none';
                    observer.disconnect();
                }
            })
            .catch(function() {})
            .finally(function() {
                loading = false;
                if (spinner) spinner.classList.add('d-none');
            });
    }
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            if (wrapper.getAttribute('data-next-url')) loadMore();
        });
    }, { root: null, rootMargin: '200px 0px', threshold: 0 });
    observer.observe(sentinel);
})();

(function() {
    var grid = document.getElementById('audioCategoryGrid');
    var wrapper = document.getElementById('audioCategoryLoadMoreWrapper');
    var countEl = document.getElementById('audioCategoryLoadMoreCount');
    var sentinel = document.getElementById('audioCategoryLoadMoreSentinel');
    var spinner = document.getElementById('audioCategoryLoadMoreSpinner');
    if (!grid || !wrapper || !sentinel) return;
    var loading = false;
    function loadMore() {
        var url = wrapper.getAttribute('data-next-url');
        if (!url || loading) return;
        loading = true;
        if (spinner) spinner.classList.remove('d-none');
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.html) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = data.html.trim();
                    while (wrap.firstChild) grid.appendChild(wrap.firstChild);
                }
                var total = parseInt(wrapper.getAttribute('data-total'), 10) || 0;
                var shown = grid.querySelectorAll('.video-card').length;
                if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' صوت';
                if (data.has_more && data.next_page_url) {
                    wrapper.setAttribute('data-next-url', data.next_page_url);
                } else {
                    wrapper.setAttribute('data-next-url', '');
                    if (sentinel) sentinel.style.display = 'none';
                    if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' صوت';
                    observer.disconnect();
                }
            })
            .catch(function() {})
            .finally(function() {
                loading = false;
                if (spinner) spinner.classList.add('d-none');
            });
    }
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            if (wrapper.getAttribute('data-next-url')) loadMore();
        });
    }, { root: null, rootMargin: '200px 0px', threshold: 0 });
    observer.observe(sentinel);
})();
</script>
@endpush
