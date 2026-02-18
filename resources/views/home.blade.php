@extends('layouts.public')

@section('title', 'منصة المناجاة الرقمية')

@section('meta')
    <meta name="description" content="الخيار الإعلامي الرقمي الأول للباحثين عن طمأنينة الروح والمصدر الموثوق للأدعية والابتهالات الواردة في القرآن الكريم وصحيح السنة النبوية. مبادرة من اقرأ @iqraa">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="منصة المناجاة الرقمية">
    <meta property="og:description" content="الخيار الإعلامي الرقمي الأول للباحثين عن طمأنينة الروح والمصدر الموثوق للأدعية والابتهالات الواردة في القرآن الكريم وصحيح السنة النبوية. مبادرة من اقرأ @iqraa">
    <meta property="og:image" content="{{ url(asset('images/logo.png')) }}">
    <meta property="og:site_name" content="المناجاة">
    <meta property="og:locale" content="ar_AR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="منصة المناجاة الرقمية">
    <meta name="twitter:description" content="الخيار الإعلامي الرقمي الأول للباحثين عن طمأنينة الروح والمصدر الموثوق للأدعية والابتهالات الواردة في القرآن الكريم وصحيح السنة النبوية. مبادرة من اقرأ @iqraa">
    <meta name="twitter:image" content="{{ url(asset('images/logo.png')) }}">
@endsection

@section('content')
<div class="home-layout">
    <!-- Sidebar -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <!-- Main Navigation -->
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
                
                <!-- Divider -->
                @if(isset($categories) && $categories->count() > 0)
                <div class="sidebar-divider"></div>
                
                <!-- Categories Section -->
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
                
                <!-- User Section -->
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">حسابي</h3>
                </div>
                @guest
                <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login" onclick="if(window.closeSidebar) closeSidebar();">
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

    <!-- Main Content -->
    <div class="main-content-wrapper home-reveal">
        <div class="container-main">
            <!-- Shorts Section (مخفية) -->
    @if(false && isset($shortsQuery) && $shortsQuery->count() > 0)
    <div class="shorts-section">
        <div class="shorts-header">
            <h2 class="shorts-title">
                فيديوهات قصيرة
            </h2>
        </div>
        <div class="shorts-container">
            <div class="shorts-scroll">
                @foreach($shortsQuery as $short)
                @php
                    $shortImage = ($short->cover_path ?? $short->thumbnail_path)
                        ? asset('storage/' . ($short->cover_path ?? $short->thumbnail_path))
                        : asset('images/logo_min.png');
                @endphp
                <a href="{{ route('assets.show.public', $short) }}" class="short-card">
                    <div class="short-thumbnail">
                        <div class="shimmer-placeholder"></div>
                        <img src="{{ $shortImage }}" 
                             alt="{{ $short->title ?: $short->file_name }}" 
                             loading="lazy"
                             width="180"
                             height="320"
                             decoding="async"
                             fetchpriority="low"
                             style="opacity: 0; transition: opacity 0.3s;"
                             onload="this.style.opacity='1'; var p=this.closest('.short-thumbnail'); if(p) p.classList.add('img-loaded');"
                             onerror="this.onerror=null; this.src='{{ asset('images/logo_min.png') }}';">
                        
                        @if($short->computed_duration)
                            <span class="short-duration">{{ $short->computed_duration }}</span>
                        @endif
                    </div>
                    <div class="short-info">
                        <h3 class="short-title">{{ \Illuminate\Support\Str::limit($short->title ?: $short->file_name, 50) }}</h3>
                        @if($short->speaker_name)
                            <span class="short-channel">{{ $short->speaker_name }}</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- بنرات مستطيلة: لا تظهر في صفحة البحث --}}
    @if(!request('search') && isset($bannersRectangle) && $bannersRectangle->count() > 0)
    <section class="home-banners-rectangle-section">
        <div class="home-banners-rectangle-list">
            @foreach($bannersRectangle as $banner)
                @include('partials.banner', ['banner' => $banner])
            @endforeach
        </div>
    </section>
    @endif

    {{-- نتائج البحث: قائمة فيديوهات واحدة تحت الأخرى (بدون إعلانات) --}}
    {{-- عند اختيار تصنيف: عرض كل فيديوهات التصنيف في قائمة واحدة مع ترقيم الصفحات --}}
    @if(request('content_category') && isset($categoryResults))
    <section class="home-section category-results-section">
        <h2 class="home-section-title mb-3">تصنيف: {{ request('content_category') }}</h2>
        @if($categoryResults->count() > 0)
            <div class="video-grid video-grid--4col" id="homeCategoryGrid">
                @include('partials.home-video-cards', ['assets' => $categoryResults])
            </div>
            @if($categoryResults->hasMorePages())
            <div class="load-more-wrapper" id="categoryLoadMoreWrapper" style="text-align: center; margin: 2rem 0; min-height: 60px;" data-total="{{ $categoryResults->total() }}" data-next-url="{{ $categoryResults->appends(request()->query())->nextPageUrl() }}">
                <p class="text-muted small mb-2" id="categoryLoadMoreCount">عرض {{ $categoryResults->count() }} من {{ $categoryResults->total() }} فيديو</p>
                <div id="categoryLoadMoreSentinel" style="height: 1px; visibility: hidden;"></div>
                <div id="categoryLoadMoreSpinner" class="load-more-spinner d-none" style="padding: 1rem;">
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    <span class="ms-2 text-muted small">جاري تحميل المزيد...</span>
                </div>
            </div>
            @else
            <p class="text-muted small text-center mt-2">عرض {{ $categoryResults->total() }} من {{ $categoryResults->total() }} فيديو</p>
            @endif
        @else
            <p class="text-muted">لا توجد فيديوهات في هذا التصنيف.</p>
        @endif
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
                    <a href="{{ route('assets.show.public', $asset) }}" class="search-result-row">
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
    @endif

    <!-- القسم ١: فيديوهات أفقية + بنرات أفقية (لا يظهر في صفحة البحث) -->
    @if(!request('search') && !request('content_category') && ((isset($landscapeFirst) && $landscapeFirst->count() > 0) || (isset($bannersLandscape) && $bannersLandscape->count() > 0)))
    <section class="home-section home-section-landscape-2rows">
        <div class="video-grid video-grid--4col" id="homeLandscapeFirst">
            @if(isset($bannersLandscape) && $bannersLandscape->count() > 0)
                @foreach($bannersLandscape as $banner)
                <div class="video-grid-item video-grid-item--banner video-grid-item--banner-landscape">
                    @include('partials.banner', ['banner' => $banner])
                </div>
                @endforeach
            @endif
            @if(isset($landscapeFirst) && $landscapeFirst->count() > 0)
                @include('partials.home-video-cards', ['assets' => $landscapeFirst])
            @endif
        </div>
    </section>
    @endif

    <!-- القسم ٢: فيديوهات عمودية + بنرات عمودية (لا يظهر في صفحة البحث) -->
    @if(!request('search') && !request('content_category') && ((isset($portraitVideos) && $portraitVideos->count() > 0) || (isset($bannersVertical) && $bannersVertical->count() > 0)))
    <section class="home-section home-section-portrait">
        <div class="video-grid video-grid--4col video-grid--portrait video-grid--portrait-one-row" id="homePortraitVideos">
            @if(isset($bannersVertical) && $bannersVertical->count() > 0)
                @foreach($bannersVertical as $banner)
                <div class="video-grid-item video-grid-item--banner video-grid-item--banner-vertical">
                    @include('partials.banner', ['banner' => $banner])
                </div>
                @endforeach
            @endif
            @if(isset($portraitVideos) && $portraitVideos->count() > 0)
                @include('partials.home-video-cards', ['assets' => $portraitVideos, 'forceLandscape' => true, 'useThumbnail' => true])
            @endif
        </div>
    </section>
    @endif

    <!-- القسم ٤: فيديوهات أفقية + تحميل المزيد (لا يظهر في صفحة البحث) -->
    @if(!request('search') && !request('content_category') && isset($landscapeMain) && $landscapeMain->count() > 0)
    <section class="home-section home-section-landscape-main">
        <div class="video-grid video-grid--4col" id="homeVideoGrid">
            @include('partials.home-video-cards', ['assets' => $landscapeMain])
        </div>

        @if($landscapeMain->hasMorePages())
        <div class="load-more-wrapper" id="loadMoreWrapper" style="text-align: center; margin: 2rem 0; min-height: 60px;" data-total="{{ $landscapeMain->total() }}" data-next-url="{{ $landscapeMain->appends(array_merge(request()->query(), ['home_section' => 'landscape_main', 'landscape_first_ids' => $landscapeFirstIds ?? []]))->nextPageUrl() }}">
            <p class="text-muted small mb-2" id="loadMoreCount">عرض {{ $landscapeMain->count() }} من {{ $landscapeMain->total() }} فيديو</p>
            <div id="loadMoreSentinel" style="height: 1px; visibility: hidden;"></div>
            <div id="loadMoreSpinner" class="load-more-spinner d-none" style="padding: 1rem;">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span class="ms-2 text-muted small">جاري تحميل المزيد...</span>
            </div>
        </div>
        @else
        <p class="text-muted small text-center mt-2">عرض {{ $landscapeMain->total() }} من {{ $landscapeMain->total() }} فيديو</p>
        @endif
    </section>
    @endif

    @if(!request('search') && !request('content_category') && (!isset($landscapeFirst) || $landscapeFirst->count() === 0) && (!isset($portraitVideos) || $portraitVideos->count() === 0) && (!isset($landscapeMain) || $landscapeMain->count() === 0))
    <div class="empty-state">
        <p>لا توجد فيديوهات متاحة</p>
    </div>
    @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Reveal / fade-in animation */
@keyframes homeFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.home-reveal {
    animation: homeFadeIn 0.5s ease-out forwards;
}

/* بنرات مستطيلة — الجزء العلوي من الصفحة */
.home-banners-rectangle-section {
    margin-bottom: var(--spacing-lg, 2rem);
}
.home-banners-rectangle-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-start;
}
.home-banners-rectangle-list .banner-link.banner-wrap {
    display: block;
    overflow: hidden;
    border-radius: var(--radius-md, 0.75rem);
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1));
    text-decoration: none;
    width: 100%;
    aspect-ratio: 4 / 1;
}
.home-banners-rectangle-list .banner-link .banner-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* نتائج البحث — قائمة فيديوهات (شكل شبيه بيوتيوب، بدون إعلانات) */
.search-results-section {
    padding: var(--spacing-md, 1rem) 0;
    max-width: 100%;
}
.search-results-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem;
}
.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.search-result-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.5rem 0;
    text-decoration: none;
    color: inherit;
    border-radius: var(--radius-md, 0.75rem);
    transition: background-color 0.2s;
}
.search-result-row:hover {
    background-color: var(--bg-hover, rgba(0,0,0,0.05));
}
.search-result-thumb {
    position: relative;
    flex-shrink: 0;
    width: 320px;
    max-width: 45%;
    aspect-ratio: 16 / 9;
    border-radius: var(--radius-md, 0.75rem);
    overflow: hidden;
    background: var(--bg-secondary, #eee);
}
.search-result-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.search-result-duration {
    position: absolute;
    bottom: 6px;
    right: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(0,0,0,0.8);
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
}
.search-result-body {
    flex: 1;
    min-width: 0;
}
.search-result-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.search-result-meta {
    font-size: 0.875rem;
    color: var(--text-secondary, #666);
    margin: 0 0 0.25rem;
}
.search-result-desc {
    font-size: 0.8125rem;
    color: var(--text-muted, #888);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* بنر داخل شبكة الفيديوهات (عمودي أو أفقي) */
.video-grid-item--banner {
    display: flex;
    align-items: center;
    justify-content: center;
}
.video-grid-item--banner .banner-link.banner-wrap {
    display: block;
    overflow: hidden;
    border-radius: var(--radius-md, 0.75rem);
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0,0,0,0.1));
    text-decoration: none;
    width: 100%;
    max-width: 100%;
}
.video-grid-item--banner .banner-link .banner-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.video-grid-item--banner-vertical .banner-link.banner-wrap {
    aspect-ratio: 9 / 16;
}
.video-grid-item--banner-landscape .banner-link.banner-wrap {
    aspect-ratio: 16 / 9;
}

/* Home Layout */
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

/* Sidebar Menu */
.sidebar-menu {
            position: relative;
            width: 240px;
            min-height: calc(100vh - 60px);
    background-color: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 1;
    transition: width 0.3s ease, opacity 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.sidebar-menu.collapsed {
    width: 0;
    opacity: 0;
    overflow: hidden;
    border: none;
}

.sidebar-content {
    padding: var(--spacing-md);
}

.sidebar-header {
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: var(--spacing-md);
}

.sidebar-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: 0.75rem var(--spacing-sm);
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
    font-size: 0.9375rem;
    font-weight: 500;
}

.sidebar-item:hover {
    background-color: var(--bg-tertiary);
    color: var(--primary-color);
}

.sidebar-item.active {
    background-color: rgba(24, 135, 129, 0.1);
    color: var(--primary-color);
    font-weight: 600;
}

.sidebar-item i {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}
.sidebar-item-icon-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    flex-shrink: 0;
}

.sidebar-category-image {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.sidebar-item-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-category-count {
    font-size: 0.8rem;
    color: var(--text-secondary, #6b7280);
    margin-right: 4px;
    flex-shrink: 0;
}

.sidebar-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: var(--spacing-sm) 0;
}

.sidebar-section-header {
    padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-xs);
    margin-top: var(--spacing-xs);
}

.sidebar-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

/* Main Content Wrapper */
.main-content-wrapper {
    flex: 1;
    margin-right: 0;
    transition: margin-right 0.3s ease;
    width: 100%;
    min-width: 0;
}

/* Container Main */
.container-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-lg) var(--spacing-md);
}

/* Responsive Design */
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
    
.sidebar-menu:not(.collapsed) {
                transform: translateX(0);
                width: 240px;
                opacity: 1;
    }
    
.sidebar-menu.collapsed {
                transform: translateX(100%);
                width: 240px;
            }
}

@media (max-width: 768px) {
    .sidebar-menu {
        width: 240px;
        top: 56px;
        height: calc(100vh - 56px);
    }
    
    .sidebar-menu:not(.collapsed) {
        width: 240px;
    }
    
    .sidebar-item {
        padding: 0.625rem var(--spacing-xs);
        font-size: 0.875rem;
    }
    
    .sidebar-item i {
        font-size: 1.125rem;
        width: 20px;
    }
}

/* Overlay for mobile when sidebar is open */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}

@media (max-width: 1024px) {
    .sidebar-overlay.active {
        display: block;
    }
}

/* Shorts Section */
.shorts-section {
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-md) 0;
}

.shorts-header {
    margin-bottom: var(--spacing-md);
    padding: 0 var(--spacing-sm);
}

.shorts-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    margin: 0;
}

.shorts-title i {
    color: #ff0000;
    font-size: 1.5rem;
}

.shorts-container {
    position: relative;
    overflow: hidden;
}

.shorts-scroll {
    display: flex;
    gap: var(--spacing-md);
    overflow-x: auto;
    overflow-y: hidden;
    padding: var(--spacing-sm);
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.shorts-scroll::-webkit-scrollbar {
    height: 8px;
}

.shorts-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.shorts-scroll::-webkit-scrollbar-thumb {
    background-color: var(--border-color);
    border-radius: 4px;
}

.shorts-scroll::-webkit-scrollbar-thumb:hover {
    background-color: var(--text-secondary);
}

.short-card {
    flex: 0 0 180px;
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
}

.short-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 9 / 16; /* عمودي مثل YouTube Shorts */
    background-color: var(--bg-tertiary);
    overflow: hidden;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}

.short-thumbnail .shimmer-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, var(--bg-tertiary) 0%, #e8e8e8 20%, var(--bg-tertiary) 40%, var(--bg-tertiary) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    transition: opacity 0.3s ease;
}
.short-thumbnail.img-loaded .shimmer-placeholder {
    opacity: 0;
    pointer-events: none;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.short-thumbnail video,
.short-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.short-thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.short-thumbnail-placeholder i {
    font-size: 2.5rem;
}

.short-duration {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: var(--radius-sm);
    font-size: 0.6875rem;
    font-weight: 600;
}

.short-info {
    padding: var(--spacing-sm);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.short-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.short-channel {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .short-card {
        flex: 0 0 150px;
    }
    
    .shorts-title {
        font-size: 1.125rem;
    }
    
    .short-title {
        font-size: 0.8125rem;
    }
    
    .short-channel {
        font-size: 0.6875rem;
    }
}

.video-info-header {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.video-channel-avatar {
    flex-shrink: 0;
}

.avatar-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
    border-radius: 50%;
    background-color: var(--bg-primary);
    padding: 4px;
}

.video-info-content {
    flex: 1;
    min-width: 0;
}

.video-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.video-meta {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.video-channel-name {
    display: block;
}

.video-category {
    display: block;
    color: var(--text-secondary);
}

/* أقسام الصفحة الرئيسية */
.home-section {
    margin-bottom: 2.5rem;
}
.home-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
}
.video-grid--4col {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-md);
}
/* صف واحد ٤ فيديوهات عمودية جنب بعض */
.video-grid--portrait-one-row {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.video-grid--6col {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: var(--spacing-md);
}
@media (max-width: 1200px) {
    .video-grid--4col { grid-template-columns: repeat(3, 1fr); }
    .video-grid--6col { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 768px) {
    .video-grid--4col { grid-template-columns: 1fr; gap: var(--spacing-sm); }
    .video-grid--portrait-one-row { grid-template-columns: 1fr; }
    .video-grid--6col { grid-template-columns: 1fr; gap: var(--spacing-sm); }
    .home-section-title { font-size: 1.1rem; }
}

@media (max-width: 768px) {
    .video-info-header {
        gap: 0.5rem;
    }
    
    .avatar-logo {
        width: 32px;
        height: 32px;
    }
    
    .video-title {
        font-size: 0.875rem;
    }
    
    .video-meta {
        font-size: 0.75rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// تحميل المزيد تلقائياً عند التمرير (infinite scroll)
(function() {
    var grid = document.getElementById('homeVideoGrid');
    var wrapper = document.getElementById('loadMoreWrapper');
    var countEl = document.getElementById('loadMoreCount');
    var sentinel = document.getElementById('loadMoreSentinel');
    var spinner = document.getElementById('loadMoreSpinner');
    if (!grid || !wrapper || !sentinel) return;

    var loading = false;

    function loadMore() {
        var url = wrapper.getAttribute('data-next-url');
        if (!url || loading) return;
        loading = true;
        if (spinner) spinner.classList.remove('d-none');

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.html) {
                var wrap = document.createElement('div');
                wrap.innerHTML = data.html.trim();
                while (wrap.firstChild) grid.appendChild(wrap.firstChild);
            }
            var total = parseInt(wrapper.getAttribute('data-total'), 10) || 0;
            var shown = grid.querySelectorAll('.video-card').length;
            if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' فيديو';
            if (data.has_more && data.next_page_url) {
                wrapper.setAttribute('data-next-url', data.next_page_url);
            } else {
                wrapper.setAttribute('data-next-url', '');
                if (sentinel) sentinel.style.display = 'none';
                if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' فيديو';
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

// تحميل المزيد عند التمرير لصفحة التصنيف (?content_category=...)
(function() {
    var grid = document.getElementById('homeCategoryGrid');
    var wrapper = document.getElementById('categoryLoadMoreWrapper');
    var countEl = document.getElementById('categoryLoadMoreCount');
    var sentinel = document.getElementById('categoryLoadMoreSentinel');
    var spinner = document.getElementById('categoryLoadMoreSpinner');
    if (!grid || !wrapper || !sentinel) return;

    var loading = false;

    function loadMore() {
        var url = wrapper.getAttribute('data-next-url');
        if (!url || loading) return;
        loading = true;
        if (spinner) spinner.classList.remove('d-none');

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.html) {
                var wrap = document.createElement('div');
                wrap.innerHTML = data.html.trim();
                while (wrap.firstChild) grid.appendChild(wrap.firstChild);
            }
            var total = parseInt(wrapper.getAttribute('data-total'), 10) || 0;
            var shown = grid.querySelectorAll('.video-card').length;
            if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' فيديو';
            if (data.has_more && data.next_page_url) {
                wrapper.setAttribute('data-next-url', data.next_page_url);
            } else {
                wrapper.setAttribute('data-next-url', '');
                if (sentinel) sentinel.style.display = 'none';
                if (countEl && total) countEl.textContent = 'عرض ' + shown + ' من ' + total + ' فيديو';
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

// Optimize image loading - no video loading needed anymore
document.querySelectorAll('img[loading="lazy"]').forEach((img, index) => {
    // Add fetchpriority for first few images
    if (index < 3) {
        img.setAttribute('fetchpriority', 'high');
    } else {
        img.setAttribute('fetchpriority', 'low');
    }
    
    // Handle image load
    if (img.complete && img.naturalHeight !== 0) {
        img.style.opacity = '1';
    } else {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        // Fallback to default image if error (handled in onerror attribute)
    }
});

</script>
@endpush
